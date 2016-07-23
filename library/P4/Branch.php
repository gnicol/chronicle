<?php
/**
 * Abstracts operations against Perforce branch specs.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        Add support for the following commands:
 *              integrate
 */
class P4_Branch extends P4_Spec_PluralAbstract
{
    const   FETCH_BY_NAME           = 'name';
    const   FETCH_BY_OWNER          = 'owner';

    protected static    $_specType  = 'branch';
    protected static    $_idField   = 'Branch';

    protected static    $_accessors = array(
        'Update'        => 'getUpdateDateTime',
        'Access'        => 'getAccessDateTime',
        'Owner'         => 'getOwner',
        'Description'   => 'getDescription',
        'Options'       => 'getOptions',
        'View'          => 'getView'
    );
    protected static    $_mutators  = array(
        'Owner'         => 'setOwner',
        'Description'   => 'setDescription',
        'Options'       => 'setOptions',
        'View'          => 'setView'
    );

    /**
     * Get all Branches from Perforce. Adds filtering options.
     *
     * @param   array   $options    optional - array of options to augment fetch behavior.
     *                              supported options are:
     *
     *                                   FETCH_MAXIMUM - set to integer value to limit to the
     *                                                   first 'max' number of entries.
     *                                   FETCH_BY_NAME - set to branch name pattern (e.g. 'bran*').
     *                                  FETCH_BY_OWNER - set to owner's username (e.g. 'jdoe').
     *
     * @param   P4_Connection_Interface $connection  optional - a specific connection to use.
     * @return  P4_Model_Iterator   all records of this type.
     */
    public static function fetchAll($options = array(), P4_Connection_Interface $connection = null)
    {
        // simply return parent - method exists to document options.
        return parent::fetchAll($options, $connection);
    }

    /**
     * Determine if the given branch id exists.
     *
     * @param   string                      $id             the id to check for.
     * @param   P4_Connection_Interface     $connection     optional - a specific connection to use.
     * @return  bool    true if the given id matches an existing branch.
     */
    public static function exists($id, P4_Connection_Interface $connection = null)
    {
        // check id for valid format
        if (!static::_isValidId($id)) {
            return false;
        }

        $branches = static::fetchAll(
            array(
                static::FETCH_BY_NAME => $id,
                static::FETCH_MAXIMUM => 1
            ),
            $connection
        );

        return (bool) count($branches);
    }

    /**
     * Get the last update time for this branch spec.
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
     * Get the last access time for this branch spec.
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
     * Get the owner of this branch.
     *
     * @return  string|null  User who owns this record.
     */
    public function getOwner()
    {
        return $this->_getValue('Owner');
    }

    /**
     * Set the owner of this branch to passed value.
     *
     * @param   string|null $owner  A string containing username or null for none
     * @return  P4_Branch   provides a fluent interface.
     * @throws  InvalidArgumentException  Owner is incorrect type.
     */
    public function setOwner($owner)
    {
        if (!is_string($owner) && !is_null($owner)) {
            throw new InvalidArgumentException('Owner must be a string or null.');
        }

        return $this->_setValue('Owner', $owner);
    }

    /**
     * Get the description for this branch.
     *
     * @return  string  description for this branch.
     */
    public function getDescription()
    {
        return $this->_getValue('Description');
    }

    /**
     * Set a description for this branch.
     *
     * @param   string|null $description    description for this branch.
     * @return  P4_Branch   provides a fluent interface.
     * @throws  InvalidArgumentException    Description is incorrect type.
     */
    public function setDescription($description)
    {
        if (!is_string($description) && !is_null($description)) {
            throw new InvalidArgumentException('Description must be a string or null.');
        }

        return $this->_setValue('Description', $description);
    }

    /**
     * Get options for this branch.
     *
     * @return  string  options which are set on this branch ('locked' or 'unlocked').
     */
    public function getOptions()
    {
        return $this->_getValue('Options');
    }

    /**
     * Set the options for this branch. See getOptions for expected values.
     *
     * @param   string|null $options    options to set on this branch.
     * @return  P4_Branch   provides a fluent interface.
     * @throws  InvalidArgumentException  Options are incorrect type.
     */
    public function setOptions($options)
    {
        if (!is_string($options) && !is_null($options)) {
            throw new InvalidArgumentException('Options must be a string or null.');
        }

        return $this->_setValue('Options', $options);
    }

