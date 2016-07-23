<?php
/**
 * Abstracts operations against Perforce files.
 *
 * THEORY OF OPERATION
 *
 * Unlike a typical database, all changes to Perforce files must be pended to
 * the current client workspace before they can be commited.
 *
 * The file model provides access to two copies of file data: the submitted
 * depot copy and the client workspace copy. When you are accessing file data
 * (be it file contents or file attributes), you must consider which of these
 * sources you want to get the data from.
 *
 * For example, if you call getDepotContents() you will get the submitted depot
 * copy of the file; whereas, if you call getLocalContents() you will get the
 * contents of the client workspace file.
 *
 * The class attempts to faithfully represent the behavior of Perforce. There
 * is, however, some simplification at work. In particular, the open() method
 * will automatically add or edit a file as appropriate. It will also sync the
 * file to the client if necessary.
 *
 * Similarly, if a file is open for delete, the add, edit and open methods will
 * revert the file and reopen it. Conversely, if delete() is called on a file
 * that is opened (but not for delete), the file will be reverted and then
 * deleted(). To suppress this behavior, pass false as the force option.
 *
 *
 * COMMON USAGE
 *
 * To fetch a file from Perforce, call fetch() and pass the filespec of the
 * file you wish to retrieve. For example:
 *
 *  $file = P4_File::fetch('//depot/file');
 *
 * To fetch several files, call fetchAll() and pass a file query object
 * representing the fstat options that you wish to use. For example:
 *
 *  $files = P4_File::fetchAll(
 *      new P4_File_Query(array('filespecs' => '//depot/path/...'))
 *  );
 *
 * The query class also has options to filter, sort and limit files. See the
 * P4_File_Query class for additional details.
 *
 * To submit a file:
 *
 *   $file = new P4_File;
 *   $file->setFilespec('//depot/file');
 *   $file->open();
 *   $file->setLocalContents('new file content');
 *   $file->submit('Description of change');
 *
 * To delete a file:
 *
 *   $file->delete();
 *   $file->submit('Description of change');
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        make fluent.
 * @todo        give submit a clobber option.
 * @todo
 *   diff($file)
 *   getRevisions()
 *   getFixes()
 *   integrate()
 *   getIntegrations()
 *   getInterchanges()
 *   getLabels()
 *   getProtections()
 *   move()
 *   getReviewers()
 *   getSize()
 *   tag($rev)
 *   untag($rev)
 */
class P4_File extends P4_ModelAbstract implements P4_ResolvableInterface
{
    const       ALL_FILES           = '//...';
    const       REVERT_UNCHANGED    = 'unchanged';

    protected   $_cache             = array();
    protected   $_filespec          = null;

    /**
     * Set the filespec identifier for the file/revision.
     * Filespec may be given in depot, client or local file-system
     * syntax. The filename may be followed by a revision specifier.
     * Wildcards are not permitted in the filespec.
     *
     * For more information on filespecs visit:
     * http://perforce.com/perforce/doc.current/manuals/cmdref/o.fspecs.html
     *
     * Note: The instance cache is cleared when the filespec changes.
     *
     * @param   string  $filespec   the filespec of the file.
     * @return  P4_File provide fluent interface.
     */
    public function setFilespec($filespec)
    {
        static::_validateFilespec($filespec);
        $this->_filespec = $filespec;

        // identity has changed - clear all of the instance caches.
        $this->_cache = array();

        return $this;
    }

    /**
     * Get the filespec used to identify this file.
     * If a revision specifier was passed to setFilespec or fetch, it
     * will be returned here; otherwise, no revision specifier will
     * be present.
     *
     * @param   bool    $stripRevspec   optional - revspecs will be removed, if present, when true
     * @return  string  the filespec of the file.
     */
    public function getFilespec($stripRevspec = false)
    {
        return $stripRevspec ? static::stripRevspec($this->_filespec) : $this->_filespec;
    }

    /**
     * Get the filespec used to identify this file including
     * a revision specification if one is known.
     *
     * If getFilespec includes a revspec, this value is used.
     * Otherwise, if we have fetched file contents or status
     * the corresponding numeric revision is used.
     *
     * @return  string  the filespec with a revision specifier if one is known.
     */
    public function getFilespecWithRevision()
    {
        $filespec = $this->getFilespec();

        if ($filespec === null || static::hasRevspec($filespec)) {
            return $filespec;
        }

        $revision = '';
        if (isset($this->_cache['revision'])) {
            $revision = '#' . $this->_cache['revision'];
        }

        return $this->_filespec . $revision;
    }

    /**
     * Fetch a model of the given filespec.
     *
     * @param   string  $filespec       a filespec with no wildcards - the filespec may
     *                                  be in any one of depot, client or local file syntax.
     * @param   P4_Connection_Interface $connection  optional - a specific connection to use.
     * @todo    throw a specialized file not found exception if the file does not exist.
     */
    public static function fetch($filespec, P4_Connection_Interface $connection = null)
    {
        // if no connection given, use default.
        $connection = $connection ?: static::getDefaultConnection();

        // determine whether the file exists.
        $rev = self::exists($filespec, $connection);
        if ($rev === false) {
            throw new P4_File_NotFoundException(
                "Cannot fetch file '$filespec'. File does not exist."
            );
        }

        // create new file instance and set the key.
        $file = new static($connection);
        $file->setFilespec($filespec);
        $file->_cache['revision'] = $rev;
        return $file;
    }

