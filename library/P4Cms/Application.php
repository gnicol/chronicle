<?php
require_once 'Zend/Application.php';
require_once 'Zend/Application/Bootstrap/Bootstrapper.php';
require_once 'Zend/Application/Bootstrap/ResourceBootstrapper.php';
require_once 'Zend/Application/Bootstrap/BootstrapAbstract.php';
require_once 'Zend/Application/Bootstrap/Bootstrap.php';

/**
 * Extends Zend_Application to work without a config file.
 *
 * P4Cms_Application will look for a Bootstrap.php when options
 * don't explicitly specify one and attempt to load default options
 * from the Bootstrap class via getDefaultOptions().
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Application extends Zend_Application
{
    protected   $_configFile    = null;

    /**
     * Reimplement Zend_Application constructor to look for a Bootstrap.php
     * if one hasn't been specified and attempt to load default options from it.
     *
     * @param  string                   $environment  application environment.
     * @param  string|array|Zend_Config $options String path to configuration file,
     *                                           or array/Zend_Config of configuration options
     * @throws Zend_Application_Exception When invalid options are provided
     * @return void
     */
    public function __construct($environment, $options = null)
    {
        $this->_environment = (string) $environment;

        // if not already present, add P4Cms_Loader to the autoloader stack
        require_once "P4Cms/Loader.php";
        spl_autoload_register(array('P4Cms_Loader', 'autoload'));

        if ($options === null) {
            $options = array();
        } elseif (is_string($options)) {
            $this->_configFile = $options;
            try {
                $options = $this->_loadConfig($options);
            } catch (Zend_Config_Exception $e) {
                $options = array();
            }
        } elseif ($options instanceof Zend_Config) {
            $options = $options->toArray();
        } elseif (!is_array($options)) {
            throw new Zend_Application_Exception(
                "Invalid options provided; must be location of config file, " .
                "a config object, or an array"
            );
        }

        // look for a 'Bootstrap' class if nothing else has been specified.
        if (!isset($options['bootstrap']) && class_exists('Bootstrap')) {

            $options['bootstrap'] = "Bootstrap.php";

            // look for provision of default options in Bootstrap class
            if (method_exists('Bootstrap', 'getDefaultOptions')) {
                $defaults = Bootstrap::getDefaultOptions($environment);
                $options  = $this->mergeOptions($defaults, $options, true);
            }
        }

        $this->setOptions($options);
    }

    /**
     * Get the configuration file this application instance was first constructed
     * with. If no configuration file was used (e.g. an array or config object was
     * injected), throws an exception.
     *
     * @return  string                      the configuration file used to construct the application
     * @throws  Zend_Application_Exception  if no config file was used to construct the application
     */
    public function getConfigFile()
    {
        if (!$this->_configFile) {
            throw new Zend_Application_Exception(
                "Cannot get the configuration file. No file was passed to application constructor."
            );
        }

        return $this->_configFile;
    }

    /**
     * Merge options recursively
     * Extended to support caching of merged result in APC.
     *
     * @param   array   $array1     array to merge into
     * @param   mixed   $array2     array to merge from
     * @param   bool    $cache      enable caching of merged result (defaults to false)
     * @return  array   the merged result
     */
    public function mergeOptions(array $array1, $array2 = null, $cache = false)
    {
        // if we have apc and caller asked for caching, we'll attempt
        // to cache the merged options, if not, we'll just call parent.
        // no point caching if array2 is empty or not an array.
        if (!$cache || !is_array($array2) || empty($array2) || !extension_loaded('apc')) {
            return parent::mergeOptions($array1, $array2);
        }

        $cacheKey  = md5(serialize($array1) . serialize($array2));
        $cacheData = apc_fetch($cacheKey);
        if (is_array($cacheData)) {
            return $cacheData;
        }

        $merged = parent::mergeOptions($array1, $array2);
        apc_store($cacheKey, $merged);

        return $merged;
    }

    /**
     * Load configuration file of options.
     * Extends Zend's _loadConfig to add caching of config data when APC is enabled.
     *
     * @param   string  $file               the name of the configuration file to load
     * @throws  Zend_Application_Exception  When invalid configuration file is provided
     * @return  array                       the loaded configuration array
     */
    protected function _loadConfig($file)
    {
        // if we have apc and a readable file, we'll attempt to cache the config
        // if not, we'll just call parent and let it deal with everything.
        if (!extension_loaded('apc') || !is_readable($file)) {
            return parent::_loadConfig($file);
        }

        // check if we have a cached version of this config file.
        $cacheKey  = $file . "-" . $this->getEnvironment() . "-" . filemtime($file);
        $cacheData = apc_fetch($cacheKey);
        if (is_array($cacheData)) {
            return $cacheData;
        }

        // no cached copy, lets make one.
        // we catch exceptions here, otherwise we would fail to cache
        // invalid config files and would repeatedly try to parse them.
        try {
            $config = parent::_loadConfig($file);
        } catch (Zend_Config_Exception $e) {
            $config = array();
        }
        apc_store($cacheKey, $config);

        return $config;
    }
}
