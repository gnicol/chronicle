<?php
/**
 * Abstracts operations against Perforce depots.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Depot extends P4_Spec_PluralAbstract
{
    protected static    $_specType  = 'depot';
    protected static    $_idField   = 'Depot';

    protected static    $_accessors = array(
        'Owner'         => 'getOwner',
        'Date'          => 'getDate',
        'Description'   => 'getDescription',
        'Type'          => 'getType',
        'Address'       => 'getAddress',
        'Suffix'        => 'getSuffix',
        'Map'           => 'getMap',
    );
    protected static    $_mutators  = array(
        'Owner'         => 'setOwner',
        'Description'   => 'setDescription',
        'Type'          => 'setType',
        'Address'       => 'setAddress',
        'Suffix'        => 'setSuffix',
        'Map'           => 'setMap',
    );

    /**
     * Determine if the given depot id exists.
     *
     * @param   string                      $id             the id to check for.
     * @param   P4_Connection_Interface     $connection     optional - a specific connection
     *                                                      to use.
     * @return  bool                        true if the given id matches an existing depot.
     */
    public static function exists($id, P4_Connection_Interface $connection = null)
    {
        // check id for valid format
        if (!static::_isValidId($id)) {
            return false;
        }

        $depots = static::fetchAll(array(), $connection);
        $depot  = $depots->filter(static::$_idField, $id);

        return (bool) count($depots);
    }

    /**
     * Get the owner of this depot.
     *
     * @return  string|null     user who owns this record.
     */
    public function getOwner()
    {
        return $this->_getValue('Owner');
    }

    /**
     * Set the owner of this depot to passed value.
     *
     * @param   string|null                 $owner  a string containing username.
     * @return  P4_Depot                    provides a fluent interface.
     * @throws  InvalidArgumentException    owner is incorrect type.
     */
    public function setOwner($owner)
    {
        if (!is_string($owner) && !is_null($owner)) {
            throw new InvalidArgumentException('Owner must be a string or null.');
        }

        return $this->_setValue('Owner', $owner);
    }

    /**
     * Get the date that this specification was last modified.
     *
     * @return  string|null  Date/Time of last update, formatted "2009/11/23 12:57:06" or null
     */
    public function getDate()
    {
        return $this->_getValue('Date');
    }

    /**
     * Get the description for this depot.
     *
     * @return  string|null     description for this depot.
     */
    public function getDescription()
    {
        return $this->_getValue('Description');
    }

    /**
     * Set a description for this depot.
     *
     * @param   string|null                 $description    description for this depot.
     * @return  P4_Depot                    provides a fluent interface.
     * @throws  InvalidArgumentException    description is incorrect type.
     */
    public function setDescription($description)
    {
        if (!is_string($description) && !is_null($description)) {
            throw new InvalidArgumentException('Description must be a string or null.');
        }

        return $this->_setValue('Description', $description);
    }

    /**
     * Get type of this depot.
     * Will be one of: local/stream/remote/spec/archive.
     *
     * @return  string|null     description for this depot.
     */
    public function getType()
    {
        return $this->_getValue('Type');
    }

    /**
     * Set type for this depot.
     * See getType for available options.
     *
     * @param   string|null                 $type   type of this depot.
     * @return  P4_Depot                    provides a fluent interface.
     * @throws  InvalidArgumentException    description is incorrect type.
     */
    public function setType($type)
    {
        if (!is_string($type) && !is_null($type)) {
            throw new InvalidArgumentException('Type must be a string or null.');
        }

        return $this->_setValue('Type', $type);
    }

    /**
     * Get the address for this depot (for remote depots).
     *
     * @return  string|null     address for this depot.
     */
    public function getAddress()
    {
        return $this->_getValue('Address');
    }

    /**
     * Set address for this depot - for remote depots.
     *
     * @param   string|null                 $address    remote depot connection address.
     * @return  P4_Depot                    provides a fluent interface.
     * @throws  InvalidArgumentException    address is incorrect type.
     */
    public function setAddress($address)
    {
        if (!is_string($address) && !is_null($address)) {
            throw new InvalidArgumentException('Address must be a string or null.');
        }

        return $this->_setValue('Address', $address);
    }

    /**
     * Get suffix for the depot.
     *
     * @return  string|null     depot suffix (for spec depots).
     */
    public function getSuffix()
    {
        return $this->_getValue('Suffix');
    }

    /**
     * Set suffix for this depot - for spec depots.
     *
     * @param   string|null                 $suffix     suffix to be used for generated paths.
     * @return  P4_Depot                    provides a fluent interface.
     * @throws  InvalidArgumentException    suffix is incorrect type.
     */
    public function setSuffix($suffix)
    {
        if (!is_string($suffix) && !is_null($suffix)) {
            throw new InvalidArgumentException('Suffix must be a string or null.');
        }

        return $this->_setValue('Suffix', $suffix);
    }

    /**
     * Get map for the depot.
     *
     * @return  string|null     depot map.
     */
    public function getMap()
    {
        return $this->_getValue('Map');
    }

    /**
     * Set map for this depot.
     *
     * @param   string|null                 $map    depot map.
     * @return  P4_Depot                    provides a fluent interface.
     * @throws  InvalidArgumentException    map is incorrect type.
     */
    public function setMap($map)
    {
        if (!is_string($map) && !is_null($map)) {
            throw new InvalidArgumentException('Map must be a string or null.');
        }

        return $this->_setValue('Map', $map);
    }

    /**
     * Return empty set of flags for the spec list command as depots takes no arguments.
     *
     * @param   array   $options    array of options to augment fetch behavior.
     *                              see fetchAll for documented options.
     * @return  array   set of flags suitable for passing to spec list command.
     */
    protected static function _getFetchAllFlags($options)
    {
        return array();
    }

    /**
     * Given a spec entry from spec list output (p4 clients), produce
     * an instance of this spec with field values set where possible.
     *
     * @param   array                       $listEntry      a single spec entry from spec list output.
     * @param   array                       $flags          the flags that were used for this 'fetchAll' run.
     * @param   P4_Connection_Interface     $connection     a specific connection to use.
     * @return  P4_Client                   a (partially) populated instance of this spec class.
     */
    protected static function _fromSpecListEntry($listEntry, $flags, P4_Connection_Interface $connection)
    {
        // time is given as a long - unset to avoid figuring out timezone conversion
        unset($listEntry['time']);
        
        // some values are mapped differently in listEntry
        $listEntry['Depot']       = $listEntry['name'];
        $listEntry['Description'] = $listEntry['desc'];
        unset($listEntry['name']);
        unset($listEntry['desc']);

        return parent::_fromSpecListEntry($listEntry, $flags, $connection);
    }
}
