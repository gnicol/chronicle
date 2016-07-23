<?php
/**
 * Provides an implementation of a non-hierarchical category for testing.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Categorization_Friend extends P4Cms_Categorization_CategoryAbstract
{
    /**
     * Specifies the root path for tag category entries.
     */
    protected static $_storageSubPath   = 'friends';

    /**
     * Specifies whether this category allows child categories.
     */
    protected static $_nestingAllowed = false;

    /**
     * This function provides the tests access to any protected static functions.
     *
     * @param   string  $function   Name of function to be called on this object
     * @param   array|string    $params     Paramater(s) to pass, optional
     * @return  mixed   Return result of called function, False on error
     */
    public static function callProtectedStaticFunc($function, $params = array())
    {
        if (!is_array($params)) {
            $params = array($params);
        }
        return call_user_func_array('static::'.$function, $params);
    }
}
