<?php
/**
 * Provides validator abstract with basic error message handling.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
abstract class P4_Validate_Abstract implements P4_Validate_Interface
{
    protected   $_value             = null;
    protected   $_messages          = array();
    protected   $_messageTemplates  = array();
    
    /**
     * Get errors for the most recent isValid() check.
     *
     * @return  array   list of error messages.
     */
    public function getMessages()
    {
        return $this->_messages;
    }

    /**
     * Get the message templates for this validator.
     * 
     * @return  array   list of error message templates.
     */
    public function getMessageTemplates()
    {
        return $this->_messageTemplates;
    }

    /**
     * Record an error detected during validation.
     * Replaces '%value%' with the value being validated.
     *
     * @param   string  $messageKey     the id of the message to add.
     */
    protected function _error($messageKey)
    {
        if (!array_key_exists($messageKey, $this->_messageTemplates)) {
            throw new InvalidArgumentException(
                "Cannot set error. Invalid message key given."
            );
        }

        // support %value% substitution.
        $value = is_object($this->_value) 
            ? get_class($this->_value)
            : (string) $this->_value;
        $message = $this->_messageTemplates[$messageKey];
        $message = str_replace('%value%', (string) $value, $message);

        $this->_messages[$messageKey] = $message;
    }

    /**
     * Sets the value being validated and clears the messages.
     *
     * @param   mixed   $value  the value being validated.
     */
    protected function _setValue($value)
    {
        $this->_value    = $value;
        $this->_messages = array();
    }
}
