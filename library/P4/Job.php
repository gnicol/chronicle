<?php
/**
 * Abstracts operations against Perforce jobs.
 *
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        Add support for the following commands:
 *              fix
 *              fixes
 */
class P4_Job extends P4_Spec_PluralAbstract
{
    const   FETCH_BY_FILTER         = 'filter';
    const   FETCH_DESCRIPTION       = 'descriptions';

    protected static    $_specType  = 'job';
    protected static    $_idField   = 'Job';

    protected static    $_accessors = array(
        102             => 'getStatus',
        103             => 'getUser',
        104             => 'getDate',
        105             => 'getDescription',
    );
    protected static    $_mutators  = array(
        102             => 'setStatus',
        103             => 'setUser',
        105             => 'setDescription',
    );

    /**
     * Get field value. If a custom field accessor exists, it will be used.
     * Extends parent to add support for accessors keyed on field code instead of name.
     *
     * @param   string  $field  the name of the field to get the value of.
     * @return  mixed   the value of the field.
     * @throws  P4_Spec_Exception   if the field does not exist.
     */
    public function getValue($field)
    {
        // if field has custom accessor based on field code, use it.
        $fieldCode = $this->_fieldNameToCode($field);
        if (isset(static::$_accessors[$fieldCode])) {
            return $this->{static::$_accessors[$fieldCode]}();
        }

        return parent::getValue($field);
    }

    /**
     * Set field value. If a custom field mutator exists, it will be used.
     * Extends parent to add support for mutators keyed on field code instead of name.
     *
     * @param   string  $field  the name of the field to set the value of.
     * @param   mixed   $value  the value to set in the field.
     * @return  P4_SpecAbstract     provides a fluent interface
     */
    public function setValue($field, $value)
    {
        // if field has custom mutator based on field code, use it.
        $fieldCode = $this->_fieldNameToCode($field);
        if (isset(static::$_mutators[$fieldCode])) {
            return $this->{static::$_mutators[$fieldCode]}($value);
        }

        return parent::setValue($field, $value);
    }

    /**
     * Get all Jobs from Perforce. Adds filtering options.
     *
     * @param   array   $options    optional - array of options to augment fetch behavior.
     *                              supported options are:
     *
     *                                   FETCH_MAXIMUM - set to integer value to limit to the
     *                                                   first 'max' number of entries.
     *                                 FETCH_BY_FILTER - set to jobview filter
     *                               FETCH_DESCRIPTION - description will be fetched if true,
     *                                                   left for later lazy loading if false.
     *                                                   * defaults to true if not specified
     * @param   P4_Connection_Interface $connection  optional - a specific connection to use.
     * @return  P4_Model_Iterator   all records of this type.
     */
    public static function fetchAll($options = array(), P4_Connection_Interface $connection = null)
    {
        // simply return parent - method exists to document options.
        return parent::fetchAll($options, $connection);
    }

    /**
     * Determine if the given job id exists.
     *
     * @param   string|int                  $id             the id to check for.
     * @param   P4_Connection_Interface     $connection     optional - a specific connection to use.
     * @return  bool    true if the given id matches an existing job.
     */
    public static function exists($id, P4_Connection_Interface $connection = null)
    {
        // check id for valid format
        if (!static::_isValidId($id)) {
            return false;
        }

        $jobs = static::fetchAll(
            array(
                static::FETCH_BY_FILTER => static::_getIdField() .'='. $id,
                static::FETCH_MAXIMUM   => 1
            ),
            $connection
        );

        return (bool) count($jobs);
    }

    /**
     * Override parent to set id to 'new' if unset and capture id returned by save.
     *
     * @return  P4_Job  provides a fluent interface
     */
    public function save()
    {
        $values = $this->_getValues();
        if ($this->getId() === null) {
            $values[static::_getIdField()] = "new";
        }

        // ensure all required fields have values.
        $this->_validateRequiredFields($values);

        $result = $this->getConnection()->run(static::_getSpecType(), "-i", $values);

        // Saved job Id is returned as a string, capture it.
        $data = $result->getData(0);

        if (!preg_match('/^Job ([^ ]+) saved\./', $data, $match)) {
            throw new P4_Spec_Exception('Cannot find ID for saved Job.');
        }

        // Store the retrieved ID
        $this->setId($match[1]);

        // should re-populate (server may change values).
        $this->_deferPopulate(true);

        return $this;
    }

    /**
     * Returns the status of this job. This will return the value of field 102 even if the
     * field name has been changed in the jobspec.
     *
     * Out of the box valid status options are: open/suspended/closed or null. Modifying the
     * jobspec can change the list of valid options.
     *
     * @return  string|null     Status of this job or null if unset.
     */
    public function getStatus()
    {
        return $this->_getValue($this->_fieldCodeToName(102));
    }

    /**
     * Update the status of this job. This will update the value of field 102 even if the
     * field name has been changed in the jobspec.
     *
     * @param   string|null $status Status of this job or null
     * @return  P4_Job  provides a fluent interface.
     * @throws  InvalidArgumentException    For input which isn't a string or null
     */
    public function setStatus($status)
    {
        if (!is_string($status) && !is_null($status)) {
            throw new InvalidArgumentException('Status must be a string or null');
        }

        return $this->_setValue($this->_fieldCodeToName(102), $status);
    }


