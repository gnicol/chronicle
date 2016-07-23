<?php
/**
 * Flattens a multi-dimensional array using array notation
 * for the keys to represent the hierarchy.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        re-write using a DOM.
 */
class P4Cms_Filter_FlattenArray implements Zend_Filter_Interface
{
    /**
     * Flatten multi-dimensional input array using array notation.
     *
     * @param   array   $input  the multi-dimensional input array to flatten.
     * @return  array   the flattened output array.
     */
    public function filter($input)
    {
        // only flatten arrays.
        if (!is_array($input)) {
            throw new InvalidArgumentException("Cannot flatten. Value is not an array.");
        }
        
        $output = array();
        foreach ($input as $key => $value) {
            $this->_flatten($value, $key, $output);
        }

        return $output;
    }

    /**
     * Recursively flatten the given input array.
     *
     * @param   mixed   $input      a input variable to flatten.
     * @param   string  $prefix     the key prefix to use.
     * @param   array   &$output    the flattened output array (by reference).
     * @return  void
     */
    protected function _flatten($input, $prefix, array &$output = array())
    {
        if (!is_array($input)) {
            $output[$prefix] = $input;
            return;
        }
        foreach ($input as $key => $value) {
            $key = $prefix . "[" . $key . "]";
            if (is_array($value)) {
                $this->_flatten($value, $key, $output);
            } else {
                $output[$key] = $value;
            }
        }
    }
}
