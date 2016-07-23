<?php
/**
 * View helper that displays stack traces without the source file.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Error_View_Helper_StackTrace extends Zend_View_Helper_Action
{
    /**
     * Format and return a stack trace for the given exception.
     * Excludes source file information and lines up numbers.
     *
     * @param   Exception   $e  the exception to produce a trace for.
     * @return  string      html presentation of the stack trace.
     */
    public function stackTrace($e)
    {
        $entries = explode("\n", $e->getTraceAsString());

        $output = "";
        foreach ($entries as $entry) {
            preg_match("/\#([0-9]+) ([^:\(]+)(?:\(([0-9]+)\))?(?:\: )?(.*)/", $entry, $matches);

            $depth = isset($matches[1]) ? $matches[1] : null;
            $line  = isset($matches[3]) && $matches[3] ? "(" . $matches[3] . ")" : null;
            $call  = isset($matches[4]) ? $matches[4] : null;

            // skip entries without call information (e.g. '{main}').
            if (!$call) {
                continue;
            }

            $output .= str_pad($this->view->escape($depth), 3, ' ', STR_PAD_LEFT) . ": ";
            $output .= str_pad($this->view->escape($line), 5, ' ', STR_PAD_LEFT) . " ";
            $output .= $this->view->escape($call);
            $output .= "\n";
        }

        return $output;
    }
}
