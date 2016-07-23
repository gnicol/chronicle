<?php
/**
 * Provides a container for query options suitable for passing to fetchAll.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        add support for limiting returned fields.
 */
class P4Cms_Record_Query
{
    const QUERY_INCLUDE_DELETED = 'includeDeleted';
    const QUERY_RECORD_CLASS    = 'recordClass';
    const QUERY_LIMIT_FIELDS    = 'limitFields';
    const QUERY_MAX_DEPTH       = 'maxDepth';
    const QUERY_MAX_ROWS        = 'maxRows';
    const QUERY_PATHS           = 'paths';
    const QUERY_IDS             = 'ids';

    const SORT_DATE             = '#REdate';
    const SORT_HEAD_REV         = '#RErev';
    const SORT_HAVE_REV         = '#NEhrev';
    const SORT_FILE_TYPE        = '#NEtype';
    const SORT_FILE_SIZE        = '#REsize';
    const SORT_ASCENDING        = 'a';
    const SORT_DESCENDING       = 'd';

    const RECORD_BASE_CLASS     = 'P4Cms_Record';

    protected $_options         = null;
    protected $_query           = null;
    protected $_recordClass     = null;

    /**
     * Constructor that accepts an array of query options
     *
     * @param array $options Optional array of options to populate
     */
    public function __construct($options = array())
    {
        $this->reset();

        // some options that are valid for both this and file query as well,
        // may not be directly passed to the file query constructor
        $fileQueryOptions = array_diff($options, array(static::QUERY_LIMIT_FIELDS));
        $this->_query     = new P4_File_Query($fileQueryOptions);

        if (isset($options) and is_array($options)) {
            foreach ($options as $key => $value) {
                if (array_key_exists($key, $this->_options)) {
                    $method = 'set'. ucfirst($key);
                    $this->$method($value);
                }
            }
        }
    }

    /**
     * Creates and returns a new Query class. Useful for working
     * around PHP's lack of new chaining.
     *
     * @param   array  $options  Optional array of options to populate for new Query class
     * @return  P4Cms_Record_Query
     */
    public static function create($options = array())
    {
        return new static($options);
    }

    /**
     * Reset the current query object to its default state.
     *
     * @return  P4Cms_Record_Query  provide a fluent interface.
     */
    public function reset()
    {
        $this->_options = array(
            static::QUERY_INCLUDE_DELETED   => false,
            static::QUERY_RECORD_CLASS      => null,
            static::QUERY_LIMIT_FIELDS      => null,
            static::QUERY_MAX_DEPTH         => null,
            static::QUERY_MAX_ROWS          => null,
            static::QUERY_PATHS             => null,
            static::QUERY_IDS               => null
        );
        $this->_query = new P4_File_Query;
        return $this;
    }

    /**
     * Provide all of the current options as an array.
     *
     * @return  array  The current query options as an array.
     */
    public function toArray()
    {
        $array = $this->_query->toArray();

        // hide filespecs option (superseded by paths)
        unset($array[P4_File_Query::QUERY_FILESPECS]);
        
        // hide max-files option (superseded by max-rows)
        unset($array[P4_File_Query::QUERY_MAX_FILES]);

        return array_merge($array, $this->_options);
    }

    /**
     * Retrieve the current filter expression.
     * Null means no filtering will take place.
     *
     * @return  P4Cms_Record_Filter|null  The current filter object or null.
     */
    public function getFilter()
    {
        return $this->_query->getFilter();
    }

    /**
     * Add a filter to this query.
     *
     * @param   P4Cms_Record_Filter     $filter     the filter to add.
     * @return  P4Cms_Record_Query      provides fluent interface.
     */
    public function addFilter(P4Cms_Record_Filter $filter)
    {
        $currentFilter = $this->getFilter();
        if (!$currentFilter) {
            return $this->setFilter($filter);
        }

        $currentFilter->addSubFilter($filter);
        return $this;
    }

