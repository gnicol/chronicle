<?php
/**
 * This is the user model. Each user corresponds to a
 * user in Perforce.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_User extends P4Cms_Record_Connected implements Zend_Auth_Adapter_Interface
{
    const               FETCH_BY_NAME       = 'name';
    const               FETCH_MAXIMUM       = 'maximum';
    const               FETCH_SYSTEM_USER   = 'systemUser';

    protected           $_p4User            = null;
    protected           $_personalAdapter   = null;
    protected static    $_rolesCache        = array();

    protected static    $_activeUser        = null;
    protected static    $_acl               = null;
    protected static    $_idField           = 'id';
    protected static    $_fields            = array(
        'fullName'      => array(
            'accessor'  => 'getFullName',
            'mutator'   => 'setFullName'
        ),
        'email'         => array(
            'accessor'  => 'getEmail',
            'mutator'   => 'setEmail'
        ),
        'password'      => array(
            'accessor'  => 'getPassword',
            'mutator'   => 'setPassword'
        )
    );

    /**
     * Clear the static roles cache entirely.
     */
    public static function clearRolesCache()
    {
        static::$_rolesCache = array();
    }

    /**
     * Check if the named user exists.
     *
     * @param   string                  $username   the username of the user to look for.
     * @param   array|null              $options    optional - no options are presently supported.
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  bool    true if the user exists, false otherwise.
     */
    public static function exists($username, $options = null, P4Cms_Record_Adapter $adapter = null)
    {
        if (!is_array($options) && !is_null($options)) {
            throw new InvalidArgumentException(
                'Options must be an array or null'
            );
        }

        try {
            static::fetch($username, null, $adapter);
            return true;
        } catch (P4Cms_Model_NotFoundException $e) {
            return false;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * Fetch the named user.
     *
     * @param   string                  $username   the username of the user to fetch.
     * @param   array|null              $options    optional - no options are presently supported.
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  P4Cms_User              instance of the requested user.
     * @throws  P4Cms_Model_NotFoundException   if the requested user does not exist.
     */
    public static function fetch($username, array $options = null, P4Cms_Record_Adapter $adapter = null)
    {
        if (!is_array($options) && !is_null($options)) {
            throw new InvalidArgumentException(
                'Options must be an array or null'
            );
        }

        $adapter = $adapter ?: static::getDefaultAdapter();

        // attempt to fetch user from perforce.
        try {
            $p4User = P4_User::fetch($username, $adapter->getConnection());
        } catch (P4_Spec_NotFoundException $e) {
            throw new P4Cms_Model_NotFoundException(
                "Cannot fetch user. User '$username' does not exist."
            );
        }

        // create new user instance
        $user = new static;
        $user->setAdapter($adapter)
             ->setId($username)
             ->_setP4User($p4User);

        return $user;
    }

    /**
     * Fetch all users in the system (ie. get users from Perforce).
     *
     * @param   array   $options    optional - array of options to augment fetch behavior.
     *                              supported options are:
     *
     *                                  FETCH_MAXIMUM - set to integer value to limit to the
     *                                                  first 'max' number of entries.
     *                                  FETCH_BY_NAME - set to user name pattern (e.g. 'jdo*'),
     *                                                  can be a single string or array of strings.
     *                              FETCH_SYSTEM_USER - set to true to include the system user
     *                                                  defaults to false (system user is excluded)
     *
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  P4Cms_Model_Iterator    all users in the system.
     */
    public static function fetchAll(
        array $options = null,
        P4Cms_Record_Adapter $adapter = null)
    {
        $adapter = $adapter ?: static::getDefaultAdapter();

        $users = new P4Cms_Model_Iterator;
        foreach (P4_User::fetchAll($options, $adapter->getConnection()) as $p4User) {
            $user = new static;
            $user->setAdapter($adapter)
                 ->setId($p4User->getId())
                 ->_setP4User($p4User);

            $users[] = $user;
        }

        // exclude system user by default
        if ((!isset($options[static::FETCH_SYSTEM_USER]) || !$options[static::FETCH_SYSTEM_USER])
            && P4Cms_Site::hasActive()
        ) {
            // we assume the active site is running as the system user; get the id
            $systemUser = P4Cms_Site::fetchActive()->getConnection()->getUser();
            $users->filter('id', $systemUser, array(P4Cms_Model_Iterator::FILTER_INVERSE));
        }

        return $users;
    }

    /**
     * Fetch all role member users.
     *
     * @param   P4Cms_Acl_Role|string|array     $role       role or list of roles to fetch members of.
     * @param   P4Cms_Record_Adapter            $adapter    optional, storage adapter to use.
     * @return  P4Cms_Model_Iterator            role(s)     member users.
     */
    public static function fetchByRole($role, P4Cms_Record_Adapter $adapter = null)
    {
        if (is_string($role) || $role instanceof P4Cms_Acl_Role) {
            $roles = array($role);
        } else if (is_array($role)) {
            $roles = $role;
        } else {
            throw new InvalidArgumentException(
                "Role must be an instance of P4Cms_Acl_Role or a string or an array."
            );
        }

        $users = array();
        foreach ($roles as $role) {
            // if role is not instance of P4Cms_Acl_Role, try to fetch it
            if (!$role instanceof P4Cms_Acl_Role) {
                if (!P4Cms_Acl_Role::exists($role, null, $adapter)) {
                    break;
                }
                $role = P4Cms_Acl_Role::fetch($role, null, $adapter);
            }

            // add role users to the users list
            $users = array_merge($users, $role->getUsers());
        }

        // early exit if no users to fetch
        if (!count($users)) {
            return new P4Cms_Model_Iterator;
        }

        // fetch all member users
        return static::fetchAll(array(static::FETCH_BY_NAME => array_unique($users)), $adapter);
    }

    /**
     * Count all users - extended to route through fetch all.
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
     * Return the user's email-address.
     *
     * @return  string|null  the user's email address
     */
    public function getEmail()
    {
        return $this->_getP4User()->getEmail();
    }

    /**
     * Return the user's full name.
     *
     * @return  string|null  the user's full name
     */
    public function getFullName()
    {
        return $this->_getP4User()->getFullName();
    }

    /**
     * Get the in-memory password (if one is set).
     *
     * @return  string|null the in-memory password.
     */
    public function getPassword()
    {
        return $this->_getP4User()->getPassword();
    }

    /**
     * Fetch the currently active user.
     * Guaranteed to return the active user model or throw an exception.
     *
     * @return  P4Cms_User              the currently active user.
     * @throws  P4Cms_User_Exception    if there is no currently active user.
     * @todo    throw a specific type of exception.
     */
    public static function fetchActive()
    {
        if (!static::$_activeUser || !static::$_activeUser instanceof P4Cms_User) {
            throw new P4Cms_User_Exception("There is no currently active user.");
        }

        return static::$_activeUser;
    }

    /**
     * Determine if there is an active user.
     *
     * @return  boolean     true if there is an active user
     */
    public static function hasActive()
    {
        try {
            static::fetchActive();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Set the active user.
     *
     * @param   P4Cms_User  $user  the user model instance to make active.
     */
    public static function setActive(P4Cms_User $user)
    {
        static::$_activeUser = $user;
    }

    /**
     * Clear the active user.
     */
    public static function clearActive()
    {
        static::$_activeUser = null;
    }

    /**
     * Determine if this user is anonymous (has no id).
     *
     * @return  bool    true if the user is anonymous.
     */
    public function isAnonymous()
    {
        return !(bool) strlen($this->getId());
    }

    /**
     * Determine if this user has member role.
     *
     * @return  bool    true if the user has member role, false otherwise.
     */
    public function isMember()
    {
        return in_array(P4Cms_Acl_Role::ROLE_MEMBER, $this->getRoles()->invoke('getId'));
    }

    /**
     * Determine if this user has administrator role.
     *
     * @return  bool    true if the user has administrator role, false otherwise.
     */
    public function isAdministrator()
    {
        return in_array(P4Cms_Acl_Role::ROLE_ADMINISTRATOR, $this->getRoles()->invoke('getId'));
    }

    /**
     * Test if the given password is correct for this user.
     *
     * @param   string  $password   the password to test.
     * @return  bool    true if the password is correct, false otherwise.
     */
    public function isPassword($password)
    {
        return $this->_getP4User()->isPassword($password);
    }

    /**
     * Determine if this user is allowed to access a particular resource
     * and (optionally) a particular privilege on the resource.
     *
     * @param   P4Cms_Acl_Resource|string           $resource   the resource to check access to.
     * @param   P4Cms_Acl_Privilege|string|null     $privilege  optional - the privilege to check.
     * @param   P4Cms_Acl|null                      $acl        optional - the acl to check against.
     *                                                          defaults to the currently active acl.
     * @return  bool    true if the user is allowed access to the resource.
     *
     * @publishes   p4cms.acl.users.privileges
     *              Gathers the resource privileges for authorization checks, or for presentation by
     *              the User module.
     *              P4Cms_Acl_Resource  $resource   The resource that must be checked for
     *                                              appropriate privileges.
     */
    public function isAllowed($resource, $privilege = null, P4Cms_Acl $acl = null)
    {
        $acl = $acl ?: P4Cms_Acl::fetchActive();

        // user is allowed access if any of the roles are.
        foreach ($this->getRoles() as $role) {
            try {
                if ($acl->isAllowed($role, $resource, $privilege)) {
                    return true;
                }
            } catch (Zend_Acl_Exception $e) {
                // acl throws if the resource doesn't exist, but
                // we don't consider this a throw-able offense here.
                // we do however treat it as permission denied.
            }
        }

        return false;
    }

    /**
     * Return list of all privileges for which user has access to a given resource.
     *
     * @param   P4Cms_Acl_Resource|string           $resource   the resource to check access to.
     * @param   P4Cms_Acl|null                      $acl        optional - the acl to check against.
     *                                                          defaults to the currently active acl.
     * @return  array                                           list of all privileges for which user
     *                                                          user has access to a given resource.
     */
    public function getAllowedPrivileges($resource, P4Cms_Acl $acl = null)
    {
        $acl        = $acl ?: P4Cms_Acl::fetchActive();
        $roles      = $this->getRoles()->toArray(true);
        $privileges = array();

        // user is allowed access if any of the roles are.
        foreach ($roles as $role) {
            $privileges = array_merge(
                $privileges,
                $acl->getAllowedPrivileges($role, $resource)
            );
        }

        return array_unique($privileges);
    }

    /**
     * Get the roles that this user belongs to.
     * Caches the results of P4Cms_Acl_Role::fetchAll().
     *
     * @return  P4Cms_Model_Iterator    the roles that this user is a member of.
     */
    public function getRoles()
    {
        // if user is un-identified, user belongs to anonymous role
        if ($this->isAnonymous()) {
            $role    = new P4Cms_Acl_Role;
            $role->setId(P4Cms_Acl_Role::ROLE_ANONYMOUS);
            $roles   = new P4Cms_Model_Iterator;
            $roles[] = $role;

            return $roles;
        }

        // for other users, roles are cached based on the adapter and user id
        $adapter  = $this->getAdapter();
        $userId   = $this->getId();
        $cacheKey = spl_object_hash($adapter) . md5($userId);

        // load the user roles (but only fetch them once)
        if (!array_key_exists($cacheKey, static::$_rolesCache)) {
            // fetch roles that user is a member of
            $roles = P4Cms_Acl_Role::fetchAll(
                array(P4Cms_Acl_Role::FETCH_BY_MEMBER => $userId),
                $adapter
            );

            static::$_rolesCache[$cacheKey] = $roles;
        }

        return static::$_rolesCache[$cacheKey];
    }

    /**
     * Generate a single role that inherits from all of the roles
     * that this user has and register it with the acl temporarily
     * (the role is not saved).
     *
     * This allows us to specify a single role when checking if the
     * user is allowed access to a given resource/privilege.
     *
     * @param   P4Cms_Acl|null  $acl    optional - the acl to check against.
     *                                  defaults to the currently active acl.
     * @return  string                  the id of the generated role combining
     *                                  all this user's roles or the id of an
     *                                  existing role if the user has only one.
     * @throws  P4Cms_User_Exception    if the user has no roles.
     */
    public function getAggregateRole(P4Cms_Acl $acl = null)
    {
        $acl = $acl ?: P4Cms_Acl::fetchActive();

        // can't get aggregate role if no roles.
        $roles = $this->getRoles();
        if (count($roles) == 0) {
            throw new P4Cms_User_Exception(
                "Cannot get aggregate role for a user with no roles."
            );
        }

        // no need to aggregate if user has one role.
        if (count($roles) <= 1) {
            return $roles->first()->getId();
        }

        // generate unique name.
        $i      = 0;
        $roles  = $roles->invoke('getId');
        $roleId = $this->getId() . "-" . implode('-', $roles);
        while ($acl->hasRole($roleId)) {
            $roleId = $this->getId() . "-" . implode('-', $roles) . "-" . ++$i;
        }

        // register role as super if any of the partial roles is super
        foreach ($roles as $role) {
            if (P4Cms_Acl_Role::isSuper($role)) {
                P4Cms_Acl_Role::addSuperRole($roleId);
                break;
            }
        }

        // add role to acl, but don't save role.
        $acl->addRole($roleId, $roles);

        return $roleId;
    }

    /**
     * Overrides parent to set adapter's connection for associated P4_User in addition.
     *
     * @param   P4Cms_Record_Adapter    $adapter    the adapter to use for this instance.
     * @return  P4Cms_User                          provides fluent interface.
     */
    public function setAdapter(P4Cms_Record_Adapter $adapter)
    {
        $this->_getP4User()->setConnection($adapter->getConnection());
        return parent::setAdapter($adapter);
    }

    /**
     * Set the user id - extended to proxy to p4 user.
     *
     * @param   string|int|null     $id     the identifier of this record.
     * @return  P4Cms_Record        provides fluent interface.
     * @todo    move more validation into record id validator
     * @todo    reject empty strings ''.
     */
    public function setId($id)
    {
        $this->_getP4User()->setId($id);

        return parent::setId($id);
    }

    /**
     * Set the user's email-address.
     *
     * @param   string|null $email  the user's email address
     * @return  P4Cms_User  provides fluent interface
     */
    public function setEmail($email)
    {
        $this->_getP4User()->setEmail($email);

        return $this;
    }

    /**
     * Set the user's full name.
     *
     * @param  string|null  $name   the user's full name
     * @return  P4Cms_User  provides fluent interface
     */
    public function setFullName($name)
    {
        $this->_getP4User()->setFullName($name);

        return $this;
    }

    /**
     * Set the user's password to the given password.
     * Does not take effect until save() is called.
     *
     * @param   string|null     $newPassword    the new password string or
     *                                          null to clear in-memory password.
     * @param   string          $oldPassword    optional - existing password.
     * @return  P4_User         provides fluent interface.
     */
    public function setPassword($newPassword, $oldPassword = null)
    {
        $this->_getP4User()->setPassword($newPassword, $oldPassword);

        return $this;
    }

    /**
     * Generate a pseudo-random password, alternating consonants and vowels to
     * assist human readability. Password strength is flexible:
     *
     *  0 = lowercase letters only
     *  1 = add uppercase consonants
     *  2 = add uppercase vowels
     *  3 = add numbers
     *  4 = add special characters
     *
     * @param   integer $length      the desired length of the password.
     * @param   integer $strength    the desired strength of the password.
     * @return  string  the generated password.
     */
    public static function generatePassword($length, $strength = 0)
    {
        // vowels and consonants excluding the letters o, i and l
        // because they can be mistaken for other letters or numbers.
        $vowels     = 'aeuy';
        $consonants = 'bcdfghjkmnpqrstvwxyz';

        if ($strength >= 1) {
            $consonants .= strtoupper($consonants);
        }

        if ($strength >= 2) {
            $vowels .= strtoupper($vowels);
        }

        // excludes the numbers 0 and 1 because they can be mistaken for letters.
        if ($strength >= 3) {
            $consonants .= '23456789';
        }

        if ($strength >= 4) {
            $consonants .= '@$%^';
        }

        $password = '';
        $alt      = rand() % 2;

        for ($i = 0; $i < $length; $i++) {
            if ($alt == 1) {
                $password .= $consonants[ (rand() % strlen($consonants)) ];
                $alt = 0;
            } else {
                $password .= $vowels[ (rand() % strlen($vowels)) ];
                $alt = 1;
            }
        }

        return $password;
    }

    /**
     * Save this user entry.
     *
     * @return  P4Cms_User  provides fluent interface.
     */
    public function save()
    {
        // save the user spec.
        $this->_getP4User()->save();

        return $this;
    }

    /**
     * Delete this user entry.
     *
     * @return  P4Cms_User  provides fluent interface.
     */
    public function delete()
    {
        // if user with personal adapter (active user) is going to be deleted,
        // run disconnect callbacks before removing the user from Perforce,
        // otherwise user may be resurrected if disconnect callbacks use
        // user's connection (e.g. for user's workspace clean-up etc.)
        if ($this->hasPersonalAdapter()) {
            $connection = $this->getPersonalAdapter()->getConnection();

            // run disconnect callbacks and clear them after to ensure they
            // are not called again after user is removed from Perforce
            $connection->runDisconnectCallbacks()
                       ->clearDisconnectCallbacks();
        }

        // delete the user spec last
        $this->_getP4User()->delete();

        // disconnect user with personal adapter
        if (isset($connection)) {
            $connection->disconnect();
        }

        return $this;
    }

    /**
     * Performs an authentication attempt
     *
     * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        // authenticate against current p4 server.
        $p4 = P4_Connection::factory(
            $this->getAdapter()->getConnection()->getPort(),
            $this->getId(),
            null,
            $this->getPassword()
        );

        try {
            $ticket = $p4->login();

            // deny if user has no real roles
            if (!$this->getRoles()->count()) {
                return new Zend_Auth_Result(
                    Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS,
                    null,
                    array('At least one role is required for successful authentication.')
                );
            }

            return new Zend_Auth_Result(
                Zend_Auth_Result::SUCCESS,
                array('id' => $this->getId(), 'ticket' => $ticket)
            );
        } catch (P4_Connection_LoginException $e) {
            return new Zend_Auth_Result(
                $e->getCode(),
                null,
                array($e->getMessage())
            );
        }
    }

    /**
     * Set the personal storage adapter for this user.
     *
     * @param   P4Cms_Record_Adapter    $adapter    the personal adapter
     * @return  P4Cms_User              provides fluent interface
     */
    public function setPersonalAdapter(P4Cms_Record_Adapter $adapter = null)
    {
        $this->_personalAdapter = $adapter;

        return $this;
    }

    /**
     * Determine if a personalized adapter has been set for this user.
     *
     * @return  bool    true if a personal adapter is set; false otherwise.
     */
    public function hasPersonalAdapter()
    {
        try {
            $this->getPersonalAdapter();
            return true;
        } catch (P4Cms_User_Exception $e) {
            return false;
        }
    }

    /**
     * Get the personalized record storage adapter for this user.
     *
     * @return  P4Cms_Record_Adapter    a personalized storage adapter.
     * @throws  P4Cms_User_Exception    if no personal adapter has been set.
     */
    public function getPersonalAdapter()
    {
        // balk if no adapter set.
        if (!$this->_personalAdapter instanceof P4Cms_Record_Adapter) {
            throw new P4Cms_User_Exception(
                "Cannot get personal storage adapter. No personal adapter has been set."
            );
        }

        return $this->_personalAdapter;
    }

    /**
     * Generate a storage adapter that communicates with Perforce as this user.
     *
     * @param   string      $ticket     optional - auth ticket to use for p4 connection
     * @param   P4Cms_Site  $site       optional - site to get personal adapter for
     *                                  (defaults to active site)
     * @return  P4Cms_Record_Adapter    a personalized storage adapter.
     */
    public function createPersonalAdapter($ticket = null, P4Cms_Site $site = null)
    {
        $site = $site ?: P4Cms_Site::fetchActive();

        // to avoid problems that result from multiple processes
        // sharing one client (namely race conditions), we generate
        // a temporary client for each request.
        $tempClientId = P4_Client::makeTempId();

        // create connection based on the active site.
        $connection = P4_Connection::factory(
            $site->getConnection()->getPort(),
            $this->getId(),
            $tempClientId,
            null,
            $ticket ?: null
        );

        // store client files under given site's workspaces path.
        $root = $site->getWorkspacesPath() . "/" . $tempClientId;

        // provide a custom clean-up callback to delete the workspace folder.
        $cleanup = function($entry, $defaultCallback) use ($root)
        {
            $defaultCallback($entry);
            P4Cms_FileUtility::deleteRecursive($root);
        };

        // create the client with the values we've setup above, using
        // makeTemp() so that it will be destroyed automatically.
        P4_Client::makeTemp(
            array(
                'Client' => $tempClientId,
                'Stream' => $site->getId(),
                'Root'   => $root
            ),
            $cleanup,
            $connection
        );

        // create personal adapter based on site adapter.
        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($connection)
                ->setBasePath("//" . $connection->getClient())
                ->setProperties($site->getStorageAdapter()->getProperties());

        return $adapter;
    }

    /**
     * Set the corresponding p4 user object instance.
     * Used when fetching users to prime the user object.
     *
     * @param   P4_User     $user       the corresponding P4_User object.
     * @return  P4Cms_User              provides fluent interface.
     * @throws  P4Cms_User_Exception    if the user is anonymous or if the given user is not a
     *                                  valid P4_User object.
     */
    protected function _setP4User($user)
    {
        // anonymous users can't have a corresponding perforce user.
        if ($this->isAnonymous()) {
            throw new P4Cms_User_Exception(
                "Cannot set p4 user for an anonymous user."
            );
        }

        if (!$user instanceof P4_User) {
            throw new P4Cms_User_Exception(
                "Cannot set p4 user. The given user is not a valid P4_User object."
            );
        }

        $this->_p4User = $user;

        return $this;
    }

    /**
     * Get the p4 user object that corresponds to this user.
     *
     * @return  P4_User     corresponding p4 user instance.
     */
    protected function _getP4User()
    {
        // only instantiate user once.
        if (!$this->_p4User instanceof P4_User) {
            $connection = $this->hasAdapter()
                ? $this->getAdapter()->getConnection()
                : null;
            $this->_p4User = new P4_User($connection);
        }

        return $this->_p4User;
    }
}
