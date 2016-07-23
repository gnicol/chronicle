<?php
/**
 * Integrates menu module with the rest of the system.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_Module extends P4Cms_Module_Integration
{
    /**
     * Perform early integration work (before load).
     */
    public static function init()
    {
        // install menus when a site is created.
        P4Cms_PubSub::subscribe('p4cms.site.created',
            function(P4Cms_Site $site)
            {
                $adapter = $site->getStorageAdapter();
                P4Cms_Menu::installDefaultMenus(null, $adapter);
            }
        );

        // update menus when a module/theme is enabled.
        $installDefaults = function(P4Cms_Site $site, P4Cms_PackageAbstract $package)
        {
            $adapter = $site->getStorageAdapter();
            P4Cms_Menu::installPackageDefaults($package, null, $adapter);
        };

        P4Cms_PubSub::subscribe('p4cms.site.module.enabled', $installDefaults);
        P4Cms_PubSub::subscribe('p4cms.site.theme.enabled',  $installDefaults);

        // update menus when a module/theme is disabled.
        $removeDefaults = function(P4Cms_Site $site, P4Cms_PackageAbstract $package)
        {
            $adapter = $site->getStorageAdapter();
            P4Cms_Menu::removePackageDefaults($package, $adapter);
        };

        P4Cms_PubSub::subscribe('p4cms.site.module.disabled', $removeDefaults);
        P4Cms_PubSub::subscribe('p4cms.site.theme.disabled',  $removeDefaults);

        // provide form to search menu management grid
        P4Cms_PubSub::subscribe('p4cms.menu.grid.form.subForms',
            function(Zend_Form $form)
            {
                return new Ui_Form_GridSearch;
            }
        );

        // filter menu management grid by keyword search
        P4Cms_PubSub::subscribe('p4cms.menu.grid.populate',
            function(P4Cms_Model_Iterator $items, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract search query.
                $query = isset($values['search']['query'])
                    ? $values['search']['query']
                    : null;

                // early exit if no query.
                if (!$query) {
                    return null;
                }

                // filter categories by the search query
                $items->search(array('label', 'type'), $query);
            }
        );

        // provide form to filter by menu
        P4Cms_PubSub::subscribe('p4cms.menu.grid.form.subForms',
            function(Zend_Form $form)
            {
                $menus   = P4Cms_Menu::fetchAll();

                // avoid adding the form if no menus are known
                if (!count($menus)) {
                    return;
                }

                $options = array_combine($menus->invoke('getId'), $menus->invoke('getLabel'));

                $form = new P4Cms_Form_SubForm;
                $form->setName('menu')
                     ->setAttrib('class', 'types-form')
                     ->setOrder(20)
                     ->addElement(
                        'MultiCheckbox',
                        'display',
                        array(
                            'label'         => 'Menu',
                            'multiOptions'  => $options,
                            'autoApply'     => true,
                            'order'         => 20
                        )
                     );

                return $form;
            }
        );

        // filter menu management grid by menu
        P4Cms_PubSub::subscribe('p4cms.menu.grid.populate',
            function(P4Cms_Model_Iterator $items, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract search query.
                $query = isset($values['menu']['display'])
                    ? $values['menu']['display']
                    : null;

                // early exit if no query.
                if (!$query) {
                    return null;
                }

                // filter categories by the search query
                $items->filterByCallback(
                    function($item) use ($query)
                    {
                        return in_array($item->getMenu()->getId(), $query);
                    }
                );
            }
        );

        // provide form to filter by type
        P4Cms_PubSub::subscribe('p4cms.menu.grid.form.subForms',
            function(Zend_Form $form)
            {
                $types = Menu_Form_MenuItem::getTypeOptions(true, true);

                $form = new P4Cms_Form_SubForm;
                $form->setName('type')
                     ->setAttrib('class', 'types-form')
                     ->setOrder(30)
                     ->addElement(
                        'NestedCheckbox',
                        'display',
                        array(
                            'label'         => 'Type',
                            'multiOptions'  => $types,
                            'autoApply'     => true,
                            'order'         => 30,
                            'onClick'       => "
                                if (this.value == 'P4Cms_Navigation_Page_Dynamic') {
                                    p4cms.ui.toggleChildCheckboxes(this);
                                } else {
                                    p4cms.ui.toggleParentCheckbox(this);
                                }
                            "
                        )
                     );

                return $form;
            }
        );

        // filter menu management grid by type
        P4Cms_PubSub::subscribe('p4cms.menu.grid.populate',
            function(P4Cms_Model_Iterator $items, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract type filter.
                $types = isset($values['type']['display'])
                    ? $values['type']['display']
                    : null;

                // early exit if no types selected.
                if (!$types || !is_array($types)) {
                    return null;
                }

                // modify query to pull out any dynamic types
                // so we can apply them after primary filtering
                $dynamicType  = 'P4Cms_Navigation_Page_Dynamic';
                $dynamicGroup = $dynamicType . '/';
                $dynamicTypes = array();
                foreach ($types as &$type) {
                    // if they have filtered for top level 'Dynamic'
                    // allow all dynamic types and break
                    if ($type == $dynamicType) {
                        $dynamicTypes = array();
                        break;
                    }

                    if (strpos($type, $dynamicGroup) === 0) {
                        $dynamicTypes[] = str_replace($dynamicGroup, '', $type);
                        $type = $dynamicType;
                    }
                }

                // filter menu items by the search query
                $items->filter('type', $types);

                // apply dynamic type filtering if present
                if (!empty($dynamicTypes)) {
                    $uuids = array();
                    foreach ($items as $item) {
                        if ($item->hasMenuItem()
                            && in_array($item->getMenuItem()->handler, $dynamicTypes)
                        ) {
                            $uuids[] = $item->getId();
                        }
                    }
                    $items->filter('id', $uuids);
                }
            }
        );

        // provide menu management actions
        P4Cms_PubSub::subscribe('p4cms.menu.grid.actions',
            function($actions)
            {
                $actions->addPages(
                    array(
                        array(
                            'label'     => 'Edit',
                            'onClick'   => 'p4cms.menu.grid.Actions.onClickEdit();',
                            'order'     => '10'
                        ),
                        array(
                            'label'     => 'Delete',
                            'onClick'   => 'p4cms.menu.grid.Actions.onClickDelete();',
                            'order'     => '20'
                        ),
                        array(
                            'label'     => 'Reset',
                            'onClick'   => 'p4cms.menu.grid.Actions.onClickReset();',
                            'onShow'    => 'p4cms.menu.grid.Actions.onShowReset(this);',
                            'order'     => '30'
                        ),
                        array(
                            'label'     => 'Go To',
                            'onClick'   => 'p4cms.menu.grid.Actions.onClickGoToMenuItem();',
                            'onShow'    => 'p4cms.menu.grid.Actions.onShowGoToMenuItem(this);',
                            'order'     => '40'
                        ),
                        array(
                            'type'      => 'P4Cms_Navigation_Page_Separator',
                            'order'     => '50'
                        ),
                        array(
                            'label'     => 'Add Menu Item',
                            'onClick'   => 'p4cms.menu.grid.Actions.onClickAddMenuItem();',
                            'order'     => '60'
                        )
                    )
                );
            }
        );

        // declare page type handlers to influence how different
        // types of menu items are edited when managing menus.
        P4Cms_PubSub::subscribe('p4cms.navigation.pageTypeHandlers',
            function()
            {
                $handlers   = array();

                // declare uri handler.
                $handler    = new P4Cms_Navigation_PageTypeHandler;
                $handlers[] = $handler;
                $handler->setId('Zend_Navigation_Page_Uri')
                        ->setLabel('Link')
                        ->setFormCallback(
                            function(Zend_Form $form)
                            {
                                // add a Uri field
                                $form->addElement(
                                    'text',
                                    'uri',
                                    array(
                                        'label'     => 'Address',
                                        'required'  => true,
                                        'filters'   => array('StringTrim'),
                                        'size'      => 32
                                    )
                                );

                                return $form;
                            }
                        );

                // declare mvc handler.
                $handler    = new P4Cms_Navigation_PageTypeHandler;
                $handlers[] = $handler;
                $handler->setId('P4Cms_Navigation_Page_Mvc')
                        ->setLabel('Action')
                        ->setFormCallback(
                            function(Zend_Form $form)
                            {
                                return new Menu_Form_MenuItemMvc;
                            }
                        );

                // declare heading handler.
                $handler    = new P4Cms_Navigation_PageTypeHandler;
                $handlers[] = $handler;
                $handler->setId('P4Cms_Navigation_Page_Heading')
                        ->setLabel('Heading')
                        ->setFormCallback(
                            function(Zend_Form $form)
                            {
                                $form->removeElement('onClick');
                                return $form;
                            }
                        );

                // declare separator handler.
                $handler    = new P4Cms_Navigation_PageTypeHandler;
                $handlers[] = $handler;
                $handler->setId('P4Cms_Navigation_Page_Separator')
                        ->setLabel('Separator')
                        ->setFormCallback(
                            function(Zend_Form $form)
                            {
                                $form->removeElement('label');
                                $form->removeElement('target');
                                $form->removeElement('onClick');

                                return $form;
                            }
                        );

                // declare dynamic handler.
                $handler    = new P4Cms_Navigation_PageTypeHandler;
                $handlers[] = $handler;
                $handler->setId('P4Cms_Navigation_Page_Dynamic')
                        ->setLabel('Dynamic')
                        ->setFormCallback(
                            function(Zend_Form $form)
                            {
                                $form->removeElement('title');
                                $form->removeElement('label');

                                // borrow elements from widget form.
                                $widgetForm = new Menu_Form_Widget;
                                $form->addElement($widgetForm->getElement('maxDepth'));
                                $form->addElement($widgetForm->getElement('maxItems'));

                                // different dynamic menu item types like different forms.
                                // give dynamic handlers a chance to prepare the form.
                                $dynamicType = $form->getValue('handler');
                                try {
                                    if ($dynamicType) {
                                        $dynamicHandler = P4Cms_Navigation_DynamicHandler::fetch($dynamicType);
                                        $dynamicHandler->prepareForm($form);
                                    }
                                } catch (P4Cms_Model_NotFoundException $e) {
                                    // no such dynamic type. we're ok with that.
                                }

                                return $form;
                            }
                        );

                // declare content handler.
                $handler    = new P4Cms_Navigation_PageTypeHandler;
                $handlers[] = $handler;
                $handler->setId('P4Cms_Navigation_Page_Content')
                        ->setLabel('Content')
                        ->setFormCallback(
                            function(Zend_Form $form)
                            {
                                return new Menu_Form_MenuItemContent;
                            }
                        );

               return $handlers;
            }
        );

        // participate in content editing by providing a subform.
        P4Cms_PubSub::subscribe('p4cms.content.form.subForms',
            function(Content_Form_Content $form)
            {
                // only show menu sub-form if user has permission
                $user = P4Cms_User::fetchActive();
                if (!$user->isAllowed('menus', 'manage-via-content')) {
                    return;
                }

                // add menu form so users can easily add content to menus.
                return new Menu_Form_Content(
                    array(
                        'name'      => 'menus',
                        'idPrefix'  => $form->getIdPrefix(),
                        'order'     => -40
                    )
                );
            }
        );

        // let users add/edit menu items when editing content.
        P4Cms_PubSub::subscribe('p4cms.content.form.populate',
            function(Content_Form_Content $form, array $values)
            {
                // nothing to do if no menu sub-form
                $menuForm = $form->getSubForm('menus');
                if (!$menuForm) {
                    return;
                }

                // pull menus from storage if not in values array.
                if (isset($values['menus'])) {
                    $items = $values['menus'];
                } else {
                    $entry = $form->getEntry();
                    $items = array();
                    foreach (P4Cms_Menu::fetchMixed() as $mixed) {
                        $item = $mixed->getMenuItem();
                        if ($item instanceof P4Cms_Navigation_Page_Content
                            && $item->contentId == $entry->getId()
                        ) {
                            $items[] = array_merge(
                                array('menuId' => $mixed->getMenu()->getId()),
                                $item->toArray()
                            );
                        }
                    }
                }

                // for each menu item, add a menu item sub-form.
                $count = 0;
                foreach ($items as $item) {
                    $itemForm = $menuForm->getItemForm($item);
                    $menuForm->addSubForm($itemForm, $count++);
                }
            }
        );

        // participate in content editing - don't store menus items
        // on content directly, store them in menu records instead.
        P4Cms_PubSub::subscribe('p4cms.content.record.preSave',
            function(P4Cms_Content $entry)
            {
                // move menus out of fields list and into a public
                // property so it doesn't get saved with the record
                if ($entry->hasField('menus')) {
                    $menus = $entry->getValue('menus');
                    $entry->unsetValue('menus');
                    $entry->menus = $menus;
                }
            }
        );
        P4Cms_PubSub::subscribe('p4cms.content.record.postSave',
            function(P4Cms_Content $entry)
            {
                // only modify menus if user has adequate permission.
                $user = P4Cms_User::fetchActive();
                if (!$user->isAllowed('menus', 'manage-via-content')) {
                    return;
                }

                $adapter   = $entry->getAdapter();
                $menuItems = is_array($entry->menus) ? $entry->menus : array();

                // when we fetch menus, we hang onto them because we are in a
                // batch and if we re-fetch them we'll lose any earlier changes.
                $menus     = array();
                $fetchMenu = function($id) use ($adapter, &$menus)
                {
                    if (!isset($menus[$id])) {
                        try {
                            $menus[$id] = P4Cms_Menu::fetch($id, null, $adapter);
                        } catch (P4Cms_Model_NotFoundException $e) {
                            return null;
                        }
                    }
                    return $menus[$id];
                };

                // content entries can have multiple menu entries, loop over
                // each posted menu entry and add/update/delete as appropriate.
                foreach ($menuItems as $values) {
                    $form = new Menu_Form_MenuItemContent;

                    // normalize values to always contain the expected elements
                    // and have the correct page type and content id.
                    $values = array_merge(
                        $form->getValues(),
                        array(
                            'type'      => 'P4Cms_Navigation_Page_Content',
                            'contentId' => $entry->getId()
                        ),
                        $values
                    );

                    // fetch the menu and the menu item if we're editing.
                    $menu = null;
                    $item = null;
                    if ($values['menuId'] && $values['uuid']) {

                        $menu = $fetchMenu($values['menuId']);
                        $item = $menu ? $menu->getContainer()->findBy('uuid', $values['uuid']) : null;

                        // if we can't find this menu or item, we assume someone
                        // deleted it, and rather than resurrect it, we skip it.
                        if (!$menu || !$item) {
                            continue;
                        }

                        // handle deleted menu items.
                        if ($values['remove']) {
                            $item->getParent()->removePage($item);
                            $menu->save();
                            continue;
                        }

                        // skip menu items that haven't changed.
                        $form->populate($item->toArray());
                        if ($values['label'] == $form->getValue('label')
                            && $values['position'] == $form->getValue('position')
                            && $values['location'] == $form->getValue('location')
                            && $values['contentAction'] == $form->getValue('contentAction')
                        ) {
                            continue;
                        }
                    }

                    // skip aborted adds (user adds then clicks remove)
                    if ($values['remove']) {
                        continue;
                    }

                    $form->populate($values);

                    // fetch the target menu so that save menu item will write to our copy
                    $targetId = reset(explode('/', $form->getValue('location'), 2));
                    $target   = $targetId ? $fetchMenu($targetId) : null;

                    Menu_ManageController::saveMenuItem($form, $item, $menu, $target);
                }
            }
        );

        // if an entry is deleted remove any content links (so long as they have no children)
        P4Cms_PubSub::subscribe('p4cms.content.record.delete',
            function(P4Cms_Content $entry)
            {
                foreach (P4Cms_Menu::fetchMixed(null, $entry->getAdapter()) as $mixed) {
                    $item = $mixed->getMenuItem();
                    if ($item instanceof P4Cms_Navigation_Page_Content
                        && $item->contentId == $entry->getId()
                        && !count($item->getPages())
                    ) {
                        $item->getParent()->removePage($item);
                        $mixed->getMenu()->save();
                    }
                }
            }
        );

        // organize menu records when pulling changes.
        P4Cms_PubSub::subscribe(
            'p4cms.site.branch.pull.groupPaths',
            function($paths, $source, $target, $result)
            {
                $paths->addSubGroup(
                    array(
                        'label'         => 'Menus',
                        'basePaths'     => $target->getId() . '/menus/...',
                        'inheritPaths'  => $target->getId() . '/menus/...',
                        'pullByDefault' => true,
                        'details'       =>
                            function($paths) use ($source, $target)
                            {
                                $pathsById = array();
                                foreach ($paths as $path) {
                                    if (strpos($path->depotFile, $target->getId() . '/menus/') === 0) {
                                        $pathsById[P4Cms_Menu::depotFileToId($path->depotFile)] = $path;
                                    }
                                }

                                $details = new P4Cms_Model_Iterator;
                                $entries = Site_Model_PullPathGroup::fetchRecords(
                                    array_keys($pathsById), 'P4Cms_Menu', $source, $target
                                );
                                foreach ($entries as $entry) {
                                    $path      = $pathsById[$entry->getId()];
                                    $details[] = new P4Cms_Model(
                                        array(
                                            'conflict' => $path->conflict,
                                            'action'   => $path->action,
                                            'label'    => $entry->getLabel()
                                        )
                                    );
                                }

                                $details->setProperty(
                                    'columns',
                                    array('label' => 'Menu', 'action' => 'Action')
                                );

                                return $details;
                            }
                    )
                );
            }
        );

        // automatically resolve menu conflicts.
        P4Cms_PubSub::subscribe(
            'p4cms.site.branch.pull.conflicts',
            function($conflicts, $target, $source, $headChange, $preview, $adapter)
            {
                // skip the resolve when previewing so the user receives a warning
                // that there is the potential for changes to be lost in the target
                if ($preview) {
                    return;
                }

                // filter conflicts for menu path entries and convert
                // depot paths to record ids so we can query records.
                $sourceIds = array();
                $targetIds = array();
                $basePath  = $target->getId() . '/menus/';
                foreach ($conflicts->getData() as $conflict) {
                    if (isset($conflict['depotFile'])
                        && strpos($conflict['depotFile'], $basePath) === 0
                    ) {
                        $id          = P4Cms_Menu::depotFileToId($conflict['depotFile'], $adapter);
                        $sourceIds[] = $id . "@" . $headChange;
                        $targetIds[] = $id;
                    }
                }

                // if there are no menu conflicts, nothing to do.
                if (!$sourceIds || !$targetIds) {
                    return;
                }

                // we use the given adapter for target rather than calling
                // target->getStorageAdapter() to ensure we are using the
                // proper user and workspace.
                $targetAdapter = $adapter;
                $sourceAdapter = $source->getStorageAdapter();

                // fetch all conflicting menus in both the source and
                // target branches, so that we can merge them.
                $targetMenus = P4Cms_Menu::fetchAll(array('ids' => $targetIds), $targetAdapter);
                $sourceMenus = P4Cms_Menu::fetchAll(array('ids' => $sourceIds), $sourceAdapter);

                foreach ($targetMenus as $id => $targetMenu) {
                    // if the source menu cannot be fetched we assume its deleted
                    // and skip it as the default merge will take care of it.
                    // deleted targets don't get fetched/looped automatically.
                    if (!isset($sourceMenus[$id])) {
                        continue;
                    }

                    // determine the base version to diff/merge against
                    $resolve = $targetAdapter->getConnection()->run(
                        'resolve',
                        array('-no', $targetMenu->toP4File()->getFilespec())
                    )->getData(0);

                    $baseFile   = P4_File::fetch($resolve['baseFile'] . '#' . $resolve['baseRev']);
                    $baseMenu   = P4Cms_Menu::fromP4File($baseFile, P4Cms_Menu::FROM_FILE_IMPORT);
                    $sourceMenu = $sourceMenus[$id];

                    // merge in changes from target menu since last pull (base)
                    // this will propagate non-conflicting changes made in
                    // the target onto the source branch's menu container.
                    $sourceMenu->merge($targetMenu, $baseMenu);

                    // update the target with our merged result
                    $targetMenu->setContainer($sourceMenu->getContainer())
                               ->save();

                    // run an 'accept yours' merge on the menu to avoid it being
                    // clobbered by the source. we need to include the undoc -e
                    // flag for the base to advance (which is key to our merge/diff)
                    $targetAdapter->getConnection()->run(
                        'resolve',
                        array('-e', '-ay', $targetMenu->toP4File()->getFilespec())
                    );
                }
            }
        );
    }
}
