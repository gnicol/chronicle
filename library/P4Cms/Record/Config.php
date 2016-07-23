<?php
/**
 * Provides consistent storage of configuration information.
 * Configuration is serialized to JSON for storage.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_Config extends P4Cms_Record
{
    protected static    $_fileContentField  = 'config';
    protected static    $_fields = array(
        'config'        => array(
            'accessor'  => 'getConfig',
            'mutator'   => 'setConfig'
        )
    );

    /**
     * Get the configuration object for this record.
     *
     * This returns a Zend_Config object which can contain any
     * configuration information the user of the record chooses
     * to store.
     *
     * @param  string  $key      Optional key to retrieve from the config
     * @param  string  $default  Optional default value if the $key value is null.
     * @return Zend_Config  the configuration object.
     */
    public function getConfig($key = null, $default = null)
    {
        $config = $this->_getValue('config');

        // convert config to zend_config if necessary.
        if (!$config instanceof Zend_Config) {
            $config = is_array($config) ? $config : array();
            $config = new Zend_Config($config, true);
            $this->_setValue('config', $config);
        }

        // if user requests a specific config option, return it.
        if (isset($key)) {
            return $config->get($key) ? $config->get($key) : $default;
        }

        return $config;
    }

    /**
     * Get the configuration object as an array.
     *
     * @return  array   the configuration in array form.
     */
    public function getConfigAsArray()
    {
        return $this->getConfig()->toArray();
    }

    /**
     * Set the configuration object for this record.
     * This does not save the configuration. You must
     * call save() to store the configuration persistently.
     *
     * @param   Zend_Config|array|null  $config     the config object or null to clear.
     * @return  P4Cms_Record_Config                 provides fluent interface.
     * @throws  InvalidArgumentException            if the config object is invalid.
     */
    public function setConfig($config)
    {
        if (is_array($config)) {
            $config = new Zend_Config($config);
        }

        if (!$config instanceof Zend_Config && $config !== null) {
            throw new InvalidArgumentException(
                "Cannot set configuration. Configuration is not a valid Zend_Config object."
            );
        }

        $this->_setValue('config', $config);

        return $this;
    }

    /**
     * Set the configuration from an array.
     *
     * @param   array   $config             the configuration to set in array form.
     * @return  P4Cms_Record_Config         provides fluent interface.
     * @throws  InvalidArgumentException    if the config argument is not an array.
     */
    public function setConfigFromArray($config)
    {
        if (!is_array($config)) {
            throw new InvalidArgumentException(
                "Cannot set configuration. Configuration is not an array."
            );
        }

        return $this->setConfig(new Zend_Config($config));
    }

    /**
     * Save this record.
     * Extends parent to convert Zend_Config to an array.
     *
     * @param   string  $description  optional - a description of the change.
     * @return  P4Cms_Record          provides a fluent interface
     */
    public function save($description = null)
    {
        // ensure config is in array form.
        $config = $this->_getValue('config');
        if ($config instanceof Zend_Config) {
            $this->_setValue('config', $config->toArray());
        }

        // let parent do the rest.
        parent::save($description);
        return $this;
    }
}
