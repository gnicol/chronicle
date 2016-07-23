<?php
/**
 * Integrate the user module with the rest of the application.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class User_Module extends P4Cms_Module_Integration
{
    /**
     * Subscribe to the relevant topics.
     */
    public static function init()
    {
        // add default roles when site is created.
        P4Cms_PubSub::subscribe('p4cms.site.created',
            function(P4Cms_Site $site, P4Cms_User $administrator)
            {
                $adapter    = $administrator->getPersonalAdapter();
                $systemUser = $site->getConnection()->getUser();

                // create author and editor roles
                $roles = array('author', 'editor');
                foreach ($roles as $id) {
                    $role = new P4Cms_Acl_Role;
                    $role->setAdapter($adapter)
                         ->setId($id)
                         ->addOwner($systemUser)
                         ->save();
                }
            }
        );

        // update acl when a site is created.
        P4Cms_PubSub::subscribe('p4cms.site.created',
            function(P4Cms_Site $site, P4Cms_User $admin)
            {
                $acl = $site->getAcl();

                // need an admin adapter to write to acl
                // when a site is created, arg 2 is the admin user
                // when a module is enabled, the active user is admin
                $acl->getRecord()->setAdapter($admin->getPersonalAdapter());
                $acl->installDefaults();

                // on a P4 server using external authentication, remove the
                // 'Add User' privilege for other users except for administrator
                if ($site->getConnection()->hasExternalAuth()) {
                    $nonAdminRoles = array_diff($acl->getRoles(), array('administrator'));
                    $acl->removeAllow($nonAdminRoles, 'users', 'add');
                }

                $acl->save();
            }
        );

        // update acl when a module is enabled.
        P4Cms_PubSub::subscribe('p4cms.site.module.enabled',
            function(P4Cms_Site $site, P4Cms_Module $module)
            {
                $acl = $site->getAcl();
                $acl->installModuleDefaults($module)->save();
            }
        );

        // update acl when a module is disabled
        P4Cms_PubSub::subscribe('p4cms.site.module.disabled',
            function(P4Cms_Site $site, P4Cms_Module $module)
            {
                $acl = $site->getAcl();
                $acl->removeModuleDefaults($module)->save();
            }
        );

        // provide dynamic user-based menu items
        P4Cms_PubSub::subscribe('p4cms.navigation.dynamicHandlers',
            function()
            {
                // expand the 'login' / 'logout' menu item
                $handler = new P4Cms_Navigation_DynamicHandler;
                $handler->setId('user.login-logout')
                        ->setLabel('Login/Logout')
                        ->setExpansionCallback(
                            function($item, $options)
                            {
                                // add login or logout link as appropriate.
                                if (P4Cms_User::fetchActive()->isAnonymous()) {
                                    $item = array(
                                        'label'         => 'Login',
                                        'module'        => 'user',
                                        'controller'    => 'index',
                                        'action'        => 'login',
                                        'onClick'       => 'p4cms.user.login(this); return false;',
                                        'class'         => 'p4cms-user-login user-login',
                                        'order'         => 10
                                    );
                                } else {
                                    $item = array(
                                        'label'         => 'Logout',
                                        'module'        => 'user',
                                        'controller'    => 'index',
                                        'action'        => 'logout',
                                        'class'         => 'p4cms-user-logout user-logout',
                                        'order'         => 10
                                    );
                                }

                                return array($item);
                            }
                        )->setFormCallback(
                            function(Zend_Form $form)
                            {
                                $form->removeElement(P4Cms_Menu::MENU_MAX_DEPTH);
                                $form->removeElement(P4Cms_Menu::MENU_MAX_ITEMS);
                                $form->removeElement(P4Cms_Menu::MENU_ROOT);

                                return $form;
                            }
                        );

                return array($handler);
            }
        );

        // provide 'user' macro.
        P4Cms_PubSub::subscribe('p4cms.macro.user',
            function($params, $body, $context)
            {
                $field = isset($params[0]) ? $params[0] : 'id';
                $user  = P4Cms_User::fetchActive();

                // add support for a 'firstName' field.
                if ($field == 'firstName') {
                    return current(explode(' ', $user->getFullName(), 2));
                }

                return $user->hasField($field) ? $user->getValue($field) : null;
            }
        );

        // provide form to filter users by roles
        P4Cms_PubSub::subscribe('p4cms.user.grid.form.subForms',
            function(Zend_Form $form)
            {
                $roles = P4Cms_Acl_Role::fetchAll()->invoke('getId');

                // early exit if there are no roles
                if (!count($roles)) {
                    return;
                }

                $form = new P4Cms_Form_SubForm;
                $form->setName('role')
                     ->setAttrib('class', 'site-role-form')
                     ->setOrder(10)
                     ->addElement(
                        'MultiCheckbox',
                        'roles',
                        array(
                            'label'         => 'Roles',
                            'autoApply'     => true,
                            'multiOptions'  => array_combine($roles, $roles)
                        )
                     );

                return $form;
            }
        );

        // filter users by roles
        P4Cms_PubSub::subscribe('p4cms.user.grid.populate',
            function(P4Cms_Model_Iterator $users, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract selected roles
                $roles = isset($values['role']['roles'])
                    ? $values['role']['roles']
                    : null;

                // early exit if no role selected
                if (!$roles) {
                    return null;
                }

                // filter users by selected roles
                $users->filterByCallback(
                    function($user, $roles)
                    {
                        $match = array_intersect($roles, $user->getRoles()->invoke('getId'));
                        return !empty($match);
                    },
                    $roles
                );
            }
        );

        // provide form to search users
        P4Cms_PubSub::subscribe('p4cms.user.grid.form.subForms',
            function(Zend_Form $form)
            {
                return new Ui_Form_GridSearch;
            }
        );

        // filter users by keyword search
        P4Cms_PubSub::subscribe('p4cms.user.grid.populate',
            function(P4Cms_Model_Iterator $users, Zend_Form $form)
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

                // remove users that don't match search query.
                return $users->search(
                    array('id', 'fullName', 'email'),
                    $query
                );
            }
        );

        // provide user grid actions
        P4Cms_PubSub::subscribe('p4cms.user.grid.actions',
            function($actions)
            {
                $actions->addPages(
                    array(
                        array(
                            'label'     => 'Edit',
                            'onClick'   => 'p4cms.user.grid.Actions.onClickEdit();',
                            'order'     => '10'
                        ),
                        array(
                            'label'     => 'Delete',
                            'onClick'   => 'p4cms.user.grid.Actions.onClickDelete();',
                            'order'     => '20'
                        )
                    )
                );
            }
        );

        // provide form to filter roles by type
        P4Cms_PubSub::subscribe('p4cms.user.role.grid.form.subForms',
            function(Zend_Form $form)
            {
                $types = array(
                    'system'    => 'System',
                    'custom'    => 'Custom'
                );

                $form = new P4Cms_Form_SubForm;
                $form->setName('type')
                     ->setAttrib('class', 'role-type-form')
                     ->setOrder(10)
                     ->addElement(
                        'MultiCheckbox',
                        'types',
                        array(
                            'label'         => 'Type',
                            'autoApply'     => true,
                            'multiOptions'  => $types
                        )
                     );

                return $form;
            }
        );

        // filter roles by type
        P4Cms_PubSub::subscribe('p4cms.user.role.grid.populate',
            function(P4Cms_Model_Iterator $roles, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract selected type
                $types = isset($values['type']['types'])
                    ? $values['type']['types']
                    : null;

                // early exit if no type selected
                if (!$types) {
                    return null;
                }

                // filter roles by selected types
                $roles->filter('type', $types, P4Cms_Model_Iterator::FILTER_NO_CASE);
            }
        );

        // provide form to search roles
        P4Cms_PubSub::subscribe('p4cms.user.role.grid.form.subForms',
            function(Zend_Form $form)
            {
                return new Ui_Form_GridSearch;
            }
        );

        // filter roles for keyword search
        P4Cms_PubSub::subscribe('p4cms.user.role.grid.populate',
            function(P4Cms_Model_Iterator $roles, Zend_Form $form)
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

                // search/filter roles by query.
                return $roles->search(array('id', 'type'), $query);
            }
        );

        // provide role grid actions
        P4Cms_PubSub::subscribe('p4cms.user.role.grid.actions',
            function($actions)
            {
                $actions->addPages(
                    array(
                        array(
                            'label'     => 'Edit',
                            'onClick'   => 'p4cms.user.role.grid.Actions.onClickEdit();',
                            'onShow'    => 'p4cms.user.role.grid.Actions.onShowEdit(this);',
                            'order'     => '10'
                        ),
                        array(
                            'label'     => 'Delete',
                            'onClick'   => 'p4cms.user.role.grid.Actions.onClickDelete();',
                            'onShow'    => 'p4cms.user.role.grid.Actions.onShowDelete(this);',
                            'order'     => '20'
                        )
                    )
                );
            }
        );

        // provide form to search acl permissions
        P4Cms_PubSub::subscribe('p4cms.user.acl.grid.form.subForms',
            function(Zend_Form $form)
            {
                return new Ui_Form_GridSearch;
            }
        );

        // filter acl permissions by keyword search.
        P4Cms_PubSub::subscribe('p4cms.user.acl.permissions',
            function(P4Cms_Model_Iterator $permissions, Zend_Form $form)
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

                // remove permissions that don't match search query.
                return $permissions->search(
                    array('resourceLabel', 'privilegeLabel'),
                    $query
                );
            }
        );

        // provide form to limit acl permissions by resource.
        P4Cms_PubSub::subscribe('p4cms.user.acl.grid.form.subForms',
            function(Zend_Form $form)
            {
                // collect resource options from acl.
                $acl     = $form->getAcl();
                $options = array();
                foreach ($acl->getResourceObjects() as $resource) {
                    $options[$resource->getResourceId()] = $resource->getLabel();
                }

                // ensure natural order.
                natcasesort($options);

                $form = new P4Cms_Form_SubForm;
                $form->setName('resource')
                     ->setAttrib('class', 'resource-form')
                     ->setOrder(20)
                     ->addElement(
                        'MultiCheckbox',
                        'resources',
                        array(
                            'label'         => 'Resource',
                            'multiOptions'  => $options,
                            'autoApply'     => true,
                        )
                     );

                return $form;
            }
        );

        // filter acl permissions by resource.
        P4Cms_PubSub::subscribe('p4cms.user.acl.permissions',
            function(P4Cms_Model_Iterator $permissions, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract selected resources.
                $resources = isset($values['resource']['resources'])
                    ? $values['resource']['resources']
                    : null;

                // early exit if no query.
                if (!$resources) {
                    return null;
                }

                // remove permissions that don't match selected resources.
                return $permissions->filter('resourceId', $resources);
            }
        );

        // touch-up user add privilege to indicate that super user
        // access is required if auto user creation is not enabled, or
        // the P4 server we are connected to uses external authentication.
        P4Cms_PubSub::subscribe(
            'p4cms.acl.users.privileges',
            function(P4Cms_Acl_Resource $resource)
            {
                try {
                    $resource->getPrivilege('add')->setOption(
                        'needsSuper',
                        !P4_User::isAutoUserCreationEnabled() ||
                        P4_Connection::getDefaultConnection()->hasExternalAuth()
                    );
                } catch (Exception $e) {
                    // fail silently.
                    return;
                }
            }
        );

        // put acl role columns in a prescribed order.
        P4Cms_PubSub::subscribe(
            'p4cms.user.acl.roles',
            function(P4Cms_Model_Iterator $roles, Zend_Form $form)
            {
                $order = array(
                    'anonymous',
                    'member',
                    'author',
                    'editor',
                    'administrator'
                );
                $roles->sortBy(
                    array(
                        array('id', array(P4Cms_Model_Iterator::SORT_FIXED => $order)),
                        array('id', array(P4Cms_Model_Iterator::SORT_NO_CASE))
                    )
                );
            }
        );

        // move acl file to 'permissions' group for pull operations.
        P4Cms_PubSub::subscribe(
            'p4cms.site.branch.pull.groupPaths',
            function($paths, $source, $target, $result)
            {
                // try to find the config group (fallback to top-level)
                $parent = $paths->getSubGroups()
                                ->filter('label', 'Configuration', array(P4Cms_Model_Iterator::FILTER_COPY))
                                ->first();
                $parent = $parent ?: $paths;

                $parent->addSubGroup(
                    array(
                        'label'         => 'Permissions',
                        'hideCount'     => true,
                        'inheritPaths'  => $target->getId() . '/config/acl',
                        'details'   =>
                            function($paths) use ($source)
                            {
                                $path    = $paths->first();
                                $details = new P4Cms_Model_Iterator;

                                if ($path) {
                                    $details[] = new P4Cms_Model(
                                        array(
                                            'conflict' => $path->conflict,
                                            'action'   => $path->action,
                                            'type'     => 'Configuration',
                                            'label'    => 'Permissions'
                                        )
                                    );
                                }

                                return $details;
                            }
                    )
                );
            }
        );
    }

    /**
     * Perform integration operations when the site is loaded.
     */
    public static function load()
    {
        // make the current users ID and Roles visible to javascript
        if (P4Cms_User::hasActive()) {
            $view    = Zend_Layout::getMvcInstance()->getView();
            $user    = P4Cms_User::fetchActive();
            $active  = array('id' => $user->getId(), 'roles' => $user->getRoles()->invoke('getId'));
            $script  = "dojo.setObject('p4cms.user.active', " . Zend_Json::encode($active) . ");";

            $view->headScript()->appendScript($script);
        }
    }
}
