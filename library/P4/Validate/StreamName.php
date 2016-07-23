<?php
/**
 * Validates string for suitability as a Perforce stream name.
 * Extends spec-name to verify we lead with two slashes and contain a
 * third. Also toggles some default settings to match p4ds validation.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Validate_StreamName extends P4_Validate_KeyName
{
    protected   $_allowPositional = true;
    protected   $_allowSlashes    = true;
    protected   $_allowPercent    = false;
    protected   $_allowCommas     = false;
    protected   $_allowRelative   = false;


    const INVALID_FORMAT          = 'format';

    /**
     * Revised message templates upon instantiation.
     */
    public function __construct()
    {
        $message = "Value is not the correct format of '//depotname/string'";
        $this->_messageTemplates[self::INVALID_FORMAT] = $message;
    }

    /**
     * Checks if the given string is a valid perforce spec name.
     *
     * @param   string|int  $value  spec name value to validate.
     * @return  boolean     true if value is a valid spec name, false otherwise.
     */
    public function isValid($value)
    {
        if (!parent::isValid($value)) {
            return false;
        }

        // verify stream is in the format //depotname/string
        if (!preg_match('#^//[^/]+/[^/]+$#', $value)) {
            $this->_error(static::INVALID_FORMAT);
            return false;
        }

        return true;
    }
}
