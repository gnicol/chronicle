<?php
/**
 * Provides a base implementation for data models that have
 * key/value pairs (fields). Each sub-class may define an initial
 * set of fields via the $_fields variable.
 *
 * Each field is defined by a name and a list of field properties
 * as key/value pairs. For example:
 *
 *      protected static $_fields = array(
 *          'foo' => array(
 *              'property1' => 'value1',
 *              'property2' => 'value2'
 *          )
 *      )
 *
 * If field has no properties, it can be defined in a shorter way
 * just by specifying field's name:
 *
 *      protected static $_fields = array('foo')
 *
 * Both methods shown above can be arbitrarily combined when
 * defining fields via $_fields variable.
 *
 * Field's property key must be a string where the following keys
 * are recognized:
 *
 *      'accessor' - value (string) specifies a name of the model's
 *                   method that will be used to retrieve field's
 *                   value;
 *                   accessor methods take no parameters and must
 *                   return a value
 *
 *      'mutator'  - value (string) specifies a name of the model's
 *                   method that will be used to set field's value;
 *                   mutator methods must accept a single value
 *                   parameter and it is recommended that mutator
 *                   methods return $this to provide a fluent interface
 *
 *      'default'  - value specifies default field's value (null
 *                   will be used if this property is not set)
 *
 *      'readOnly' - value (boolean) specifies if field is read only
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Model implements P4Cms_ModelInterface
{
    protected           $_values            = array();
    protected           $_id                = null;

    protected static    $_fields            = array();
    protected static    $_idField           = null;

    /**
     * Create a new model instance and (optionally) set the field values.
     *
     * @param   array   $values     associative array of keyed field values
     *                              to load into the model.
     */
    public function __construct($values = null)
    {
        // normalize fields definition
        $this->_normalizeFields();

        if (is_array($values)) {
            $this->setValues($values);
        }
    }

    /**
     * Creates and returns a new instance of this class.
     * Useful for working around PHP's lack of chaining off 'new'.
     *
     * @param   array           $values     associative array of keyed field
     *                                      values to load into the model.
     * @return  P4Cms_Model     new instance of this model class.
     */
    public static function create($values = null)
    {
        return new static($values);
    }

    /**
     * Permits field values to be accessed like public class members.
     * Is invoked when reading data from inaccessible members.
     *
     * @param   string  $field  the name of the field to get the value of.
     * @return  mixed   the value of the field.
     */
    public function __get($field)
    {
        if ($this->hasField($field)) {
            return $this->getValue($field);
        }

        return null;
    }

    /**
     * Permits field values to be set like public class members.
     * Is invoked when writing data to inaccessible members.
     *
     * @param   string  $field  the name of the field to set the value of.
     * @param   mixed   $value  the value to set.
     */
    public function __set($field, $value)
    {
        if ($this->hasField($field)) {
            $this->setValue($field, $value);
        } else {
            $this->{$field} = $value;
        }
    }

    /**
     * Allows isset() to be called on fields.
     * Is invoked when calling isset() or empty() on inaccessible members.
     *
     * @param   string  $field  the name of the field to call isset() on.
     */
    public function __isset($field)
    {
        $value = $this->getValue($field);
        return isset($value);
    }

    /**
     * Allows unset() to be called on fields.
     * Is invoked when calling unset() is used on inaccessible members.
     *
     * @param   string  $field  the name of the field to call unset() on.
     */
    public function __unset($field)
    {
        $this->unsetValue($field);
    }

    /**
     * Get the id of this record.
     *
     * @return  mixed   the value of the id field.
     */
    public function getId()
    {
        // if _idField is defined, return value of id field or null if unset.
        if (!empty(static::$_idField)) {
            if (array_key_exists(static::$_idField, $this->_values)) {
                return $this->_values[static::$_idField];
            } else {
                return null;
            }
        }

        // if we make it this far; no _idField is defined, use _id value
        return $this->_id;
    }

    /**
     * Set the id of this record.
     *
     * @param   mixed   $id     the value of the id of this record.
     * @return  P4Cms_Model     provides a fluent interface
     */
    public function setId($id)
    {
        // if _idField is defined, set value of id field.
        if (isset(static::$_idField)) {
            if ($this->isReadOnlyField(static::$_idField)) {
                throw new P4Cms_Model_Exception("Can't set the value of a read-only field");
            }

            $this->_values[static::$_idField] = $id;
        } else {
            $this->_id = $id;
        }

        return $this;
    }

    /**
     * Determine if this record has an id.
     *
     * @return  bool    true if the model has a non-empty id.
     */
    public function hasId()
    {
        return (bool) strlen($this->getId());
    }

    /**
     * Check if this model has a specific field.
     *
     * @param   string      $field  the field to check for the existence of.
     * @return  boolean     true if the model has the named field, false otherwise.
     */
    public function hasField($field)
    {
        return in_array($field, $this->getFields());
    }

    /**
     * Check if the specified field is read only.
     *
     * @param   string  $field  The field name to check
     * @return  bool    true if read only false otherwise
     */
    public function isReadOnlyField($field)
    {
        return (bool)$this->_getFieldProperty($field, 'readOnly');
    }

    /**
     * Get all of the model field names.
     *
     * @return  array   a list of field names for this model.
     */
    public function getFields()
    {
        $fields = array_flip($this->getDefinedFields())
                + $this->_values;

        // return field names.
        return array_keys($fields);
    }

    /**
     * Get the explicitly defined fields.
     *
     * @return  array   a list of this model's predefined field names
     */
    public function getDefinedFields()
    {
        $fields = array_keys(static::$_fields);

        // ensure id field is present if defined.
        if (!empty(static::$_idField) && !in_array(static::$_idField, $fields)) {
            array_unshift($fields, static::$_idField);
        }

        // return field names.
        return $fields;
    }

    /**
     * Get all of the model field values.
     *
     * @return  array   an associative array of field values.
     */
    public function getValues()
    {
        $values = array();
        foreach ($this->getFields() as $field) {
            $values[$field] = $this->getValue($field);
        }
        return $values;
    }

    /**
     * Set all of the model's values at once.
     *
     * @param   array|null  $values     associative array of field values or null to clear values.
     * @param   bool        $filter     optional - if true, ignores values for unknown fields.
     * @return  P4Cms_Model             provides a fluent interface
     */
    public function setValues($values, $filter = false)
    {
        if ($values === null) {
            $this->_values = array();
        }

        if (!is_array($values)) {
            throw new InvalidArgumentException(
                "Cannot set values. Values must be an array."
            );
        }

        foreach ($values as $field => $value) {

            // skip unknown fields if filter is set.
            if ($filter === true && !$this->hasField($field)) {
                continue;
            }

            // skip read only fields
            if ($this->isReadOnlyField($field)) {
                continue;
            }

            $this->setValue($field, $value);
        }

        return $this;
    }

    /**
     * Get a particular field value. Will route through custom
     * field accessor if one is defined.
     *
     * @param   string  $field  the name of the field to get the value of.
     * @return  mixed   the value of the field.
     * @throws  P4Cms_Model_Exception   if the field does not exist.
     */
    public function getValue($field)
    {
        // if an accessor is specified for this field, use it.
        $fieldAccessor = $this->_getFieldProperty($field, 'accessor');
        if ($fieldAccessor !== null) {
            return $this->{$fieldAccessor}();
        } else {
            return $this->_getValue($field);
        }
    }

    /**
     * Set a particular field value. Will route through custom
     * field mutator if one is defined.
     *
     * @param   string  $field          the name of the field to set the value of.
     * @param   mixed   $value          the value to set in the field.
     * @return  P4Cms_Model             provides a fluent interface
     * @throws  P4Cms_Model_Exception   if the field does not exist.
     */
    public function setValue($field, $value)
    {
        // if a mutator is specified for this field, use it.
        $fieldMutator = $this->_getFieldProperty($field, 'mutator');
        if ($fieldMutator !== null) {
            return $this->{$fieldMutator}($value);
        } else {
            return $this->_setValue($field, $value);
        }
    }

    /**
     * Unset a particular field value. Will route through custom
     * field mutator if one is defined. This will remove the field
     * from the fields list (@see getFields()), unless it is a 'defined'
     * field (@see getDefinedFields()).
     *
     * @param   string  $field          the name of the field to unset the value of.
     * @return  P4Cms_Model             provides a fluent interface
     * @throws  P4Cms_Model_Exception   if the field does not exist.
     */
    public function unsetValue($field)
    {
        $this->setValue($field, null);
        unset($this->_values[$field]);
    }

    /**
     * Get the model as an array.
     *
     * @return  array   the model data as an array.
     */
    public function toArray()
    {
        return $this->getValues();
    }

    /**
     * Generate a new model instance from an array.
     *
     * @param   array   $data   the data to populate the model with.
     * @return  P4Cms_Model     a new model instance with the given data.
     */
    public function fromArray($data)
    {
        $model = new static;
        return $model->setValues($data);
    }

    /**
     * Normalize fields definition such that after this operation, all fields
     * are defined as:
     *
     *    <field name> => <array with field properties>
     *
     * where properties array is blank for fields with no properties.
     */
    protected function _normalizeFields()
    {
        $normalizedFields = array();
        foreach (static::$_fields as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $normalizedFields[$value] = array();
            } else {
                $normalizedFields[$key] = $value;
            }
        }

        static::$_fields = $normalizedFields;
    }

    /**
     * Get value of given property from given field definition.
     *
     * @param   string      $field      name of field to get property value for.
     * @param   string      $property   name of field property to get value for.
     * @return  mixed|null              value for given field property or null
     *                                  if property has not been set.
     */
    protected function _getFieldProperty($field, $property)
    {
        return isset(static::$_fields[$field][$property])
            ? static::$_fields[$field][$property]
            : null;
    }

    /**
     * Get a raw field value. Does not use custom accessor methods.
     * If idField is specified; will utilize 'getId' function.
     *
     * @param   string  $field  the name of the field to get the value of.
     * @return  mixed   the value of the field.
     * @throws  P4Cms_Model_Exception   if the field does not exist.
     */
    protected function _getValue($field)
    {
        // if they are asking for the idField; route through getId
        if (isset(static::$_idField)
            && $field === static::$_idField) {
            return $this->getId();
        }

        // if field has an explicit value, return it.
        if (array_key_exists($field, $this->_values)) {
            return $this->_values[$field];
        }

        return $this->_getDefaultValue($field);
    }

    /**
     * Get all of the raw field values.
     *
     * @return  array   an associative array of raw values.
     */
    protected function _getValues()
    {
        $values = array();
        foreach ($this->getFields() as $field) {
            $values[$field] = $this->_getValue($field);
        }
        return $values;
    }

    /**
     * Get a default field value. Returns null if there is no default
     * value for the requested field.
     *
     * @param   string      $field  the name of the field to get the value of.
     * @return  mixed|null  the default value of the field or null if no
     *                      default value has been set.
     */
    protected function _getDefaultValue($field)
    {
        return $this->_getFieldProperty($field, 'default');
    }

    /**
     * Set raw field value. Does not use custom mutator methods.
     * If idField is specified; will utilize 'setId' function.
     *
     * @param   string  $field          the name of the field to set the value of.
     * @param   mixed   $value          the value to set in the field.
     * @return  P4Cms_Model             provides fluent interface.
     * @throws  P4Cms_Model_Exception   if the field does not exist.
     */
    protected function _setValue($field, $value)
    {
        if ($this->isReadOnlyField($field)) {
            throw new P4Cms_Model_Exception("Can't set the value of a read-only field");
        }

        // if they are setting the idField; route through setId
        if (isset(static::$_idField)
            && $field === static::$_idField) {
            return $this->setId($value);
        }

        $this->_values[$field] = $value;

        return $this;
    }

    /**
     * Set all of the model's raw values at once.
     * Does not use custom mutator methods.
     *
     * @param   array  $values  associative array of field values.
     * @return  P4Cms_Model     provides a fluent interface
     */
    protected function _setValues(array $values)
    {
        foreach ($values as $field => $value) {
            try {
                $this->_setValue($field, $value);
            } catch (P4Cms_Model_Exception $e) {
            }
        }

        return $this;
    }
}
