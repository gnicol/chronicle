<?php
/**
 * Validates string for suitability as a CSS class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_CssClass extends Zend_Validate_Abstract
{
    const ILLEGAL_CHARACTERS    = 'illegalCharacters';
    const INVALID_TYPE          = 'invalidType';

    /**
     * Revise message templates upon instantiation.
     */
    public function __construct()
    {
        $message = "Only '-', '_' and alpha-numeric characters are permitted in CSS classes.";
        $this->_messageTemplates[static::ILLEGAL_CHARACTERS] = $message;
        $this->_messageTemplates[self::INVALID_TYPE]         = 'Invalid type given.';
    }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Checks if the given string is a valid CSS class.
     *
     * @param   string   $value     The value to validate.
     * @return  boolean  true if value is a valid CSS class, false otherwise.
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        // tolerate empty values
        if (!isset($value)) {
            return true;
        }

        // ensure that only string values are valid.
        if (is_numeric($value)) {
            $value = (string) $value;
        }
        if (!is_string($value)) {
            $this->_error(static::INVALID_TYPE);
            return false;
        }

        // validate permitted characters, but do not complain about unset values
        if (preg_match("/[^a-z0-9_\- ]/i", $value)) {
            $this->_error(self::ILLEGAL_CHARACTERS);
            return false;
        }

        return true;
    }
}
