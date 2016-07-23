<?php
/**
 * Extends P4Cms_Model class and provides in-memory storage of a record adapter
 * and records default adapter.
 *
 * It only provides methods to retrieve, store, check-for and clear a record adapter
 * and/or records default adapter; it doesn't provide any functionality for permanent
 * storage of P4Cms_Model objects.
 *
 * Constructor will set an adapter for the new instance if the caller
 * provides one or there is a default record adapter available.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_Connected extends P4Cms_Model
{
    protected           $_adapter           = null;
    protected static    $_defaultAdapter    = null;

    /**
     * We need a custom sleep to exclude the adapter property.
     * Adapter connection objects cannot be serialized.
     *
     * @return  array   list of properties to serialize
     */
    public function __sleep()
    {
        return array_diff(
            array_keys(get_object_vars($this)),
            array('_adapter')
        );
    }

    /**
     * Create a new model instance and (optionally) set the field values.
     * Extends parent to set the default adapter in the instance.
     *
     * @param   array                   $values     array of key/values to load into the model.
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     */
    public function __construct($values = null, P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use the default adapter.
        if (!$adapter && static::hasDefaultAdapter()) {
            $adapter = static::getDefaultAdapter();
        }

        // set the adapter if we have one.
        if ($adapter) {
            $this->setAdapter($adapter);
        }

        parent::__construct($values);
    }

    /**
     * Set the storage adapter to use when accessing records.
     *
     * @param   P4Cms_Record_Adapter    $adapter    the adapter to use for this instance.
     * @return  P4Cms_Record_Connected  provides fluent interface.
     */
    public function setAdapter(P4Cms_Record_Adapter $adapter)
    {
        $this->_adapter = $adapter;

        return $this;
    }

    /**
     * Get the storage adapter used by this instance when accessing records.
     *
     * @return  P4Cms_Record_Adapter    the adapter used by this instance.
     * @throws  P4Cms_Record_Exception  if the adapter was not set.
     */
    public function getAdapter()
    {
        if ($this->_adapter instanceof P4Cms_Record_Adapter) {
            return $this->_adapter;
        }

        throw new P4Cms_Record_Exception(
            "Cannot get storage adapter. Adapter has not been set."
        );
    }

    /**
     * Determine if a record adapter has been set.
     *
     * @return  bool    true if a record adapter has been set, false otherwise.
     */
    public function hasAdapter()
    {
        try {
            $this->getAdapter();
            return true;
        } catch (P4Cms_Record_Exception $e) {
            return false;
        }
    }

    /**
     * Clear this record's storage adapter. This is primarily for testing purposes.
     */
    public function clearAdapter()
    {
        $this->_adapter = null;
    }

    /**
     * Set the default storage adapter to use when accessing records.
     *
     * @param   P4Cms_Record_Adapter    $adapter    the adapter to use by default.
     */
    public static function setDefaultAdapter(P4Cms_Record_Adapter $adapter)
    {
        static::$_defaultAdapter = $adapter;
    }

    /**
     * Get the default storage adapter to use when accessing records.
     *
     * @return  P4Cms_Record_Adapter    the default storage adapter.
     */
    public static function getDefaultAdapter()
    {
        if (static::$_defaultAdapter instanceof P4Cms_Record_Adapter) {
            return static::$_defaultAdapter;
        }

        throw new P4Cms_Record_Exception(
            "Cannot get default storage adapter. Adapter has not been set."
        );
    }

    /**
     * Determine if a default storage adapter has been set.
     *
     * @return  bool    true if a default storage adapter has been set.
     */
    public static function hasDefaultAdapter()
    {
        try {
            static::getDefaultAdapter();
            return true;
        } catch (P4Cms_Record_Exception $e) {
            return false;
        }
    }

    /**
     * Clear the default record storage adapter. This is primarily for testing purposes.
     */
    public static function clearDefaultAdapter()
    {
        static::$_defaultAdapter = null;
    }
}