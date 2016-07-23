<?php
/**
 * Modules are containers for functionality. The module model
 * provides access to modules so that they can be listed and
 * interrogated. Module models are read-only.
 *
 * Modules may contain a static module class 'Module.php', (aka
 * the module integration class) which can provide functionality
 * and integrate the module with the rest of the system. The
 * module integration class name should be begin with the name of
 * the module, followed by an underscore and the word Module
 * (e.g. 'Foo_Module').
 *
 * To use modules, you are required to indicate where "core"
 * modules reside via the setCoreModulesPath() method.
 * Additional paths can be added via addPackagesPath(). The
 * order that paths are added is significant. If a module
 * exists in two or more paths, the path that was added last
 * takes precedence, unless it is a core module. Core modules
 * are always loaded from the core modules path.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Module extends P4Cms_PackageAbstract
{
    const               PACKAGE_FILENAME    = 'module.ini';
    const               CONFIG_RECORD_PATH  = 'config/module';

    protected           $_configRecord      = null;
    protected           $_configFolderName  = 'module';

    protected static    $_idField           = 'name';
    protected static    $_coreModulesPath   = null;
    protected static    $_packagesPaths     = array();
    protected static    $_configRecords     = null;
    protected static    $_isInitialized     = array();
    protected static    $_isLoaded          = array();
    protected static    $_dependencyTypes   = array('library', 'module');

    protected static    $_fields            = array(
        'name'              => array(
            'accessor'      => 'getName',
            'mutator'       => 'setName'
        ),
        'label'             => array(
            'accessor'      => 'getLabel',
            'mutator'       => 'readOnly'
        ),
        'core'              => array(
            'accessor'      => 'isCoreModule',
            'mutator'       => 'readOnly'
        ),
        'enabled'           => array(
            'accessor'      => 'isEnabled',
            'mutator'       => 'readOnly'
        ),
        'configurable'      => array(
            'accessor'      => 'isConfigurable',
            'mutator'       => 'readOnly'
        ),
        'configUri'         => array(
            'accessor'      => 'getConfigUri',
            'mutator'       => 'readOnly'
        ),
        'version'           => array(
            'accessor'      => 'getVersion',
            'mutator'       => 'readOnly'
        ),
        'description'       => array(
            'accessor'      => 'getDescription',
            'mutator'       => 'readOnly'
        ),
        'maintainerInfo'    => array(
            'accessor'      => 'getMaintainerInfo',
            'mutator'       => 'readOnly'
        ),
        'dependencies'      => array(
            'accessor'      => 'getDependencies',
            'mutator'       => 'readOnly'
        ),
        'tags'              => array(
            'accessor'      => 'getTags',
            'mutator'       => 'readOnly'
        )
    );

    /**
     * Throws read-only exceptions on behalf of most mutators.
     *
     * @throws  P4Cms_Module_Exception  Every time; configured for specific mutators.
     */
    public function readOnly()
    {
        throw new P4Cms_Module_Exception('P4Cms_Module is primarily a read-only class.');
    }

    /**
     * Set the full path to the package folder.
     * Extends parent to ensure that basename is valid in a class name.
     *
     * @param   string  $path           the full path to the package folder.
     * @return  P4Cms_PackageAbstract   provides fluent interface.
     */
    public function setPath($path)
    {
        // validate module name - only allow characters valid in a class name.
        if (preg_match('/[^a-z0-9\\/\\\\_.-]/i', basename($path))) {
            throw new P4Cms_Module_Exception(
                "Invalid module ('" . $name . "'). "
               . "The module name contains illegal characters."
           );
        }

        return parent::setPath($path);
    }

    /**
     * Get the name of this package.
     * Extends parent to upper-case the first letter.
     *
     * @return  string  the name of this package.
     */
    public function getName()
    {
        return ucfirst(parent::getName());
    }

    /**
     * Set the name of this package.
     *
     * @param   string  $name   The new name to use.
     * @return  P4Cms_Module    To maintain a fluent interface.
     */
    public function setName($name)
    {
        return $this->_setValue('name');
    }

    /**
     * Get the url to this module's resources folder.
     *
     * @return  string  the base url for this module's resources.
     */
    public function getBaseUrl()
    {
        return parent::getBaseUrl() . '/resources';
    }

    /**
     * Get any routes configured for this module.
     *
     * @return  array   list of routes from module.ini.
     */
    public function getRoutes()
    {
        $info = $this->getPackageInfo();
        return isset($info['routes']) && is_array($info['routes']) ? $info['routes'] : array();
    }

    /**
     * Get all core modules.
     *
     * @return  P4Cms_Model_Iterator    all core modules.
     */
    public static function fetchAllCore()
    {
        $modules = new P4Cms_Model_Iterator;
        foreach (static::fetchAll() as $module) {
            if ($module->isCoreModule()) {
                $modules[] = $module;
            }
        }
        return $modules;
    }

    /**
     * Get all modules that are enabled - includes core modules
     * unless $excludeCoreModules is set to true.
     *
     * @param   bool    $excludeCoreModules optional - defaults to false - set to true to
     *                                      exclude core modules from the set of returned modules.
     * @return  P4Cms_Model_Iterator        all enabled modules.
     */
    public static function fetchAllEnabled($excludeCoreModules = false)
    {
        $modules = new P4Cms_Model_Iterator;
        foreach (static::fetchAll() as $module) {
            if ((!$module->isCoreModule() || !$excludeCoreModules) && $module->isEnabled()) {
                $modules[] = $module;
            }
        }
        return $modules;
    }

    /**
     * Get all modules that are disabled.
     *
     * @return  P4Cms_Model_Iterator    all disabled modules.
     */
    public static function fetchAllDisabled()
    {
        $modules = new P4Cms_Model_Iterator;
        foreach (static::fetchAll() as $module) {
            if (!$module->isEnabled()) {
                $modules[] = $module;
            }
        }
        return $modules;
    }

    /**
     * Enable this module for the current site.
     *
     * @return  P4Cms_Module            provides a fluent interface.
     * @throws  P4Cms_Module_Exception  if the module cannot be enabled.
     */
    public function enable()
    {
        // ensure module is disabled.
        $this->_ensureDisabled();

        // ensure dependencies are satisfied.
        if (!$this->areDependenciesSatisfied()) {
            throw new P4Cms_Module_Exception("Can't enable the '" . $this->getName()
                . "' module. One or more dependencies are not satisfied.");
        }

        // enable module by saving its configuration.
        // this will restore the previous config if one exists.
        $this->saveConfig(null, "Enabled '" . $this->getName() . "' module.");

        // initialize the module in the environment.
        $this->init();
        try {
            if ($this->hasIntegrationMethod('enable')) {
                $this->callIntegrationMethod('enable');
            }
        } catch (Exception $e) {
            $message = "Failed to enable the '" . $this->getName() . "' module.";
            P4Cms_Log::logException($message, $e);

            $this->disable();
            throw new P4Cms_Module_Exception($message);
        }

        return $this;
    }

    /**
     * Disable this module.
     *
     * @return  P4Cms_Module            provides fluent interface.
     * @throws  P4Cms_Module_Exception  if the module cannot be disabled.
     */
    public function disable()
    {
        // ensure module is enabled to begin with.
        $this->_ensureEnabled();

        // ensure module is not a core module.
        if ($this->isCoreModule()) {
            throw new P4Cms_Module_Exception("The '" . $this->getName()
                . "' module is a core module. Core modules cannot be disabled.");
        }

        // ensure dependencies are respected.
        if ($this->hasDependents()) {
            throw new P4Cms_Module_Exception("Can't disable the '" . $this->getName()
                . "' module. One or more enabled modules depend upon it.");
        }

        // call the 'disable' integration method if defined.
        if ($this->hasIntegrationMethod('disable')) {
            $this->callIntegrationMethod('disable');
        }

        // disable module by deleting config record.
        if ($this->_hasStoredConfigRecord()) {
            $this->_getConfigRecord()->delete("Disabled '" . $this->getName() . "' module.");
            unset(static::$_configRecords[$this->getName()]);
        }

        return $this;
    }

    /**
     * Check if this module is enabled.
     * A module is considered enabled if it has a config record in storage.
     *
     * @return  boolean     true if the module is enabled.
     */
    public function isEnabled()
    {
        // core modules are always enabled.
        if ($this->isCoreModule()) {
            return true;
        }

        // check for stored config record.
        return $this->_hasStoredConfigRecord();
    }

    /**
     * Check if the module can be enabled.
     *
     * @return  bool    true if the module can be enabled.
     */
    public function canEnable()
    {
        if ($this->isCoreModule() || $this->isEnabled() || !$this->areDependenciesSatisfied()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check if the module can be disabled.
     *
     * @return  bool    true if the module can be disabled.
     */
    public function canDisable()
    {
        if ($this->isCoreModule() || !$this->isEnabled() || $this->hasDependents()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Determine if all of the module dependencies are satisfied and
     * the module can be enabled.
     *
     * @return  boolean     true if all of the required modules are installed and enabled.
     */
    public function areDependenciesSatisfied()
    {
        // if no dependencies, can be enabled.
        if (!$this->hasDependencies()) {
            return true;
        }

        // check if required modules are installed and enabled.
        foreach ($this->getDependencies() as $dependency) {
            extract($dependency);
            if (!static::isDependencySatisfied($name, $type, $versions)) {
                return false;
            }
        }

        // all dependencies are satisfied - can enable!
        return true;
    }

    /**
     * Determine if the module has any dependencies.
     *
     * @return  boolean     true if the module has dependencies.
     */
    public function hasDependencies()
    {
        return (bool) count($this->getDependencies());
    }

    /**
     * Determine if any other enabled modules depend upon this module.
     *
     * @return  boolean     true if other enabled modules depend on this module.
     */
    public function hasDependents()
    {
        // check if any enabled modules are dependent on this module.
        foreach (P4Cms_Module::fetchAllEnabled() as $module) {
            if (!$module->hasDependencies()) {
                continue;
            }
            $dependencies = $module->getDependencies();
            foreach ($dependencies as $dependency) {
                if ($dependency['type'] === 'module'
                    && $dependency['name'] == $this->getName()
                ) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if the given dependency is satisified.
     *
     * Versions may be specified using wildcards such as '*' and '?'.
     * Matching is performed using fnmatch() and therefore supports
     * all of the same shell patterns recognized by that function.
     *
     * @param   string          $name       the name of the required package.
     * @param   string          $type       the type of dependency (module or library)
     * @param   string|array    $versions   suitable versions of the package.
     * @return  bool            true if the correct package is installed/enabled.
     */
    public static function isDependencySatisfied($name, $type, $versions)
    {
        // dependency name and a valid type must be specified.
        if (!isset($name) || !in_array($type, static::$_dependencyTypes)) {
            return false;
        }

        // accept single version string or array of versions.
        $versions = (array) $versions;

        // differentiate module and library dependency checking.
        if ($type === 'library') {
            $className = $name . "_Version";
            if (!class_exists($className)) {
                return false;
            }

            $version = $className::VERSION;
        } else if ($type === 'module') {
            // check if the named module exists.
            if (!P4Cms_Module::exists($name)) {
                return false;
            }

            // fetch the named module and check if it's enabled.
            $module = P4Cms_Module::fetch($name);
            if (!$module->isEnabled()) {
                return false;
            }

            $version = $module->getVersion();
        }

        // check version.
        foreach ($versions as $suitableVersion) {
            if (fnmatch($suitableVersion, $version)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the name of the corresponding integration class for this module.
     *
     * @return  string  the name of the integration class.
     */
    public function getIntegrationClassName()
    {
        return $this->getName() . '_Module';
    }

    /**
     * Determine if this module is a core module.
     *
     * @return  bool    true if this module is a core (required) module.
     */
    public function isCoreModule()
    {
        return (dirname($this->getPath()) == $this->getCoreModulesPath());
    }

    /**
     * Set the path to the core modules.
     *
     * @param   string  $path   the path to the core modules.
     */
    public static function setCoreModulesPath($path)
    {
        static::$_coreModulesPath = $path;
    }

    /**
     * Get the path to the core modules.
     *
     * @return  string  the path that contains the core modules.
     */
    public static function getCoreModulesPath()
    {
        if (!static::$_coreModulesPath) {
            throw new P4Cms_Module_Exception("Can't get core modules. Path is unset.");
        }
        return static::$_coreModulesPath;
    }

    /**
     * Extends parent to add core modules path last.
     *
     * @return  array   the list of paths that can contain packages.
     */
    public static function getPackagesPaths()
    {
        $paths   = static::$_packagesPaths;
        $paths[] = static::getCoreModulesPath();
        return $paths;
    }

    /**
     * Get any dependencies that this module has on other packages.
     *
     * @return  array   list of dependencies - each entry contains three elements:
     *                      - name     (name of package)
     *                      - type     (type of package)
     *                      - versions (list of suitable versions)
     */
    public function getDependencies()
    {
        $info = $this->getPackageInfo();
        if (!isset($info['dependencies']) || !is_array($info['dependencies'])) {
            return array();
        }

        // normalize/flatten dependencies.
        $dependencies = array();
        foreach (static::$_dependencyTypes as $type) {
            if (!isset($info['dependencies'][$type]) || !is_array($info['dependencies'][$type])) {
                continue;
            }

            // convert comma-separated version list to array form.
            foreach ($info['dependencies'][$type] as $name => $versions) {
                $dependencies[] = array(
                    'name'      => $name,
                    'type'      => $type,
                    'versions'  => str_getcsv($versions, ',')
                );
            }
        }

        return $dependencies;
    }

    /**
     * Initialize the module in the environment.
     *
     * This method only needs to be called once per-module, per-request.
     * to register the module with the application.
     *
     * @return  P4Cms_Module    provides fluent interface.
     */
    public function init()
    {
        // don't initialize modules twice
        if ($this->_isInitialized()) {
            return $this;
        }

        // an instance of the view is required to properly initialize a module.
        if (!Zend_Layout::getMvcInstance()) {
            Zend_Layout::startMvc();
        }
        $view = Zend_Layout::getMvcInstance()->getView();
        if (!$view instanceof Zend_View) {
            throw new P4Cms_Module_Exception("Can't initialize module. Failed to get view instance.");
        }

        // add module to loader so that module classes will auto-load.
        P4Cms_Loader::addPackagePath($this->getName(), $this->getPath());

        // add module's controllers directory to front controller so that requests will route.
        Zend_Controller_Front::getInstance()->addControllerDirectory(
            $this->getPath() . '/controllers',
            basename($this->getPath())
        );

        // each module can have layouts - add layout script path.
        if (is_dir($this->getPath() . '/layouts/scripts')) {
            $view->addScriptPath($this->getPath() . '/layouts/scripts');
        }

        // each module can have helpers (under views or layouts) - add helper paths.
        if (is_dir($this->getPath() . '/views/helpers')) {
            $view->addHelperPath(
                $this->getPath() . '/views/helpers/',
                $this->getName() . '_View_Helper_'
            );
        }
        if (is_dir($this->getPath() . '/layouts/helpers')) {
            $view->addHelperPath(
                $this->getPath() . '/layouts/helpers/',
                $this->getName() . '_View_Helper_'
            );
        }

        // each module can have form plugins:
        // elements, decorators, validators and filters.
        $plugins = array(
            array(Zend_Form::ELEMENT,           '/forms/elements/',     'Form_Element'),
            array(Zend_Form::DECORATOR,         '/forms/decorators/',   'Form_Decorator'),
            array(Zend_Form_Element::VALIDATE,  '/validators/',         'Validate'),
            array(Zend_Form_Element::FILTER,    '/filters/',            'Filter')
        );
        foreach ($plugins as $plugin) {
            if (is_dir($this->getPath() . $plugin[1])) {
                P4Cms_Form::registerPrefixPath(
                    $this->getName() . "_" . $plugin[2],
                    $this->getPath() . $plugin[1],
                    $plugin[0]
                );
            }
        }

        // each module can have controller action helpers (under controllers)
        if (is_dir($this->getPath() . '/controllers/helpers')) {
            Zend_Controller_Action_HelperBroker::addPath(
                $this->getPath() . '/controllers/helpers/',
                $this->getName() . '_Controller_Helper_'
            );
        }

        // call the 'init' integration method if defined.
        try {
            if ($this->hasIntegrationMethod('init')) {
                $this->callIntegrationMethod('init');
            }
        } catch (Exception $e) {
            P4Cms_Log::logException("Failed to init the '" . $this->getName() . "' module.", $e);
        }

        // flag the module as initialized
        $this->_setInitialized();

        return $this;
    }

    /**
     * Attempt to load this module
     *
     * @return  P4Cms_Module    provides fluent interface.
     * @todo    Consider moving guts of this to init() as it isn't clear why
     *          stylesheets, scripts, dojo modules and routes are added here and
     *          not during init - we should keep load() for third-party modules.
     */
    public function load()
    {
        // don't load modules twice (without unloading first)
        if ($this->isLoaded()) {
            return $this;
        }

        // add module meta, stylesheets and scripts to view.
        $this->_loadHtmlMeta();
        $this->_loadStylesheets();
        $this->_loadScripts();

        // load dojo components.
        $this->_loadDojo();

        // if the module is a route provider add the routes to the router.
        $router = Zend_Controller_Front::getInstance()->getRouter();
        $router->addConfig(new Zend_Config($this->getRoutes()));

        try {
            if ($this->hasIntegrationMethod('load')) {
                $this->callIntegrationMethod('load');
            }
            $this->_setLoaded();
        } catch (Exception $e) {
            P4Cms_Log::logException("Failed to load the '" . $this->getName() . "' module.", $e);
        }

        return $this;
    }

    /**
     * Determine if this module has a corresponding static module integration class.
     *
     * @return  boolean     true if the module has a corresponding module integration class.
     */
    public function hasIntegrationClass()
    {
        if (is_file($this->getPath() . '/Module.php') &&
            class_exists($this->getIntegrationClassName())) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether an integration class method exists.
     *
     * @param  string  $method  the name of the method to find.
     */
    public function hasIntegrationMethod($method)
    {
        if (!$this->hasIntegrationClass()) {
            return false;
        }
        if (!method_exists($this->getIntegrationClassName(), $method)) {
            return false;
        }
        return true;
    }

    /**
     * Call a static method on the integration class.
     *
     * @param   string  $method     the name of the method to call.
     * @param   array   $params     optional - the arguments to pass to the method.
     * @return  mixed   the return value of the integration method.
     * @throws  P4Cms_Module_Exception if the integration class or method does not exist.
     */
    public function callIntegrationMethod($method, $params = array())
    {
        if (!$this->hasIntegrationClass()) {
            throw new P4Cms_Module_Exception("Can't call integration method: '"
              . $method . "'. " . $this->getName() . " module does not have an integration class.");
        }
        if (!method_exists($this->getIntegrationClassName(), $method)) {
            throw new P4Cms_Module_Exception("Can't call integration method: '"
              . $method . "'. " . $this->getName() . " module does not have this method.");
        }
        return call_user_func_array(array($this->getIntegrationClassName(), $method), $params);
    }

    /**
     * Determine if this module is configurable.
     * Considered configurable if the module.ini file specifies
     * a configure controller.
     *
     * @return  boolean     true if the module is configurable.
     */
    public function isConfigurable()
    {
        $info = $this->getPackageInfo();
        return isset($info['configure']['controller']) && is_array($info['configure']);
    }

    /**
     * Get the URI to configure this module.
     *
     * @return  string  the URI to configure this module.
     */
    public function getConfigUri()
    {
        if (!$this->isConfigurable()) {
            throw new P4Cms_Module_Exception(
                "Cannot get URI to configure '" . $this->getName() .
                "'. The module is not configurable."
            );
        }

        // return assembled uri.
        $router = Zend_Controller_Front::getInstance()->getRouter();
        return $router->assemble($this->getConfigRouteParams(), null, true);
    }

    /**
     * Get the route params to configure this module.
     *
     * @return  array  the route params to configure this module.
     */
    public function getConfigRouteParams()
    {
        if (!$this->isConfigurable()) {
            throw new P4Cms_Module_Exception(
                "Cannot get URI params to configure '" . $this->getName() .
                "'. The module is not configurable."
            );
        }

        // if no module is specified, default to this module.
        $info           = $this->getPackageInfo();
        $routeParams    = $info['configure'];
        if (!isset($routeParams['module'])) {
            $routeParams['module'] = $this->getRouteFormattedName();
        }

        return $routeParams;
    }

    /**
     * Get the route-formatted name of this module (e.g. foo-bar
     * instead of FooBar).
     *
     * @return  string  the route formatted module name.
     */
    public function getRouteFormattedName()
    {
        return P4Cms_Controller_Router_Route_Module::formatRouteParam(
            $this->getName()
        );
    }

    /**
     * Get the configuration of this module.
     *
     * This returns a Zend_Config object which can contain any
     * configuration information the module developer chooses to
     * store.
     *
     * @return  Zend_Config     the module configuration.
     */
    public function getConfig()
    {
        return $this->_getConfigRecord()->getConfig();
    }

    /**
     * Set the configuration of this module. This does
     * not save the configuration. You must call saveConfig()
     * to store the configuration persistently.
     *
     * @param   Zend_Config     $config     the configuration to set.
     * @return  P4Cms_Module                provides fluent interface.
     * @throws  InvalidArgumentException    if the given config is invalid.
     */
    public function setConfig($config)
    {
        $this->_getConfigRecord()->setConfig($config);

        return $this;
    }

    /**
     * Save the configuration of this module.
     *
     * @param   Zend_Config $config         optional - the configuration to save.
     * @param   string      $description    optional - a description of the change.
     * @return  P4Cms_Module                provides fluent interface.
     * @throws  P4Cms_Record_Exception      if an invalid config object is given.
     */
    public function saveConfig($config = null, $description = null)
    {
        $record = $this->_getConfigRecord();

        // if config is given, set it.
        if ($config) {
            $record->setConfig($config);
        }

        // if no description is given, generate one.
        if (!$description) {
            $description = "Saved configuration for '" . $this->getName() . "' module.";
        }

        // save config record.
        $record->save($description);

        static::$_configRecords[$this->getName()] = $record;

        return $this;
    }

    /**
     * Clear the config records cache.
     */
    public static function clearConfigCache()
    {
        static::$_configRecords = null;
    }

    /**
     * Clear the config cache, modules paths and the list of loaded and initialized modules.
     * This is useful for testing purposes.
     */
    public static function reset()
    {
        static::$_isLoaded          = array();
        static::$_isInitialized     = array();
        static::$_coreModulesPath   = null;
        static::clearPackagesPaths();
        static::clearConfigCache();
    }

    /**
     * Get the flag indicating loaded status.
     */
    public function isLoaded()
    {
        return isset(static::$_isLoaded[$this->getName()]);
    }

    /**
     * Determine if this module has a configuration record in storage.
     * This method will only return true if the record is in storage.
     *
     * @return  bool    true if this module has a config record in storage; false otherwise.
     */
    protected function _hasStoredConfigRecord()
    {
        return array_key_exists($this->getName(), $this->_getConfigRecords());
    }

    /**
     * Get the instance copy of the config record associated with this module.
     * If no record exists in storage, one will be created in memory.
     *
     * @return  P4Cms_Record_Config     instance copy of this module's config record.
     */
    protected function _getConfigRecord()
    {
        // get/make instance copy of config record if necessary.
        if ($this->_configRecord === null) {
            if ($this->_hasStoredConfigRecord()) {
                $records = $this->_getConfigRecords();
                $this->_configRecord = clone $records[$this->getName()];
            } else {
                $this->_configRecord = new P4Cms_Record_Config;
                $this->_configRecord->setId(self::CONFIG_RECORD_PATH . '/' . $this->getName());
            }
        }

        return $this->_configRecord;
    }

    /**
     * Get all module config records from storage.
     *
     * Provides localized cache of records to avoid repeatedly
     * fetching records from Perforce. Records are keyed on module
     * name for quick/easy lookups.
     *
     * @return  P4Cms_Model_Iterator    all module config records.
     */
    protected function _getConfigRecords()
    {
        // only fetch config records once.
        if (static::$_configRecords === null) {
            $records = P4Cms_Record_Config::fetchAll(
                P4Cms_Record_Query::create()->addPath(self::CONFIG_RECORD_PATH .'/...')
            );

            // store records keyed on module name.
            static::$_configRecords = array();
            foreach ($records as $record) {
                static::$_configRecords[basename($record->getId())] = $record;
            }
        }

        return static::$_configRecords;
    }

    /**
     * Ensure that this module is disabled.
     *
     * @throws  P4Cms_Module_Exception  if the module is not disabled.
     */
    protected function _ensureDisabled()
    {
        if ($this->isEnabled()) {
            throw new P4Cms_Module_Exception(
                "The '" . $this->getName() . "' module is not disabled."
            );
        }
    }

    /**
     * Ensure that this module is enabled.
     *
     * @throws  P4Cms_Module_Exception  if the module is not enabled.
     */
    protected function _ensureEnabled()
    {
        if (!$this->isEnabled()) {
            throw new P4Cms_Module_Exception(
                "The '" . $this->getName() . "' module is a not enabled."
            );
        }
    }

    /**
     * Set the flag indicating loaded status.
     */
    private function _setLoaded()
    {
        static::$_isLoaded[$this->getName()] = true;
    }

    /**
     * Set the flag indicating unloaded status.
     */
    private function _setUnloaded()
    {
        static::$_isLoaded[$this->getName()] = false;
    }

    /**
     * Get the flag indicating initialization status.
     */
    private function _isInitialized()
    {
        return isset(static::$_isInitialized[$this->getName()]);
    }

    /**
     * Set the flag indicating initialization status.
     */
    private function _setInitialized()
    {
        static::$_isInitialized[$this->getName()] = true;
    }
}
