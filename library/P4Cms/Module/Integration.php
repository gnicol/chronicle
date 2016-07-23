<?php
/**
 * Module integration classes provide a place to add functionality
 * and integrate a module with the rest of the system. To integrate a
 * module with the system, modules may extend the Integration class
 * and subscribe to topics via the PubSub component. For example:
 *
 *  P4Cms_PubSub::subscribe('p4cms.some.topic', <callback>);
 *
 * When other parts of the system publish to the topic, the module's
 * callback function will be invoked.
 *
 * Third-party modules may publish additional topics to define their
 * own integration points.
 *
 * All module integration classes should be static classes whose name
 * begins with the name of the module, followed by an underscore and
 * the word Module (e.g. 'Foo_Module').
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
abstract class P4Cms_Module_Integration
{
    /**
     * Enforce static class - prevent instantiation.
     *
     * @codeCoverageIgnore
     */
    private final function __construct()
    {
    }

    /**
     * When a module is to be activated, this method is called. Activation happens when a
     * module has been installed and a user now wants to use its services within the
     * application.
     */
    public static function enable()
    {
    }

    /**
     * When a module is to deactivated, this method is called. Deactivation happens when a
     * user no longer wants to use a module's services within the application.
     */
    public static function disable()
    {
    }

    /**
     * Init preceeds load.
     *
     * This method allows the module author to participate in the module
     * initialization process. Prior to calling this method, the package
     * system registers the module with the various components of the
     * application such as the autoloader, view, front-controller, etc.
     *
     * This method is intended for the module author to perform additional
     * registration operations such as subscribing to pub/sub topics. In
     * general, the initization phase is not intended for doing actual
     * work.
     *
     * Notes:
     *  - It is not safe to access other modules from init() as they
     *    are not guaranteed to be initialized yet.
     *  - Only core modules are initialized during setup.
     */
    public static function init()
    {
    }

    /**
     * Load follows init.
     *
     * This method allows the module author to perform additional work
     * during bootstrap, for example publishing pub/sub topics, interacting
     * with other modules, adding request-specific javascript to the page,
     * etc. Unlike init, this method is intended for doing actual work.
     *
     * Notes:
     *  - It is safe to reference other modules from load() as they are
     *    already initialized, but not necessarily 'loaded'.
     *  - Only a subset of core modules are loaded during setup.
     */
    public static function load()
    {
    }

    /**
     * Automatically proxy calls for non-existant methods to the package.
     *
     * @param   string  $name   the name of the method called.
     * @param   array   $args   the arguments to the method.
     * @return  mixed   the result of the method call.
     * @throws  BadMethodCallException  if the method doesn't exist.
     */
    public static function __callStatic($name, $args)
    {
        $class  = get_called_class();
        $module = substr($class, 0, strpos($class, '_'));
        if (P4Cms_Module::exists($module)) {
            $module = P4Cms_Module::fetch($module);
            if (method_exists($module, $name)) {
                return call_user_func_array(array($module, $name), $args);
            }
        }

        throw new BadMethodCallException(
            "Method " . $class . "::" . $name . " does not exist."
        );
    }
}