    /**
     * Fetch all files matching the given query.
     *
     * @param   P4_File_Query|array         $query          A query object or array expressing fstat options.
     * @param   P4_Connection_Interface     $connection     optional - a specific connection to use.
     * @return  P4_Model_Iterator           List of retrieved files.
     * @throws  InvalidArgumentException    if no filespec is given.
     */
    public static function fetchAll($query, P4_Connection_Interface $connection = null)
    {
        if (!$query instanceof P4_File_Query && !is_array($query)) {
            throw new InvalidArgumentException(
                'Query must be a P4_File_Query or array.'
            );
        }

        // normalize array input to a query
        if (is_array($query)) {
            $query = new P4_File_Query($query);
        }

        // ensure caller provided a filespec.
        if (!count($query->getFilespecs())) {
            throw new InvalidArgumentException(
                'Cannot fetch files. No filespecs provided in query.'
            );
        }

        // if no connection given, use default.
        $connection = $connection ?: static::getDefaultConnection();

        // get fstat flags for given query options and run fstat command.
        $flags  = array_merge($query->getFstatFlags(), $query->getFilespecs());

        // try/catch parent to deal with the exception we get on non-existend depots
        try {
            $result = $connection->run('fstat', $flags);
        } catch (P4_Connection_CommandException $e) {
            // if the 'depot' has been interpreted as an invalid client, just return no matches
            if (preg_match("/Command failed: .+ - must refer to client/", $e->getMessage())) {
                return new P4_Model_Iterator;
            }

            // unexpected error; rethrow it
            throw $e;
        }

        // if fetching by change, the last block of data contains
        // the change description - remove it (unless we're fetching
        // from the default changelist)
        $dataBlocks = $result->getData();
        if ($query->getLimitToChangelist() !== null
            && $query->getLimitToChangelist() !== P4_Change::DEFAULT_CHANGE) {
            array_pop($dataBlocks);
        }

        // generate file models from fstat output.
        $files = new P4_Model_Iterator;
        foreach ($dataBlocks as $data) {
            $file = new static($connection);
            $file->setFilespec($data['depotFile']);
            $file->setStatusCache($data);

            $files[] = $file;
        }

        return $files;
    }

    /**
     * Count files matching the given query.
     * This is a faster alternative to counting the result of fetchAll().
     *
     * @param   P4_File_Query|array         $query          A query object or array expressing fstat options.
     * @param   P4_Connection_Interface     $connection     optional - a specific connection to use.
     * @return  P4_Model_Iterator           count of matching files.
     * @todo    optimize to only fetch a single field per file.
     */
    public static function count($query, P4_Connection_Interface $connection = null)
    {
        if (!$query instanceof P4_File_Query && !is_array($query)) {
            throw new InvalidArgumentException(
                'Query must be a P4_File_Query or array.'
            );
        }

        // normalize array input to a query
        if (is_array($query)) {
            $query = new P4_File_Query($query);
        }

        // ensure caller provided a filespec.
        if (!count($query->getFilespecs())) {
            throw new InvalidArgumentException(
                'Cannot count files. No filespecs provided in query.'
            );
        }

        // if no connection given, use default.
        $connection = $connection ?: static::getDefaultConnection();

        // remove options that cause unnecessary work for the server
        $query = clone $query;
        $query->setSortBy(null)->setReverseOrder(false);

        // only fetch a single field for performance.
        $query->setLimitFields('depotFile');

        // get fstat flags for given query and run fstat command.
        $flags  = array_merge($query->getFstatFlags(), $query->getFilespecs());
        $result = $connection->run('fstat', $flags);
        $count  = count($result->getData());

        // if fetching by change, the last block of data contains
        // the change description - remove it (unless we're fetching
        // from the default changelist)
        if ($query->getLimitToChangelist() !== null
            && $query->getLimitToChangelist() !== P4_Change::DEFAULT_CHANGE
        ) {
            $count--;
        }

        return $count;
    }

    /**
     * Check if the given filespec is known to Perforce.
     *
     * @param   string                  $filespec           a filespec with no wildcards.
     * @param   P4_Connection_Interface $connection         optional - a specific connection to use.
     * @param   bool                    $excludeDeleted     optional - exclude deleted files (defaults to false).
     * @return  bool|int                head revision number or false if filespec doesn't exist
     */
    public static function exists($filespec, P4_Connection_Interface $connection = null, $excludeDeleted = false)
    {
        static::_validateFilespec($filespec);

        // if no connection given, use default.
        $connection = $connection ?: static::getDefaultConnection();

        // run files to see if file exists.
        $result = $connection->run('files', $filespec);
        if ($result->hasWarnings()) {
            return false;
        } elseif ($excludeDeleted && strstr($result->getData(0, 'action'), 'delete') !== false) {
            return false;
        } else {
            $rev = $result->getData(0, 'rev');

            // this really shouldn't happen; just being defensive
            if ($rev === false) {
                throw new P4_File_Exception('Failed to capture revision during existance test');
            }

            return $rev;
        }
    }

    /**
     * Open file for add or edit as appropriate.
     *
     * If the file is open for delete, revert and edit unless force=false.
     * Will sync the file before opening it for edit.
     *
     * @param   int     $change     optional - a numbered pending change to open the file in.
     * @param   string  $fileType   optional - the file-type to open the file as.
     * @param   bool    $force      optional - defaults to true - reverts files that are
     *                              open for delete then reopens them. if false, files that are
     *                              open for delete will result in an exception being thrown.
     * @return  P4_File provide fluent interface.
     */
    public function open($change = null, $fileType = null, $force = true)
    {
        // verify we have a filespec set; throws if invalid/missing
        $this->_validateHasFilespec();

        // add the file if it doesn't exist or is deleted at head - otherwise edit.
        if (!static::exists($this->getFilespecWithRevision(), $this->getConnection()) ||
            $this->getStatus('headAction') == 'delete') {
            $this->add($change, $fileType);
        } else {
            $this->sync(true);
            $this->edit($change, $fileType, $force);
        }

        return $this;
    }

    /**
     * Open this file for delete.
     *
     * If the file is open, but not for delete, the file will be
     * reverted and then deleted unless the force flag has been
     * set to false.
     *
     * @param   int     $change     optional - a numbered pending change to open the file in.
     * @param   bool    $force      optional - defaults to true - reverts files that are
     *                              open then deletes them. if false, files that are
     *                              open (not for delete) will result in an exception
     *                              being thrown.
     * @return  P4_File provide fluent interface.
     */
    public function delete($change = null, $force = true)
    {
        return $this->_openForAction('delete', $change, null, $force);
    }

    /**
     * Delete the local file from the workspace.
     *
     * @throws  P4_File_Exception  if the local file cannot be deleted.
     * @return  P4_File provide fluent interface.
     */
    public function deleteLocalFile()
    {
        $localFile = $this->getLocalFilename();
        if (!file_exists($localFile)) {
            throw new P4_File_Exception("Cannot delete local file. File does not exist.");
        }
        chmod($localFile, 0777);
        if (unlink($localFile) === false) {
            throw new P4_File_Exception("Failed to delete local file.");
        }

        return $this;
    }

    /**
     * Open the file for add.
     *
     * @param   int     $change     optional - a numbered pending change to open the file in.
     * @param   string  $fileType   optional - the file-type to open the file as.
     * @return  P4_File provides fluent interface.
     */
    public function add($change = null, $fileType = null)
    {
        return $this->_openForAction('add', $change, $fileType, false);
    }

