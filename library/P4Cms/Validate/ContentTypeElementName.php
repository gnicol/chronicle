<?php
/**
 * Validates string for suitability as a content type element name.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_ContentTypeElementName extends P4Cms_Validate_RecordField
{
    const ILLEGAL_ELEMENT_NAME  = 'illegalElementName';
    const ZEND_FORM_EXCEPTION   = 'zendFormException';

    /**
     * Revised message templates upon instantiation.
     */
    public function __construct()
    {
        $message = "Only '_' and alphanumeric characters are permitted in element names.";
        $this->_messageTemplates[self::ILLEGAL_ELEMENT_NAME] = $message;
        $this->_messageTemplates[self::ZEND_FORM_EXCEPTION]  = 'Zend_Form failed to accept the field name.';
    }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Checks if the given element name conforms to Zend_Form naming.
     *
     * @param   string   $value  The value to validate.
     * @return  boolean  true if value is a valid content type element name, false otherwise.
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        // validate that the name contains something
        if (!isset($value) or !strlen($value)) {
            $this->_error(self::IS_EMPTY);
            return false;
        }

        // validate against Zend_Form_Element's filterName
        $element = null;
        try {
            $element = new Zend_Form_Element($value);
        } catch (Exception $e) {
            $this->_error(self::ZEND_FORM_EXCEPTION);
            return false;
        }
        if ($element->filterName($value) !== $value) {
            $this->_error(self::ILLEGAL_ELEMENT_NAME);
            return false;
        }

        return parent::isValid($value);
    }
}
