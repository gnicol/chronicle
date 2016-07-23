<?php
/**
 * Provides a static log method that will write to a Zend_Log
 * instance set via setLogger(). This gives predictable, singleton
 * access to a system-wide logger.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Log
{
    const EMERG   = 0;  // Emergency: system is unusable
    const ALERT   = 1;  // Alert: action must be taken immediately
    const CRIT    = 2;  // Critical: critical conditions
    const ERR     = 3;  // Error: error conditions
    const WARN    = 4;  // Warning: warning conditions
    const NOTICE  = 5;  // Notice: normal but significant condition
    const INFO    = 6;  // Informational: informational messages
    const DEBUG   = 7;  // Debug: debug messages

    protected static    $_logger    = null;

    /**
     * Private constructor to prevent instances from being created.
     *
     * @codeCoverageIgnore
     */
    final private function __construct()
    {
    }

    /**
     * Set the logger to use when logging.
     *
     * @param   null|Zend_Log    $logger    a zend log instance to log to or null to clear.
     * @throws  InvalidArgumentException    if the given log is not a valid zend log.
     */
    public static function setLogger($logger)
    {
        if ($logger !== null && !$logger instanceof Zend_Log) {
            throw new InvalidArgumentException(
                "Cannot set logger. The given logger is not a valid zend log instance."
            );
        }

        static::$_logger = $logger;
    }

    /**
     * Get the logger to use when logging.
     *
     * @return  Zend_Log            the zend log instance to log to.
     * @throws  Zend_Log_Exception  if there is no log instance set.
     */
    public static function getLogger()
    {
        if (!static::$_logger instanceof Zend_Log) {
            throw new P4_Exception(
                "Cannot get logger. No logger has been set."
            );
        }

        return static::$_logger;
    }

    /**
     * Determine if a logger has been set.
     *
     * @return  bool    true if a logger has been set; false otherwise.
     */
    public static function hasLogger()
    {
        try {
            static::getLogger();
            return true;
        } catch (P4_Exception $e) {
            return false;
        }
    }

    /**
     * Log a message at a priority using the zend log
     * instance set via setLogger. If no logger has been
     * set, fails quietly.
     *
     * @param  string   $message   Message to log
     * @param  integer  $priority  Priority of message
     * @param  mixed    $extras    Extra information to log in event
     */
    public static function log($message, $priority = null, $extras = null)
    {
        try {
            if ($priority === null) {
                $priority = self::INFO;
            }
            static::getLogger()->log($message, $priority, $extras);
        } catch (Exception $e) {
            // don't let failure to log stop execution.
        }
    }

    /**
     * Log an exception. Logs a caller provided message (to give
     * context) with the exception message and type (as an error).
     * Also logs a backtrace (at debug priority).
     *
     * @param  string   $message    Message to log with the exception.
     * @param  integer  $exception  The exception that occured.
     */
    public static function logException($message, $exception)
    {
        // if caller failed to provide an exception object, just log
        // the message.
        if (!$exception instanceof Exception) {
            static::log($message, static::ERR);
            return;
        }

        static::log(
            $message . " " . get_class($exception) . ": " . $exception->getMessage(),
            static::ERR
        );
        static::log(
            "Backtrace:\n" . $exception->getTraceAsString(),
            static::DEBUG
        );
    }
}
