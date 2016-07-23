<?php
/**
 * Stub to test module integration.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Types_Module extends P4Cms_Module_Integration
{
    /**
     * Perform load work; register Content URI callback.
     */
    public static function load()
    {
        // Set the URI callback
        P4Cms_Content::setUriCallback(
            function($content, $action, $params)
            {
                $id = '';

                // if given valid content, add in the type id or content id as appropriate
                if ($content instanceof P4Cms_Content) {
                    if ($action == 'add') {
                        $id = $content->getValue(P4Cms_Content::TYPE_FIELD);
                    } else {
                        $id = $content->getId();
                    }
                }

                return $id . '/' . $action . '/' . implode('/', $params);
            }
        );
    }
}
