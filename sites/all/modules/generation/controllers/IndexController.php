<?php
/**
 * Handles content generation via the generation controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Generation_IndexController extends Zend_Controller_Action
{
    const BATCH_SIZE    = 100;
    const STATUS_FILE   = 'generation.status.file';
    public    $contexts = array(
        'index'     => array('partial'),
        'status'    => array('json')
    );

    /**
     * Show form and handle generation
     */
    public function indexAction()
    {
        $this->acl->check('content', 'manage');
        $this->getHelper('layout')->setLayout('manage-layout');

        $request    = $this->getRequest();
        $form       = new Generation_Form_Configure();
        $view       = $this->view;
        $view->form = $form;

        $view->headTitle()->set('Content Generation');

        if ($request->isPost() && $form->isValid($request->getPost())) {
            $targetEntryCount = $form->getValue('count');

            $view->showStatus = getmypid();
            $this->getHelper('layout')->direct()->content = $this->view->render('index/index.phtml');
            echo $this->getHelper('layout')->render();

            // write the first status update now, so it's available when we disconnect from the browser.
            $this->_writeStatusFile(
                array(
                    'message' => 'Starting to generate content entries.',
                    'time'    => time(),
                    'done'    => false,
                    'count'   => 0,
                    'total'   => $targetEntryCount
                )
            );

            // disconnect the browser and continue
            $this->getHelper('browserDisconnect')->disconnect();
            $this->getHelper('layout')->disableLayout();
            $this->getHelper('viewRenderer')->setNoRender(true);

            $adapter            = P4Cms_Content::getDefaultAdapter();
            $currentEntryCount  = 0;
            $batchEntryCount    = 0;
            $batchCounter       = 0;

            // create entries until we hit the requested amount; submit out batches every BATCH_SIZE entries
            while ($currentEntryCount < $targetEntryCount) {
                $currentEntryCount++;
                $this->_writeStatusFile(
                    array(
                        'message' => 'Generating content entries.',
                        'time'    => time(),
                        'done'    => false,
                        'count'   => $currentEntryCount,
                        'total'   => $targetEntryCount
                    )
                );

                if (!$adapter->inBatch()) {
                    $batchCounter++;
                    $adapter->beginBatch(
                        'Generating ' . $targetEntryCount . ' content entries.  '
                        . 'Batch ' . $batchCounter . ' of ' . ceil($targetEntryCount / static::BATCH_SIZE) . '.'
                    );
                }

                $generator = new Generation_ContentFactory();
                $generator->createEntry()->save();
                $batchEntryCount++;

                // end and start a new batch if current one is full
                if ($batchEntryCount == static::BATCH_SIZE) {
                    $batchEntryCount = 0;
                    // this can take some time with no progress bar update; inform the user
                    $this->_writeStatusFile(
                        array(
                            'message' => 'Submitting content entries.',
                            'time'    => time(),
                            'done'    => false,
                            'count'   => $currentEntryCount,
                            'total'   => $targetEntryCount
                        )
                    );
                    $adapter->commitBatch();
                }
            }

            // commit or revert open batch
            if ($adapter->inBatch()) {
                // Write status to inform user; count is set to 99% of the total count so the user can tell that further
                // action is happening.
                $this->_writeStatusFile(
                    array(
                        'message' => 'Submitting content entries.',
                        'time'    => time(),
                        'done'    => false,
                        'count'   => $currentEntryCount,
                        'total'   => $targetEntryCount
                    )
                );
                $adapter->commitBatch();
            }

            $this->_writeStatusFile(
                array(
                    'message' => 'Content generation completed.',
                    'time'    => time(),
                    'done'    => true
                )
            );
        }
    }

    /**
     * Provide a status update in Json format.
     */
    public function statusAction()
    {
        // enforce permissions.
        $this->acl->check('content', 'manage');

        // set context
        $this->contextSwitch->initContext('json');

        $processId  = $this->getRequest()->getParam('processId');
        $statusFile = sys_get_temp_dir() . '/' . static::STATUS_FILE . $processId;

        if (!file_exists($statusFile) ) {
            $status = array(
                'label'   => 'no status',
                'message' => 'No current status file.',
                'done'    => true
            );
            $this->view->status = $status;
            return;
        }

        $this->view->status = $this->_readStatusFile($processId);
    }

    /**
     * Read JSON-encoded information from temporary status file
     *
     * @param   string      $processId  The process for which to read the status.
     * @return  mixed       The JSON-decoded contents from the status file.
     */
    private function _readStatusFile($processId)
    {
        $statusFile = sys_get_temp_dir() . '/' . static::STATUS_FILE . $processId;

        $status = '';
        if (file_exists($statusFile)) {
            $content = file_get_contents($statusFile);
            $status = Zend_Json::decode($content);
        }
        return $status;
    }

    /**
     * Write status information to the status file.
     *
     * @param  array   $data      The status data to report.
     * @param  string  $processId The process for which to write the status.  If empty, use current process.
     */
    private function _writeStatusFile($data, $processId = null)
    {
        $processId = ($processId) ?: getmypid();

        $statusFile = sys_get_temp_dir() . '/' . static::STATUS_FILE . $processId;

        file_put_contents($statusFile, Zend_Json::encode($data));
    }
}
