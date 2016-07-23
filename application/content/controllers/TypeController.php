<?php
/**
 * Manages content-type operations (e.g. list, add, edit, etc.).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_TypeController extends Zend_Controller_Action
{
    public $contexts = array(
        'index'     => array('partial', 'json'),
        'add'       => array('partial', 'dojoio'),
        'edit'      => array('partial', 'dojoio'),
    );

    /**
     * Use management layout for all actions
     */
    public function init()
    {
        // list of actions that will be skipped from the permissions check
        $skipActions = array('icon');

        // enforce permissions.
        if (!in_array($this->getRequest()->getActionName(), $skipActions)) {
            $this->_helper->acl->check('content', 'manage-types');
        }

        $this->getHelper('layout')->setLayout('manage-layout');
        $this->getHelper('audit')->addLoggedParam('id');
    }

    /**
     * List defined content types.
     *
     * @publishes   p4cms.content.type.grid.actions
     *              Modify the passed menu (add/modify/delete items) to influence the actions shown
     *              on entries in the Manage Content Types grid.
     *              P4Cms_Navigation            $actions    A menu to hold grid actions.
     *
     * @publishes   p4cms.content.type.grid.data.item
     *              Return the passed item after applying any modifications (add properties, change
     *              values, etc.) to influence the row values sent to the Manage Content Types grid.
     *              array                       $item       The item to potentially modify.
     *              mixed                       $model      The original object/array that was used
     *                                                      to make the item.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.content.type.grid.data
     *              Adjust the passed data (add properties, modify values, etc.) to influence the
     *              row values sent to the Manage Content Types grid.
     *              Zend_Dojo_Data              $data       The data to be filtered.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.content.type.grid.populate
     *              Adjust the passed query (possibly based on values in the passed form) to filter
     *              which content types will be shown on the Manage Content Types grid.
     *              P4Cms_Record_Query          $query      The query used to filter the content
     *                                                      types.
     *              P4Cms_Form_PubSubForm       $form       A form containing filter options.
     *
     * @publishes   p4cms.content.type.grid.render
     *              Make adjustments to the datagrid helper's options pre-render (e.g. change
     *              options to add columns) for the Manage Content Types grid.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.content.type.grid.form
     *              Make arbitrary modifications to the Manage Content Types filters form.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.content.type.grid.form.subForms
     *              Return a Form (or array of Forms) to have them added to the Manage Content Types
     *              filters form. The returned form(s) should have a 'name' set on them to allow
     *              them to be uniquely identified.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.content.type.grid.form.preValidate
     *              Allows subscribers to adjust the Manage Content Types filters form prior to
     *              validation of the passed data. For example, modify element values based on
     *              related selections to permit proper validation.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.content.type.grid.form.validate
     *              Return false to indicate the Manage Content Types filters form is invalid.
     *              Return true to indicate your custom checks were satisfied, so form validity
     *              should be unchanged.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.content.type.grid.form.populate
     *              Allows subscribers to adjust the Manage Content Types filters form after it has
     *              been populated with the passed data.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     The values passed to the populate
     *                                                      method.
     */
    public function indexAction()
    {
        // get list option sub-forms.
        $request        = $this->getRequest();
        $gridNamespace  = 'p4cms.content.type.grid';
        $form           = new Ui_Form_GridOptions(
            array(
                'namespace'   => $gridNamespace
            )
        );
        $form->populate($request->getParams());

        // setup view.
        $view               = $this->view;
        $view->form         = $form;
        $view->pageSize     = $request->getParam('count', 100);
        $view->rowOffset    = $request->getParam('start', 0);
        $view->pageOffset   = round($view->rowOffset / $view->pageSize, 0) + 1;
        $view->headTitle()->set('Manage Content Types');

        // set DataGrid view helper namespace
        $helper = $view->dataGrid();
        $helper->setNamespace($gridNamespace);

        // early exit for standard requests (ie. not json)
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->getHelper('helpUrl')->setUrl('content-types.html');
            // collect the actions from interested parties
            $actions = new P4Cms_Navigation;
            P4Cms_PubSub::publish($gridNamespace . '.actions', $actions);
            $view->actions = $actions;

            return;
        }

        // construct list query - allow third-parties to influence query.
        $query = new P4Cms_Record_Query;
        try {
            $result = P4Cms_PubSub::publish($gridNamespace . '.populate', $query, $form);
        } catch (Exception $e) {
            P4Cms_Log::logException("Error building content type list query.", $e);
        }

        // prepare sorting options
        $sortKey   = $request->getParam('sort', 'label');
        $sortFlags = array();

        // handle sort order; descending sort identified with '-' prefix.
        if (substr($sortKey, 0, 1) == '-') {
            $sortKey     = substr($sortKey, 1);
            $sortFlags[] = P4Cms_Record_Query::SORT_DESCENDING;
        }

        $query->setSortBy($sortKey, $sortFlags);

        // add types to the view.
        $view->types = P4Cms_Content_Type::fetchAll($query);
    }

    /**
     * Add a content type.
     *
     * The p4cms.content.type.form events documented on the index action will
     * also be broadcast when this action is accessed.
     */
    public function addAction()
    {
        $request    = $this->getRequest();
        $form       = new Content_Form_Type;
        $view       = $this->view;
        $view->form = $form;
        $view->headTitle()->set('Add Content Type');

        // if a valid type was posted, add it.
        // otherwise, present add type form.
        if ($request->isPost()) {
            // ensure id is unique
            if ($form->isValid($request->getParams())) {
                $id = $form->getValue('id');
                if (P4Cms_Content_Type::exists($id)) {
                    $form->getElement('id')->addError(
                        "The id you provided appears to be taken. Please choose a different id."
                    );
                }
            }

            // if form contains errors, set response code and exit
            if ($form->getMessages()) {
                $this->getResponse()->setHttpResponseCode(400);
                $view->errors = $form->getMessages();

                // retrieve the group value and set it as default in the form
                if (isset($values['group'])) {
                    $group   = $form->getElement('group');
                    $options = $group->getAttrib('options');
                    if (!in_array($values['group'], $options)) {
                        array_unshift($options, $values['group']);
                        $group->setAttrib('options', $options);
                    } else {
                        $group->setDijitParam('value', $values['group']);
                    }
                }

                return;
            }

            // create new type with collected values.
            $type = new P4Cms_Content_Type;
            $type->setValues($form);

            // save type.
            $type->save();

            // set notification message
            $view->message = "Content type '{$type->getValue('label')}' has been successfully added.";

            // for traditional requests, notify user and return to content type list.
            if (!$this->contextSwitch->getCurrentContext()) {
                P4Cms_Notifications::add(
                    $view->message,
                    P4Cms_Notifications::SEVERITY_SUCCESS
                );

                $this->redirector->gotoSimple('index');
            }
        }
    }

    /**
     * Edit a content type.
     */
    public function editAction()
    {
        // fetch the content type to be edited.
        $request = $this->getRequest();
        $type    = P4Cms_Content_Type::fetch($request->getParam('id'));

        // setup form - disable id field.
        $form       = new Content_Form_Type;
        $view       = $this->view;
        $view->form = $form;
        $form->getElement('id')
             ->setAttrib('disabled', true);

        // set the page title.
        $this->view->headTitle()->set("Edit Content Type");

        // always populate form from storage first to ensure
        // the form knows about existing icon file information
        $form->populate($type);

        // elements field gets set to array above,
        // clobber with text (INI) version
        $form->setDefault('elements', $type->getElementsAsIni());

        // if there is an existing icon, set thumbnail on the form.
        if ($type->hasIcon()) {
            $form->getElement('icon')->setExistingFileInfo(
                'iconUri',
                $this->_helper->url(
                    'icon',
                    'type',
                    'content',
                    array('id' => $type->getId())
                )
            );
        }

        // re-populate from request if posted.
        if ($request->isPost()) {
            $form->populate($request->getParams());
        }

        // if form was posted and is valid, save it.
        if ($request->isPost()) {
            // if form is invalid, set response code and exit
            if (!$form->isValid($request->getParams())) {
                $this->getResponse()->setHttpResponseCode(400);
                $view->errors = $form->getMessages();
                return;
            }

            $this->getHelper('audit')->addLoggedParams(
                array('label', 'group', 'description', 'elements', 'workflow', 'layout')
            );

            // set form values on type.
            $type->setValues($form);

            // save type record.
            $type->save();

            // clear any cached entries related to this type
            P4Cms_Cache::clean(
                Zend_Cache::CLEANING_MODE_MATCHING_TAG,
                array('p4cms_content_type_' . bin2hex($type->getId()))
            );

            // set notification message
            $view->message = "Content type '{$type->getLabel()}' has been updated.";

            // for traditional contexts, notify user and redirect to type list.
            if (!$this->contextSwitch->getCurrentContext()) {
                P4Cms_Notifications::add(
                    $view->message,
                    P4Cms_Notifications::SEVERITY_SUCCESS
                );
                $this->redirector->gotoSimple('index');
            }
        }
    }

    /**
     * Remove a content type.
     */
    public function deleteAction()
    {
        $request = $this->getRequest();
        $type    = P4Cms_Content_Type::fetch($request->getParam('id'));
        $label   = $type->getValue('label');
        $type->delete();
        P4Cms_Notifications::add(
            'Content type "'. $label .'" deleted.',
            P4Cms_Notifications::SEVERITY_SUCCESS
        );

        // clear any cached entries related to this type
        P4Cms_Cache::clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            array('p4cms_content_type_' . bin2hex($type->getId()))
        );

        $this->redirector->gotoSimple('index');
    }

    /**
     * Get the icon for a given content type.
     */
    public function iconAction()
    {
        // fetch the content type to serve the icon for.
        $request = $this->getRequest();
        $typeId  = $request->getParam('id');

        // attempt to locate the type, we will fall back to the
        // no icon case if the requested type cannot be located
        try {
            $type = $typeId
                ? P4Cms_Content_Type::fetch($typeId, array('includeDeleted' => true))
                : null;
        } catch (P4Cms_Record_NotFoundException $e) {
            $type = null;
        }

        // disable autorendering for this action.
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        // tag the page cache so it can be appropriately cleared later
        if (P4Cms_Cache::canCache('page')) {
            P4Cms_Cache::getCache('page')->addTag('p4cms_content_type')
                                         ->addTag('p4cms_content_type_' . bin2hex($typeId));
        }

        // if there is no image to serve, get the default icon; if unavailable, present 404.
        if (!$type || !$type->hasIcon()) {
            $iconFile = dirname(__DIR__) . "/resources/images/"
                      . ($type ? "default-type-icon.png" : "missing-type-icon.png");

            if (!is_readable($iconFile)) {
                $this->_forward('page-not-found', 'index', 'error');
                return;
            } else {
                $iconData = file_get_contents($iconFile);
                $metadata = array('mimeType' => 'image/png');
            }
        } else {
            $iconData = $type->getValue('icon');
            $metadata = $type->getFieldMetadata('icon');
        }

        // serve image.
        if (isset($metadata['mimeType'])) {
            $this->getResponse()->setHeader('Content-Type', $metadata['mimeType']);
        }

        print $iconData;
    }

    /**
     * Restore the default content types.
     */
    public function resetAction()
    {
        // enforce permissions.
        $this->acl->check('content', 'manage-types');

        // clean out existing types
        P4Cms_Content_Type::fetchAll()->invoke('delete');

        // re-install default types (clobber deleted = true)
        P4Cms_Content_Type::installDefaultTypes(null, true);

        // clear any cached entries related to content types
        P4Cms_Cache::clean(
            Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
            array('p4cms_content_type', 'p4cms_content')
        );

        P4Cms_Notifications::add(
            'Content Types Reset',
            P4Cms_Notifications::SEVERITY_SUCCESS
        );

        $this->redirector->gotoSimple('index');
    }
}
