<?php
/**
 * This model provides a way to register record implementations
 * (sub-classes) with the system as named record 'types' via
 * pub/sub:
 *
 *  p4cms.record.registeredTypes
 *
 * The subscribed callback should return one or more configured
 * P4Cms_Record_RegisteredType instances. Each registered type
 * needs a unique type identifier. It must also set the name of
 * the concrete record class it is representing.
 *
 * The primary goal of this infrastructure is to allow for a
 * record to be fetched given only a record type and entry id
 * (e.g. type: 'content', id: '123').
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_RegisteredType extends P4Cms_Model
{
    protected static    $_fields    = array(
        'recordClass'   => array(
            'accessor'  => 'getRecordClass',
            'mutator'   => 'setRecordClass'
        ),
        'uriCallback'   => array(
            'accessor'  => 'getUriCallback',
            'mutator'   => 'setUriCallback'
        )
    );

    /**
     * Get a specific record type.
     *
     * @param   string  $id                     the id of the type to get.
     * @return  P4Cms_Record_RegisteredType     the registered record type.
     * @throws  P4Cms_Model_NotFoundException   if an unknown type is requested.
     */
    public static function fetch($id)
    {
        $types = static::fetchAll();

        // verify requested type is registered.
        if (!isset($types[$id])) {
            throw new P4Cms_Model_NotFoundException(
                "Cannot fetch record type. The requested type is not registered."
            );
        }

        return $types[$id];
    }

    /**
     * Get all of the record types that have been registered.
     * Types that do not pass the isValid() test, are ignored.
     *
     * @return  P4Cms_Model_Iterator    all registered record types.
     *
     * @publishes   p4cms.record.registeredTypes
     *              Return a P4Cms_Record_RegisteredType (or array of Registered Types) to be
     *              included in the registered type fetchAll results. The last subscriber to return
     *              a given ID wins. If an ID is specified but the type is invalid, this is
     *              considered a request to remove any previously registered type with that ID.
     */
    public static function fetchAll()
    {
        $types    = new P4Cms_Model_Iterator;
        $feedback = P4Cms_PubSub::publish('p4cms.record.registeredTypes');
        foreach ($feedback as $providedTypes) {
            if (!is_array($providedTypes)) {
                $providedTypes = array($providedTypes);
            }

            foreach ($providedTypes as $type) {
                // skip clearly incorrect entries
                if (!$type instanceof P4Cms_Record_RegisteredType) {
                    continue;
                }

                // if they specified an id but the type is not valid
                // (e.g. does not specify a record class), consider this
                // a request to remove a previously registered type.
                if (!$type->isValid() && strlen($type->getId())) {
                    unset($types[$type->getId()]);
                }

                // regardless of id, skip over invalid types at this point
                if (!$type->isValid()) {
                    continue;
                }

                $types[$type->getId()] = $type;
            }
        }

        return $types;
    }

    /**
     * Get the record class name for this type.
     *
     * @return  string  the record class name for this type.
     */
    public function getRecordClass()
    {
        return $this->_getValue('recordClass');
    }

    /**
     * Set the record class name for this type.
     *
     * @param   string|P4Cms_Record|null        $recordClass    the class to use or null to clear.
     * @return  P4Cms_Record_RegisteredType     provides fluent interface.
     */
    public function setRecordClass($recordClass)
    {
        // normalize any passed objects to a string
        if (is_object($recordClass)) {
            $recordClass = get_class($recordClass);
        }

        // verify the passed class is of the correct heritage or null
        if ($recordClass != 'P4Cms_Record'
            && !is_subclass_of($recordClass, 'P4Cms_Record')
            && $recordClass !== null
        ) {
            throw new InvalidArgumentException(
                'Record class must be a string, null or P4Cms_Record instance.'
            );
        }

        $this->_setValue('recordClass', $recordClass);

        return $this;
    }

    /**
     * Get a specific record by id using the types record class.
     *
     * @param   string|int                      $id         the id of the record to fetch.
     * @param   P4Cms_Record_Query|array|null   $options    optional - query options to augment result.
     * @param   P4Cms_Record_Adapter            $adapter    optional - storage adapter to use.
     * @return  P4Cms_Record                    the requested record.
     * @throws  P4Cms_Record_NotFoundException  if the requested record can't be found
     * @throws  P4Cms_Model_Exception           if no record class has been set on this type
     */
    public function fetchRecord($id, $options = null, P4Cms_Record_Adapter $adapter = null)
    {
        if ($this->getRecordClass() === null) {
            throw new P4Cms_Model_Exception(
                'Cannot fetch entry, no record class has been specified.'
            );
        }

        return call_user_func_array(
            array($this->getRecordClass(), 'fetch'),
            array($id, $options, $adapter)
        );
    }

    /**
     * Verifies this type has specified a class and id.
     *
     * @return  bool    true if type is valid; false otherwise.
     */
    public function isValid()
    {
        return $this->getRecordClass() !== null && strlen($this->getId());
    }

    /**
     * Get the URI for the given id of this type.
     * If an action is provided, will return a URI to perform the given action.
     *
     * @param   int|string  $id         the id of the entry.
     * @param   string      $action     optional - action to perform - defaults to 'view'.
     * @param   array       $params     optional - additional params to add to the uri.
     * @return  string                  the uri of the content entry.
     */
    public function getUri($id, $action = 'view', $params = array())
    {
        return call_user_func($this->getUriCallback(), $id, $action, $params);
    }

    /**
     * Set the function to use when generating URI's for this type.
     *
     * @param   null|callback   $function   The callback function for URI generation. The
     *                                      function should expect three parameters:
     *                                      - $id      (Record ID)
     *                                      - $action  (string)
     *                                      - $params  (array)
     *                                      Returns a string (the uri).
     * @return  P4Cms_Record_RegisteredType provides fluent interface.
     */
    public function setUriCallback($function)
    {
        if (!is_callable($function) && $function !== null) {
            throw new InvalidArgumentException(
                'Cannot set URI callback. Expected a callable function or null.'
            );
        }

        return $this->_setValue('uriCallback', $function);
    }

    /**
     * Determines if a valid URI callback has been set.
     *
     * @return  bool    True if valid URI callback set, False otherwise.
     */
    public function hasUriCallback()
    {
        return is_callable($this->_getValue('uriCallback'));
    }


    /**
     * Returns the current URI callback if one has been set.
     *
     * @return  callback    The current URI callback.
     * @throws  P4Cms_Record_Exception     If no URI callback has been set.
     */
    public function getUriCallback()
    {
        if (!$this->hasUriCallback()) {
            throw new P4Cms_Record_Exception(
                'Cannot get URI callback, no URI callback has been set.'
            );
        }

        return $this->_getValue('uriCallback');
    }
}
