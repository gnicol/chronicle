<?php
/**
 * Provides password strength validation according
 * to server security requirements
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Validate_StrongPassword extends P4_Validate_Abstract
{
    const       WEAK_PASSWORD       = 'weakPassword';

    protected   $_messageTemplates  = null;
    
    /**
     * Initialize the message templates.
     */
    public function __construct()
    {
        $this->_messageTemplates = array(
            self::WEAK_PASSWORD =>
                "Passwords must be at least 8 characters long and contain "
              . "mixed case or both alphabetic and non-alphabetic characters."
        );
    }

    /**
     * Check if value is strong password according
     * to server requirements for strong password.
     *
     * @param string $value password
     * @return boolean
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        $conditionsMet = 0;
        if ($this->_containsUppercaseLetter($value)) {
            $conditionsMet++;
        }
        if ($this->_containsLowercaseLetter($value)) {
            $conditionsMet++;
        }
        if ($this->_containsNonAlphabetic($value)) {
            $conditionsMet++;
        }

        if (strlen($value) < 8 || $conditionsMet < 2) {
            $this->_error(self::WEAK_PASSWORD);
            return false;
        }

        return true;
    }

    /**
     * Return true if value contains at least one uppercase letter,
     * otherwise return false.
     *
     * @param string $value password
     * @return boolean
     */
    protected function _containsUppercaseLetter($value)
    {
        return preg_match("/[A-Z]+/", $value);
    }

    /**
     * Return true if value contains at least one lowercase letter,
     * otherwise return false.
     *
     * @param string $value password
     * @return boolean
     */
    protected function _containsLowercaseLetter($value)
    {
        return preg_match("/[a-z]+/", $value);
    }

    /**
     * Return true if value contains at least one nonalphabetic character,
     * otherwise return false.
     *
     * @param string $value password
     * @return boolean
     */
    protected function _containsNonAlphabetic($value)
    {
        return preg_match("/[^a-zA-Z]+/", $value);
    }
}
