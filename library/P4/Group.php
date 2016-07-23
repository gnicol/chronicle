<?php
/**
 * Abstracts operations against Perforce user groups.
 *
 * Abandon all hope ye who go beyond this point.
 *
 * Groups is a bit of an odd duck. Identified un-expected behaviour includes:
 * - "group -i" with no populated users/owners/subgroups will report 'created' but it isn't
 * - "groups" output is unusually formatted; see Pural Abstract for details
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Group extends P4_Spec_PluralAbstract
{
    const FETCH_BY_MEMBER       = 'member';
    const FETCH_INDIRECT        = 'indirect';
    const FETCH_BY_NAME         = 'name';

    protected static $_specType = 'group';
    protected static $_idField  = 'Group';

    protected static $_accessors    = array(
        'MaxResults'    => 'getMaxResults',
        'MaxScanRows'   => 'getMaxScanRows',
        'MaxLockTime'   => 'getMaxLockTime',
        'Timeout'       => 'getTimeout',
        'Subgroups'     => 'getSubgroups',
        'Owners'        => 'getOwners',
        'Users'         => 'getUsers',
    );
    protected static    $_mutators  = array(
        'MaxResults'    => 'setMaxResults',
        'MaxScanRows'   => 'setMaxScanRows',
        'MaxLockTime'   => 'setMaxLockTime',
        'Timeout'       => 'setTimeout',
        'Subgroups'     => 'setSubgroups',
        'Owners'        => 'setOwners',
        'Users'         => 'setUsers',
    );

    /**
     * Get all Groups from Perforce. Adds filtering options.
     *
     * @param   array   $options    optional - array of options to augment fetch behavior.
     *                              supported options are:
     *                                   FETCH_MAXIMUM - set to integer value to limit to the first
     *                                                   'max' number of entries.
     *                                                   *Note: Limits imposed client side.
     *                                 FETCH_BY_MEMBER - get groups containing passed group or
     *                                                   user (no wildcards).
     *                                  FETCH_INDIRECT - used with FETCH_BY_MEMBER to also list
     *                                                   indirect matches.
     *                                   FETCH_BY_NAME - get the named group. esstentially a 'fetch'
     *                                                   but performed differently (no wildcards).
     *                                                   *Note: not compatible with FETCH_BY_MEMBER
     *                                                          or FETCH_INDIRECT
     *
     * @param   P4_Connection_Interface $connection  optional - a specific connection to use.
     * @return  P4_Model_Iterator   all records of this type.
     */
    public static function fetchAll($options = array(), P4_Connection_Interface $connection = null)
    {
        // the 'groups' command produces very unique output; we have taken over the parent
        // function to handle it here.

        // if no connection given, use default.
        $connection = $connection ?: static::getDefaultConnection();

        // get command to use
        $command = static::_getFetchAllCommand();

        // get fstat flags for given fetch options.
        $flags = static::_getFetchAllFlags($options);

        // fetch all specs.
        $result = $connection->run($command, $flags);

        // we manually implement FETCH_MAXIMUM for base class compatibility
        // pull out the passed value if present for later use.
        if (isset($options[self::FETCH_MAXIMUM])) {
            $max = (int) $options[self::FETCH_MAXIMUM];
        }


        // 'groups' produces the below data-block for each owner/user/sub-group
        //   array (
        //      'user'          => 'tester',
        //      'group'         => 'test',
        //      'isSubGroup'    => '0',
        //      'isOwner'       => '0',
        //      'isUser'        => '1',
        //      'maxResults'    => '0',
        //      'maxScanRows'   => '0',
        //      'maxLockTime'   => '0',
        //      'timeout'       => '43200',
        //      'isValidUser'   => '1',
        //   )

        // convert result data to spec objects.
        $specs = new P4_Model_Iterator;
        $data  = $result->getData();

        // keep outer loop running so long as we have data (and haven't hit max results)
        while (current($data) !== false) {
            $element    = current($data);
            $id         = $element['group'];
            $values     = array(
                'Group'         => $id,
                'MaxResults'    => $element['maxResults'],
                'MaxScanRows'   => $element['maxScanRows'],
                'MaxLockTime'   => $element['maxLockTime'],
                'Timeout'       => $element['timeout'],
                'Subgroups'     => array(),
                'Owners'        => array(),
                'Users'         => array()
            );

            // loop-de-loop
            // collect all data blocks for a given group
            while ($element !== false && $element['group'] == $id) {

                // defer to lazy load if FETCH_BY_MEMBER option was used
                // as result data doesn't contain all the values
                if (isset($options[self::FETCH_BY_MEMBER])) {
                    $values = array('Group' => $id);
                } else {
                    if ($element['isSubGroup'] == 1) {
                        $values['Subgroups'][]  = $element['user'];
                    }

                    if ($element['isOwner'] == 1) {
                        $values['Owners'][]     = $element['user'];
                    }

                    if ($element['isUser'] == 1) {
                        $values['Users'][]      = $element['user'];
                    }
                }

                $element = next($data);
            }

            // at this point we have all of the groups details
            // populate a spec and add it to the iterator
            $spec = new static($connection);
            $spec->_setValues($values)
                 ->_deferPopulate();

            $specs[] = $spec;

            // stop looping if we reach 'FETCH_MAXIMUM'
            if (isset($max) && count($specs) == $max) {
                break;
            }
        }

        return $specs;
    }

    /**
     * Save this spec to Perforce. Extend parent to throw if group is 'empty'
     *
     * @param   bool    $owner      save the group as a group owner
     * @return  P4_SpecAbstract     provides a fluent interface
     * @throws  P4_Spec_Exception   if group is empty
     */
    public function save($owner = false)
    {
        if ($this->isEmpty()) {
            throw new P4_Spec_Exception("Cannot save. Group is empty.");
        }

        // ensure all required fields have values.
        $this->_validateRequiredFields();

        $flags = array('-i');
        if ($owner) {
            $flags[] = '-a';
        }

        $this->getConnection()->run(
            static::_getSpecType(),
            $flags,
            $this->_getValues()
        );

        // should re-populate (server may change values).
        $this->_deferPopulate(true);

        return $this;
    }

    /**
     * Determine if the given group id exists.
     *
     * @param   string                      $id             the id to check for.
     * @param   P4_Connection_Interface     $connection     optional - a specific connection to use.
     * @return  bool    true if the given id matches an existing group.
     */
    public static function exists($id, P4_Connection_Interface $connection = null)
    {
        // check id for valid format
        if (!static::_isValidId($id)) {
            return false;
        }

        $groups = static::fetchAll(
            array(
                static::FETCH_BY_NAME => $id,
                static::FETCH_MAXIMUM => 1
            ),
            $connection
        );

        return (bool) count($groups);
    }

    /**
     * Determines if this group is 'empty'.
     *
     * A group is considered empty if no entries are present in:
     * -SubGroups
     * -Owners
     * -Users
     *
     * Values in Group (id), MaxResults, MaxScanRows, MaxLockTime do not
     * count towards 'emptiness'.
     *
     * @return  bool    True if group is empty, False otherwise
     */
    public function isEmpty()
    {
        $entries =  count($this->getValue('Subgroups')) +
                    count($this->getValue('Owners')) +
                    count($this->getValue('Users'));

        return !(bool) $entries;
    }

    /**
     * The maximum number of results that members of this group can access
     * from the server from a single command. The default value is null.
     *
     * Will be an integer >0, null (if 'unset') or the string 'unlimited'
     *
     * @return  null|int|string     Null if unset, integer >0 or 'unlimited'
     */
    public function getMaxResults()
    {
        return $this->_getMaxValue('MaxResults');
    }

    /**
     * Set the MaxResults for this group. See getMaxResults for more info.
     *
     * The string 'unset' may be passed in place of null for convienence.
     *
     * @param   null|int|string     $max    null (or 'unset'), integer >0 or 'unlimited'
     * @return  P4_Group    provides fluent interface.
     */
    public function setMaxResults($max)
    {
        return $this->_setMaxValue('MaxResults', $max);
    }

    /**
     * The maximum number of rows that members of this group can scan from
     * the server from a single command. The default value is null.
     *
     * Will be an integer >0, null (if 'unset') or the string 'unlimited'
     *
     * @return  null|int|string     Null if unset, integer >0 or 'unlimited'
     */
    public function getMaxScanRows()
    {
        return $this->_getMaxValue('MaxScanRows');
    }

    /**
     * Set the MaxScanRows for this group. See getMaxScanRows for more info.
     *
     * The string 'unset' may be passed in place of null for convienence.
     *
     * @param   null|int|string     $max    null (or 'unset'), integer >0 or 'unlimited'
     * @return  P4_Group    provides fluent interface.
     */
    public function setMaxScanRows($max)
    {
        return $this->_setMaxValue('MaxScanRows', $max);
    }

    /**
     * The maximum length of time (in milliseconds) that any one operation can
     * lock any database table when scanning data. The default value is null.
     *
     * Will be an integer >0, null (if 'unset') or the string 'unlimited'
     *
     * @return  null|int|string     Null if unset, integer >0 or 'unlimited'
     */
    public function getMaxLockTime()
    {
        return $this->_getMaxValue('MaxLockTime');
    }

    /**
     * Set the MaxLockTime for this group. See getMaxLockTime for more info.
     *
     * The string 'unset' may be passed in place of null for convienence.
     *
     * @param   null|int|string     $max    null (or 'unset'), integer >0 or 'unlimited'
     * @return  P4_Group    provides fluent interface.
     */
    public function setMaxLockTime($max)
    {
        return $this->_setMaxValue('MaxLockTime', $max);
    }

    /**
     * The duration (in seconds) of the validity of a session ticket created
     * by p4 login. The default value is 43200 seconds (12 hours).
     * For tickets that do not expire, will return 'unlimited'.
     *
     * Will be an integer >0, null (if 'unset') or the string 'unlimited'
     *
     * @return  null|int|string     Null if unset, integer >0 or 'unlimited'
     */
    public function getTimeout()
    {
        return $this->_getMaxValue('Timeout');
    }

    /**
     * Set the Timeout for this group. See getTimeout for more info.
     *
     * The string 'unset' may be passed in place of null for convienence.
     *
     * @param   null|int|string     $timeout    null (or 'unset'), integer >0 or 'unlimited'
     * @return  P4_Group    provides fluent interface.
     */
    public function setTimeout($timeout)
    {
        return $this->_setMaxValue('Timeout', $timeout);
    }

    /**
     * Returns the sub-groups for this group.
     *
     * @return  array   subgroups belonging to this group
     */
    public function getSubgroups()
    {
        return $this->_getValue('Subgroups') ?: array();
    }

    /**
     * Set the sub-groups for this group.
     * Expects an array containing group names or P4_Group objects.
     *
     * @param   array   $subgroups  array of group names or P4_Group objects
     * @return  P4_Group    provides fluent interface.
     */
    public function setSubgroups($subgroups)
    {
        if (!is_array($subgroups)) {
            throw new InvalidArgumentException(
                'Subgroups must be specified as an array.'
            );
        }

        foreach ($subgroups as &$group) {
            // normalize to strings
            if ($group instanceof P4_Group) {
                $group = $group->getId();
            }

            if (!static::_isValidId($group)) {
                throw new InvalidArgumentException(
                    'Individual sub-groups must be a valid ID in either string or P4_Group format.'
                );
            }
        }

        return $this->_setValue('Subgroups', $subgroups);
    }

    /**
     * Adds the passed group to the end of the current sub-groups.
     *
     * @param   string|P4_Group $group  new group to add
     * @return  P4_Group    provides fluent interface.
     */
    public function addSubgroup($group)
    {
        $subgroups = $this->getSubgroups();
        $subgroups[] = $group;

        return $this->setSubgroups($subgroups);
    }

    /**
     * Returns the owners for this group.
     *
     * @return  array   owners belonging to this group
     */
    public function getOwners()
    {
        return $this->_getValue('Owners') ?: array();
    }

    /**
     * Set the owners for this group.
     * Expects an array containing user names or P4_User objects.
     *
     * @param   array   $owners array of user names or P4_User objects
     * @return  P4_Group    provides fluent interface.
     */
    public function setOwners($owners)
    {
        if (!is_array($owners)) {
            throw new InvalidArgumentException(
                'Owners must be specified as an array.'
            );
        }

        foreach ($owners as &$owner) {
            // normalize to strings
            if ($owner instanceof P4_User) {
                $owner = $owner->getId();
            }

            if (!static::_isValidUserId($owner)) {
                throw new InvalidArgumentException(
                    'Individual owners must be a valid ID in either string or P4_User format.'
                );
            }
        }

        return $this->_setValue('Owners', $owners);
    }

    /**
     * Adds the passed owner to the end of the current owners.
     *
     * @param   string|P4_User  $owner  new owner to add
     * @return  P4_Group    provides fluent interface.
     */
    public function addOwner($owner)
    {
        $owners   = $this->getOwners();
        $owners[] = $owner;

        return $this->setOwners($owners);
    }

    /**
     * Returns the users for this group.
     *
     * @return  array   users belonging to this group
     */
    public function getUsers()
    {
        return $this->_getValue('Users') ?: array();
    }

    /**
     * Set the users for this group.
     * Expects an array containing user names or P4_User objects.
     *
     * @param   array   $users  array of user names or P4_User objects
     * @return  P4_Group    provides fluent interface.
     */
    public function setUsers($users)
    {
        if (!is_array($users)) {
            throw new InvalidArgumentException(
                'Users must be specified as an array.'
            );
        }

        foreach ($users as &$user) {
            // normalize to strings
            if ($user instanceof P4_User) {
                $user = $user->getId();
            }

            if (!static::_isValidUserId($user)) {
                throw new InvalidArgumentException(
                    'Individual users must be a valid ID in either string or P4_User format.'
                );
            }
        }

        return $this->_setValue('Users', $users);
    }

    /**
     * Adds the passed user to the end of the current users.
     *
     * @param   string|P4_User  $user   new user to add
     * @return  P4_Group    provides fluent interface.
     */
    public function addUser($user)
    {
        $users   = $this->getUsers();
        $users[] = $user;

        return $this->setUsers($users);
    }

    /**
     * Get the value for a 'max' style field
     * (one of MaxResults, MaxScanRows, MaxLockTime and Timeout).
     *
     * @param   string          $field  Name of the field to get the value from
     * @return  null|int|string null (if 'unset'), integer >0 or 'unlimited'
     */
    protected function _getMaxValue($field)
    {
        $max = $this->_getValue($field);

        // translate the string 'unset' to null
        if ($max === 'unset') {
            return null;
        }

        // integers come back from perforce as strings
        // casting to an int, then back to a string screens out non-digit
        // characters and allows for a 'pure digit' check.
        if ($max == (string)(int)$max) {
            return (int)$max;
        }

        return $max;
    }

    /**
     * Check if the given id is in a valid format for group specs.
     *
     * @param   string      $id     the id to check
     * @return  bool        true if id is valid, false otherwise
     */
    protected static function _isValidId($id)
    {
        $validator = new P4_Validate_GroupName;
        return $validator->isValid($id);
    }

    /**
     * Check if the given id is in a valid format for user specs.
     *
     * @param   string      $id     the id to check
     * @return  bool        true if id is valid, false otherwise
     */
    protected static function _isValidUserId($id)
    {
        $validator = new P4_Validate_UserName;
        return $validator->isValid($id);
    }

    /**
     * Set the value for a 'max' style field
     * (one of MaxResults, MaxScanRows, MaxLockTime and Timeout).
     *
     * Valid 'max' inputs are:
     * -null, gets converted to 'unset'
     * -the string 'unset'
     * -an integer greater than 0
     * -the string 'unlimited'
     *
     * @param   string          $field  Name of the field to set value on
     * @param   null|int|string $max    null (or 'unset'), integer >0 or 'unlimited'
     * @return  P4_Group    provides a fluent interface
     * @throws  InvalidArgumentException    If input is of incorrect type of format
     */
    protected function _setMaxValue($field, $max)
    {
        // ensure input is in the ballpark
        if (!is_null($max) && !is_int($max) && !is_string($max)) {
            throw new InvalidArgumentException(
                "Type of input must be one of: null, int, string"
            );
        }

        // convert null to 'unset'
        if ($max === null) {
            $max = 'unset';
        }

        // verify string format input matches expected value
        if (is_string($max) && $max !== 'unlimited' && $max !== 'unset') {
            throw new InvalidArgumentException(
                "For string input, only the values 'unlimited' and 'unset' are valid."
            );
        }

        // ensure integer input is greater than zero
        if (is_int($max) && $max <= 0) {
            throw new InvalidArgumentException(
                "For integer input, only values greater than zero are valid."
            );
        }

        return $this->_setValue($field, $max);
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
        // clear FETCH_MAXIMUM if present as we handle it seperately
        unset($options[self::FETCH_MAXIMUM]);

        $flags = parent::_getFetchAllFlags($options);

        if (isset($options[static::FETCH_BY_NAME])) {
            $name = $options[static::FETCH_BY_NAME];

            if (!static::_isValidId($name) && !static::_isValidUserId($name)) {
                throw new InvalidArgumentException(
                    'Filter by Name expects a valid group id.'
                );
            }

            if (isset($options[static::FETCH_INDIRECT]) ||
                isset($options[static::FETCH_BY_MEMBER])
            ) {
                throw new InvalidArgumentException(
                    'Filter by Name is not compatible with Fetch by Member or Fetch Indirect.'
                );
            }

            $flags[] = '-v';
            $flags[] = $name;
        }

        if (isset($options[static::FETCH_INDIRECT], $options[static::FETCH_BY_MEMBER])) {
            $flags[] = '-i';
        }

        if (isset($options[static::FETCH_BY_MEMBER])) {
            $member = $options[static::FETCH_BY_MEMBER];

            if (!static::_isValidId($member) && !static::_isValidUserId($member)) {
                throw new InvalidArgumentException(
                    'Filter by Member expects a valid group or username.'
                );
            }

            $flags[] = $member;
        }


        return $flags;
    }

    /**
     * This function is not utilized by P4_Group as our result format is incompatible.
     * Any attempt to call this function results in an exception.
     *
     * @param   array                       $listEntry      a single spec entry from spec list output.
     * @param   array                       $flags          the flags that were used for this 'fetchAll' run.
     * @param   P4_Connection_Interface     $connection     a specific connection to use.
     * @return  P4_Spec_PluralAbstract      a (partially) populated instance of this spec class.
     * @throws  BadFunctionCallException    On any use of this function in this class.
     */
    protected static function _fromSpecListEntry($listEntry, $flags, P4_Connection_Interface $connection)
    {
        throw new BadFunctionCallException(
            'From Spec List Entry is not implemented in the P4_Group class.'
        );
    }
}
