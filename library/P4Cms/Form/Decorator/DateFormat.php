<?php
/**
 * Extends Zend_Dojo_Form_Decorator_Abstract to actually use the date format.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_Decorator_DateFormat extends Zend_Form_Decorator_Abstract
{
    const   DEFAULT_DATE_PATTERN = 'MMMM d, yyyy';

    /**
     * Render a formatted date.
     *
     * @param   string  $value  The date-time string to format.
     * @return  string  The formatted date.
     */
    public function render($value)
    {
        $value = strip_tags($value);

        if (empty($value)) {
            return $value;
        }

        // if the decorator has a date pattern set, use it;
        // otherwise, check if the element has a getDatePattern method;
        // fall back to the default date pattern if none given.
        $datePattern = $this->getOption('datePattern');
        if (!$datePattern) {
            if (($element = $this->getElement()) && method_exists($element, 'getDatePattern')) {
                $datePattern = $element->getDatePattern();
            }
            
            $datePattern = ($datePattern) ? $datePattern : self::DEFAULT_DATE_PATTERN;
        }

        $date = new Zend_Date(strtotime($value), Zend_Date::TIMESTAMP);
        return $date->toString($datePattern);
    }
}
