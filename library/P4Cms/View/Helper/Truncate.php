<?php
/**
 * Limit the length of a string and (optionally) append a trailing string
 * when the length limit is exceeded.
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        add option to split on word boundaries.
 */
class P4Cms_View_Helper_Truncate extends Zend_Dojo_View_Helper_Dojo
{
    /**
     * Trims whitespace, truncates and appends given suffix if truncated.
     * Truncates on word boundary provided the truncated string contains whitespace.
     *
     * @param   string  $input          the string to truncate.
     * @param   int     $length         the limit of the output string (excluding trailing string).
     * @param   string  $trailing       optional - string to append if truncated.
     * @param   bool    $escapeOutput   optional - if true (by default) then output will be escaped
     * @return  string  the truncated string.
     */
    public function truncate($input, $length, $trailing = null, $escapeOutput = true)
    {
        $input  = trim($input);
        $output = $input;

        if (strlen($input) > $length) {
            $output = substr($input, 0, $length);
            if (preg_match('/\S/', $input[$length]) && preg_match('/\s/', $output)) {
                $output = preg_replace('/\S+$/', '', $output);
            }

            $output = rtrim($output) . $trailing;
        }

        return $escapeOutput ? $this->view->escape($output) : $output;
    }
}
