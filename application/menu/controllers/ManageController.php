<?php
/**
 * Manages menu operations (e.g. add/edit/delete of menus and menu entries).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_ManageController extends Zend_Controller_Action
{
    public  $contexts   =  array(
        'index'         => array('json'    => 'get'),
        'add'           => array('partial' => 'get', 'json' => 'post'),
        'edit'          => array('partial' => 'get', 'json' => 'post'),
        'delete'        => array('json'    => 'post'),
        'item-form'     => array('partial'),
        'add-item'      => array('partial' => 'get', 'json' => 'post'),
        'edit-item'     => array('partial' => 'get', 'json' => 'post'),
        'delete-item'   => array('json'    => 'post'),
        'reset'         => array('json'    => 'post'),
        'reorder'       => array('json'    => 'post')
    );

    /**
     * Use management layout for all actions.
     */
    public function init()
    {
        $this->getHelper('layout')->setLayout('manage-layout');
        $this->getHelper('audit')->addLoggedParams(array('menuId', 'label'));
    }

    /**
     * List menus and entries.
     *
     * @publishes   p4cms.menu.grid.actions
     *              Modify the passed menu (add/modify/delete items) to influence the actions shown
     *              on entries in the Manage Menus grid.
     *              P4Cms_Navigation            $actions    A menu to hold grid actions.
     *
     * @publishes   p4cms.menu.grid.data.item
     *              Return the passed item after applying any modifications (add properties, change
     *              values, etc.) to influence the row values sent to the Manage Menus grid.
     *              array                       $item       The item to potentially modify
     *              mixed                       $model      The original object/array that was used
     *                                                      to make the item.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.menu.grid.data
     *              Adjust the passed data (add properties, modify values, etc.) to influence the
     *              row values sent to the Manage Menus grid.
     *              Zend_Dojo_Data              $data       The data to be filtered.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.menu.grid.populate
     *              Adjust the passed iterator (possibly based on values in the passed form) to
     *              filter which menus will be shown on the Manage Menus grid.
     *              P4Cms_Model_Iterator        $mixed      An iterator of P4Cms_Menu_Mixed objects.
     *              P4Cms_Form_PubSubForm       $form       A form containing filter options.
     *
     * @publishes   p4cms.menu.grid.render
     *              Make adjustments to the datagrid helper's options pre-render (e.g. change
     *              options to add columns) for the Manage Menus grid.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.menu.grid.form
     *              Make arbitrary modifications to the Manage Menus filters form.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.menu.grid.form.subForms
     *              Return a Form (or array of Forms) to have them added to the Manage Menus filters
     *              form. The returned form(s) should have a 'name' set on them to allow them to be
     *              uniquely identified.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.menu.grid.form.preValidate
     *              Allows subscribers to adjust the Manage Menus filters form prior to validation
     *              of the passed data. For example, modify element values based on related
     *              selections to permit proper validation.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.menu.grid.form.validate
     *              Return false to indicate the Manage Menus filters form is invalid. Return true
     *              to indicate your custom checks were satisfied, so form validity should be
     *              unchanged.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.menu.grid.form.populate
     *              Allows subscribers to adjust the Manage Menus filters form after it has been
     *              populated with the passed data.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     The values passed to the populate
     *                                                      method.
     */
    public function indexAction()
    {
        // enforce permissions.
        $this->acl->check('menus', 'manage');

        // setup list options form
        $request        = $this->getRequest();
        $gridNamespace  = 'p4cms.menu.grid';
        $form           = new Ui_Form_GridOptions(
            array(
                'namespace'   => $gridNamespace
            )
        );
        $form->populate($request->getParams());

        // set up view
        $view               = $this->view;
        $view->form         = $form;
        $view->pageSize     = $request->getParam('count', 100);
        $view->rowOffset    = $request->getParam('start', 0);
        $view->pageOffset   = round($view->rowOffset / $view->pageSize, 0) + 1;
        $view->headTitle()->set('Manage Menus');

        // collect the actions from interested parties
        $actions = new P4Cms_Navigation;
        P4Cms_PubSub::publish($gridNamespace . '.actions', $actions);
        $view->actions = $actions;

        // set DataGrid view helper namespace
        $helper = $view->dataGrid();
        $helper->setNamespace($gridNamespace);

        // early exit for standard requests (ie. not json)
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->getHelper('helpUrl')->setUrl('navigation.html');
            return;
        }

        // fetch menus and make a copy for later use
        $items = P4Cms_Menu::fetchMixed();
        $copy  = new P4Cms_Model_Iterator($items->getArrayCopy());

        // allow third-parties to influence list
        try {
            P4Cms_PubSub::publish($gridNamespace . '.populate', $items, $form);
        } catch (Exception $e) {
            P4Cms_Log::logException("Error building menus list.", $e);
        }

        // restore menus hierarchy by appending missing ancestors to the
        // filtered list
        $items = $this->_restoreObligatory($items, $copy);

        // compose list of sorted items
        $view->items = $items;
    }

    /**
     * Add menu
     */
    public function addAction()
    {
        // enforce permissions.
        $this->acl->check('menus', 'manage');

        $request          = $this->getRequest();
        $menu             = new P4Cms_Menu;
        $form             = new Menu_Form_Menu;
        $this->view->form = $form;

        if ($request->isPost()) {
            // if a valid data were posted, try to update the menu
            if ($form->isValid($request->getPost())) {

                // error out if menu already exists, otherwise save addition
                if (P4Cms_Menu::exists($request->getParam('id'))) {
                    $form->getElement('id')->addError(
                        "The specified ID is already in use."
                    );
                } else {
                    $menu->setValues($form->getValues());
                    $menu->save();
                }
            }

            // if form has messages; set error code, pass messages and exit
            if ($form->getMessages()) {
                $this->getResponse()->setHttpResponseCode(400);
                $view->errors = $form->getMessages();
                return;
            }
        }
    }

    /**
     * Edit menu
     */
    public function editAction()
    {
        // enforce permissions.
        $this->acl->check('menus', 'manage');

        $request          = $this->getRequest();
        $menu             = P4Cms_Menu::fetch($request->getParam('id'));
        $form             = new Menu_Form_Menu;
        $this->view->form = $form;
        $form->getElement('id')
             ->setAttrib('disabled', true);

        $form->populate($menu->getValues());

        if ($request->isPost()) {
            // if a valid data were posted, try to update the menu
            if ($form->isValid($request->getParams())) {
                $menu->setValues($form->getValues());
                $menu->save();
            }

            // clear any cached entries related to content types
            P4Cms_Cache::clean(
                Zend_Cache::CLEANING_MODE_MATCHING_TAG,
                array('p4cms_menu_' . bin2hex($menu->getId()))
            );

            // if form has messages; set error code, pass messages and exit
            if ($form->getMessages()) {
                $this->getResponse()->setHttpResponseCode(400);
                $view->errors = $form->getMessages();
                return;
            }
        }
    }

    /**
     * Delete a menu
     */
    public function deleteAction()
    {
        // deny if not accessed via post
        $request = $this->getRequest();
        if (!$request->isPost()) {
            throw new P4Cms_AccessDeniedException(
                "Deleting menus is not permitted in this context."
            );
        }

        $menu           = P4Cms_Menu::fetch($request->getParam('id'));
        $this->view->id = $menu->getId();

        $menu->delete();

        // clear any cached entries related to content types
        P4Cms_Cache::clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            array('p4cms_menu_' . bin2hex($menu->getId()))
        );
    }

    /**
     * Renders the item form for the requested entry.
     * Forces the 'partial' request context.
     */
    public function itemFormAction()
    {
        // explicitly set partial context for all requests.
        $this->contextSwitch->initContext('partial');

        $request  = $this->getRequest();
        $form     = new Menu_Form_MenuItem;

        // do an initial populate so we can access the type
        $form->populate($request->getParams());

        // allow the handler a chance to modify the form
        $handler = P4Cms_Navigation_PageTypeHandler::fetch($form->getValue('type'));
        $form    = $handler->prepareForm($form);

        // re-populate the (potentially modified) form
        $form->populate($request->getParams());

        // populate the view.
        $this->view->form = $form;
    }

    /**
     * Add menu item
     */
    public function addItemAction()
    {
        // enforce permissions.
        $this->acl->check('menus', 'manage');

        // get the menu we are adding this item to.
        $request    = $this->getRequest();
        $menuId     = $request->getParam('menuId');
        if ($menuId && !P4Cms_Menu::exists($menuId)) {
            $menuId = null;
        }
        $menuItemId = $request->getParam('id');

        // setup the form
        // different item types need different forms, we use the type
        // handler to prepare the form and we take the type id from
        // the request if present, otherwise from the form default.
        $view       = $this->view;
        $form       = new Menu_Form_MenuItem;
        $type       = current($form->splitType($request->getParam('type', $form->getValue('type'))));
        $handler    = P4Cms_Navigation_PageTypeHandler::fetch($type);
        $form       = $handler->prepareForm($form);
        $view->form = $form;

        // populate the form to position the new menu item *after*
        // the given menu id by default.
        $form->populate(
            array(
                'menuId'   => $menuId,
                'location' => $menuId ? $menuId . '/' . $menuItemId : '',
                'position' => 'after'
            )
        );

        // if request was not posted, nothing more to do.
        if (!$request->isPost()) {
            return;
        }

        // if valid data was posted, save the menu item
        // otherwise, set error code, and pass messages to the view.
        if ($form->isValid($request->getPost())) {
            $this->saveMenuItem($form);

            // clear any cached entries related to content types
            P4Cms_Cache::clean(
                Zend_Cache::CLEANING_MODE_MATCHING_TAG,
                array('p4cms_menu_' . bin2hex($menuId))
            );
        } else {
            $this->getResponse()->setHttpResponseCode(400);
            $view->errors = $form->getMessages();
        }
    }

    /**
     * Edit menu items
     *
     * @param   array    $params      The values to apply
     */
    public function editItemAction(array $params = null)
    {
        // enforce permissions.
        $this->acl->check('menus', 'manage');

        // fetch the menu and the menu item we're editing
        $request    = $this->getRequest();
        $menu       = P4Cms_Menu::fetch($request->getParam('menuId'));
        $item       = $menu->getContainer()->findBy('uuid', $request->getParam('id'));

        // if params is specified, we use them to override the existing values
        // else item values come from storage initally and from request when posted
        if (isset($params)) {
            $values     = array_merge($item->toArray(), $params);
        } else {
            $values     = $request->isPost() ? $request->getPost() : $item->toArray();
        }

        // setup the form - do an initial populate so we can access the type
        $form       = new Menu_Form_MenuItem;
        $form->populate($values);

        // allow the handler a chance to modify the form
        $handler    = P4Cms_Navigation_PageTypeHandler::fetch($form->getValue('type'));
        $form       = $handler->prepareForm($form);

        // re-populate the (potentially modified) form
        $form->populate($values);

        // populate the view.
        $view       = $this->view;
        $view->form = $form;

        // if request was not posted, nothing more to do.
        if (!$request->isPost()) {
            return;
        }

        // if valid data was posted, try to update the menu item
        // otherwise, set error code, and pass messages to the view.
        if ($form->isValid($values)) {
            $this->saveMenuItem($form, $item, $menu);

            // clear any cached entries related to content types
            P4Cms_Cache::clean(
                Zend_Cache::CLEANING_MODE_MATCHING_TAG,
                array('p4cms_menu_' . bin2hex($menu->getId()))
            );
        } else {
            $this->getResponse()->setHttpResponseCode(400);
            $view->errors = $form->getMessages();
        }
    }

    /**
     * Delete a menu item
     */
    public function deleteItemAction()
    {
        // deny if not accessed via post
        $request = $this->getRequest();
        if (!$request->isPost()) {
            throw new P4Cms_AccessDeniedException(
                "Deleting menu items is not permitted in this context."
            );
        }

        $menu     = P4Cms_Menu::fetch($request->getParam('menuId'));
        $menuItem = $menu->getContainer()
                         ->findBy('uuid', $request->getParam('id'));

        // throw if we cannot locate the menu item
        if (!$menuItem) {
            throw new P4Cms_Record_NotFoundException(
                'The specified menu item could not be found'
            );
        }

        $this->view->menuId = $menuItem->uuid;
        $this->view->id     = $menu->getId();

        $menuItem->getParent()->removePage($menuItem);
        $menu->save();

        // clear any cached entries related to content types
        P4Cms_Cache::clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            array('p4cms_menu_' . bin2hex($menu->getId()))
        );
    }

    /**
     * Restore default menus.
     */
    public function resetAction()
    {
        // enforce permissions.
        $this->acl->check('menus', 'manage');

        // clean out existing menus - if request specifies
        // a single menu to reset, only delete that menu.
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            if (!in_array($id, P4Cms_Menu::getDefaultMenuIds())) {
                throw new Menu_Exception("Cannot reset a non-default menu.");
            }
            P4Cms_Menu::remove($id);
        } else {
            $menus = P4Cms_Menu::fetchAll();
            $menus->invoke('delete');
        }

        // install fresh menus
        P4Cms_Menu::installDefaultMenus($id);

        // clear any cached entries related to menus
        P4Cms_Cache::clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            array('p4cms_menu')
        );

        // notify and redirect for standard requests (ie. not json)
        if (!$this->contextSwitch->getCurrentContext()) {
            P4Cms_Notifications::add(
                'Menu' . ($id ? '' : 's') . ' Reset',
                P4Cms_Notifications::SEVERITY_SUCCESS
            );

            $this->redirector->gotoSimple('index');
        }

        // indicate which menus were reset in the response.
        $this->view->menus = $id ? array($id) : $menus->invoke('getId');
    }

    /**
     * Reorder menu items
     */
    public function reorderAction()
    {
        // enforce permissions.
        $this->acl->check('menus', 'manage');

        $request    = $this->getRequest();
        $values     = array(
            "position" => $request->getParam('position'),
            "location" => $request->getParam('location')
        );

        // let the edit action handle the reordering and saving
        $this->editItemAction($values);
    }

    /**
     * Scans over the filtered list of menu items and re-adds any missing
     * ancestors to ensure we can show a full heirachy to our matches.
     *
     * @param   P4Cms_Model_Iterator    $items      The filtered list of items
     * @param   P4Cms_Model_Iterator    $originals  A full list of all items
     * @return  P4Cms_Model_Iterator    A new iterator with the obligatory items restored
     */
    protected function _restoreObligatory(P4Cms_Model_Iterator $items, P4Cms_Model_Iterator $originals)
    {
        // produce an original list indexed by id for later lookups
        $originalsById = new P4Cms_Model_Iterator;
        foreach ($originals as $original) {
            $originalsById[$original->getId()] = $original;
        }

        // produce an array of obligatory items to later tack back on
        $obligatory = array();
        $itemKeys = $items->invoke('getId');
        foreach ($items as $item) {
            $parent = $item;

            while ($parent->getParentId()) {
                $parent = $originalsById[$parent->getParentId()];

                if (!in_array($parent->getId(), $itemKeys)) {
                    $obligatory[] = $parent->getId();
                }
            }
        }

        // append and mark obligatory items but maintain original
        // item ordering
        $obligatory = array_unique($obligatory);
        $result     = new P4Cms_Model_Iterator;
        foreach ($originalsById as $id => $item) {
           if (in_array($id, $obligatory)) {
               $item->setValue('obligatory', true);
               $result->append($item);
           } else if (in_array($id, $itemKeys)) {
               $result->append($item);
           }
        }

        return $result;
    }

    /**
     * Save a menu item to menu storage.
     *
     * Takes a populated form to pull menu item values from.
     * If editing, also accepts the existing menu item and the
     * menu record it is currently saved to.
     *
     * This method will parent the menu item and set its order
     * value as appropriate (according to the position and
     * location form fields).
     *
     * @param   P4Cms_Form              $form           the populated form to pull values from.
     * @param   Zend_Navigation_Page    $item           optional - an existing menu item to
     *                                                  update (if editing).
     * @param   P4Cms_Menu|null         $menu           optional - the menu record the item
     *                                                  is currently saved to (if editing).
     * @param   P4Cms_Menu|null         $targetMenu     optional - the menu record the item is
     *                                                  going to be saved to
     */
    public static function saveMenuItem(
        P4Cms_Form $form,
        Zend_Navigation_Page $item  = null,
        P4Cms_Menu $menu            = null,
        P4Cms_Menu $targetMenu      = null)
    {
        $values = $form->getValues();

        // if the item is currently in a menu, remove it.
        if ($item && $item->getParent()) {
            $item->getParent()->removePage($item);
        }

        // if item type fails to match type selected in form, recreate it.
        if ($item && get_class($item) !== $form->getValue('type')) {
            $values = array_merge(
                $item->toArray(),
                $values
            );
            $item = null;
        }

        // if no menu item given, make one (must be adding or re-typing).
        if (!$item) {
            $item = $values;
            $item = P4Cms_Navigation::inferPageType($item);
            $item = Zend_Navigation_Page::factory($item);
        }

        // update values on the menu item
        foreach ($values as $key => $value) {
            $item->$key = $value;
        }

        // figure out the target location of this menu item.
        // location value is in the form of 'menuId/itemId'
        $targetIds    = explode('/', $form->getValue('location'), 2);
        $targetMenuId = isset($targetIds[0]) ? $targetIds[0] : null;
        $targetItemId = isset($targetIds[1]) ? $targetIds[1] : null;

        // get the menu that we're putting the item in.
        if (!$targetMenu && $menu && $menu->getId() == $targetMenuId) {
            $targetMenu = $menu;
        } else if (!$targetMenu) {
            $targetMenu = P4Cms_Menu::fetch($targetMenuId);
        }

        // get the target container that we're positioning relative to.
        // if we don't have a target item id, or can't find the
        // specified item, fallback to the target menu container.
        $target = $targetItemId
            ? $targetMenu->getContainer()->findBy('uuid', $targetItemId)
            : null;
        $target = $target ?: $targetMenu->getContainer();

        // add the item to it's target location.
        // if the position is under or we have a non-page container
        // we need to add the menu item as a child of the target
        // otherwise, we actually want to add ourselves as a peer.
        $position = $form->getValue('position');
        if ($position === 'under' || !$target instanceof Zend_Navigation_Page) {
            $target->addPage($item);
        } else {
            $target->getParent()->addPage($item);
        }

        // adjust ordering of this item relative to its peers.
        //  1. if position is 'before', put it before target-item.
        //  2. if position is 'after', put it after target-item.
        //  3. if position is 'under', put it last.
        $order   = 0;
        $parent  = $item->getParent();
        $padding = P4Cms_Menu::ITEM_ORDER_PADDING;
        foreach (iterator_to_array($parent) as $page) {
            if ($page === $item) {
                continue;
            }
            if ($position === 'before' && $page === $target) {
                $item->order = ++$order * $padding;
            }
            $page->order = ++$order * $padding;
            if ($position === 'after' && $page === $target) {
                $item->order = ++$order * $padding;
            }
        }
        if ($position === 'under') {
            $item->order = ++$order * $padding;
        }

        // save'em up.
        $adapter = $targetMenu->getAdapter();
        $batch   = !$adapter->inBatch()
            ? $adapter->beginBatch('Saving menu item')
            : false;
        if ($menu && $menu !== $targetMenu) {
            $menu->save();
        }
        $targetMenu->save();
        if ($batch) {
            $adapter->commitBatch();
        }
    }
}
