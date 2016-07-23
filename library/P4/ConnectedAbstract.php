<?php
/**
 * Base class for all Perforce models.
 *
 * Provides get/set Perforce connection capabilities. This allows
 * a specific perforce connection to be set on a per-object basis.
 *
 * To set the perforce connection to use for a particular instance,
 * pass a connection to the constructor or call setConnection() on
 * the object if it already exists.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
abstract class P4_ConnectedAbstract implements P4_ConnectedInterface
{
    protected   $_connection    = null;

    /**
     * We need a custom sleep to exclude the connection property.
     * Connection objects cannot be serialized.
     *
     * @return  array   list of properties to serialize
     */
    public function __sleep()
    {
        return array_diff(
            array_keys(get_object_vars($this)),
            array('_connection')
        );
    }

    /**
     * Instantiate the model and set the connection to use.
     *
     * @param   P4_Connection_Interface $connection  optional - a connection to use for this instance.
     */
    public function __construct(P4_Connection_Interface $connection = null)
    {
        if ($connection) {
            $this->setConnection($connection);
        } else if (P4_Connection::hasDefaultConnection()) {
            $this->setConnection(P4_Connection::getDefaultConnection());
        }
    }

    /**
     * Set the Perforce connection to use when
     * issuing Perforce commands for this instance.
     *
     * @param   P4_Connection_Interface $connection     the connection to use for this instance.
     * @return  P4_ModelAbstract        provides fluent interface.
     */
    public function setConnection(P4_Connection_Interface $connection)
    {
        $this->_connection = $connection;

        return $this;
    }

    /**
     * Get the Perforce connection used by this model.
     *
     * @return  P4_Connection_Interface  the connection instance used by this model.
     */
    public function getConnection()
    {
        if ($this->_connection instanceof P4_Connection_Interface) {
            return $this->_connection;
        }

        throw new P4_Exception("Cannot get connection. No connection is set.");
    }

    /**
     * Get the default Perforce connection to use.
     *
     * @return  P4_Connection_Interface  the default connection.
     */
    public static function getDefaultConnection()
    {
        return P4_Connection::getDefaultConnection();
    }

    /**
     * Determine if this model has a connection to Perforce.
     *
     * @return  bool  true if the model has a connection to Perforce.
     */
    public function hasConnection()
    {
        try {
            $this->getConnection();
            return true;
        } catch (P4_Exception $e) {
            return false;
        }
    }

    /**
     * Clear this model's connection. This is primarily for testing purposes.
     */
    public function clearConnection()
    {
        $this->_connection = null;
    }
}
