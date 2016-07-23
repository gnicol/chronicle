<?php
/**
 * Extended view helper for hidden form element to support rendering sequence
 * of hidden elements in the case of element's value is an array.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_FormHidden extends Zend_View_Helper_FormHidden
{
    /**
     * Override parent to support for array of values.
     * If $value is an array, then this method returns
     * list of hidden elements where the name will have
     * appended square brackets.
     * 
     * @param   string          $name       the element name
     * @param   string|array    $value      optional - the element value or list
     *                                      of values
     * @param   array           $attribs    optional - attributes for the element
     *
     * @return  string          A hidden element or a sequence of hidden elements.
     */
    protected function _hidden($name, $value = null, $attribs = null)
    {
        if (is_array($value)) {
            // for array values, remove id from attributes as there
            // would be multiple hidden elements with the same id
            unset($attribs['id']);

            // assemble list if hidden elements, one for each value
            $elementsList = array();
            foreach ($value as $singleValue) {
                $elementsList[] = parent::_hidden($name . '[]', $singleValue, $attribs);
            }

            return implode("\n", $elementsList);
        }

        return parent::_hidden($name, $value, $attribs);
    }
}