<?php
/**
 * Manages user permissions.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class User_AclController extends Zend_Controller_Action
{
    public $contexts = array(
        'index'     => array('json'),
        'save'      => array('json')
    );

    /**
     * Set management layout as default layout for acl.
     */
    public function init()
    {
        $this->_helper->layout->setLayout('manage-layout');
    }

    /**
     * List all permissions.
     *
     * @publishes   p4cms.user.acl.roles
     *              Adjust the passed iterator (possibly based on values in the passed form) to
     *              filter which roles will be shown on the Manage Permissions grid.
     *              P4Cms_Model_Iterator        $roles      An iterator of P4Cms_Acl_Role objects.
     *              P4Cms_Form_PubSubForm       $form       A form containing filter options.
     *
     * @publishes   p4cms.user.acl.permissions
     *              Adjust the passed iterator (possibly based on values in the passed form) to
     *              filter which permissions will be shown on the Manage Permissions grid.
     *              P4Cms_Model_Iterator        $permissions    An iterator of P4Cms_Model objects
     *                                                          (representing each permission).
     *              P4Cms_Form_PubSubForm       $form           A form containing filter options.
     *
     * @publishes   p4cms.user.acl.grid.data.item
     *              Return the passed item after applying any modifications (add properties, change
     *              values, etc.) to influence the row values sent to the Manage Permissions grid.
     *              array                       $item       The item to potentially modify.
     *              mixed                       $model      The original object/array that was used
     *                                                      to make the item.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.user.acl.grid.data
     *              Adjust the passed data (add properties, modify values, etc.) to influence the
     *              row values sent to the Manage Permissions grid.
     *              Zend_Dojo_Data              $data       The data to be filtered.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this topic.
     *
     * @publishes   p4cms.user.acl.grid.render
     *              Make adjustments to the datagrid helper's options pre-render (i.e. change
     *              options) for the Manage Permissions grid.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.user.acl.grid.form
     *              Make arbitrary modifications to the Manage Permissions filters form.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.user.acl.grid.form.subForms
     *              Return a Form (or array of Forms) to have them added to the Manage Permissions
     *              filters form. The returned form(s) should have a 'name' set on them to allow
     *              them to be uniquely identified.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.user.acl.grid.form.preValidate
     *              Allows subscribers to adjust the Manage Permissions filters form prior to
     *              validation of the passed data. For example, modify element values based on
     *              related selections to permit proper validation.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.user.acl.grid.form.validate
     *              Return false to indicate the Manage Permissions filters form is invalid. Return
     *              true to indicate your custom checks were satisfied, so form validity should be
     *              unchanged.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.user.acl.grid.form.populate
     *              Allows subscribers to adjust the Manage Permissions filters form after it has
     *              been populated with the passed data.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     The values passed to the populate
     *                                                      method.
     */
    public function indexAction()
    {
        // enforce permissions
        $this->acl->check('users', 'manage-acl');

        // grab current acl.
        $acl = $this->acl->getAcl();

        // setup grid options form.
        $request        = $this->getRequest();
        $gridNamespace  = 'p4cms.user.acl.grid';
        $form           = new User_Form_AclGridOptions(
            array(
                'acl'       => $acl,
                'namespace' => $gridNamespace
            )
        );
        $form->populate($request->getParams());

        // setup view.
        $view               = $this->view;
        $view->acl          = $acl;
        $view->form         = $form;
        $view->pageSize     = $request->getParam('count', 100);
        $view->rowOffset    = $request->getParam('start', 0);
        $view->pageOffset   = round($view->rowOffset / $view->pageSize, 0) + 1;
        $view->headTitle()->set('Manage Permissions');

        // set DataGrid view helper namespace
        $helper = $view->dataGrid();
        $helper->setNamespace($gridNamespace);

        // prepare different data for different request contexts.
        if (!$this->contextSwitch->getCurrentContext()) {

            // load roles and rules for standard requests
            $roles = P4Cms_Acl_Role::fetchAll();
            $rules = $this->_getRules($acl);

            // allow third-parties to influence roles in acl
            P4Cms_PubSub::publish('p4cms.user.acl.roles', $roles, $form);

            $this->view->roles = $roles;
            $this->view->rules = $rules;

            $this->getHelper('helpUrl')->setUrl('permissions.manage.html');
        } else {

            // collect permissions for other (e.g. json) requests.
            $permissions = $this->_getPermissions($acl);

            // allow third-parties to influence permissions list
            P4Cms_PubSub::publish('p4cms.user.acl.permissions', $permissions, $form);

            // sort permissions by resource first and privilege second.
            $permissions->sortBy(
                array('resourceLabel', 'privilegeLabel'),
                array(P4Cms_Model_Iterator::SORT_NATURAL, P4Cms_Model_Iterator::SORT_NO_CASE)
            );

            $this->view->permissions = $permissions;
        }
    }

    /**
     * Save modified rules.
     */
    public function saveAction()
    {
        // enforce permissions
        $this->acl->check('users', 'manage-acl');

        // only accept post requests.
        $request = $this->getRequest();
        if (!$request->isPost()) {
            throw new P4Cms_AccessDeniedException(
                "You may only save ACL via HTTP post."
            );
        }

        // rules are expected as a multi-dimensional array
        // organized into roles that contain resources which
        // contain privileges:
        //
        //  array(
        //      <role> => array(
        //          <resource> => array(
        //              <privilege> => array('allowed' => true,  'disabled' => false),
        //              <privilege> => array('allowed' => false, 'disabled' => true)
        //          ),
        //          <resource> => ...
        //      ),
        //      <role> => ...
        //  )
        //
        $rules = $request->getPost('rules');

        // if context is json, rules must be decoded.
        if ($this->contextSwitch->getCurrentContext() == 'json') {
            $rules = Zend_Json::decode($rules);
        }

        // grab the active acl.
        $acl = $this->acl->getAcl();

        // iterate over roles, resources and privileges
        // set allow rule if privilege evaluates to true,
        // remove rule if it is false (ie. no explicit 'deny').
        foreach ((array) $rules as $role => $resources) {
            // resources must be an array.
            if (!is_array($resources)) {
                continue;
            }

            foreach ($resources as $resource => $privileges) {

                // skip invalid resources.
                if (!$acl->has($resource)) {
                    continue;
                }

                $resource = $acl->get($resource);

                // privileges must be an array.
                if (!is_array($privileges)) {
                    continue;
                }

                // set each privilege rule.
                foreach ($privileges as $privilege => $rule) {

                    // skip non-existant privileges.
                    if (!$resource->hasPrivilege($privilege)) {
                        continue;
                    }

                    $privilege = $resource->getPrivilege($privilege);

                    // skip disabled privileges.
                    if ($this->_isDisabledPrivilege($privilege, $acl->getRole($role))) {
                        continue;
                    }

                    // use a proxy for assertions to allow for assert
                    // classes that might not exist at all times.
                    $assert = $privilege->getOption('assertion');
                    if ($assert) {
                        $assert = new P4Cms_Acl_Assert_Proxy($assert);
                    }

                    // set the acl rule
                    $operation = isset($rule['allowed']) && $rule['allowed']
                        ? P4Cms_Acl::OP_ADD
                        : P4Cms_Acl::OP_REMOVE;
                    $this->acl->getAcl()->setRule(
                        $operation,
                        P4Cms_Acl::TYPE_ALLOW,
                        $role,
                        $resource,
                        $privilege->getId(),
                        $assert
                    );
                }
            }
        }

        // save the acl.
        $acl->save();

        // ACL changes can have quite an affect; clear caches
        P4Cms_Cache::clean();

        $this->redirector->gotoSimple('index');
    }

    /**
     * Reset acl resources and privileges to defaults.
     */
    public function resetAction()
    {
        // enforce permissions
        $this->acl->check('users', 'manage-acl');

        $acl = $this->acl->getAcl();

        // reset acl and re-install defaults.
        $acl->removeAll()
            ->installDefaults()
            ->save();

        // ACL changes can have quite an affect; clear caches
        P4Cms_Cache::clean();

        // notify user of reset.
        P4Cms_Notifications::add(
            'Permissions Reset',
            P4Cms_Notifications::SEVERITY_SUCCESS
        );

        $this->redirector->gotoSimple('index');
    }

    /**
     * Get all of the privilege rules organized by role and then resource.
     *
     * @param   P4Cms_Acl   $acl    the acl to read rules from.
     * @return  array       the list of all privilege rules grouped by role and then resource.
     */
    protected function _getRules(P4Cms_Acl $acl)
    {
        $rules = array();
        foreach ($acl->getRoles() as $role) {
            $rules[$role] = $this->_getRoleRules($role, $acl);
        }

        return $rules;
    }

    /**
     * Get all of the privilege rules for the given role, organized by resource.
     *
     * @param   string      $role   the role to get rules for.
     * @param   P4Cms_Acl   $acl    the acl to read rules from.
     * @return  array       list of privilege rules for the given role, grouped by resource.
     */
    protected function _getRoleRules($role, P4Cms_Acl $acl)
    {
        $rules = array();
        foreach ($acl->getResourceObjects() as $resource) {
            $rules[$resource->getId()] = array();
            foreach ($resource->getPrivileges() as $privilege) {

                // skip hidden privileges.
                if ($privilege->getOption('hidden')) {
                    continue;
                }

                // determine if role is allowed access to privilege.
                $allowed = $acl->isAllowed(
                    $role,
                    $resource->getId(),
                    $privilege->getId()
                );

                $rules[$resource->getId()][$privilege->getId()] = array(
                    'allowed'  => $allowed,
                    'disabled' => $this->_isDisabledPrivilege($privilege, $acl->getRole($role))
                );
            }
        }

        return $rules;
    }

    /**
     * Combine resources and privileges into one list of permissions.
     *
     * Each list entry contains:
     *  - type
     *  - resourceId
     *  - resourceLabel
     *  - privilegeId    (null for resources)
     *  - privilegeLabel (null for resources)
     *
     * @param   P4Cms_Acl   $acl        the acl to read permissions from.
     * @return  P4Cms_Model_Iterator    a iterator of resources and privileges.
     */
    protected function _getPermissions(P4Cms_Acl $acl)
    {
        $permissions = new P4Cms_Model_Iterator;
        foreach ($acl->getResourceObjects() as $resource) {

            // add the resource first.
            $permissions[] = new P4Cms_Model(
                array(
                    'type'              => 'resource',
                    'resourceId'        => $resource->getId(),
                    'resourceLabel'     => $resource->getLabel(),
                    'privilegeId'       => null,
                    'privilegeLabel'    => null,
                    'options'           => array()
                )
            );

            // add any associated privileges.
            foreach ($resource->getPrivileges() as $privilege) {

                // skip hidden privileges.
                if ($privilege->getOption('hidden')) {
                    continue;
                }

                $permissions[] = new P4Cms_Model(
                    array(
                        'type'              => 'privilege',
                        'resourceId'        => $resource->getId(),
                        'resourceLabel'     => $resource->getLabel(),
                        'privilegeId'       => $privilege->getId(),
                        'privilegeLabel'    => $privilege->getLabel(),
                        'options'           => $privilege->getOptions()
                    )
                );
            }

        }

        return $permissions;
    }

    /**
     * Check if the given privilege is disabled for the named role.
     * A privilege is disabled if it is locked or if it requires super
     * user access and the given role is not a super-user role.
     *
     * @param   P4Cms_Acl_Privilege     $privilege  the privilege to check.
     * @param   Zend_Acl_Role_Interface $role       the role to check for.
     */
    protected function _isDisabledPrivilege(P4Cms_Acl_Privilege $privilege, Zend_Acl_Role_Interface $role)
    {
        $needsSuper = $privilege->getOption('needsSuper');
        $locked     = $privilege->getOption('locked');
        if (is_array($locked)) {
            $locked = in_array($role->getRoleId(), $locked);
        }

        if ($locked || ($needsSuper && !P4Cms_Acl_Role::isSuper($role->getRoleId()))) {
            return true;
        }

        return false;
    }
}
