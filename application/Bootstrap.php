<?php
// define constants required by the application.
defined('BASE_PATH')
    or define('BASE_PATH',          realpath(dirname(__DIR__)));
defined('DATA_PATH')
    or define('DATA_PATH',          BASE_PATH . '/data');
defined('APPLICATION_PATH')
    or define('APPLICATION_PATH',   BASE_PATH . '/application');
defined('LIBRARY_PATH')
    or define('LIBRARY_PATH',       BASE_PATH . '/library');
defined('SITES_PATH')
    or define('SITES_PATH',         BASE_PATH . '/sites');

/**
 * Initialize the application and environment.
 * Note: the init functions are called in the order that they are defined.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    /**
     * Provide default application settings to ensure proper operation.
     *
     * @param   string  $environment    the application environment to get defaults for.
     * @return  array   the default application options.
     */
    public static function getDefaultOptions($environment = null)
    {
        $options = array(
            "phpSettings"   => array(
                "display_startup_errors"        => 0,
                "display_errors"                => 0,
                "error_reporting"               => (E_ALL & ~(E_STRICT|E_NOTICE))
            ),
            "resources"     => array(
                "log"               => array(
                    "file"      => DATA_PATH . '/log',
                    "priority"  => Zend_Log::ERR,
                    "format"    => "%timestamp% PID (%pid%) %priorityName% (%priority%): %message%\n"
                ),
                "frontController"   => array(
                    "env"                       => APPLICATION_ENV,
                    "moduleDirectory"           => APPLICATION_PATH,
                    "defaultModule"             => "content",
                    "prefixDefaultModule"       => true,
                    "actionHelperPaths"         => array(
                        "P4Cms_Controller_Action_Helper" =>
                            LIBRARY_PATH . "/P4Cms/Controller/Action/Helper"
                    )
                ),
                "layout"            => array(
                    "layout"                    => "default-layout"
                ),
                "view"              => array(
                    "encoding"                  => "UTF-8",
                    "useStreamWrapper"          => true,
                    "helperPath"                => array(
                        "Zend_Dojo_View_Helper"         =>
                            LIBRARY_PATH . "/Zend/Dojo/View/Helper",
                        "Zend_View_Helper_Navigation"   =>
                            LIBRARY_PATH . "/Zend/View/Helper/Navigation",
                        "P4Cms_View_Helper"             =>
                            LIBRARY_PATH . "/P4Cms/View/Helper"
                        )
                ),
                "cachemanager"      => array(
                    "enabled"       => true,
                    "default"       => array(
                        "frontend"  => array(
                            "name"      => "Core",
                            "options"   => array("automatic_serialization" => true)
                        ),
                        "backend"   => array(
                            "name"                  => "P4Cms_Cache_Backend_File",
                            "customBackendNaming"   => true,
                            "options"               => array("cache_dir" => DATA_PATH . '/cache/default')
                        ),
                    ),
                    "page"          => array(
                        "frontend"  => array(
                            "name"                  => "P4Cms_Cache_Frontend_Action",
                            "customFrontendNaming"  => true,
                            "options"               => array(
                                'default_options'   => array(
                                    'specific_lifetime'         => 43200    // 12h
                                ),
                                'actions'           => array(
                                    'content/list-widget/rss'   => array(),
                                    'content/index/index'       => array(),
                                    'content/index/view'        => array(),
                                    'content/index/image'       => array(
                                        'compress'              => false,
                                        'cache_with_username'   => true,
                                        'make_id_with_locale'   => false,
                                        'cache_with_session'    => true,
                                        'make_id_with_session'  => false,
                                        'cache_with_get'        => true
                                    ),
                                    'content/index/download'    => array(
                                        'compress'              => false,
                                        'cache_with_username'   => true,
                                        'make_id_with_locale'   => false,
                                        'cache_with_session'    => true,
                                        'make_id_with_session'  => false,
                                        'cache_with_get'        => true
                                    ),
                                    'widget/image-widget/image' => array(
                                        'compress'              => false,
                                        'cache_with_username'   => true,
                                        'make_id_with_rolename' => false,
                                        'make_id_with_locale'   => false,
                                        'cache_with_session'    => true,
                                        'make_id_with_session'  => false
                                    ),
                                    'content/type/icon'         => array(
                                        'compress'              => false,
                                        'cache_with_username'   => true,
                                        'make_id_with_locale'   => false,
                                        'cache_with_session'    => true,
                                        'make_id_with_session'  => false,
                                        'specific_lifetime'     => null     // forever
                                    )
                                )
                            )
                        ),
                        "backend"   => array(
                            "name"                  => "P4Cms_Cache_Backend_File",
                            "customBackendNaming"   => true,
                            "options"               => array("cache_dir" => DATA_PATH . '/cache/page')
                        ),
                    ),
                    "global"        => array(
                        "frontend"  => array(
                            "name"      => "Core",
                            "options"   => array("automatic_serialization" => true)
                        ),
                        "backend"   => array(
                            "name"                  => "P4Cms_Cache_Backend_File",
                            "customBackendNaming"   => true,
                            "options"               => array(
                                "cache_dir" => DATA_PATH . '/cache/global',
                                "namespace" => false
                            )
                        )
                    )
                ),
                'assethandler'      => array(
                    'class'             => 'P4Cms_AssetHandler_File',
                    'options'           => array(
                        'outputPath'    => DATA_PATH . '/resources'
                    )
                ),
                'session'           => array(
                    'save_path'         => DATA_PATH . '/sessions',
                    'gc_maxlifetime'    => 43200    // 12h
                )
            ),
            "performance"   => array(
                "aggregateCss"  => true,
                "aggregateJs"   => true,
                "autoBuildDojo" => true,
            ),
            "reportVersion"     => false
        );

        // tweak defaults for development environments
        //  - increase error/log verbosity
        //  - disable resource aggregation
        //  - disable caching
        if ($environment == "development" || $environment == "testing") {
            $options['phpSettings']['display_startup_errors']   = 1;
            $options['phpSettings']['display_errors']           = 1;
            $options['phpSettings']['error_reporting']          = (E_ALL & ~E_STRICT);
            $options['resources']['log']['priority']            = Zend_Log::DEBUG;
            $options['resources']['cachemanager']['enabled']    = false;
            $options['performance']['aggregateCss']             = false;
            $options['performance']['aggregateJs']              = false;
            $options['performance']['autoBuildDojo']            = false;
            $options["reportVersion"]                           = true;
        }

        return $options;
    }

    /**
     * Initialize PATH to include bundled p4 binaries.
     * Used primarily by _initPath() - made public static so it can used by tests.
     */
    public static function initPath()
    {
        $system  = php_uname('s');
        $release = php_uname('r');
        $machine = php_uname('m');

        // normalize uname info according to p4-bin conventions.
        $system = strtolower($system);
        $machine = preg_replace("/i.86/", "x86", $machine);

        // treat all versions of windows the same
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $system = 'nt';
        }

        switch ($system) {
            case 'darwin':
                if (floatval($release) >= 9.0) {
                    $release = '90';
                }

                // there is no easy way to detect 32 vs 64 bit on mac
                // we hard-code to 64 as only the core-duos were 32 bit.
                $machine = 'x86_64';
                break;
            case 'linux':
                $release = substr(str_replace('.', '', $release), 0, 2);
                if (intval($release) > 26) {
                    $release = '26';
                }
                break;
            case 'nt':
                $release = '';
                $machine = 'x86';
                break;
        }

        // if a matching p4-bin path exists, add to PATH.
        $path = BASE_PATH . '/p4-bin/bin.' . $system . $release . $machine;
        if (is_dir($path) && strpos(getenv('PATH'), $path . PATH_SEPARATOR) !== 0) {
            putenv('PATH=' . $path . PATH_SEPARATOR . getenv('PATH'));
        }
    }

    /**
     * Determine if setup needs to be run. Setup must be run if
     * we have no connection to Perforce or no sites are defined.
     *
     * @return  boolean     true if setup needs to be run.
     */
    public function isSetupNeeded()
    {
        return !$this->hasResource('perforce') || !$this->hasResource('site');
    }

    /**
     * Register a new resource plugin. Extended to ignore any resources
     * that are already known class resources. The plugin version won't
     * be used if there is a class resource of the same name, however,
     * we could get an exception if the plugin does not exist.
     *
     * @param   string|Zend_Application_Resource_Resource   $resource   the resource to register
     * @param   mixed                                       $options    options for the resource
     * @return  Zend_Application_Bootstrap_BootstrapAbstract            provides fluent interface
     * @throws  Zend_Application_Bootstrap_Exception                    when invalid resource is provided
     */
    public function registerPluginResource($resource, $options = null)
    {
        $classResources = $this->getClassResourceNames();
        if (is_string($resource) && in_array(strtolower($resource), $classResources)) {
            return $this;
        }

        return parent::registerPluginResource($resource, $options);
    }

    /**
     * Set default timezone to suppress PHP warnings.
     */
    protected function _initTimezone()
    {
        date_default_timezone_set(@date_default_timezone_get());
    }

    /**
     * Register known libraries with our loader.
     */
    protected function _initLibraries()
    {
        $libraries = array('P4', 'P4Cms', 'Phly', 'Zend');
        foreach ($libraries as $library) {
            P4Cms_Loader::addPackagePath($library, LIBRARY_PATH . '/' . $library);
        }
    }

    /**
     * Initialize the logger.
     *
     * @return  Zend_Log    the initialized logger.
     */
    protected function _initLog()
    {
        // don't setup logger if we can't write log file.
        $options = $this->getOption('resources');
        $options = isset($options['log']) ? (array) $options['log'] : array();
        $logFile = isset($options['file']) ? $options['file'] : null;
        if (!$logFile || !@touch($logFile)) {
            return;
        }

        // if we don't already have a logger; set one up.
        if (!P4Cms_Log::hasLogger()) {
            P4Cms_Log::setLogger(new Zend_Log);
        }

        // attach our writer to the logger
        $logger = P4Cms_Log::getLogger();
        $logger->setEventItem('pid', getmypid());
        $writer = new Zend_Log_Writer_Stream($logFile);
        $logger->addWriter($writer);

        // configure log format.
        $format = new Zend_Log_Formatter_Simple(
            isset($options['format']) ? $options['format'] : null
        );
        $writer->setFormatter($format);

        // filter by priority.
        $filter = new Zend_Log_Filter_Priority((int)$options['priority']);
        $writer->addFilter($filter);

        // log uncaught exceptions.
        set_exception_handler(
            function(Exception $e)
            {
                // abort page caching on unhandled exceptions
                // as we want users to get the real content
                // if the issue is later fixed.
                if (P4Cms_Cache::canCache('page')) {
                    P4Cms_Cache::getCache('page')->cancel();
                }

                P4Cms_Log::logException("Uncaught ", $e);

                trigger_error($e);
            }
        );

        return $logger;
    }

    /**
     * Create the request object (normally doesn't happen until dispatch).
     *
     * @return  Zend_Controller_Request_Http    the initialized request object.
     */
    protected function _initRequest()
    {
        return new P4Cms_Controller_Request_Http;
    }

    /**
     * Configure the cache manager.
     *
     * Registers the cache manager with P4Cms_Cache for easy/static
     * access to caching facilities (e.g. save(), load()).
     *
     * @return  void|P4Cms_Cache_Manager    the configured cache manager or void if none.
     */
    protected function _initCacheManager()
    {
        // don't initialize cache manager if its not configured or enabled.
        $options = $this->getOptions();
        if (!isset($options['resources']['cachemanager']['enabled'])
            || !$options['resources']['cachemanager']['enabled']
        ) {
            P4Cms_Cache::setLoggingEnabled(false);
            return;
        }

        // create the cache manager.
        $manager = new P4Cms_Cache_Manager;

        // provide convenient static access via P4Cms_Cache
        P4Cms_Cache::setManager($manager);

        return $manager;
    }

    /**
     * Register any global cache templates.
     *
     * Any template that specifies a fixed namespace option is considered
     * to be 'global' and will be initialized here. All other templates
     * are automatically assumed to be site specific and will be configured
     * after we initialize the site so that we can set the site namespace.
     */
    protected function _initGlobalCache()
    {
        $this->bootstrap('cacheManager');
        $manager = $this->getResource('cacheManager');
        $options = $this->getOptions();

        // if no cache manager, can't configure global caches.
        if (!$manager) {
            return;
        }

        // add global cache templates (explicit namespaces only)
        $templates = $options['resources']['cachemanager'];
        foreach ($templates as $name => $template) {
            if (!is_array($template) || !isset($template['backend']['options']['namespace'])) {
                continue;
            }

            // skip any backend which specifies a non-writable cache_dir
            if (isset($template['backend']['options']['cache_dir'])) {
                $cachePath = $template['backend']['options']['cache_dir'];
                @mkdir($cachePath, 0755, true);
                if (!is_writable($cachePath)) {
                    continue;
                }
            }

            $manager->setCacheTemplate($name, $template);
        }
    }

    /**
     * Figure out the current site.
     *
     * @return  P4Cms_Site  the active site model.
     */
    protected function _initSite()
    {
        $this->bootstrap('request');
        $request = $this->getResource('request');

        // tell site model where sites are stored.
        P4Cms_Site::setSitesPackagesPath(SITES_PATH);
        P4Cms_Site::setSitesDataPath(DATA_PATH . '/sites');

        // try to fetch sites without initializing perforce.
        // this will only work if we have previously cached
        // sites - if it fails, init perforce and try again.
        $perforce = null;
        try {
            $sites = P4Cms_Site::fetchAll();
        } catch (Exception $e) {
            $this->bootstrap('perforce');
            $perforce = $this->getResource('perforce');
            if (!$perforce) {
                return;
            }

            // cold cache case, need to fetch against perforce.
            $sites = P4Cms_Site::fetchAll(null, $perforce);
        }

        // can't init site if there are no sites.
        if (!$sites->count()) {
            return;
        }

        // application config may limit (whitelist) the
        // branches that can be safely exposed.
        $limit   = null;
        $options = $this->getOption('resources');
        if (isset($options['site']['whitelist'])) {
            $limit = (array) $options['site']['whitelist'];
        }

        // find the best site/branch for this request (honor limit).
        $site = P4Cms_Site::fetchByRequest($request, $limit, $perforce);

        return $site;
    }

    /**
     * Register any site specific cache templates.
     *
     * Cache templates that do not specify a namespace are automatically
     * assumed to be site specific and are configured here (post site init).
     */
    protected function _initSiteCache()
    {
        $this->bootstrap('site');
        $this->bootstrap('cacheManager');
        $site    = $this->getResource('site');
        $manager = $this->getResource('cacheManager');
        $options = $this->getOptions();

        // if no cache manager or no site, can't configure site caches.
        if (!$manager || !$site) {
            return;
        }

        // add site specific cache templates (no explicit namespace)
        $templates = $options['resources']['cachemanager'];
        foreach ($templates as $name => $template) {
            if (!is_array($template) || isset($template['backend']['options']['namespace'])) {
                continue;
            }

            // skip any backend which specify a non-writable cache_dir
            if (isset($template['backend']['options']['cache_dir'])) {
                $cachePath = $template['backend']['options']['cache_dir'];
                @mkdir($cachePath, 0755, true);
                if (!is_writable($cachePath)) {
                    continue;
                }
            }

            // make cache site specific by passing the site id as a
            // 'namespace' option to the backend (the backend will
            // simply ignore this option if it doesn't support it).
            $template['backend']['options']['namespace'] = $site->getId();

            $manager->setCacheTemplate($name, $template);
        }
    }

    /**
     * If the page cache has been configured by the cacheManager bootstrap;
     * this task will 'start' it causing a cached page to be served if possible
     * and attempting to capture the current page otherwise.
     *
     * @return void|Zend_Cache_Core     The page cache instance or void if none.
     */
    protected function _initPageCache()
    {
        $this->bootstrap('cacheManager');
        $this->bootstrap('request');
        $cacheManager = $this->getResource('cacheManager');
        $request      = $this->getResource('request');

        // early exit if no page cache is configured
        if (!$cacheManager instanceof Zend_Cache_Manager || !$cacheManager->hasCache('page')) {
            return;
        }

        $cache = $cacheManager->getCache('page');

        // setting the baseUrl allows the page cache to munge regex's to still
        // match with sub-folder installs
        $cache->setBaseUrl($request->getBaseUrl());

        // Provide default values for username/rolenames aimed at anonymous users.
        $cache->setUsername('')
              ->setRolenames(array());

        // If cookies are present attempt to pull out the user name and role names.
        // We avoid this when there are no cookies to prevent needlessly starting
        // a new session for anonymous users.
        if (count($_COOKIE)) {
            // verify the session is configured if we are taking this branch.
            $this->bootstrap('sessionHandler');

            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $identity = $auth->getIdentity();
                if (is_array($identity) && isset($identity['id'])) {
                    $username  = (string)$identity['id'];

                    // the roles are cached for us by initUser, attempt to access them here.
                    $rolenames = P4Cms_Cache::load('p4cms_user_roles_' . md5($username));

                    // if we weren't able to find a list of roles for this user, then
                    // we have no way of making a suitable cache id, early exit.
                    // presumably we will put the roles into cache for the next request.
                    if ($rolenames === false) {
                        return $cacheManager->getCache('page');
                    } else {
                        $cache->setUsername($username);
                        $cache->setRolenames($rolenames);
                    }
                }
            }
        }

        // Start the page cache. If a cached copy is present it
        // will be output at this point and the script will die
        // to stop further execution.
        $cache->start();

        return $cacheManager->getCache('page');
    }

    /**
     * If session options were declared, intialize the session handler
     * at this point. By default initialization would happen much later
     * and we could end up accessing session data prior to the handler
     * being initialized.
     */
    protected function _initSessionHandler()
    {
        $options = $this->getOption('resources');

        // attempt to make writable session save path if one is set.
        if (isset($options['session']['save_path'])) {
            $path = $options['session']['save_path'];
            @mkdir($path, 0700, true);
            if (!is_writable($path)) {
                @chmod($path, 0700);
            }

            // if still not writable, clear unusable save path.
            if (!is_writable($path)) {
                P4Cms_Log::log(
                    "Session save path '$path' is not writable. Using default instead.",
                    P4Cms_Log::WARN
                );

                $this->setOptions(
                    array('resources' => array('session' => array('save_path' => null)))
                );
            }
        }

        if (isset($options['session'])) {
            $this->bootstrap('session');
        }
    }

    /**
     * Setup view with our (theme-aware) view renderer and automatic context switching.
     *
     * @return  Zend_View   the configured view instance.
     */
    protected function _initView()
    {
        // grab view options.
        $options = $this->getOptions();
        if (isset($options['resources']['view'])) {
            $options = $options['resources']['view'];
        } else {
            $options = array();
        }

        // setup view.
        $view = new Zend_View($options);

        // use our renderer.
        $renderer = new P4Cms_Controller_Action_Helper_ViewRenderer;
        $renderer->setView($view);
        Zend_Controller_Action_HelperBroker::addHelper($renderer);

        return $view;
    }

    /**
     * Define application version constants.
     */
    protected function _initVersion()
    {
        $versionFile = BASE_PATH . '/Version';
        $versionData = is_readable($versionFile)
            ? file_get_contents($versionFile)
            : '';

        // extract key/value constants from version file
        preg_match_all('/^([A-Z]+)\s*=\s*(.+[^\s])\s*;/m', $versionData, $matches);

        // preset release, patchlevel and suppdate to guard against their absence
        $constants = array(
            'NAME'          => 'P4CHRONICLE',
            'RELEASE'       => 'unknown-release',
            'PATCHLEVEL'    => 'unknown-patchlevel',
            'SUPPDATE'      => 'unknown-date'
        );

        // merge in constants from the file
        if (is_array($matches[1]) && is_array($matches[2])) {
            foreach ($matches[1] as $key => $constant) {
                if (isset($matches[2][$key])) {
                    $constants[$constant] = $matches[2][$key];
                }
            }
        }

        // properly format our constants
        $constants['RELEASE']  = str_replace(' ', '.', $constants['RELEASE']);
        $constants['SUPPDATE'] = str_replace(' ', '/', $constants['SUPPDATE']);

        // define our constants
        foreach ($constants as $name => $value) {
            $constantName = 'P4CMS_VERSION_' . $name;
            if (!defined($constantName)) {
                define($constantName, $value);
            }
        }

        // produce a version string
        if (!defined('P4CMS_VERSION')) {
            $version = P4CMS_VERSION_NAME
                     . '/' . P4CMS_VERSION_RELEASE
                     . '/' . P4CMS_VERSION_PATCHLEVEL
                     . ' (' . P4CMS_VERSION_SUPPDATE . ')';
            define('P4CMS_VERSION', $version);
        }

        $options = $this->getOptions();
        if (isset($options['reportVersion']) && $options['reportVersion'] === true) {
            $this->getResource('view')->headMeta()->appendName('chronicle-version', P4CMS_VERSION);
        }
    }

    /**
     * Setup action helpers.
     */
    protected function _initActionHelpers()
    {
        $options = $this->getOptions();

        // use our automatic context switch helper to adjust
        // view script suffix (no need to call init context).
        Zend_Controller_Action_HelperBroker::addHelper(
            new P4Cms_Controller_Action_Helper_ContextSwitch
        );

        // use the WidgetContext helper to provide contextual data to widgets
        Zend_Controller_Action_HelperBroker::addHelper(
            new P4Cms_Controller_Action_Helper_WidgetContext
        );

        // use the extended Redirector helper to provide option
        // to redirect to one of the previously visited pages
        Zend_Controller_Action_HelperBroker::addHelper(
            new P4Cms_Controller_Action_Helper_Redirector
        );

        // add acl helper to provide convenient access checks
        Zend_Controller_Action_HelperBroker::addHelper(
            new P4Cms_Controller_Action_Helper_Acl
        );

        // add audit helper to record dispatched actions
        Zend_Controller_Action_HelperBroker::addHelper(
            new P4Cms_Controller_Action_Helper_Audit
        );
    }

    /**
     * Ensure our existing request object is set on the front controller
     * so that we don't have two of them in-flight.
     */
    protected function _initFrontRequest()
    {
        $this->bootstrap('request');
        $this->bootstrap('frontController');
        $request = $this->getResource('request');
        $front   = $this->getResource('frontController');
        $front->setRequest($request);
    }

    /**
     * Setup the router to use a custom version of the default route.
     * The custom version provides support for shorter urls (e.g. the
     * controller can be omitted if it is the default ('index')
     * controller.
     *
     * @return  Zend_Controller_Router_Interface    the initialized router.
     */
    protected function _initRouter()
    {
        $this->bootstrap('frontController');
        $this->bootstrap('request');
        $front   = $this->getResource('frontController');
        $request = $this->getResource('request');

        $router  = new P4Cms_Controller_Router_Rewrite;
        $front->setRouter($router);
        $router->addRoute(
            'default',
            new P4Cms_Controller_Router_Route_Module(
                array(),
                $front->getDispatcher(),
                $request
            )
        );

        return $router;
    }

    /**
     * Initialize pub-sub component to use our provider.
     */
    protected function _initPubSub()
    {
        P4Cms_PubSub::setInstance(new P4Cms_PubSub_Provider);
    }

    /**
     * Initialize PATH to include bundled p4 binaries.
     */
    protected function _initPath()
    {
        static::initPath();
    }

    /**
     * Establish our connection with Perforce.
     */
    protected function _initPerforce()
    {
        // place the p4trust file under the data path.
        // the trust file has to be in a writable
        // location to support ssl enabled servers
        putenv('P4TRUST=' . DATA_PATH . '/p4trust');

        // set the app name to use for all connections created
        // via the connection factory (needed for license).
        P4_Connection::setAppName('chronicle');

        $options = $this->getOption('resources');
        if (!isset($options['perforce']) || !is_array($options['perforce'])) {
            return null;
        }

        // if we are using perforce we need to ensure we select
        // the proper p4/p4d by initing the PATH first.
        $this->bootstrap('path');

        // extract perforce options into local scope.
        extract($options['perforce']);

        // create a perforce connection object.
        $connection = P4_Connection::factory(
            isset($port)     ? $port     : null,
            isset($user)     ? $user     : null,
            isset($client)   ? $client   : null,
            isset($password) ? $password : null,
            isset($ticket)   ? $ticket   : null,
            isset($type)     ? $type     : null
        );

        // login and set as default connection.
        $connection->login();
        P4_Connection::setDefaultConnection($connection);

        return $connection;
    }

    /**
     * Load the current site.
     *
     * @return  P4Cms_Site  the active site model.
     */
    protected function _initLoadSite()
    {
        $this->bootstrap('site');
        $this->bootstrap('perforce');
        $site     = $this->getResource('site');
        $perforce = $this->getResource('perforce');

        // if we have a site and a connection, load the site.
        if ($site && $perforce) {
            $site->setConnection($perforce)
                 ->load();
        }

        return $site;
    }

    /**
     * Initialize the current active user.
     *
     * @return  P4Cms_User  the active user model.
     */
    protected function _initUser()
    {
        $this->bootstrap('loadSite');
        $this->bootstrap('cacheManager');

        // if auth has a valid identity and we have a default adapter,
        // fetch the authenticated user (can't fetch w.out adapter).
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity() && P4Cms_Record::hasDefaultAdapter()) {
            $identity = $auth->getIdentity();
            if (is_array($identity) && isset($identity['id'])) {
                try {
                    $user = P4Cms_User::fetch($identity['id']);

                    // deny access if user has no roles
                    $roles = $user->getRoles();
                    if (!count($roles)) {
                        throw new Exception(
                            'Any authenticated user must have at least one role.'
                        );
                    }

                    // make the active user's personal adapter the default
                    // so that we communicate with Perforce as the user.
                    $ticket     = isset($identity['ticket']) ? $identity['ticket'] : null;
                    $adapter    = $user->createPersonalAdapter($ticket);
                    $connection = $adapter->getConnection();

                    // test user's ticket.
                    $connection->run('login', '-s');

                    $user->setPersonalAdapter($adapter);
                    P4Cms_Record::setDefaultAdapter($adapter);
                    P4_Connection::setDefaultConnection($connection);

                    // Cache out the roles so initPageCache will have access to them
                    // on subsequent requests without having to talk to perforce.
                    if (P4Cms_Cache::canCache()) {
                        P4Cms_Cache::save(
                            $roles->invoke('getId'),
                            'p4cms_user_roles_' . md5($user->getId()),
                            array('p4cms_user_' . md5($user->getId))
                        );
                    }

                } catch (Exception $e) {
                    P4Cms_Log::logException(
                        "Failed to initialize authenticated user",
                        $e
                    );

                    unset($user);
                    $auth->clearIdentity();
                    P4Cms_Notifications::add(
                        "You have been logged out.",
                        P4Cms_Notifications::SEVERITY_WARNING
                    );
                }
            }
        }

        // if no authenticated user, create anonymous user instance.
        if (!isset($user)) {
            $user = new P4Cms_User;
        }

        // set the active user for this request.
        P4Cms_User::setActive($user);

        return $user;
    }

    /**
     * Initialize the ACL for the application.
     *
     * @return  P4Cms_Acl   the active acl.
     */
    protected function _initAcl()
    {
        $this->bootstrap('view');
        $this->bootstrap('loadSite');
        $this->bootstrap('user');

        // if there is an active site, load it's acl
        // otherwise, create a blank one.
        if (P4Cms_Site::hasActive()) {
            $acl = P4Cms_Site::fetchActive()->getAcl();
        } else {
            $acl = new P4Cms_Acl;
        }

        // if the active user has a personal adapter, use it.
        $user = P4Cms_User::fetchActive();
        if ($user->hasPersonalAdapter()) {
            $acl->getRecord()->setAdapter($user->getPersonalAdapter());
        }

        // set acl on acl action helper.
        if (Zend_Controller_Action_HelperBroker::hasHelper('acl')) {
            $helper = Zend_Controller_Action_HelperBroker::getExistingHelper('acl');
            $helper->setAcl($acl);
        }

        // set acl on navigation view helper.
        $view = $this->getResource('view');
        $role = $user->getAggregateRole($acl);
        $view->getHelper('navigation')
             ->setRole($role)
             ->setAcl($acl);

        return $acl->makeActive();
    }

    /**
     * Initialize all of the enabled modules.
     */
    protected function _initModules()
    {
        $this->bootstrap('loadSite');

        // tell package system where the application's public folder is.
        P4Cms_PackageAbstract::setDocumentRoot(BASE_PATH);

        // tell p4cms module where to find core modules.
        P4Cms_Module::setCoreModulesPath(APPLICATION_PATH);

        // if setup must be run or if setup is already running,
        // restrict module bootstrap to just an init of core modules
        if ($this->isSetupNeeded() || self::_isSetupRunning()) {
            // init all of the core modules and any optional modules that
            // are enabled by default so they can participate in setup.
            $modules = P4Cms_Module::fetchAll();
            foreach ($modules as $module) {
                if ($module->isCoreModule() || $module->getPackageInfo('enableByDefault')) {
                    $module->init();
                }
            }

            // load modules we require for proper operation during setup.
            $required = array('Error', 'Setup', 'Ui', 'Dojo');
            foreach ($required as $module) {
                P4Cms_Module::fetch($module)->load();
            }

            return;
        }

        // initialize then load the enabled modules.
        $modules = P4Cms_Module::fetchAllEnabled();
        $modules->invoke('init');
        $modules->invoke('load');

        return $modules;
    }

    /**
     * Initialize the theme.
     *
     * @return  P4Cms_Theme     the current theme.
     */
    protected function _initTheme()
    {
        // if a theme is already active, return it.
        if (P4Cms_Theme::hasActive()) {
            return P4Cms_Theme::fetchActive();
        }

        // if there is an active site, load it's theme.
        if (P4Cms_Site::hasActive()) {
            try {
                $config = P4Cms_Site::fetchActive()->getConfig();
                $theme  = P4Cms_Theme::fetch($config->getTheme());
                $theme->load();

                return $theme;
            } catch (Exception $e) {
                P4Cms_Log::logException(
                    "Failed to load theme.",
                    $e
                );
            }
        }

        // load default theme.
        P4Cms_Theme::addPackagesPath(SITES_PATH . '/all/themes');
        $theme = P4Cms_Theme::fetchDefault();
        $theme->load();

        return $theme;
    }

    /**
     * Setup the asset handler. This is primarly intended for storing generated
     * assets. In single server configurations, default, this just directs assets
     * to the local filesystem. When running multiple servers you can use a shared
     * asset store to ensure all web servers have access.
     *
     * @return P4Cms_AssetHandlerInterface|null     asset handler or null
     */
    protected function _initAssetHandler()
    {
        // return if we don't have any settings
        $options = $this->getOption('resources');
        if (!isset($options['assethandler']) || !isset($options['assethandler']['class'])) {
            return null;
        }

        // return if class is invalid
        $class = $options['assethandler']['class'];
        if (!class_exists($class)) {
            return null;
        }

        // pull out options defaulting to empty array
        $options = isset($options['assethandler']['options'])
                 ? $options['assethandler']['options']
                 : array();

        $handler = new $class($options);

        // returm null if the required interface isn't implemented
        if (!is_a($handler, 'P4Cms_AssetHandlerInterface')) {
            return null;
        }

        return $handler;
    }

    /**
     * Setup view helpers (enable dojo, css aggregation, ...)
     *
     * @todo move dojo specific parts into dojo modules init method
     */
    protected function _initViewHelpers()
    {
        $this->bootstrap('view');
        $this->bootstrap('request');
        $this->bootstrap('assetHandler');
        $view         = $this->getResource('view');
        $request      = $this->getResource('request');
        $assetHandler = $this->getResource('assetHandler');
        $options      = $this->getOption('performance');

        // enable dojo.
        Zend_Dojo::enableView($view);
        Zend_Dojo_View_Helper_Dojo::setUseDeclarative();
        $dojo = $view->getHelper('dojo');
        $dojo->setLocalPath(
            $request->getBaseUrl() . '/application/dojo/resources/dojo/dojo.js'
        );

        // setup automatic dojo builds if enabled and we have a site.
        if ($options['autoBuildDojo']) {
            $dojo->setAutoBuild(true)
                 ->setDocumentRoot(BASE_PATH)
                 ->setAssetHandler($assetHandler);
        }

        // setup head script with JS aggregation.
        $headScript = $view->getHelper('headScript');
        $headScript->appendScript(
            "if (typeof(p4cms) == 'undefined') p4cms = {};\n" .
            "p4cms.baseUrl='"       . $request->getBaseUrl() . "';\n" .
            "p4cms.branchBaseUrl='" . $request->getBranchBaseUrl() . "';\n"
        );
        if ($options['aggregateJs']) {
            $headScript->setAggregateJs(true)
                       ->setDocumentRoot(BASE_PATH)
                       ->setAssetHandler($assetHandler);
        }

        // setup css aggregation if so enabled.
        if ($options['aggregateCss']) {
            $headLink = $view->getHelper('headLink');
            $headLink->setAggregateCss(true)
                     ->setDocumentRoot(BASE_PATH)
                     ->setAssetHandler($assetHandler);
        }

        // set the page title to the app name or site name (if active).
        $title = $view->getHelper('headTitle');
        $title->setSeparator(' - ');
        if (P4Cms_Site::hasActive()) {
            $site = P4Cms_Site::fetchActive();
            $title->setPostfix($site->getConfig()->getTitle());
        } else {
            $title->setPostfix('Chronicle');
        }
    }

    /**
     * Initialize setup program if necessary.
     */
    protected function _initSetup()
    {
        $this->bootstrap('request');
        $this->bootstrap('loadSite');
        $this->bootstrap('modules');

        // if setup is needed, make it run (unless it is already running).
        if ($this->isSetupNeeded() && !self::_isSetupRunning()) {
            $this->getResource('request')->setPathInfo('/setup');
        }

        // if setup isn't going to run, nothing more to do.
        if (!self::_isSetupRunning()) {
            return;
        }

        // we want errors to show during setup regardless of app config.
        $this->getApplication()->setPhpSettings(
            array(
                'display_errors'    => 1,
                'phpSettings'       => (E_ALL & ~(E_STRICT|E_NOTICE))
            )
        );
    }

    /**
     * Determine if setup is currently set to run.
     *
     * @return  boolean     true if setup has been requested to run.
     */
    protected function _isSetupRunning()
    {
        $this->bootstrap('request');
        $request = $this->getResource('request');
        $paths   = explode('/', substr($request->getPathInfo(), 1));
        if (isset($paths[0]) && $paths[0] == 'setup') {
            return true;
        } else {
            return false;
        }
    }
}
