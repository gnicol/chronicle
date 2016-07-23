<?php
/**
 * Provides method for creating image drivers implementing P4Cms_Image_Driver_Interface.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Image_Driver_Factory
{
    /**
     * List of known image driver classes. This list (in order) will be used
     * by the create() method when asked for providing a default image driver.
     *
     * @var     array
     * @todo    make this more flexible such that third-party image drivers
     *          can appear in this list
     */
    protected static $_drivers = array(
        'P4Cms_Image_Driver_Imagick',
        'P4Cms_Image_Driver_Gd'
    );

    /**
     * Create and return a single instance of an image driver implementing
     * P4Cms_Image_Driver_Interface.
     *
     * @param   string  $className              optional - driver class name;
     *                                          if omitted, first driver that can be
     *                                          instantiated from the following list (in given
     *                                          order) will be returned: imagick, gmagick, gd
     * @return  P4Cms_Image_Driver_Interface    an image driver implementation
     * @throws  P4Cms_Image_Exception           if a given $className is not a valid driver class
     */
    public static function create($className = null)
    {
        // if driver class was provided, ensure its valid and return new driver instance
        if ($className) {
            if (!static::isValidType($className)) {
                throw new P4Cms_Image_Exception(
                    "Cannot create a driver: Class " . $className . " is not a valid image driver."
                );
            }

            return new $className;
        }

        // if no driver class was provided by user, try to return first driver we can create
        foreach (static::$_drivers as $driver) {
            try {
                return new $driver;
            } catch (P4Cms_Image_Exception $e) {
                // continue to try next driver
            }
        }

        // if we made it to this point, it means that user didn't provide any driver
        // and we are unable to create one from available candidates
        throw new P4Cms_Image_Exception("Cannot create image driver.");
    }

    /**
     * Determine if the given Image driver type is valid.
     *
     * @param   string  $className  the driver implementation class name to check
     * @return  bool    true if the given Driver class exists and implements
     *                  P4Cms_Image_Driver_Interface, false otherwise
     */
    public static function isValidType($className)
    {
        if (!class_exists($className)) {
            return false;
        }
        if (!in_array('P4Cms_Image_Driver_Interface', class_implements($className))) {
            return false;
        }

        return true;
    }

    /**
     * Return list of driver classes the factory is aware of.
     *
     * @return  array   list with available driver classes
     */
    public static function getDriverClasses()
    {
        return static::$_drivers;
    }

    /**
     * Private constructor. Prevents callers from creating a factory instance.
     */
    private function __construct()
    {
    }
}
