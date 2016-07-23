<?php
/**
 * Extended Zend_Filter_HtmlEntities to filter value by using PHP htmlspecialchars function.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_HtmlSpecialChars extends Zend_Filter_HtmlEntities
{
    /**
     * Overwrite parent method to convert applicable characters by using
     * htmlspecialchars function instead of htmlentities function.
     *
     * @param  string $value    Value to filter.
     * @return string           Filtered value.
     */
    public function filter($value)
    {
        return htmlspecialchars((string) $value, $this->getQuoteStyle(), $this->getEncoding(), $this->getDoubleQuote());
    }
}
