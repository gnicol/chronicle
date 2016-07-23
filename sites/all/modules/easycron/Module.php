<?php
/**
 * Sets up view to add easy cron script.
 *
 * The Easy Cron module runs the core Cron operation using normal page requests
 * instead of having to set up a crontab. The module inserts a small amount of
 * JavaScript to send Ajax requests on each page when it is enabled (by default,
 * the module is enabled.)
 *
 * The users should not notice any kind of delay or disruption when viewing the
 * site. However, it does have a performance impact to the server because every
 * page load will send a request to run the cron tasks.  The core Cron module
 * will decide if the tasks need to run upon each request.
 *
 * This module requires that the site gets regular traffic/visitors in order to
 * trigger the cron tasks.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class EasyCron_Module extends P4Cms_Module_Integration
{
    /**
     * Load the easy cron scripts into the head section of the page.
     */
    public static function load()
    {
        // add the script to send a cron run request.
        $view = Zend_Layout::getMvcInstance()->getView();
        $view->headScript()->appendScript(
            "
            // we want this to happen last, so we wait until the page
            // is otherwise loaded, then add 100ms for good measure.
            dojo.connect(dojo.body(), 'onload', function() {
                window.setTimeout(
                    function() {
                        dojo.xhrGet({
                            url:   p4cms.url({
                                module:     'cron',
                                background: true,
                                format:     'json'
                            })
                        });
                    },
                    100
                );
            });
"
        );
    }
}