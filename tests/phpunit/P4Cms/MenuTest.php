<?php
/**
 * Test the menu model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_MenuTest extends TestCase
{
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
    }

    /**
     * Test behaviour of fetchAll
     */
    public function testFetchAll()
    {
        $menu = new P4Cms_Menu;
        $menu->setId('test');

        $container = new P4Cms_Navigation;
        $container->addPage(
            array(
                'label'      => 'Users',
                'uri'        => 'http://google.com',
                'order'      => 3,
                'class'      => 'users'
            )
        );

        $menu->setContainer($container)->save();

        $menus = P4Cms_Menu::fetchAll();
        $this->assertEquals(1, count($menus), 'Expected menu count');
    }

    /**
     * Test behaviour of fetchMixed
     */
    public function testFetchMixed()
    {
        $menu = new P4Cms_Menu;
        $menu->setId('test');

        $container = new P4Cms_Navigation;
        $container->addPage(
            array(
                'label'      => 'Users',
                'uri'        => 'http://google.com',
                'order'      => 3,
                'class'      => 'users',
                'uuid'       => 'usersItem',
                'pages'      => array(
                    array(
                        'label'      => 'Users Sub',
                        'uri'        => 'http://google2.com',
                        'order'      => 1,
                        'class'      => 'usersSub',
                        'uuid'       => 'usersSubItem',
                    )
                )
            )
        );

        $menu->setContainer($container)->save();

        $items = P4Cms_Menu::fetchMixed();

        $this->assertSame(
            array('test', 'test/usersItem', 'test/usersSubItem'),
            $items->invoke('getId'),
            'expected matching ids'
        );

        $this->assertSame(
            array(null, 'test', 'test/usersItem'),
            $items->invoke('getParentId'),
            'expected matching parent ids'
        );
    }

    /**
     * Test behaviour of getItemId
     */
    public function testGetItemId()
    {
        $menu = new P4Cms_Menu;
        $menu->setId('test');

        $container = new P4Cms_Navigation;
        $container->addPage(
            array(
                'label'      => 'Users',
                'uri'        => 'http://google.com',
                'order'      => 3,
                'class'      => 'users'
            )
        );

        $menu->setContainer($container);
        $pages = $menu->getContainer()->getPages();
        $id = $menu->getItemId($pages[0]);
        $this->assertFalse(isset($id), 'Expect no id until menu saved');

        $menu->save();
        $pages = $menu->getContainer()->getPages();
        $id = $menu->getItemId($pages[0]);
        $this->assertTrue(isset($id), 'Expect id after menu saved');
    }

    /**
     * Test behaviour of getContainer
     */
    public function testGetContainer()
    {
        $menu = new P4Cms_Menu;
        $menu->setId('test');

        $container = $menu->getContainer();
        $this->assertTrue($container instanceof P4Cms_Navigation, 'Expected to receive a navigation container');
    }

    /**
     * Test behaviour of setContainer
     */
    public function testSetContainer()
    {
        $tests = array(
            array(
                'label' => __LINE__ .': null',
                'container' => null,
                'error'     => null,
                'expected'  => new P4Cms_Navigation(),
            ),
            array(
                'label' => __LINE__ .': numeric',
                'container' => 123.45,
                'error'     => array(
                    'InvalidArgumentException'
                    => 'Cannot set container, expected Zend_Navigation_Container, array or null.'
                ),
                'expected'  => new P4Cms_Navigation(),
            ),
            array(
                'label' => __LINE__ .': string',
                'container' => 'a string',
                'error'     => array(
                    'InvalidArgumentException'
                    => 'Cannot set container, expected Zend_Navigation_Container, array or null.'
                ),
                'expected'  => new P4Cms_Navigation(),
            ),
            array(
                'label' => __LINE__ .': array',
                'container' => array(array('uri' => 'one'), array('uri' => 'two')),
                'error'     => null,
                'expected'  => new P4Cms_Navigation(array(array('uri' => 'one'), array('uri' => 'two'))),
            ),
            array(
                'label' => __LINE__ .': Zend_Navigation_Container',
                'container' => new Zend_Navigation,
                'error'     => null,
                'expected'  => new P4Cms_Navigation(),
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];

            $menu = new P4Cms_Menu;
            $menu->setId('test');
            try {
                $menu->setContainer($test['container']);
                if (isset($test['error'])) {
                    $this->fail("$label - Unexpected success");
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (Exception $e) {
                if (isset($test['error'])) {
                    list($class, $message) = each($test['error']);
                    $this->assertSame(
                        $class,
                        get_class($e),
                        "$label - expected exception type: ". $e->getMessage()
                    );
                    $this->assertSame(
                        $message,
                        $e->getMessage(),
                        "$label - expected exception message"
                    );
                } else {
                    $this->fail(
                        "$label - Unexpected exception ("
                        . get_class($e) .') '. $e->getMessage()
                    );
                }
            }

            if (!$test['error']) {
                $this->assertEquals(
                    $test['expected']->toArray(),
                    $menu->getContainer()->toArray(),
                    "$label - Expected container"
                );
            }
        }
    }

    /**
     * Test behavour of get/setLabel
     */
    public function testGetSetLabel()
    {
        $menu = new P4Cms_Menu;
        $this->assertSame('', $menu->getLabel(), 'Expected label with no id');

        $menu->setId('test');
        $this->assertSame('Test', $menu->getLabel(), 'Expected label with test id');

        $menu->setId('test-menu');
        $this->assertSame('Test Menu', $menu->getLabel(), 'Expected label with test-menu id');

        $menu->setLabel('custom');
        $this->assertSame('custom', $menu->getLabel(), 'Expected label with custom value');
    }

    /**
     * Test behaviour of installDefaultMenus
     */
    public function testInstallDefaultMenus()
    {
        // check that there are no menus initially
        $menus = P4Cms_Menu::fetchAll();
        $this->assertEquals(0, count($menus), 'Expected menu count before install defaults');

        // attempt to install default menus
        P4Cms_Menu::installDefaultMenus();

        // check that no menus exist; we haven't enabled a module that provides menus yet
        $menus = P4Cms_Menu::fetchAll();
        $this->assertEquals(0, count($menus), 'Expected menu count after install defaults with no modules enable');

        // enable a module that provides menus
        $module = P4Cms_Module::fetch('Navigable')->enable();
        $module->load();

        // attempt to install default menus again
        P4Cms_Menu::installDefaultMenus();

        // check that menus now exist
        $menus = P4Cms_Menu::fetchAll();
        $first = $menus->first();
        $middle = $menus->next();
        $last  = $menus->last();
        $this->assertEquals(3, count($menus), 'Expected menu count after install defaults with module enabled');
        $this->assertSame(P4Cms_Menu::DEFAULT_MENU, $first->getId(), 'Expected id for menu #1');
        $pages = $first->getContainer()->getPages();
        $this->assertEquals(3, count($pages), 'Expected page count for menu #1');

        $this->assertSame('manage-toolbar', $middle->getId(), 'Expected id for menu #2');
        $pages = $middle->getContainer()->getPages();
        $this->assertEquals(1, count($pages), 'Expected page count for menu #2');

        $this->assertSame('navigable', $last->getId(), 'Expected id for menu #3');
        $pages = $last->getContainer()->getPages();
        $this->assertEquals(2, count($pages), 'Expected page count for menu #3');

        // enable the default theme
        P4Cms_Theme::addPackagesPath(SITES_PATH . '/all/themes');
        P4Cms_PackageAbstract::setDocumentRoot(SITES_PATH);
        $theme = P4Cms_Theme::fetch('default');
        $theme->load();

        // attempt to install default menus again
        P4Cms_Menu::installDefaultMenus();

        // check that menus still exist
        $menus = P4Cms_Menu::fetchAll();
        $first = $menus->first();
        $middle = $menus->next();
        $last  = $menus->last();
        $this->assertEquals(3, count($menus), 'Expected menu count after 2nd install defaults with module enabled');
        $this->assertSame(P4Cms_Menu::DEFAULT_MENU, $first->getId(), 'Expected id for menu #1');
        $pages = $first->getContainer()->getPages();
        $this->assertEquals(3, count($pages), 'Expected page count for menu #1');

        $this->assertSame('manage-toolbar', $middle->getId(), 'Expected id for menu #2');
        $pages = $middle->getContainer()->getPages();
        $this->assertEquals(1, count($pages), 'Expected page count for menu #2');

        $this->assertSame('navigable', $last->getId(), 'Expected id for menu #3');
        $pages = $last->getContainer()->getPages();
        $this->assertEquals(2, count($pages), 'Expected page count for menu #3');

        // attempt to install default menus yet again, to test behaviour when menus already exist.
        P4Cms_Menu::installDefaultMenus();

        // check that menus still exist
        $menus = P4Cms_Menu::fetchAll();
        $first = $menus->first();
        $middle = $menus->next();
        $last  = $menus->last();
        $this->assertEquals(3, count($menus), 'Expected menu count after 3rd install defaults with module enabled');
        $this->assertSame(P4Cms_Menu::DEFAULT_MENU, $first->getId(), 'Expected id for menu #1');
        $pages = $first->getContainer()->getPages();
        $this->assertEquals(3, count($pages), 'Expected page count for menu #1');

        $this->assertSame('manage-toolbar', $middle->getId(), 'Expected id for menu #2');
        $pages = $middle->getContainer()->getPages();
        $this->assertEquals(1, count($pages), 'Expected page count for menu #2');

        $this->assertSame('navigable', $last->getId(), 'Expected id for menu #3');
        $pages = $last->getContainer()->getPages();
        $this->assertEquals(2, count($pages), 'Expected page count for menu #3');
    }

    /**
     * Test behaviour of installDefaultMenus with a menu argument
     */
    public function testInstallSingleDefaultMenu()
    {
        // check that there are no menus initially
        $menus = P4Cms_Menu::fetchAll();
        $this->assertEquals(0, count($menus), 'Expected menu count before install defaults');

        // enable a module that provides menus
        $module = P4Cms_Module::fetch('Navigable')->enable();
        $module->load();

        // attempt to install a single default menus
        P4Cms_Menu::installDefaultMenus('navigable');

        // check that only one menu exists.
        $menus = P4Cms_Menu::fetchAll();
        $this->assertEquals(1, count($menus));

        // check that it's the one we want.
        $menu = $menus->first();
        $this->assertSame('navigable', $menu->getId());
    }

    /**
     * Test installation of default menus when one package references
     * menus in another - ensure that menu properties are merged.
     */
    public function testInstallSharedDefaults()
    {
        // one module that defines some menu structure
        $a = P4Cms_Module::fetch('Navigable');

        // a second module that references (and tweaks) the
        // menu structure defined by the first module.
        $b = P4Cms_Module::fetch('Independent');

        // install menus from both.
        P4Cms_Menu::installPackageDefaults($a);
        P4Cms_Menu::installPackageDefaults($b);

        $menu      = P4Cms_Menu::fetch(P4Cms_Menu::DEFAULT_MENU);
        $container = $menu->getContainer();

        // ensure 'test' item is present and reflects merged properties.
        //  - check that properties are merged
        //  - check that last value wins
        //  - check that type is re-assessed
        //  - check that sub-pages are correct
        $test = $container->findBy('uuid', P4Cms_Uuid::fromMd5(md5('test')));
        $this->assertTrue($test instanceof Zend_Navigation_Page);
        $this->assertSame($test->get('label'), 'Test');
        $this->assertSame($test->get('class'), 'test-menu');
        $this->assertSame($test->get('uri'),   'http://test-host.com/');
        $this->assertSame($test->get('id'),    'testeroo');
        $this->assertSame('P4Cms_Navigation_Page_Uri', get_class($test));
        $this->assertSame(2, count($test->getPages()));

        // check that sub-pages are merged also
        $apple = $container->findBy('uuid', P4Cms_Uuid::fromMd5(md5('test/apple')));
        $this->assertTrue($apple instanceof Zend_Navigation_Page);
        $this->assertSame('Apple', $apple->get('label'));

        // verify explicit types are preserved.
        $test2 = $container->findBy('uuid', P4Cms_Uuid::fromMd5(md5('test2')));
        $this->assertTrue($test2 instanceof P4Cms_Navigation_Page_Heading);
    }

    /**
     * Test installation of default menus when one package references
     * menus in another - ensure that menu properties are merged.
     */
    public function testRemoveDefaults()
    {
        // one module that defines some menu structure
        $a = P4Cms_Module::fetch('Navigable');

        // a second module that references (and tweaks) the
        // menu structure defined by the first module.
        $b = P4Cms_Module::fetch('Independent');

        // install menus from both.
        P4Cms_Menu::installPackageDefaults($a);
        P4Cms_Menu::installPackageDefaults($b);

        // remove package 'b' menus.
        P4Cms_Menu::removePackageDefaults($b);

        $id        = P4Cms_Uuid::fromMd5(md5('test/home'));
        $menu      = P4Cms_Menu::fetch(P4Cms_Menu::DEFAULT_MENU);
        $container = $menu->getContainer();

        // ensure 'home' link is gone.
        $home = $container->findBy('uuid', $id);
        $this->assertTrue($home == null);

        // re-install 'b' menus.
        P4Cms_Menu::installPackageDefaults($b);

        // modify home link.
        $menu      = P4Cms_Menu::fetch(P4Cms_Menu::DEFAULT_MENU);
        $container = $menu->getContainer();
        $page      = $container->findBy('uuid', $id);
        $page->setLabel('Homer');
        $menu->save();

        // remove package 'b' menus.
        P4Cms_Menu::removePackageDefaults($b);

        $menu      = P4Cms_Menu::fetch(P4Cms_Menu::DEFAULT_MENU);
        $container = $menu->getContainer();

        // ensure 'home' link is still here.
        $home = $container->findBy('uuid', $id);
        $this->assertTrue($home instanceof Zend_Navigation_Page);

        // remove package 'a' menus.
        P4Cms_Menu::removePackageDefaults($a);

        $menus = P4Cms_Menu::fetchAll();
        $this->assertEquals(1, count($menus), 'Expected menu count after remove "a" defaults');
    }

    /**
     * Test behaviour of addPage.
     */
    public function testAddPage()
    {
        $menu = new P4Cms_Menu;
        $pages = $menu->getContainer()->getPages();
        $this->assertEquals(0, count($pages), 'Expect no pages initially');

        // add a page
        $uriPage = new P4Cms_Navigation_Page_Uri(array('id' => 'test', 'label' => 'Test', 'uri' => '/'));
        $menu->addPage($uriPage);

        $pages = $menu->getContainer()->getPages();
        $this->assertEquals(1, count($pages), 'Expect 1 page after add');
        $this->assertEquals($uriPage->toArray(), $pages[0]->toArray(), 'Expected page after add');

        try {
            $menu->addPage('string');
            $this->fail('Expected adding bad page to throw exception.');
        }
        catch(Zend_Navigation_Exception $e) {
            $this->assertEquals(
                'Invalid argument: $page must be an instance of '
                . 'Zend_Navigation_Page or Zend_Config, or an array',
                $e->getMessage(),
                'Expected exception message.'
            );
        }
    }

    /**
     * Test behaviour of getExpandedContainer.
     */
    public function testGetExpandedContainer()
    {
        $menu = new P4Cms_Menu;
        $container = $menu->getExpandedContainer();
        $this->assertEquals(0, count($container->getPages()), 'Expect no pages initially');

        // enable a module that provides menus
        $module = P4Cms_Module::fetch('Navigable')->enable();
        $module->load();

        // prepare lots of pages
        $manyPages = array();
        $idIndex = 0;
        for ($i = 0; $i < 10; $i++) {
            $page = array('label' => "Test$i", 'uri' => "http://test$i/");
            if ($i >= 5) {
                $idIndex++;
                $subPage = array(
                    'label' => "Test$i #$idIndex",
                    'uri'   => "http://subtest$i/",
                );
                for ($j = 1; $j < $idIndex; $j++) {
                    $subPage = array(
                        'label' => "Test$i #$idIndex - $j",
                        'uri'   => "http://nestedtest$j/",
                        'pages' => array($subPage),
                    );
                }
                $page['pages'] = array($subPage);
            }
            $manyPages[] = $page;
        }

        $date = date('Y-M-d');
        $tests = array(
            array(
                'label'     => __LINE__. ': uri',
                'addPage'   => array('label' => 'Test', 'uri' => '/'),
                'options'   => array(),
                'expected'  => "'Test' /\n",
            ),
            array(
                'label'     => __LINE__. ': many pages',
                'addPages'  => $manyPages,
                'options'   => array(),
                'expected'  => "'Test0' http://test0/
'Test1' http://test1/
'Test2' http://test2/
'Test3' http://test3/
'Test4' http://test4/
'Test5' http://test5/
    'Test5 #1' http://subtest5/
'Test6' http://test6/
    'Test6 #2 - 1' http://nestedtest1/
        'Test6 #2' http://subtest6/
'Test7' http://test7/
    'Test7 #3 - 2' http://nestedtest2/
        'Test7 #3 - 1' http://nestedtest1/
            'Test7 #3' http://subtest7/
'Test8' http://test8/
    'Test8 #4 - 3' http://nestedtest3/
        'Test8 #4 - 2' http://nestedtest2/
            'Test8 #4 - 1' http://nestedtest1/
                'Test8 #4' http://subtest8/
'Test9' http://test9/
    'Test9 #5 - 4' http://nestedtest4/
        'Test9 #5 - 3' http://nestedtest3/
            'Test9 #5 - 2' http://nestedtest2/
                'Test9 #5 - 1' http://nestedtest1/
                    'Test9 #5' http://subtest9/
",
            ),
            array(
                'label'     => __LINE__. ': many pages, maxItems=9',
                'addPages'  => $manyPages,
                'options'   => array(P4Cms_Menu::MENU_MAX_ITEMS => 9),
                'expected'  => "'Test0' http://test0/
'Test1' http://test1/
'Test2' http://test2/
'Test3' http://test3/
'Test4' http://test4/
'Test5' http://test5/
    'Test5 #1' http://subtest5/
'Test6' http://test6/
    'Test6 #2 - 1' http://nestedtest1/
",
            ),
            array(
                'label'     => __LINE__. ': many pages, maxDepth=0',
                'addPages'  => $manyPages,
                'options'   => array(P4Cms_Menu::MENU_MAX_DEPTH => 0),
                'expected'  => "'Test0' http://test0/
'Test1' http://test1/
'Test2' http://test2/
'Test3' http://test3/
'Test4' http://test4/
'Test5' http://test5/
'Test6' http://test6/
'Test7' http://test7/
'Test8' http://test8/
'Test9' http://test9/
",
            ),
            array(
                'label'     => __LINE__. ': many pages, maxDepth=1',
                'addPages'  => $manyPages,
                'options'   => array(P4Cms_Menu::MENU_MAX_DEPTH => 1),
                'expected'  => "'Test0' http://test0/
'Test1' http://test1/
'Test2' http://test2/
'Test3' http://test3/
'Test4' http://test4/
'Test5' http://test5/
    'Test5 #1' http://subtest5/
'Test6' http://test6/
    'Test6 #2 - 1' http://nestedtest1/
'Test7' http://test7/
    'Test7 #3 - 2' http://nestedtest2/
'Test8' http://test8/
    'Test8 #4 - 3' http://nestedtest3/
'Test9' http://test9/
    'Test9 #5 - 4' http://nestedtest4/
",
            ),
            array(
                'label'     => __LINE__. ': many pages, maxDepth=3',
                'addPages'  => $manyPages,
                'options'   => array(P4Cms_Menu::MENU_MAX_DEPTH => 3),
                'expected'  => "'Test0' http://test0/
'Test1' http://test1/
'Test2' http://test2/
'Test3' http://test3/
'Test4' http://test4/
'Test5' http://test5/
    'Test5 #1' http://subtest5/
'Test6' http://test6/
    'Test6 #2 - 1' http://nestedtest1/
        'Test6 #2' http://subtest6/
'Test7' http://test7/
    'Test7 #3 - 2' http://nestedtest2/
        'Test7 #3 - 1' http://nestedtest1/
            'Test7 #3' http://subtest7/
'Test8' http://test8/
    'Test8 #4 - 3' http://nestedtest3/
        'Test8 #4 - 2' http://nestedtest2/
            'Test8 #4 - 1' http://nestedtest1/
'Test9' http://test9/
    'Test9 #5 - 4' http://nestedtest4/
        'Test9 #5 - 3' http://nestedtest3/
            'Test9 #5 - 2' http://nestedtest2/
",
            ),
            array(
                'label'     => __LINE__. ': many pages, maxDepth=10',
                'addPages'  => $manyPages,
                'options'   => array(P4Cms_Menu::MENU_MAX_DEPTH => 10),
                'expected'  => "'Test0' http://test0/
'Test1' http://test1/
'Test2' http://test2/
'Test3' http://test3/
'Test4' http://test4/
'Test5' http://test5/
    'Test5 #1' http://subtest5/
'Test6' http://test6/
    'Test6 #2 - 1' http://nestedtest1/
        'Test6 #2' http://subtest6/
'Test7' http://test7/
    'Test7 #3 - 2' http://nestedtest2/
        'Test7 #3 - 1' http://nestedtest1/
            'Test7 #3' http://subtest7/
'Test8' http://test8/
    'Test8 #4 - 3' http://nestedtest3/
        'Test8 #4 - 2' http://nestedtest2/
            'Test8 #4 - 1' http://nestedtest1/
                'Test8 #4' http://subtest8/
'Test9' http://test9/
    'Test9 #5 - 4' http://nestedtest4/
        'Test9 #5 - 3' http://nestedtest3/
            'Test9 #5 - 2' http://nestedtest2/
                'Test9 #5 - 1' http://nestedtest1/
                    'Test9 #5' http://subtest9/
",
            ),
            array(
                'label'     => __LINE__. ': many pages, maxDepth=2, maxItems=15',
                'addPages'  => $manyPages,
                'options'   => array(
                    P4Cms_Menu::MENU_MAX_DEPTH => 2,
                    P4Cms_Menu::MENU_MAX_ITEMS => 15,
                ),
                'expected'  => "'Test0' http://test0/
'Test1' http://test1/
'Test2' http://test2/
'Test3' http://test3/
'Test4' http://test4/
'Test5' http://test5/
    'Test5 #1' http://subtest5/
'Test6' http://test6/
    'Test6 #2 - 1' http://nestedtest1/
        'Test6 #2' http://subtest6/
'Test7' http://test7/
    'Test7 #3 - 2' http://nestedtest2/
        'Test7 #3 - 1' http://nestedtest1/
'Test8' http://test8/
    'Test8 #4 - 3' http://nestedtest3/
",
            ),
            array(
                'label'     => __LINE__. ': many pages, maxDepth=3, maxItems=15',
                'addPages'  => $manyPages,
                'options'   => array(
                    P4Cms_Menu::MENU_MAX_DEPTH => 3,
                    P4Cms_Menu::MENU_MAX_ITEMS => 15,
                ),
                'expected'  => "'Test0' http://test0/
'Test1' http://test1/
'Test2' http://test2/
'Test3' http://test3/
'Test4' http://test4/
'Test5' http://test5/
    'Test5 #1' http://subtest5/
'Test6' http://test6/
    'Test6 #2 - 1' http://nestedtest1/
        'Test6 #2' http://subtest6/
'Test7' http://test7/
    'Test7 #3 - 2' http://nestedtest2/
        'Test7 #3 - 1' http://nestedtest1/
            'Test7 #3' http://subtest7/
'Test8' http://test8/
",
            ),

            array(
                'label'     => __LINE__. ': many pages, root=-1',
                'addPages'  => $manyPages,
                'options'   => array(),
                'root'      => -1,
                'expected'  => "",
            ),
            array(
                'label'     => __LINE__. ': many pages, root=9',
                'addPages'  => $manyPages,
                'options'   => array(),
                'root'      => 9,
                'expected'  => "'Test9 #5 - 4' http://nestedtest4/
    'Test9 #5 - 3' http://nestedtest3/
        'Test9 #5 - 2' http://nestedtest2/
            'Test9 #5 - 1' http://nestedtest1/
                'Test9 #5' http://subtest9/
",
            ),
            array(
                'label'     => __LINE__. ': many pages, root=9, maxItems=3',
                'addPages'  => $manyPages,
                'options'   => array(
                    P4Cms_Menu::MENU_MAX_ITEMS => 3,
                ),
                'root'      => 9,
                'expected'  => "'Test9 #5 - 4' http://nestedtest4/
    'Test9 #5 - 3' http://nestedtest3/
        'Test9 #5 - 2' http://nestedtest2/
",
            ),

            array(
                'label'     => __LINE__. ': dynamic',
                'addPage'   => new P4Cms_Navigation_Page_Dynamic(
                    array(
                        'handler'   => 'test/test',
                    )
                ),
                'options'   => array(),
                'expected'  => "'Test #0 - $date' http://test0.$date.test/
    'Test #0.1 - $date' http://test0.1.$date.test/
        'Test #0.1.1 - $date' http://test0.1.1.$date.test/
            'Test #0.1.1.last - $date' http://test0.1.1.last.$date.test/
'Test #1 - $date' http://test1.$date.test/
    'Test #1.1 - $date' http://test1.1.$date.test/
        'Test #1.1.1 - $date' http://test1.1.1.$date.test/
            'Test #1.1.1.last - $date' http://test1.1.1.last.$date.test/
'Test #2 - $date' http://test2.$date.test/
    'Test #2.1 - $date' http://test2.1.$date.test/
        'Test #2.1.1 - $date' http://test2.1.1.$date.test/
            'Test #2.1.1.last - $date' http://test2.1.1.last.$date.test/
'Test #3 - $date' http://test3.$date.test/
    'Test #3.1 - $date' http://test3.1.$date.test/
        'Test #3.1.1 - $date' http://test3.1.1.$date.test/
            'Test #3.1.1.last - $date' http://test3.1.1.last.$date.test/
'Test #4 - $date' http://test4.$date.test/
    'Test #4.1 - $date' http://test4.1.$date.test/
        'Test #4.1.1 - $date' http://test4.1.1.$date.test/
            'Test #4.1.1.last - $date' http://test4.1.1.last.$date.test/
",
            ),
            array(
                'label'     => __LINE__. ': dynamic, maxItems=6',
                'addPage'   => new P4Cms_Navigation_Page_Dynamic(
                    array(
                        'handler'   => 'test/test',
                    )
                ),
                'options'   => array(
                    P4Cms_Menu::MENU_MAX_ITEMS => 6,
                ),
                'expected'  => "'Test #0 - $date' http://test0.$date.test/
    'Test #0.1 - $date' http://test0.1.$date.test/
        'Test #0.1.1 - $date' http://test0.1.1.$date.test/
            'Test #0.1.1.last - $date' http://test0.1.1.last.$date.test/
'Test #1 - $date' http://test1.$date.test/
    'Test #1.1 - $date' http://test1.1.$date.test/
",
            ),
            array(
                'label'     => __LINE__. ': dynamic, maxDepth=0',
                'addPage'   => new P4Cms_Navigation_Page_Dynamic(
                    array(
                        'handler'   => 'test/test',
                    )
                ),
                'options'   => array(
                    P4Cms_Menu::MENU_MAX_DEPTH => 0,
                ),
                'expected'  => "'Test #0 - $date' http://test0.$date.test/
'Test #1 - $date' http://test1.$date.test/
'Test #2 - $date' http://test2.$date.test/
'Test #3 - $date' http://test3.$date.test/
'Test #4 - $date' http://test4.$date.test/
",
            ),
            array(
                'label'     => __LINE__. ': dynamic, maxDepth=0, maxItems=3',
                'addPage'   => new P4Cms_Navigation_Page_Dynamic(
                    array(
                        'handler'   => 'test/test',
                    )
                ),
                'options'   => array(
                    P4Cms_Menu::MENU_MAX_DEPTH => 0,
                    P4Cms_Menu::MENU_MAX_ITEMS => 3,
                ),
                'expected'  => "'Test #0 - $date' http://test0.$date.test/
'Test #1 - $date' http://test1.$date.test/
'Test #2 - $date' http://test2.$date.test/
",
            ),
            array(
                'label'     => __LINE__. ': dynamic, root=-1',
                'addPage'   => new P4Cms_Navigation_Page_Dynamic(
                    array(
                        'handler'   => 'test/test',
                    )
                ),
                'options'   => array(),
                'root'      => -1,
                'expected'  => "",
            ),
            array(
                'label'     => __LINE__. ': dynamic, root=2',
                'addPage'   => new P4Cms_Navigation_Page_Dynamic(
                    array(
                        'handler'   => 'test/test',
                    )
                ),
                'options'   => array(),
                'root'      => 2,
                'expected'  => "'Test #2.1 - $date' http://test2.1.$date.test/
    'Test #2.1.1 - $date' http://test2.1.1.$date.test/
        'Test #2.1.1.last - $date' http://test2.1.1.last.$date.test/
",
            ),
            array(
                'label'     => __LINE__. ': dynamic, root=2, maxDepth=1',
                'addPage'   => new P4Cms_Navigation_Page_Dynamic(
                    array(
                        'handler'   => 'test/test',
                    )
                ),
                'options'   => array(
                    P4Cms_Menu::MENU_MAX_DEPTH => 1,
                ),
                'root'      => 2,
                'expected'  => "'Test #2.1 - $date' http://test2.1.$date.test/
    'Test #2.1.1 - $date' http://test2.1.1.$date.test/
",
            ),
            array(
                'label'     => __LINE__. ': dynamic, root=2, maxItems=2',
                'addPage'   => new P4Cms_Navigation_Page_Dynamic(
                    array(
                        'handler'   => 'test/test',
                    )
                ),
                'options'   => array(
                    P4Cms_Menu::MENU_MAX_ITEMS => 2,
                ),
                'root'      => 2,
                'expected'  => "'Test #2.1 - $date' http://test2.1.$date.test/
    'Test #2.1.1 - $date' http://test2.1.1.$date.test/
",
            ),
            array(
                'label'     => __LINE__. ': dynamic, root=2, maxDepth=1, maxItems=1',
                'addPage'   => new P4Cms_Navigation_Page_Dynamic(
                    array(
                        'handler'   => 'test/test',
                    )
                ),
                'options'   => array(
                    P4Cms_Menu::MENU_MAX_DEPTH => 1,
                    P4Cms_Menu::MENU_MAX_ITEMS => 1,
                ),
                'root'      => 2,
                'expected'  => "'Test #2.1 - $date' http://test2.1.$date.test/
",
            ),

            array(
                'label'     => __LINE__. ': dynamic without handler',
                'addPage'   => new P4Cms_Navigation_Page_Dynamic(
                    array(
                        'id'        => 'dynamic',
                    )
                ),
                'options'   => array(),
                'expected'  => '',
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $menu = new P4Cms_Menu;
            $menu->setId('testable')->setContainer(null);
            if (array_key_exists('addPage', $test)) {
                $menu->addPage($test['addPage']);
            }
            if (array_key_exists('addPages', $test)) {
                foreach ($test['addPages'] as $page) {
                    $menu->addPage($page);
                }
            }
            $menu->save();
            $showUuid = array_key_exists('showUuid', $test) ? $test['showUuid'] : false;
            if (array_key_exists('root', $test)) {
                if ($test['root'] < 0) {
                    $id = 'does-not-exist';
                } else {
                    $container = $menu->getExpandedContainer();
                    $page = array_shift(array_slice($container->getPages(), $test['root'], 1));
                    $id = $menu->getItemId($page);
                }
                $test['options'][P4Cms_Menu::MENU_ROOT] = $id;
            }

            $container = $menu->getExpandedContainer($test['options']);
            $actual = $this->_formatContainer($container, $showUuid);
            $this->assertEquals($test['expected'], $actual, "$label - expected container layout");
        }

        // ensure exception handling is tested
        $menu = new P4Cms_Menu;

        try {
            $menu->getExpandedContainer('string');
        }
        catch(Zend_Navigation_Exception $e) {
            $this->assertEquals(
                'Invalid argument: $page must be an instance of '
                . 'Zend_Navigation_Page or Zend_Config, or an array',
                $e->getMessage(),
                'Expected exception message.'
            );
        }
    }

    /**
     * A test helper function that produces a summary display of a navigation container,
     * with suitable indenting.
     *
     * @param   Zend_Navigation_Container  $container  The container to process.
     * @param   boolean  $showUuid  when true, includes a page's Uuid in the output.
     * @param   string   $indent  The text to use for indenting nested containers.
     * @return  string   The summary display.
     */
    protected function _formatContainer($container, $showUuid = false, $indent = '')
    {
        $output = '';
        foreach ($container as $page) {
            $format = "%s'%s' %s\n";
            $options = array($indent, $page->getLabel(), $page->getUri());
            if ($showUuid) {
                $format = "%s'%s' %s (%s)\n";
                $options[] = $page->uuid;
            }
            array_unshift($options, $format);
            $output .= call_user_func_array('sprintf', $options);
            if (count($page->getPages())) {
                $output .= $this->_formatContainer($page->getPages(), $showUuid, "$indent    ");
            }
        }
        return $output;
    }
}
