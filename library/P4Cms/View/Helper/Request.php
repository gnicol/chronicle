<?php
/**
 * View helper that returns an instance of the current request.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_Request
{
    /**
     * Get the current request.
     */
    public function request()
    {
        return Zend_Controller_Front::getInstance()->getRequest();
    }
}
