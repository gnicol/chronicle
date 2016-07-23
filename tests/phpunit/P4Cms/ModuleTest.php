<?php
/**
 * Test the module model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_ModuleTest extends TestCase
{
    /**
     * Add the path to the test modules and setup module
     * configuration capabilities.
     */
    public function setUp()
    {
        parent::setUp();
        P4Cms_Module::reset();
        P4Cms_Module::setCoreModulesPath(TEST_ASSETS_PATH . DIRECTORY_SEPARATOR . 'core-modules');
        P4Cms_Module::addPackagesPath(TEST_ASSETS_PATH . DIRECTORY_SEPARATOR . 'optional-modules');

        // storage adapter is needed for module config.
        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($this->p4)
                ->setBasePath("//depot");
        P4Cms_Record::setDefaultAdapter($adapter);
    }

    /**
     * Remove the path to the test modules and clear module
     * configuration capabilities.
     */
    public function tearDown()
    {
        P4Cms_Record::clearDefaultAdapter();
        P4Cms_Module::reset();

        parent::tearDown();
    }

    /**
     * Test that known modules can be fetched (and are properly populated),
     * and that non-existent modules throw a lookup exception.
     */
    public function testFetch()
    {
        $module = P4Cms_Module::fetch('Independent');
        $this->assertTrue($module instanceof P4Cms_Module);
        $this->assertTrue($module->getName() == 'Independent');
        $this->assertTrue(
            $module->getPath() == 
            TEST_ASSETS_PATH . DIRECTORY_SEPARATOR . 'optional-modules' . DIRECTORY_SEPARATOR . 'independent'
        );
        $this->assertTrue($module->getDescription() == 'A test module with no dependencies.');
        $this->assertTrue(is_array($module->getMaintainerInfo()));
        $this->assertTrue($module->getMaintainerInfo('name')  == 'Perforce Software');
        $this->assertTrue($module->getMaintainerInfo('email') == 'support@perforce.com');
        $this->assertTrue($module->getMaintainerInfo('url')   == 'http://www.perforce.com');
        $this->assertTrue($module->getVersion()        == '1.0');
        $this->assertTrue(count($module->getDependencies()) == 0);

        // test another known module.
        $module = P4Cms_Module::fetch('Dependent');
        $this->assertTrue($module instanceof P4Cms_Module);

        // test a core module.
        $module = P4Cms_Module::fetch('Core');
        $this->assertTrue($module instanceof P4Cms_Module);

        // ensure fetch of non-existent module fails.
        try {
            P4Cms_Module::fetch('asdfghjkl');
            $this->fail();
        } catch (P4Cms_Model_NotFoundException $e) {
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail();
        }
    }

    /**
     * Test that fetch all returns entire list of installed modules.
     */
    public function testFetchAll()
    {
        // count the core modules.
        $modules = array();
        foreach (new DirectoryIterator(TEST_ASSETS_PATH . DIRECTORY_SEPARATOR . 'core-modules') as $entry) {
            if ($entry->isDir() && !$entry->isDot()) {
                $modules[] = $entry->getBasename();
            }
        }

        // count the optional modules.
        foreach (new DirectoryIterator(TEST_ASSETS_PATH . DIRECTORY_SEPARATOR . 'optional-modules') as $entry) {
            if ($entry->isDir() && !$entry->isDot()) {
                $modules[] = $entry->getBasename();
            }
        }

        $moduleModels = P4Cms_Module::fetchAll();
        $this->assertTrue($moduleModels instanceof P4Cms_Model_Iterator);
        $this->assertTrue(count($moduleModels) == count(array_unique($modules)));
    }

    /**
     * Test fetching all enabled modules.
     */
    public function testFetchAllEnabled()
    {
        // ensure core modules appear.
        $modules = P4Cms_Module::fetchAllEnabled();
        $this->assertTrue(count($modules) == 1);

        // ensure core modules excluded when true passed.
        $modules = P4Cms_Module::fetchAllEnabled(true);
        $this->assertTrue(count($modules) == 0);

        // ensure that enabled modules appear in list.
        $module = P4Cms_Module::fetch('Independent');
        $module->enable();
        P4Cms_Module::clearConfigCache();
        $modules = P4Cms_Module::fetchAllEnabled(true);
        $this->assertTrue(count($modules) == 1);

        // ensure core modules appear alonside enabled ones.
        $modules = P4Cms_Module::fetchAllEnabled();
        $this->assertTrue(count($modules) == 2);

        // ensure that enabled modules don't share the same config file
        // see P4Cms_Module::_getConfigRecords(), static::$_configRecords array
        // index
        $module = P4Cms_Module::fetch('Dependent');
        $module->enable();
        P4Cms_Module::clearConfigCache();
        $modules = P4Cms_Module::fetchAllEnabled();
        $this->assertTrue(count($modules) == 3);
    }

    /**
     * Test fetchAll for disabled modules.
     */
    public function testFetchAllDisabled()
    {
        $modules = P4Cms_Module::fetchAllDisabled();
        $this->assertSame(
            3,
            count($modules),
            'Expected matching number of results'
        );
        foreach ($modules as $module) {
            $this->assertFalse(
                $module->isEnabled(),
                'Expected module to be disabled.'
            );
        }

        // ensure that enabled modules fall out of list.
        P4Cms_Module::fetch('Independent')->enable();
        $modules = P4Cms_Module::fetchAllDisabled();
        $this->assertSame(
            2,
            count($modules),
            'Expected matching number of modules'
        );
        $this->assertSame(
            'Dependent',
            $modules->current()->getName(),
            'Expected first module name to match'
        );
    }

    /**
     * Test idExists().
     */
    public function testIdExists()
    {
        $this->assertTrue(P4Cms_Module::exists('Independent'));
        $this->assertTrue(P4Cms_Module::exists('Dependent'));
        $this->assertFalse(P4Cms_Module::exists('NonExistant'));
    }

    /**
     * Test getConfig().
     */
    public function testGetConfig()
    {
        P4Cms_Module::fetch('Independent')->enable();
        $config = P4Cms_Module::fetch('Independent')->getConfig();
        $this->assertTrue($config instanceof Zend_Config);
    }

    /**
     * Test setConfig().
     */
    public function testSetConfig()
    {
        $module = P4Cms_Module::fetch('Independent');
        $config = new Zend_Config(array('foo'=>'bar'));
        $module->setConfig($config);
        $this->assertTrue($module->getConfig() instanceof Zend_Config);
        $this->assertTrue($module->getConfig()->foo == 'bar');
    }

    /**
     * Test saveConfig().
     */
    public function testSaveConfig()
    {
        // test basic usage of save config.
        $module = P4Cms_Module::fetch('Independent');
        $module->saveConfig(new Zend_Config(array('foo'=>'bar'), true));
        $module = P4Cms_Module::fetch('Independent');
        $this->assertTrue($module->getConfig() instanceof Zend_Config);
        $this->assertTrue($module->getConfig()->foo == 'bar');

        // test usage of save config via set config.
        $module->setConfig(new Zend_Config(array('baz'=>'bof'), true));
        $module->saveConfig();
        $module = P4Cms_Module::fetch('Independent');
        $this->assertTrue($module->getConfig() instanceof Zend_Config);
        $this->assertTrue($module->getConfig()->baz == 'bof');

        // test save config via reference.
        $config = $module->getConfig();
        $config->zig = 'zag';
        $module->saveConfig();
        $module = P4Cms_Module::fetch('Independent');
        $this->assertTrue($module->getConfig() instanceof Zend_Config);
        $this->assertTrue($module->getConfig()->zig == 'zag');
    }

    /**
     * Test isEnabled().
     */
    public function testIsEnabled()
    {
        $module = P4Cms_Module::fetch('Independent');
        $this->assertFalse($module->isEnabled());
        $module->enable();
        $this->assertTrue($module->isEnabled());
        $module = P4Cms_Module::fetch('Independent');
        $this->assertTrue($module->isEnabled());
    }

    /**
     * Test canDisable().
     */
    public function testCanDisable()
    {
        $module = P4Cms_Module::fetch('Core');
        $this->assertFalse($module->canDisable());
        $this->assertTrue($module->isEnabled());

        $module = P4Cms_Module::fetch('Independent');
        $this->assertFalse($module->canDisable());
        $this->assertFalse($module->isEnabled());

        $module->enable();
        $this->assertTrue($module->canDisable());
        $this->assertTrue($module->isEnabled());

        // now add known dependent.
        $dependent = P4Cms_Module::fetch('Dependent')->enable();
        $this->assertTrue($dependent->isEnabled());
        $this->assertTrue($dependent->canDisable());
        $this->assertFalse($module->canDisable());

        // remove dependency.
        $dependent->disable();
        $this->assertTrue($module->canDisable());
    }

    /**
     * Test disabling of modules.
     */
    public function testDisable()
    {
        // disable on core module should bork.
        $module = P4Cms_Module::fetch('Core');
        try {
            $module->disable();
            $this->fail("Disable of core module should fail.");
        } catch (P4Cms_Module_Exception $e) {
            $this->assertTrue(true);
        }

        // disable on disabled module should bork.
        $module = P4Cms_Module::fetch('Independent');
        try {
            $module->disable();
            $this->fail("Disable of disabled module should fail.");
        } catch (P4Cms_Module_Exception $e) {
            $this->assertTrue(true);
        }

        // now enable so that we can disable.
        $fluent = $module->enable();
        $this->assertTrue($module->isEnabled());

        // ensure fluent.
        $this->assertSame($module, $fluent);

        // ensure fetches as enabled.
        $fetched = P4Cms_Module::fetch('Independent');
        $this->assertTrue($fetched->isEnabled());

        // now test disable.
        $module->disable();
        $this->assertFalse($module->isEnabled());
    }

    /**
     * Test enabling a disabled module.
     */
    public function testEnable()
    {
        $module = P4Cms_Module::fetch('Independent');
        $enabledModule = $module->enable();
        $this->assertTrue($module->isEnabled());
        $this->assertTrue($enabledModule->isEnabled());
        $this->assertSame($module, $enabledModule);
    }

    /**
     * Test un-enablable module.
     */
    public function testUnenablable()
    {
        $moduleName = 'Unenablable';

        $module = P4Cms_Module::fetch($moduleName);

        try {
            $module->enable();
            $this->fail('Unexpected Success');
        } catch (P4Cms_Module_Exception $e) {
            $this->assertSame(
                "Failed to enable the 'Unenablable' module.",
                $e->getMessage(),
                'Expected matching error'
            );
        }
    }

    /**
     * Test canEnable.
     */
    public function testCanEnable()
    {
        $this->assertSame(
            false,
            P4Cms_Module::fetch('Independent')->isEnabled(),
            'Expected independent to be disabled'
        );

        $this->assertSame(
            false,
            P4Cms_Module::fetch('Dependent')->canEnable(),
            'Expected dependent to be disabled'
        );

        $this->assertSame(
            true,
            P4Cms_Module::fetch('Independent')->canEnable(),
            'Expected to be able to enable module.'
        );
    }

    /**
     * Test module initialization.
     */
    public function testInit()
    {
        // add another modules path to hit a different 'independent' module.
        P4Cms_Module::addPackagesPath(TEST_ASSETS_PATH . DIRECTORY_SEPARATOR . 'more-optional-modules');

        // enable will call init.
        $module = P4Cms_Module::fetch('Independent')->enable();

        // ensure module was added to loader.
        $foundPath = false;
        foreach (P4Cms_Loader::getPackagePaths() as $name => $path) {
            if ($name == $module->getName() && $path == $module->getPath()) {
                $foundPath = true;
            }
        }
        $this->assertTrue(
            $foundPath,
            "Failed to find Independent in enabled modules"
        );

        // ensure module controllers are added to front controller.
        $dir = Zend_Controller_Front::getInstance()->getControllerDirectory(basename($module->getPath()));
        $this->assertFalse(empty($dir));

        // ensure module layouts added to view.
        $foundPath = false;
        $view = Zend_Layout::getMvcInstance()->getView();
        foreach ($view->getScriptPaths() as $path) {
            if ($path == $module->getPath() . '/layouts/scripts/') {
                $foundPath = true;
            }
        }
        $this->assertTrue($foundPath, "Independent should have an enabled layout");

        // ensure form plugins paths are registered.
        P4Cms_Module::fetch('Core')->init();
        $prefixPaths = P4Cms_Form::getPrefixPathRegistry();
        $this->assertTrue(
            array_key_exists('Core_Form_Element', $prefixPaths),
            "Expected 'Core_Form_Element' prefix path to be registered."
        );
    }

    /**
     * Test module loading.
     */
    public function testLoad()
    {
        // enable and load a module.
        $module = P4Cms_Module::fetch('Independent')->enable();
        $module->load();

        // ensure all dojo modules are added to dojo.
        $view = P4Cms_Module::getView();
        $dojoModules = $module->getDojoModules();
        foreach ($dojoModules as $dojoModule) {
            $suppliedName = $dojoModule['namespace'];
            $suppliedUrl  = $dojoModule['path'];
            $foundModule = false;
            foreach ($view->dojo()->getModulePaths() as $foundName => $foundUrl) {
                if ($suppliedName == $foundName && $suppliedUrl == $foundUrl) {
                    $foundModule = true;
                }
            }
            $this->assertTrue(
                $foundModule,
                "Failed to find module '$suppliedName' in enabled dojo modules"
            );
        }

        // ensure all routes are added to the router.
        $router = Zend_Controller_Front::getInstance()->getRouter();
        $routes = $module->getRoutes();
        foreach ($routes as $name => $route) {
            $this->assertTrue($router->hasRoute($name), "Failed to hasRoute");
            $this->assertEquals(
                get_class($router->getRoute($name)),
                $route['type'],
                "Failed to match route type"
            );
            $this->assertEquals(
                $router->getRoute($name)->getDefaults(),
                $route['defaults'],
                "Failed to match route defaults"
            );
            $this->assertTrue(
                $router->getRoute($name)->match($route['route']) !== false,
                "Expected route regex to be a 'match'"
            );
        }
    }

    /**
     * Test module dependencies.
     */
    public function testDependencies()
    {
        // dependent module should report dependencies.
        $this->assertTrue(P4Cms_Module::fetch('Dependent')->hasDependencies());

        // dependent module should have two dependencies
        // one module and one library.
        $dependencies = array(
            array(
                'name'      => 'P4Cms',
                'type'      => 'library',
                'versions'  => array('1.*')
            ),
            array(
                'name'      => 'Independent',
                'type'      => 'module',
                'versions'  => array('*')
            )
        );
        $this->assertSame(
            $dependencies,
            P4Cms_Module::fetch('Dependent')->getDependencies(),
            "Expected matching dependencies array."
        );

        // independent module should report no dependencies.
        $this->assertFalse(P4Cms_Module::fetch('Independent')->hasDependencies());

        // test dependency satisfaction code.
        $module = P4Cms_Module::fetch('Dependent');
        $this->assertFalse(
            $module->areDependenciesSatisfied(),
            "Dependent module should have missing dependencies."
        );
        P4Cms_Module::fetch('Independent')->enable();
        $this->assertTrue(
            $module->areDependenciesSatisfied(),
            "Dependent module should have all dependencies."
        );
        $module->enable();

        // test arbitrary dependencies.
        $tests = array(
            array(
                "name"      => "FooModule",
                "type"      => "module",
                "versions"  => "*",
                "expected"  => false,
                "label"     => __LINE__ . " unexpected result."
            ),
            array(
                "name"      => "Dependent",
                "type"      => "module",
                "versions"  => "*",
                "expected"  => true,
                "label"     => __LINE__ . " unexpected result."
            ),
            array(
                "name"      => "Dependent",
                "type"      => "module",
                "versions"  => "1.0",
                "expected"  => true,
                "label"     => __LINE__ . " unexpected result."
            ),
            array(
                "name"      => "Dependent",
                "type"      => "module",
                "versions"  => "2.0",
                "expected"  => false,
                "label"     => __LINE__ . " unexpected result."
            ),
            array(
                "name"      => "Dependent",
                "type"      => "module",
                "versions"  => "0.9",
                "expected"  => false,
                "label"     => __LINE__ . " unexpected result."
            ),
            array(
                "name"      => "Dependent",
                "type"      => "module",
                "versions"  => array("1.0", "2.0", "3.0"),
                "expected"  => true,
                "label"     => __LINE__ . " unexpected result."
            ),
            array(
                "name"      => "Dependent",
                "type"      => "module",
                "versions"  => array("0.1", "2.0", "1.0.beta"),
                "expected"  => false,
                "label"     => __LINE__ . " unexpected result."
            ),
            array(
                "name"      => "Dependent",
                "type"      => "module",
                "versions"  => array("1.*"),
                "expected"  => true,
                "label"     => __LINE__ . " unexpected result."
            ),
            array(
                "name"      => "Dependent",
                "type"      => "module",
                "versions"  => array("1.?"),
                "expected"  => true,
                "label"     => __LINE__ . " unexpected result."
            ),
            array(
                "name"      => "Dependent",
                "type"      => "module",
                "versions"  => array("1?", "2.*"),
                "expected"  => false,
                "label"     => __LINE__ . " unexpected result."
            ),
            array(
                "name"      => "Dependent",
                "type"      => "module",
                "versions"  => array("1.[0245]", "2.*"),
                "expected"  => true,
                "label"     => __LINE__ . " unexpected result."
            ),
            array(
                "name"      => "Dependent",
                "type"      => "module",
                "versions"  => array("1.0*"),
                "expected"  => true,
                "label"     => __LINE__ . " unexpected result."
            ),
            array(
                "name"      => "P4Cms",
                "type"      => "library",
                "versions"  => array("1.0*"),
                "expected"  => true,
                "label"     => __LINE__ . " unexpected result."
            ),
            array(
                "name"      => "P4Cms",
                "type"      => "library",
                "versions"  => array("1.1"),
                "expected"  => false,
                "label"     => __LINE__ . " unexpected result."
            ),
            array(
                "name"      => "Zend",
                "type"      => "library",
                "versions"  => array("1.*"),
                "expected"  => true,
                "label"     => __LINE__ . " unexpected result."
            ),
            array(
                "name"      => "FooBar",
                "type"      => "library",
                "versions"  => array("1.0*"),
                "expected"  => false,
                "label"     => __LINE__ . " unexpected result."
            ),
        );

        foreach ($tests as $test) {
            $this->assertSame(
                $test['expected'],
                P4Cms_Module::isDependencySatisfied(
                    $test['name'],
                    $test['type'],
                    $test['versions']
                ),
                $test['label']
            );
        }
    }

    /**
     * Test hasDependents().
     */
    public function testHasDependents()
    {
        // test module with no dependents.
        $module = P4Cms_Module::fetch('Independent')->enable();
        $this->assertFalse($module->hasDependents());

        // enable a known dependent.
        P4Cms_Module::fetch('Dependent')->enable();
        $this->assertTrue($module->hasDependents());
    }

    /**
     * Test missing dependency enable.
     */
    public function testMissingDependencyEnable()
    {
        $moduleName = 'Dependent';

        $module = P4Cms_Module::fetch($moduleName);

        try {
            $module->enable();
            $this->fail('Unexpected Success');
        } catch (P4Cms_Module_Exception $e) {
            $this->assertSame(
                "Can't enable the '" . $moduleName .
                "' module. One or more dependencies are not satisfied.",
                $e->getMessage(),
                'Expected matching error'
            );
        }
    }

    /**
     * Test getIntegrationClassName().
     */
    public function testIntegrationClassName()
    {
        $module = P4Cms_Module::fetch('Independent');
        $this->assertSame($module->getIntegrationClassName(), 'Independent_Module');
        $module = P4Cms_Module::fetch('Dependent');
        $this->assertSame($module->getIntegrationClassName(), 'Dependent_Module');
    }

    /**
     * Test hasIntegrationClass().
     */
    public function testHasIntegrationClass()
    {
        // add another modules path to hit a different 'independent' module.
        P4Cms_Module::addPackagesPath(TEST_ASSETS_PATH . '/more-optional-modules');

        $module = P4Cms_Module::fetch('Independent')->enable();
        $this->assertTrue($module->hasIntegrationClass());

        $module = P4Cms_Module::fetch('Dependent')->enable();
        $this->assertFalse($module->hasIntegrationClass());
    }

    /**
     * Test callIntegrationMethod().
     */
    public function testCallIntegrationMethod()
    {
        // add another modules path to hit a different 'independent' module.
        P4Cms_Module::addPackagesPath(TEST_ASSETS_PATH . '/more-optional-modules');

        $module = P4Cms_Module::fetch('Independent')->enable();
        $this->assertTrue($module->callIntegrationMethod('load'));
        $input  = array('a','b','c');
        $output = $module->callIntegrationMethod('returnInput', array($input));
        $this->assertTrue($output == $input);
    }

    /**
     * Test isConfigurable().
     */
    public function testIsConfigurable()
    {
        // add another modules path to hit a different 'independent' module.
        P4Cms_Module::addPackagesPath(TEST_ASSETS_PATH . '/more-optional-modules');

        $module = P4Cms_Module::fetch('Independent')->enable();
        $this->assertTrue($module->isConfigurable());

        $module = P4Cms_Module::fetch('Dependent')->enable();
        $this->assertFalse($module->isConfigurable());
    }

    /**
     * Test order of module paths.
     */
    public function testModulePathsPrecedence()
    {
        $module = P4Cms_Module::fetch('Core');
        $this->assertSame($module->getPath(), $module->getCoreModulesPath() . DIRECTORY_SEPARATOR . 'core');
    }

    /**
     * Test module-provided view helpers.
     */
    public function testModuleViewHelper()
    {
        // add p4cms library helpers to a view.
        $view = new Zend_View;
        $view->addHelperPath(
            LIBRARY_PATH . '/P4Cms/View/Helper/',
            'P4Cms_View_Helper_'
        );

        $helper = $view->getHelper('module');

        // ensure disabled modules can't be fetched via helper.
        try {
            $helper->module('Independent');
            $this->fail("Should not be able to get disabled module via helper.");
        } catch (P4Cms_Module_Exception $e) {
            $this->assertTrue(true);
        }

        $module = P4Cms_Module::fetch('Independent')->enable();
        $helper = $view->getHelper('module');
        $this->assertTrue($helper instanceof P4Cms_View_Helper_Module);
        $this->assertTrue($helper->module('Independent') instanceof P4Cms_Module);
    }
}
