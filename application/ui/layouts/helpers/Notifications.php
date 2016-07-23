<?php
/**
 * View helper that renders the notification area.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Ui_View_Helper_Notifications extends Zend_View_Helper_Abstract
{
    /**
     * Render the notifications dijit.
     *
     * @param  string  $severity  The severity of notifications to render.
     *                            null means render all available notifications.
     */
    public function notifications($severity = null)
    {
        $html = "<div id=\"p4cms-ui-notices\">\n";
        $notifications = array();
        if (P4Cms_Notifications::exist()) {
            // Cancel page caching as a notification will be present
            if (P4Cms_Cache::canCache('page')) {
                P4Cms_Cache::getCache('page')->cancel();
            }

            $notifications = P4Cms_Notifications::fetch(null);
        }
        $severityList = array();
        if (isset($severity) and array_key_exists($severity, $notifications)) {
            $severityList[] = $severity;
        } else {
            $keys = array_keys($notifications);
            sort($keys);
            $severityList = array_unique(
                array_merge(array('error', 'warn', 'info'), $keys)
            );
        }

        foreach ($severityList as $aSeverity) {
            if (!array_key_exists($aSeverity, $notifications)) {
                continue;
            }
            foreach ($notifications[$aSeverity] as $message) {
                $escapedMessage = htmlspecialchars($message);
                $span = ($aSeverity == 'error') ? '' : '<span class="close">&times;</span>';
                $html .= <<<EOM
<div class="severity-$aSeverity" dojoType="p4cms.ui.Notice"
    message="$escapedMessage" severity="$aSeverity"
>
    <span class="message">$span$escapedMessage</span>
</div>
EOM;
            }
        }
        $html .= "</div>\n";

        return $html;
    }
}
