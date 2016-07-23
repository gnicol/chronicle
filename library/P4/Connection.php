<?php
/**
 * Perforce connection factory.
 *
 * A Factory used to create a Perforce Connection instance. This class is
 * responsible for deciding the specific implementation to use.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Connection
{
    protected static    $_defaultConnection;
    protected static    $_appName;

    /**
     * Factory method that creates and returns a single instance of a Perforce Connection
     * implementation. The caller should not need to worry about the specific implemenation
     * used, only that it implements P4_Connection_Interface.
     *
     * @param   string  $port        optional - the port to connect to.
     * @param   string  $user        optional - the user to connect as.
     * @param   string  $client      optional - the client spec to use.
     * @param   string  $password    optional - the password to use.
     * @param   string  $ticket      optional - a ticket to use.
     * @param   string  $type        optional - a specific client implementation to use.
     *
     * @return  P4_Connection_Interface  a perforce client implementation.
     * @throws  P4_Exception      if an invalid API type is given.
     */
    public static function factory(
        $port       = null,
        $user       = null,
        $client     = null,
        $password   = null,
        $ticket     = null,
        $type       = null )
    {
        // use the type parameter if it was provided.
        // throw an exception if it specifies an invalid type.
        if ($type) {
            if (!self::isValidType($type)) {
                throw new P4_Exception("Invalid Perforce Connection Type: " . $type);
            }
        } else {
            if (extension_loaded("perforce")) {
                $type = "P4_Connection_Extension";
            } else {
                $type = "P4_Connection_CommandLine";
            }
        }

        // create instance of desired type.
        $connection = new $type(
            $port,
            $user,
            $client,
            $password,
            $ticket
        );

        // if we have an app name, set it.
        if (static::$_appName) {
            $connection->setAppName(static::$_appName);
        }

        // if no default connection has been set, use this one.
        if (!self::hasDefaultConnection()) {
            self::setDefaultConnection($connection);
        }

        return $connection;
    }

    /**
     * Get the identity of the current default Connection implementation.
     *
     * @return  array   an array of client Connection information containing the name,
     *                  platform, version, build and date of the client library.
     */
    public static function getConnectionIdentity()
    {
        $p4 = self::factory();
        return $p4->getConnectionIdentity();
    }

    /**
     * Determine if the given Connection type is valid.
     *
     * @param   string  $type  the Connection implementation class to use.
     * @return  bool    true if the given Connection class exists and is valid.
     */
    public static function isValidType($type)
    {
        if (!class_exists($type)) {
            return false;
        }
        if (!in_array('P4_Connection_Interface', class_implements($type))) {
            return false;
        }
        return true;
    }

    /**
     * Set a default connection for the environment.
     *
     * @param   P4_Connection_Interface  $connection     the default connection to use.
     * @throws  P4_Exception  if the given connection is not a valid Connection instance.
     */
    public static function setDefaultConnection(P4_Connection_Interface $connection)
    {
        self::$_defaultConnection = $connection;
    }

    /**
     * Unset the default connection.
     */
    public static function clearDefaultConnection()
    {
        self::$_defaultConnection = null;
    }

    /**
     * Get the default connection for the environment.
     *
     * @return  P4_Connection_Interface  the default connection.
     * @throws  P4_Exception  if no default connection has been set.
     */
    public static function getDefaultConnection()
    {
        if (!self::$_defaultConnection instanceof P4_Connection_Interface) {
            throw new P4_Exception(
                "Failed to get connection. A default connection has not been set.");
        }

        return self::$_defaultConnection;
    }

    /**
     * Check if a default connection has been set.
     *
     * @return  bool    true if a default connection is set.
     */
    public static function hasDefaultConnection()
    {
        try {
            self::getDefaultConnection();
            return true;
        } catch (P4_Exception $e) {
            return false;
        }
    }

    /**
     * Provide a application name to set on any new connections.
     *
     * @param   string|null     $name   app name to report to the server
     */
    public static function setAppName($name)
    {
        static::$_appName = is_null($name) ? $name : (string) $name;
    }

    /**
     * Get the application name that will be set on any new connections.
     *
     * @return  string|null     app name to be set on new connections.
     */
    public static function getAppName()
    {
        return static::$_appName;
    }

    /**
     * Private constructor. Prevents callers from creating a factory instance.
     */
    private function __construct()
    {
    }
}