    /**
     * Set the "filter expression" to limit the returned set of records.
     * See 'p4 help fstat' and 'p4 help jobview' for more information on
     * the filter format. Accepts a P4Cms_Record_Filter or string for input,
     * or null to remove any filter.
     *
     * @param   string|array|P4Cms_Record_Filter|null  $filter  The desired filter expression.
     * @return  P4Cms_Record_Query  provide a fluent interface.
     */
    public function setFilter($filter = null)
    {
        if (is_string($filter) || is_array($filter)) {
            $filter = new P4Cms_Record_Filter($filter);
        }
        
        if (!$filter instanceof P4Cms_Record_Filter && !is_null($filter)) {
            throw new InvalidArgumentException(
                'Cannot set filter; argument must be a P4Cms_Record_Filter, an array, a string, or null.'
            );
        }
        $this->_query->setFilter($filter);
        return $this;
    }

    /**
     * Get the current sort field.
     * Null means default sorting will take place.
     *
     * @return  string  The current sort field, or null if not set.
     */
    public function getSortBy()
    {
        return $this->_query->getSortBy();
    }

    /**
     * Set the record field which will be used to sort results. Valid sort fields are:
     * SORT_DATE, SORT_HEAD_REV, SORT_HAVE_REV, SORT_FILE_TYPE, SORT_FILE_SIZE.
     * Specify null to receive records in the default order.
     *
     * @param   array|string|null   $sortBy   An array of fields or field => options, a string field,
     *                                        or default null.
     * @param   array|null          $options  Sorting options, only used when sortBy is a string.
     * @return  P4Cms_Record_Query  provide a fluent interface.
     * @see     P4_File_Query
     */
    public function setSortBy($sortBy = null, $options = null)
    {
        $this->_query->setSortBy($sortBy, $options);
        return $this;
    }

    /**
     * Retrieve the current reverse order flag setting.
     * True means that the sort order will be reversed.
     *
     * @return  boolean  true if the sort order will be reversed.
     */
    public function getReverseOrder()
    {
        return $this->_query->getReverseOrder();
    }

    /**
     * Set the flag indicating whether the results will be returned in reverse order.
     *
     * @param   boolean  $reverse  Set to true to reverse sort order.
     * @return  P4Cms_Record_Query  provide a fluent interface.
     */
    public function setReverseOrder($reverse = false)
    {
        $this->_query->setReverseOrder($reverse);
        return $this;
    }

    /**
     * Retrieve the flag indicating whether deleted files will be included in results.
     * True means deleted files will be included.
     *
     * @return  boolean  True indicates deleted files included.
     */
    public function getIncludeDeleted()
    {
        return $this->_options[static::QUERY_INCLUDE_DELETED];
    }

    /**
     * Set the flag indicating whether deleted files will be included in results.
     * True means deleted files should be included.
     *
     * @param   boolean  $include  Flag to include deleted files.
     * @return  P4Cms_Record_Query  provide a fluent interface.
     */
    public function setIncludeDeleted($include = false)
    {
        $this->_options[static::QUERY_INCLUDE_DELETED] = (bool) $include;
        return $this;
    }

    /**
     * Return the starting row for matching records.
     * Null means all matching records will be returned.
     *
     * @return  int|null  The starting row.
     */
    public function getStartRow()
    {
        return $this->_query->getStartRow();
    }

    /**
     * Set the starting row to return from matching records,
     * or null to return all matching records.
     *
     * @param   int|null  $row   The starting row.
     * @return  P4Cms_Record_Query    provide a fluent interface.
     */
    public function setStartRow($row = null)
    {
        $this->_query->setStartRow($row);
        return $this;
    }

    /**
     * Retrieve the maximum number of records to include in results.
     * 0 or null means unlimited.
     *
     * @return  integer  The maximum number of records to include in results.
     */
    public function getMaxRows()
    {
        return $this->_query->getMaxFiles();
    }

    /**
     * Set to limit the number of matching records returned, or null
     * to return all matching records.
     *
     * @param   int|null  $max  The maximum number of records to return.
     * @return  P4_File_Query   provide a fluent interface.
     */
    public function setMaxRows($max = null)
    {
        $this->_options[static::QUERY_MAX_ROWS] = $max;
        $this->_query->setMaxFiles($max);
        return $this;
    }

    /**
     * Retrieve the list of record fields to return in server responses.
     * Null means all fields will be returned.
     *
     * @return  array|null  The current list of record fields.
     */
    public function getLimitFields()
    {
        return $this->_options[static::QUERY_LIMIT_FIELDS];
    }

