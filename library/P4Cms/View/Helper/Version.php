<?php
/**
 * Helper to display the application version
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        add option to return part of the version string (release, patchlevel, suppdate)
 */
class P4Cms_View_Helper_Version extends Zend_View_Helper_Abstract
{
    /**
     * Display the application version
     *
     * @return  string  The value of the constant
     */
    public function version()
    {
        return P4CMS_VERSION_RELEASE . '/' . P4CMS_VERSION_PATCHLEVEL;
    }
}

