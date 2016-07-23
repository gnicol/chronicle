<?php
/**
 * Abstracts operations against Perforce labels.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Label extends P4_Spec_PluralAbstract
{
    const   FETCH_BY_NAME           = 'name';
    const   FETCH_BY_OWNER          = 'owner';

    protected static    $_specType  = 'label';
    protected static    $_idField   = 'Label';

    protected static    $_accessors = array(
        'Update'        => 'getUpdateDateTime',
        'Access'        => 'getAccessDateTime',
        'Owner'         => 'getOwner',
        'Description'   => 'getDescription',
        'Options'       => 'getOptions',
        'Revision'      => 'getRevision',
        'View'          => 'getView'
    );
    protected static    $_mutators  = array(
        'Owner'         => 'setOwner',
        'Description'   => 'setDescription',
        'Options'       => 'setOptions',
        'Revision'      => 'setRevision',
        'View'          => 'setView'
    );

    /**
     * Get all Labels from Perforce. Adds filtering options.
     *
     * @param   array   $options    optional - array of options to augment fetch behavior.
     *                              supported options are:
     *
     *                                   FETCH_MAXIMUM - set to integer value to limit to the
     *                                                   first 'max' number of entries.
     *                                   FETCH_BY_NAME - set to label name pattern (e.g. 'labe*').
     *                                  FETCH_BY_OWNER - set to owner's username (e.g. 'jdoe').
     *
     * @param   P4_Connection_Interface $connection optional - a specific connection to use.
     * @return  P4_Model_Iterator   all records of this type.
     */
    public static function fetchAll($options = array(), P4_Connection_Interface $connection = null)
    {
        // simply return parent - method exists to document options.
        return parent::fetchAll($options, $connection);
    }

    /**
     * Determine if the given label id exists.
     *
     * @param   string                      $id             the id to check for.
     * @param   P4_Connection_Interface     $connection     optional - a specific connection to use.
     * @return  bool    true if the given id matches an existing label.
     */
    public static function exists($id, P4_Connection_Interface $connection = null)
    {
        // check id for valid format
        if (!static::_isValidId($id)) {
            return false;
        }

        $labels = static::fetchAll(
            array(
                static::FETCH_BY_NAME => $id,
                static::FETCH_MAXIMUM => 1
            ),
            $connection
        );

        return (bool) count($labels);
    }

    /**
     * Get the last update time for this label spec.
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
     * Get the last access time for this label spec.
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
     * Get the owner of this label.
     *
     * @return  string|null User who owns this record.
     */
    public function getOwner()
    {
        return $this->_getValue('Owner');
    }

    /**
     * Set the owner of this label to passed value.
     *
     * @param   string|P4_User|null $owner  A string containing username
     * @return  P4_Label   provides a fluent interface.
     * @throws  InvalidArgumentException  Owner is incorrect type.
     */
    public function setOwner($owner)
    {
        if ($owner instanceof P4_User) {
            $owner = $owner->getId();
        }

        if (!is_string($owner) && !is_null($owner)) {
            throw new InvalidArgumentException('Owner must be a string, P4_User or null.');
        }

        return $this->_setValue('Owner', $owner);
    }

    /**
     * Get the description for this label.
     *
     * @return  string|null description for this label.
     */
    public function getDescription()
    {
        return $this->_getValue('Description');
    }

    /**
     * Set a description for this label.
     *
     * @param   string|null $description    description for this label.
     * @return  P4_Label   provides a fluent interface.
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
     * Get options for this label.
     *
     * @return  string|null options which are set on this label ('locked' or 'unlocked').
     */
    public function getOptions()
    {
        return $this->_getValue('Options');
    }

    /**
     * Set the options for this label. See getOptions for expected values.
     *
     * @param   string|null $options    options to set on this label.
     * @return  P4_Label    provides a fluent interface.
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
     * Get the revision setting for this label.
     *
     * @return  string|null Revision setting for this label.
     */
    public function getRevision()
    {
        $revision = $this->_getValue('Revision');

        // strip quotes if needed
        if (is_string($revision) &&
            substr($revision, 0, 1) === '"' &&
            substr($revision, -1) === '"') {
            return substr($revision, 1, -1);
        }

        return $revision;
    }

    /**
     * Set the revision setting for this label.
     *
     * @param   string|null $revision   Revision setting for this label.
     * @return  P4_Label    provides a fluent interface.
     * @throws  InvalidArgumentException  revision is incorrect type.
     */
    public function setRevision($revision)
    {
        if (!is_string($revision) && !is_null($revision)) {
            throw new InvalidArgumentException('Revision must be a string or null.');
        }

        // quote string values; leaves null values alone
        if (is_string($revision)) {
            $revision = '"' . $revision . '"';
        }

        return $this->_setValue('Revision', $revision);
    }

    /**
     * Get the view for this label.
     * View entries will be returned as an array of strings e.g.:
     * array (
     *      0 => '//depot/example/with space/...',
     *      1 => '//depot/alternate/example/*'
     *  )
     * Labels view is fairly unique as each entry is only one depot path.
     *
     * @return  array  list view entries for this label, empty array if none.
     */
    public function getView()
    {
        return $this->_getValue('View') ?: array();
    }

    /**
     * Set the view for this label. See getView for format details.
     *
     * @param   array  $view  Array of view strings, empty array for none.
     * @return  P4_Label     provides a fluent interface.
     * @throws  InvalidArgumentException  View array, or a view entry, is incorrect type.
     */
    public function setView($view)
    {
        if (!is_array($view)) {
            throw new InvalidArgumentException('View must be passed as array.');
        }

        foreach ($view as $entry) {
            if (!is_string($entry) || trim($entry) === "") {
                throw new InvalidArgumentException(
                   "Each view entry must be a non-empty string."
                );
            }
        }

        return $this->_setValue('View', $view);
    }

    /**
     * Add a view entry to this Label.
     *
     * @param   string  $path   the depot path to add.
     * @return  P4_Label    provides a fluent interface.
     */
    public function addView($path)
    {
        $entries   = $this->getView();
        $entries[] = $path;

        return $this->setView($entries);
    }

    /**
     * Adds the specified filespecs to this label. The update is completed
     * synchronously, no need to call save.
     *
     * @param   array   $filespecs  The filespecs to add to this label, can include rev-specs
     * @return  P4_Label            provides a fluent interface.
     */
    public function tag($filespecs)
    {
        if (!is_array($filespecs) || in_array(false, array_map('is_string', $filespecs))) {
            throw new InvalidArgumentException(
                'Tag requires an array of string values for input'
            );
        }

        // there is a potential to exceed the arg-max limit;
        // run tag command as few times as possible
        $connection = $this->getConnection();
        $batches    = $connection->batchArgs($filespecs, array('-l', $this->getId()));
        foreach ($batches as $batch) {
            $connection->run('tag', $batch);
        }

        return $this;
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

            if (!is_string($name) || trim($name) === '') {
                throw new InvalidArgumentException(
                    'Filter by Name expects a non-empty string as input'
                );
            }

            $flags[] = '-e';
            $flags[] = $name;
        }

        if (isset($options[static::FETCH_BY_OWNER])) {
            $owner = $options[static::FETCH_BY_OWNER];

            // We allow empty values as this returns labels with no owner
            if (!is_string($owner) || trim($owner) === '') {
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
     * Given a spec entry from spec list output (p4 labels), produce
     * an instance of this spec with field values set where possible.
     *
     * @param   array                       $listEntry      a single spec entry from spec list output.
     * @param   array                       $flags          the flags that were used for this 'fetchAll' run.
     * @param   P4_Connection_Interface     $connection     a specific connection to use.
     * @return  P4_Label                    a (partially) populated instance of this spec class.
     * @todo    account for timezone when converting from unixtime to string date.
     */
    protected static function _fromSpecListEntry($listEntry, $flags, P4_Connection_Interface $connection)
    {
        // convert unixtimes to date string format.
        $listEntry['Access'] = date("Y/m/d G:i:s", $listEntry['Access']);
        $listEntry['Update'] = date("Y/m/d G:i:s", $listEntry['Update']);

        return parent::_fromSpecListEntry($listEntry, $flags, $connection);
    }
}
