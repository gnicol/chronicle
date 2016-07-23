<?php
/**
 * An implementation of the model abstract class for testing.
 * Uses a static 'records' variable for temporary storage.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Model_Implementation extends P4Cms_Model
{
    public              $someMember         = 'some-value';
    protected static    $_fields            = array(
        'key',
        'foo'           => array(
            'accessor'  => 'getFoo',
            'mutator'   => 'setFoo'
        ),
        'bar'           => array(
            'accessor'  => 'getBar',
            'mutator'   => 'setBar'
        ),
        'baz'           => array(
            'accessor'  => 'getBaz',
            'mutator'   => 'setBaz'
        ),
        'noWrite'       => array(
            'accessor'  => 'getNoWrite',
            'readOnly'  => true
        )
    );
    protected static    $_idField           = 'key';
    private static      $_records           = array();
    private             $_fooPrefix         = null;
    private             $_bazSet            = false;

    /**
     * Check if a record exists with the given id.
     *
     * @param   mixed   $id     the id to check for.
     * @return  bool    true if the given id matches an existing record.
     */
    public static function exists($id)
    {
        try {
            static::fetch($id);
            return true;
        } catch (P4Cms_Model_NotFoundException $e) {
            return false;
        }
    }

    /**
     * Fetch a single model.
     *
     * @param   string      $id                 the id of the model to fetch.
     * @return  P4Cms_Model                     the first model matching key.
     * @throws  P4Cms_Model_NotFoundException   if the requested model can't be found.
     */
    public static function fetch($id)
    {
        foreach (static::$_records as $record) {
            if ($record->getId() == $id) {
                return $record;
            }
        }
        throw new P4Cms_Model_NotFoundException("Can't find matching model.");
    }

    /**
     * Fetch all models.
     *
     * @return  P4Cms_Model_Iterator    all models.
     */
    public static function fetchAll()
    {
        return new P4Cms_Model_Iterator(static::$_records);
    }

    /**
     * Add/update this record in (our temp) data store.
     */
    public function save()
    {
        if (P4Cms_Model_Implementation::exists($this->getId())) {
            $records = array();
            foreach (static::$_records as $record) {
                if ($record->getId() == $this->getId()) {
                    $records[] = $this;
                } else {
                    $records[] = $record;
                }
            }
            static::$_records = $records;
        } else {
            static::$_records[] = $this;
        }
    }

    /**
     * Remove this record from the (temp) data store.
     */
    public function delete()
    {
        $records = array();
        foreach (static::$_records as $record) {
            if ($record->getId() != $this->getId()) {
                $records[] = $record;
            }
        }
        static::$_records = $records;
    }

    /**
     * Clear all records.
     */
    public static function clearRecords()
    {
        static::$_records = array();
    }

    /**
     * Custom foo accessor so that we can test this capability.
     * Prepends foo with a prefix if set.
     *
     * @return string   the value of foo.
     */
    public function getFoo()
    {
        return $this->_fooPrefix . $this->_getValue('foo');
    }

    /**
     * Custom bar accessor so that we can test this capability.
     *
     * @return string   the value of bar.
     */
    public function getBar()
    {
        return $this->_getValue('bar');
    }

    /**
     * Custom baz accessor so that we can test this capability.
     *
     * @return string   the value of baz.
     */
    public function getBaz()
    {
        return $this->_getValue('baz');
    }

    /**
     * Custom noWrite accessor to provide a dynamic value based
     * on Bar/Baz.
     *
     * @return string   Combo of bar and baz with a / in the middle
     */
    public function getNoWrite()
    {
        return $this->getBar() . '/' . $this->getBaz();
    }

    /**
     * Set the prefix to use when getting foo.
     *
     * @param string $prefix    the prefix to use with foo.
     */
    public function setFooPrefix($prefix)
    {
        $this->_fooPrefix = $prefix;
    }

    /**
     * Set the foo field.
     *
     * @param   mixed   $value  the value to set in the field.
     */
    public function setFoo($value)
    {
        $this->_setValue('foo', $value);
    }

    /**
     * Set the bar field.
     *
     * @param   mixed   $value  the value to set in the field.
     */
    public function setBar($value)
    {
        $this->_setValue('bar', $value);
    }

    /**
     * Set the baz field and flag that it was set.
     *
     * @param   mixed   $value  the value to set in the field.
     */
    public function setBaz($value)
    {
        $this->_bazSet = true;
        $this->_setValue('baz', $value);
    }

    /**
     * Detect if baz was set via the custom mutator.
     *
     * @return  bool    true if baz set through mutator.
     */
    public function wasBazSet()
    {
        return $this->_bazSet;
    }

    /**
     * Clear the baz set flag.
     */
    public function clearBazSetFlag()
    {
        $this->_bazSet = false;
    }

    /**
     * Set raw values, bypassing mutators.
     *
     * @param  array  $values  Associative array of field values
     */
    public function setRawValues(array $values)
    {
        $this->_setValues($values);
    }
}
