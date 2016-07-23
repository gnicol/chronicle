<?php
/**
 * Adds a notification facility which will ultimately be exposed in the UI.
 *
 * Example usage:
 * P4Cms_Notifications::add('An error message', 'error');
 * P4Cms_Notifications::add('Other users are editing this document', 'warn');
 * P4Cms_Notifications::add('Changes saved.');
 *
 * if (P4Cms_Notifications::exist('error')) {
 *     echo P4Cms_Notifications::getCount('error') .' error notifications exist.';
 * }
 *
 * Notifications are intended to be transient; users should only be presented
 * with a notifications once. Internally, Zend_Session is used to persist
 * notifications across web requests, but once retrieved, by default, each
 * notification is destroyed. Calling code can opt to retain notifications;
 * please see the documentation for fetch() below.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Notifications
{
    /**
     * Define known severities.
     */
    const SEVERITY_SUCCESS  = 'success';
    const SEVERITY_INFO     = 'info';
    const SEVERITY_WARNING  = 'warning';
    const SEVERITY_ERROR    = 'error';

    /**
     * Zend_Session storage object.
     * @var Zend_Session
     */
    private static $_session = null;

    /**
     * Add a message to the collection of notifications of the specified
     * priority.
     *
     * @param   string|array  $message   Message(s) to add.
     * @param   string        $severity  Severity of the message(s) - default: SEVERITY_INFO
     * @return  void
     * @throw   InvalidArgumentException  When severity is passed as an array.
     */
    public static function add($message, $severity = self::SEVERITY_INFO)
    {
        $session = static::_getSession();
        // Initialize the notifications, if necessary
        if (!isset($session->notifications)) {
            $session->notifications = array();
        }

        // make sure severity has a reasonable default
        if (is_null($severity)) {
            $severity = static::SEVERITY_INFO;
        }

        // severity passed as an array is not acceptable.
        if (is_array($severity)) {
            throw new InvalidArgumentException('Severity cannot be an array.');
        }

        // initialize notifications for the current severity, if necessary
        if (!array_key_exists($severity, $session->notifications)) {
            $session->notifications[$severity] = array();
        }

        // handle messages as an array or single item, as appropriate
        if (is_array($message)) {
            foreach ($message as $msg) {
                $session->notifications[$severity][] = $msg;
            }
        } else {
            $session->notifications[$severity][] = $message;
        }
    }

    /**
     * Retrieve current notifications.
     * If severity is specified, retrieve only the notifications at that severity.
     *
     * The default behaviour is to clear the notifications upon retrieval. Set
     * $clear to false to prohibit that.
     *
     * @param  string  $severity  Optional severity to retrieve.
     * @param  bool    $clear     Set to false to prohibit notification clearing.
     *                            Defaults to true.
     * @return  array  Retrieved notifications.
     */
    public static function fetch($severity = null, $clear = true)
    {
        if (!isset(static::_getSession()->notifications)) {
            static::_getSession()->notifications = array();
        }

        $notifications = static::_getSession()->notifications;
        if (!is_null($severity)) {
            if (!array_key_exists($severity, $notifications)) {
                $notifications = array();
            } else {
                $notifications = $notifications[$severity];
            }
        }

        if ($clear) {
            static::_resetNotifications($severity);
        }

        return $notifications;
    }

    /**
     * Reports whether there are any notifications.
     * If severity is specified, identifies whether any notifications exist
     * at that severity.
     *
     * @param   string  $severity  Optional severity to test.
     * @return  bool    Indicates whether any notifications exist.
     */
    public static function exist($severity = null)
    {
        return (bool) static::getCount($severity) > 0;
    }

    /**
     * Returns the count of current notifications.
     * If severity is specified, only count notifications at that severity.
     *
     * @param   string  $severity  Optional severity for notification counting.
     * @return  int     Total of defined notifications.
     */
    public static function getCount($severity = null)
    {
        $notifications = static::_getSession()->notifications;
        // if we are not yet initialized, the count is 0.
        if (!isset($notifications)) {
            return 0;
        }

        $total = 0;
        if (is_null($severity)) {
            foreach ($notifications as $aSeverity => $list) {
                $total += count($list);
            }
        } elseif (is_string($severity)) {
            if (array_key_exists($severity, $notifications)) {
                $total = count($notifications[$severity]);
            }
        }

        return $total;
    }

    /**
     * Reset/initialize the notifications. If a severity is provided,
     * reset/initialize only that severity.
     *
     * @param   string  $severity  Optional severity to reset/initialize.
     * @return  void
     */
    protected static function _resetNotifications($severity = null)
    {
        // initialize notification storage, if necessary
        if (!isset(static::_getSession()->notifications)) {
            static::_getSession()->notifications = array();
        }

        if (is_null($severity)) {
            static::_getSession()->notifications = array();
        } elseif (is_string($severity)) {
            static::_getSession()->notifications[$severity] = array();
        }
    }

    /**
     * Return the static session object, initializing if necessary.
     *
     * @return Zend_Session_Namespace
     */
    protected static function _getSession()
    {
        if (!static::$_session instanceof Zend_Session_Namespace) {
            static::$_session = new Zend_Session_Namespace('Notifications');
        }

        return static::$_session;
    }
}
