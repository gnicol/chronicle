<?php
/**
 * Provides system information.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class System_IndexController extends Zend_Controller_Action
{
    public $contexts = array(
        'md5'       => array('json')
    );

    /**
     * Add parameters used in the md5 action to the audit log.
     */
    public function init()
    {
        $this->getHelper('audit')->addLoggedParams(array('target', 'type'));
    }

    /**
     * Index action handles permission check, view setup, and publishing
     * of the p4cms.system.info topic.
     *
     * @publishes   p4cms.system.info
     *              Allows subscribers to add additional System_Model_Info models to the passed
     *              iterator, or modify any existing entries, to influence the data available on the
     *              System Info management page.
     *              P4Cms_Model_Iterator    $info   The list of System_Model_Info objects.
     */
    public function indexAction()
    {
        $this->acl->check('system', 'view');

        // collect system information from participating modules
        $info = new P4Cms_Model_Iterator();
        P4Cms_PubSub::publish('p4cms.system.info', $info);
        $info->sortBy(array('order'));

        $this->view->info = $info;

        // include styles for printing system info here; we don't want them aggregated
        // or otherwise included in any other request
        $baseUrl = P4Cms_Module::fetch('system')->getBaseUrl();
        $this->view->headLink()->appendStylesheet(
            "$baseUrl/print.css",
            'print'
        );

        $this->getHelper('helpUrl')->setUrl('site.system-information.html');
    }

    /**
     * Action to handle asynchronous loading of md5 comparisons.
     */
    public function md5Action()
    {
        $this->acl->check('system', 'view');
        $this->contextSwitch->initContext('json');

        $target     = $this->getRequest()->getParam('target');
        $type       = $this->getRequest()->getParam('type');

        if ($type == 'library') {
            $target = LIBRARY_PATH . DIRECTORY_SEPARATOR . $target;
        } else if ($type == 'theme') {
            $target = P4Cms_Theme::fetch($target);
        } else if ($type == 'module') {
            $target = P4Cms_Module::fetch($target);
        } else {
            throw new Exception('Invalid type ' . $type . ', cannot calculate md5 sum.');
        }

        if ($target instanceof P4Cms_PackageAbstract) {
            $target = $target->getPath();
        }

        $this->view->md5Data = $this->_formatMd5Data($target, $type);
    }

    /**
     * Delete page, resource, package, etc. caches.
     */
    public function clearCacheAction()
    {
        $this->acl->check('system', 'clear-cache');

        $cachePaths = array(
            DATA_PATH . '/cache',
            DATA_PATH . '/resources'
        );

        foreach ($cachePaths as $path) {
            P4Cms_FileUtility::deleteRecursive($path);
        }

        $this->redirector->gotoUrl($this->getRequest()->getBaseUrl());
    }

    /**
     * Obtains and formats md5 data for a target directory and compares to the
     * md5 data found in the $fileprefix.md5 file in the same directory.
     *
     * @param string $targetPath    The path to the target for which to obtain the md5 data.
     * @param string $filePrefix    What type of object, for figuring out the name of the packaged md5 file.
     * @return array An array of information regarding the md5 comparison.
     */
    protected function _formatMd5Data($targetPath, $filePrefix)
    {
        $md5FileName = $filePrefix . '.md5';
        $md5FilePath = $targetPath . DIRECTORY_SEPARATOR . $md5FileName;

        if (is_readable($md5FilePath)) {
            $calculatedMd5 = P4Cms_FileUtility::md5Recursive(
                $targetPath,
                null,
                array($md5FileName)
            );

            $originalMd5    = System_Module::fetchMd5($md5FilePath);
            $originalMd5    = explode("\n", $originalMd5);

            $md5Diff        = array_diff($originalMd5, $calculatedMd5);
            $md5Info            = array(
                'displayClass'  => count($md5Diff) ? 'bad' : 'good',
                'details'       => count($md5Diff) > 0 ? array_values($md5Diff) : array('MD5 check ok')
            );
        } else {
            P4Cms_Log::log(
                'Could not read md5 file ' . $md5FileName . ' for ' . $filePrefix . ' ' . $targetPath . '.',
                P4Cms_Log::DEBUG
            );

            $md5Info = array(
                'displayClass'  => 'warn',
                'details'       => array('No MD5 sums are available to check for this ' . $filePrefix . '.')
            );
        }

        return $md5Info;
    }
}