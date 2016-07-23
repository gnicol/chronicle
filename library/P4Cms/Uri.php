<?php
/**
 * Extends Zend_Uri to provide additional utility methods.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
abstract class P4Cms_Uri extends Zend_Uri
{
    /**
     * Determine if the given uri is relative.
     *
     * @param   string  $uri    the uri to examine.
     * @return  boolean         true if the uri is relative.
     */
    public static function isRelativeUri($uri)
    {
        if (substr($uri, 0, 1) == "/") {
            return false;
        }
        if (static::hasScheme($uri)) {
            return false;
        }
        return true;
    }

    /**
     * Determines if the given uri has a scheme component
     * (e.g. http://).
     *
     * @param   string  $uri    the uri to examine.
     * @return  bool    true if the uri has a scheme.
     */
    public static function hasScheme($uri)
    {
        return (bool) preg_match("/^[a-zA-Z]+:\//", $uri);
    }
}
