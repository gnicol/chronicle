<?php
/**
 * Provides volatile (unsubmitted/unversioned) storage in Perforce.
 * This works by creating pending files and setting open attributes
 * on those files. This storage is useful for casual data that has
 * high turn-over or that we simply don't want to make permanent.
 *
 * Opened attributes can only be viewed by the client workspace that
 * set them. Therefore, this class introduces get/setClientMasquerade()
 * methods that cause it to masquerade as another client workspace
 * when executing Perforce commands.
 *
 * Volatile records have many limitations:
 *  - much simpler than regular records
 *  - no support for file content fields
 *  - no support for fetching multiple records
 *  - only string values are supported
 *  - no auto-generation of ids
 *  - no lazy-loading
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_Volatile extends P4Cms_Record_Connected
{
    const       CLIENT              = 'volatileClient';

    protected   $_clientMasquerade  = null;
    protected   $_depotFile         = null;
    protected   $_originalClient    = null;
    protected   $_originalHost      = null;
    protected   $_storageSubPath    = null;

    /**
     * Fetch a stored volatile record by id.
     *
     * @param   string                  $id             the id of the record to fetch
     * @param   P4Cms_Record_Adapter    $adapter        the storage adapter to use
     * @param   P4_Client|string|null   $masquerade     the client to masquerade as
     * @return  P4Cms_Record_Volatile   the populated volatile record
     * @throws  P4Cms_Record_NotFoundException          if no matching record can be found.
     */
    public static function fetch($id, $adapter, $masquerade = null)
    {
        $record = new static;
        $record->setId($id)
               ->setAdapter($adapter)
               ->setClientMasquerade($masquerade)
               ->_populate();

        return $record;
    }

    /**
     * Check for the existance of a volatile record by id.
     *
     * @param   string                  $id             the id of the record to lookup
     * @param   P4Cms_Record_Adapter    $adapter        the storage adapter to use
     * @param   P4_Client|string|null   $masquerade     the client to masquerade as
     * @return  bool                    true if the record exists; false otherwise.
     */
    public static function exists($id, $adapter, $masquerade = null)
    {
        try {
            static::fetch($id, $adapter, $masquerade);
            return true;
        } catch (P4Cms_Record_NotFoundException $e) {
            return false;
        }
    }

    /**
     * Set the client workspace to masquerade as.
     *
     * @param   P4_Client|string|null   $client     the client workspace to masquerade as
     * @return  P4Cms_Record_Volatile   provides fluent interface.
     */
    public function setClientMasquerade($client)
    {
        // upgrade client to a client object.
        $client = (!$client instanceof P4_Client && !is_null($client))
            ? P4_Client::fetch($client, $this->getAdapter()->getConnection())
            : $client;

        $this->_clientMasquerade = $client;

        return $this;
    }

    /**
     * Get the client workspace we are set to masquerade as.
     * If no masquerade client has been explicitly set we will
     * fetch/create one based on the presence of a 'volatileClient'
     * property on the storage adapter.
     *
     * @return  P4_Client|null  the client workspace to masquerade as.
     */
    public function getClientMasquerade()
    {
        // if we already have a client that we're masquerading as, return it.
        if ($this->_clientMasquerade) {
            return $this->_clientMasquerade;
        }

        // the adapter may specify a volatile client property (the site
        // branch object does this), if we don't have one early exit.
        $adapter = $this->getAdapter();
        if (!$adapter->hasProperty(static::CLIENT)) {
            return null;
        }

        $p4       = $adapter->getConnection();
        $clientId = $adapter->getProperty(static::CLIENT);

        // try to fetch the volatile client - if it does not exist, create one
        // based on the adapter's existing client (ie. same view/stream).
        try {
            $client = P4_Client::fetch($clientId, $p4);
        } catch (P4_Spec_NotFoundException $e) {
            $client = P4_Client::fetch($p4->getClient(), $p4);
            $client->setId($clientId)
                   ->setDescription("Chronicle generated 'volatile' record client.")
                   ->setRoot(P4_Environment::isWindows() ? "NUL" : "/dev/null")
                   ->touchUpView()
                   ->save();
        }

        $this->_clientMasquerade = $client;

        return $this->_clientMasquerade;
    }

    /**
     * Save the values in this volatile record to Perforce.
     * Note that the values are not submitted, only pended.
     *
     * @return  P4Cms_Record_Volatile   provides fluent interface.
     */
    public function save()
    {
        $file = $this->_getDepotFile();

        // masquerade as another client
        $this->_beginCharade();

        try {
            // ensure pending file is opened for add (or edit)
            // we use flush and -t/k to avoid touching files on disk.
            $this->_runFree('flush', $file);
            $this->_runFree('add',   array('-t',  'text', $file));
            $this->_runFree('edit',  array('-kt', 'text', $file));

            $params = array();
            foreach ($this->_values as $key => $value) {
                $params[] = "-n";
                $params[] = $key;
                $params[] = "-v";
                $params[] = (string) $value;
            }
            $params[] = $file;

            $this->getAdapter()->getConnection()->run('attribute', $params);
        } catch (Exception $e) {
            // look away!
        }

        // restore original client.
        $this->_endCharade();

        // if an exception occurred, throw it now.
        if (isset($e)) {
            throw $e;
        }

        return $this;
    }

    /**
     * Remove the volatile record. Since volatile records are never
     * submitted, this is actually a 'p4 revert' operation.
     *
     * @return  P4Cms_Record_Volatile   provides fluent interface.
     */
    public function delete()
    {
        $file = $this->_getDepotFile();

        // masquerade as another client
        $this->_beginCharade();

        try {
            $this->getAdapter()->getConnection()->run('revert', array('-k', $file));
        } catch (Exception $e) {
            // look away!
        }

        // restore original client.
        $this->_endCharade();

        // if an exception occurred, throw it now.
        if (isset($e)) {
            throw $e;
        }

        return $this;
    }

    /**
     * Set the id of this record.
     * Extended to clear depotFile anytime id changes.
     *
     * @param   mixed   $id             the value of the id of this record.
     * @return  P4Cms_Record_Volatile   provides a fluent interface
     */
    public function setId($id)
    {
        $this->_depotFile = null;
        return parent::setId($id);
    }

    /**
     * Load values into this record from Perforce.
     * Clobbers any existing in-memory values.
     *
     * @return  P4Cms_Record_Volatile   provides fluent interface.
     */
    protected function _populate()
    {
        $file = $this->_getDepotFile();

        // masquerade as another client
        $this->_beginCharade();

        try {
            $result = $this->getAdapter()->getConnection()->run('fstat', array('-Oa', $file));

            // extract information if no warings (ie. file exists).
            if (!$result->hasWarnings()) {
                $this->_values = array();
                foreach ((array) $result->getData(0) as $key => $value) {
                    $parts = explode('-', $key, 2);
                    if (count($parts) !== 2 || $parts[0] !== 'openattr') {
                        continue;
                    }

                    $this->_setValue($parts[1], $value);
                }
            } else {
                // warnings indicate no such record.
                $e = new P4Cms_Record_NotFoundException(
                    "Cannot fetch record: " . $this->getId() . ". No matching record."
                );
            }
        } catch (Exception $e) {
            // look away!
        }

        // restore original client.
        $this->_endCharade();

        // if an exception occurred, throw it now.
        if (isset($e)) {
            throw $e;
        }

        return $this;
    }

    /**
     * Run a Perforce command with no exceptions.
     *
     * @param   string          $command    the command to run.
     * @param   array|string    $params     optional - one or more arguments.
     * @return  bool|P4_Result  command result or false if it failed.
     */
    protected function _runFree($command, $params)
    {
        $adapter = $this->getAdapter();
        $p4      = $adapter->getConnection();

        try {
            return $p4->run($command, $params);
        } catch (P4_Exception $e) {
            return false;
        }
    }

    /**
     * Begin masquerading as another client/host.
     */
    protected function _beginCharade()
    {
        // nothing to do if not masquerading.
        $masquerade = $this->getClientMasquerade();
        if (!$masquerade) {
            return;
        }

        $adapter = $this->getAdapter();
        $p4      = $adapter->getConnection();

        // clobber the current client and host, but remember
        // them so we can restore them afterwards.
        $this->_originalClient = $p4->getClient();
        $this->_originalHost   = $p4->getHost();
        $p4->setClient($masquerade->getId() ?: $p4->getClient());
        $p4->setHost($masquerade->getHost() ?: $p4->getHost());
    }

    /**
     * Stop masquerading - restore original client/host.
     */
    protected function _endCharade()
    {
        // nothing to do if not masquerading.
        if (!$this->_originalClient) {
            return;
        }

        $adapter = $this->getAdapter();
        $p4      = $adapter->getConnection();

        $p4->setClient($this->_originalClient);
        $p4->setHost($this->_originalHost);

        $this->_originalClient = null;
        $this->_originalHost   = null;
    }

    /**
     * Get the (depot-syntax formatted) path to this record in Perforce.
     *
     * @return  string  the path to this record in depot-syntax.
     */
    protected function _getDepotFile()
    {
        // must have an id to get depot file.
        if (!$this->getId()) {
            throw new P4Cms_Record_Exception("Cannot get record file path without an id.");
        }

        // convert id to depot file syntax if we haven't already.
        if (!$this->_depotFile) {
            $adapter = $this->getAdapter();
            $subPath = trim($this->_storageSubPath, '/\\');
            $subPath = $subPath ? '/' . $subPath . '/' : '/';
            $file    = $adapter->getBasePath() . $subPath . $this->getId();
            $result  = $adapter->getConnection()->run('where', $file);

            $this->_depotFile = $result->getData(0, 'depotFile');
        }

        return $this->_depotFile;
    }
}
