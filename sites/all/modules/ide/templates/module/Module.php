<?php
/**
 * Integrate the {{package:label}} module with the rest of the application.
 *
 * @author      {{user:fullName}}
 * @version     1.0
 */
class {{package:namespace}}_Module extends P4Cms_Module_Integration
{
    /**
     * Perform early integration work (before load).
     */
    public static function init()
    {
        // connect to some event.
        //P4Cms_PubSub::subscribe('p4cms.some.event',
        //    function($arguments)
        //    {
        //        // participate.
        //    }
        //);
    }

    /**
     * Perform integration operations when the site is loaded.
     */
    public static function load()
    {
    }
}
