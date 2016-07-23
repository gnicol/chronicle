<?php
/**
 * This is the 'role' model. Each role corresponds to a
 * group in Perforce, except for the virtual roles which
 * always exist.
 *
 * Virtual roles cannot be permanently assigned to any user
 * and serve for special cases, such as to recognize unknown
 * (anonymous) user.
 *
 * Some roles are recognized as system roles - their existence
 * is guaranteed as their corresponding groups in Perforce
 * are created during site setup.
 *
 * Both system and virtual roles are protected from being
 * deleted or altered.
 *
 * The role class also introduces the concept of a 'parent group'.
 * A parent group acts like a folder under which all roles are
 * stored. When a parent group is set on the storage adapter, only
 * roles with the parent group name as a prefix are accessible.
 *
 * The prefix is read from the storage adapter and is not exposed
 * outside of the class. It is managed internally and automatically
 * prepended or stripped from role ids as appropriate. Any new roles
 * that are created will be added as sub-groups of the parent group.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Acl_Role extends P4Cms_Record_Connected implements Zend_Acl_Role_Interface
{
    const               PARENT_GROUP        = 'parentGroup';
    const               PREFIX_DELIMITER    = '--';

    const               FETCH_BY_MEMBER     = 'member';
    const               FETCH_HIDE_VIRTUAL  = 'hideVirtual';

    const               ROLE_ANONYMOUS      = 'anonymous';
    const               ROLE_MEMBER         = 'member';
    const               ROLE_ADMINISTRATOR  = 'administrator';

    const               TYPE_SYSTEM         = 'system';
    const               TYPE_CUSTOM         = 'custom';

    protected           $_p4Group           = null;

    protected static    $_systemRoles       = array(
                            self::ROLE_MEMBER,
                            self::ROLE_ADMINISTRATOR,
                            self::ROLE_ANONYMOUS
                        );
    protected static    $_virtualRoles      = array(
                            self::ROLE_ANONYMOUS,
                        );
    protected static    $_superRoles        = array(
                            self::ROLE_ADMINISTRATOR,
                        );
    protected static    $_idField           = 'id';
    protected static    $_fields            = array(
        'users'         => array(
            'accessor'  => 'getUsers',
            'mutator'   => 'setUsers'
        ),
        'type'          => array(
            'accessor'  => 'getType',
            'mutator'   => 'setType'
        )
    );

    /**
     * Defined by Zend_Acl_Role_Interface; returns the Role identifier
     *
     * @return string   id of this role
     */
    public function getRoleId()
    {
        // performance optimization - do not change without good reason.
        // effectively the same as calling getId(), but more direct.
        // under some circumstances getRoleId() can be called a great
        // many times and according to xdebug/webgrind this helps a
        // lot, according to real world testing, it helps a little.
        return isset($this->_values['id']) ? $this->_values['id'] : null;
    }

    /**
     * Get the members of this role.
     *
     * @return  array   list of all users having this role.
     */
    public function getUsers()
    {
        return $this->_getP4Group()->getUsers();
    }

    /**
     * Get members of this role that actually exist.
     *
     * @return  array   list of all valid users having this role.
     */
    public function getRealUsers()
    {
        $users = array();
        foreach ($this->getUsers() as $user) {
            if (P4Cms_User::exists($user, null, $this->getAdapter())) {
                $users[] = $user;
            }
        }

        return $users;
    }

    /**
     * Determine if given user has this role.
     *
     * @param   P4Cms_User  $user   user to check
     * @return  boolean             true if user has this role otherwise false
     */
    public function hasUser(P4Cms_User $user)
    {
        return in_array($user->getId(), $this->getUsers());
    }

    /**
     * Assign this role to the given user.
     *
     * @param   P4Cms_User      $user   user to add
     * @return  P4Cms_Acl_Role          provides a fluent interface
     */
    public function addUser(P4Cms_User $user)
    {
        $this->_getP4Group()->addUser($user->getId());
        return $this;
    }

    /**
     * Add given user to the owners list of the associated group in Perforce.
     *
     * @param   P4Cms_User|string       $user   id or instance of user to add
     * @return  P4Cms_Acl_Role          provides a fluent interface
     */
    public function addOwner($user)
    {
        $user = $user instanceof P4Cms_User ? $user->getId() : $user;

        if ($user !== null) {
            $this->_getP4Group()->addOwner($user);
        }

        return $this;
    }

    /**
     * Remove this role from a given user.
     *
     * @param   P4Cms_User      $user   user to remove the role from
     * @return  P4Cms_Acl_Role          provides a fluent interface
     */
    public function removeUser(P4Cms_User $user)
    {
        $users = $this->getUsers();
        $this->setUsers(array_diff($users, array($user->getId())));

        return $this;
    }

    /**
     * Assign this role to all of the given users.
     *
     * @param   array|P4Cms_Model_Iterator  $users  list of users to assign this role to
     * @return  P4Cms_Acl_Role              provides a fluent interface
     */
    public function setUsers($users = array())
    {
        // normalize to array/iterator form.
        $users = is_null($users) ? array() : $users;

        // collect user ids.
        $ids = array();
        foreach ($users as $user) {
            $ids[] = $user instanceof P4Cms_User ? $user->getId() : $user;
        }

        $this->_getP4Group()->setUsers($ids);

        return $this;
    }

    /**
     * Determine if this role is a system role.
     *
     * @return  boolean true if this role is a system role, false otherwise
     */
    public function isSystem()
    {
        return in_array($this->getId(), static::$_systemRoles);
    }

    /**
     * Determine if this role is a virtual role.
     *
     * @return  boolean true if this role is a virtual role, false otherwise
     */
    public function isVirtual()
    {
        return in_array($this->getId(), static::$_virtualRoles);
    }

    /**
     * Determine if this role is a super-user role.
     *
     * @param   Zend_Acl_Role_Interface|string  $role       role to check for
     * @return  boolean                                     true if given role is
     *                                                      a super role, false otherwise
     */
    public static function isSuper($role)
    {
        if ($role instanceof Zend_Acl_Role_Interface) {
            $role = $role->getRoleId();
        } else if (!is_string($role)) {
            throw new P4Cms_Acl_Exception(
                "isSuper() expects $role to be of type string or Zend_Acl_Role_Interface"
            );
        }

        return in_array($role, static::$_superRoles);
    }

    /**
     * Set the role id - extended to proxy to p4 group.
     * Add prefix to the id for associated group name in Perforce.
     *
     * @param   string|int|null     $id     the identifier of this record.
     * @return  P4Cms_Acl_Role              provides fluent interface.
     */

    public function setId($id)
    {
        parent::setId($id);

        // if role is not virtual, we need to add a prefix
        // to the id for the associated group in Perforce
        if (!$this->isVirtual()) {
            $adapter = $this->hasAdapter()
                ? $this->getAdapter()
                : static::getDefaultAdapter();
            $this->_getP4Group()->setId(
                static::_getGroupPrefix($adapter) . $id
            );
        }

        return $this;
    }

    /**
     * Save this role entry.
     *
     * @return  P4Cms_Acl_Role  provides fluent interface.
     */
    public function save()
    {
        // clear user roles cache
        P4Cms_User::clearRolesCache();

        // save the group spec.
        // if not connected as a super user, try as owner.
        $group = $this->_getP4Group();
        $owner = !$group->getConnection()->isSuperUser();
        $group->save($owner);

        // if a parent group is specified, add role to parent group.
        $adapter = $this->getAdapter();
        if ($adapter->hasProperty(static::PARENT_GROUP)
            && $adapter->getProperty(static::PARENT_GROUP)
        ) {
            $parent = P4_Group::fetch(
                $adapter->getProperty(static::PARENT_GROUP),
                $adapter->getConnection()
            );
            if (!in_array($group->getId(), $parent->getSubgroups())) {
                $parent->addSubgroup($group->getId())->save($owner);
            }
        }

        return $this;
    }

    /**
     * Delete this role entry.
     *
     * @return  P4Cms_Acl_Role          provides fluent interface.
     * @throws  P4Cms_User_Exception    if the role is protected as such roles cannot be deleted
     */
    public function delete()
    {
        // clear user roles cache
        P4Cms_User::clearRolesCache();

        // system roles cannot be deleted
        if ($this->isSystem()) {
            throw new P4Cms_Acl_Exception(
                "Cannot delete system role."
            );
        }

        // delete the group spec.
        $this->_getP4Group()->delete();

        return $this;
    }

    /**
     * Register given role as a super role.
     *
     * @param string|Zend_Acl_Role_Interface $role  role to register as a super role
     */
    public static function addSuperRole($role)
    {
        if ($role instanceof Zend_Acl_Role_Interface) {
            $role = $role->getRoleId();
        } else if (!is_string($role)) {
            throw new P4Cms_Acl_Exception(
                "addSuperRole() expects $role to be of type string or Zend_Acl_Role_Interface"
            );
        }

        static::$_superRoles[] = $role;
    }

    /**
     * Fetch the named role.
     *
     * @param   string                  $roleId     name of the role to fetch.
     * @param   array|null              $options    optional - no options are presently supported.
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  P4Cms_Acl_Role                      instance of the requested role.
     * @throws  P4Cms_Model_NotFoundException       if the requested role does not exist.
     */
    public static function fetch($roleId, array $options = null, P4Cms_Record_Adapter $adapter = null)
    {
        if (!is_array($options) && !is_null($options)) {
            throw new InvalidArgumentException(
                'Options must be an array or null'
            );
        }

        $adapter = $adapter ?: static::getDefaultAdapter();

        // attempt to fetch group from Perforce.
        try {
            $groupId = static::_getGroupPrefix($adapter) . $roleId;
            $p4Group = P4_Group::fetch($groupId, $adapter->getConnection());
        } catch (P4_Spec_NotFoundException $e) {
            // if role is not virtual, throw an exception
            if (!in_array($roleId, static::$_virtualRoles)) {
                throw new P4Cms_Model_NotFoundException(
                    "Cannot fetch role. Role '$roleId' does not exist."
                );
            }
        }

        $role = new static;
        $role->setAdapter($adapter)
             ->setId($roleId);

        if (isset($p4Group)) {
            $role->_setP4Group($p4Group);
        }

        return $role;
    }

    /**
     * Fetch all roles.
     *
     * Fetches groups from perforce (filtered by group prefix)
     * and adds virtual roles (unless excluded).
     *
     * @param   array                   $options    optional - array of options to augment
     *                                              fetch behavior.
     *                                              Supported options are:
     *                                              FETCH_BY_MEMBER - get roles for given user
     *                                              FETCH_HIDE_VIRTUAL - don't include virtual
     *                                              roles in the result
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  P4Cms_Model_Iterator                all user roles in the system.
     */
    public static function fetchAll(
        $options = array(),
        P4Cms_Record_Adapter $adapter = null)
    {
        $adapter = $adapter ?: static::getDefaultAdapter();
        $roles   = new P4Cms_Model_Iterator;
        $prefix  = static::_getGroupPrefix($adapter);

        // get roles represented by groups with given prefix
        foreach (P4_Group::fetchAll($options, $adapter->getConnection()) as $p4Group) {

            // skip groups without the required prefix.
            if (strpos($p4Group->getId(), $prefix) !== 0) {
                continue;
            }

            $role = new static;
            $role->setAdapter($adapter)
                 ->setId(substr($p4Group->getId(), strlen($prefix)))
                 ->_setP4Group($p4Group);

            $roles[] = $role;
        }

        // add virtual roles (with respect to options)
        if ((!isset($options[self::FETCH_HIDE_VIRTUAL]) || $options[self::FETCH_HIDE_VIRTUAL] != true)
            && !isset($options[self::FETCH_BY_MEMBER])
        ) {
            foreach (static::$_virtualRoles as $roleId) {
                $roles[] = static::fetch($roleId, null, $adapter);
            }
        }

        // ensure consistent ordering of roles.
        $flags = array();
        if (!$adapter->getConnection()->isCaseSensitive()) {
            $flags = array(P4Cms_Model_Iterator::SORT_NO_CASE);
        }
        $roles->sortBy('id', $flags);

        return $roles;
    }

    /**
     * Count all roles - extended to route through fetch all.
     *
     * @param   array                   $options    optional - array of options to augment count
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  integer                 The count of all matching records
     */
    public static function count(
        $options = array(),
        P4Cms_Record_Adapter $adapter = null)
    {
        return static::fetchAll($options, $adapter)->count();
    }

    /**
     * Check if the named role exists.
     *
     * @param   string                  $roleId     the name of the role to look for.
     * @param   array|null              $options    optional - no options are presently supported.
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  bool                                true if the role exists, false otherwise.
     */
    public static function exists($roleId, $options = null, P4Cms_Record_Adapter $adapter = null)
    {
        if (!is_array($options) && !is_null($options)) {
            throw new InvalidArgumentException(
                'Options must be an array or null'
            );
        }

        try {
            static::fetch($roleId, null, $adapter);
            return true;
        } catch (P4Cms_Model_NotFoundException $e) {
            return false;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Set given roles to the given user, i.e. after this action, user will have
     * only the roles given in the parameter.
     *
     * @param P4Cms_User                $user       user to set roles.
     * @param array|null                $roles      list of roles to assign.
     * @param P4Cms_Record_Adapter|null $adapter    optional - storage adapter to use.
     */
    public static function setUserRoles(
        P4Cms_User $user,
        array $roles = null,
        P4Cms_Record_Adapter $adapter = null
    )
    {
        $roles      = $roles ?: array();
        $adapter    = $adapter ?: static::getDefaultAdapter();
        $userRoles  = $user->getRoles()->invoke('getId');

        // add new roles
        foreach (array_diff($roles, $userRoles) as $roleId) {
            static::fetch($roleId, null, $adapter)->addUser($user)
                                                  ->save();
        }

        // remove roles (remove administrator role last)
        $roleAdministrator = null;
        foreach (array_diff($userRoles, $roles) as $roleId) {
            $role = static::fetch($roleId, null, $adapter)->removeUser($user);
            $roleId === static::ROLE_ADMINISTRATOR
             ? $roleAdministrator = $role
             : $role->save();
        }

        // remove administrator role if needed
        if ($roleAdministrator !== null) {
            $roleAdministrator->save();
        }
    }

    /**
     * Get the type of this role (system or custom).
     *
     * @return  string  the type of this role.
     */
    public function getType()
    {
        return $this->isSystem()
            ? static::TYPE_SYSTEM
            : static::TYPE_CUSTOM;
    }

    /**
     * Make type a read-only field.
     *
     * @throws  P4Cms_Acl_Exception     type is read-only.
     */
    public function setType()
    {
        throw new P4Cms_Acl_Exception("Cannot set type. Type is read-only.");
    }

    /**
     * Set the corresponding p4 group object instance.
     * Used when fetching groups to prime the role object.
     *
     * @param   P4_Group                $group  the corresponding P4_Group object.
     * @return  P4Cms_Acl_Role          provides fluent interface.
     * @throws  P4Cms_User_Exception    if the role is a virtual role or the given group is
     *                                  not a valid P4_Group object.
     */
    protected function _setP4Group(P4_Group $group)
    {
        // virtual roles can't have corresponding Perforce group.
        if ($this->isVirtual()) {
            throw new P4Cms_Acl_Exception(
                "Cannot set p4 group for virtual roles."
            );
        }

        $this->_p4Group = $group;

        return $this;
    }

    /**
     * Get the p4 group object that corresponds to this role.
     *
     * @return  P4_Group                corresponding p4 group instance.
     * @throws  P4Cms_User_Exception    throw exception if role is a virtual role.
     */
    protected function _getP4Group()
    {
        // virtual roles have no corresponding Perforce group.
        if ($this->isVirtual()) {
            throw new P4Cms_Acl_Exception(
                "Cannot get p4 group for virtual roles."
            );
        }

        // only fetch once.
        if (!$this->_p4Group instanceof P4_Group) {
            $adapter        = $this->getAdapter();
            $this->_p4Group = new P4_Group($adapter->getConnection());
            $this->_p4Group->setId(
                static::_getGroupPrefix($adapter) . $this->getId()
            );
        }

        return $this->_p4Group;
    }

    /**
     * Get the group prefix property of the record adapter.
     *
     * @param   P4Cms_Record_Adapter    $adapter    the adapter to get the property from
     * @return  string                  the group prefix or null if no prefix set.
     */
    protected static function _getGroupPrefix(P4Cms_Record_Adapter $adapter)
    {
        return $adapter->hasProperty(static::PARENT_GROUP)
            ? $adapter->getProperty(static::PARENT_GROUP) . static::PREFIX_DELIMITER
            : '';
    }
}