    /**
     * Set the list of fields to include in the response from the server.
     *
     * Record limitFields are converted to the file fields (i.e. file attributes) when
     * this query is converted to the file query as we need to know the record class to
     * determine id and file content fields that have to be skipped.
     *
     * @param   string|array|null   $fields     The list of desired fields. Supply a string to specify
     *                                          one field, or supply a null to retrieve all fields.
     * @return  P4Cms_Record_Query  provide a fluent interface.
     */
    public function setLimitFields($fields = array())
    {
        $this->_options[static::QUERY_LIMIT_FIELDS] = $fields;
        return $this;
    }

    /**
     * Retrieve the maximum path depth of records to include in results.
     * 0 means include records only at the current depth. Null means
     * unlimited.
     *
     * @return  integer  The maximum depth of records to include in results.
     */
    public function getMaxDepth()
    {
        return $this->_options[static::QUERY_MAX_DEPTH];
    }

    /**
     * Set the maximum path depth for records to be included in results.
     * 0 means include records only at the current depth. Null means
     * unlimited.
     *
     * @param   integer  $depth  The maximum depth of records to include in results.
     * @return  P4Cms_Record_Query  provide a fluent interface.
     */
    public function setMaxDepth($depth = null)
    {
        // accept numeric string values, for convenience.
        if (is_string($depth)) {
            $depth = (int) $depth;
        }
        if (isset($depth) and (!is_integer($depth) or $depth < 0)) {
            throw new InvalidArgumentException(
                'Cannot set maximum depth; argument must be a non-negative integer or null.'
            );
        }

        $this->_options[static::QUERY_MAX_DEPTH] = $depth;
        return $this;
    }

    /**
     * Retrieve the IDs to be used to fetch records.
     *
     * @return  array|null  The list of ids to be used to fetch records.
     */
    public function getIds()
    {
        return $this->_options[static::QUERY_IDS];
    }

    /**
     * Set the list of IDs to be used to fetch records.
     *
     * @param   array|null  $ids    The IDs to be used to fetch records.
     * @return  P4Cms_Record_Query  provide a fluent interface.
     */
    public function setIds($ids = null)
    {
        if (!is_array($ids) && !is_null($ids)) {
            throw new InvalidArgumentException("IDs should be an array or null");
        }

        $this->_options[static::QUERY_IDS] = $ids;
        return $this;
    }

    /**
     * Add a single ID to the list of IDs to be used to fetch records.
     *
     * @param   string|int  $id         An ID to be added.
     * @return  P4Cms_Record_Query      provide a fluent interface.
     */
    public function addId($id)
    {
        if (!is_string($id) && !is_int($id)) {
            throw new InvalidArgumentException('Cannot add ID; argument must be a string or int.');
        }

        return $this->addIds(array($id));
    }

    /**
     * Add IDs to the list of IDs to be used to fetch records.
     *
     * @param   array   $ids            set of string or int IDs to be added.
     * @return  P4Cms_Record_Query      provide a fluent interface.
     */
    public function addIds($ids)
    {
        if (!is_array($ids)) {
            throw new InvalidArgumentException('Cannot add IDs; argument must be an array.');
        }

        $this->setIds(array_merge($this->getIds() ?: array(), $ids));
        
        return $this;
    }

    /**
     * Remove a single ID from the list to be used to fetch records.
     *
     * @param   string|int  $id     ID to remove from list
     * @return  P4Cms_Record_Query  provide a fluent interface.
     */
    public function removeId($id)
    {
        if (!is_string($id) && !is_int($id)) {
            throw new InvalidArgumentException('Cannot remove id; argument must be a string or int.');
        }

        return $this->removeIds(array($id));
    }

    /**
     * Remove IDs from the list to be used to fetch records.
     *
     * @param   array  $ids         The IDs to be removed.
     * @return  P4Cms_Record_Query  provide a fluent interface.
     */
    public function removeIds($ids = array())
    {
        if (!is_array($ids)) {
            throw new InvalidArgumentException('Cannot remove IDs; argument must be an array.');
        }

        $currentIds = $this->getIds() ?: array();
        foreach ($ids as $id) {
            $index = array_search($id, $currentIds);
            if ($index !== false) {
                unset($currentIds[$index]);
            }
        }
        $this->setIds($currentIds);

        return $this;
    }

