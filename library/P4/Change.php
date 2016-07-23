<?php
/**
 * Abstracts operations against Perforce changes.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        support shelved changes.
 */
class P4_Change extends P4_Spec_PluralAbstract implements P4_ResolvableInterface
{
    const DEFAULT_CHANGE         = 'default';
    const PENDING_CHANGE         = 'pending';
    const SUBMITTED_CHANGE       = 'submitted';

    const FETCH_BY_FILESPEC      = 'filespec';
    const FETCH_BY_STATUS        = 'status';
    const FETCH_INTEGRATED       = 'integrated';
    const FETCH_BY_CLIENT        = 'client';
    const FETCH_BY_USER          = 'user';

    const RESOLVE_FILE           = 'file';

    const MAX_SUBMIT_ATTEMPTS    = 3;

    protected static $_specType  = 'change';
    protected static $_idField   = 'Change';
    protected static $_accessors = array(
        'Date'          => 'getDateTime',
        'User'          => 'getUser',
        'Client'        => 'getClient',
        'Status'        => 'getStatus',
        'Description'   => 'getDescription',
        'JobStatus'     => 'getJobStatus',
        'Jobs'          => 'getJobs',
        'Files'         => 'getFiles'
    );
    protected static $_mutators  = array(
        'Description'   => 'setDescription',
        'JobStatus'     => 'setJobStatus',
        'Jobs'          => 'setJobs',
        'Files'         => 'setFiles'
    );

    protected $_cache   = array();

    /**
     * Get the number of this change.
     * Extends parent to return an integer value for numbered changes.
     *
     * @return  null|string|id  the integer number of the change, the literal string 'default'
     *                          or null if no id has been set.
     */
    public function getId()
    {
        $id = parent::getId();
        if ($id !== null && $id !== static::DEFAULT_CHANGE) {
            $id = intval($id);
        }
        return $id;
    }

    /**
     * Set the id of this spec entry. Id must be in a valid format or null.
     * Extended from parent to clear cache.
     *
     * @param   null|string     $id     the id of this entry - pass null to clear.
     * @return  P4_Spec_PluralAbstract  provides a fluent interface
     * @throws  InvalidArgumentException    if id does not pass validation.
     */
    public function setId($id)
    {
        if ($this->getId() !== $id) {
            $this->_cache = array();
        }

        return parent::setId($id);
    }

    /**
     * Determine if the given change id exists.
     *
     * @param   string                   $id          the id to check for.
     * @param   P4_Connection_Interface  $connection  optional - a specific connection to use.
     * @return  bool  true if the given id matches an existing change.
     */
    public static function exists($id, P4_Connection_Interface $connection = null)
    {
        // check id for valid format
        if (!static::_isValidId($id)) {
            return false;
        }

        // if no connection given, use default.
        $connection = $connection ?: static::getDefaultConnection();

        // default change always exists.
        if ($id === static::DEFAULT_CHANGE) {
            return true;
        }

        // attempt to fetch change - assume id does not exist on failure.
        try {
            $connection->run('change', array('-o', $id));
            return true;
        } catch (P4_Exception $e) {
            return false;
        }
    }

