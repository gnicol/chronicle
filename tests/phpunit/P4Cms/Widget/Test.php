<?php
/**
 * Test the widget model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Widget_Test extends TestCase
{
    protected $_testType;

    /**
     * Ensure we're using a known connection/adapter, and limit
     * module participation severely to remove external influences.
     */
    public function setUp()
    {
        parent::setUp();

        // set the test connection as the default connection for the environment.
        P4_Connection::setDefaultConnection($this->p4);

        // storage adapter is needed for module config.
        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($this->p4)
                ->setBasePath("//depot/records");
        P4Cms_Record::setDefaultAdapter($adapter);

        // override module paths to avoid potential contamination from other modules
        P4Cms_Module::reset();
        P4Cms_Module::setCoreModulesPath(TEST_ASSETS_PATH . '/core-modules');
        P4Cms_Module::addPackagesPath(TEST_SITES_PATH . '/test/modules');
        P4Cms_Module::fetchAllEnabled()->invoke('init');

        // ensure that our widgets test module is loaded
        $module = P4Cms_Module::fetch('Widgets');
        $module->enable();
        $module->load();

        // create a widget type
        $type = new P4Cms_Widget_Type;
        $type->setId('test')
             ->setValue('module',     'widgets')
             ->setValue('controller', 'index');
        P4Cms_Widget_Type::addType($type);
        $this->assertTrue(P4Cms_Widget_Type::exists('test'), 'Expect the test type to exist.');
        $this->_testType = $type;
    }

    /**
     * Test behaviour of fetchAll
     */
    public function testFetchAll()
    {
        $widget = P4Cms_Widget::create()
                ->setId('test')
                ->setConfig(array('foo' => 'bar'))
                ->save();

        $widgets = P4Cms_Widget::fetchAll();
        $this->assertEquals(1, count($widgets), 'Expected widget count');
        $this->assertSame('bar', $widgets->first()->getConfig('foo'), 'Expected value');
        $options = $widget->getConfigAsArray();
        $this->assertSame('bar', $options['foo'], 'Expected options value');
    }

    /**
     * Test behaviour of factory.
     */
    public function testFactory()
    {
        try {
            $widget = P4Cms_Widget::factory('foo');
            $this->fail('Expected factory to fail with non-existant type');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4Cms_Widget_Exception $e) {
            $this->assertSame(
                'Cannot create widget. The given widget type is invalid.',
                $e->getMessage(),
                'Expected error message with non-existant type'
            );
        }

        $widget = P4Cms_Widget::factory('test');
        $this->assertTrue($widget instanceof P4Cms_Widget, 'Expected a widget using valid type');
    }

    /**
     * Test behaviour of setType
     */
    public function testSetType()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'type'      => null,
                'error'     => null,
            ),
            array(
                'label'     => __LINE__ .': int',
                'type'      => 1,
                'error'     => array(
                    'InvalidArgumentException'   => 'Widget type must be a string, a widget type instance or null.',
                ),
            ),
            array(
                'label'     => __LINE__ .': string',
                'type'      => 'test',
                'error'     => null,
            ),
            array(
                'label'     => __LINE__ .': P4Cms_Widget_Type',
                'type'      => $this->_testType,
                'error'     => null,
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $widget = new P4Cms_Widget;
            try {
                $widget->setType($test['type']);
                if ($test['error']) {
                    $this->fail("$label - Unexpected success");
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (Exception $e) {
                if (!$test['error']) {
                    $this->fail("$label - Unexpected exception (". get_class($e) .') :'. $e->getMessage());
                } else {
                    list($class, $error) = each($test['error']);
                    $this->assertEquals(
                        $class,
                        get_class($e),
                        "$label - expected exception class: ". $e->getMessage()
                    );
                    $this->assertEquals(
                        $error,
                        $e->getMessage(),
                        "$label - expected exception message"
                    );
                }
            }
        }
    }

    /**
     * Test behaviour of getType.
     */
    public function testGetType()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': fresh',
                'widget'    => new P4Cms_Widget,
                'error'     => array(
                    'P4Cms_Widget_Exception'   => 'Cannot get widget type. The type has not been set.',
                ),
            ),
            array(
                'label'     => __LINE__ .': existing',
                'widget'    => P4Cms_Widget::factory('test'),
                'error'     => null,
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $widget = $test['widget'];
            try {
                $widget->getType();
                if ($test['error']) {
                    $this->fail("$label - Unexpected success");
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (Exception $e) {
                if (!$test['error']) {
                    $this->fail("$label - Unexpected exception (". get_class($e) .') :'. $e->getMessage());
                } else {
                    list($class, $error) = each($test['error']);
                    $this->assertEquals(
                        $class,
                        get_class($e),
                        "$label - expected exception class: ". $e->getMessage()
                    );
                    $this->assertEquals(
                        $error,
                        $e->getMessage(),
                        "$label - expected exception message"
                    );
                }
            }
        }
    }

    /**
     * Test run behaviour.
     */
    public function testRun()
    {
        // this should not be successful
        $widget = P4Cms_Widget::factory('test');
        $output = $widget->run();
        $this->assertSame('', $output, 'Expected no output.');
        $this->assertTrue($widget->hasError(), 'Expected an error.');
        $this->assertTrue($widget->hasException(), 'Expected an exception.');
        $this->assertSame(
            'Action view helper requires both a registered request'
            . ' and response object in the front controller instance',
            $widget->getError(),
            'Expected error message.'
        );
        $this->assertSame(
            'Action view helper requires both a registered request'
            . ' and response object in the front controller instance',
            $widget->getException()->getMessage(),
            'Expected exception message.'
        );

        // and again, with throw exceptions
        try {
            $output = $widget->run(true);
            $this->fail('Unexpected success with throw exceptions.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (Exception $e) {
            $this->assertSame(
                'Action view helper requires both a registered request'
                . ' and response object in the front controller instance',
                $e->getMessage(),
                'Expected exception message with throw exceptions.'
            );
        }
    }

    /**
     * Test behaviour of getError.
     */
    public function testGetError()
    {
        $widget = P4Cms_Widget::factory('test');
        try {
            $widget->getError();
            $this->fail('Unexpected success prior to run.');
        } catch (P4Cms_Widget_Exception $e) {
            $this->assertSame(
                'Cannot get error. No error occurred.',
                $e->getMessage(),
                'Expected exception message'
            );
        }

        $output = $widget->run();
        $this->assertSame(
            'Action view helper requires both a registered request'
            . ' and response object in the front controller instance',
            $widget->getError(),
            'Expected error message.'
        );
    }

    /**
     * Test behaviour of getException.
     */
    public function testGetException()
    {
        $widget = P4Cms_Widget::factory('test');
        try {
            $widget->getException();
            $this->fail('Unexpected success prior to run.');
        } catch (P4Cms_Widget_Exception $e) {
            $this->assertSame(
                'Cannot get exception. No exception occurred.',
                $e->getMessage(),
                'Expected exception message'
            );
        }

        $output = $widget->run();
        $this->assertSame(
            'Action view helper requires both a registered request'
            . ' and response object in the front controller instance',
            $widget->getException()->getMessage(),
            'Expected exception message.'
        );
    }

    /**
     * Test behaviour of isAsynchronous.
     */
    public function testIsAsynchronous()
    {
        $widget = P4Cms_Widget::factory('test');
        $this->assertTrue($widget instanceof P4Cms_Widget, 'Expected a widget using valid type');
        $this->assertFalse($widget->isAsynchronous(), 'Expected false');

        $widget->asynchronous = true;
        $this->assertTrue($widget->isAsynchronous(), 'Expected true');
    }

    /**
     * Test save/load of options.
     */
    public function testSaveLoadOptions()
    {
        $widget = P4Cms_Widget::factory('test');
        $options = array(
            'foo'       => 'bar',
            'test'      => 1,
            'another'   => 'xyz123',
        );
        $widget->setId(1)
               ->setConfig($options)
               ->save();

        $fetchedWidget = P4Cms_Widget::fetch(1);
        $this->assertSame(
            $options,
            $fetchedWidget->getConfigAsArray(),
            'Expected options'
        );

        $another = $fetchedWidget->getConfig('another');
        $this->assertSame($options['another'], $another, 'Expected value for another.');
    }

    /**
     * Test removing/adding package defaults
     */
    public function testRemoveAddPackageDefaults()
    {
        // load default theme.
        P4Cms_Theme::addPackagesPath(TEST_SITES_PATH . '/test/themes');
        P4Cms_Theme::setDocumentRoot(TEST_SITES_PATH);
        $theme = P4Cms_Theme::fetch('test');
        $theme->load();

        $adapter = P4Cms_Record::getDefaultAdapter();
        $theme = P4Cms_Theme::fetchActive();

        // initial widget check
        $widgets = P4Cms_Widget::fetchAll();
        $this->assertEquals(0, count($widgets), 'Expected 0 widgets at start');

        // install the defaults
        P4Cms_Widget::installPackageDefaults($theme, $adapter, true);
        $widgets = P4Cms_Widget::fetchAll();
        $this->assertEquals(1, count($widgets), 'Expected 1 widget after install package defaults');

        // install the defaults again, to verify that widgets don't double up
        P4Cms_Widget::installPackageDefaults($theme, $adapter, true);
        $widgets = P4Cms_Widget::fetchAll();
        $this->assertEquals(1, count($widgets), 'Expected 1 widget after second pass install package defaults');

        // remove the defaults
        P4Cms_Widget::removePackageDefaults($theme, $adapter);
        $widgets = P4Cms_Widget::fetchAll();
        $this->assertEquals(0, count($widgets), 'Expected 0 widget after remove package defaults');
    }

    /**
     * Test the behaviour of fetching by region.
     */
    public function testSaveAndFetchByRegion()
    {
        // fresh region, fetch should return empty list.
        $this->assertEquals(
            0,
            count(P4Cms_Widget::fetchByRegion('foo')),
            'Expected no widgets from fresh region.'
        );

        // add a widget with an invalid type
        $title = 'A test widget';
        $widget = P4Cms_Widget::create()
                ->setValue('title', $title)
                ->setValue('type', 'doesnotexist')
                ->setValue('region', 'foo')
                ->save();

        $title = 'A test widget2';
        $widget = P4Cms_Widget::create()
                ->setValue('title', $title)
                ->setValue('type', 'test')
                ->setValue('region', 'foo')
                ->save();
        $expectedTitles = array($title);

        // getWidgets should now return 1 widget
        $titles = array();
        foreach (P4Cms_Widget::fetchByRegion('foo') as $widget) {
            $titles[] = $widget->getValue('title');
        }
        $this->assertEquals(
            $expectedTitles,
            $titles,
            'Expected 1 widget after adding one valid and one invalid type.'
        );
    }

    /**
     * Test the behaviour of delete.
     */
    public function testDelete()
    {
        // fresh region, fetch should return empty list.
        $this->assertEquals(
            0,
            count(P4Cms_Widget::fetchByRegion('foo')),
            'Expected no widgets from fresh region.'
        );

        // fresh region, add a widget, fetch should return 1 widget.
        $widgets = array();
        $titles  = array();
        for ($i = 0; $i < 5; $i++) {
            $widget = P4Cms_Widget::create()
                    ->setId($i+1)
                    ->setValue('title', "Test widget #$i")
                    ->setValue('type', 'test')
                    ->setValue('region', 'foo')
                    ->save();
        }

        $this->assertEquals(
            5,
            count(P4Cms_Widget::fetchByRegion('foo')),
            'Expected matching count post bulk add.'
        );

        // now try deleting a widget
        P4Cms_Widget::remove(5);
        $this->assertEquals(
            4,
            count(P4Cms_Widget::fetchByRegion('foo')),
            'Expected matching count post delete.'
        );
    }

    /**
     * Verify deleting an unknown widget id throws
     *
     * @expectedException P4_File_Exception
     */
    public function testBadDelete()
    {
        P4Cms_Widget::remove(5);
    }

    /**
     * Verify a simple fetch and exists check functions
     */
    public function testExistsFetch()
    {
        $this->assertFalse(P4Cms_Widget::exists('widgeto'), 'expected to not exist at start');

        P4Cms_Widget::create()
                    ->setId('widgeto')
                    ->setValue('title', "Test widget #1")
                    ->setValue('type', 'test')
                    ->setValue('region', 'foo')
                    ->save();

        $this->assertTrue(P4Cms_Widget::exists('widgeto'), 'expected to exist after add');

        $widget = P4Cms_Widget::fetch('widgeto');

        $this->assertTrue($widget instanceof P4Cms_Widget, 'expected correct type on fetch');
        $this->assertSame('Test widget #1', $widget->getValue('title'), 'expected correct title');
    }

}
