<?php
/**
 * Manages actions related to the content.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_ContentController extends Zend_Controller_Action
{
    public $contexts = array(
        'change-state' => array('partial', 'json')
    );

    /**
     * Change workflow status (workflow state) on multiple content entries
     * in a batch.
     *
     * If 'forced' parameter is present in the request and equals to true,
     * then workflow  state will be changed only on those entries where its
     * possible, otherwise status is changed either on all or on none of the
     * specified entries.
     */
    public function changeStateAction()
    {
        $request  = $this->getRequest();
        $forced   = (bool) $request->getParam('forced');
        $form     = new Workflow_Form_ManageContent(
            array(
                'workflows' => $request->getParam('workflows'),
            )
        );

        // tweak manage content form decorators to act as a form
        $form->addDecorator('Form')
             ->removeDecorator('ContentPane');

        // set up the view
        $view       = $this->view;
        $view->form = $form;

        // populate the form from the request
        $form->populate($request->getParams());

        // if there are posted data, process the form
        if ($request->isPost()) {
            // if form is invalid, set response code and exit
            if (!$form->isValid($request->getParams())) {
                $this->getResponse()->setHttpResponseCode(400);
                $view->errors = $form->getMessages();
                return;
            }

            // get adapter for batch and fetch all entries to change state
            $adapter = P4Cms_Content::getDefaultAdapter();
            $entries = P4Cms_Content::fetchAll(array('ids' => (array) $form->getValue('ids')));

            // get all workflows by content type
            $workflowsByType = Workflow_Model_Workflow::fetchTypeMap();

            // attempt to change state on all entries in a batch
            $failedEntries = array();
            $adapter->beginBatch($form->getValue('comment') ?: 'No description provided.');
            foreach ($entries as $entry) {
                try {
                    // throw exception manually if entry has no workflow and
                    // target state is different from 'published'
                    $contentType = $entry->getContentTypeId();
                    $hasWorkflow = $contentType && isset($workflowsByType[$contentType]);
                    if (!$hasWorkflow && $form->getValue('state') !== Workflow_Model_State::PUBLISHED) {
                        throw new Exception;
                    }

                    if ($hasWorkflow) {
                        $entry->setValue('workflow', $form->getValues(true));
                    }

                    // create a new version of the content entry regardless of whether
                    // or not it has workflow as the user might expect a new mod-time
                    $entry->save();
                } catch (Exception $e) {
                    // cannot change state of given entry
                    // revert file and add entry to the failed entries list
                    $file = $entry->toP4File();
                    if ($file->isOpened() && $forced) {
                        $entry->toP4File()->revert();
                    }
                    $failedEntries[] = $entry->getId();
                }
            }

            // commit batch if forced or no failed entries, otherwise set response code
            // and revert the batch
            if ($forced || !$failedEntries) {
                $adapter->commitBatch();

                // clear any affected cached entries
                $tags           = array('p4cms_content_list');
                $changedEntries = array_diff($entries->invoke('getId'), $failedEntries);
                foreach ($changedEntries as $entryId) {
                    $tags[] = 'p4cms_content_' . bin2hex($entryId);
                }
                P4Cms_Cache::clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $tags);

                // pass list of changed entries to the view
                $view->changedIds = $changedEntries;
            } else {
                $adapter->revertBatch();
                $this->getResponse()->setHttpResponseCode(400);

                // pass list of failed entries to the view
                $view->failedIds = $failedEntries;
            }
        }
    }
}