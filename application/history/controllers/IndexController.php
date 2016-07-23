<?php
/**
 * Manages history operations (e.g. viewing historic version details).
 *
 * The controller can provide history details for any registered
 * P4Cms_Record based class (ie. P4Cms_Record_RegisteredType).
 * Record classes can be registered via pub/sub:
 *
 *  p4cms.record.registeredTypes
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class History_IndexController extends Zend_Controller_Action
{
    public $contexts = array(
        'index'     => array('json', 'partial'),
        'toolbar'   => array('partial'),
    );

    /**
     * List changes affecting the specified record.
     *
     * To provide an action for a given record type participants subscribe
     * to the 'p4cms.history.grid.actions' topic. Three arguments will be
     * passed to subscribers:
     *
     *     $type - P4Cms_Record_RegisteredType
     *             the type we are gathering actions for
     *   $record - The record being worked on
     *  $actions - P4Cms_Navigation
     *             a navigation container to hold all actions
     *
     * Subscribed callbacks are expected to add/modify/etc. pages to the
     * navigation container. The pages will be rendered to a Menu Dijit so
     * utilizing the onClick and, optionally, onShow events is advised to
     * control menu item behaviour.
     *
     * The default actions are added during module init. To modify/remove
     * a default action, subscribe during module load, or later, to ensure
     * the default navigation entries are already present.
     *
     * @publishes   p4cms.history.grid.actions
     *              Modify the passed menu (add/modify/delete items) to influence the actions shown
     *              on entries in the History grid.
     *              P4Cms_Navigation            $actions    A menu to hold grid actions.
     *
     * @publishes   p4cms.history.grid.data.item
     *              Return the passed item after applying any modifications (add properties, change
     *              values, etc.) to influence the row values sent to the History grid.
     *              array                       $item       The item to potentially modify.
     *              mixed                       $model      The original object/array that was used
     *                                                      to make the item.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.history.grid.data
     *              Adjust the passed data (add properties, modify values, etc.) to influence the
     *              row values sent to the History grid.
     *              Zend_Dojo_Data              $data       The data to be filtered.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.history.grid.populate
     *              Adjust the passed iterator (possibly based on values in the passed form) to
     *              filter which versions will be shown on the History grid.
     *              P4Cms_Model_Iterator        $changes    An iterator of P4_Change objects
     *              P4Cms_Form_PubSubForm       $form       A form containing filter options.
     *
     * @publishes   p4cms.history.grid.render
     *              Make adjustments to the datagrid helper's options pre-render (e.g. change
     *              options to add columns) for the History grid.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.history.grid.form
     *              Make arbitrary modifications to the History filters form.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.history.grid.form.subForms
     *              Return a Form (or array of Forms) to have them added to the History filters
     *              form. The returned form(s) should have a 'name' set on them to allow them to be
     *              uniquely identified.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.history.grid.form.preValidate
     *              Allows subscribers to adjust the History filters form prior to validation of the
     *              passed data. For example, modify element values based on related selections to
     *              permit proper validation.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.history.grid.form.validate
     *              Return false to indicate the History filters form is invalid. Return true to
     *              indicate your custom checks were satisfied, so form validity should be
     *              unchanged.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.history.grid.form.populate
     *              Allows subscribers to adjust the History filters form after it has been
     *              populated with the passed data.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     The values passed to the populate
     *                                                      method.
     */
    public function indexAction()
    {
        // setup grid options form.
        $request        = $this->getRequest();
        $type           = P4Cms_Record_RegisteredType::fetch($request->getParam('type'));
        $record         = $type->fetchRecord($request->getParam('id'), array('includeDeleted' => true));
        $gridNamespace  = 'p4cms.history.grid';

        $form           = new History_Form_HistoryGridOptions(
            array(
                'namespace' => $gridNamespace,
                'record'    => $record
            )
        );
        $form->populate($request->getParams());

        // setup view.
        $view               = $this->view;
        $view->form         = $form;
        $view->pageSize     = $request->getParam('count', 100);
        $view->rowOffset    = $request->getParam('start', 0);
        $view->pageOffset   = round($view->rowOffset / $view->pageSize, 0) + 1;
        $view->id           = $request->getParam('id');
        $view->type         = $request->getParam('type');
        $view->headTitle()->set('History');

        // set DataGrid view helper namespace
        $helper = $view->dataGrid();
        $helper->setNamespace($gridNamespace);

        // early exit for standard requests (ie. not json)
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->_helper->layout->setLayout('manage-layout');
            return;
        }

        // allow third-parties to influence list of changes
        $changes = $form->getChanges();
        try {
            P4Cms_PubSub::publish($gridNamespace . '.populate', $changes, $form);
        } catch (Exception $e) {
            P4Cms_Log::logException("Error building history list.", $e);
        }

        // prepare sorting options
        $sortKey    = $request->getParam('sort', '-Change');

        // we don't know the version at this stage; sort on ID instead
        if ($sortKey == 'version') {
            $sortKey = 'Change';
        } else if ($sortKey == '-version') {
            $sortKey = '-Change';
        }

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

        // apply sorting options.
        $changes->sortBy(ucfirst($sortKey), $sortFlags);

        // collect the actions from interested parties
        $actions = new P4Cms_Navigation;
        P4Cms_PubSub::publish($gridNamespace . '.actions', $type, $record, $actions);
        $view->actions = $actions;

        // add changes and record details to the view.
        $view->changes = $changes;
        $view->record  = $record;
    }

    /**
     * Get the sub-toolbar content for the specified type/id
     *
     * @publishes   p4cms.history.toolbar.actions
     *              Modify the passed menu (add/modify/delete items) to influence
     *              the actions shown on entries in the history toolbar.
     *              P4Cms_Record_RegisteredType $type       The type for which actions are being gathered.
     *              P4Cms_Record                $record     The record being worked on.
     *              P4Cms_Navigation            $actions    A menu to hold grid actions.
     */
    public function toolbarAction()
    {
        // force partial context.
        $this->contextSwitch->initContext('partial');

        $request = $this->getRequest();

        // if a version is present, generate rev-spec
        $revspec = $request->getParam('version');
        if ($revspec) {
            $revspec = '#' . $revspec;
        }

        $type    = P4Cms_Record_RegisteredType::fetch($request->getParam('type'));
        $record  = $type->fetchRecord($request->getParam('id') . $revspec, array('includeDeleted' => true));
        $file    = $record->toP4File();
        $headFile = P4_File::fetch(P4_File::stripRevspec($file->getFilespec()));
        $changes = $file->getChanges();
        $change  = $changes->filter(
            'Change',
            $file->getStatus('headChange'),
            P4Cms_Model_Iterator::FILTER_COPY
        )->first();

        // setup view.
        $view           = $this->view;
        $view->id       = $request->getParam('id');
        $view->version  = $request->getParam('version');
        $view->record   = $record;
        $view->file     = $file;
        $view->change   = $change;
        $view->changes  = $changes;
        $view->type     = $type;
        $view->headFile = $headFile;
        $view->rev      = $file->getStatus('headRev');
        $view->headRev  = $headFile->getStatus('headRev');

        // collect the actions from interested parties
        $actions = new P4Cms_Navigation;
        P4Cms_PubSub::publish('p4cms.history.toolbar.actions', $type, $record, $actions);
        $view->actions = $actions;
    }
}