    /**
     * Get all changes from Perforce. Adds filtering options.
     *
     * @param   array   $options    optional - array of options to augment fetch behavior.
     *                              supported options are:
     *
     *                                   FETCH_MAXIMUM - set to integer value to limit to the
     *                                                   first 'max' number of entries.
     *                               FETCH_BY_FILESPEC - set to a filespec to limit changes to those
     *                                                   affecting the file(s) matching the filespec.
     *                                 FETCH_BY_STATUS - set to a valid change status to limit result
     *                                                   to changes with that status (e.g. 'pending').
     *                                FETCH_INTEGRATED - set to true to include changes integrated
     *                                                   into the specified files.
     *                                 FETCH_BY_CLIENT - set to a client to limit changes to those
     *                                                   on the named client.
     *                                   FETCH_BY_USER - set to a user to limit changes to those
     *                                                   owned by the named user.
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
     * Extend parent to set id to 'new' if unset and to reopen files that
     * are open in other pending changelists where necessary.
     *
     * @param   bool        $force      optional - default false - true to save submitted change.
     * @return  P4_Change   provides a fluent interface
     * @throws  P4_UnopenedException                if change contains unopened files.
     * @throws  P4_Connection_CommandException      if save command fails for some reason.
     * @todo    support the -u flag to force update of submitted change description.
     */
    public function save($force = false)
    {
        $values = $this->_getValues();
        if (!isset($values[static::_getIdField()]) || $this->isDefault()) {
            $values[static::_getIdField()] = "new";
        }

        // ensure all required fields have values.
        $this->_validateRequiredFields($values);

        // can't update a submitted change without the force option.
        if (!$force && $this->isSubmitted()) {
            throw new P4_Spec_Exception(
                "Cannot update a submitted change without the force option."
            );
        }

        // perform save.
        $connection = $this->getConnection();
        try {

            $flags = array("-i");
            if ($force) {
                $flags[] = "-u";
            }
            $result = $connection->run(static::_getSpecType(), $flags, $values);

            // extract change number from command result.
            $data = $result->getData(0);

            if (!preg_match('/^Change ([^ ]+) (created|updated)/', $data, $matches)) {
                throw new P4_Spec_Exception('Cannot determine number of saved change.');
            }

            $id = $matches[1];

        } catch (P4_Connection_CommandException $e) {

            // if the exception was caused by non-existant jobs, the change should
            // have been created.
            if (preg_match(
                "/Change ([^ ]+) (created|updated).*Job '([^']+)' doesn't exist\./s",
                $e->getMessage(), $matches
            )) {
                $this->setId($matches[1]);
                throw $e;
            }

            // if exception not caused by un-opened files, re-throw.
            if (strpos($e->getMessage(), "Can't include file(s) not already opened.") === false) {
                throw $e;
            }

            // if any files are truly un-opened throw a special un-opened files exception.
            // (save will complain of un-opened files if files are not in default change)
            $flags  = array("-Ro", "-T", "depotFile");
            $flags  = array_merge($flags, $values['Files']);
            $result = $connection->run("fstat", $flags);
            if ($result->hasWarnings()) {
                throw new P4_UnopenedException(
                    "Cannot save change. One or more files are not open."
                );
            }

            // all files are actually open, save w.out files first, then reopen.
            $change = clone $this;
            $id     = $change->setFiles(null)->save()->getId();
            $flags  = $values['Files'];
            array_unshift($flags, "-c", $id);
            $connection->run("reopen", $flags);

        }

        // Store the retrieved id.
        $this->setId($id);

        // should re-populate (server may change values).
        $this->_deferPopulate(true);

        return $this;
    }

    /**
     * Save and submit this changelist.
     *
     * @param   string              $description    optional - a description of this change.
     * @param   null|string|array   $options        optional resolve flags, to be used if conflict
     *                                              occurs. See resolve() for details.
     * @return  P4_Change           provides fluent interface.
     * @throws  P4_Spec_Exception   if the change is not a pending change.
     * @throws  P4_Change_ResolveConflictException  if change contains files requiring resolve.
     * @throws  P4_Change_DeleteConflictException   if change contains files that have been deleted.
     */
    public function submit($description = null, $options = null)
    {
        // ensure change is a pending change.
        if (!$this->isPending()) {
            throw new P4_Spec_Exception("Can only submit pending changes.");
        }

        // if description is given, use it.
        if (strlen($description)) {
            $this->setDescription($description);
        }

        // save the change before submit.
        $this->save();

        // try repeatedly to submit (with resolves in-between attempts)
        // note: no need to explicitly sync as submit schedules resolve
        for ($i = static::MAX_SUBMIT_ATTEMPTS; $i > 0; $i--) {
            try {
                $result = $this->getConnection()->run("submit", array("-c", $this->getId()));

                // everything went ok no need to retry.
                break;
            } catch (P4_Connection_ConflictException $e) {
                // if there are no resolve options or we have exceeded
                // max resolve attempts; re-throw the resolve exception
                if ($i <= 1 || empty($options)) {
                    throw $e;
                }

                // our changelist id has possibly been updated
                // update our id field to match.
                $this->setId($e->getChange()->getId());

                $this->resolve($options);
            }
        }

        // extract change number from last data block of command result
        // because the change number may have changed during submit.
        $last = end($result->expandSequences()->getData());
        $this->setId($last['submittedChange']);

        return $this;
    }

