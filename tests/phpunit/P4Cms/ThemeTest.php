<?php
/**
 * Test the site model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_ThemeTest extends TestCase
{
    /**
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();
        P4Cms_Theme::addPackagesPath(SITES_PATH . '/all/themes');
        P4Cms_PackageAbstract::setDocumentRoot(SITES_PATH);
    }

    /**
     * Test teardown.
     */
    public function tearDown()
    {
        P4Cms_PackageAbstract::setDocumentRoot(null);
        parent::tearDown();
    }

    /**
     * Ensure changes to path influences the baseUrl.
     */
    public function testPaths()
    {
        $theme = new P4Cms_Theme;
        $theme->setPath(SITES_PATH .'/all/themes/default');
        $this->assertSame('/all/themes/default', $theme->getBaseUrl(), 'Expected base URL.');
        $this->assertSame(
            TEST_SITES_PATH .'/all/themes/default/views',
            $theme->getViewsPath(),
            'Expected views path.'
        );

        // ensure that invalid path throws.
        try {
            $theme->setPath(SITES_PATH .'/non-existant-site/themes/default');
            $this->fail("Expected exception setting non-existant theme path.");
        } catch (P4Cms_Package_Exception $e) {
            $this->assertTrue(true);
        }

        // ensure path must be under public path.
        try {
            P4Cms_PackageAbstract::setDocumentRoot(SITES_PATH . '/invalid');
            $theme->setPath(SITES_PATH .'/all/themes/default');
            $theme->getBaseUrl();
            $this->fail("Expected exception getting base url of theme outside of public path.");
        } catch (P4Cms_Package_Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Ensure we can fetch themes.
     */
    public function testFetch()
    {
        // valid key.
        try {
            $theme = P4Cms_Theme::fetch('default');
            $this->assertTrue(true);
        } catch (P4Cms_Model_NotFoundException $e) {
            $this->fail();
        }

        // ensure it's populated.
        $this->assertTrue((bool)$theme->getName());
        $this->assertTrue((bool)$theme->getPath());
        $this->assertTrue((bool)count($theme->getMaintainerInfo()));
        $this->assertTrue((bool)$theme->getDescription());

        // invalid key.
        try {
            $theme = P4Cms_Theme::fetch('alskdjfaskldfjsdlk');
            $this->fail();
        } catch (P4Cms_Model_NotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Ensure we can fetch a list of themes.
     */
    public function testFetchAll()
    {
        try {
            $themes = P4Cms_Theme::fetchAll();
            $this->assertTrue(true);
            $this->assertTrue($themes instanceof P4Cms_Model_Iterator);
        } catch (P4Cms_Model_NotFoundException $e) {
            $this->fail();
        }
    }

    /**
     * Ensure themes load properly.
     */
    public function testLoad()
    {
        // load theme.
        $theme = P4Cms_Theme::fetch('alternative');
        $theme->load();

        $view = P4Cms_Theme::getView();

        // ensure stylesheets added.
        foreach ($theme->getStylesheets() as $stylesheet) {
            $this->assertTrue(strpos($view->headLink()->toString(), $stylesheet['href']) !== false);
        }

        // ensure scripts added.
        foreach ($theme->getScripts() as $script) {
            $this->assertTrue(strpos($view->headScript()->toString(), $script['src']) !== false);
        }
    }

    /**
     * Test that stylesheets can be retrieved correctly.
     */
    public function testStylesheets()
    {
        $theme = new P4Cms_Theme;
        $theme->setPath(SITES_PATH . '/all/themes/default');
        $stylesheets = $theme->getStylesheets();
        $this->assertTrue(count($stylesheets) == 4);
        $this->assertTrue($stylesheets[0]['href']           == $theme->getBaseUrl() . '/test1.css');
        $this->assertTrue($stylesheets[0]['media']          == 'all');
        $this->assertTrue($stylesheets[0]['conditional']    == false);
        $this->assertTrue($stylesheets[1]['href']           == $theme->getBaseUrl() . '/test2.css');
        $this->assertTrue($stylesheets[1]['media']          == 'all');
        $this->assertTrue($stylesheets[1]['conditional']    == false);
        $this->assertTrue($stylesheets[2]['href']           == 'http://foo.com/bar.css');
        $this->assertTrue($stylesheets[2]['media']          == 'screen');
        $this->assertTrue($stylesheets[2]['conditional']    == false);
        $this->assertTrue($stylesheets[3]['href']           == $theme->getBaseUrl() . '/print.css');
        $this->assertTrue($stylesheets[3]['media']          == 'print');
        $this->assertTrue($stylesheets[3]['conditional']    == false);
    }

    /**
     * Test that scripts can be retrieved correctly.
     */
    public function testScripts()
    {
        $theme = new P4Cms_Theme;
        $theme->setPath(SITES_PATH . '/all/themes/default');
        $scripts = $theme->getScripts();
        $this->assertTrue(count($scripts) == 3);
        $this->assertTrue($scripts[0]['src']    == $theme->getBaseUrl() . '/test1.js');
        $this->assertTrue($scripts[0]['type']   == 'text/javascript');
        $this->assertTrue($scripts[0]['attrs']  == array());
        $this->assertTrue($scripts[1]['src']    == $theme->getBaseUrl() . '/test2.js');
        $this->assertTrue($scripts[1]['type']   == 'text/javascript');
        $this->assertTrue($scripts[1]['attrs']  == array());
        $this->assertTrue($scripts[2]['src']    == $theme->getBaseUrl() . '/test.vbs');
        $this->assertTrue($scripts[2]['type']   == 'text/vbscript');
        $this->assertTrue($scripts[2]['attrs']  == array());
    }

    /**
     * Test the behaviour of getWidgetConfig.
     */
    public function testGetWidgetConfig()
    {
        $theme = P4Cms_Theme::fetch('default');
        $theme->load();

        $widgetConfig = $theme->getWidgetConfig();
        $this->assertSame(array('test'), array_keys($widgetConfig), 'Expected region names in widget config.');
        $this->assertSame(
            array(
                'title' => 'Test',
                'type'  => 'widget/text',
            ),
            current($widgetConfig['test']),
            'Expected widget config for test region.'
        );
    }

    /**
     * Test behaviour of getLabel.
     */
    public function testGetLabel()
    {
        $theme = P4Cms_Theme::fetch('default');
        $theme->load();

        $this->assertSame('Default Theme', $theme->getLabel(), 'Expected title.');
    }

    /**
     * Test behaviour of hasActive.
     */
    public function testHasActive()
    {
        $this->assertFalse(P4Cms_Theme::hasActive(), 'Expect false without theme load.');

        $theme = P4Cms_Theme::fetch('default');
        $theme->load();
        $this->assertTrue(P4Cms_Theme::hasActive(), 'Expect true with theme load.');
    }
}
