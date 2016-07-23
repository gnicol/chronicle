<?php
/**
 * Sets up view to add analytics code
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Analytics_Module extends P4Cms_Module_Integration
{

    /**
     * Load the analytics data into the head section of the page.
     */
    public static function load()
    {
        $module = P4Cms_Module::fetch('Analytics');
        $config = $module->getConfig();

        if (!$config->get('accountNumber')) {
            return false;
        }

        $template     = $module->getPath() . '/views/scripts/analytics-template.phtml';
        $originalView = Zend_Layout::getMvcInstance()->getView();

        // prepping a custom view, so we can render without influencing the rest of the page.
        $view         = clone $originalView;

        $view->setScriptPath(dirname($template));

        $view->accountNumber = $config->get('accountNumber');
        $customVars          = $config->get('customVars');
        $view->customVars    = ($customVars && is_object($customVars))
                             ? $customVars->toArray()
                             : array();

        // if user-specific information is required, fetch it and send to view script
        if (in_array('userId', $view->customVars) || in_array('userRole', $view->customVars)) {
            try {
                $user           = P4Cms_User::fetchActive();
                $view->userId   = $user->getId();
                $view->userRole = implode(', ', $user->getRoles()->invoke('getId'));
            }
            catch (Exception $e) {
                P4Cms_Log::logException('Failed to fetch active user.', $e);
            }
        }

        // render the script snippet and add to the page.
        $analyticsCode = $view->render(basename($template));
        $originalView->headScript()->appendScript($analyticsCode);

        // The contentId and contentType custom vars will only be included if the
        // p4cms.content.render.close topic is called.
        $function = function($html, $contentEntryViewHelper) use ($view)
        {
            $entry = $contentEntryViewHelper->getEntry();
            if ($entry != $view->contentEntry()->getDefaultEntry()) {
                return $html;
            }

            $customVariableCode = "var entries = dojo.query('[dojotype=p4cms.content.Entry]');\n"
                                . "if (entries.length === 1) {\n"
                                . "var entry = entries[0];\n";
            $customVariableCode .= (!in_array('contentId', $view->customVars))
                              ? : "_gaq.push(['_setCustomVar', 2, 'contentId', "
                                . "dojo.attr(entry, 'contentId')]);\n";

            $customVariableCode .= (!in_array('contentType', $view->customVars))
                              ? : "_gaq.push(['_setCustomVar', 3, 'contentType', "
                                . "dojo.attr(entry, 'contentType')]);\n";

            // additional trackPageview is required to record custom variables
            $customVariableCode .= "_gaq.push(['_trackPageview']);\n"
                                . "}\n";

            Zend_Layout::getMvcInstance()->getView()->headScript()->appendScript(
                $customVariableCode
            );

            return $html;
        };

        P4Cms_PubSub::subscribe('p4cms.content.render.close', $function);
    }
}