    /**
     * Revert all of the files in this changelist.
     *
     * @return  P4_Change           provides fluent interface.
     * @throws  P4_Spec_Exception   if the change is not a pending change.
     */
    public function revert()
    {
        // ensure change is a pending change.
        if (!$this->isPending()) {
            throw new P4_Spec_Exception("Can only revert pending changes.");
        }

        // save the change before revert (updates files in change).
        $this->save();

        // perform revert.
        $result = $this->getConnection()->run(
            "revert",
            array("-c", $this->getId(), '//...')
        );

        // should re-populate.
        $this->_deferPopulate(true);

        return $this;
    }


    /**
     * Delete this changelist.
     *
     * @param   bool        $force  optional - defaults to false - set to true to force delete of
     *                              another user/client's changelist or a submitted (empty) change.
     * @return  P4_Change   provides fluent interface.
     */
    public function delete($force = false)
    {
        $id = $this->getId();
        if ($id === null) {
            throw new P4_Spec_Exception("Cannot delete change. No id has been set.");
        }

        // default change cannot be deleted.
        if ($id === P4_Change::DEFAULT_CHANGE) {
            throw new P4_Spec_Exception("Cannot delete the default change.");
        }

        // ensure id exists.
        $connection = $this->getConnection();
        if (!static::exists($id, $connection)) {
            throw new P4_Spec_NotFoundException(
                "Cannot delete change $id. Record does not exist."
            );
        }

        // unknown or unhandled change status (e.g. 'shelved').
        if (!$this->isPending() && !$this->isSubmitted()) {
            throw new P4_Spec_Exception(
                "Unable to delete change with status '" . $this->getStatus() . "'."
            );
        }

        // handle submitted changes.
        $connection = $this->getConnection();
        if ($this->isSubmitted()) {

            // requires force option.
            if (!$force) {
                throw new P4_Spec_Exception(
                    "Cannot delete a submitted change without the force option."
                );
            }

            // check for files.
            if (count($this->getFiles())) {
                throw new P4_Spec_Exception(
                    "Cannot delete a submitted change that contains files."
                );
            }

            $result = $connection->run("change", array("-d", "-f", $id));
        }

        // handle pending changes (must remove files and fixes first).
        if ($this->isPending()) {
            if (!$force &&
                ($this->getUser()   !== $connection->getUser() ||
                 $this->getClient() !== $connection->getClient())) {
                throw new P4_Spec_Exception(
                    "Cannot delete a change from another user/client without the force option."
                );
            }

            // remove any associated files or jobs.
            $change = clone $this;
            $change->setFiles(null)->setJobs(null)->save();

            // delete the change.
            $flags = array("-d", $id);
            if ($force) {
                array_unshift($flags, "-f");
            }
            $connection->run("change", $flags);
        }

        // confirm delete successful (change -d does not surface errors).
        if (static::exists($id)) {
            throw new P4_Spec_Exception("Failed to delete change $id.");
        }

        // should re-populate.
        $this->_deferPopulate(true);

        return $this;
    }

