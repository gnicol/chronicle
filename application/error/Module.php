<?php
/**
 * Register the error module as the error handler.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Error_Module extends P4Cms_Module_Integration
{
    /**
     * Reconfigure the error handler plugin.
     */
    public static function load()
    {
        $front = Zend_Controller_Front::getInstance();
        $front->registerPlugin(
            new Zend_Controller_Plugin_ErrorHandler(
                array(
                    'module'     => 'error',
                    'controller' => 'index',
                    'action'     => 'error',
                )
            ),
            100
        );
    }
}
