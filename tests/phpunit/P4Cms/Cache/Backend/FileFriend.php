<?php
/**
 * Provide a friend class to get options off of the file cache backend.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Cache_Backend_FileFriend extends Zend_Cache_Backend_File
{
    /**
     * Returns the requested options value or null.
     * 
     * @param   Zend_Cache_Backend  $instance   the instance to read options from
     * @param   string              $name       the options name
     * @return  mixed               the options value or null
     */
    public static function getOption($instance, $name)
    {
        if (!isset($instance->_options[$name])) {
            return null;
        }

        return $instance->_options[$name];
    }
}