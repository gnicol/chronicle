<?php
/**
 * Validates string for suitability as a category id.
 * Disallows backslash ('\'), revision characters ('#', '@'),
 * wildcards ('*', '...', '%%n'), and others ('_', '%', '.')
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        move is-empty and leading-slash tests up to record id.
 */
class P4Cms_Validate_CategoryId extends P4Cms_Validate_RecordId
{
    const LEADING_PERIOD = 'leadingPeriod';
    const LEADING_DASH   = 'leadingDash';
    const LEADING_SLASH  = 'leadingSlash';
    const RESERVED_ID    = 'reservedId';
    const INVALID_TYPE   = 'invalidType';
    const IS_EMPTY       = 'isEmpty';

    /**
     * Revise message templates upon instantiation.
     */
    public function __construct()
    {
        $message = "Only '-', '.' and alpha-numeric characters are permitted in category ids.";
        $this->_messageTemplates[self::ILLEGAL_CHARACTERS] = $message;

        $this->_messageTemplates[self::LEADING_PERIOD] = 'Leading periods are not permitted in category ids.';
        $this->_messageTemplates[self::LEADING_DASH]   = 'Leading dashes are not permitted in category ids.';
        $this->_messageTemplates[self::LEADING_SLASH]  = 'Leading slashes are not permitted in category ids.';
        $this->_messageTemplates[self::RESERVED_ID]    = 'Id is reserved for internal use.';
        $this->_messageTemplates[self::INVALID_TYPE]   = 'Invalid type given.';
        $this->_messageTemplates[self::IS_EMPTY]       = 'Is an empty string.';
    }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Checks if the given string is a valid site name.
     *
     * @param   string   $value     The value to validate.
     * @return  boolean  true if value is a valid category name, false otherwise.
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        // test for valid type.
        if (!is_string($value)) {
            $this->_error(static::INVALID_TYPE);
            return false;
        }

        // test for empty value.
        if ($value === '') {
            $this->_error(static::IS_EMPTY);
            return false;
        }

        // test for conflict with metadata file.
        if ($value == P4Cms_Categorization_CategoryAbstract::CATEGORY_FILENAME) {
            $this->_error(self::RESERVED_ID);
            return false;
        }

        // test for leading . or - which would break sorted output.
        if (isset($value[0])) {
            if (preg_match(':^\.+|/\.+:', $value)) {
                $this->_error(self::LEADING_PERIOD);
                return false;
            }
            if ($value[0] === '-') {
                $this->_error(self::LEADING_DASH);
                return false;
            }
            if ($value[0] === '/') {
                $this->_error(self::LEADING_SLASH);
                return false;
            }
        }

        return parent::isValid($value);
    }
}
