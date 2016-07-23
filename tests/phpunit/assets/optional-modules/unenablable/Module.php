<?php
/**
 * Integrates module with system.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Unenablable_Module extends P4Cms_Module_Integration
{
    /**
     * Extend parent to force failure.
     *
     * @throws Exception    Always throws; for testing.
     */
    public static function enable()
    {
        throw new Exception('Enable was called, throwing');
    }
}
