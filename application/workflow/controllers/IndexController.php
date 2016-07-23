<?php
/**
 * Manages workflows.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_IndexController extends Zend_Controller_Action
{
    public $contexts = array(
        'index'     => array('json'),
        'add'       => array('partial', 'json'),
        'edit'      => array('partial', 'json'),
        'delete'    => array('json'),
    );

    /**
     * Use management layout for all actions.
     */
    public function init()
    {
        $this->_helper->layout->setLayout('manage-layout');
    }

    /**
     * List all workflows.
     *
     * @publishes   p4cms.workflow.grid.actions
     *              Modify the passed menu (add/modify/delete items) to influence the actions shown
     *              on entries in the Manage Workflows grid.
     *              P4Cms_Navigation            $actions    A menu to hold grid actions.
     *
     * @publishes   p4cms.workflow.grid.data.item
     *              Return the passed item after applying any modifications (add properties, change
     *              values, etc.) to influence the row values sent to the Manage Workflows grid.
     *              array                       $item       The item to potentially modify.
     *              mixed                       $model      The original object/array that was used
     *                                                      to make the item.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.workflow.grid.data
     *              Adjust the passed data (add properties, modify values, etc.) to influence the
     *              row values sent to the Manage Workflows grid.
     *              Zend_Dojo_Data              $data       The data to be filtered.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.workflow.grid.populate
     *              Adjust the passed iterator (possibly based on values in the passed form) to
     *              filter which workflows will be shown on the Manage Workflows grid.
     *              P4Cms_Model_Iterator        $workflows  An iterator of Workflow_Model_Workflow
     *                                                      objects.
     *              P4Cms_Form_PubSubForm       $form       A form containing filter options.
     *
     * @publishes   p4cms.workflow.grid.render
     *              Make adjustments to the datagrid helper's options pre-render (e.g. change
     *              options to add columns) for the Manage Workflows grid.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.workflow.grid.form
     *              Make arbitrary modifications to the Manage Workflows filters form.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.workflow.grid.form.subForms
     *              Return a Form (or array of Forms) to have them added to the Manage Workflows
     *              filters form. The returned form(s) should have a 'name' set on them to allow
     *              them to be uniquely identified.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.workflow.grid.form.preValidate
     *              Allows subscribers to adjust the Manage Workflows filters form prior to
     *              validation of the passed data. For example, modify element values based on
     *              related selections to permit proper validation.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.workflow.grid.form.validate
     *              Return false to indicate the Manage Workflows filters form is invalid. Return
     *              true to indicate your custom checks were satisfied, so form validity should be
     *              unchanged.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.workflow.grid.form.populate
     *              Allows subscribers to adjust the Manage Workflows filters form after it has
     *              been populated with the passed data.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     The values passed to the populate
     *                                                      method.
     */
    public function indexAction()
    {
        // enforce permissions
        $this->acl->check('workflows', 'manage');

        // setup list options form
        $request        = $this->getRequest();
        $gridNamespace  = 'p4cms.workflow.grid';
        $form           = new Ui_Form_GridOptions(
            array(
                'namespace'   => $gridNamespace
            )
        );
        $form->populate($request->getParams());

        // setup view
        $view               = $this->view;
        $view->form         = $form;
        $view->pageSize     = $request->getParam('count', 100);
        $view->rowOffset    = $request->getParam('start', 0);
        $view->pageOffset   = round($view->rowOffset / $view->pageSize, 0) + 1;
        $view->headTitle()->set('Manage Workflows');

        // collect the actions from interested parties
        $actions = new P4Cms_Navigation;
        P4Cms_PubSub::publish($gridNamespace . '.actions', $actions);
        $view->actions = $actions;

        // set DataGrid view helper namespace
        $helper = $view->dataGrid();
        $helper->setNamespace($gridNamespace);

        // early exit for standard requests
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->getHelper('helpUrl')->setUrl('workflows.manage.html');
            return;
        }

        // fetch workflows - allow third-parties to influence list
        $workflows = Workflow_Model_Workflow::fetchAll();

        try {
            P4Cms_PubSub::publish($gridNamespace . '.populate', $workflows, $form);
        } catch (Exception $e) {
            P4Cms_Log::logException("Error building workflows list.", $e);
        }

        // prepare sorting options
        $sortKey    = $request->getParam('sort', 'label');
        $sortFlags  = array(
            P4Cms_Model_Iterator::SORT_NATURAL,
            P4Cms_Model_Iterator::SORT_NO_CASE
        );
        if (substr($sortKey, 0, 1) == '-') {
            $sortKey = substr($sortKey, 1);
            $sortFlags[] = P4Cms_Model_Iterator::SORT_DESCENDING;
        } else {
            $sortFlags[] = P4Cms_Model_Iterator::SORT_ASCENDING;
        }

        // apply sorting options
        $workflows->sortBy($sortKey, $sortFlags);

        // add workflows to the view
        $view->workflows = $workflows;
    }

    /**
     * Add new workflow.
     */
    public function addAction()
    {
        // enforce permissions
        $this->acl->check('workflows', 'manage');

        // set up view
        $request            = $this->getRequest();
        $form               = new Workflow_Form_Workflow;
        $view               = $this->view;
        $view->form         = $form;
        $view->headTitle()->set('Add Workflow');

        if ($request->isPost()) {
            // if form is valid populate the form and verify id is unique
            if ($form->isValid($request->getParams())) {
                $form->populate($request->getParams());

                // ensure id is unique
                $id = $form->getValue('id');
                if (Workflow_Model_Workflow::exists($id)) {
                    $form->getElement('id')->addError(
                        "The id you provided appears to be taken. Please choose a different id."
                    );
                }
            }

            // if form contains errors, set response code and exit
            if ($form->getMessages()) {
                $this->getResponse()->setHttpResponseCode(400);
                $view->errors = $form->getMessages();
                return;
            }

            // save new workflow
            $workflow = new Workflow_Model_Workflow;
            $workflow->setValues($form->getValues())
                     ->save();

            // set notification message
            $view->message = "Workflow '{$workflow->getId()}' has been successfully added.";

            // for traditional requests, add notification message and redirect
            if (!$this->contextSwitch->getCurrentContext()) {
                P4Cms_Notifications::add(
                    $view->message,
                    P4Cms_Notifications::SEVERITY_SUCCESS
                );
                $this->redirector->gotoSimple('manage');
            }
        }
    }

    /**
     * Edit existing workflow.
     */
    public function editAction()
    {
        // enforce permissions
        $this->acl->check('workflows', 'manage');

        // set up view
        $request            = $this->getRequest();
        $form               = new Workflow_Form_Workflow;
        $view               = $this->view;
        $view->form         = $form;
        $view->headTitle()->set('Edit Workflow');

        // fetch workflow to edit
        $workflowId         = $request->getParam('id');
        $workflow           = Workflow_Model_Workflow::fetch($workflowId);

        // populate form from post if available, otherwise from storage
        if ($request->isPost()) {
            $form->populate($request->getParams());
        } else {
            // present states field in INI format
            $values           = $workflow->getValues();
            $values['states'] = $workflow->getStatesAsIni();
            $form->populate($values);
        }

        // disable the id field
        $form->getElement('id')
             ->setAttrib('disabled', true);

        if ($request->isPost()) {
            // if form is invalid, set response code and exit
            if (!$form->isValid($request->getParams())) {
                $this->getResponse()->setHttpResponseCode(400);
                $view->errors = $form->getMessages();
                return;
            }

            // save updated workflow
            $workflow->setValues($form->getValues())
                     ->save();

            // set notification message
            $view->message = "Workflow '{$workflow->getId()}' has been successfully updated.";

            // for traditional requests, add notification message and redirect
            if (!$this->contextSwitch->getCurrentContext()) {
                P4Cms_Notifications::add(
                    $view->message,
                    P4Cms_Notifications::SEVERITY_SUCCESS
                );
                $this->redirector->gotoSimple('manage');
            }
        }
    }

    /**
     * Delete workflow record. Available only via post.
     */
    public function deleteAction()
    {
        // enforce permissions
        $this->acl->check('workflows', 'manage');

        // deny if not accessed via post
        $request = $this->getRequest();
        if (!$request->isPost()) {
            throw new P4Cms_AccessDeniedException(
                "Deleting workflows is not permitted in this context."
            );
        }

        $id       = $request->getParam('id');
        $workflow = Workflow_Model_Workflow::fetch($id);

        // delete workflow record
        $workflow->delete();

        // set notification and redirect for traditional requests
        if (!$this->contextSwitch->getCurrentContext()) {
            P4Cms_Notifications::add(
                'Workflow "'. $workflow->getValue('label') .'" has been deleted.',
                P4Cms_Notifications::SEVERITY_SUCCESS
            );
            return $this->redirector->gotoSimple('manage');
        }

        $this->view->workflowId = $id;
    }

    /**
     * Restore the default workflows.
     */
    public function resetAction()
    {
        // enforce permissions.
        $this->acl->check('workflows', 'manage');

        // clean out existing workflows
        Workflow_Model_Workflow::fetchAll()->invoke('delete');

        // re-install default types
        Workflow_Model_Workflow::installDefaultWorkflows();

        P4Cms_Notifications::add(
            'Workflows Reset',
            P4Cms_Notifications::SEVERITY_SUCCESS
        );

        // redirect to workflows management page
        $this->redirector->gotoSimple('manage');
    }
}
