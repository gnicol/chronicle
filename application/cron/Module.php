<?php
/**
 * Integrates cron module with the rest of the application.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Cron_Module extends P4Cms_Module_Integration
{
    /**
     * Integrate cron module with the rest of the application.
     */
    public static function load()
    {
        // remove cron entries from pull operations.
        P4Cms_PubSub::subscribe(
            'p4cms.site.branch.pull.groupPaths',
            function($paths, $source, $target, $result)
            {
                $paths->getPaths()->filter(
                    'depotFile',
                    $target->getId() . '/cron/',
                    array(
                        P4Cms_Model_Iterator::FILTER_STARTS_WITH,
                        P4Cms_Model_Iterator::FILTER_INVERSE
                    )
                );
            }
        );
    }
}