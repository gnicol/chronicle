<?php
/**
 * Test methods for the Bootstrap class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class BootstrapTest extends TestCase
{
    protected $_application = null;
    protected $_bootstrap   = null;
    protected $_environment = "testing";

    /**
     * Create a new instance of P4CMS application.
     */
    public function setUp()
    {
        parent::setUp();

        $this->_application = new P4Cms_Application($this->_environment);
        $this->_bootstrap   = $this->_application->getBootstrap();
    }

    /**
     * Clean-up.
     */
    public function tearDown()
    {
        $this->_application = null;
        $this->_bootstrap   = null;

        $this->utility->tearDownModuleTest($this);

        parent::tearDown();
    }

    /**
     * Test bootstrap default options.
     */
    public function testBootstrapDefaults()
    {
        // we should be able to get default options from bootstrap.
        $options = Bootstrap::getDefaultOptions();
        $this->assertTrue(
            count($options) > 0,
            "Bootstrap should provide default options."
        );

        // should suppress errors by default.
        $this->assertTrue($options['phpSettings']['display_errors'] == 0);
        $this->assertTrue($options['phpSettings']['display_startup_errors'] == 0);

        // should configure front controller, layout & view by default.
        $this->assertTrue(is_array($options['resources']['frontController']));
        $this->assertTrue(is_array($options['resources']['layout']));
        $this->assertTrue(is_array($options['resources']['view']));

        // application should have default options + Bootstrap.
        $options = Bootstrap::getDefaultOptions($this->_environment);
        $options['bootstrap'] = 'Bootstrap.php';
        $this->assertSame($options, $this->_application->getOptions());

        // errors should be displayed for development environment.
        $options = Bootstrap::getDefaultOptions('development');
        $this->assertTrue($options['phpSettings']['display_errors'] == 1);
        $this->assertTrue($options['phpSettings']['display_startup_errors'] == 1);
    }

    /**
     * Test that all expected resources get initialized.
     */
    public function testResourcesWithSetupNeeded()
    {
        // ensure there are no sites (and setup is needed).
        $this->utility->removeSites();

        $this->_application->bootstrap();
        $this->assertTrue($this->_bootstrap->hasResource('view'));
        $this->assertTrue($this->_bootstrap->hasResource('request'));
        $this->assertTrue($this->_bootstrap->hasResource('theme'));
        $this->assertTrue($this->_bootstrap->hasResource('frontController'));
        $this->assertTrue($this->_bootstrap->hasResource('layout'));
    }

    /**
     * Test that all expected resources get initialized.
     */
    public function testResourcesWithSetupDone()
    {
        $this->_mockSetup();

        $this->_application->bootstrap();
        $this->assertTrue($this->_bootstrap->hasResource('view'));
        $this->assertTrue($this->_bootstrap->hasResource('request'));
        $this->assertTrue($this->_bootstrap->hasResource('log'));
        $this->assertTrue($this->_bootstrap->hasResource('loadSite'));
        $this->assertTrue($this->_bootstrap->hasResource('user'));
        $this->assertTrue($this->_bootstrap->hasResource('modules'));
        $this->assertTrue($this->_bootstrap->hasResource('theme'));
        $this->assertTrue($this->_bootstrap->hasResource('frontController'));
        $this->assertTrue($this->_bootstrap->hasResource('layout'));
    }

    /**
     * Ensure that bootstrap configures view with p4cms renderer.
     */
    public function testInitView()
    {
        $this->_application->bootstrap('view');
        $view = $this->_bootstrap->getResource('view');
        $this->assertTrue(
            $view instanceof Zend_View,
            "View resource should be a Zend_View instance."
        );

        $renderer = Zend_Controller_Action_HelperBroker::getExistingHelper('viewRenderer');
        $this->assertTrue(
            $renderer instanceof P4Cms_Controller_Action_Helper_ViewRenderer,
            "View renderer should be the P4CMS renderer."
        );

        $this->assertSame(
            $view,
            $renderer->view,
            "Renderer should have same view as bootstrap."
        );
    }

    /**
     * Verify request gets setup.
     */
    public function testInitRequest()
    {
        $this->_application->bootstrap('request');
        $request = $this->_bootstrap->getResource('request');
        $this->assertTrue(
            $request instanceof Zend_Controller_Request_Http,
            "Request resource should be a Zend request instance."
        );

        $this->_application->bootstrap('frontRequest');
        $front = $this->_bootstrap->getResource('frontController');
        $this->assertSame(
            $request,
            $front->getRequest(),
            "Front controller should have same request object as bootstrap."
        );
    }

    /**
     * Verify dojo gets enabled.
     */
    public function testInitViewHelpers()
    {
        $this->_application->bootstrap('viewHelpers');

        // ensure view has dojo helpers.
        $view = $this->_bootstrap->getResource('view');
        $helperPaths = $view->getHelperPaths();
        $this->assertTrue(
            in_array("Zend_Dojo_View_Helper_", array_keys($helperPaths)),
            "Dojo should be enabled on the view."
        );

        $this->assertTrue(
            Zend_Dojo_View_Helper_Dojo::useDeclarative(),
            "Dojo should be set to use declarative."
        );

        $dojo = $view->getHelper('dojo');
        $this->assertTrue(
            $dojo instanceof Zend_Dojo_View_Helper_Dojo,
            "Should be able to get dojo helper from view."
        );

        $baseUrl = $this->_bootstrap->getResource('request')->baseUrl;
        $this->assertSame(
            $dojo->getLocalPath(),
            $baseUrl . '/application/dojo/resources/dojo/dojo.js',
            "Dojo script path doesn't match expectations."
        );
    }

    /**
     * Ensure setup program runs when appropriate.
     */
    public function testInitSetupNeeded()
    {
        // ensure there are no sites.
        $this->utility->removeSites();

        // we have no sites, therefore setup should be required
        // this should be evident in that setup has been forced.
        $this->_application->bootstrap('setup');
        $request = $this->_bootstrap->getResource('request');
        $this->assertSame(
            $request->getPathInfo(),
            '/setup',
            "Request path info should point to setup module."
        );
    }

    /**
     * Ensure setup program runs when appropriate.
     */
    public function testInitSetupNotNeeded()
    {
        $this->_mockSetup();

        // we have sites, therefore setup should not be required
        // this should be evident in that setup has not been forced.
        $this->_application->bootstrap('setup');
        $request = $this->_bootstrap->getResource('request');
        $this->assertSame(
            $request->getPathInfo(),
            '/',
            "Request path info should not point to setup module."
        );
    }

    /**
     * Ensure that logger is created and put in registry.
     * Attempt to log something.
     */
    public function testInitLog()
    {
        $this->_application->bootstrap('log');

        $this->assertTrue(
            $this->_bootstrap->hasResource('log'),
            "Bootstrap should have logger"
        );

        $this->assertTrue(
            P4Cms_Log::hasLogger(),
            "Logger should be setup."
        );

        $logger = P4Cms_Log::getLogger();
        $this->assertTrue(
            $logger instanceof Zend_Log,
            "Logger should be instance of Zend_Log"
        );

        try {
            $logger->info("test");
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail("Should be able to write to log.");
        }
    }

    /**
     * Ensure site gets loaded when app is setup.
     */
    public function testInitSiteWhenSetupDone()
    {
        $this->_mockSetup();

        // there should be an active site.
        $this->_application->bootstrap('loadSite');
        $this->assertTrue(P4Cms_Site::hasActive(), "There should be an active site.");

        // site should have config and connection.
        $site = P4Cms_Site::fetchActive();
        $this->assertTrue($site instanceof P4Cms_Site);
        $this->assertTrue($site->getConnection() instanceof P4_Connection_Interface);
        $this->assertTrue($site->getConfig() instanceof P4Cms_Site_Config);
    }

    /**
     * Ensure no site is loaded when app needs setup.
     */
    public function testInitSiteWhenSetupNeeded()
    {
        // ensure there are no sites.
        $this->utility->removeSites();

        // there should be no active site.
        $this->_application->bootstrap('loadSite');
        $this->assertFalse(P4Cms_Site::hasActive(), "There should be no active site.");
    }

    /**
     * Verify we get the first site for an invalid url
     */
    public function testInitInvalidSite()
    {
        // ensure there are sites and a perforce connection
        $this->_mockSetup();

        // fake a request for a host with no matching site.
        $_SERVER['HTTP_HOST'] = 'http://0/';

        $this->_application->bootstrap('loadSite');

        $site  = $this->_bootstrap->getResource('loadSite');
        $sites = P4Cms_Site::fetchAll();
        $this->assertSame(
            $sites->first()->getValues(),
            $site->getValues(),
            "Bootstrap should have loaded first site."
        );
    }

    /**
     * Ensure that front controller is configured for a
     * modular directory structure and that it knows how to find
     * the default ('page') module.
     */
    public function testInitFrontController()
    {
        $this->_application->bootstrap('frontController');
        $front = Zend_Controller_Front::getInstance();
        $moduleDirectory = $front->getModuleDirectory('content');
        $this->assertTrue($moduleDirectory == APPLICATION_PATH . DIRECTORY_SEPARATOR . 'content');
    }

    /**
     * Ensure layout is setup and mvc is started with the correct layout path.
     */
    public function testInitLayout()
    {
        $this->_application->bootstrap('layout');
        $layout = Zend_Layout::getMvcInstance();
        $this->assertTrue($layout != null);
        $this->assertTrue(
            $layout->getMvcEnabled(),
            "MVC layout should be enabled."
        );
        $this->assertSame(
            $layout->getLayout(),
            "default-layout",
            "Current layout should be default-layout."
        );
        $this->assertTrue($layout->getLayoutPath() == null);
    }

    /**
     * Ensure bootstrap sets active user.
     */
    public function testInitAnonymousUserNoSites()
    {
        $this->assertFalse(P4Cms_User::hasActive(), "Expected no active user.");

        $this->_application->bootstrap('user');
        $user = $this->_bootstrap->getResource('user');

        $this->assertTrue(P4Cms_User::hasActive(), "Expected active user");
        $this->assertTrue($user instanceof P4Cms_User, "Expected user instance.");
        $this->assertSame($user, P4Cms_User::fetchActive(), "Expected matching user instances.");
        $this->assertTrue($user->isAnonymous(), "Expected anonymous user");
    }

    /**
     * Ensure bootstrap sets active anonymous user.
     */
    public function testInitAnonymousUserWithSites()
    {
        $this->assertFalse(P4Cms_User::hasActive(), "Expected no active user.");

        $this->utility->createTestSites();
        $this->_application->bootstrap('loadSite');
        $this->_application->bootstrap('user');
        $user = $this->_bootstrap->getResource('user');

        $this->assertTrue(P4Cms_User::hasActive(), "Expected active user");
        $this->assertTrue($user instanceof P4Cms_User, "Expected user instance.");
        $this->assertSame($user, P4Cms_User::fetchActive(), "Expected matching user instances.");
        $this->assertTrue($user->isAnonymous(), "Expected anonymous user");
    }

    /**
     * Ensure bootstrap sets active user.
     */
    public function testInitAuthenticatedUserNoSites()
    {
        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($this->p4);
        $user = new P4Cms_User;
        $user->setAdapter($adapter)
             ->setId($this->p4->getUser())
             ->setPassword($this->p4->getPassword());
        $auth = Zend_Auth::getInstance();
        $auth->authenticate($user);

        $this->_application->bootstrap('user');
        $this->_bootstrap->getResource('user');

        $this->assertTrue(P4Cms_User::hasActive(), "Expected active user");
        $this->assertTrue(P4Cms_User::fetchActive() instanceof P4Cms_User, "Expected user instance.");
        $this->assertTrue(P4Cms_User::fetchActive()->isAnonymous(), "Expected anonymous user");
    }

    /**
     * Ensure bootstrap sets active user.
     */
    public function testInitAuthenticatedUserWithSites()
    {
        $this->_mockSetup();

        $this->_application->bootstrap('loadSite');

        $user = new P4Cms_User;
        $user->setId($this->p4->getUser())
             ->setPassword($this->p4->getPassword());
        $auth = Zend_Auth::getInstance();
        $auth->authenticate($user);

        $this->_application->bootstrap('user');
        $this->_bootstrap->getResource('user');

        $this->assertTrue(P4Cms_User::hasActive(), "Expected active user");
        $this->assertTrue(P4Cms_User::fetchActive() instanceof P4Cms_User, "Expected user instance.");
        $this->assertFalse(P4Cms_User::fetchActive()->isAnonymous(), "Expected authenticated user");
        $this->assertSame($user->getId(), P4Cms_User::fetchActive()->getId(), "Expected matching user instances.");
    }

    /**
     * Ensure bootstrap loads all enabled modules.
     */
    public function testInitModulesSetupNeeded()
    {
        // ensure there are no sites.
        $this->utility->removeSites();

        $this->_application->bootstrap('modules');
        $modules = $this->_bootstrap->getResource('modules');

        // ensure that no modules returned.
        $this->assertTrue($modules === null, "Expected no modules.");
    }

    /**
     * Ensure bootstrap loads all enabled modules.
     */
    public function testInitModulesSetupDone()
    {
        $this->_mockSetup();

        $this->_application->bootstrap('modules');
        $modules = $this->_bootstrap->getResource('modules');

        // ensure there are modules.
        $this->assertFalse(count($modules) === 0, "Expected modules.");

        foreach ($modules as $module) {
            $this->assertTrue(
                $module->isLoaded(),
                "Expected " . $module->getName() . " module to be loaded."
            );
        }
    }

    /**
     * Ensure caching options default to the expected values
     */
    public function testCacheDefaults()
    {
        $options = Bootstrap::getDefaultOptions();

        // caching should be enabled.
        $this->assertTrue($options['resources']['cachemanager']['enabled']);

        // should configure a default cache manager template
        $this->assertTrue(is_array($options['resources']['cachemanager']['default']));

        // caching should be disabled for dev environment.
        $options = Bootstrap::getDefaultOptions('development');
        $this->assertFalse($options['resources']['cachemanager']['enabled']);
    }

    /**
     * Ensure cache manager is not loaded when application environment
     * is development or caching disabled - ensure it's loaded correctly
     * when conditions are right.
     */
    public function testInitCacheManager()
    {
        $this->utility->createTestSites();
        $site = P4Cms_Site::fetch(key($this->utility->getTestSites()));


        // ensure no static access to cache manager.
        $this->assertFalse(P4Cms_Cache::hasManager());

        // should be disabled for development env.
        $application = new P4Cms_Application('development');
        $application->bootstrap('cachemanager');
        $manager = $application->getBootstrap()->getResource('cachemanager');
        $this->assertFalse($manager instanceof Zend_Cache_Manager);

        // should be disabled if caching disabled explicitly.
        $application = new P4Cms_Application('production');
        $application->setOptions(
            $application->mergeOptions(
                $application->getOptions(),
                array('resources' => array('cachemanager' => array('enabled' => false)))
            )
        );
        $application->bootstrap('cachemanager');
        $application->bootstrap('globalcache');
        $application->bootstrap('sitecache');
        $manager = $application->getBootstrap()->getResource('cachemanager');
        $this->assertFalse($manager instanceof Zend_Cache_Manager);

        // should be present for production, but without
        // templates if cache path is not writable
        mkdir(DATA_PATH . DIRECTORY_SEPARATOR . 'cache', 0555);

        // on Windows, the mode 0555 in the mkdir function call above is ignored.
        // Since the data path should be writable, the subdirectory created under
        // it is also writable.
        // As a result, this part is not feasible to test under windows unless
        // we find a way to set a folder/file read-only on Windows.
        if (!P4_Environment::isWindows()) {
            $this->assertFalse(is_writable(DATA_PATH . DIRECTORY_SEPARATOR . 'cache'));
            $application = new P4Cms_Application('production');
            $application->bootstrap('cachemanager');
            $manager = $application->getBootstrap()->getResource('cachemanager');
            $this->assertTrue($manager instanceof Zend_Cache_Manager);
            $this->assertFalse($manager->hasCacheTemplate('global'));
        }

        // should auto-create cache path.
        rmdir(DATA_PATH . DIRECTORY_SEPARATOR . 'cache');
        $application = new P4Cms_Application('production');
        $application->bootstrap('cachemanager');
        $application->bootstrap('globalcache');
        $application->bootstrap('sitecache');
        $this->assertTrue(is_writable(DATA_PATH . DIRECTORY_SEPARATOR . 'cache'));

        // should be present w. a default template if cache
        // path exists and is writable.
        $this->assertTrue(is_writable(DATA_PATH . DIRECTORY_SEPARATOR . 'cache'));
        $application = new P4Cms_Application('production');
        $application->bootstrap('cachemanager');
        $application->bootstrap('globalcache');
        $application->bootstrap('sitecache');
        $manager = $application->getBootstrap()->getResource('cachemanager');
        $this->assertTrue($manager instanceof Zend_Cache_Manager);
        $this->assertTrue($manager->hasCacheTemplate('global'));

        // now ensure that we have static access via P4Cms_Cache.
        $this->assertTrue(P4Cms_Cache::hasManager());
        $this->assertSame($manager, P4Cms_Cache::getManager());

        // finally, verify we can put data in and get it out again.
        $data = array(1, 2, 3);
        P4Cms_Cache::save($data, 'test', array(), null, null, 'global');
        $this->assertSame($data, P4Cms_Cache::load('test', 'global'));
    }

    /**
     * Fake a configured application with perforce connection
     * information and sites.
     */
    protected function _mockSetup()
    {
        // mock perforce configuration.
        $this->_application = new P4Cms_Application(
            $this->_environment,
            array('resources' => array('perforce' => $this->utility->getP4Params()))
        );
        $this->_bootstrap   = $this->_application->getBootstrap();

        // create mock sites.
        $this->utility->createTestSites();
    }
}
