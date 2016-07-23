<?php
/**
 * Abstracts operations against Perforce counters.
 * 
 * As counters are quite volatile, this class performs no caching. Reading a counter
 * always queries perforce for the current value and setting a new value immediately 
 * stores to perforce.
 *  
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Counter extends P4_ConnectedAbstract
{
    const       FETCH_MAXIMUM   = 'maximum';
    
    protected   $_id            = null;
    
    /**
     * Get the id of this counter.
     *
     * @return  null|string     the id of this entry.
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Set the id of this counter. Id must be in a valid format or null.
     *
     * @param   null|string     $id     the id of this entry - pass null to clear.
     * @return  P4_Counter              provides a fluent interface
     * @throws  InvalidArgumentException    if id does not pass validation.
     */
    public function setId($id)
    {
        if ($id !== null && !static::_isValidId($id)) {
            throw new InvalidArgumentException("Cannot set id. Id is invalid.");
        }

        $this->_id = $id;
        
        return $this;
    }
    
    /**
     * Determine if the given counter id exists.
     *
     * @param   string                      $id             the id to check for.
     * @param   P4_Connection_Interface     $connection     optional - a specific connection to use.
     * @return  bool    true if the given id matches an existing counter.
     */
    public static function exists($id, P4_Connection_Interface $connection = null)
    {
        // check id for valid format
        if (!static::_isValidId($id)) {
            return false;
        }

        $counters = static::fetchAll(array(), $connection);

        return in_array($id, $counters->invoke('getId'));
    }
    
    /**
     * Get the requested counter from Perforce.
     *
     * @param   string                  $id         the id of the counter to fetch.
     * @param   P4_Connection_Interface $connection optional - a specific connection to use.
     * @return  P4_Counter              instace of the requested counter.
     * @throws  InvalidArgumentException    if invalid id is given.
     */
    public static function fetch($id, P4_Connection_Interface $connection = null)
    {
        // ensure a valid id is provided.
        if (!static::_isValidId($id)) {
            throw new InvalidArgumentException("Must supply a valid id to fetch.");
        }

        // if no connection given, use default.
        $connection = $connection ?: static::getDefaultConnection();

        // ensure id exists.
        if (!static::exists($id, $connection)) {
            throw new P4_Exception(
                "Cannot fetch counter. Counter does not exist."
            );
        }

        // construct counter instance.
        $counter = new static($connection);
        $counter->setId($id);

        return $counter;
    }
    
    /**
     * Get all Counters from Perforce.
     *
     * @param   array   $options    optional - array of options to augment fetch behavior.
     *                              supported options are:
     *                                   FETCH_MAXIMUM - set to integer value to limit to the first
     *                                                   'max' number of entries.
     *                                                   *Note: Limits imposed client side.
     *
     * @param   P4_Connection_Interface $connection  optional - a specific connection to use.
     * @return  P4_Iterator         all counters matching passed option(s).
     */
    public static function fetchAll($options = array(), P4_Connection_Interface $connection = null)
    {
        // if no connection given, use default.
        $connection = $connection ?: static::getDefaultConnection();

        // fetch all counters.
        $result = $connection->run('counters');

        // convert result data to counter objects.
        $counters = new P4_Iterator;
        $max      = array_key_exists(static::FETCH_MAXIMUM, $options) ? (int)$options[static::FETCH_MAXIMUM] : 0;
  
        foreach ($result->getData() as $data) {
            // populate a counter and add it to the iterator
            $counter = new static($connection);
            $counter->setId($data['counter']);

            $counters[] = $counter;

            // stop looping if we reach 'FETCH_MAXIMUM'
            if ($max > 0 && count($counters) == $max) {
                break;
            }
        }

        return $counters;
    }

    /**
     * Delete this counter entry.
     *
     * @param   bool    $force      optional - force delete the counter.
     * @return  P4_Counter          provides a fluent interface
     * @throws  P4_Exception        if no id has been set.
     */
    public function delete($force = false)
    {
        $id = $this->getId();
        if ($id === null) {
            throw new P4_Exception("Cannot delete. No id has been set.");
        }

        // ensure id exists.
        $connection = $this->getConnection();
        if (!static::exists($id, $connection)) {
            throw new P4_Exception(
                "Cannot delete counter. Counter does not exist."
            );
        }

        // setup counter command args.
        $params   = $force ? array('-f') : array();
        $params[] = "-d";
        $params[] = $id;

        $result = $connection->run('counter', $params);

        return $this;
    }

    /**
     * Get counter's value. There is no caching, the value is always read from
     * perforce.
     *
     * @return  mixed   the value of the counter.
     */
    public function getValue()
    {
        $id = $this->getId();
        $connection = $this->getConnection();

        // if the ID is not set or the ID doesn't exist in perforce, return null
        if ($id === null || !static::exists($id, $connection)) {
            return null;
        }
        
        $result = $connection->run('counter', $id);
        $data = $result->getData();

        return $data[0]['value'];
    }

    /**
     * Set counters value. The value will be immediately written to perforce.
     *
     * @param   mixed   $value  the value to set in the counter.
     * @param   bool    $force  optional - force set the counter.
     * @return  P4_Counter      provides a fluent interface
     * @throws  P4_Exception    if no Id has been set
     */
    public function setValue($value, $force = false)
    {
        $id = $this->getId();
        if ($id === null) {
            throw new P4_Exception("Cannot set value. No id has been set.");
        }
        
        // setup counter command args.
        $params   = $force ? array('-f') : array();
        $params[] = $id;
        $params[] = $value;

        $this->getConnection()->run('counter', $params);

        return $this;
    }
    
    /**
     * Increment counters value by 1. If the counter doesn't exist it will be
     * created and assigned the value 1.
     * The update is carried out atomically by the server.
     *
     * @return  string          The counters new value
     * @throws  P4_Exception    If the current value is non-numeric
     */
    public function increment()
    {
        $id = $this->getId();
        if ($id === null) {
            throw new P4_Exception("Cannot increment value. No id has been set.");
        }

        $result = $this->getConnection()->run('counter', array('-i', $id));
        $data   = $result->getData();

        return $data[0]['value'];
    }

    /**
     * Check if the given id is in a valid format.
     *
     * @param   string      $id     the id to check
     * @return  bool        true if id is valid, false otherwise
     */
    protected static function _isValidId($id)
    {
        $validator = new P4_Validate_CounterName;
        return $validator->isValid($id);
    }
}
