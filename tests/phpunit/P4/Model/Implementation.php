<?php
/**
 * An implementation of the model abstract class for testing.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Model_Implementation extends P4_ModelAbstract
{
    protected   $_fields    = array('foo' => null, 'bar' => null, 'baz' => null);

    /**
     * Get model fields.
     *
     * @return  array   list of field names.
     */
    public function getFields()
    {
        return array_keys($this->_fields);
    }

    /**
     * Get model field value.
     *
     * @param   string  $field  name of field to get value of.
     * @return  mixed   value of field.
     */
    public function getValue($field)
    {
        return isset($this->_fields[$field]) ? $this->_fields[$field] : null;
    }

    /**
     * Check if model has field.
     *
     * @param   string  $field  name of field to check for.
     * @return  bool    true if model has field; false otherwise.
     */
    public function hasField($field)
    {
        return array_key_exists($field, $this->_fields);
    }

    /**
     * Set model values.
     *
     * @param   array   $values     values to set on model.
     */
    public function setValues($values)
    {
        $this->_fields = $values;
    }
}
