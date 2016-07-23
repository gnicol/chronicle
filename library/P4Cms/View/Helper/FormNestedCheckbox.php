<?php
/**
 * Derivative of zend form multi checkbox view helper.
 *
 * This version leverages the multi checkbox to do the actual input rendering but adds
 * on nesting via UL's.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_FormNestedCheckbox extends Zend_View_Helper_FormMultiCheckbox
{
    /**
     * Generates a set of nested checkbox button elements.
     *
     * @param string|array $name If a string, the element name.  If an array,
     *                           all other parameters are ignored, and the
     *                           array elements are extracted in place of
     *                           added parameters.
     * @param mixed $values      The checkbox value(s) to mark as 'checked'.
     * @param array $attribs     Optional HTML attributes for checkbox
     * @param array $options     A multidimensional array of key-value pairs
     *                           where the array key is the checkbox value,
     *                           and the array value is the checkbox text. For
     *                           nesting entries, the key is ignored and the
     *                           value is an array of further entries and/or
     *                           nesting levels.
     * @param int   $depth       optional - how far we've recursed.
     * @return string The checkbox XHTML.
     */
    public function formNestedCheckbox(
        $name,
        $values  = null,
        $attribs = null,
        $options = null,
        $depth   = 0
    )
    {
        // if set (and it appears to happen) attribs listsep will over-ride our passed
        // value. Eliminate the possibility.
        unset($attribs['listsep']);

        $classes    = !$depth ? "nested-checkbox" : "";
        $classes   .= !$depth && empty($options) ? " empty-checkbox" : "";
        $html       = "<ul" . ($classes ? " class=\"" . $classes . "\"" : "") . ">\n";
        $entries    = array();

        foreach ($options ?: array() as $checkboxValue => $value) {
            // recurse if the entries value is itself an array
            if (is_array($value)) {
                // first output any entries we have collected at the current level and clear cache
                if (!empty($entries)) {
                   $html .= $this->formCheckbox($name, $values, $attribs, $entries);
                }
                $entries = array();

                // handle the new nesting level
                $html .= $this->formNestedCheckbox($name, $values, $attribs, $value, $depth + 1);

                continue;
            }

            // cache out this entry, it will be output pre-recursion or at the end
            $entries[$checkboxValue] = $value;
        }

        // output any left-over entries that weren't caught by recursion
        if (!empty($entries)) {
            $html .= $this->formCheckbox($name, $values, $attribs, $entries);
        }

        // if there are no entries, output empty text if set.
        if (!$depth && empty($options) && isset($attribs['emptyText'])) {
            $html .= "<li>" . $this->view->escape($attribs['emptyText']) . "</li>";
        }

        $html .= "</ul>";

        return $html;
    }

    /**
     * Modified version of parent's formRadio to support:
     *  - read-only checkboxes
     *  - supports element specific 'class' options
     *  - id is an md5 of the value for values over 32 characters
     *
     * @param   string|array    $name       If a string, the element name.
     *                                      If an array, all other parameters are
     *                                      ignored, and the array elements are
     *                                      extracted in place of added parameters.
     * @param   mixed           $value      The checkbox value to mark as 'checked'.
     * @param   array|string    $attribs    Attributes added to each checkbox.
     * @param   array           $options    An array of key-value pairs where the array
     *                                      key is the checkbox value, and the array value
     *                                      is the checkbox text.
     * @return  string          The checkbox XHTML.
     */
    public function formCheckbox($name, $value = null, $attribs = null, $options = null)
    {

        $info = $this->_getInfo($name, $value, $attribs, $options);
        extract($info); // name, value, attribs, options, disable

        // also pull out css class details
        $class = isset($attribs['class']) ? $attribs['class'] : null;

        // retrieve attributes for labels (prefixed with 'label_' or 'label')
        $labelAttribs = array();
        foreach ($attribs as $key => $val) {
            $tmp    = false;
            $keyLen = strlen($key);
            if ((6 < $keyLen) && (substr($key, 0, 6) == 'label_')) {
                $tmp = substr($key, 6);
            } elseif ((5 < $keyLen) && (substr($key, 0, 5) == 'label')) {
                $tmp = substr($key, 5);
            }

            if ($tmp) {
                // make sure first char is lowercase
                $tmp[0] = strtolower($tmp[0]);
                $labelAttribs[$tmp] = $val;
                unset($attribs[$key]);
            }
        }

        $labelPlacement = 'append';
        foreach ($labelAttribs as $key => $val) {
            switch (strtolower($key)) {
                case 'placement':
                    unset($labelAttribs[$key]);
                    $val = strtolower($val);
                    if (in_array($val, array('prepend', 'append'))) {
                        $labelPlacement = $val;
                    }
                    break;
            }
        }

        // the checkbox values and labels
        $options = (array) $options;

        // build the element
        $xhtml = '';
        $list  = array();

        // should the name affect an array collection?
        $name = $this->view->escape($name);
        if ($this->_isArray && ('[]' != substr($name, -2))) {
            $name .= '[]';
        }

        // ensure value is an array to allow matching multiple times
        $value = (array) $value;

        // XHTML or HTML end tag?
        $endTag = ' />';
        if (($this->view instanceof Zend_View_Abstract) && !$this->view->doctype()->isXhtml()) {
            $endTag= '>';
        }

        // Set up the filter - Alnum + hyphen + underscore
        require_once 'Zend/Filter/PregReplace.php';
        $pattern = @preg_match('/\pL/u', 'a')
            ? '/[^\p{L}\p{N}\-\_]/u'    // Unicode
            : '/[^a-zA-Z0-9\-\_]/';     // No Unicode
        $filter = new Zend_Filter_PregReplace($pattern, "");

        // normalize read-only option to an array.
        $readOnly = isset($attribs['readOnly']) && is_array($attribs['readOnly'])
            ? $attribs['readOnly']
            : array();
        unset($attribs['readOnly']);

        // add checkboxes to the list.
        foreach ($options as $optValue => $optLabel) {

            // Should the label be escaped?
            if ($escape) {
                $optLabel = $this->view->escape($optLabel);
            }

            // if class value is an array pull out our elements entry
            $optClass = $class;
            if (is_array($class)) {
                $optClass = isset($class[$optValue]) ? $class[$optValue] : null;
            }
            $labelAttribs['class'] = $attribs['class'] = $optClass;

            // is it disabled?
            $disabled = '';
            if (true === $disable) {
                $disabled = ' disabled="disabled"';
            } elseif (is_array($disable) && in_array($optValue, $disable)) {
                $disabled = ' disabled="disabled"';
            }

            // is it checked?
            $checked = '';
            if (in_array($optValue, $value)) {
                $checked = ' checked="checked"';
            }

            // is it read-only?
            // if so, render a hidden input so the value gets submitted,
            // but set the main input to be disabled so it looks right.
            $hiddenInput = '';
            if (in_array($optValue, $readOnly)) {
                $disabled    = ' disabled="disabled"';
                $hiddenInput = '<input type="' . $this->_inputType . '"'
                             . ' style="display: none;"'
                             . ' name="' . $name . '"'
                             . ' value="' . $this->view->escape($optValue) . '"'
                             . $checked
                             . $endTag;

                // clear the value so it is only output once.
                $optValue   = null;
            }

            // generate ID
            $optId = $id . '-' . (strlen($optValue) <= 32
                ? $filter->filter($optValue)
                : md5($optValue));

            // Wrap the checkboxes in labels
            $checkbox = '<label'
                      . $this->_htmlAttribs($labelAttribs) . ' for="' . $optId . '">'
                      . $hiddenInput
                      . (('prepend' == $labelPlacement) ? $optLabel : '')
                      . '<input type="' . $this->_inputType . '"'
                      . ' name="' . $name . '"'
                      . ' id="' . $optId . '"'
                      . ' value="' . $this->view->escape($optValue) . '"'
                      . $checked
                      . $disabled
                      . $this->_htmlAttribs($attribs)
                      . $endTag
                      . (('append' == $labelPlacement) ? $optLabel : '')
                      . '</label>';

            // Wrap the checkbox in an li
            $checkbox = '<li' . $this->_htmlAttribs(array('class' => $optClass)) . '>'
                      . $checkbox
                      . '</li>';

            // add to the array of checkboxes
            $list[] = $checkbox;
        }

        // done!
        $xhtml .= implode("\n", $list);

        return $xhtml;
    }
}
