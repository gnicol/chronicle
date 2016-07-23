<?php
/**
 * Implements Zend_Filter_Interface to convert titles to ids
 * which are URL and Record friendly.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_TitleToId implements Zend_Filter_Interface
{
    /**
     * Convert a provided title into an acceptable id for use
     * in URLs or Records.
     *
     * @param   mixed   $value  The title to be filtered
     * @return  string  The id based on the provided title.
     */
    public function filter($value)
    {
        $value = strtolower((string)$value);
        $value = preg_replace('/[^a-z0-9]/', '-', $value);

        return trim($value, '-');
    }

}