    /**
     * Open the file for edit.
     *
     * If the file is opened for delete, the file will be reverted
     * and then edited unless the force flag has been set to false.
     *
     * @param   int     $change     optional - a numbered pending change to open the file in.
     * @param   string  $fileType   optional - the file-type to open the file as.
     * @param   bool    $force      optional - defaults to true - set to false to avoid reopening.
     * @return  P4_File provide fluent interface.
     * @todo    make force work against branch/delete, etc.
     */
    public function edit($change = null, $fileType = null, $force = true)
    {
        // If our 'have' rev and our 'head' revision aren't the
        // same value throw an exception (caller needs to sync).
        if (!$this->hasStatusField('haveRev')
            || $this->getStatus('headRev') != $this->getStatus('haveRev')
        ) {
            throw new P4_File_Exception(
                'Workspace file is not at specified revision; unable to edit'
            );
        }

        return $this->_openForAction('edit', $change, $fileType, $force);
    }

    /**
     * Flush the file - tells the server we have the file.
     *
     * @return  P4_File             provide fluent interface.
     * @throws  P4_File_Exception   if the flush fails.
     */
    public function flush()
    {
        return $this->sync(false, true);
    }

    /**
     * Resolves the file based on the passed option(s).
     *
     * You must specify one of the below:
     *  RESOLVE_ACCEPT_MERGED
     *   Automatically accept the Perforce-recom mended file revision:
     *   if theirs is identical to base, accept yours; if yours is identical
     *   to base, accept theirs; if yours and theirs are different from base,
     *   and there are no conflicts between yours and theirs; accept merge;
     *   other wise, there are conflicts between yours and theirs, so skip this file.
     *  RESOLVE_ACCEPT_YOURS
     *   Accept Yours, ignore theirs.
     *  RESOLVE_ACCEPT_THEIRS
     *   Accept Theirs. Use this flag with caution!
     *  RESOLVE_ACCEPT_SAFE
     *   Safe Accept. If either yours or theirs is different from base,
     *   (and the changes are in common) accept that revision. If both
     *   are different from base, skip this file.
     *  RESOLVE_ACCEPT_FORCE
     *   Force Accept. Accept the merge file no matter what. If the merge file
     *   has conflict markers, they will be left in, and you'll need to remove
     *   them by editing the file.
     *
     * Additionally, one of the following whitespace options can, optionally, be passed:
     *  IGNORE_WHITESPACE_CHANGES
     *   Ignore whitespace-only changes (for instance, a tab replaced by eight spaces)
     *  IGNORE_WHITESPACE
     *   Ignore whitespace altogether (for instance, deletion of tabs or other whitespace)
     *  IGNORE_LINE_ENDINGS
     *   Ignore differences in line-ending convention
     *
     * @param   array|string    $options    Resolve option(s); must include a RESOLVE_* preference.
     * @return  P4_File         provide fluent interface.
     * @todo implement a way to accept edit
     */
    public function resolve($options)
    {
        if (is_string($options)) {
            $options = array($options);
        }

        if (!is_array($options)) {
            throw new InvalidArgumentException('Expected a string or array of options.');
        }

        // limit the resolve to just our file and let change do the work
        $options[P4_Change::RESOLVE_FILE] = $this->getFilespec(true);
        $this->getChange()->resolve($options);

        return $this;
    }

    /**
     * Used to check if the file requires resolve or not. This function
     * will return true only when a resolve is scheduled. It doesn't attempt to
     * look at the current state and estimate if calling 'submit' would result in
     * an unresolved exception.
     *
     * @return  bool    true if file is resolved, false otherwise
     */
    public function needsResolve()
    {
        $this->_validateHasFilespec();

        $result = $this->getConnection()->run(
            'resolve',
            '-n',
            $this->getFilespecWithRevision()
        );

        return (bool) $result->hasData();
    }

    /**
     * Check if the file has the named attribute.
     *
     * @param   string  $attribute  the name of the attribute to check for.
     * @return  bool    true if the file has an attribute with this name.
     */
    public function hasAttribute($attribute)
    {
        return array_key_exists($attribute, $this->getAttributes());
    }

    /**
     * Check if the file has the named open attribute.
     *
     * @param   string  $attribute  the name of the open attribute to check for.
     * @return  bool    true if the file has an open attribute with this name.
     */
    public function hasOpenAttribute($attribute)
    {
        return array_key_exists($attribute, $this->getOpenAttributes());
    }

    /**
     * Get all submitted attributes of this file.
     * Submitted attributes are attributes that have been committed to the depot.
     *
     * @param   bool    $open   optional - get open attributes - defaults to false.
     * @return  array   all attributes of the file.
     */
    public function getAttributes($open = false)
    {
        $attributes = array();
        foreach ($this->getStatus() as $field => $value) {
            if (!$open && substr($field, 0, 5) == 'attr-') {
                $attributes[substr($field, 5)] = $value;
            } else if ($open && substr($field, 0, 9) == 'openattr-') {
                $attributes[substr($field, 9)] = $value;
            }
        }
        return $attributes;
    }

    /**
     * Get all pending attributes for this file.
     * Pending attributes are attributes that have been written to the client
     * but are not yet submitted to the depot.
     *
     * @return  array   all pending attributes of the file.
     */
    public function getOpenAttributes()
    {
        return $this->getAttributes(true);
    }

    /**
     * Get the named attribute from the set of submitted attributes on this file.
     * Submitted attributes are attributes that have been committed to the depot.
     *
     * @param   string  $attribute  the name of the attribute to get the value of.
     * @return  string  the value of the attribute.
     */
    public function getAttribute($attribute)
    {
        return $this->getStatus('attr-' . $attribute);
    }

    /**
     * Get the named attribute from the set of pending attributes on this file.
     * Pending attributes are attributes that have been written to the client
     * but are not yet submitted to the depot.
     *
     * @param   string  $attribute  the name of the open attribute to get the value of.
     * @return  string  the value of the attribute.
     */
    public function getOpenAttribute($attribute)
    {
        return $this->getStatus('openattr-' . $attribute);
    }

