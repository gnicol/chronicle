<?php
/**
 * Handles disconnecting from the browser to allow long-running code to complete behind the scenes.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Controller_Action_Helper_BrowserDisconnect extends P4Cms_Controller_Action_Helper_Redirector
{
    // The time limit for long-running tasks, in minutes
    protected   $_timeLimit = 10;

    /**
     * Disconnect from the browser to begin long-running tasks.
     *
     * @param   string  $redirectTarget The action to redirect the user to, if applicable.
     * @param   int     $timeLimit      The time limit for long-running tasks in minutes.
     */
    public function disconnect($redirectTarget = null, $timeLimit = null)
    {
        // set time limit for script execution
        $timeLimit = ($timeLimit) ?: $this->_timeLimit;
        set_time_limit(60 * $timeLimit);

        ignore_user_abort(true);

        // Clear output buffer and save output; while loop handles potential multiple levels of output buffering.
        $output = '';
        while (ob_get_level()) {
            $output.= ob_get_clean();
        }

        if ($redirectTarget) {
            $this->setExit(false);
            $this->gotoSimple($redirectTarget);
        }

        // Disable gzip compression in apache, as it can result in this request being buffered until it is complete,
        // regardless of other settings.
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', 1);
        }

        // If not redirecting, send appropriate headers and output.
        header('Connection: close');
        header('Content-length: ' . strlen($output));

        $this->getResponse()->sendHeaders();
        $this->getResponse()->clearHeaders();

        echo $output;

        session_write_close();
        flush();
    }
}