    /**
     * Retrieve the paths to be used to fetch records.
     *
     * @return  array|null  The list of paths to be used to fetch records.
     */
    public function getPaths()
    {
        return $this->_query->getFilespecs();
    }

    /**
     * Set the list of paths to be used to fetch records.
     *
     * @param   array|null  $paths  The paths to be used to fetch records.
     * @return  P4Cms_Record_Query  provide a fluent interface.
     */
    public function setPaths($paths = null)
    {
        $this->_query->setFilespecs($paths);
        $this->_options[static::QUERY_PATHS] = $paths;
        return $this;
    }

    /**
     * Add a single path to the list of paths to be used to fetch records.
     *
     * @param   string  $path           A path to be added.
     * @param   bool    $intersect      optional - defaults to false - intersect with existing paths.
     * @return  P4Cms_Record_Query      provide a fluent interface.
     */
    public function addPath($path, $intersect = false)
    {
        if (!isset($path) or !is_string($path)) {
            throw new InvalidArgumentException('Cannot add path; argument must be a string.');
        }

        return $this->addPaths(array($path), $intersect);
    }

    /**
     * Add paths to the list of paths to be used to fetch records.
     *
     * @param   array   $paths          set of paths to be added.
     * @param   bool    $intersect      optional - defaults to false - intersect with existing paths.
     * @return  P4Cms_Record_Query      provide a fluent interface.
     */
    public function addPaths($paths = array(), $intersect = false)
    {
        if (!isset($paths) or !is_array($paths)) {
            throw new InvalidArgumentException('Cannot add paths; argument must be an array.');
        }

        // if paths are currently null, no need to combine.
        if ($this->getPaths() === null) {
            $this->setPaths($paths);
            return $this;
        }

        // combine arrays appropriately
        if ($intersect) {
            $this->setPaths(array_intersect($this->getPaths(), $paths));
        } else {
            $this->setPaths(array_merge($this->getPaths(), $paths));
        }

        return $this;
    }