    /**
     * Set attributes on this file. Does not clear existing attributes.
     *
     * @param array $attributes the set of key/value pairs to set on the file.
     * @param bool  $propagate  optional - defaults to true - automatically propagate
     *                          the attributes to new revisions.
     * @param bool  $force      optional - write the attributes to the depot directly
     *                          by default attributes are pended to the client workspace.
     * @return  P4_File provide fluent interface.
     */
    public function setAttributes($attributes, $propagate = true, $force = false)
    {
        if (!is_array($attributes)) {
            throw new InvalidArgumentException(
                "Can't set attributes. Attributes must be an array."
            );
        }

        // if no attributes to set, nothing to do.
        if (empty($attributes)) {
            return $this;
        }

        // verify we have a filespec set; throws if invalid/missing
        $this->_validateHasFilespec();

        $params = array();
        foreach ($attributes as $key => $value) {
            $value = is_null($value) ? '' : $value;

            // ensure value is a string.
            if (!is_string($value)) {
                throw new InvalidArgumentException("Cannot set attribute. Value must be a string.");
            }

            // ensure attribute key name is valid.
            $validator = new P4_Validate_AttributeName;
            if (!$validator->isValid($key)) {
                throw new InvalidArgumentException("Cannot set attribute. Attribute name is invalid.");
            }

            // add params for attribute name/value.
            $params[] = '-n';
            $params[] = $key;
            $params[] = '-v';
            $params[] = bin2hex($value);
        }

        // setup shared inital parameters
        $prefixParams = array();
        if ($propagate) {
            $prefixParams[] = '-p';
        }
        if ($force) {
            $prefixParams[] = '-f';
        }

        // write value in binhex to avoid problems with binary data.
        $prefixParams[] = '-e';

        // permit revspec only if force writing attribute.
        $filespec = $force
            ? $this->getFilespecWithRevision()
            : $this->getFilespec(true);

        // see if we can set multiple attributes at once (for performance)
        // if we're unable (e.g. a value exceeds arg-max), set individually via input.
        $batches    = array();
        $connection = $this->getConnection();
        try {
            $batches = $connection->batchArgs($params, $prefixParams, array($filespec), 4);
        } catch (P4_Exception $e) {
            $prefixParams[] = '-i';
            foreach ($attributes as $key => $value) {
                $value  = is_null($value) ? '' : $value;
                $result = $this->getConnection()->run(
                    'attribute',
                    array_merge($prefixParams, array('-n', $key, $filespec)),
                    bin2hex($value)
                );

                // stop processing if we encounter warnings.
                if ($result->hasWarnings()) {
                    break;
                }
            }
        }

        // if we were able to batch the arguments, process them now.
        foreach ($batches as $batch) {
            $result = $this->getConnection()->run('attribute', $batch);

            // stop processing if we encounter warnings.
            if ($result->hasWarnings()) {
                break;
            }
        }

        if ($result->hasWarnings()) {
            throw new P4_File_Exception(
                "Failed to set attribute(s) on file: " . implode(", ", $result->getWarnings())
            );
        }

        // status has changed - clear the status cache.
        $this->clearStatusCache();

        return $this;
    }

    /**
     * Set the given attribute/value on the file.
     *
     * By default attributes will propagate to new revisions of the file
     * To disable this, set the propagate argument to false.
     *
     * By default attributes will be pended. To write attributes to the depot
     * directly, set the force flag to true.
     *
     * @param string        $key        the name of the attribute to write.
     * @param string|null   $value      the value to write.
     * @param bool          $propagate  optional - defaults to true - propagate the attribute
     *                                  to new revisions.
     * @param bool          $force      optional - defaults to false - write the attribute
     *                                  to the depot directly.
     * @return  P4_File provide fluent interface.
     */
    public function setAttribute($key, $value, $propagate = true, $force = false)
    {
        // ensure attribute key name is valid.
        // we do this prior to forming the array as an
        // invalid key (e.g. an array) would cause an error.
        $validator = new P4_Validate_AttributeName;
        if (!$validator->isValid($key)) {
            throw new InvalidArgumentException("Cannot set attribute. Attribute name is invalid.");
        }

        return $this->setAttributes(array($key => $value), $propagate, $force);
    }

    /**
     * Clear the specified attributes on this file.
     *
     * @param array $attributes the set of attributes to clear.
     * @param bool  $force      optional - clear the attributes in the depot directly
     *                          by default attributes are pended to the client workspace.
     * @return  P4_File provide fluent interface.
     */
    public function clearAttributes($attributes, $force = false)
    {
        if (!is_array($attributes)) {
            throw new InvalidArgumentException(
                "Can't clear attributes. Attributes must be an array."
            );
        }

        // if no attributes given, nothing to clear.
        if (empty($attributes)) {
            return $this;
        }

        // verify we have a filespec set; throws if invalid/missing
        $this->_validateHasFilespec();
        $filespec = $force
            ? $this->getFilespecWithRevision()
            : $this->getFilespec(true);

        // make -n/attr-name argument pairs.
        $params = array();
        foreach ($attributes as $attribute) {
            $params[] = "-n";
            $params[] = $attribute;
        }

        // there is a potential to exceed the arg-max/option-limit;
        // run attribute command as few times as possible
        $connection   = $this->getConnection();
        $prefixParams = $force ? array('-f') : array();
        foreach ($connection->batchArgs($params, $prefixParams, array($filespec), 2) as $batch) {
            $connection->run('attribute', $batch);
        }

        // status has changed - clear the status cache.
        $this->clearStatusCache();

        return $this;
    }

    /**
     * Clear the given attribute on the file.
     *
     * By default the cleared attribute will be pended. To clear attributes in the depot
     * directly, set the force flag to true.
     *
     * @param string    $attribute  the name of the attribute to clear.
     * @param bool      $force      optional - defaults to false - clear the attribute
     *                              in the depot directly.
     * @return  P4_File provide fluent interface.
     */
    public function clearAttribute($attribute, $force = false)
    {
        return $this->clearAttributes(array($attribute), $force);
    }

    /**
     * Test if a file is deleted in the depot.
     * Note: this method reports the deleted status based on the
     * filespec, which could be a non-head revision.
     *
     * @return boolean indicated whether the file is deleted.
     */
    public function isDeleted()
    {
        $headAction = $this->getStatus('headAction');
        if (preg_match('/delete/', $headAction)) {
            return true;
        }
        return false;
    }

