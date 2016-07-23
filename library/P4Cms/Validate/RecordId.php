<?php
/**
 * Validates string for suitability as a record id.
 * Disallows:
 *  - backslash ('\')
 *  - revision characters ('#', '@')
 *  - wildcards ('*', '...', '%%n')
 *  - percent ('%'
 *  - leading minus ('-foo')
 *  - trailing slash ('foo/')
 *  - empty strings ('')
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_RecordId extends Zend_Validate_Abstract
{
    const ILLEGAL_CHARACTERS        = 'illegalCharacters';
    const ILLEGAL_NO_SLASH          = 'illegalCharsNoSlash';
    const INVALID_TYPE              = 'invalidType';
    const LEADING_MINUS             = 'leadingMinus';
    const TRAILING_SLASH            = 'trailingSlash';
    const EMPTY_STRING              = 'emptyString';
    const THREE_DOT                 = 'threeDot';

    protected $_allowForwardSlash   = true;
    protected $_messageTemplates    = array(
        self::ILLEGAL_CHARACTERS    =>
            "Only '-', '/', '_', '.' and alpha-numeric characters are permitted in identifiers.",
        self::ILLEGAL_NO_SLASH      =>
            "Only '-', '_', '.' and alpha-numeric characters are permitted in identifiers.",
        self::INVALID_TYPE          =>
            "Only string and integer identifiers are permitted.",
        self::LEADING_MINUS         =>
            "Path components cannot begin with the minus character ('-').",
        self::TRAILING_SLASH        =>
            "Trailing slashes are not permitted in identifiers.",
        self::EMPTY_STRING          =>
            "Empty strings are not valid identifiers.",
        self::THREE_DOT             =>
            "Three or more consecutive dots are not permitted in identifiers.",
    );

    /**
     * Defined by Zend_Validate_Interface
     *
     * Checks if the given string is a valid record id.
     *
     * @param   string|int  $value  The value to validate.
     * @return  boolean     true if value is a valid category name, false otherwise.
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        // normalize ints to string.
        if (is_int($value)) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            $this->_error(self::INVALID_TYPE);
            return false;
        }

        if (!strlen($value)) {
            $this->_error(self::EMPTY_STRING);
            return false;
        }

        // check for illegal characters.
        // use a different pattern and error if forward slashes disallowed
        if ($this->allowForwardSlash()) {
            if (preg_match("/[^a-z0-9_\-\.\/]/i", $value)) {
                $this->_error(self::ILLEGAL_CHARACTERS);
                return false;
            }
        } else {
            if (preg_match("/[^a-z0-9_\-\.]/i", $value)) {
                $this->_error(self::ILLEGAL_NO_SLASH);
                return false;
            }
        }

        // test for leading minus ('-') character in path components
        if (preg_match(':(^|/)-:', $value)) {
            $this->_error(static::LEADING_MINUS);
            return false;
        }

        // test for trailing slash.
        if (substr($value, -1) === '/') {
            $this->_error(static::TRAILING_SLASH);
            return false;
        }

        // test for three or more dots
        if (preg_match('/\.\.\.+/', $value)) {
            $this->_error(self::THREE_DOT);
            return false;
        }

        return true;
    }

    /**
     * Returns the current setting for allowForwardSlash; default value is true.
     *
     * @return  bool    True if forward slashes are permitted, False otherwise
     */
    public function allowForwardSlash()
    {
        return (bool) $this->_allowForwardSlash;
    }

    /**
     * Controls whether or not forward slashes are permitted in the id.
     *
     * @param   bool    $allowed           True if forward slashes are permitted, False otherwise
     * @return  P4Cms_Validate_RecordId    To maintain a fluent interface
     */
    public function setAllowForwardSlash($allowed)
    {
        $this->_allowForwardSlash = (bool) $allowed;

        return $this;
    }
}
