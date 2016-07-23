<?php
/**
 * View helper that returns an instance of the named module.
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_Module
{
    /**
     * Get an enabled module by name.
     *
     * @param   string  $name   the name of the module to fetch.
     */
    public function module($name)
    {
        $module = P4Cms_Module::fetch($name);
        if (!$module->isEnabled()) {
            throw new P4Cms_Module_Exception(
                "Cannot access $name module. Module is disabled."
            );
        }

        return $module;
    }
}
