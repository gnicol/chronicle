<?php
/**
 * Integrate the Setup module with the rest of the system.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Setup_Module extends P4Cms_Module_Integration
{
    /**
     * Perform early integration work (before load).
     */
    public static function init()
    {
        P4Cms_PubSub::subscribe('p4cms.system.info', 'Setup_Module', 'getSetupInformation');
    }

    /**
     * Adds a new system information module to the systemInformation list.
     * Shares information regarding the setup pre-requisites.
     *
     * @param array $systemInformation  The list of system information models to add to.
     */
    public static function getSetupInformation($systemInformation)
    {
        $sysinfo = new System_Model_Info();
        $sysinfo->setId('requirements');
        $sysinfo->title = 'Requirements Check';
        $sysinfo->order = 20;
        $sysinfo->view  = APPLICATION_PATH . '/setup/views/scripts/system-info.phtml';

        $systemInformation[] = $sysinfo;
    }
}