    /**
     * Get file status (run fstat on file).
     *
     * File status is fetched once and then cached in the instance.
     * The cache can be primed via setStatusCache(). It can be cleared
     * via clearStatusCache().
     *
     * Attributes are fetched along with the status.
     *
     * @param   string  $field  optional - a specific status field to get.
     *                          by default all fields are returned.
     * @throws  P4_File_Exception  if the requested status field does not exist.
     */
    public function getStatus($field = null)
    {
        // if cache is not primed, run fstat.
        if (!array_key_exists('status', $this->_cache) || !isset($this->_cache['status'])) {
            // verify we have a filespec set; throws if invalid/missing
            $this->_validateHasFilespec();

            $result = $this->getConnection()->run(
                'fstat',
                array('-Oal', $this->getFilespecWithRevision())
            );
            if ($result->hasWarnings()) {
                throw new P4_File_Exception(
                    "Cannot get status: " . implode(", ", $result->getWarnings())
                );
            }

            if (is_array($result->getData(0))) {
                $this->setStatusCache($result->getData(0));
            } else {
                $this->setStatusCache(array());
            }
        }

        // return a specific field or all fields as appropriate.
        if ($field) {
            if (!array_key_exists($field, $this->_cache['status'])) {
                throw new P4_File_Exception("Can't fetch status. The requested field ('"
                    . $field . "') does not exist.");
            } else {
                return $this->_cache['status'][$field];
            }
        } else {
            return $this->_cache['status'];
        }
    }

    /**
     * Determine if this file has the named status field.
     *
     * @param   string  $field  the name of the field to check for.
     * @return  bool    true if the field exists.
     */
    public function hasStatusField($field)
    {
        try {
            $this->getStatus($field);
            return true;
        } catch (P4_File_Exception $e) {
            return false;
        }
    }

    /**
     * Set the file status cache to the given array of fields/values.
     *
     * @param   array   $status an array of field/value pairs.
     * @throws  InvalidArgumentException    if the given value is not an array.
     * @return  P4_File provide fluent interface.
     */
    public function setStatusCache($status)
    {
        if (!is_array($status)) {
            throw new InvalidArgumentException('Cannot set status cache. Status must be an array.');
        }
        $this->_cache['status'] = $status;

        if (isset($status['headRev'])) {
            $this->_cache['revision'] = $status['headRev'];
        }

        return $this;
    }

    /**
     * Clear the file status cache.
     *
     * @return  P4_File provide fluent interface.
     */
    public function clearStatusCache()
    {
        $this->_cache['status'] = null;

        return $this;
    }

    /**
     * Lock this file in the depot.
     *
     * @return  P4_File provide fluent interface.
     */
    public function lock()
    {
        // verify we have a filespec set; throws if invalid/missing
        $this->_validateHasFilespec();

        $this->getConnection()->run('lock', $this->getFilespec(true));

        // status has changed - clear the status cache.
        $this->clearStatusCache();

        return $this;
    }

    /**
     * Unlock this file in the depot.
     *
     * @return  P4_File provide fluent interface.
     */
    public function unlock()
    {
        // verify we have a filespec set; throws if invalid/missing
        $this->_validateHasFilespec();

        $this->getConnection()->run('unlock', $this->getFilespec(true));

        // status has changed - clear the status cache.
        $this->clearStatusCache();

        return $this;
    }

