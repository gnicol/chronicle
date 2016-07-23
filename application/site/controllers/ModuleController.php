<?php
/**
 * List, view and enable/disable modules for the current site.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Site_ModuleController extends Zend_Controller_Action
{
    public $contexts = array(
        'index'     => array('json', 'partial'),
    );

    /**
     * Work common to all module actions.
     */
    public function init()
    {
        // enforce permissions.
        $this->_helper->acl->check('site', 'manage-modules');

        // use management layout.
        $this->_helper->layout->setLayout('manage-layout');

        // enable logging of the moduleName parameter
        $this->getHelper('audit')->addLoggedParam('moduleName');

        // clear module cache in case it is stale.
        P4Cms_Module::clearCache();
    }

    /**
     * List available modules.
     *
     * @publishes   p4cms.site.module.grid.data.item
     *              Return the passed item after applying any modifications (add properties, change
     *              values, etc.) to influence the row values sent to the Manage Modules grid.
     *              array                       $item       The item to potentially modify.
     *              mixed                       $model      The original object/array that was used
     *                                                      to make the item.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.site.module.grid.data
     *              Adjust the passed data (add properties, modify values, etc.) to influence the
     *              row values sent to the Manage Modules grid.
     *              Zend_Dojo_Data              $data       The data to be filtered.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.site.module.grid.populate
     *              Adjust the passed iterator (possibly based on values in the passed form) to
     *              filter which modules will be shown on the Manage Modules grid.
     *              P4Cms_Model_Iterator        $modules    An iterator of P4Cms_Module objects.
     *              P4Cms_Form_PubSubForm       $form       A form containing filter options.
     *
     * @publishes   p4cms.site.module.grid.render
     *              Make adjustments to the datagrid helper's options pre-render (e.g. change
     *              options to add columns) for the Manage Modules grid.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.site.module.grid.form
     *              Make arbitrary modifications to the Manage Modules filters form.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.site.module.grid.form.subForms
     *              Return a Form (or array of Forms) to have them added to the Manage Modules
     *              filters form. The returned form(s) should have a 'name' set on them to allow
     *              them to be uniquely identified.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.site.module.grid.form.preValidate
     *              Allows subscribers to adjust the Manage Modules filters form prior to
     *              validation of the passed data. For example, modify element values based on
     *              related selections to permit proper validation.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.site.module.grid.form.validate
     *              Return false to indicate the Manage Modules filters form is invalid. Return true
     *              to indicate your custom checks were satisfied, so form validity should be
     *              unchanged.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.site.module.grid.form.populate
     *              Allows subscribers to adjust the Manage Modules filters form after it has been
     *              populated with the passed data.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     The values passed to the populate
     *                                                      method.
     */
    public function indexAction()
    {
        // get list option sub-forms.
        $request        = $this->getRequest();
        $gridNamespace  = 'p4cms.site.module.grid';
        $form           = new Ui_Form_GridOptions(
            array(
                'namespace'   => $gridNamespace
            )
        );
        $form->populate($request->getParams());

        // setup view.
        $view               = $this->view;
        $view->form         = $form;
        $view->query        = http_build_query($form->getValues());
        $view->pageSize     = $request->getParam('count', 100);
        $view->rowOffset    = $request->getParam('start', 0);
        $view->pageOffset   = round($view->rowOffset / $view->pageSize, 0) + 1;
        $view->headTitle()->set('Manage Modules');

        // set DataGrid view helper namespace
        $helper = $view->dataGrid();
        $helper->setNamespace($gridNamespace);

        // early exit for standard requests (ie. not json)
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->getHelper('helpUrl')->setUrl('modules.management.html');
            return;
        }

        // fetch modules and allow third-parties to manipulate the list
        $modules = P4Cms_Module::fetchAll();
        try {
            $result = P4Cms_PubSub::publish($gridNamespace . '.populate', $modules, $form);
        } catch (Exception $e) {
            P4Cms_Log::logException("Error building module list.", $e);
        }

        // prepare sorting options
        $sortKey    = $request->getParam('sort', 'name');
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

        // some column names differ from the model, so we need a map.
        $sortKeyMap = array(
            'name'          => 'name',
            'maintainer'    => array('maintainerInfo', 'name'),
            'status'        => array('enabled', 'name')
        );

        // look up requested sort column in our map.
        $sortKey = isset($sortKeyMap[$sortKey])
            ? $sortKeyMap[$sortKey]
            : 'name';

        // apply sorting options.
        $modules->sortBy($sortKey, $sortFlags);

        // add modules to the view.
        $view->modules = $modules;
    }

    /**
     * Enable a module.
     *
     * @publishes   p4cms.site.module.enabled
     *              Perform operations when a module is enabled by the Site module.
     *              P4Cms_Site      $site       The site for which the module is being enabled.
     *              P4Cms_Module    $module     The module being enabled.
     */
    public function enableAction()
    {
        // enforce permissions
        $this->acl->check('site', 'manage-modules');

        // only respond to post requests.
        $request = $this->getRequest();
        if (!$request->isPost()) {
            $this->_forward('index');
            return;
        }

        // instantiate given module as a model and enable it.
        $module = P4Cms_Module::fetch($request->getPost('moduleName'));
        $module->enable();

        // Module changes can have quite an affect; clear caches
        P4Cms_Cache::clean();

        // notify subscribers of enabled module event.
        $site = P4Cms_Site::fetchActive();
        P4Cms_PubSub::publish('p4cms.site.module.enabled', $site, $module);

        // notify user of successful module enable
        P4Cms_Notifications::add(
            $module->getName() . ' module successfully enabled.',
            P4Cms_Notifications::SEVERITY_SUCCESS
        );

        $this->redirector->gotoSimple('index');
    }

    /**
     * Disable a module.
     *
     * @publishes   p4cms.site.module.disabled
     *              Perform operations when a module is disabled by the Site module.
     *              P4Cms_Site      $site       The site for which the module is being disabled.
     *              P4Cms_Module    $module     The module being disabled.
     */
    public function disableAction()
    {
        // enforce permissions
        $this->acl->check('site', 'manage-modules');

        // only respond to post requests.
        $request = $this->getRequest();
        if (!$request->isPost()) {
            $this->_forward('index');
            return;
        }

        // instantiate given module as a model and disable it.
        $module = P4Cms_Module::fetch($request->getPost('moduleName'));
        $module->disable();

        // Module changes can have quite an affect; clear caches
        P4Cms_Cache::clean();

        // notify subscribers of enabled module event.
        $site = P4Cms_Site::fetchActive();
        P4Cms_PubSub::publish('p4cms.site.module.disabled', $site, $module);

        // notify user of successful module disable
        P4Cms_Notifications::add(
            $module->getName() . ' module successfully disabled.',
            P4Cms_Notifications::SEVERITY_SUCCESS
        );

        // back to index.
        $this->redirector->gotoSimple('index');
    }
}