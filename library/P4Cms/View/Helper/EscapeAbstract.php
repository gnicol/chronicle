<?php
/**
 * Abstract view helper for escaping untrusted data before inserting them in the view.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
abstract class P4Cms_View_Helper_EscapeAbstract extends Zend_Dojo_View_Helper_Dojo
{
    // list of safe characters that will not be escaped
    protected $_safeChars  = array();

    // list of illegal characters (represented by their ordinal values)
    protected $_illegalOrd = array();

    /**
     * Encodes given value.
     * See _encodeChar() method for details how single characters are encoded.
     *
     * @param   string  $value      Value to encode.
     * @return  string              Encoded value.
     */
    protected function _encode($value)
    {
        $output  = '';
        $charset = $this->view->getEncoding();
        for ($i = 0; $i < iconv_strlen($value, $charset); $i++) {
            $char = iconv_substr($value, $i, 1, $charset);
            $output .= $this->_encodeChar($char);
        }

        return $output;
    }

    /**
     * Help function to detect if given ordinal value represents alphanumeric character.
     *
     * @param   int     $ordinalValue   Ordinal value to check.
     * @return  bool                    True if ordinal value represents alphanum char,
     *                                  false otherwise.
     */
    protected function _isAlnum($ordinalValue)
    {
        return ($ordinalValue >= 48 && $ordinalValue <= 57)
            || ($ordinalValue >= 65 && $ordinalValue <= 90)
            || ($ordinalValue >= 97 && $ordinalValue <= 122);
    }

    /**
     * Encode single character into a value that can be safely inserted
     * into the view.
     *
     * @param   string  $char   Character to encode.
     * @return  string          Encoded character.
     */
    protected function _encodeChar($char)
    {
        // if char is safe, return it
        if (in_array($char, $this->_safeChars)) {
            return $char;
        }

        // convert char to 4-byte
        $char4Byte = iconv($this->view->getEncoding(), "UTF-32LE", $char);

        // get the ordinal value of the character
        list(, $ordinalValue) = unpack("V", $char4Byte);

        // encode char
        if ($this->_isAlnum($ordinalValue)) {
            return $char;
        } else if (in_array($ordinalValue, $this->_illegalOrd)) {
            return " ";
        } else {
            return $this->_format($ordinalValue);
        }
    }

    /**
     * Formats character represented by its ordinal value into a string that can
     * be safely to inserted in the view.
     * Implemented by concrete class as it depends on the context (html attrib, js, css etc.).
     *
     * @param int $ordinalValue     Ordinal value to format.
     */
    abstract protected function _format($ordinalValue);
}