    /**
     * Check if the file is opened in Perforce by the current client.
     *
     * @return  bool    true if the file is opened by the current client.
     */
    public function isOpened()
    {
        if ($this->hasStatusField('action')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if this file is at the head revision or not.
     *
     * @return  bool    true if the file is at head, false otherwise
     */
    public function isHead()
    {
        $head = static::exists($this->getFilespec(true), $this->getConnection());

        if ($head === $this->getStatus('headRev')) {
            return true;
        }

        return false;
    }

    /**
     * Get the contents of the file in Perforce.
     *
     * File content is fetched once and then cached in the instance.
     * The cache can be primed via setContentCache(). It can be cleared
     * via clearContentCache().
     *
     * @return  string              the contents of the file in the depot.
     * @throws  P4_File_Exception   if the print command fails.
     */
    public function getDepotContents()
    {
        if (!array_key_exists('content', $this->_cache)) {
            // verify we have a filespec set; throws if invalid/missing
            $this->_validateHasFilespec();

            $result = $this->getConnection()->run('print', $this->getFilespecWithRevision());

            // check for warnings.
            if ($result->hasWarnings()) {
                throw new P4_File_Exception(
                    "Failed to get depot contents: " . implode(", ", $result->getWarnings())
                );
            }

            $print  = static::_parsePrintOutput($result->getData());

            // get first element
            $print  = reset($print);

            $this->_cache['content']  = $print['content'];
            $this->_cache['revision'] = $print['rev'];
        }
        return $this->_cache['content'];
    }

    /**
     * Get the annotated contents of the file in Perforce.
     *
     * Annotated file content is fetched once and then cached in the instance.
     * The cache can be primed via setAnnotateCache(). It can be cleared
     * via clearAnnotateCache().
     *
     * @return  array  an array of the file's lines, where each array entry looks like:
     *
     * array(
     *     'upper' => <upper version number>,
     *     'lower' => <lower version number>,
     *     'data'  => <text data for the current line>,
     * )
     *
     */
    public function getAnnotateContent()
    {
        if (
            !array_key_exists('annotatedContent', $this->_cache)
            || !isset($this->_cache['annotatedContent'])
        ) {
            // verify we have a filespec set; throws if invalid/missing
            $this->_validateHasFilespec();

            $result = $this->getConnection()->run('annotate', $this->getFilespec(true));
            $annotate = $result->getData();
            // remove the command's metadata
            array_shift($annotate);
            $this->_cache['annotatedContent'] = $annotate;
        }
        return $this->_cache['annotatedContent'];
    }

    /**
     * Clear the annotated content cache.
     *
     * @return  P4_File provide fluent interface.
     */
    public function clearAnnotateCache()
    {
        $this->_cache['annotatedContent'] = null;

        return $this;
    }

    /**
     * Prime the depot file cache with the given value.
     *
     * @param   string  $content    the contents of the file in the depot.
     * @return  P4_File provide fluent interface.
     */
    public function setContentCache($content)
    {
        $this->_cache['content'] = $content;

        return $this;
    }

    /**
     * Clear the depot file cache.
     *
     * @return  P4_File provide fluent interface.
     */
    public function clearContentCache()
    {
        unset($this->_cache['content']);

        return $this;
    }

    /**
     * Get the contents of the local file in the client workspace.
     *
     * @return  string  the contents of the local client file.
     */
    public function getLocalContents()
    {
        if (!file_exists($this->getLocalFilename())) {
            throw new P4_File_Exception(
                'Cannot get local file contents. Local file does not exist.'
            );
        }
        return file_get_contents($this->getLocalFilename());
    }

    /**
     * Write contents to the local client file.
     * If the file does not exist, it will be created.
     *
     * @param   string $content   the content to write to the file
     * @throws  P4_File_Exception if the file cannot be written.
     * @return  P4_File provide fluent interface.
     */
    public function setLocalContents($content)
    {
        $this->touchLocalFile();
        if (!is_writable($this->getLocalFilename())) {
            if (!chmod($this->getLocalFilename(), 0644)) {
                $message = "Failed to make local file writable.";
                throw new P4_File_Exception($message);
            }
        }
        if (file_put_contents($this->getLocalFilename(), $content) === false) {
            $message = "Failed to write local file.";
            throw new P4_File_Exception($message);
        }

        return $this;
    }

    /**
     * Touch the local client file.
     * If the file does not exist, it will be created.
     *
     * @throws  P4_File_Exception if the file cannot be touched.
     * @return  P4_File provide fluent interface.
     */
    public function touchLocalFile()
    {
        if (!is_dir($this->getLocalPath())) {
            $this->_createLocalPath();
        }
        if (!is_file($this->getLocalFilename())) {
            if (!touch($this->getLocalFilename())) {
                $message = "Failed to touch local file.";
                throw new P4_File_Exception($message);
            }
        }

        return $this;
    }

    /**
     * Open the file in another change and/or as a different filetype.
     *
     * @param   string  $change the change list to open the file in.
     * @param   string  $type   the filetype to open the file as.
     * @throws  InvalidArgumentException    if neither a change nor a type are given.
     * @return  P4_File provide fluent interface.
     */
    public function reopen($change = null, $type = null)
    {
        // verify we have a filespec set; throws if invalid/missing
        $this->_validateHasFilespec();

        // ensure user has specified a change and/or a type
        if (!$change && !$type) {
            throw new InvalidArgumentException(
                'Cannot reopen file. You must provide a change and/or a filetype.'
            );
        }

        $params = array();
        if ($change) {
            $params[] = '-c';
            $params[] = $change;
        }
        if ($type) {
            $params[] = '-t';
            $params[] = $type;
        }
        $params[] = $this->getFilespec(true);
        $this->getConnection()->run('reopen', $params);

        // status has changed - clear the status cache.
        $this->clearStatusCache();

        return $this;
    }

    /**
     * Revert the file.
     *
     * @param   string|array|null   $options    options to influence the operation:
     *                                              REVERT_UNCHANGED - only revert if unchanged
     * @return  P4_File             provides fluent interface.
     */
    public function revert($options = null)
    {
        // verify we have a filespec set; throws if invalid/missing
        $this->_validateHasFilespec();

        // if the unchanged option is given, add -a flag.
        $params = array();
        $unchanged = in_array(static::REVERT_UNCHANGED, (array) $options);
        if ($unchanged) {
            $params[] = "-a";
        }

        $params[] = $this->getFilespec(true);

        $this->getConnection()->run('revert', $params);

        // status has changed - clear the status cache.
        $this->clearStatusCache();

        return $this;
    }

    /**
     * Submit the file to perforce.
     * If the optional resolve flags are passed, an attempt will be made to automatically
     * resolve/resubmit should a conflict occur.
     *
     * @param   string              $description    the change description.
     * @param   null|string|array   $options        optional resolve flags, to be used if conflict
     *                                              occurs. See resolve() for details.
     * @throws  InvalidArgumentException            if no description is given.
     * @return  P4_File provide fluent interface.
     */
    public function submit($description, $options = null)
    {
        // verify we have a filespec set; throws if invalid/missing
        $this->_validateHasFilespec();

        // ensure that we have a description.
        if (!is_string($description) || !strlen($description)) {
            throw new InvalidArgumentException(
                'Cannot submit. Description must be a non-empty string.'
            );
        }

        // ensure the file is in the default pending change.
        // this is required to avoid inadvertently affecting
        // a numbered pending change description and its files.
        if ($this->hasStatusField('change') && $this->getStatus('change') != 'default') {
            $this->reopen('default');
        }

        // setup the submit options
        $params   = array();
        $params[] = '-d';
        $params[] = $description;
        $params[] = $this->getFilespec(true);

        try {
            $this->getConnection()->run('submit', $params);
        } catch (P4_Connection_ConflictException $e) {
            // if there are no resolve options; re-throw the resolve exception
            if (empty($options)) {
                throw $e;
            }

            // re-do submit via our change as this will
            // attempt to do the resolve. note change presently
            // does a wasted try prior to resolve but hopefully
            // the use is seldom enough we don't take a notable
            // performance hit on it.
            $e->getChange()->submit(null, $options);
        }

        // file has changed - clear all of the instance caches.
        $this->_cache = array();

        // if we had a rev-spec previously, take it off
        $this->setFilespec($this->getFilespec(true));

        return $this;
    }

    /**
     * Sync the file from the depot.
     * Note when the P4_File is fetched, or if made via new the first time it is
     * accessed and has a valid filespec, the revision is pinned at that point in
     * time. Sync will always use the pinned revision which is not necessarily head.
     *
     * @param   bool    $force      optional - defaults to false - force sync the file.
     * @param   bool    $flush      optional - defaults to false - don't transfer the file.
     * @return  P4_File             provide fluent interface.
     * @throws  P4_File_Exception   if sync fails.
     */
    public function sync($force = false, $flush = false)
    {
        // verify we have a filespec set; throws if invalid/missing
        $this->_validateHasFilespec();

        $params = array();
        if ($force) {
            $params[] = '-f';
        }
        if ($flush) {
            $params[] = '-k';
        }
        $params[] = $this->getFilespecWithRevision();
        $result = $this->getConnection()->run('sync', $params);

        // status has changed - clear the status cache.
        $this->clearStatusCache();

        // verify sync was successful.
        if ($result->hasWarnings()) {
            // if we had warnings throw if the haveRev doesn't equal the headRev
            // unless it is a deleted file in which case we expect a warning
            $haveRev = $this->hasStatusField('haveRev') ? $this->getStatus('haveRev') : -1;
            $headRev = $this->hasStatusField('headRev') ? $this->getStatus('headRev') : 0;
            if (!$this->isDeleted() && $headRev !== $haveRev) {
                throw new P4_File_Exception(
                    "Failed to sync file: " . implode(", ", $result->getWarnings())
                );
            }
        }

        return $this;
    }

    /**
     * Get the file's size in the depot.
     *
     * @return  int  the depot file's size in bytes, or zero.
     * @todo make this work properly.
     */
    public function getFileSize()
    {
        if (!$this->hasStatusField('fileSize')) {
            throw new P4_File_Exception('The file does not have a fileSize attribute.');
        }
        return (int) $this->getStatus('fileSize');
    }

    /**
     * Get the size of the local client file.
     *
     * @return  int the local file's size in bytes, or zero.
     */
    public function getLocalFileSize()
    {
        if (!file_exists($this->getLocalFilename())) {
            throw new P4_File_Exception('The local file does not exist.');
        }
        return (int) filesize($this->getLocalFilename());
    }

    /**
     * Get the path to the file in local file syntax.
     *
     * @return  string  the path to the file in local file syntax.
     */
    public function getLocalFilename()
    {
        // verify we have a filespec set; throws if invalid/missing
        $this->_validateHasFilespec();

        $filespec = $this->getFilespec(true);

        // if filespec is in local-file syntax return it.
        if (strlen($filespec) >=2 && substr($filespec, 0, 2) != '//') {
            return $filespec;
        }

        // otherwise, get local filename from p4 where.
        $where = $this->where();
        return $where[2];
    }

    /**
     * Get the local path to the file.
     *
     * @return  string  the local path to the file.
     */
    public function getLocalPath()
    {
        return dirname($this->getLocalFilename());
    }

    /**
     * Get the path to the file in depot file syntax.
     *
     * @return  string  the path to the file in depot file syntax.
     */
    public function getDepotFilename()
    {
        // verify we have a filespec set; throws if invalid/missing
        $this->_validateHasFilespec();

        $filespec = $this->getFilespec(true);

        // if filespec is in depot-file syntax, return it.
        // note, we must verify that it doesn't start with the client name.
        $clientPrefix = "//" . $this->getConnection()->getClient() . "/";
        if (strlen($filespec) >=2 && substr($filespec, 0, 2) == '//' &&
            substr($filespec, 0, strlen($clientPrefix)) != $clientPrefix) {
            return $filespec;
        }

        // otherwise, get depot file from p4 where.
        $where = $this->where();
        return $where[0];
    }

    /**
     * Get the depot path to the file.
     *
     * @return  string  the depot path to the file.
     */
    public function getDepotPath()
    {
        return dirname($this->getDepotFilename());
    }

    /**
     * Get the basename of the file.
     *
     * @param   string  $suffix if filename ends in this suffix it will be cut off.
     * @return  string  the basename of the file.
     */
    public function getBasename($suffix = null)
    {
        // verify we have a filespec set; throws if invalid/missing
        $this->_validateHasFilespec();

        return basename($this->getFilespec(true), $suffix);
    }

    /**
     * Determine how this file maps through the client view.
     *
     * Produces an array with three variations on the filespec.
     * Depot-syntax, client-syntax and local file-system syntax
     * (in that order).
     *
     * Caches the result so that subsequent lookups do not incur
     * the 'p4 where' command overhead.
     *
     * @return  array   three variations of the filespec: depot-syntax
     *                  client-syntax and local-syntax (respectively).
     * @throws  P4_File_Exception if the file is not mapped by the client.
     */
    public function where()
    {
        if (!array_key_exists('where', $this->_cache) || !isset($this->_cache['where'])) {
            // verify we have a filespec set; throws if invalid/missing
            $this->_validateHasFilespec();

            $result = $this->getConnection()->run('where', $this->getFilespec(true));
            if ($result->hasWarnings()) {
                throw new P4_File_Exception("Where failed. File is not mapped.");
            }
            $this->_cache['where'] = array_values($result->getData(0));
        }
        return $this->_cache['where'];
    }

    /**
     * Convienence function to return all changes associated with this file.
     *
     * @param   array   $options    optional - array of options to augment fetch behavior.
     *                              supported options are the same as P4_Change, except for
     *                              the use of FETCH_BY_FILESPEC which is not permitted here.
     * @return  P4_Iterator         Iterator of P4_Changes
     */
    public function getChanges(array $options = null)
    {
        $this->_validateHasFilespec();

        $options = array_merge(
            (array) $options,
            array(P4_Change::FETCH_BY_FILESPEC => $this->getFilespec(true))
        );

        return P4_Change::fetchAll($options, $this->getConnection());
    }

    /**
     * Convenience function to return the change object associated with the file at its current revspec.
     *
     * @return  P4_Change  The associated change object.
     */
    public function getChange()
    {
        return P4_Change::fetch($this->getStatus('headChange'), $this->getConnection());
    }

    /**
     * Strip the revision specifier from a file specification.
     * This removes the \#rev or \@change component from a filespec.
     *
     * @param   string  $filespec   the filespec to strip the revspec from.
     * @return  string  the filespec without the revspec.
     */
    public static function stripRevspec($filespec)
    {
        $revPos = strpos($filespec, "#");
        if ($revPos !== false) {
            $filespec = substr($filespec, 0, $revPos);
        }
        $revPos = strpos($filespec, "@");
        if ($revPos !== false) {
            $filespec = substr($filespec, 0, $revPos);
        }
        return $filespec;
    }

    /**
     * Check if the given filespec has a revision specifier.
     *
     * @param   string  $filespec   the filespec to check for a revspec.
     * @return  bool    true if the filespec has a revspec component.
     */
    public static function hasRevspec($filespec)
    {
        if (strpos($filespec, "#") !== false ||
            strpos($filespec, "@") !== false) {
            return true;
        }
        return false;
    }

    /**
     * Check if given field is valid model field.
     *
     * @param  string  $field  model field to check
     * @return boolean
     */
    public function hasField($field)
    {
        return $this->hasStatusField($field);
    }

    /**
     * Return array with all model fields.
     *
     * @return array
     */
    public function getFields()
    {
        return array_keys($this->getStatus());
    }

    /**
     * Return value of given field of the model.
     *
     * @param  string  $field  model field to retrieve
     * @return mixed
     */
    public function getValue($field)
    {
        return $this->getStatus($field);
    }

    /**
     * Parses print output for one or more files into the below format:
     *
     * Array
     * (
     *      [//depot/path/to/file.ext] => Array
     *      (
     *          [depotFile] => //depot/path/to/file.ext
     *          [rev]       => 6
     *          [change]    => 222450
     *          [action]    => edit
     *          [type]      => text
     *          [time]      => 1257743394
     *          [content]   => Full content of file
     *      )
     * )
     *
     * @param   array   $data   Data from p4 print result
     * @return  array           File details and content keyed on depotFile; formated as above
     */
    protected static function _parsePrintOutput($data)
    {
        $files = array();

        // print output consists of the following elements, repeated for each file:
        //  - file info
        //  - file content  (repeated for every 4k of file content, or when server feels like it)
        foreach ($data as $block) {
            // If we are at a meta-data block, store the file name and meta-data then continue
            if (is_array($block) && isset($block['depotFile'])) {
                $name           = $block['depotFile'];
                $files[$name]   = $block;

                // prime content entry as string
                $files[$name]['content']  = '';

                continue;
            }

            // Be defensive, clear file name if we hit an unrecognized block
            if (is_array($block)) {
                $name = null;
            }

            // If we made it this far, and we have a file name, it's a content block; append it
            if (isset($name)) {
                $files[$name]['content'] .= $block;
            }
        }

        return $files;
    }

    /**
     * Open the file for the specified action.
     *
     * @param   string  $action     the action to open the file for ('add', 'edit' or 'delete').
     * @param   int     $change     optional - a numbered pending change to open the file in.
     * @param   string  $fileType   optional - the file-type to open the file as.
     * @param   bool    $force      optional - defaults to true - set to false to avoid reopening.
     * @return  P4_File provide fluent interface.
     * @todo    better handling of files open for branch operations - currently, such files
     *          will be reverted because the action won't match - this is not correct.
     */
    protected function _openForAction($action, $change = null, $fileType = null, $force = true)
    {
        // verify we have a filespec set; throws if invalid/missing
        $this->_validateHasFilespec();

        // action must be one of: add, edit or delete.
        if (!in_array($action, array('add', 'edit', 'delete'))) {
            throw new P4_File_Exception("Cannot open file. Invalid open 'action' specified.");
        }

        // if already opened for specified action, verify change and type, then return.
        if ($this->_isOpenForAction($action)) {
            if (($change && $this->getStatus('change') !== $change)
                || ($fileType && $this->getStatus('type') !== $fileType)
            ) {
                $this->reopen($change, $fileType);
            }

            return $this;
        }

        $p4   = $this->getConnection();
        $file = $this->getFilespec(true);

        // if force is true, revert files opened for the wrong action
        // unless it's open for integrate and we are trying to edit
        // or it's open for branch and we are trying to add (to keep
        // the integration credit).
        if ($force
            && $this->isOpened()
            && !$this->_isOpenForAction($action)
            && !($action == 'edit' && $this->_isOpenForAction('integrate'))
            && !($action == 'add'  && $this->_isOpenForAction('branch'))
        ) {
            $result = $p4->run('revert', $file);
        }

        // setup command flags.
        $flags = array();
        if ($change) {
            $flags[] = '-c';
            $flags[] = $change;
        }
        if ($fileType) {
            $flags[] = '-t';
            $flags[] = $fileType;
        }

        // allows delete to work without having to sync file.
        if ($action === 'delete') {
            $flags[] = '-v';
        }
        $flags[] = $file;

        // throw for edit or delete of a deleted file (these are dead ends!)
        // use the -n flag to see what would happen without actually opening file.
        if (in_array($action, array('edit', 'delete'))) {
            $result   = $p4->run($action, array_merge(array('-n'), $flags));
            foreach ($result->getData() as $data) {
                if (is_string($data)
                    && preg_match("/warning: $action of deleted file/", $data)
                ) {
                    throw new P4_File_Exception(
                        "Failed to open file for $action: " . $data
                    );
                }
            }
        }

        // open file for specified action.
        $result = $p4->run($action, $flags);

        // check for warnings.
        if ($result->hasWarnings()) {
            throw new P4_File_Exception(
                "Failed to open file for $action: " . implode(", ", $result->getWarnings())
            );
        }

        // status has changed - clear the status cache.
        $this->clearStatusCache();

        // verify file was opened for specified action.
        if (!$this->hasStatusField('action') || $this->getStatus('action') !== $action) {
            throw new P4_File_Exception(
                "Failed to open file for $action: " . $result->getData(0)
            );
        }

        return $this;
    }

    /**
     * Checks if the file is open for the given action.
     *
     * Applies a bit of fuzzy logic to consider move/add to be open for
     * edit since a file must be opened for edit before it can be moved.
     *
     * @param   string  $action     the action to check for
     * @return  bool    true if the file is open for the given action
     */
    protected function _isOpenForAction($action)
    {
        // if not opened at all, nothing more to check
        if (!$this->isOpened()) {
            return false;
        }

        $openAction = $this->getStatus('action');
        if ($openAction == $action) {
            return true;
        }

        // consider move/add to also be open for edit - a file must be opened
        // for edit before it can be moved; therefore, a move/add file is open
        // for edit - without this, calling edit() on the target of a move
        // would incur a revert unless force is explicitly set to false.
        if ($openAction == 'move/add' && $action == 'edit') {
            return true;
        }

        return false;
    }

    /**
     * Ensure that a valid, non-empty, filespec has been set on this instance.
     * Will throw an exception if the filespec has wildcards or is unset.
     *
     * @throws  P4_File_Exception     if the filespec is empty or invalid
     */
    private function _validateHasFilespec()
    {
        $filespec = $this->getFilespec();

        if (empty($filespec)) {
            throw new P4_File_Exception("Cannot complete operation, no filespec has been specified");
        }

        $this->_validateFilespec($filespec);
    }

    /**
     * Ensure that the given filespec has no wildcards.
     * Will throw an exception if the filespec has wildcards
     *
     * @param   string  $filespec            a filespec key to check for wildcards.
     * @throws  P4_File_Exception     if the filespec has wildcards.
     */
    private static function _validateFilespec($filespec)
    {
        if (!is_string($filespec) ||
            strpos($filespec, "*")   !== false ||
            strpos($filespec, "...") !== false) {
            throw new P4_File_Exception("Invalid filespec provided. In this context, "
                . "filespecs must be a reference to a single file.");
        }
    }

    /**
     * Create the directory structure for the local file.
     */
    private function _createLocalPath()
    {
        if (!is_dir($this->getLocalPath())) {
            if (!mkdir($this->getLocalPath(), 0755, TRUE)) {
                throw new P4_File_Exception("Unable to create path: " . $this->getLocalPath());
            }
        }
    }
}