    /**
     * Returns the user who created this job. This will return the value of field 103
     * even if the field name has been changed in the jobspec.
     *
     * @return  string|null     User who created this job or null if unset.
     */
    public function getUser()
    {
        return $this->_getValue($this->_fieldCodeToName(103));
    }

    /**
     * Update the user who created this job. This will update the value of field 103
     * even if the field name has been changed in the jobspec.
     *
     * @param   string|P4_User|null $user User who created this job, or null
     * @return  P4_Job  provides a fluent interface.
     * @throws  InvalidArgumentException    For input which isn't a string, P4_User or null
     */
    public function setUser($user)
    {
        if ($user instanceof P4_User) {
            $user = $user->getId();
        }

        if (!is_null($user) && !is_string($user)) {
            throw new InvalidArgumentException('User must be a string, P4_User or null');
        }

        return $this->_setValue($this->_fieldCodeToName(103), $user);
    }

    /**
     * Returns the date this job was created. This will return the value of field 104
     * even if the field name has been changed in the jobspec.
     *
     * @return  string|null     Date this job was created or null if unset.
     */
    public function getDate()
    {
        return $this->_getValue($this->_fieldCodeToName(104));
    }

    /**
     * Returns the description for this job. This will return the value of field 105
     * even if the field name has been changed in the jobspec.
     *
     * @return  string|null     Description for this job or null if unset.
     */
    public function getDescription()
    {
        return $this->_getValue($this->_fieldCodeToName(105));
    }

    /**
     * Update the decription for this job. This will update the value of field 105
     * even if the field name has been changed in the jobspec.
     *
     * @param   string|null $description    Description for this job, or null
     * @return  P4_Job  provides a fluent interface.
     * @throws  InvalidArgumentException    For input which isn't a string or null
     */
    public function setDescription($description)
    {
        if (!is_null($description) && !is_string($description)) {
            throw new InvalidArgumentException('Description must be a string or null');
        }

        return $this->_setValue($this->_fieldCodeToName(105), $description);
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

        if (isset($options[static::FETCH_BY_FILTER])) {
            $filter = $options[static::FETCH_BY_FILTER];

            if (!is_string($filter) || trim($filter) === "") {
                throw new InvalidArgumentException(
                    'Fetch by Filter expects a non-empty string as input'
                );
            }

            $flags[] = '-e';
            $flags[] = $filter;
        }

        // if they have not specified FETCH_DESCRIPTION or
        // they have and its true; include full descriptions
        if (!isset($options[static::FETCH_DESCRIPTION]) ||
            $options[static::FETCH_DESCRIPTION]) {
            $flags[] = '-l';
        }

        return $flags;
    }

    /**
     * Check if the given id is in a valid format for this spec type.
     *
     * @param   string|int  $id     the id to check
     * @return  bool        true if id is valid, false otherwise
     */
    protected static function _isValidId($id)
    {
        $validator = new P4_Validate_SpecName;
        $validator->allowPurelyNumeric(true);
        return $validator->isValid($id);
    }

    /**
     * Extends parent to control description inclusion based on FETCH options.
     *
     * @param   array                       $listEntry      a single spec entry from spec list output.
     * @param   array                       $flags          the flags that were used for this 'fetchAll' run.
     * @param   P4_Connection_Interface     $connection     a specific connection to use.
     * @return  P4_Job                      a (partially) populated instance of this spec class.
     */
    protected static function _fromSpecListEntry($listEntry, $flags, P4_Connection_Interface $connection)
    {
        // discard the description if it isn't the 'long' version
        if (!in_array('-l', $flags)) {
            unset($listEntry['Description']);
        }

        return parent::_fromSpecListEntry($listEntry, $flags, $connection);
    }

    /**
     * Given a field name this function will return the associated field code.
     *
     * @param   string  $name   String representing the field's name.
     * @return  int     The field code associated with the passed name.
     */
    protected function _fieldNameToCode($name)
    {
        $field = $this->getSpecDefinition()->getField($name);

        return (int) $field['code'];
    }

    /**
     * Given a field code this function will return the associated field name.
     *
     * @param   int|string  $code   Int or string representing value between 101-199 inclusive.
     * @return  string  The field name associated with the passed code
     * @throws  InvalidArgumentException    If passed an invalid or non-existent field code
     */
    protected function _fieldCodeToName($code)
    {
        // if we are passed a string, and casting through int doesn't change it,
        // it is purely numeric, cast to an int.
        if (is_string($code) &&
            $code === (string)(int)$code) {
            $code = (int)$code;
        }

        // if we made it this far, fail unless we have an int
        if (!is_int($code)) {
            throw new InvalidArgumentException('Field must be a purely numeric string or int.');
        }

        // job spec defines this is the valid range for field id's
        if ($code < 101 || $code > 199) {
            throw new InvalidArgumentException('Field code must be between 101 and 199 inclusive.');
        }

        $fields = $this->getSpecDefinition()->getFields();

        foreach ($fields as $name => $field) {
            if ($field['code'] == $code) {
                return $name;
            }
        }

        throw new InvalidArgumentException('Specified field code does not exist.');
    }
}
