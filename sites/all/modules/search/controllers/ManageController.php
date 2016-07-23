<?php
/**
 * Manages the search and index.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Search_ManageController extends Zend_Controller_Action
{
    protected $_activeIndexPath       = 'search-index';
    protected $_maintenanceLockFile   = 'search.maintenance.lock.file';
    protected $_maintenanceStatusFile = 'search.maintenance.status.file';
    protected $_statusFile            = null;

    const REBUILD_BATCH_SIZE = 100;

    public $contexts = array(
        'index'     => array('partial', 'json'),
        'status'    => array('json' => array('POST', 'GET'))
    );

    /**
     * Show a manage search page.
     */
    public function indexAction()
    {
        // enforce permissions.
        $this->acl->check('search', 'manage');

        $request = $this->getRequest();
        $form    = new Search_Form_Manage;

        // set up view
        $view       = $this->view;
        $view->form = $form;
        $view->headTitle()->set('Manage Search');

        // use manage layout for traditional contexts
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->getHelper('layout')->setLayout('manage-layout');
        }

        if ($request->isPost()) {
            $data = $request->getPost();

            if ($form->isValid($data)) {

                $maxBufferedDocs = $data['maxBufferedDocs'];
                $maxMergeDocs    = $data['maxMergeDocs'];
                $mergeFactor     = $data['mergeFactor'];

                $config = array();

                if (strlen($maxBufferedDocs) != 0) {
                    $config['maxBufferedDocs'] = $maxBufferedDocs;
                } else {
                    $config['maxBufferedDocs'] = Search_Module::getMaxBufferedDocs();
                }

                if (strlen($maxMergeDocs) != 0) {
                    $config['maxMergeDocs'] = $maxMergeDocs;
                } else {
                    $config['maxMergeDocs'] = Search_Module::getMaxMergeDocs();
                }

                if (strlen($mergeFactor) != 0) {
                    $config['mergeFactor'] = $mergeFactor;
                } else {
                    $config['mergeFactor'] = Search_Module::getMergeFactor();
                }

                $this->_saveConfig($config);

                P4Cms_Notifications::add(
                    'Search configuration saved.',
                    P4Cms_Notifications::SEVERITY_SUCCESS
                );

                // redirect for traditional requests
                if (!$this->contextSwitch->getCurrentContext()) {
                    $this->redirector->gotoSimple('index');
                }
            } else {
                $this->getResponse()->setHttpResponseCode(400);
                $view->errors = $form->getMessages();
            }
        } else {
            $data = array();

            $data['maxBufferedDocs'] = Search_Module::getMaxBufferedDocs();
            $data['maxMergeDocs']    = (Search_Module::getMaxMergeDocs()
                                        && (Search_Module::getMaxMergeDocs() != PHP_INT_MAX))
                                     ? Search_Module::getMaxMergeDocs()
                                     : '';
            $data['mergeFactor']     = Search_Module::getMergeFactor();

            $form->populate($data);
        }
    }

    /**
     * Provide a status update in Json format.
     */
    public function statusAction()
    {
        // enforce permissions.
        $this->acl->check('search', 'manage');

        $statusFile = P4Cms_Site::fetchActive()->getDataPath()
                    . '/' . $this->_maintenanceStatusFile;

        if (!file_exists($statusFile) ) {
            $status = array(
                'message' => 'Search Index maintenance task completed',
                'done' => true
            );
            $this->view->status = $status;
            return;
        }

        $status = $this->_readStatusFile();

        // for optimize, get the progress and merge it to the status file contents
        if (isset($status['action']) &&
            ($status['action'] == 'optimize') &&
            !$status['done']) {

            if (isset($status['index'])) {
                $status = array_merge(
                    $status,
                    $this->_getoptimizeProgress($status['index'])
                );
            } else {
                $status = array_merge($status, $this->_getoptimizeProgress());
            }
        }

        if (!array_key_exists('searchMaintenanceTask', $_SESSION)) {
            $status['message'] = "A Search Index '" . ucfirst($status['action'])
                               . "' operation is currently running. Its status and progress is below -- "
                               . $status['message'];
        }

        $this->contextSwitch->initContext('json');

        $this->view->status = $status;
    }

    /**
     * optimize the Lucene search index.
     */
    public function optimizeAction()
    {
        // enforce permissions.
        $this->acl->check('search', 'manage');

        // check if there is another maintenance task (optimize/rebuild)
        // running. if it is, redirect to the status page
        $maitenanceLockFile = P4Cms_Site::fetchActive()->getDataPath()
                            . '/' . $this->_maintenanceLockFile;

        if (file_exists($maitenanceLockFile)) {
            $redirector = $this->_helper->getHelper('redirector');
            $redirector->gotoSimple('status');
            return;
        }

        // create the maintenance lock file
        touch($maitenanceLockFile);

        P4Cms_Log::log(
            "optimize Search Index: BEGIN; pid=". getmypid(),
            P4Cms_Log::DEBUG
        );

        // put the current task in the session
        $_SESSION['searchMaintenanceTask'] = 'optimize';

        $this->_writeStatusFile(
            array(
                'action'  => 'optimize',
                'message' => 'Starting to optimize search index.',
                'time'    => time(),
                'done'    => false,
            )
        );

        // close the session but continue running, since index rebuilt may
        // take longer than browser timeout
        $this->getHelper('browserDisconnect')->disconnect('status', 10);

        $index = Search_Module::factory();
        $index->optimize();
        $this->_writeStatusFile(
            array(
                'action'  => 'optimize',
                'message' => 'Done. Search index optimization completed.',
                'time'    => time(),
                'done'    => true,
            )
        );
        unlink($maitenanceLockFile);
    }

    /**
     * Rebuild the Lucene search index.
     *
     * @publishes   p4cms.search.index.rebuild
     *              Return a Zend_Paginator of P4Cms_Content entries (or null) to be included when
     *              the search index is rebuilt.
     */
    public function rebuildAction()
    {
        // enforce permissions.
        $this->acl->check('search', 'manage');

        // check if there is another maintenance task (optimize/rebuild)
        // running. if it is, redirect to the status page
        $maitenanceLockFile = P4Cms_Site::fetchActive()->getDataPath()
                            . '/' . $this->_maintenanceLockFile;

        if (file_exists($maitenanceLockFile)) {
            $redirector = $this->_helper->getHelper('redirector');
            $redirector->gotoSimple('status');
            return;
        }

        // create the maintenance lock file
        touch($maitenanceLockFile);

        P4Cms_Log::log(
            "Rebuild Search Index: BEGIN; pid=". getmypid(),
            P4Cms_Log::DEBUG
        );

        // put the current task in the session
        $_SESSION['searchMaintenanceTask'] = 'rebuild';

        // put the status file's filename in the session
        $this->_statusFile = tempnam('/tmp', 'p4cms-search-rebuild.'. getmypid() .'.');
        $_SESSION['searchMaintenanceStatusFile'] = $this->_statusFile;

        $this->_writeStatusFile(
            array(
                'action'  => 'rebuild',
                'message' => 'Starting to rebuild search index.',
                'time'    => time(),
                'done'    => false,
            )
        );

        // close the session but continue running, since index rebuilt may
        // take longer than browser timeout
        $this->getHelper('browserDisconnect')->disconnect('status', 60 * 24);

        // clear the current index, if any
        // and create a new one
        $index = Search_Module::factory('temp-index');

        // publish the search index rebuild topic, expects subscribers to
        // return Zend_Paginator instances
        $feedbacks = P4Cms_PubSub::publish('p4cms.search.index.rebuild');

        $entryCount = 0;

        // get the total number of content entries
        foreach ($feedbacks as $feedback) {
            if ($feedback instanceof Zend_Paginator) {
                $entryCount += $feedback->getTotalItemCount();
            }
        }

        // start to rebuild the search index
        $count = 0;

        foreach ($feedbacks as $feedback) {
            // if the feedback is not a paginator as expected, skip it
            if (!$feedback instanceof Zend_Paginator) {
                continue;
            }

            $feedback->setItemCountPerPage(self::REBUILD_BATCH_SIZE);

            // for each page, get the items and index them
            for ($i = 1; $i <= $feedback->count(); $i++) {
                // set the current page
                $feedback->setCurrentPageNumber($i);

                // if there is no items in the current page, nothing to do
                if ($feedback->getCurrentItemCount() == 0) {
                    continue;
                }
                $itemCountPerPage = $feedback->getCurrentItemCount();

                // get a batch of entries
                $this->_writeStatusFile(
                    array(
                        'action'  => 'rebuild',
                        'message' => "Fetching the $i batch of $entryCount existing entries...",
                        'time'    => time(),
                        'done'    => false,
                    )
                );

                // get the items in the current page
                $items = $feedback->getCurrentItems();

                // update the status
                $this->_writeStatusFile(
                    array(
                        'action'  => 'rebuild',
                        'message' => "Starting to rebuild from the $i batch of $entryCount existing entries.",
                        'time'    => time(),
                        'done'    => false,
                    )
                );

                foreach ($items as $item) {
                    if (!$item instanceof Zend_Search_Lucene_Document) {
                        if (method_exists($item, 'toLuceneDocument')) {
                            try {
                                $item = $item->toLuceneDocument();
                            } catch (Zend_Filter_Exception $e) {
                                P4Cms_Log::logException(
                                    'Failed converting content to Lucene document.',
                                    $e
                                );

                                continue;
                            } catch (Zend_Search_Lucene_Exception $e) {
                                P4Cms_Log::logException(
                                    'Failed converting content to Lucene document.',
                                    $e
                                );

                                continue;
                            }
                        } else {
                            continue;
                        }
                    }

                    $index->addDocument($item);

                    $count++;

                    // update the status
                    $this->_writeStatusFile(
                        array(
                            'action'  => 'rebuild',
                            'message' => "Indexing content: number $count of $entryCount.",
                            'count'   => $count,
                            'total'   => $entryCount,
                            'time'    => time(),
                            'done'    => false,
                        )
                    );
                }
            }
        }

        // optimize the index after it's rebuilt
        $this->_writeStatusFile(
            array(
                'action'  => 'optimize',
                'index'   => 'temp-index',
                'message' => 'Optimizing the search index after rebuild.',
                'time'    => time(),
                'done'    => false,
            )
        );

        $index->optimize();

        $this->_writeStatusFile(
            array(
                'action'  => 'optimize',
                'index'   => 'temp-index',
                'message' => "Done.  Search index has been rebuilt.",
                'time'    => time(),
                'done'    => true
            )
        );
        Search_Module::clearSearchInstances();
        $this->_setActiveSearchIndex('temp-index');
        unlink($maitenanceLockFile);
    }

    /**
     * Read JSON-encoded information from temporary status file
     *
     * @return  mixed   The JSON-decoded contents from the status file.
     */
    private function _readStatusFile()
    {
        // put the status file's filename in the session
        $statusFile = P4Cms_Site::fetchActive()->getDataPath()
                    . '/' . $this->_maintenanceStatusFile;

        $status = '';
        if (file_exists($statusFile)) {
            $handle = fopen($statusFile, 'r');
            $content = fread($handle, 1024);
            fclose($handle);
            $status = Zend_Json::decode($content);
        }
        return $status;
    }

    /**
     * Write status information to the status file.
     *
     * @param  array   $data     The status data to report.
     */
    private function _writeStatusFile($data)
    {
        $statusFile = P4Cms_Site::fetchActive()->getDataPath()
                    . '/' . $this->_maintenanceStatusFile;

        $data['pid'] = getmypid();
        $handle = fopen($statusFile, 'w');
        fwrite($handle, Zend_Json::encode($data));
        fclose($handle);
    }

    /**
     * Save the search options
     *
     * @param array|Zend_Config  $config  the options
     */
    private function _saveConfig($config)
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        }

        $module = P4Cms_Module::fetch('Search');
        $module->saveConfig($config);
    }

    /**
     * Get the search options.
     *
     * @return array   the search options
     */
    private function _getConfig()
    {
        $module = P4Cms_Module::fetch('Search');
        $config = $module->getConfig();

        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        }

        return $config;
    }

    /**
     * Make a search index active.
     *
     * @param string $index  the search index directory
     * @return boolean       true,  if success;
     *                        false, otherwise
     */
    protected function _setActiveSearchIndex($index)
    {
        // if $index is not a string or it's an empty string
        // we cannot get search index
        $index = $this->_nomaliseIndexName($index);
        if (strlen($index) == 0) {
            throw new Zend_Search_Exception(
                'Require a folder name to set the active Search index.'
            );
        }

        $activeIndex = $this->_activeIndexPath;

        // nothing to do if the index given is the active index
        if ($index == $activeIndex) {
            return true;
        }

        // remove the active index contents
        if (!$this->_removeSearchIndex($activeIndex)) {
            throw new Zend_Search_Exception(
                "Failed removing the active search index: $activeIndex."
            );
        }

        $dataPath   = P4Cms_Site::fetchActive()->getDataPath() . '/';
        $newPath    = $dataPath . $index;
        $activePath = $dataPath . $activeIndex;

        return rename($newPath, $activePath);
    }

    /**
     * Normalise a Lucene search index name.
     * - Remove extra spaces on both ends.
     * - Remove any slashes ('/', '\') on both ends.
     *
     * @param  string $index  the original index name
     * @return string         the index name with spaces and slashes removed
     *                         from both ends
     */
    protected static function _nomaliseIndexName($index)
    {
        // if the name is not a string
        if (!is_string($index)) {
            return '';
        }

        // trim spaces and slashes
        $index = trim($index, " \t\n\r\0\x0B/\\");

        return $index;
    }

    /**
     * Remove the search index by deleting all files from its folder on disk.
     *
     * @param   string  $indexName   the folder name of a search index
     * @return  boolean          true,  if success
     *                            false, otherwise
     */
    protected function _removeSearchIndex($indexName)
    {
        // if the index folder is an empty string, nothing to do
        if (strlen($indexName) == 0) {
            return true;
        }

        $indexDirectory = P4Cms_Site::fetchActive()->getDataPath() . '/' . $indexName;

        // if the index does not exist, nothing to do
        if (!file_exists($indexDirectory)) {
            return true;
        }

        $files = scandir($indexDirectory);

        // remove all files in the search index folder
        foreach ($files as $file) {
            if (is_dir($file)) {
                continue;
            }

            unlink($indexDirectory . '/' . $file);
        }

        return rmdir($indexDirectory);
    }

    /**
     * Get the Search index optimization progress by observing the file
     * size change in the search index folder.
     *
     * When optimizing the search index, Zend Lucene Search creates six
     * new files: .fdt, .fdx, .frq, .prx, .tii, .tis
     *
     * The size of these file will increase during the optimization and
     * the total size goes towards the sum size of the .cfs files
     * in the index folder.
     *
     * After the optimization, these files will be removed and there will
     * be only one .cfs file in the directory.
     *
     * @param  string $indexName  the index whose progress is needed
     * @return array              the optimization status
     */
    private function _getoptimizeProgress($indexName = null)
    {
        // get the search index directory
        $index = Search_Module::factory($indexName);
        $directory = $index->getDirectory();

        // get all files in the directory
        $files = $directory->fileList();

        // set the progress total and current count
        $total = 0;
        $count = 0;
        foreach ($files as $file) {
            $extention = pathinfo($file, PATHINFO_EXTENSION);

            switch ($extention) {
                case 'cfs':
                    $total += $directory->fileLength($file);
                    break;
                case 'sti':
                    break;
                case 'fdt':
                case 'fdx':
                case 'frq':
                case 'prx':
                case 'tii':
                case 'tis':
                    $count += $directory->fileLength($file);
                    break;
                default:
                    break;
            }
        }

        // if we are not dealing with the active search index
        // and the total we got is 0, we try to get the total
        // from the active index.
        if (($total == 0) && $indexName) {
            $index = Search_Module::factory();
            $directory = $index->getDirectory();

            // get all files in the directory
            $files = $directory->fileList();

            // set the progress total and current count
            foreach ($files as $file) {
                $extention = pathinfo($file, PATHINFO_EXTENSION);

                if ($extention == 'cfs') {
                    $total += $directory->fileLength($file);
                }
            }
        }

        $status = array(
            'total'   => $total,
            'count'   => $count,
            'message' => 'Optimizing the search index...'
        );

        return $status;
    }
}