    /**
     * Resolves the change based on the passed option(s).
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
     * Lastly, the resolve can be limited to a particular file in the change by passing:
     *  RESOLVE_FILE => filespec with no wildcards
     *
     * @param   array|string    $options    Resolve option(s); must include a RESOLVE_* preference.
     * @return  P4_Change       provide fluent interface.
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

        $mode       = '';
        $whitespace = '';
        $arguments  = array();

        // loop options so we accept the last mode
        // and whitespace setting we encounter.
        foreach ($options as $option) {
            switch ($option)
            {
                case static::RESOLVE_ACCEPT_MERGED:
                    $mode = '-am';
                    break;
                case static::RESOLVE_ACCEPT_YOURS:
                    $mode = '-ay';
                    break;
                case static::RESOLVE_ACCEPT_THEIRS:
                    $mode = '-at';
                    break;
                case static::RESOLVE_ACCEPT_SAFE:
                    $mode = '-as';
                    break;
                case static::RESOLVE_ACCEPT_FORCE:
                    $mode = '-af';
                    break;
            }

            switch ($option)
            {
                case static::IGNORE_WHITESPACE_CHANGES:
                    $whitespace = '-db';
                    break;
                case static::IGNORE_WHITESPACE:
                    $whitespace = '-dw';
                    break;
                case static::IGNORE_LINE_ENDINGS:
                    $whitespace = '-dl';
                    break;
            }
        }

        // we can't do anything without a mode; throw
        if (empty($mode)) {
            throw new InvalidArgumentException(
                'No action specified. Expected Resolve Accept Merged|Yours|Theirs|Safe|Force'
            );
        }

        // compile our various flags into our arguments array
        $arguments[] = $mode;
        if ($whitespace) {
            $arguments[] = $whitespace;
        }

        $files = $this->getFiles();
        if (isset($options[static::RESOLVE_FILE])) {
            $file = $options[static::RESOLVE_FILE];
            if (!in_array($file, $files)) {
                throw new InvalidArgumentException(
                    "The RESOLVE_FILE specified is not in this change."
                );
            }

            $files = array($file);
        }

        // resolve files in several (as few as possible) runs as
        // there is a potential to exceed the arg-max on this command
        $connection = $this->getConnection();
        $batches    = $connection->batchArgs($files, $arguments);
        foreach ($batches as $batch) {
            $connection->run('resolve', $batch);
        }

        return $this;
    }

    /**
     * Get the date this change was last modified on the server.
     *
     * @return  null|string     the date this change was last modified on the server,
     *                          or null if the change does not exist on the server.
     * @todo    modify to use DateTime object.
     */
    public function getDateTime()
    {
        return $this->_getValue('Date');
    }

    /**
     * Get the user that created this change.
     *
     * @return  string  the user that created this change.
     */
    public function getUser()
    {
        $user = $this->_getValue('User');
        if (!$user) {
            $user = $this->getConnection()->getUser();
        }
        return $user;
    }

    /**
     * Get the client on which this change was created.
     *
     * @return  string  the client on which this change was created.
     */
    public function getClient()
    {
        $client = $this->_getValue('Client');
        if (!$client) {
            $client = $this->getConnection()->getClient();
        }
        return $client;
    }

    /**
     * Get the status of this change (either 'pending' or 'submitted').
     *
     * @return  string  the status of this change: 'pending', 'submitted'.
     */
    public function getStatus()
    {
        $status = $this->_getValue('Status');
        if (!$status) {
            $status = static::PENDING_CHANGE;
        }
        return $status;
    }

    /**
     * Get the description for this change.
     *
     * @return  string  the description for this change.
     */
    public function getDescription()
    {
        return $this->_getValue('Description');
    }

    /**
     * Set the description for this change.
     *
     * @param   string|null     $description    description for this change.
     * @return  P4_Change       provides a fluent interface.
     * @throws  InvalidArgumentException  if description is incorrect type.
     */
    public function setDescription($description)
    {
        if ($description !== null && !is_string($description)) {
            throw new InvalidArgumentException("Cannot set description. Invalid type given.");
        }

        return $this->_setValue('Description', $description);
    }