    /**
     * Get the view for this branch.
     * View entries will be returned as an array with 'source' and 'target' entries, e.g.:
     * array (
     *      0 => array (
     *          'source' => '//depot/branchA/with space/...',
     *          'target' => '//depot/branchB/with space/...'
     *      )
     *  )
     *
     * @return  array  list view entries for this branch.
     */
    public function getView()
    {
        // The raw view data is formatted as:
        //  array (
        //      0 => '"//depot/example/with space/..." //depot/example/nospace/...',
        //  )
        //
        // We split this into 'source' and 'target' components via the str_getcsv function
        // and key the two resulting entries as 'source' and 'target'
        $view = array();
        // The ?: translates empty views into an empty array
        foreach ($this->_getValue('View') ?: array() as $entry) {
            $entry = str_getcsv($entry, ' ');
            $view[] = array_combine(array('source','target'), $entry);
        }

        return $view;
    }

    /**
     * Set the view for this branch.
     * View is passed as an array of view entries. Each view entry can be an array with
     * 'source' and 'target' entries or a raw string.
     *
     * @param   array  $view  View entries, formatted into source/target sub-arrays.
     * @return  P4_Branch   provides a fluent interface.
     * @throws  InvalidArgumentException  View array, or a view entry, is incorrect type.
     */
    public function setView($view)
    {
        if (!is_array($view)) {
            throw new InvalidArgumentException('View must be passed as array.');
        }

        // The View array contains either:
        // - Child arrays keyed on source/target which we glue together
        // - Raw strings which we simply leave as is
        // The below foreach run will normalize the whole thing for storage
        $parsedView = array();
        foreach ($view as $entry) {
            if (is_array($entry) &&
                isset($entry['source'], $entry['target']) &&
                is_string($entry['source']) &&
                is_string($entry['target'])) {
                $entry = '"'. $entry['source'] .'" "'. $entry['target'] .'"';
            }

            if (!is_string($entry)) {
                throw new InvalidArgumentException(
                   "Each view entry must be a 'source' and 'target' array or a string."
                );
            }

            $validate = str_getcsv($entry, ' ');
            if (count($validate) != 2 || trim($validate[0]) === '' || trim($validate[1]) === '') {
                throw new InvalidArgumentException(
                   "Each view entry must contain two depot paths, no more, no less."
                );
            }

            $parsedView[] = $entry;
        };

        return $this->_setValue('View', $parsedView);
    }

    /**
     * Add a view mapping to this Branch.
     *
     * @param   string  $source     the source half of the view mapping.
     * @param   string  $target     the target half of the view mapping.
     * @return  P4_Branch   provides a fluent interface.
     */
    public function addView($source, $target)
    {
        $mappings   = $this->getView();
        $mappings[] = array("source" => $source, "target" => $target);

        return $this->setView($mappings);
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

            if (!is_string($name) || trim($name) === "") {
                throw new InvalidArgumentException(
                    'Filter by Name expects a non-empty string as input'
                );
            }

            $flags[] = '-e';
            $flags[] = $name;
        }

        if (isset($options[static::FETCH_BY_OWNER])) {
            $owner = $options[static::FETCH_BY_OWNER];

            // We allow empty values as this returns branches with no owner
            if (!is_string($owner) || trim($owner) === "") {
                throw new InvalidArgumentException(
                    'Filter by Owner expects a non-empty string as input'
                );
            }

            $flags[] = '-u';
            $flags[] = $owner;
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
        // Branch is a special case; over-ridden to add 'es' instead of 's' to spec type.
        return static::_getSpecType() . "es";
    }

    /**
     * Given a spec entry from spec list output (p4 branches), produce
     * an instance of this spec with field values set where possible.
     *
     * @param   array                       $listEntry      a single spec entry from spec list output.
     * @param   array                       $flags          the flags that were used for this 'fetchAll' run.
     * @param   P4_Connection_Interface     $connection     a specific connection to use.
     * @return  P4_Branch                   a (partially) populated instance of this spec class.
     */
    protected static function _fromSpecListEntry($listEntry, $flags, P4_Connection_Interface $connection)
    {
        // update/access time are return as longs. Unset to avoid figuring out timezone
        // for a proper conversion.
        unset($listEntry['update']);
        unset($listEntry['access']);

        return parent::_fromSpecListEntry($listEntry, $flags, $connection);
    }
}
