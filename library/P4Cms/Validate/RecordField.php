<?php
/**
 * Validates string for suitability as a record field name.
 * The string must pass p4 attribute name validation.
 * Additionally, it cannot begin with an underscore as
 * this is reserved for record field metadata.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_RecordField extends P4_Validate_AttributeName
{
    const   LEADING_UNDERSCORE  = 'leadingUnderscore';

    /**
     * Add a message template upon instantiation.
     */
    public function __construct()
    {
        $message = "First character cannot be underscore ('_').";
        $this->_messageTemplates[self::LEADING_UNDERSCORE] = $message;
    }

    /**
     * Checks if the given string is a valid record field name.
     *
     * @param   string   $value     the value to validate.
     * @return  boolean  true if value is a valid field name, false otherwise.
     */
    public function isValid($value)
    {
        // test for leading underscore ('_') character.
        if ($value[0] === "_") {
            $this->_error(static::LEADING_UNDERSCORE);
            return false;
        }

        return parent::isValid($value);
    }
}