    /**
     * Get the job status of this change (the status that associated jobs will
     * have when the change is submitted).
     *
     * The value of the job status field is not preserved. You cannot get the
     * job status of a saved or submitted change. Once a changelist is saved or
     * submitted, the job status field is cleared. It can only be read after it
     * has been explicitly set, and before the change is saved or submitted.
     *
     * @return  null|string     the job status of this change if not yet saved or submitted.
     *                          null otherwise.
     */
    public function getJobStatus()
    {
        return $this->_getValue('JobStatus');
    }

    /**
     * Get the jobs attached to this change.
     *
     * @return  array   the list of jobs attached to this change.
     * @todo    return P4_Job objects in an iterator.
     */
    public function getJobs()
    {
        $jobs = $this->_getValue('Jobs');
        return is_array($jobs) ? $jobs : array();
    }

    /**
     * Set the list of jobs attached to this change.
     *
     * @param   null|array      $jobs   the jobs to attach to this change.
     * @return  P4_Change       provides a fluent interface.
     * @throws  InvalidArgumentException    if jobs is incorrect type.
     * @throws  P4_Spec_Exception           if change is submitted.
     */
    public function setJobs($jobs)
    {
        if ($jobs === null) {
            $jobs = array();
        }

        // if jobs is an iterator, extract the job numbers.
        if ($jobs instanceof P4_Model_Iterator) {
            $newJobs = array();
            foreach ($jobs as $job) {
                if ($job instanceof P4_Job) {
                    $newJobs[] = $job->getId();
                } else {
                    throw new InvalidArgumentException('Each iterator job must be a P4_Job object.');
                }
            }
            $jobs = $newJobs;
        }

        // ensure jobs is an array.
        if (!is_array($jobs)) {
            throw new InvalidArgumentException('Cannot set jobs. Invalid type given.');
        }

        // ensure job elements are strings.
        foreach ($jobs as $job) {
            if (!is_string($job)) {
                throw new InvalidArgumentException('Each job must be a string.');
            }
        }

        // don't permit set jobs on submitted changes.
        if ($this->isSubmitted()) {
            throw new P4_Spec_Exception('Cannot set jobs on a submitted change.');
        }

        return $this->_setValue('Jobs', $jobs);
    }

    /**
     * Add a job to the list of jobs attached to this change.
     *
     * @param   string      $job    the id of the job to attach to this change.
     * @return  P4_Change   provides fluent interface.
     */
    public function addJob($job)
    {
        $jobs = $this->getJobs();
        if (!in_array($job, $jobs)) {
            $jobs[] = $job;
        }
        $this->setJobs($jobs);

        return $this;
    }

    /**
     * Get the files attached to this change. Revspecs are included for submitted
     * changes but are not present on pending changes.
     *
     * @return  array   list of files associated with this change.
     */
    public function getFiles()
    {
        $files = $this->_getValue('Files');
        return is_array($files) ? $files : array();
    }

    /**
     * Get the files attached to this change in P4_File format.
     *
     * @return  P4_Model_Iterator   list of P4_File's associated with this change.
     */
    public function getFileObjects()
    {
        if (!isset($this->_cache['fileObjects'])
            || !$this->_cache['fileObjects'] instanceof P4_Model_Iterator
        ) {
            $this->_cache['fileObjects'] = P4_File::fetchAll(
                P4_File_Query::create()->addFilespecs(
                    $this->getFiles()
                )
            );
        }

        return clone $this->_cache['fileObjects'];
    }

    /**
     * Get the requested file attached to this change in P4_File format.
     *
     * @param   P4_File|string  $file       Filespec in string or P4_File format; rev is ignored
     * @return  P4_File         The requested file at the revision associated with this change
     * @throws  InvalidArgumentException    If the specified file doesn't exist.
     */
    public function getFileObject($file)
    {
        // normalize to string
        if ($file instanceof P4_File) {
            $file = $file->getDepotFilename();
        }

        // validate input
        if (!is_string($file)) {
            throw new InvalidArgumentException('File must be a string or P4_File object.');
        }

        // ensure no rev-spec is present on our comparison entry
        $file = P4_File::stripRevspec($file);
        foreach ($this->getFileObjects() as $changeFile) {
            if (P4_File::stripRevspec($changeFile->getDepotfilename()) == $file) {
                return $changeFile;
            }
        }

        throw new InvalidArgumentException('The requested file was not found in this change');
    }

