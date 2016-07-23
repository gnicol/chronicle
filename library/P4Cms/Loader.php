<?php
require_once "Zend/Loader.php";
require_once "Zend/Loader/Autoloader.php";

/**
 * Enhances Zend_Loader to provide a package registry and auto-loading
 * of 'special' module classes such as: controllers, forms, models, etc.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Loader
{
    protected static  $_packages = array();

    /**
     * Load classes via package registry and with knowledge of 'special'
     * module classes such as: controllers, forms, models, etc.
     *
     * @param   string          $class  the name of the class to load.
     * @return  string|false    class name on success - false on failure
     */
    public static function autoload($class)
    {
        // optimistic (registered package) case.
        $package = substr($class, 0, strpos($class, '_'));
        if (isset(static::$_packages[$package])) {
            $file = static::$_packages[$package] . '/'
                  . str_replace('_', '/', substr($class, strlen($package) + 1))
                  . '.php';

            // if file exists (via direct or 'special' mapping), include it.
            if (is_readable($file)
                || $file = static::_resolveSpecialClass($class, $package)
            ) {
                include $file;
                return $class;
            }
        }

        // general case: autodiscover the path from the class name
        $file = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
        self::_securityCheck($file);
        if (Zend_Loader::isReadable($file)) {
            include $file;
            return $class;
        }

        return false;
    }
    
    /**
     * Register a module path to be checked when auto-loading classes.
     *
     * @param   string  $namespace  the module namespace.
     * @param   string  $path       the absolute path to the module.
     */
    public static function addPackagePath($namespace, $path)
    {
        self::$_packages[$namespace] = $path;
    }

    /**
     * Set module paths to the given array of paths keyed on namespaces.
     *
     * @param   array   $paths  an array of module paths keyed on namespaces.
     */
    public static function setPackagePaths($paths)
    {
        self::$_packages = $paths;
    }

   /**
     * Get the set of registered module paths.
     *
     * @return  array   an array of registered module paths keyed on namespaces.
     */
    public static function getPackagePaths()
    {
        return self::$_packages;
    }

    /**
     * Find the path to a special class that resides in a registered package.
     *
     * @param   string          $class      the name of the class to resolve.
     * @param   string          $package    the name of the package.
     * @return  false|string    the path to the class, or false if unsuccessful.
     */
    protected static function _resolveSpecialClass($class, $package)
    {
        // map various class types to their associated sub-folders.
        $classTypes = array(
            "Acl"               => "acls",
            "Acl_Assert"        => "acls/asserts",
            "Controller_Helper" => "controllers/helpers",
            "Filter"            => "filters",
            "Form"              => "forms",
            "Form_Decorator"    => "forms/decorators",
            "Form_Element"      => "forms/elements",
            "Model"             => "models",
            "Test"              => "tests",
            "Validate"          => "validators",
            "View_Helper"       => "views/helpers"
        );

        // extract class prefix (sans package name) and suffix.
        $suffix = substr($class, strrpos($class, '_') + 1);
        $prefix = substr($class,  strpos($class, '_') + 1, -(strlen($suffix) + 1));

        // generate path according to class type:
        //      controllers - special case ending in 'Controller'
        //  special classes - map class prefix to path
        if (!$prefix && substr($suffix, -10) === "Controller") {
            $path = "controllers/" . $suffix;
        } else if (isset($classTypes[$prefix])) {
            $path = $classTypes[$prefix] . '/' . $suffix;
        } else {
            return false;
        }

        // return filename.
        $file = static::$_packages[$package] . '/' . $path . ".php";
        if (is_readable($file)) {
            return $file;
        } else {
            return false;
        }
    }

    /**
     * Ensure that filename does not contain exploits
     *
     * @param   string  $filename   the filename to check for exploits
     * @return  void
     * @throws  Zend_Exception      if the filename contains illegal characters
     */
    protected static function _securityCheck($filename)
    {
        if (preg_match('/[^a-z0-9\\/\\\\_.:-]/i', $filename)) {
            require_once 'Zend/Exception.php';
            throw new Zend_Exception('Security check: Illegal character in filename');
        }
    }
}
