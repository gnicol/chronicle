<?php
/**
 * Provides a base for singular spec models such as protections,
 * triggers, typemap, etc. to extend.
 *
 * Keyed specs such as changes, jobs, users, etc. should extend
 * P4_Spec_KeyedAbstract.
 *
 * When extending this class, be sure to set the _specType to
 * the name of the Perforce Specification Type (e.g. protect,
 * typemap, etc.)
 *
 * To provide custom field accessor methods, add entries to the
 * _accessors array in the form of: '<field>' => '<function>'.
 * Accessor methods take no parameters and must return a value.
 *
 * Similarly, to provide custom field mutator methods, add
 * entries to the _mutators array in the same format. Mutator
 * methods must accept a single value parameter. It is recommended
 * that mutator methods return $this to provide a fluent interface.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_SpecAbstract extends P4_ModelAbstract
{

    // Must be set by implementor to spec name
    protected static    $_specType          = '';
    protected static    $_accessors         = array();
    protected static    $_mutators          = array();

    protected           $_values            = array();
    protected           $_needsPopulate     = false;
    protected           $_specDefinition    = null;

    /**
     * Get this spec from Perforce.
     * Creates a new spec instance and schedules a populate.
     *
     * @param   P4_Connection_Interface $connection optional - a specific connection to use.
     * @return  P4_Spec_PluralAbstract  instace of the requested entry.
     * @throws  InvalidArgumentException    if no id is given.
     */
    public static function fetch(P4_Connection_Interface $connection = null)
    {
        $spec = new static($connection);
        $spec->_deferPopulate();

        return $spec;
    }

    /**
     * Gets the definition of this specification from Perforce.
     *
     * The specification definition provides: field names,
     * field types, field options, preset values, comments, etc.
     *
     * Only fetches it once per instance. Additionally, the spec
     * definition object has a per-process (static) cache.
     *
     * @return  P4_Spec_Definition  instance containing details about this spec type.
     */
    public function getSpecDefinition()
    {
        // load the spec definition if we haven't already done so.
        if (!$this->_specDefinition instanceof P4_Spec_Definition) {
            $this->_specDefinition = P4_Spec_Definition::fetch(
                static::_getSpecType(),
                $this->getConnection()
            );
        }

        return $this->_specDefinition;
    }

    /**
     * Check if this spec has a particular field.
     *
     * @param   string      $field  the field to check for the existence of.
     * @return  boolean     true if the spec has the named field, false otherwise.
     */
    public function hasField($field)
    {
        return in_array((string)$field, $this->getFields());
    }

    /**
     * Get all of the spec field names.
     *
     * @return  array   a list of field names for this spec.
     */
    public function getFields()
    {
        $fields = $this->getSpecDefinition()->getFields();
        return array_keys($fields);
    }

    /**
     * Get all of the required fields.
     *
     * @return  array   a list of required fields for this spec.
     */
    public function getRequiredFields()
    {
        $fields = array();
        $spec   = $this->getSpecDefinition();
        foreach ($this->getFields() as $field) {
            if ($spec->isRequiredField($field)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Get all of the spec field values.
     * Uses custom accessors where available.
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
     * Set several of the spec's values at once.
     * Uses custom mutators where available.
     *
     * @param   array   $values     associative array of field values.
     * @return  P4_SpecAbstract     provides a fluent interface
     * @throws  InvalidArgumentException    if values is not an array.
     * @todo    Consider ignoring only missing fields and read-only field errors not all SpecExcep.
     */
    public function setValues($values)
    {
        if (!is_array($values)) {
            throw new InvalidArgumentException("Values must be passed as an array.");
        }

        foreach ($values as $field => $value) {
            try {
                $this->setValue($field, $value);
            } catch (P4_Spec_Exception $e) {
            }
        }

        return $this;
    }

    /**
     * Get field value. If a custom field accessor exists, it will be used.
     *
     * @param   string  $field  the name of the field to get the value of.
     * @return  mixed   the value of the field.
     * @throws  P4_Spec_Exception   if the field does not exist.
     */
    public function getValue($field)
    {
        // if field doesn't exist, throw exception.
        if (!$this->hasField($field)) {
            throw new P4_Spec_Exception("Can't get the value of a non-existant field.");
        }

        // if field has custom accessor, use it.
        if (isset(static::$_accessors[$field])) {
            return $this->{static::$_accessors[$field]}();
        }

        return $this->_getValue($field);
    }

    /**
     * Set field value. If a custom field mutator exists, it will be used.
     *
     * @param   string  $field  the name of the field to set the value of.
     * @param   mixed   $value  the value to set in the field.
     * @return  P4_SpecAbstract     provides a fluent interface
     * @throws  P4_Spec_Exception   if the field does not exist.
     */
    public function setValue($field, $value)
    {
        // if field doesn't exist, throw exception.
        if (!$this->hasField($field)) {
            throw new P4_Spec_Exception("Can't set the value of a non-existant field.");
        }

        // if field has custom mutator, use it.
        if (isset(static::$_mutators[$field])) {
            return $this->{static::$_mutators[$field]}($value);
        }

        $this->_setValue($field, $value);

        return $this;
    }

    /**
     * Save this spec to Perforce.
     *
     * @return  P4_SpecAbstract     provides a fluent interface
     */
    public function save()
    {
        // ensure all required fields have values.
        $this->_validateRequiredFields();

        $this->getConnection()->run(
            static::_getSpecType(),
            "-i",
            $this->_getValues()
        );

        // should re-populate (server may change values).
        $this->_deferPopulate(true);

        return $this;
    }

    /**
     * Get the type of this spec.
     *
     * @return  string  the name of this spec type.
     * @throws  P4_Spec_Exception   if the spec type is unset.
     */
    protected static function _getSpecType()
    {
        // if spec type not defined, throw.
        if (!is_string(static::$_specType) || !trim(static::$_specType)) {
           throw new P4_Spec_Exception('No type is defined for this specification.');
        }

        return static::$_specType;
    }

    /**
     * Get the values for this spec from Perforce and set them
     * in the instance. Won't clobber existing values.
     */
    protected function _populate()
    {
        // early exit if populate not needed.
        if (!$this->_needsPopulate) {
            return;
        }

        // get spec data from Perforce.
        $data = $this->_getSpecData();

        // ensure fields is an array.
        if (!is_array($data)) {
            throw new P4_Spec_Exception("Failed to populate spec. Perforce result invalid.");
        }

        // copy field values to instance without clobbering.
        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $this->_values)) {
                $this->_values[$key] = $value;
            }
        }

        // clear needs populate flag.
        $this->_needsPopulate = false;
    }

    /**
     * Schedule populate to run when data is requested (lazy-load).
     *
     * @param   bool    $reset  optionally clear instance values.
     */
    protected function _deferPopulate($reset = false)
    {
        $this->_needsPopulate = true;

        if ($reset) {
            $this->_values = array();
        }
    }

    /**
     * Get all of the raw field values.
     * DOES NOT use custom accessors.
     *
     * @return  array   an associative array of field values.
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
     * Get a field's raw value.
     *
     * @param   string  $field  the name of the field to get the value of.
     * @return  mixed   the value of the field.
     * @throws  P4_Spec_Exception   if the field does not exist.
     */
    protected function _getValue($field)
    {
        // if field doesn't exist, throw exception.
        if (!$this->hasField($field)) {
            throw new P4_Spec_Exception("Can't get the value of a non-existant field.");
        }

        // if field has not been set, populate.
        if (!array_key_exists($field, $this->_values)) {
            $this->_populate();
        }

        // if field has a value, return it.
        if (array_key_exists($field, $this->_values)) {
            return $this->_values[$field];
        }

        // get default value if field is required - return null for
        // optional fields so that they don't get values automatically.
        // optional field defaults are best handled by the server.
        if ($this->getSpecDefinition($this->getConnection())->isRequiredField($field)) {
            return $this->_getDefaultValue($field);
        } else {
            return null;
        }
    }

    /**
     * Get a field's default value.
     *
     * @param   string  $field  the name of the field to get the default value of.
     * @return  mixed   the default value of the field.
     */
    protected function _getDefaultValue($field)
    {
        $definition = $this->getSpecDefinition();
        $field      = $definition->getField($field);

        if (isset($field['default'])) {
            return $definition::expandDefault($field['default'], $this->getConnection());
        } else {
            return null;
        }
    }

    /**
     * Set a field's raw value.
     *
     * @param   string  $field  the name of the field to set the value of.
     * @param   mixed   $value  the value to set in the field.
     * @return  P4_SpecAbstract     provides a fluent interface
     * @throws  P4_Spec_Exception   if the field does not exist.
     */
    protected function _setValue($field, $value)
    {
        // if field doesn't exist, throw exception.
        if (!$this->hasField($field)) {
            throw new P4_Spec_Exception("Can't set the value of a non-existant field.");
        }

        // if field is read-only, throw exception.
        if ($this->getSpecDefinition()->isReadOnlyField($field)) {
            throw new P4_Spec_Exception("Can't set the value of a read-only field.");
        }

        $this->_values[$field] = $value;

        return $this;
    }

    /**
     * Set several of the spec's raw values at once.
     * DOES NOT use custom mutators.
     *
     * @param   array   $values     associative array of raw field values.
     * @return  P4_SpecAbstract     provides a fluent interface
     * @todo    As per setValues, consider handling exception eating differently
     */
    protected function _setValues($values)
    {
        foreach ($values as $field => $value) {
            if ($this->hasField($field)) {
                $this->_values[$field] = $value;
            }
        }

        return $this;
    }

    /**
     * Get raw spec data direct from Perforce. No caching involved.
     *
     * @return  array   $data   the raw spec output from Perforce.
     */
    protected function _getSpecData()
    {
        $result = $this->getConnection()->run(static::_getSpecType(), "-o");
        return $result->expandSequences()->getData(0);
    }

    /**
     * Ensure that all required fields have values.
     *
     * @param   array   $values     optional - set of values to validate against
     *                              defaults to instance values.
     * @throws  P4_Spec_Exception   if any required fields are missing values.
     */
    protected function _validateRequiredFields($values = null)
    {
        $values = (array) $values ?: $this->_getValues();

        // check that each required field has a value.
        foreach ($this->getRequiredFields() as $field) {

            $value = isset($values[$field]) ? $values[$field] : null;

            // in order to satisfy a required field, array values
            // must have elements and all values must have string length.
            if ((is_array($value) && !count($value)) || (!is_array($value) && !strlen($value))) {
                $missing[] = $field;
            }

        }

        if (isset($missing)) {
            throw new P4_Spec_Exception(
                "Cannot save spec. Missing required fields: " . implode(", ", $missing)
            );
        }
    }
}
