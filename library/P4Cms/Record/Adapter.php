<?php
/**
 * Encapsulates everything needed for Records to read from and write
 * to storage. Specifically, a p4 connection, a base path in perforce,
 * and a prefix for sequence counters.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        consider adding callbacks for batch operations (e.g.
 *              preCommit, postCommit, commitError, revertBatch, etc.)
 */
class P4Cms_Record_Adapter
{
    const               COMMIT_THROW_CONFLICT = "throw";

    protected           $_basePath            = null;
    protected           $_connection          = null;
    protected           $_batchId             = null;
    protected           $_properties          = array();

    /**
     * Get the connection object to use to communicate with Perforce.
     *
     * @return  P4_Connection_Abstract  the p4 connection to use.
     * @throws  P4Cms_Record_Exception  if no valid connection is set.
     */
    public function getConnection()
    {
        if (!$this->_connection instanceof P4_Connection_Abstract) {
            throw new P4Cms_Record_Exception(
                "Cannot get connection. No valid p4 connection has been set."
            );
        }

        return $this->_connection;
    }

    /**
     * Set the Perforce connection to use to communicate with the
     * Perforce backend.
     *
     * @param   P4_Connection_Abstract  $p4     the p4 connection to use.
     * @return  P4Cms_Record_Adapter    provides fluent interface.
     * @throws  P4Cms_Record_Exception  if the connection object is invalid.
     */
    public function setConnection($p4)
    {
        if (!$p4 instanceof P4_Connection_Abstract) {
            throw new P4Cms_Record_Exception(
                "Cannot set connection. The given argument is not a valid p4 connection."
            );
        }

        $this->_connection = $p4;
        return $this;
    }

    /**
     * Get the base path in Perforce under which records should be
     * read from and written to.
     *
     * @return  string                  the record storage base path.
     * @throws  P4Cms_Record_Exception  if no base path is set.
     */
    public function getBasePath()
    {
        if (!is_string($this->_basePath) || !strlen($this->_basePath)) {
            throw new P4Cms_Record_Exception(
                "Cannot get base path. No base path is set."
            );
        }

        return $this->_basePath;
    }

    /**
     * Set the base path in Perforce under which records should be
     * read from and written to.
     *
     * @param   string  $path           the record storage base path.
     * @return  P4Cms_Record_Adapter    provides fluent interface.
     * @throws  P4Cms_Record_Exception  if the base path is not a valid string.
     */
    public function setBasePath($path)
    {
        if (!is_string($path) || !strlen($path)) {
            throw new P4Cms_Record_Exception(
                "Cannot set base path. Given path is not a valid string."
            );
        }

        $this->_basePath = $path;
        return $this;
    }

    /**
     * Check if adapter has a particular property.
     *
     * @param   string  $name   the property name to check for the existence of
     * @return  boolean         true if the adapter has the named property, false otherwise.
     */
    public function hasProperty($name)
    {
        return array_key_exists($name, $this->_properties);
    }

    /**
     * Get a particular property value of this adapter.
     *
     * @param   string  $name           name of the property to get the value of
     * @return  mixed                   the value of the property name
     * @throws  P4Cms_Record_Exception  if the property name does not exist
     */
    public function getProperty($name)
    {
        // return property value if it was set, otherwise throw an exception
        if ($this->hasProperty($name)) {
            return $this->_properties[$name];
        }

        throw new P4Cms_Record_Exception(
            "Cannot find adapter property '$name'. Property was not set."
        );
    }

    /**
     * Get all properties of this adapter.
     *
     * @return  array   all properties set to this adapter
     */
    public function getProperties()
    {
        return $this->_properties;
    }

    /**
     * Set a particular property of this adapter.
     *
     * @param   string                  $name   name of the property to set the value of
     * @param   mixed                   $value  value to set
     * @return  P4Cms_Record_Adapter            provides a fluent interface
     */
    public function setProperty($name, $value)
    {
        $this->_properties[$name] = $value;
        return $this;
    }

    /**
     * Set adapter properties.
     *
     * @param   array   $properties     array with properties to set
     * @return  P4Cms_Record_Adapter    provides fluent interface
     */
    public function setProperties(array $properties)
    {
        $this->_properties = $properties;
        return $this;
    }

