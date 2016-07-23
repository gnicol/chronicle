<?php
/**
 * A mechanism for providing an adapter that will be loaded on-demand.
 * This is particularly useful if creating the adapter will have notable
 * expense you wish to avoid, or if you expect the adapter may change.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_DeferredAdapter extends P4Cms_Record_Adapter
{
    protected   $_callback  = null;

    /**
     * Create a new deferred adapter from the given callback.
     * A callback must be provided.
     *
     * @param   callable    $callback       the callback function to get the real adapter.
     * @throws  InvalidArgumentException    if the given callback is not callable.
     */
    public function __construct($callback)
    {
        $this->setCallback($callback);
    }

    /**
     * Set the callback to use to get the real adapter to use.
     *
     * @param   callable    $callback           the callback function to get the real adapter.
     *                                          the callback will be called with no arguments
     *                                          and must return an adapter instance
     * @return  P4Cms_Record_DeferredAdapter    provides fluent interface.
     * @throws  InvalidArgumentException        if the given callback is not callable.
     */
    public function setCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException(
                "Cannot set callback. Given callback is not callable."
            );
        }

        $this->_callback = $callback;

        return $this;
    }

    /**
     * Resolves the callback to a real adapter.
     *
     * @return  P4Cms_Record_Adapter    the real adapter to use.
     * @throws  P4Cms_Record_Exception  if callback fails to return a proper adapter.
     */
    public function getAdapter()
    {
        $adapter = call_user_func($this->_callback);

        if (!$adapter instanceof P4Cms_Record_Adapter) {
            throw new P4Cms_Record_Exception(
                "Cannot resolve deferred adapter. Callback failed to return a proper adapter."
            );
        }

        return $adapter;
    }

    /**
     * Override every method in the base class to call through to the real adapter.
     */

    // @codingStandardsIgnoreStart
    public function getConnection()                                     { return $this->getAdapter()->getConnection(); }
    public function setConnection($p4)                                  { return $this->getAdapter()->setConnection($p4); }
    public function getBasePath()                                       { return $this->getAdapter()->getBasePath(); }
    public function setBasePath($path)                                  { return $this->getAdapter()->setBasePath($path); }
    public function hasProperty($name)                                  { return $this->getAdapter()->hasProperty($name); }
    public function getProperty($name)                                  { return $this->getAdapter()->getProperty($name); }
    public function getProperties()                                     { return $this->getAdapter()->getProperties(); }
    public function setProperty($name, $value)                          { return $this->getAdapter()->setProperty($name, $value); }
    public function setProperties(array $properties)                    { return $this->getAdapter()->setProperties($properties); }
    public function beginBatch($description)                            { return $this->getAdapter()->beginBatch($description); }
    public function commitBatch($description = null, $options = null)   { return $this->getAdapter()->commitBatch($description, $options); }
    public function revertBatch()                                       { return $this->getAdapter()->revertBatch(); }
    public function getBatchId()                                        { return $this->getAdapter()->getBatchId(); }
    public function inBatch()                                           { return $this->getAdapter()->inBatch(); }
    public function setBatchDescription($description)                   { return $this->getAdapter()->setBatchDescription($description); }
    // @codingStandardsIgnoreEnd
}
