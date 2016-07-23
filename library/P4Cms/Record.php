<?php
/**
 * Provides persistent storage of data models in Perforce.
 * Each record corresponds to a file in Perforce. Each record
 * may contain properties that will be stored as attributes
 * on the corresponding file (if sub-classed, a single property
 * may be selected for storage in the file).
 *
 * Records are schemaless. Records of the same kind are not
 * obligated to have the same fields. However, the record class
 * may be sub-classed to define fields.
 *
 * Each record has an id that uniquely identifies the record in
 * the record storage path. The storage base path is provided
 * by the record storage adapter and may be narrowed (if sub-
 * classed) by specifying a storage sub-path.
 *
 * If no id is specified when saving a record a new UUID will
 * be assigned. UUIDs are used instead of incrementing numbers
 * because they avoid collisions when record files are moved or
 * branched in the depot.
 *
 * A single record can be fetched by its id via the fetch()
 * method. Multiple records can be fetched via fetchAll().
 *
 * Records can be saved via the save() method and deleted via
 * delete(). Each save() and delete() constitutes a submit in
 * Perforce and produces a new revision of the record.
 *
 * Field names must be valid as file attribute names.
 * Additionally, field names must not begin with an underscore
 * ('_'). Leading underscore is reserved for field metadata.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record extends P4Cms_Record_Connected
{
    const               ENCODING_METADATA_KEY       = "_encoding";
    const               ENCODING_FORMAT_JSON        = "json";
    const               SAVE_THROW_CONFLICT         = "throw";
    const               FROM_FILE_IMPORT            = 'import';

    protected           $_id                    = null;
    protected           $_p4File                = null;
    protected           $_metadata              = array();
    protected           $_needsPopulate         = false;
    protected           $_needsFilePopulate     = false;
    protected static    $_whereCache            = array();
    protected static    $_hasValidFields        = null;

    /**
     * All records should have an id field.
     */
    protected static    $_idField           = 'id';

    /**
     * Optionally, bin2hex encode identifiers when converting
     * to/from depot filespecs to permit non-standard characters.
     */
    protected static    $_encodeIds         = false;

    /**
     * Specifies the array of fields that the current Record class wishes to use.
     * The implementing class MUST set this property.
     */
    protected static    $_fields            = array();

    /**
     * Specifies the sub-path to use for storage of records.
     * This is used in combination with the records path (provided
     * by the storage adapter) to construct the full storage path.
     * The implementing class MUST set this property.
     */
    protected static    $_storageSubPath    = null;

    /**
     * Specifies the name of the record field which will be
     * persisted in the file used to store the records.
     * If desired, the implementing class needs to set this property
     * to match an entry defined in the $_fields array.
     * If left null, all fields will persist as file attributes.
     */
    protected static    $_fileContentField  = null;

    /**
     * Create a new record instance, using optional field values, in a chainable fashion.
     *
     * @param   array                   $values     associative array of keyed field values
     *                                              to load into the model.
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     */
    public static function create($values = null, P4Cms_Record_Adapter $adapter = null)
    {
        return new static($values, $adapter);
    }

    /**
     * Determine if id is valid identifier for this record.
     *
     * @param   string  $id     record identifier
     * @return  boolean         true if valid, otherwise false
     */
    public function isValidId($id)
    {
        $id = static::$_encodeIds ? static::_encodeId($id) : $id;
        $validator = new P4Cms_Validate_RecordId;
        return $validator->isValid($id);
    }

    /**
     * Get the id of this record.
     * Extended to always return a string or null.
     *
     * @return  string|null     the value of the id field.
     */
    public function getId()
    {
        $id = parent::getId();

        // cast non-null ids to strings.
        return $id === null ? null : (string) $id;
    }

    /**
     * Set the id of this record.
     *
     * @param   string|int|null     $id     the identifier of this record.
     * @return  P4Cms_Record        provides fluent interface.
     */
    public function setId($id)
    {
        if ($id !== null && !$this->isValidId($id)) {
            throw new InvalidArgumentException("Cannot set id. Given id is invalid.");
        }

        // if populate was deferred, caller expects it
        // to have been populated already.
        $this->_populate();

        // if id has changed, clear associated p4 file.
        if ($id !== $this->getId()) {
            $this->_p4File = null;
        }

        return parent::setId($id);
    }

    /**
     * Get all of the model field names.
     * Extends parent to populate first and throw an exception
     * if it encounters any invalid field names.
     *
     * @return  array                   a list of field names for this model.
     * @throws  P4Cms_Record_Exception  if any of the predefined field names are invalid.
     */
    public function getFields()
    {
        // populate but skip getting the file contents at this point
        $this->_populate(true);

        // validate predefined fields on first access.
        if (static::$_hasValidFields === null) {
            static::$_hasValidFields = true;
            $validator = new P4Cms_Validate_RecordField;
            foreach ($this->getDefinedFields() as $field) {
                if (!$validator->isValid($field)) {
                    static::$_hasValidFields = false;
                }
            }
        }

        // if fields are invalid, throw exception.
        if (static::$_hasValidFields === false) {
            throw new P4Cms_Record_Exception(
                "Cannot get fields. Record has one or more fields with invalid names."
            );
        }

        // let parent do its thing.
        $fields = parent::getFields();

        // ensure file content field is present if defined.
        if (!empty(static::$_fileContentField) && !in_array(static::$_fileContentField, $fields)) {
            $fields[] = static::$_fileContentField;
        }

        return $fields;
    }

    /**
     * Set a particular field value.
     * Extends parent to validate names of new fields.
     *
     * @param   string  $field  the name of the field to set the value of.
     * @param   mixed   $value  the value to set in the field.
     * @return  P4Cms_Model     provides a fluent interface
     * @throws  P4Cms_Model_Exception   if the field does not exist.
     * @throws  P4Cms_Record_Exception  if the field name is invalid.
     */
    public function setValue($field, $value)
    {
        // if field is new, validate field name.
        if (!$this->hasField($field)) {
            $validator = new P4Cms_Validate_RecordField;
            if (!$validator->isValid($field)) {
                throw new P4Cms_Record_Exception(
                    "Cannot set value. Field '$field' is not a valid field name."
                );
            }
        }

        return parent::setValue($field, $value);
    }

    /**
     * Set all of the model's values at once.
     * Extends parent to support passing a form object.
     *
     * Accepting a form object permits special handling of certain form
     * elements via the P4Cms_Record_EnhancedElementInterface. This interface
     * requires a populateRecord() method which allows the element to make
     * decisions and modify other aspects of the record object.
     *
     * @param   Zend_Form|array|null    $values     form or array of values to set on record.
     * @param   bool                    $filter     optional - if true, ignores values for unknown fields.
     * @return  P4Cms_Record            provides a fluent interface
     */
    public function setValues($values, $filter = false)
    {
        // let parent deal with non-form input.
        if (!$values instanceof Zend_Form) {
            return parent::setValues($values, $filter);
        }

        // set values from form input.
        $form   = $values;
        $values = $form->getValues();
        foreach ($values as $field => $value) {

            // skip read-only fields.
            if ($this->isReadOnlyField($field)) {
                continue;
            }

            // skip filtered fields.
            if ($filter && !$this->hasField($field)) {
                continue;
            }

            // handle record-aware elements.
            $element = $form->getElement($field);
            if ($element instanceof P4Cms_Record_EnhancedElementInterface) {
                $element->populateRecord($this);
            } else {
                $this->setValue($field, $value);
            }
        }

        return $this;
    }

    /**
     * Set a field value to the contents of the given file.
     *
     * @param   string          $field      the field to set the value of.
     * @param   string          $file       the full path to the file to read from.
     * @return  P4Cms_Record                provides fluent interface.
     * @throws  InvalidArgumentException    if the given file does not exist.
     */
    public function setValueFromFile($field, $file)
    {
        if (!is_file($file)) {
            throw new InvalidArgumentException("Cannot set value from file. File does not exist.");
        }

        $this->setValue($field, file_get_contents($file));

        return $this;
    }

    /**
     * Check if a record with the given id exists.
     *
     * Query options may, optionally, be passed. Any paths/ids present
     * in the options will ignored.
     *
     * @param   string|int                      $id         the id of the record to fetch.
     * @param   P4Cms_Record_Query|array|null   $query      optional - query options to augment result.
     * @param   P4Cms_Record_Adapter            $adapter    optional - storage adapter to use.
     * @return  bool                            true if the record exists, false otherwise.
     */
    public static function exists(
        $id,
        $query = null,
        P4Cms_Record_Adapter $adapter = null)
    {
        $query = static::_normalizeQuery($query);

        // if no id given, return false.
        if (!strlen($id)) {
            return false;
        }

        // clobber any existing IDs with our own and clear any paths on query.
        $query->setIds(array($id))->setPaths(array());

        return static::count($query, $adapter) > 0;
    }

    /**
     * Get a specific record by id.
     * A revision specifier may be, optionally, included in the id field.
     * Rev Specifiers will influence the data returned but will not be
     * present in the id of the returned record.
     *
     * Query options may, optionally, be passed. Any paths/ids present
     * in the options will ignored.
     *
     * @param   string|int                      $id         the id of the record to fetch.
     * @param   P4Cms_Record_Query|array|null   $query      optional - query options to augment result.
     * @param   P4Cms_Record_Adapter            $adapter    optional - storage adapter to use.
     * @return  P4Cms_Record                    the requested record.
     * @throws  P4Cms_Record_NotFoundException  if the requested record can't be found.
     */
    public static function fetch($id, $query = null, P4Cms_Record_Adapter $adapter = null)
    {
        $query = static::_normalizeQuery($query);

        // clobber any existing IDs with our own and clear any paths on options.
        $query->setIds(array($id))->setPaths(array());

        $results = static::fetchAll($query, $adapter);

        if (!count($results)) {
            throw new P4Cms_Record_NotFoundException(
                "Cannot fetch record '$id'. Record does not exist."
            );
        }

        return $results->first();
    }

    /**
     * Get all records under the record storage path.
     * Results can be limited by providing a query object or array.
     *
     * @param   P4Cms_Record_Query|array|null   $query      optional - query options to augment result.
     * @param   P4Cms_Record_Adapter            $adapter    optional - storage adapter to use.
     * @return  P4Cms_Model_Iterator            all records of this type.
     */
    public static function fetchAll($query = null, P4Cms_Record_Adapter $adapter = null)
    {
        $query = static::_normalizeQuery($query);

        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        // convert record query to a p4 file query.
        $query = $query->toFileQuery(get_called_class(), $adapter);

        // early exit if no filespecs in query, return empty iterator.
        if (is_array($query->getFilespecs()) && !count($query->getFilespecs())) {
            return new P4Cms_Model_Iterator;
        }

        // fetch files from perforce.
        $files = P4_File::fetchAll($query, $adapter->getConnection());

        // convert files to records.
        $records = new P4Cms_Model_Iterator;
        foreach ($files as $file) {
            $record = static::fromP4File($file, null, $adapter);
            $records[$record->getId()] = $record;
        }

        return $records;
    }

    /**
     * Count all records matching the given query.
     *
     * @param   P4Cms_Record_Query|array|null   $query      optional - query options to augment result.
     * @param   P4Cms_Record_Adapter            $adapter    optional - storage adapter to use.
     * @return  integer                         The count of all matching records
     */
    public static function count(
        P4Cms_Record_Query   $query   = null,
        P4Cms_Record_Adapter $adapter = null)
    {
        $query = static::_normalizeQuery($query);

        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        // convert record query to a p4 file query.
        $query = $query->toFileQuery(get_called_class(), $adapter);

        // early exit if no filespecs in query, return zero.
        if (is_array($query->getFilespecs()) && !count($query->getFilespecs())) {
            return 0;
        }

        // only fetch a single field - use headRev because it's tiny.
        $query->setLimitFields('headRev');

        // fetch count from perforce.
        return P4_File::count($query, $adapter->getConnection());
    }

    /**
     * Save this record. If the record does not have an id, a new
     * UUID will be assigned to identify the record.
     *
     * @param   string              $description    optional - a description of the change.
     * @param   null|string|array   $options        optional - passing the SAVE_THROW_CONFLICTS
     *                                              flag will cause exceptions on conflict; default
     *                                              behaviour is to crush any conflicts.
     *                                              Note this flag has no effect in batches.
     * @return  P4Cms_Record        provides a fluent interface
     */
    public function save($description = null, $options = null)
    {
        // if we are in a batch, pend the record to the
        // changelist identified by the batch id.
        $adapter = $this->getAdapter();
        $change  = ($adapter->inBatch()) ? $adapter->getBatchId() : null;

        // if this record has an id, attempt to flush and edit the file.
        // if it has no id, generate a new UUID to identify the record.
        if ($this->getId()) {
            $file = $this->_getP4File();
            try {
                // if our file isn't deleted simply attempt to sync and edit.
                // perforce doesn't let you edit a deleted revision (you can't
                // 'have' a deleted revision) so if it is deleted, sync to the
                // previous revision and attempt to edit that.
                if (!$file->isDeleted()) {
                    $file->sync();
                    $file->edit($change);
                } else {
                    // if we are deleted, sync to the previous revision
                    // we create a new file object because we want to sync/edit
                    // the previous version without changing this record object's
                    // file instance (which would have negative side-effects).
                    $revSpec      = '#' . ((int)$file->getStatus('headRev') - 1);
                    $previousFile = P4_File::fetch(
                        $file->getFilespec(true) . $revSpec,
                        $file->getConnection()
                    );
                    $previousFile->sync();

                    // attempt to open for edit. if the file is deleted at the head
                    // revision this will fail and we will open for add later.
                    $previousFile->edit($change);

                    // clear file's status cache so it can be aware of changes made
                    // by previousFile (e.g. 'isOpened' check will be acurate)
                    $file->clearStatusCache();
                }
            } catch (P4_File_Exception $e) {
                // edit failed, but that's ok - we'll attempt to add below.
            } catch (P4_Connection_CommandException $e) {
                // if command failed due to a chmod error, just eat the exception;
                // file will get created later. normally this problem should not
                // occur, but if a virtual integrate or copy was performed, it can.
                if (!stripos($e->getMessage(), "Command failed: chmod: ") === 0) {
                    throw $e;
                }
            }
        } else {
            $this->setId((string) new P4Cms_Uuid);
            $file = $this->_getP4File();
        }

        // write file content field to file contents.
        // if we don't have a file content field we
        // simply touch the file to ensure it's on disk
        if (static::hasFileContentField()) {
            $field = static::getFileContentField();

            // we avoid reading the file into memory if possible
            // but there are situations where we have to:
            // - if this is an add write the value to persist the default
            // - if this is an edit the file should already exist but if its missing
            //   make a go of reading its current value and writing it back out
            // - lastly if we have a value in memory we need to write it to persist it
            if (!$file->isOpened()
                || !file_exists($file->getLocalFilename())
                || array_key_exists($field, $this->_values)
            ) {
                $value = $this->_encodeFieldValue($field, $this->_getValue($field));
                $file->setLocalContents($value);
            }
        } else {
            $file->touchLocalFile();
        }

        // if file is not yet opened, add it now - we do this after
        // the file is written so perforce can detect the file type.
        if (!$file->isOpened()) {
            $file->add($change);
        }

        // write field values and metadata as file attributes.
        // we clear any attributes we don't know about (ie. the field was
        // explicitly unset, or this record was not fetched, but happens
        // to collide with a file in perforce that has attributes)
        $clear      = array();
        $ignore     = array();
        $attributes = array();

        // collect field values to set as attributes.
        foreach ($this->getFields() as $field) {
            if ($field != static::$_idField && $field != static::$_fileContentField) {
                $attributes[$field] = $this->_encodeFieldValue($field, $this->_getValue($field));
            }
        }

        // collect metadata to set or ignore -- if we were unable to decode
        // certain metadata when reading it, we set it to false to indicate it
        // should be left alone (could be third-party data for example)
        foreach ($this->_metadata as $field => $data) {
            $field = "_" . $field;
            if (!empty($data)) {
                $attributes[$field] = $this->_encodeMetadata($data);
            } else if ($data === false) {
                $ignore[] = $field;
            }
        }

        // determine the fields to clear - as above, we clear any attributes
        // we don't know about so long as they aren't listed as ignored.
        foreach ($file->getAttributes() as $key => $value) {
            if (!array_key_exists($key, $attributes) && !in_array($key, $ignore)) {
                $clear[] = $key;
            }
        }

        $file->setAttributes($attributes);
        $file->clearAttributes($clear);

        // if we're not in a batch, submit file to perforce
        if (!$adapter->inBatch()) {
            if (!$description) {
                $description = $this->_generateSubmitDescription();
            }

            // default option is to 'accept yours' but we switch to
            // null if SAVE_THROW_CONFLICTS flag is passed.
            $resolveFlag = P4_File::RESOLVE_ACCEPT_YOURS;
            if (in_array(static::SAVE_THROW_CONFLICT, (array)$options)) {
                $resolveFlag = null;
            }

            $file->submit($description, $resolveFlag);
        }

        return $this;
    }

    /**
     * Store a record. Equivalent to the instance method save(), but offered
     * as a static method for convenience.
     *
     * @param   array|string|null       $values     optional - list of values for the new record
     *                                              if a string is given, it will be taken as the
     *                                              record identifier - if no id given, a new
     *                                              UUID will be assigned.
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  P4Cms_Record                        provides a fluent interface.
     */
    public static function store($values = array(), P4Cms_Record_Adapter $adapter = null)
    {
        // normalize values to an array.
        if (!is_array($values)) {
            $values = array(static::$_idField => $values);
        }

        $record = static::create($values, $adapter);
        $record->save();

        return $record;
    }

    /**
     * Delete this record.
     *
     * @param   string  $description  optional - a description of the change.
     * @return  P4Cms_Record          provides fluent interface.
     */
    public function delete($description = null)
    {
        // if we are in a batch, pend the record to the
        // changelist identified by the batch id.
        $adapter = $this->getAdapter();
        $change  = ($adapter->inBatch()) ? $adapter->getBatchId() : null;

        // open depot file for delete.
        $file = $this->_getP4File();
        try {
            $file->delete($change);
        } catch (P4_File_Exception $e) {
            // ignore exception if file was open for add - otherwise rethrow.
            if (!$file->isOpened() || $file->getStatus('action') !== 'add') {
                throw $e;
            }
        }

        // ensure local file deleted.
        if (file_exists($file->getLocalFilename())) {
            $file->deleteLocalFile();
        }

        // if we're not in a batch, submit file to perforce
        if (!$adapter->inBatch()) {
            if (!$description) {
                $description = "Deleted '" . static::$_storageSubPath . "' record.";
            }
            $file->submit($description);
        }

        return $this;
    }

    /**
     * Remove a record from storage. Equivalent to delete but class-based for convenience.
     *
     * @param   string                  $id         the id of the record to remove.
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  P4Cms_Record            provides fluent interface.
     */
    public static function remove($id, P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        $record = new static;
        $record->setId($id)
               ->setAdapter($adapter)
               ->delete();

        return $record;
    }

    /**
     * Override parent to clear associated p4 file if adapter has changed to ensure the file
     * will get the connection from the new adapter.
     *
     * @param   P4Cms_Record_Adapter    $adapter    the adapter to use for this instance.
     * @return  P4Cms_Record            provides fluent interface.
     */
    public function setAdapter(P4Cms_Record_Adapter $adapter)
    {
        // if adapter has changed, clear associated p4 file.
        if ($adapter !== $this->_adapter) {
            $this->_p4File = null;
        }

        return parent::setAdapter($adapter);
    }

    /**
     * Get the Perforce path used for the storage of this class of records.
     * The storage path is a combination of the records path (provided by the record
     * storage adapter) and the sub-path (defined by the record class).
     *
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  string  the path used to store this class of records.
     */
    public static function getStoragePath(P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        // normalize the path components.
        $basePath = rtrim($adapter->getBasePath(), '/');
        $subPath  = rtrim(static::$_storageSubPath, '/');

        // return basePath w. subPath (if set).
        return strlen($subPath) ? $basePath . '/' . $subPath : $basePath;
    }

    /**
     * Determine if this record class has a field mapped to the file contents.
     *
     * @return  bool    true if the class has a file content field; false otherwise.
     */
    public static function hasFileContentField()
    {
        return isset(static::$_fileContentField);
    }

    /**
     * Get the name of the field that is mapped to the file contents.
     *
     * @return  string                  the name of the file content field.
     * @throws  P4Cms_Record_Exception  if there is no file content field.
     */
    public static function getFileContentField()
    {
        if (!static::hasFileContentField()) {
            throw new P4Cms_Record_Exception(
                "Cannot get the file content field. No field is mapped to the file."
            );
        }

        return static::$_fileContentField;
    }

    /**
     * Get metadata for the given field.
     *
     * Field metadata is stored in a file attribute named for the
     * field, but with a leading underscore (e.g. '_field-name').
     *
     * @param   string  $field          the field to get metadata for.
     * @return  array                   the metadata for the given field.
     * @throws  P4Cms_Record_Exception  if the field does not exist.
     */
    public function getFieldMetadata($field)
    {
        // populate but skip getting the file contents at this point
        $this->_populate(true);

        if (!$this->hasField($field)) {
            throw new P4Cms_Record_Exception(
                "Cannot get field metadata for a non-existant field."
            );
        }

        return $this->_getFieldMetadata($field);
    }

    /**
     * Set metadata for the given field.
     *
     * Field metadata is stored in a file attribute named for the
     * field, but with a leading underscore (e.g. '_field-name').
     *
     * @param   string          $field  the field to set metadata for.
     * @param   array|null      $data   the metadata to store for the field.
     * @return  P4Cms_Record            provides fluent interface.
     * @throws  P4Cms_Record_Exception  if the field does not exist.
     */
    public function setFieldMetadata($field, array $data = null)
    {
        if (!$this->hasField($field)) {
            throw new P4Cms_Record_Exception(
                "Cannot set field metadata for a non-existant field."
            );
        }

        $this->_metadata[$field] = $data;

        return $this;
    }

    /**
     * Test if this record is deleted in perforce.
     *
     * @return  boolean     true if record is deleted or doesn't have an id,
     *                      otherwise returns true.
     */
    public function isDeleted()
    {
        return $this->getId() && $this->_getP4File()->isDeleted();
    }

    /**
     * Provides access to a copy of the p4_file object which is underlying the
     * current record instance. By default it returns a cloned copy, pass true
     * to get a reference.
     *
     * @param   bool    $reference  optional - pass true to get a reference to the file
     * @return  P4_File             the file associated with this record instance.
     */
    public function toP4File($reference = false)
    {
        return $reference
            ? $this->_getP4File()
            : clone $this->_getP4File();
    }

    /**
     * Given a p4 file instance, produce a record instance with id
     * adapter and associated p4 file object all set appropriately.
     *
     * Under normal operation a reference is maintained to the given
     * file and the id of the record is derived from the filespec.
     * If the import option is specified, only the values are taken
     * from the file. No reference is maintained and the resulting
     * record will have a null id.
     *
     * @param   P4_File                 $file       a p4 file instance to convert into a record.
     * @param   string|array|null       $options    options to influence the operation:
     *                                                FROM_FILE_IMPORT - only the file's values are
     *                                                                   used, the id is ignored
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  P4Cms_Record            the record instance generated from the file.
     */
    public static function fromP4File($file, $options = null, P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $import  = in_array(static::FROM_FILE_IMPORT, (array)$options);
        $adapter = $adapter ?: static::getDefaultAdapter();
        $id      = $import ? null : static::depotFileToId($file->getDepotFilename(), $adapter);

        $record = new static();
        $record->setId($id)
               ->setAdapter($adapter)
               ->_setP4File($file)
               ->_deferPopulate();

        // if we are doing an import force the record to read in
        // values then clear any reference to the passed file.
        if ($import) {
            $record->_populate()
                   ->_setP4File(null);
        }

        return $record;
    }

    /**
     * Get the depot-syntax form of the perforce path used for the storage of
     * this class of records. The path returned by getStoragePath() is in an
     * unknown form. It could be in depot, client or local file-system syntax.
     *
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  string  the depot path used to store this class of records.
     */
    public static function getDepotStoragePath(P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        // get the storage path (in unknown form).
        $storagePath = static::getStoragePath($adapter);

        // we cache the depot-syntax version on a per-path, per-adapter basis.
        // to avoid running 'p4 where' everytime we need to get the depot storage path.
        if (isset(static::$_whereCache[spl_object_hash($adapter)][$storagePath])) {
            return static::$_whereCache[spl_object_hash($adapter)][$storagePath];
        }

        // convert to depot-syntax.
        $result = $adapter->getConnection()->run('where', $storagePath . '/...');
        if ($result->hasWarnings()) {
            throw new P4Cms_Record_Exception(
                "Cannot get the depot storage path. Storage path is not in client view."
            );
        }
        $depotPath = substr($result->getData(0, 'depotFile'), 0, -4);

        // cache per adapter/path.
        static::$_whereCache[spl_object_hash($adapter)][$storagePath] = $depotPath;

        return $depotPath;
    }

    /**
     * Given a record id, determine the corresponding filespec.
     *
     * @param   string                  $id         the record id to get the filespec for.
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  string                  the filespec for a given record id.
     */
    public static function idToFilespec($id, P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        // id is required.
        if (!strlen($id)) {
            throw new InvalidArgumentException("Cannot get filespec for an empty id.");
        }

        // optionally encode id for storage.
        if (static::$_encodeIds) {
            $id = static::_encodeId($id);
        }

        return static::getStoragePath($adapter) . '/' . $id;
    }

    /**
     * Return name of the id field.
     *
     * @return  string  name of id field.
     */
    public static function getIdField()
    {
        return static::$_idField;
    }

    /**
     * Given a record filespec in depotFile syntax, determine the id.
     *
     * @param   string                  $depotFile  a record depotFile.
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  string|int  the id portion of the depotFile file spec.
     */
    public static function depotFileToId(
        $depotFile,
        P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        // strip the depot storage path from the depotFile to produce the id.
        $depotBasePath = static::getDepotStoragePath($adapter) . '/';
        if (strpos($depotFile, $depotBasePath) === 0) {
            $id = substr($depotFile, strlen($depotBasePath));

            // optionally decode stored id.
            if (static::$_encodeIds) {
                $id = static::_decodeId($id);
            }

        } else {
            throw new P4Cms_Record_Exception(
                "Cannot determine record id for a file outside of the record storage path."
            );
        }

        return $id;
    }

    /**
     * Set the corresponding P4 File object instance.
     * Used when fetching records to prime the record object.
     *
     * @param   P4_File|null          $file  the corresponding P4_File object.
     * @return  P4Cms_Record          provides fluent interface.
     * @throws  Record_Exception      if the file is not a valid P4_File object.
     */
    protected function _setP4File($file)
    {
        if (!$file instanceof P4_File && !is_null($file)) {
            throw new P4Cms_Record_Exception(
                'Cannot set P4 File. The given file is not a valid P4_File object.'
            );
        }

        $this->_p4File = $file;
        return $this;
    }

    /**
     * Get the P4 File object that corresponds to this record.
     *
     * @return  P4_File  corresponding P4 File instance.
     */
    protected function _getP4File()
    {
        // create corresponding p4 file instance if necessary.
        if (!$this->_p4File instanceof P4_File) {
            $filespec       = static::idToFilespec($this->getId(), $this->getAdapter());
            $this->_p4File  = new P4_File;
            $this->_p4File->setFilespec($filespec)
                          ->setConnection($this->getAdapter()->getConnection());
        }

        return $this->_p4File;
    }

    /**
     * Encode metadata for storage (using JSON).
     *
     * @param   mixed   $data   The data to be encoded.
     * @return  string  The 'encoded' data.
     */
    protected function _encodeMetadata($data)
    {
        return Zend_Json::encode($data);
    }

    /**
     * Decode metadata (presumably from storage).
     *
     * @param   string  $data  The data to be decoded.
     * @return  mixed   The 'decoded' data.
     */
    protected function _decodeMetadata($data)
    {
        return strlen($data)
            ? Zend_Json::decode($data)
            : null;
    }

    /**
     * Encode field's value as JSON if its not string or numeric.
     * Updates field metadata to record encoding.
     *
     * @param   string  $field  the field to encode the value for.
     * @param   mixed   $value  the value to encode.
     * @return  string  the encoded value.
     */
    protected function _encodeFieldValue($field, $value)
    {
        $metadata = $this->_getFieldMetadata($field);
        if (is_numeric($value)) {
            $value = (string) $value;
        }
        if (isset($value) && !is_string($value)) {

            // json encode
            $value = Zend_Json::encode($value);
            $metadata[self::ENCODING_METADATA_KEY] = self::ENCODING_FORMAT_JSON;

        } else if (array_key_exists(self::ENCODING_METADATA_KEY, $metadata)) {
            unset($metadata[self::ENCODING_METADATA_KEY]);
        }

        $this->setFieldMetadata($field, $metadata);
        return $value;
    }

    /**
     * Decode field's value if it is encoded (checks field metadata).
     *
     * @param   string  $field  the field we are decoding
     * @param   string  $value  the encoded value.
     * @return  mixed   the decoded value (could be string or array).
     */
    protected function _decodeFieldValue($field, $value)
    {
        $metadata = $this->_getFieldMetadata($field);
        if (strlen($value)
            && isset($metadata[self::ENCODING_METADATA_KEY])
            && self::ENCODING_FORMAT_JSON === $metadata[self::ENCODING_METADATA_KEY]
        ) {
            try {
                return Zend_Json::decode($value);
            } catch (Exception $e) {
                P4Cms_Log::logException("Failed to decode field value", $e);
            }
        }

        // convert empty strings to null.
        // this is done so that null values round-trip correctly
        // this prevents empty strings from round-tripping, but
        // that was deemed a reasonable trade-off.
        if (!strlen($value)) {
            return null;
        }

        return $value;
    }

    /**
     * Overrides parent to populate the record first.
     * Get a raw (but decoded) field value. Does not use custom accessor methods.
     * If idField is specified; will utilize 'getId' function.
     *
     * @param   string  $field  the name of the field to get the value of.
     * @return  mixed   the value of the field.
     * @throws  P4Cms_Model_Exception   if the field does not exist.
     */
    protected function _getValue($field)
    {
        $excludeFile = ($field !== static::$_fileContentField);
        $this->_populate($excludeFile);

        return parent::_getValue($field);
    }

    /**
     * Schedule populate to run when data is requested (lazy-load).
     *
     * @return  P4Cms_Record    provides fluent interface.
     */
    protected function _deferPopulate()
    {
        $this->_needsPopulate     = true;
        $this->_needsFilePopulate = true;

        return $this;
    }

    /**
     * Get the values for this record from Perforce and set them
     * in the instance. Won't clobber existing values.
     *
     * @param   bool    $excludeFile    optional - skip populating file content
     * @return  P4Cms_Record            provides fluent interface.
     */
    protected function _populate($excludeFile = false)
    {
        // if record has no id and no file, we can't pull from storage.
        if (!$this->hasId() && !$this->_p4File) {
            return $this;
        }

        if ($this->_needsPopulate) {
            // clear needsPopulate flag.
            $this->_needsPopulate = false;

            // get file attributes from associated p4 file.
            $file = $this->_getP4File();
            try {
                $attributes = $file->getAttributes();
            } catch (P4_File_Exception $e) {
                // no matching file in storage, nothing to populate from.
                return $this;
            }

            // set field metadata first from file attributes.
            foreach ($attributes as $key => $value) {
                if ($key[0] === '_') {
                    $field = substr($key, 1);
                    if (!array_key_exists($field, $this->_metadata)) {
                        try {
                            $this->_metadata[$field] = $this->_decodeMetadata($value);
                        } catch (Exception $e) {
                            // we failed to decode the metadata entry -- we set it to
                            // false to tell save that the attribute should be ignored.
                            $this->_metadata[$field] = false;
                        }
                    }
                }
            }

            // set field values from file attributes.
            $validator = new P4Cms_Validate_RecordField;
            foreach ($attributes as $key => $value) {
                if ($validator->isValid($key)) {
                    if (!array_key_exists($key, $this->_values)) {
                        $this->_values[$key] = $this->_decodeFieldValue($key, $value);
                    }
                }
            }
        }

        if ($this->_needsFilePopulate && !$excludeFile) {
            // clear needsPopulate flag.
            $this->_needsFilePopulate = false;

            // set file content field if record has one.
            $fileField = static::$_fileContentField;
            if (strlen($fileField) && !array_key_exists($fileField, $this->_values)) {
                $file = $this->_getP4File();
                try {
                    if (!$file->isDeleted()) {
                        $contents = $file->getDepotContents();
                    } else {
                        // if we are deleted, pull the file content from the previous revision
                        $revSpec  = '#' . ((int)$file->getStatus('headRev') - 1);
                        $contents = P4_File::fetch(
                            $file->getFilespec(true) . $revSpec,
                            $file->getConnection()
                        )->getDepotContents();
                    }

                    $this->_values[$fileField] = $this->_decodeFieldValue($fileField, $contents);
                } catch (P4_File_Exception $e) {
                    // presumably no depot file content to get.
                }
            }
        }

        return $this;
    }

    /**
     * Get metadata for the given field - doesn't populate or check field existance.
     *
     * Field metadata is stored in a file attribute named for the
     * field, but with a leading underscore (e.g. '_field-name').
     *
     * @param   string  $field          the field to get metadata for.
     * @return  array                   the metadata for the given field.
     */
    protected function _getFieldMetadata($field)
    {
        if (array_key_exists($field, $this->_metadata)) {
            return (array) $this->_metadata[$field];
        } else {
            return array();
        }
    }

    /**
     * Encode id for storage (via bin2hex).
     *
     * @param   string  $id     the id to encode.
     * @return  string  the encoded id.
     */
    protected function _encodeId($id)
    {
        return bin2hex($id);
    }

    /**
     * Decode stored id (reverse bin2hex).
     *
     * @param   string  $id     the id to decode.
     * @return  string  the decoded id.
     */
    protected function _decodeId($id)
    {
        return pack("H*", $id);
    }

    /**
     * Generate a save description for this record.
     *
     * @return  string  a default submit description.
     */
    protected function _generateSubmitDescription()
    {
        return static::$_storageSubPath
            ? "Saved '" . static::$_storageSubPath . "' record."
            : "Saved record.";
    }

    /**
     * Queries arguments (e.g. to fetch/fetchAll) can be given as a
     * query object, an array or null. This helper method normalizes
     * the input to a query object and throws on invalid arguments.
     *
     * @param   P4Cms_Record_Query|array|null   $query  optional - query options to augment result.     *
     * @return  P4Cms_Record_Query              the query input normalized to a query object.
     * @throws  InvalidArgumentException        if the query input is not valid.
     */
    protected static function _normalizeQuery($query)
    {
        if (!$query instanceof P4Cms_Record_Query && !is_array($query) && !is_null($query)) {
            throw new InvalidArgumentException(
                'Query must be a P4Cms_Record_Query, array or null'
            );
        }

        // normalize array input to a query
        if (is_array($query)) {
            $query = new P4Cms_Record_Query($query);
        }

        // if null query given, make a new one.
        $query = $query ?: new P4Cms_Record_Query;

        return $query;
    }
}
