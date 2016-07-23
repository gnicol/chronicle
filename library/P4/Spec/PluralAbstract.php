<?php
/**
 * This class layers support for plural specs such as changes, jobs,
 * users, etc. on top of the singular spec support already present
 * in P4_SpecAbstract.
 *
 * ------------------------------------------------------------------------------------------------
 * Note: there are great inconsistencies between the data produced by
 * the commands that return a single spec entry (e.g. 'p4 group', 'p4 change')
 * and the commands that return a list of spec entries (e.g. 'p4 groups',
 * 'p4 changes'). These inconsistencies are (roughly):
 *
 * MOST SPECS:
 *  = Case-inconsistency in field names.
 *  = Fewer fields.
 *
 * SOME SPECS:
 *  = More fields
 *
 * CHANGE SPEC:
 *  = desc -> Description (truncated w.out -l)
 *  = time -> Date (format change)
 *
 * CLIENT + LABEL + USER SPECS:
 *  = update/access format
 *
 * DEPOT SPECS:
 *  = name -> Depot
 *  = time -> Date (format)
 *  = extra -> Address
 *
 * GROUP SPECS:
 *  = max (-m) broken
 *  = one entry per user per group.
 *  = user field kindof maps to Users field
 *  = might be best to use untagged output.
 *
 * JOB SPECS:
 *  = Description truncated without -l.
 *
 * ------------------------------------------------------------------------------------------------
 * Note: most commands will provide results when fetching by an invalid ID. Details are provided
 * below on how to determine if you have an existing entry or a blank template:
 *
 * CHANGE
 * Exception thrown on invalid ID, no action required.
 *
 * CLIENT / LABEL / USER
 * 'always' fields access/updated are not present if new entry. For a more reliable check run:
 *
 *  'clients -e ID'
 *  'labels -e ID'
 *  'users ID'
 *
 * And check for an empty result.
 *
 * DEPOT
 * No way to tell from single output and no way to filter. Run depots and see if its listed.
 *
 * GROUP
 * No way to tell from single output. Run 'groups -v ID' item isn't present if result is empty.
 * It is notable 'groups -v' without ID produces extensive output, not the expected usage error.
 *
 * JOB
 * ReportedDate not present on new entries but the jobspec is rather malleable.
 * Safest check is running:
 *
 *  'jobs -e job=ID'
 *
 * And check for an empty result.
 *
 * ------------------------------------------------------------------------------------------------
 * Note: It was originally assumed Plural Spec IDs could not include revision specifiers or file
 * Wildcards. This would limit:
 * '*', '...', '%%1'-'%%9'
 * The following additional, common restrictions were found:
 * all digits, starts with '-'
 *
 * On a per class basis, the forbidden items are:
 *
 * CHANGE
 *  Pure digits are allowed, all else forbidden
 *
 * CLIENT
 *  '*', '...', '%%', pure digits, leading '-'
 *
 * LABEL
 *  '*', '...', '%%', pure digits, leading '-'
 *
 * USER
 *  '*', '...', pure digits, leading '-'
 *
 * DEPOT
 *  '*', '...', '%%', pure digits, leading '-'
 *
 * GROUP
 *  '*', '...', pure digits, leading '-'
 *
 * JOB
 *  '*', '...'
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
abstract class P4_Spec_PluralAbstract extends P4_SpecAbstract
{
    const               FETCH_MAXIMUM       = 'maximum';
    const               TEMP_ID_PREFIX      = '~tmp';
    const               TEMP_ID_DELIMITER   = ".";

    protected static    $_idField           = null;

    /**
     * Get the id of this spec entry.
     *
     * @return  null|string     the id of this entry.
     */
    public function getId()
    {
        if (array_key_exists(static::_getIdField(), $this->_values)) {
            return $this->_values[static::_getIdField()];
        } else {
            return null;
        }
    }

    /**
     * Set the id of this spec entry. Id must be in a valid format or null.
     *
     * @param   null|string     $id     the id of this entry - pass null to clear.
     * @return  P4_Spec_PluralAbstract  provides a fluent interface
     * @throws  InvalidArgumentException    if id does not pass validation.
     */
    public function setId($id)
    {
        if ($id !== null && !static::_isValidId($id)) {
            throw new InvalidArgumentException("Cannot set id. Id is invalid.");
        }

        // if populate was deferred, caller expects it
        // to have been populated already.
        $this->_populate();

        $this->_values[static::_getIdField()] = $id;

        return $this;
    }

    /**
     * Determine if a spec record with the given id exists.
     * Must be implemented by sub-classes because this test
     * is impractical to generalize.
     *
     * @param   string                      $id             the id to check for.
     * @param   P4_Connection_Interface     $connection     optional - a specific connection to use.
     * @return  bool    true if the given id matches an existing record.
     */
    abstract public static function exists($id, P4_Connection_Interface $connection = null);

    /**
     * Get the requested spec entry from Perforce.
     *
     * @param   string                  $id         the id of the entry to fetch.
     * @param   P4_Connection_Interface $connection optional - a specific connection to use.
     * @return  P4_Spec_PluralAbstract  instace of the requested entry.
     * @throws  InvalidArgumentException    if no id is given.
     */
    public static function fetch($id, P4_Connection_Interface $connection = null)
    {
        // ensure a valid id is provided.
        if (!static::_isValidId($id)) {
            throw new InvalidArgumentException("Must supply a valid id to fetch.");
        }

        // if no connection given, use default.
        $connection = $connection ?: static::getDefaultConnection();

        // ensure id exists.
        if (!static::exists($id, $connection)) {
            throw new P4_Spec_NotFoundException(
                "Cannot fetch " . static::_getSpecType() . " $id. Record does not exist."
            );
        }

        // construct spec instance.
        $spec = new static($connection);
        $spec->setId($id)
             ->_deferPopulate();

        return $spec;
    }

    /**
     * Get all entries of this type from Perforce.
     *
     * @param   array   $options    optional - array of options to augment fetch behavior.
     *                              supported options are:
     *
     *                                  FETCH_MAXIMUM - set to integer value to limit to the
     *                                                  first 'max' number of entries.
     *
     * @param   P4_Connection_Interface $connection optional - a specific connection to use.
     * @return  P4_Model_Iterator   all records of this type.
     * @todo    make limit work for depot (in a P4_Depot sub-class)
     */
    public static function fetchAll($options = array(), P4_Connection_Interface $connection = null)
    {
        // if no connection given, use default.
        $connection = $connection ?: static::getDefaultConnection();

        // get command to use
        $command = static::_getFetchAllCommand();

        // get command flags for given fetch options.
        $flags = static::_getFetchAllFlags($options);

        // fetch all specs.
        $result = $connection->run($command, $flags);

        // expand any sequences present
        $result->expandSequences();

        // convert result data to spec objects.
        $specs = new P4_Model_Iterator;
        foreach ($result->getData() as $data) {
            $spec = static::_fromSpecListEntry($data, $flags, $connection);
            $spec->_deferPopulate();
            $specs[] = $spec;
        }

        return $specs;
    }

    /**
     * Create a temporary entry.
     *
     * The passed values can, optionally, specify the id of the temp entry.
     * If no id is passed in values, one will be generated following the
     * conventions described in makeTempId().
     *
     * Temp entries are deleted when the connection is closed.
     *
     * @param   array|null              $values             optional - values to set on temp entry,
     *                                                      can include ID
     * @param   function|null           $cleanupCallback    optional - callback to use for cleanup.
     *                                                      signature is:
     *                                                      function($entry, $defaultCallback)
     * @param   P4_Connection_Interface $connection optional - a specific connection to use.
     * @return  P4_Spec_PluralAbstract  instace of the temp entry.
     */
    public static function makeTemp(
        array $values = null,
        $cleanupCallback = null,
        P4_Connection_Interface $connection = null)
    {
        // normalize to array
        $values = $values ?: array();

        // generate an id if no value for our id field is present
        $idField = static::_getIdField();
        if (!isset($values[$idField])) {
            $values[$idField] = static::makeTempId();
        }

        // create the temporary instance.
        $temp = new static($connection);
        $temp->setValues($values)->save();

        // remove the temp entry when the connection terminates.
        $defaultCallback = static::_getTempCleanupCallback();
        $temp->getConnection()->addDisconnectCallback(
            function($connection) use ($temp, $cleanupCallback, $defaultCallback)
            {
                try {
                    // use the passed callback if valid, fallback to the default callback
                    if (is_callable($cleanupCallback)) {
                        $cleanupCallback($temp, $defaultCallback);
                    } else {
                        $defaultCallback($temp);
                    }
                } catch (Exception $e) {
                    P4_Log::logException("Failed to delete temporary entry.", $e);
                }
            }
        );

        return $temp;
    }

    /**
     * Generate a temporary id by combining the id prefix
     * with the current time, pid and a random uniqid():
     *
     *  ~tmp.<unixtime>.<pid>.<uniqid>
     *
     * The leading tilde ('~') places the temporary id at the end of
     * the list.  The unixtime ensures that the oldest ids will
     * appear first (among temp ids), while the pid and uniqid provide
     * reasonable assurance that no two ids will collide.
     *
     * @return  string  an id suitable for use with temporary specs.
     */
    public static function makeTempId()
    {
        return implode(
            static::TEMP_ID_DELIMITER, 
            array(
                static::TEMP_ID_PREFIX,
                time(),
                getmypid(),
                uniqid("", true)
            )
        );
    }

    /**
     * Delete this spec entry.
     *
     * @param   array   $params         optional - additional flags to pass to delete
     *                                  (e.g. some specs support -f to force delete).
     * @return  P4_Spec_PluralAbstract  provides a fluent interface
     * @throws  P4_Spec_Exception       if no id has been set.
     */
    public function delete(array $params = null)
    {
        $id = $this->getId();
        if ($id === null) {
            throw new P4_Spec_Exception("Cannot delete. No id has been set.");
        }

        // ensure id exists.
        $connection = $this->getConnection();
        if (!static::exists($id, $connection)) {
            throw new P4_Spec_NotFoundException(
                "Cannot delete " . static::_getSpecType() . " $id. Record does not exist."
            );
        }

        $params = array_merge((array) $params, array("-d", $id));
        $result = $connection->run(static::_getSpecType(), $params);

        // should re-populate.
        $this->_deferPopulate(true);

        return $this;
    }

    /**
     * Provide a callback function to be used during cleanup of
     * temp entries. The callback should expect a single parameter,
     * the entry being removed.
     *
     * @return function     A callback function with the signature function($entry)
     */
    protected static function _getTempCleanupCallback()
    {
        return function($entry)
        {
            // remove the temp entry we are responsible for
            $entry->delete();
        };
    }

    /**
     * Check if the given id is in a valid format for this spec type.
     *
     * @param   string      $id     the id to check
     * @return  bool        true if id is valid, false otherwise
     */
    protected static function _isValidId($id)
    {
        $validator = new P4_Validate_SpecName;
        return $validator->isValid($id);
    }

    /**
     * Get a field's raw value.
     * Extend parent to use getId() for id field.
     *
     * @param   string  $field  the name of the field to get the value of.
     * @return  mixed   the value of the field.
     * @throws  P4_Spec_Exception   if the field does not exist.
     */
    protected function _getValue($field)
    {
        if ($field === static::_getIdField()) {
            return $this->getId();
        }

        // call-through.
        return parent::_getValue($field);
    }

    /**
     * Set a field's raw value.
     * Extend parent to use setId() for id field.
     *
     * @param   string  $field  the name of the field to set the value of.
     * @param   mixed   $value  the value to set in the field.
     * @return  P4_SpecAbstract     provides a fluent interface
     * @throws  P4_Spec_Exception   if the field does not exist.
     */
    protected function _setValue($field, $value)
    {
        if ($field === static::_getIdField()) {
            return $this->setId($value);
        }

        // call-through.
        return parent::_setValue($field, $value);
    }

    /**
     * Get the name of the id field for this spec.
     *
     * @return  string  the name of this spec's id field.
     * @throws  P4_Spec_Exception  if the spec's id field is unset.
     */
    protected static function _getIdField()
    {
        // if spec id field not defined, throw.
        if (!is_string(static::$_idField) || !trim(static::$_idField)) {
           throw new P4_Spec_Exception('No id field is defined for this specification.');
        }

        return static::$_idField;
    }

    /**
     * Extend parent populate to exit early if id is null.
     */
    protected function _populate()
    {
        // early exit if populate not needed.
        if (!$this->_needsPopulate) {
            return;
        }

        // don't attempt populate if id null.
        if ($this->getId() === null) {
            return;
        }

        parent::_populate();
    }

    /**
     * Extended to preserve id when values are cleared.
     * Schedule populate to run when data is requested (lazy-load).
     *
     * @param   bool    $reset  optionally clear instance values.
     */
    protected function _deferPopulate($reset = false)
    {
        if ($reset) {
            $id = $this->getId();
        }

        parent::_deferPopulate($reset);

        if ($reset) {
            $this->setId($id);
        }
    }

    /**
     * Get raw spec data direct from Perforce. No caching involved.
     * Extends parent to supply an id to the spec -o command.
     *
     * @return  array   $data   the raw spec output from Perforce.
     */
    protected function _getSpecData()
    {
        $result = $this->getConnection()->run(
            static::_getSpecType(),
            array("-o", $this->getId())
        );
        return $result->expandSequences()->getData(0);
    }

    /**
     * Given a spec entry from spec list output (e.g. 'p4 jobs'), produce
     * an instance of this spec with field values set where possible.
     *
     * @param   array                       $listEntry      a single spec entry from spec list output.
     * @param   array                       $flags          the flags that were used for this 'fetchAll' run.
     * @param   P4_Connection_Interface     $connection     a specific connection to use.
     * @return  P4_Spec_PluralAbstract      a (partially) populated instance of this spec class.
     */
    protected static function _fromSpecListEntry($listEntry, $flags, P4_Connection_Interface $connection)
    {
        // most spec list entries have leading lower-case field
        // names which is inconsistent with defined field names.
        // make all field names lead with an upper-case letter.
        $keys      = array_map('ucfirst', array_keys($listEntry));
        $listEntry = array_combine($keys, $listEntry);

        // instantiate new spec object and set raw field values.
        $spec = new static($connection);
        $spec->_setValues($listEntry);
        return $spec;
    }

    /**
     * Produce set of flags for the spec list command, given fetch all options array.
     *
     * @param   array   $options    array of options to augment fetch behavior.
     *                              see fetchAll for documented options.
     * @return  array   set of flags suitable for passing to spec list command.
     */
    protected static function _getFetchAllFlags($options)
    {
        $flags = array();

        if (isset($options[self::FETCH_MAXIMUM])) {
            $flags[] = "-m";
            $flags[] = (int) $options[self::FETCH_MAXIMUM];
        }

        return $flags;
    }

    /**
     * Get the fetch all command, generally a plural version of the spec type.
     *
     * @return  string  Perforce command to use for fetchAll
     */
    protected static function _getFetchAllCommand()
    {
        // derive list command from spec type by adding 's'
        // this works for most of the known plural specs
        return static::_getSpecType() . "s";
    }
}
