<?php
/**
 * Stub to test module integration.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Independent_Module extends P4Cms_Module_Integration
{
    /**
     * A mock load method.
     *
     * @return  boolean  Indicates whether load was successful.
     */
    public static function load()
    {
        return true;
    }

    /**
     * A static module method for testing.
     *
     * @param   mixed  $input  Some input.
     * @return  mixed  returns whatever was passed
     */
    public static function returnInput($input)
    {
        return $input;
    }
}