    /**
     * Start a batch. Changes made to records will be
     * placed in a numbered pending change and will not be
     * submitted until the batch is committed.
     *
     * Batches cannot be nested. Attempting to begin a
     * batch while in a batch will result in an
     * exception.
     *
     * @param   string  $description    required - a description of the batch.
     * @return  P4Cms_Record_Adapter    provides fluent interface.
     * @throws  P4Cms_Record_Exception  if already in a batch.
     * @todo    automatically aggregate descriptions when in batches.
     */
    public function beginBatch($description)
    {
        if ($this->inBatch()) {
            throw new P4Cms_Record_Exception(
                "Cannot begin batch. Already in a batch."
            );
        }

        // create a new pending change.
        $change = new P4_Change($this->getConnection());
        $change->setDescription($description)->save();

        return $this->_batchId = $change->getId();
    }

    /**
     * Commit the batch. Submits the pending change
     * corresponding to the batch id.
     *
     * @param   string              $description    optional - a final description of the batch.
     * @param   null|string|array   $options        optional - passing the SAVE_THROW_CONFLICTS
     *                                              flag will cause exceptions on conflict; default
     *                                              behaviour is to crush any conflicts.
     * @return  P4Cms_Record_Adapter    provides fluent interface.
     * @throws  P4Cms_Record_Exception  if not in a batch.
     */
    public function commitBatch($description = null, $options = null)
    {
        if (!$this->inBatch()) {
            throw new P4Cms_Record_Exception(
                "Cannot commit batch. Not in a batch."
            );
        }

        // submit the change identified by batch id.
        $change = P4_Change::fetch(
            $this->getBatchId(),
            $this->getConnection()
        );
        try {
            // default option is to 'accept yours' but we switch to
            // null if SAVE_THROW_CONFLICTS flag is passed.
            $resolveFlag = P4_Change::RESOLVE_ACCEPT_YOURS;
            if (in_array(static::COMMIT_THROW_CONFLICT, (array)$options)) {
                $resolveFlag = null;
            }

            $change->submit($description, $resolveFlag);
        } catch (P4_Connection_CommandException $e) {
            // ignore exception if change is empty - otherwise rethrow.
            if (count($change->getFiles()) == 0) {
                $change->delete();
            } else {
                throw $e;
            }
        }

        // clear the batch id.
        $this->_batchId = null;

        return $this;
    }

    /**
     * Reverts all files in the pending change corresponding to
     * the batch id.
     *
     * @return  P4Cms_Record_Adapter    provides fluent interface.
     * @throws  P4Cms_Record_Exception  if not in a batch.
     * @todo    automatically revert batches when unhandled exceptions/errors occur.
     */
    public function revertBatch()
    {
        if (!$this->inBatch()) {
            throw new P4Cms_Record_Exception(
                "Cannot revert batch. Not in a batch."
            );
        }

        // revert the change identified by batch id.
        $change = P4_Change::fetch(
            $this->getBatchId(),
            $this->getConnection()
        );
        $change->revert()
               ->delete();

        // clear the batch id.
        $this->_batchId = null;

        return $this;
    }

    /**
     * Get the id of the current batch. The batch id
     * corresponds to a pending changelist in Perforce.
     *
     * @return  int                     the batch id (pending change number).
     * @throws  P4Cms_Record_Exception  if not in a batch.
     */
    public function getBatchId()
    {
        if (!$this->inBatch()) {
            throw new P4Cms_Record_Exception(
                "Cannot get batch id. Not in a batch."
            );
        }

        return (int) $this->_batchId;
    }

    /**
     * Determine if we are currently in a batch on this
     * storage adapter.
     *
     * @return  bool    true if we are in a batch.
     */
    public function inBatch()
    {
        $id = $this->_batchId;
        return (int) $id > 0;
    }

    /**
     * Change the description of the current batch
     *
     * @param   string  $description    the description of the current batch.
     * @return  P4Cms_Record_Adapter    provides fluent interface.
     */
    public function setBatchDescription($description)
    {
        $change = P4_Change::fetch(
            $this->getBatchId(),
            $this->getConnection()
        );
        $change->setDescription($description)
               ->save();

        return $this;
    }
}
