<?php
/**
 * Abstracts operations against Perforce users.
 *
 * The P4 User class differs from the 'user' spec definition in that it
 * does not have a password field. This is because the password does
 * not behave like other fields. To change a user's password, use the
 * setPassword() function. To test if a given string matches a user's
 * password, use the isPassword() method. It is not possible to get a
 * user's password.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_User extends P4_Spec_PluralAbstract
{
    const   FETCH_BY_NAME           = 'name';

    protected static    $_specType  = 'user';
    protected static    $_idField   = 'User';

    protected static    $_accessors = array(
        'Email'     => 'getEmail',
        'Update'    => 'getUpdateDateTime',
        'Access'    => 'getAccessDateTime',
        'FullName'  => 'getFullName',
        'JobView'   => 'getJobView',
        'Reviews'   => 'getReviews',
        'Password'  => 'getPassword',
    );
    protected static    $_mutators  = array(
        'Email'     => 'setEmail',
        'FullName'  => 'setFullName',
        'JobView'   => 'setJobView',
        'Reviews'   => 'setReviews',
        'Password'  => 'setPassword',
    );

    /**
     * Determine if the given user id exists.
     *
     * @param   string                      $id             the id to check for.
     * @param   P4_Connection_Interface     $connection     optional - a specific connection to use.
     * @return  bool    true if the given id matches an existing user.
     */
    public static function exists($id, P4_Connection_Interface $connection = null)
    {
        // check id for valid format
        if (!static::_isValidId($id)) {
            return false;
        }

        // if no connection given, use default.
        $connection = $connection ?: static::getDefaultConnection();

        // fetch all users that matches the id.
        $result = $connection->run(static::_getFetchAllCommand(), array($id));

        return (bool) count($result->getData());
    }

    /**
     * Get all users from Perforce. Adds filtering option.
     *
     * @param   array   $options    optional - array of options to augment fetch behavior.
     *                              supported options are:
     *
     *                                  FETCH_MAXIMUM - set to integer value to limit to the
     *                                                  first 'max' number of entries.
     *                                  FETCH_BY_NAME - set to user name pattern (e.g. 'jdo*'),
     *                                                  can be a single string or array of strings.
     *
     * @param   P4_Connection_Interface $connection optional - a specific connection to use.
     * @return  P4_Model_Iterator   all records of this type.
     */
    public static function fetchAll($options = array(), P4_Connection_Interface $connection = null)
    {
        $connection = $connection ?: static::getDefaultConnection();

        // if not fetching by name, defer to parent
        if (!isset($options[static::FETCH_BY_NAME])) {
            return parent::fetchAll($options, $connection);
        }

        // get fetch max option and uset it from the options as we handle it manually
        $max = isset($options[static::FETCH_MAXIMUM]) ? $options[static::FETCH_MAXIMUM] : 0;
        unset($options[static::FETCH_MAXIMUM]);

        // sort names before fetching users from the server, so if max is set
        // we get the first max users (according to case-sensitivity of server)
        $names = (array) $options[static::FETCH_BY_NAME];
        if ($connection->isCaseSensitive()) {
            sort($names);
        } else {
            usort($names, 'strcasecmp');
        }

        // fetch users in several (as few as possible) runs as
        // there is a potential to exceed the arg-max on this command
        $users = new P4_Model_Iterator;
        foreach ($connection->batchArgs($names) as $batch) {
            $options[static::FETCH_BY_NAME] = $batch;
            foreach (parent::fetchAll($options, $connection) as $user) {
                $users[] = $user;

                // exit loop if we've reached the max limit
                if ($max && $users->count() == $max) {
                    break(2);
                }
            }
        }

        return $users;
    }

    /**
     * Save this user to Perforce. This will not save changes to the
     * password field. Passwords must be set via setPassword().
     *
     * @return  P4_SpecAbstract     provides a fluent interface
     * @throws  P4_Spec_Exception   if no id has been set.
     */
    public function save()
    {
        // ensure all required fields have values.
        $this->_validateRequiredFields();

        // set 'password' field to '******' otherwise password
        // will be deleted under certain security levels.
        $values = $this->_getValues();
        $values['Password'] = '******';

        // initialize command flags with first arg.
        $flags = array("-i");

        // if we are connected with super user privileges, add in the -f flag.
        // otherwise, if not connected as this user, connect as this user.
        $connection = $this->getConnection();
        if ($connection->isSuperUser()) {
            $flags[] = "-f";
        } else if ($connection->getUser() != $this->getId()) {
            $connection = P4_Connection::factory(
                $connection->getPort(),
                $this->getId()
            );
        }

        // send user spec to server.
        $password = $this->_getValue('Password');
        try {
            $connection->run(static::_getSpecType(), $flags, $values);
        } catch (P4_Connection_CommandException $e) {

            // if saving user failed because password has not been
            // set, and the caller supplied a password, try setting
            // the password first, then saving user again.
            //
            // @todo This workaround relies on the fact, that user has been created although previous
            // command had failed. At the moment it seems, that when adding a new user (with non-superuser
            // connection) this error cannot be avoided.
            $errors = $e->getResult()->getErrors();
            if (stristr($errors[0], "password must be set") && is_array($password) && isset($password[0])) {
                $this->_setPassword($password[0], null, $connection);
                $connection->run(static::_getSpecType(), $flags, $values);
                // avoid redundant password change
                $password[0] = null;
            } else {
                throw $e;
            }
        }

        // change the users password if they have set a new one.
        if (is_array($password) && $password[0] !== null) {
            $this->_setPassword($password[0], $password[1], $connection);
        }

        // should re-populate (server may change values).
        $this->_deferPopulate(true);

        return $this;
    }

    /**
     * Remove this user from Perforce.
     *
     * @return  P4_SpecAbstract     provides a fluent interface
     * @throws  P4_Spec_Exception   if no id has been set.
     */
    public function delete()
    {
        if ($this->getId() === null) {
            throw new P4_Spec_Exception("Cannot delete. No id has been set.");
        }

        // initialize command flags with first arg.
        $flags = array("-d");

        // if we are connected with super user privileges, add in the -f flag.
        // otherwise, if not connected as this user, connect as this user.
        $connection = $this->getConnection();
        if ($connection->isSuperUser()) {
            $flags[] = "-f";
        } else if ($connection->getUser() != $this->getId()) {
            $connection = P4_Connection::factory(
                $connection->getPort(),
                $this->getId()
            );
        }

        // issue delete user command.
        $flags[] = $this->getId();
        $result = $connection->run(static::_getSpecType(), $flags);

        // should re-populate.
        $this->_deferPopulate(true);

        return $this;
    }

    /**
     * Get the in-memory password (if one is set).
     *
     * @return  string|null the in-memory password.
     */
    public function getPassword()
    {
        $password = $this->_getValue('Password');
        return is_array($password) ? $password[0] : null;
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
        $this->_setValue('Password', array($newPassword, $oldPassword));

        return $this;
    }

    /**
     * Test if the given password is correct for this user.
     *
     * @param   string  $password   the password to test.
     * @return  bool    true if the password is correct, false otherwise.
     */
    public function isPassword($password)
    {
        $p4 = P4_Connection::factory(
            $this->getConnection()->getPort(),
            $this->getId(),
            null,
            $password
        );

        try {
            $p4->login();
            return true;
        } catch (P4_Connection_LoginException $e) {
            return false;
        }
    }

    /**
     * Get an Iterator of all the Clients this user owns.
     *
     * @return  P4_Model_Iterator   Iterator of P4_Clients owned by current user
     * @throws  P4_Spec_Exception   If no ID is set for this user
     */
    public function getClients()
    {
        if (!static::_isValidId($this->getId())) {
            throw new P4_Spec_Exception("Cannot get clients. No user id has been set.");
        }

        return P4_Client::fetchAll(
            array(P4_Client::FETCH_BY_OWNER => $this->getId()),
            $this->getConnection()
        );
    }

    /**
     * Get the names of groups that this user belongs to.
     *
     * @return  P4_Model_Iterator   Iterator of P4_Groups this user belongs to.
     */
    public function getGroups()
    {
        if (!static::_isValidId($this->getId())) {
            throw new P4_Spec_Exception("Cannot get groups. No user id has been set.");
        }

        return P4_Group::fetchAll(
            array(P4_Group::FETCH_BY_MEMBER => $this->getId(), P4_Group::FETCH_INDIRECT),
            $this->getConnection()
        );
    }

    /**
     * Add this user to the named group.
     *
     * @param   string  $group  the name of the group to add the user to.
     * @return  P4_User provides fluent interface.
     */
    public function addToGroup($group)
    {
        $group = P4_Group::fetch($group, $this->getConnection())
            ->addUser($this->getId())
            ->save();

        return $this;
    }

    /**
     * Get the user's full name.
     *
     * @return  string|null the user's full name.
     */
    public function getFullName()
    {
        return $this->_getValue('FullName');
    }

    /**
     * Set the user's full name.
     *
     * @param   string|null $name   the full name to give the user.
     * @return  P4_User provides fluent interface.
     * @throws  InvalidArgumentException    if given name is not a string.
     */
    public function setFullName($name)
    {
        if ($name !== null && !is_string($name)) {
            throw new InvalidArgumentException("Cannot set full name. Invalid type given.");
        }
        return $this->_setValue('FullName', $name);
    }

    /**
     * Get the user's email address.
     *
     * @return  string|null the user's email address.
     */
    public function getEmail()
    {
        return $this->_getValue('Email');
    }

    /**
     * Set the user's email address. We don't require a valid email
     * address here because Perforce doesn't enforce one. If we did
     * then users with invalid emails would be innaccessible.
     *
     * @param   string|null $email  the email of the user.
     * @return  P4_User provides fluent interface.
     * @throws  InvalidArgumentException    if given email is not a string.
     */
    public function setEmail($email)
    {
        if ($email !== null && !is_string($email)) {
            throw new InvalidArgumentException("Cannot set email. Invalid type given.");
        }
        return $this->_setValue("Email", $email);
    }

    /**
     * Get the user's job view (selects jobs for inclusion during changelist creation).
     *
     * @return  string|null the user's job view.
     */
    public function getJobView()
    {
        return $this->_getValue('JobView');
    }

    /**
     * Set the user's job view (selects jobs for inclusion during changelist creation).
     *
     * @param   string|null $jobView    the user's job view.
     * @return  P4_User provides fluent interface.
     * @throws  InvalidArgumentException    if given job view is not a string.
     */
    public function setJobView($jobView)
    {
        if ($jobView !== null && !is_string($jobView)) {
            throw new InvalidArgumentException("Cannot set job view. Invalid type given.");
        }
        return $this->_setValue("JobView", $jobView);
    }


    /**
     * Get the reviews for this client (depot paths to notify user of changes to).
     *
     * @return  array   list of filespec strings.
     */
    public function getReviews()
    {
        return $this->_getValue('Reviews') ?: array();
    }

    /**
     * Set the reviews for this user (depot paths to notify user of changes to).
     * Reviews is passed as an array of filespec strings.
     *
     * @param   array   $reviews    Review entries - an array of filespec strings.
     * @return  P4_User provides a fluent interface.
     * @throws  InvalidArgumentException    if reviews is not an array.
     */
    public function setReviews($reviews)
    {
        if (!is_array($reviews)) {
            throw new InvalidArgumentException('Reviews must be passed as array.');
        }

        return $this->_setValue('Reviews', $reviews);
    }

    /**
     * Get the last update time for this user spec.
     * This value is read only, no setUpdateTime function is provided.
     *
     * If this is a brand new spec, null will be returned in lieu of a time.
     *
     * @return  string|null  Date/Time of last update, formatted "2009/11/23 12:57:06" or null
     */
    public function getUpdateDateTime()
    {
        return $this->_getValue('Update');
    }

    /**
     * Get the last access time for this user spec.
     * This value is read only, no setAccessTime function is provided.
     *
     * If this is a brand new spec, null will be returned in lieu of a time.
     *
     * @return  string|null  Date/Time of last access, formatted "2009/11/23 12:57:06" or null
     */
    public function getAccessDateTime()
    {
        return $this->_getValue('Access');
    }

    /**
     * Check if automatic user creation is enabled.
     *
     * @param   P4_Connection_Interface     $connection     optional - a specific connection to use.
     * @return  bool                        true if auto user creation is enabled, false otherwise.
     * @throws  P4_Exception                if we exceed the maximum number of unlikely usernames
     */
    public static function isAutoUserCreationEnabled(P4_Connection_Interface $connection = null)
    {
        // if no connection given, use default.
        $connection = $connection ?: static::getDefaultConnection();

        $port = $connection->getPort();

        // limit the number of 'unlikely' username lookups to 3.
        $maxLookups = 3;
        for ($i = 0; $i < $maxLookups; $i++) {
            // generate an unlikely user name.
            $username = md5(mt_rand());

            // try to run p4 users as the unlikely user
            // (perforce won't create an account for this lookup).
            try {
                $connection = P4_Connection::factory($port, $username);
                $result = $connection->run('users', $username);
            } catch (P4_Connection_CommandException $e) {
                return false;
            }

            // ensure unlikely user doesn't exist.
            if (!$result->getData()) {
                return true;
            }
        }

        throw new P4_Exception(
            "Failed to determine if auto user creation is enabled."
          . "Exceeded the maximum of $maxLookups 'unlikely' username lookups."
        );
    }

    /**
     * Produce set of flags for the spec list command, given fetch all options array.
     * Extends parent to add support for filter option.
     *
     * @param   array   $options    array of options to augment fetch behavior.
     *                              see fetchAll for documented options.
     * @return  array   set of flags suitable for passing to spec list command.
     */
    protected static function _getFetchAllFlags($options)
    {
        $flags = parent::_getFetchAllFlags($options);

        if (isset($options[static::FETCH_BY_NAME])) {
            $name = $options[static::FETCH_BY_NAME];

            if ((!is_array($name) || !count($name)) && (!is_string($name) || trim($name) === "")) {
                throw new InvalidArgumentException(
                    'Filter by Name expects a non-empty string or an non-empty array as input'
                );
            }

            // if array is given, ensure values are non-empty strings
            if (is_array($name)) {
                $names    = $name;
                $filtered = array_filter($names,    'is_string');
                $filtered = array_filter($filtered, 'trim');

                if (count($names) !== count($filtered)) {
                    throw new InvalidArgumentException(
                        'Filter by Name expects all names in the input array to be non-empty strings'
                    );
                }
                $flags = array_merge($flags, $names);
            } else {
                $flags[] = $name;
            }
        }

        return $flags;
    }

    /**
     * Check if the given id is in a valid format for user specs.
     *
     * @param   string      $id     the id to check
     * @return  bool        true if id is valid, false otherwise
     */
    protected static function _isValidId($id)
    {
        $validator = new P4_Validate_UserName;
        return $validator->isValid($id);
    }

    /**
     * Given a spec entry from spec list output (p4 users), produce
     * an instance of this spec with field values set where possible.
     *
     * @param   array                       $listEntry      a single spec entry from spec list output.
     * @param   array                       $flags          the flags that were used for this 'fetchAll' run.
     * @param   P4_Connection_Interface     $connection     a specific connection to use.
     * @return  P4_User                     a (partially) populated instance of this spec class.
     */
    protected static function _fromSpecListEntry($listEntry, $flags, P4_Connection_Interface $connection)
    {
        // update/access time are return as longs. Unset to avoid figuring out timezone
        // for a proper conversion.
        unset($listEntry['update']);
        unset($listEntry['access']);

        return parent::_fromSpecListEntry($listEntry, $flags, $connection);
    }

    /**
     * Immediately set the user's password to the given password.
     * If the current password is given, it will be validated.
     *
     * @param   string                      $newPassword    the new password.
     * @param   string                      $oldPassword    optional - existing password.
     * @param   P4_Connection_Interface     $connection     optional - a specific connection to use.
     * @return  P4_User                     provides fluent interface.
     * @throws  P4_Exception                if the password can't be set.
     */
    protected function _setPassword(
        $newPassword,
        $oldPassword = null,
        P4_Connection_Interface $connection = null
    )
    {
        $input = array();

        // if caller supplied an old password, prepend it to input array.
        if ($oldPassword) {
            $input[] = $oldPassword;
        }

        // always confirm old password
        $input[] = $newPassword;
        $input[] = $newPassword;

        // if no connection given, use default.
        $connection = $connection ?: $this->getConnection();

        // if not connected as this user, supply user id.
        $flags = array();
        if ($connection->getUser() !== $this->getId()) {
            $flags[] = $this->getId();
        }

        // attempt to set password.
        $result = $connection->run("password", $flags, $input);

        // change connection credentials if password for connected user has been changed
        // if we don't do this automatically, subsequent commands will fail when using
        // the command-line connection, but would succeed using the P4PHP extension.
        if ($connection->getUser() === $this->getId()) {
            $connection->setPassword($newPassword);
            if ($connection->getTicket()) {
                $connection->login();
            }
        }

        return $this;
    }
}
