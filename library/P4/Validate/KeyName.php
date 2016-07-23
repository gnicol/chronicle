<?php
/**
 * Validates string for suitability as a Perforce key name.
 *
 * By default disallows:
 *  - whitespace
 *  - purely numeric names
 *  - revision characters ('#', '@')
 *  - wildcards ('*', '...')
 *  - slashes ('/')
 *  - non-printable characters
 *  - leading minus ('-')
 *
 * By default allows, but can block:
 *  - percent character ('%')
 *  - positional specifiers ('%%x')
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Validate_KeyName extends P4_Validate_Abstract
{
    const       INVALID_TYPE            = 'invalidType';
    const       IS_EMPTY                = 'isEmpty';
    const       IS_NUMERIC              = 'isNumeric';
    const       HAS_WHITESPACE          = 'hasSpaces';
    const       REVISION_CHARACTERS     = 'revision';
    const       WILDCARDS               = 'wildcards';
    const       LEADING_MINUS           = 'leadingMinus';
    const       UNPRINTABLE_CHARACTERS  = 'unprintable';
    const       SLASHES                 = 'slashes';
    const       COMMAS                  = 'commas';
    const       PERCENT                 = 'percent';
    const       POSITIONAL_SPECIFIERS   = 'positional';
    const       RELATIVE                = 'relative';

    protected   $_allowPurelyNumeric    = false;
    protected   $_allowPositional       = true;
    protected   $_allowPercent          = true;
    protected   $_allowSlashes          = false;
    protected   $_allowCommas           = true;
    protected   $_allowRelative         = true;
    protected   $_messageTemplates      = array(
        self::INVALID_TYPE              => "Invalid type given.",
        self::IS_EMPTY                  => "Is an empty string.",
        self::IS_NUMERIC                => "Purely numeric values are not allowed.",
        self::HAS_WHITESPACE            => "Whitespace is not permitted.",
        self::REVISION_CHARACTERS       => "Revision characters ('#', '@') are not permitted.",
        self::WILDCARDS                 => "Wildcards ('*', '...') are not permitted.",
        self::LEADING_MINUS             => "First character cannot be minus ('-').",
        self::UNPRINTABLE_CHARACTERS    => "Unprintable characters are not permitted.",
        self::SLASHES                   => "Slashes ('/') are not permitted.",
        self::COMMAS                    => "Commas (',') are not permitted.",
        self::PERCENT                   => "Percent ('%') is not permitted.",
        self::POSITIONAL_SPECIFIERS     => "Positional specifiers ('%%x') are not permitted.",
        self::RELATIVE                  => "Relative paths are not permitted."
    );

    /**
     * Checks if the given string is a valid perforce spec name.
     *
     * @param   string|int  $value  spec name value to validate.
     * @return  boolean     true if value is a valid spec name, false otherwise.
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        // permit ints if allowPurelyNumeric is true.
        if ($this->_allowPurelyNumeric && is_int($value)) {
            $value = (string) $value;
        }

        // test for valid type.
        if (!is_string($value)) {
            $this->_error(static::INVALID_TYPE);
            return false;
        }

        // test for unprintable characters.
        if (preg_match('/[\x00-\x1F\x80-\xFF]/', $value)) {
            $this->_error(static::UNPRINTABLE_CHARACTERS);
            return false;
        }

        // test for purely numeric name.
        if (!$this->_allowPurelyNumeric && preg_match('/^[0-9]+$/', $value)) {
            $this->_error(static::IS_NUMERIC);
            return false;
        }

        // test for empty value.
        if ($value === '') {
            $this->_error(static::IS_EMPTY);
            return false;
        }

        // test for whitespace.
        if (preg_match('/\s/', $value)) {
            $this->_error(static::HAS_WHITESPACE);
            return false;
        }

        // test for revision characters.
        if (preg_match('/@|#/', $value)) {
            $this->_error(static::REVISION_CHARACTERS);
            return false;
        }

        // test for wildcard characters.
        if (preg_match('/\*|\.\.\./', $value)) {
            $this->_error(static::WILDCARDS);
            return false;
        }

        // test for positional specifiers.
        if (!$this->_allowPositional && strpos($value, '%%') !== false) {
            $this->_error(static::POSITIONAL_SPECIFIERS);
            return false;
        }

        // test for percent character
        if (!$this->_allowPercent && strpos($value, '%') !== false) {
            $this->_error(static::PERCENT);
            return false;
        }

        // test for comma character
        if (!$this->_allowCommas && strpos($value, ',') !== false) {
            $this->_error(static::COMMAS);
            return false;
        }

        // test for leading minus ('-') character.
        if ($value[0] === "-") {
            $this->_error(static::LEADING_MINUS);
            return false;
        }

        // test for forward slash character.
        if (!$this->_allowSlashes && strpos($value, '/') !== false) {
            $this->_error(static::SLASHES);
            return false;
        }

        // If relative paths aren't allowed the following are blocked:
        //  two or more slashes after the first character
        //  containing '/./'
        //  containing '/../'
        //  ending in a slash
        //  ending in '/.'
        //  ending in '/..'
        if (!$this->_allowRelative && preg_match('#.+//|/\./|/\.\./|.+/$|/\.$|/\.\.$#', $value)) {
            $this->_error(static::RELATIVE);
            return false;
        }

        return true;
    }

    /**
     * Control if purely numeric key names are permitted
     * (values consisting of only characters 0-9).
     *
     * @param  bool  $allowed  pass true to allow purely numeric names, false to disallow.
     */
    public function allowPurelyNumeric($allowed)
    {
        $this->_allowPurelyNumeric = (bool) $allowed;
    }

    /**
     * Control if positional specifiers are permitted
     * (values containing '%%", as in '%%1').
     *
     * @param  bool  $allowed  pass true to allow positional specifiers, false to disallow.
     */
    public function allowPositional($allowed)
    {
        $this->_allowPositional = (bool) $allowed;
    }

    /**
     * Control if forward slashes '/' are permitted
     *
     * @param  bool  $allowed  pass true to allow forward slashes, false (default) to disallow.
     */
    public function allowSlashes($allowed)
    {
        $this->_allowSlashes = (bool) $allowed;
    }

    /**
     * Control if percent character '%' is permitted
     *
     * @param  bool  $allowed  pass true (default) to allow percent '%', false to disallow.
     */
    public function allowPercent($allowed)
    {
        $this->_allowPercent = (bool) $allowed;
    }

    /**
     * Control if comma character ',' is permitted
     *
     * @param  bool  $allowed  pass true (default) to allow commas ',', false to disallow.
     */
    public function allowCommas($allowed)
    {
        $this->_allowCommas = (bool) $allowed;
    }
}
