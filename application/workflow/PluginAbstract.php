<?php
/**
 * Base class for workflow plugins (conditions and actions).
 * This abstract class provides basic handling of options.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
abstract class Workflow_PluginAbstract
{
    protected   $_options       = array();

    /**
     * Create a new plugin instance and (optionally) set plugin options.
     *
     * @param   array   $options    options to set for this plugin.
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Get options attached to the plugin.
     *
     * @return  array   plugin options.
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Get single option.
     * 
     * @param   string      $key    key to get option value for.
     * @return  mixed|null  option with given key, or null if option not found.
     */
    public function getOption($key)
    {
        $key = (string) $key;
        return isset($this->_options[$key]) ? $this->_options[$key] : null;
    }

    /**
     * Set options for this plugin.
     *
     * @param   array   $options    plugin options to set.
     */
    public function setOptions(array $options)
    {
        $this->_options = $options;
    }
}