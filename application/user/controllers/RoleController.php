<?php
/**
 * Manages user roles.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class User_RoleController extends Zend_Controller_Action
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
     * List all roles.
     *
     * @publishes   p4cms.user.role.grid.actions
     *              Modify the passed menu (add/modify/delete items) to influence the actions shown
     *              on entries in the Manage Users grid.
     *              P4Cms_Navigation            $actions    A menu to hold grid actions.
     *
     * @publishes   p4cms.user.role.grid.data.item
     *              Return the passed item after applying any modifications (add properties, change
     *              values, etc.) to influence the row values sent to the Manage Roles grid.
     *              array                       $item       The item to potentially modify.
     *              mixed                       $model      The original object/array that was used
     *                                                      to make the item.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.user.role.grid.data
     *              Adjust the passed data (add properties, modify values, etc.) to influence the
     *              row values sent to the Manage Roles grid.
     *              Zend_Dojo_Data              $data       The data to be filtered.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.user.role.grid.populate
     *              Adjust the passed iterator (possibly based on values in the passed form) to
     *              filter which roles will be shown on the Manage Roles grid.
     *              P4Cms_Model_Iterator    $roles          An iterator of Role_Model_Role objects.
     *              P4Cms_Form_PubSubForm   $form           A form containing filter options.
     *
     * @publishes   p4cms.user.role.grid.render
     *              Make adjustments to the datagrid helper's options pre-render (e.g. change
     *              options to add columns) for the Manage Roles grid.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.user.role.grid.form
     *              Make arbitrary modifications to the Manage Roles filters form.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.user.role.grid.form.subForms
     *              Return a Form (or array of Forms) to have them added to the Manage Roles filters
     *              form. The returned form(s) should have a 'name' set on them to allow them to be
     *              uniquely identified.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.user.role.grid.form.preValidate
     *              Allows subscribers to adjust the Manage Roles filters form prior to validation
     *              of the passed data. For example, modify element values based on related
     *              selections to permit proper validation.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.user.role.grid.form.validate
     *              Return false to indicate the Manage Roles filters form is invalid. Return true
     *              to indicate your custom checks were satisfied, so form validity should be
     *              unchanged.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.user.role.grid.form.populate
     *              Allows subscribers to adjust the Manage Roles filters form after it has been
     *              populated with the passed data.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     The values passed to the populate
     *                                                      method.
     */
    public function indexAction()
    {
        // enforce permissions
        $this->acl->check('users', 'manage-roles');

        // setup list options form.
        $request        = $this->getRequest();
        $gridNamespace  = 'p4cms.user.role.grid';
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
        $view->headTitle()->set('Manage Roles');

        // set DataGrid view helper namespace
        $helper = $view->dataGrid();
        $helper->setNamespace($gridNamespace);

        // collect the actions from interested parties
        $actions = new P4Cms_Navigation;
        P4Cms_PubSub::publish($gridNamespace . '.actions', $actions);
        $view->actions = $actions;

        // early exit for standard requests (ie. not json)
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->getHelper('helpUrl')->setUrl('roles.manage.html');
            return;
        }

        // fetch roles - allow third-parties to influence list
        $roles = P4Cms_Acl_Role::fetchAll();
        try {
            P4Cms_PubSub::publish($gridNamespace . '.populate', $roles, $form);
        } catch (Exception $e) {
            P4Cms_Log::logException("Error building role list.", $e);
        }

        // prepare sorting options
        $sortKey    = $request->getParam('sort', 'id');
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
        $roles->sortBy($sortKey, $sortFlags);

        // add users to the view.
        $view->roles = $roles;
    }

    /**
     * Add new role.
     */
    public function addAction()
    {
        // enforce permissions
        $this->acl->check('users', 'manage-roles');

        $request            = $this->getRequest();
        $activeUser         = P4Cms_User::fetchActive();
        $form               = new User_Form_AddRole;

        // set up view
        $view               = $this->view;
        $view->form         = $form;
        $view->headTitle()->set('Add Role');

        // deny non-administrator users
        if (!$activeUser->isAdministrator()) {
            throw new P4Cms_AccessDeniedException(
                "You don't have permission to create roles."
            );
        }

        // if posted, validate form and save role.
        if ($request->isPost()) {
            // if form is invalid, set response code and exit
            if (!$form->isValid($request->getParams())) {
                $this->getResponse()->setHttpResponseCode(400);
                $view->errors = $form->getMessages();
                return;
            }

            // create new role entry
            $role = new P4Cms_Acl_Role;
            $role->setValues($form->getValues());

            // add owner to the associated group
            $this->_addOwner($role);

            $role->save();

            // clear affected cache entries.
            $this->_clearCaches($role->getId(), $role->getUsers(), 'add');

            // set notification message
            $view->message = "Role '{$role->getId()}' has been successfuly added.";

            // for traditional requests, add notification message and redirect
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
     * Edit existing role.
     */
    public function editAction()
    {
        // enforce permissions
        $this->acl->check('users', 'manage-roles');

        $request            = $this->getRequest();
        $activeUser         = P4Cms_User::fetchActive();
        $roleId             = $request->getParam('id');
        $role               = P4Cms_Acl_Role::fetch($roleId);
        $form               = new User_Form_EditRole;

        // deny if role is virtual
        if ($role->isVirtual()) {
            throw new P4Cms_AccessDeniedException(
                "Cannot modify '$roleId' role."
            );
        }

        // deny non-administrator users
        if (!$activeUser->isAdministrator()) {
            throw new P4Cms_AccessDeniedException(
                "You don't have permission to edit this role."
            );
        }

        // set up view
        $view               = $this->view;
        $view->form         = $form;
        $view->headTitle()->set('Edit Role');

        // assemble values array to populate the form with
        $values = $role->getValues();
        $users  = $role->getRealUsers();
        if ($users) {
            $values['users'] = array_combine($users, $users);
        }

        // populate form from post if available, otherwise from storage.
        $form->populate($request->isPost() ? $request->getParams() : $values);

        // always set the id from storage
        $form->getElement('id')->setValue($role->getRoleId());

        // if posted, validate form and save changes.
        if ($request->isPost()) {
            // if form is invalid, set response code and exit
            if (!$form->isValid($request->getParams())) {
                $this->getResponse()->setHttpResponseCode(400);
                $view->errors = $form->getMessages();
                return;
            }

            // collect list of associated users prior to updating role
            // for the purpose of clearing cache for users that are removed
            $originalUsers = $role->getUsers();

            $role->setValues($form->getValues())
                 ->save();

            // clear affected caches.
            $affectedUsers = array_unique(array_merge($role->getUsers(), $originalUsers));
            $this->_clearCaches($role->getId(), $affectedUsers, 'edit');

            // set notification message
            $view->message = "Role '{$role->getId()}' has been successfuly updated.";

            // for traditional requests, add notification message and redirect
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
     *  Delete role entry.
     *	Role can be removed only via post.
     */
    public function deleteAction()
    {
        // enforce permissions
        $this->acl->check('users', 'manage-roles');

        // deny if not accessed via post
        if (!$this->getRequest()->isPost()) {
            throw new P4Cms_AccessDeniedException(
                "Deleting roles is not permitted in this context."
            );
        }

        $activeUser = P4Cms_User::fetchActive();
        $post       = $this->getRequest()->getPost();
        $roleId     = $post['id'];
        $role       = P4Cms_Acl_Role::fetch($roleId);

        // deny if role is a protected system role
        if ($role->isSystem()) {
            throw new P4Cms_AccessDeniedException(
                "System roles cannot be deleted."
            );
        }

        // deny if not super access level
        if (!$activeUser->isAdministrator()) {
            throw new P4Cms_AccessDeniedException(
                "You don't have permission to delete this role."
            );
        }

        // clear affected cache entries.
        $this->_clearCaches($role->getId(), $role->getUsers(), 'delete');

        // do the actual delete
        $role->delete();

        // for traditional requests, add message and redirect
        if (!$this->contextSwitch->getCurrentContext()) {

            P4Cms_Notifications::add(
                "Role '$roleId' has been deleted.",
                P4Cms_Notifications::SEVERITY_SUCCESS
            );
            $this->redirector->gotoSimple('index');
        }

        $this->view->roleId = $roleId;
    }

    /**
     * Adds the sytem user to the owners of associated group of the given role
     * to avoid deleting the role if no users are assigned as groups in Perforce
     * cannot be empty.
     *
     * Doesn't add system user to the owners of administrator's group as this
     * role is handled differently (it always should have users).
     *
     * @param P4Cms_Acl_Role    $role   role to add system user to the owners
     *                                  of the associated group.
     */
    protected function _addOwner(P4Cms_Acl_Role $role)
    {
        if ($role->getId() !== P4Cms_Acl_Role::ROLE_ADMINISTRATOR) {
            $user = $this->getInvokeArg('bootstrap')->getResource('perforce')->getUser();
            $role->addOwner($user);
        }
    }

    /**
     * Utility method to assist with clearing caches when roles are changed.
     *
     * @param   string  $role       the id of the role to clear.
     * @param   array   $users      list of ids of affected users.
     * @param   string  $action     one of: add, edit or delete
     */
    protected function _clearCaches($role, $users, $action)
    {
        $tags = array();

        // clear entries tagged as needing to be cleared when any role changes.
        $tags[] = 'p4cms_user_roles';

        // clear entries tagged with this specific role.
        $tags[] = 'p4cms_user_role_' . md5($role);

        // clear affected user specific caches
        foreach ($users as $user) {
            $tags[] = 'p4cms_user_' . md5($user);
        }

        P4Cms_Cache::clean('all', $tags);

        // if a role has been added or deleted, we need to clear
        // the sites cache as each site's acl has a list of roles.
        if ($action != 'edit') {
            P4Cms_Cache::remove(P4Cms_Site::CACHE_KEY, 'global');
        }
    }
}