    /**
     * Remove a single path from the list to be used to fetch records.
     * 
     * @param   string  $path       path to remove from list
     * @return  P4Cms_Record_Query  provide a fluent interface.
     */
    public function removePath($path)
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException('Cannot remove path; argument must be a string.');
        }

        return $this->removePaths(array($path));
    }

    /**
     * Remove paths from the list to be used to fetch records.
     *
     * @param   array  $paths  The paths to be removed.
     * @return  P4Cms_Record_Query  provide a fluent interface.
     */
    public function removePaths($paths = array())
    {
        if (!isset($paths) or !is_array($paths)) {
            throw new InvalidArgumentException('Cannot remove paths; argument must be an array.');
        }

        $currentPaths = $this->getPaths() ?: array();
        foreach ($paths as $path) {
            $index = array_search($path, $currentPaths);
            if ($index !== false) {
                array_splice($currentPaths, $index, 1);
            }
        }
        $this->setPaths($currentPaths);

        return $this;
    }

    /**
     * Set the record class to use when preparing the query for execution.
     * If no record class is set uses the base record class.
     * 
     * @param   object|string|null  $class  instance or name of a record class - null to clear.
     * @return  P4Cms_Record_Query          provides fluent interface. 
     * @throws  InvalidArgumentException    if the given class is not a valid record class.
     */
    public function setRecordClass($class)
    {
        $class = is_object($class) ? get_class($object) : $class;
        
        // only validate if class is not null.
        if ($class) {
            $this->_validateRecordClass($class);
        }

        $this->_recordClass = $class;

        return $this;
    }
    
    /**
     * Get the record class to use when preparing the query.
     * If no record class has been set, returns the base record class.
     * 
     * @return  string  the name of the record class to use.
     */
    public function getRecordClass()
    {
        return $this->_recordClass ?: static::RECORD_BASE_CLASS;
    }
    
    /**
     * Compose a P4_File_Query to be used in P4_File::fetchAll() calls.
     *
     * @param   string                  $recordClass    optional - a specific record class to influence storage paths.
     * @param   P4Cms_Record_Adapter    $adapter        optional - storage adapter to use.
     * @return  P4_File_Query           A query object for use with P4_File::fetchAll()
     */
    public function toFileQuery($recordClass = null, P4Cms_Record_Adapter $adapter = null)
    {
        // validate record class.
        $recordClass = $recordClass ?: $this->getRecordClass();
        $this->_validateRecordClass($recordClass);

        // if no adapter given, use default.
        $adapter = $adapter ?: $recordClass::getDefaultAdapter();

        // determine location of records in depot.
        $depotStoragePath = $recordClass::getDepotStoragePath($adapter);
        $storagePath      = $recordClass::getStoragePath($adapter);

        // clone the current query options, so that later on we
        // don't have to undo the modifications below.
        $query = clone $this->_query;

        // update the filter to remove deleted records, unless include deleted is true
        if (!$this->getIncludeDeleted()) {
            $filter = "^headAction=...delete";
            $filter = $query->getFilter() !== null && (string) $query->getFilter() !== ''
                ? '('. $query->getFilter() .') & '. $filter
                : $filter;

            $query->setFilter($filter);
        }

        // set limit fields - we have to do it here as we need to know recordClass
        // to ignore id and file content fields
        if ($this->_options[static::QUERY_LIMIT_FIELDS]) {
            // always include depotFile field
            $limitFields = array('depotFile');

            $fields = (array) $this->_options[static::QUERY_LIMIT_FIELDS];
            foreach ($fields as $field) {
                // ignore id and file content fields
                if ($field === $recordClass::getIdField() 
                    || ($recordClass::hasFileContentField() && $field === $recordClass::getFileContentField())
                ) {
                    continue;
                }

                $limitFields[] = 'attr-' . $field;
            }

            $query->setLimitFields($limitFields);
        }

        // modify the filter to limit results by depth, if required
        if ($this->getMaxDepth() !== null) {
            // restrict depth by filtering depot paths deeper than max depth.
            $filter = '^depotFile=' . $depotStoragePath
                    . str_repeat('/*', $this->getMaxDepth() + 1) .'/...';
            $filter = $query->getFilter() !== null
                ? '('. $query->getFilter() . ') & '. $filter
                : $filter;

            $query->setFilter($filter);
        }

        $filespecs = array();

        // collect all paths, prepending storage path
        foreach ($this->getPaths() ?: array() as $path) {
            $filespecs[] = $storagePath . "/" . $path;
        }

        // collect all IDs, translating to filespec
        foreach ($this->getIds() ?: array() as $id) {
            $filespecs[] = $recordClass::idToFilespec($id, $adapter);
        }

        // do any required global touchup
        foreach ($filespecs as &$filespec) {
            if (!P4_File::hasRevspec($filespec)) {
                $filespec .= '#head';
            }
        }

        // four cases for handling path filters:
        //            null - set to entire storage path and append #head to hide pending adds.
        //     empty array - leave empty (no results desired).
        //        one path - simply set the one filespec directly.
        //  multiple paths - put paths in a temp label to limit results.
        if ($this->getPaths() === null && $this->getIds() === null) {
            $query->setFilespecs($storagePath . "/...#head");
        } else {
            switch (count($filespecs)) {
                case 0:
                case 1:
                    $query->setFilespecs($filespecs);
                    break;
                default:
                    $label = P4_Label::makeTemp(
                        array('View' => array($depotStoragePath . '/...')),
                        null,
                        $adapter->getConnection()
                    );
                    $label->tag($filespecs);
                    $query->setFilespecs($storagePath . '/...@' . $label->getId());
            }
        }

        return $query;
    }
    
    /**
     * Verify that the given class name is a valid record class.
     * 
     * @param   string  $class              the class name to verify.
     * @throws  InvalidArgumentException    if the class does not exist or is not a record. 
     */
    protected function _validateRecordClass($class) 
    {
        $base = static::RECORD_BASE_CLASS;
        
        // ensure class exists and is a valid record class.
        if (!class_exists($class) 
            || (!is_subclass_of($class, $base) && $class !== $base)
        ) {
            throw new InvalidArgumentException("Invalid record class given.");
        }
    }
}