    /**
     * Set the list of opened files attached to this change.
     *
     * @param   null|array|P4_Model_Iterator  $files  the files to attach to this change.
     * @return  P4_Change                     provides a fluent interface.
     * @throws  InvalidArgumentException      if files is incorrect type.
     * @throws  P4_Spec_Exception             if change is submitted.
     * @todo    accept model iterator of p4 file objects as input.
     */
    public function setFiles($files)
    {
        if ($files === null) {
            $files = array();
        }

        if ($files instanceof P4_Model_Iterator) {
            $files = iterator_to_array($files);
        }

        // ensure files is an array.
        if (!is_array($files)) {
            throw new InvalidArgumentException('Cannot set files. Invalid type given.');
        }

        // normalize the array entries to a string, stripping revspecs
        foreach ($files as &$file) {
            if ($file instanceof P4_File) {
                $file = $file->getFilespec();
            }

            if (!is_string($file)) {
                throw new InvalidArgumentException('All files must be a string or P4_File');
            }

            $file = P4_File::stripRevspec($file);
        }

        // don't permit set files on submitted changes.
        if ($this->isSubmitted()) {
            throw new P4_Spec_Exception('Cannot set files on a submitted change.');
        }

        // we cache file objects; clear that out
        $this->_cache = array();

        $this->_setValue('Files', $files);

        return $this;
    }

    /**
     * Add a file to the list of files in this changelist.
     *
     * @param   string|P4_File  $file   the file to attach to this change.
     * @return  P4_Change       provides fluent interface.
     */
    public function addFile($file)
    {
        // if file is a P4_File object, extract the filespecs.
        if ($file instanceof P4_File) {
            $file = $file->getFilespec();
        }

        $files = $this->getFiles();
        if (!in_array($file, $files)) {
            $files[] = $file;
        }
        $this->setFiles($files);

        return $this;
    }

    /**
     * Check if this is a pending change.
     *
     * @return  bool  true if this is a pending change - false otherwise.
     */
    public function isPending()
    {
        return ($this->isDefault() || $this->getStatus() === static::PENDING_CHANGE);
    }

    /**
     * Check if this is a submitted change.
     *
     * @return  bool  true if this is a submitted change - false otherwise.
     */
    public function isSubmitted()
    {
        return ($this->getStatus() === static::SUBMITTED_CHANGE);
    }

    /**
     * Test if this is the default change.
     *
     * @return  bool  true if this is the default change - false otherwise.
     */
    public function isDefault()
    {
        return ($this->getId() === static::DEFAULT_CHANGE);
    }

    /**
     * Get files that need to be resolved.
     *
     * @return  P4_Model_Iterator   files that need to be resolved.
     */
    public function getFilesToResolve()
    {
        $query = P4_File_Query::create()
                 ->addFilespec(P4_File::ALL_FILES)
                 ->setLimitToChangelist($this->getId())
                 ->setLimitToNeedsResolve(true)
                 ->setLimitToOpened(true);
        return P4_File::fetchAll($query, $this->getConnection());
    }

    /**
     * Get files that must be reverted.
     *
     * @return  P4_Model_Iterator   files that must be reverted.
     */
    public function getFilesToRevert()
    {
        // setup fstat filter to match files that must be reverted.
        // several conditions to catch:
        //  - files open for add, but already existing and not deleted, or
        //  - files that are not open for add, but are deleted in the depot.
        $filter = "(action=add & headAction=* & ^headAction=...deleted) | "
                . "(headAction=...delete & ^action=add)";

        $query = P4_File_Query::create()
                 ->addFilespec(P4_File::ALL_FILES)
                 ->setLimitToChangelist($this->getId())
                 ->setLimitToOpened(true)
                 ->setFilter($filter);
        return P4_File::fetchAll($query, $this->getConnection());
    }

    /**
     * Check if the given id is in a valid format for a change number.
     *
     * @param   string      $id     the id to check
     * @return  bool        true if id is valid, false otherwise
     */
    protected static function _isValidId($id)
    {
        $validator = new P4_Validate_ChangeNumber;
        return $validator->isValid($id);
    }

    /**
     * Get raw spec data direct from Perforce. No caching involved.
     * Overrides parent to suppress id when id is 'default' and to
     * fetch files for submitted changes.
     *
     * @return  array   $data   the raw spec output from Perforce.
     * @todo    get jobs for submitted changes.
     */
    protected function _getSpecData()
    {
        $flags = array('-o');
        if ($this->getId() !== static::DEFAULT_CHANGE) {
            $flags[] = $this->getId();
        }
        $data = $this->getConnection()
                     ->run(static::_getSpecType(), $flags)
                     ->expandSequences()
                     ->getData(0);

        // get files/jobs if this is a submitted change
        // note: can't use isSubmitted here - populate not complete yet.
        if ($data['Status'] == P4_Change::SUBMITTED_CHANGE) {
            $query = P4_File_Query::create()
                     ->addFilespec(P4_File::ALL_FILES)
                     ->setLimitToChangelist($this->getId());
            $files = P4_File::fetchAll($query, $this->getConnection());

            $this->_cache['fileObjects'] = $files;
            $data['Files'] = array();
            foreach ($files as $file) {
                $data['Files'][] = $file->getFilespec() . '#' . $file->getStatus('headRev');
            }
        }

        return $data;
    }

    /**
     * Produce set of flags for the spec list command, given fetch all options array.
     * Extends parent to add support for additional options.
     *
     * @param   array   $options    array of options to augment fetch behavior.
     *                              see fetchAll for documented options.
     * @return  array   set of flags suitable for passing to spec list command.
     */
    protected static function _getFetchAllFlags($options)
    {
        $flags = parent::_getFetchAllFlags($options);

        // always use -l (for full descriptions).
        $flags[] = "-l";

        if (isset($options[static::FETCH_INTEGRATED]) &&
            $options[static::FETCH_INTEGRATED] === true) {
            $flags[] = "-i";
        }

        if (isset($options[static::FETCH_BY_STATUS])) {
            $flags[] = "-s";
            $flags[] = $options[static::FETCH_BY_STATUS];
        }

        if (isset($options[static::FETCH_BY_CLIENT])) {
            $flags[] = "-c";
            $flags[] = $options[static::FETCH_BY_CLIENT];
        }

        if (isset($options[static::FETCH_BY_USER])) {
            $flags[] = "-u";
            $flags[] = $options[static::FETCH_BY_USER];
        }

        // filespec must come last.
        if (isset($options[static::FETCH_BY_FILESPEC])) {
            $flags[] = $options[static::FETCH_BY_FILESPEC];
        }

        return $flags;
    }

    /**
     * Given a spec entry from spec list output (p4 changes), produce
     * an instance of this spec with field values set where possible.
     *
     * @param   array                       $listEntry      a single spec entry from spec list output.
     * @param   array                       $flags          the flags that were used for this 'fetchAll' run.
     * @param   P4_Connection_Interface     $connection     a specific connection to use.
     * @return  P4_User                     a (partially) populated instance of this spec class.
     * @todo    properly convert unixtime to DateTime object.
     */
    protected static function _fromSpecListEntry($listEntry, $flags, P4_Connection_Interface $connection)
    {
        // time is in unixtime - unset to avoid figuring out timezone
        // for a proper conversion.
        unset($listEntry['time']);

        // rename 'desc' field to 'Description'.
        $listEntry['Description'] = $listEntry['desc'];
        unset($listEntry['desc']);

        return parent::_fromSpecListEntry($listEntry, $flags, $connection);
    }
}
