<?php
/**
 * Test methods for the P4Cms HeadLink View Helper.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_HeadLinkTest extends TestCase
{
    protected $_helper;

    /**
     * Setup the helper and view for each test.
     */
    public function setUp()
    {
        parent::setUp();
        P4Cms_Theme::addPackagesPath(SITES_PATH . '/all/themes');
        P4Cms_PackageAbstract::setDocumentRoot(SITES_PATH);

        $this->_helper = new P4Cms_View_Helper_HeadLink;
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
     * Test get/set document root.
     */
    public function testGetSetDocumentRoot()
    {
        $documentRoot = $this->_helper->getDocumentRoot();
        $this->assertEquals(null, $documentRoot, 'Expected initial document root');

        // set a unix-like path
        $this->_helper->setDocumentRoot('/a/b/c/');
        $documentRoot = $this->_helper->getDocumentRoot();
        $this->assertEquals('/a/b/c', $documentRoot, 'Expected unix-like document root');

        // set a windows-like path
        $this->_helper->setDocumentRoot('\\a\\b\\c\\');
        $documentRoot = $this->_helper->getDocumentRoot();
        $this->assertEquals('\\a\\b\\c', $documentRoot, 'Expected windows-like document root');
    }

    /**
     * Test get/set output path.
     */
    public function testGetSetAssetHandler()
    {
        $assetHandler = $this->_helper->getAssetHandler();
        $this->assertEquals(null, $assetHandler, 'Expected initial asset handler');

        $this->_helper->setAssetHandler(new P4Cms_AssetHandler_File);
        $assetHandler = $this->_helper->getAssetHandler();
        $this->assertTrue($assetHandler instanceof P4Cms_AssetHandler_File, 'expected matching handler');
    }

    /**
     * Test toString without a loaded theme.
     */
    public function testToStringNoTheme()
    {
        // should expect failure without a loaded theme
        try {
            $output = $this->_helper->toString();
            $this->fail('Unexpected success without a loaded theme');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4Cms_Theme_Exception $e) {
            $this->assertEquals(
                'There is no active (currently loaded) theme.',
                $e->getMessage(),
                'Expected exception without a loaded theme.'
            );
        } catch (Exception $e) {
            $this->fail(
                "Unexpected Exception without a loaded theme (" . get_class($e) . '): ' . $e->getMessage()
            );
        }
    }

    /**
     * Test toString with a loaded theme.
     */
    public function testToStringWithTheme()
    {
        // load theme and try again
        $theme = P4Cms_Theme::fetch('alternative');
        $theme->load();
        $view = $theme->getView();
        $this->_helper->setView($view);
        $output = $this->_helper->toString();

        // ensure stylesheets added.
        foreach ($theme->getStylesheets() as $stylesheet) {
            $this->assertTrue(
                strpos($output, $stylesheet['href']) !== false,
                'Expected stylesheet href "'. $stylesheet['href'] .'" to exist in: '. $output
            );
        }
    }

    /**
     * Test toString with MSIE
     */
    public function testToStringWithInternetExplorer()
    {
        $theme = P4Cms_Theme::fetch('alternative');
        $theme->load();
        $view = $theme->getView();
        $this->_helper->setView($view)
                      ->setDocumentRoot(SITES_PATH);

        // capture output for MSIE
        $_SERVER['HTTP_USER_AGENT'] = "Mozilla/4.0 (compatible;MSIE 7.0;Windows NT 6.0)";
        $output = $this->_helper->toString();
        unset($_SERVER['HTTP_USER_AGENT']);

        // verify headLink output
        foreach ($theme->getStylesheets() as $stylesheet) {
            if ($stylesheet['conditional']) {
                $this->assertTrue(
                    strpos($output, '<!--[if gte IE 6]>') !== false,
                    "Expected CSS condition start comment to exist in:\n$output"
                );
                $this->assertTrue(
                    strpos($output, '<![endif]-->') !== false,
                    "Expected CSS condition start comment to exist in:\n$output"
                );
            }
            $import = "@import url('". $stylesheet['href'] ."');";
            $this->assertTrue(
                strpos($output, $import) !== false,
                "Expected MSIE import '". $stylesheet['href'] ."' to exist in:\n$output"
            );
        }
    }

    /**
     * Test toString with aggregation enabled.
     */
    public function testToStringWithAggregation()
    {
        $theme = P4Cms_Theme::fetch('alternative');
        $theme->load();
        $view = $theme->getView();
        $handlerOptions = array(
            'outputPath'   => TEST_DATA_PATH .'/resources',
            'documentRoot' => SITES_PATH
        );
        $this->_helper->setView($view)
                      ->setAggregateCss(true)
                      ->setAssetHandler(new P4Cms_AssetHandler_File($handlerOptions))
                      ->setDocumentRoot(SITES_PATH);
        $output = $this->_helper->toString();

        $expected = array(
            '/all/themes/alternative/test1.css' => false,
            '/all/themes/alternative/test2.css' => true,
            '/all/themes/alternative/test3.css' => true,
            '/all/themes/alternative/test4.css' => false,
            'http://css.css/css.css'            => true,
        );

        foreach ($theme->getStylesheets() as $stylesheet) {
            $match = "@". $stylesheet['href'] ."@";
            $compare = $expected[$stylesheet['href']] ? 1 : 0;
            $this->assertTrue(
                preg_match($match, $output) === $compare,
                "Stylesheet '". $stylesheet['href'] ."' should ". ($compare ? 'NOT' : '')
                . " have been aggregated"
            );

            if ($stylesheet['conditional']) {
                $match  = '@<!--\\[if gte IE 6\\]>[^>]+';
                $match .= $compare
                    ? $stylesheet['href']
                    : 'all-packages-gte-IE-6';
                $match .= "[^<]+<!\\[endif\\]-->@";
                $this->assertTrue(
                    preg_match($match, $output) > 0,
                    "Expected CSS condition comment surrounding '". $stylesheet['href'] ."' in:\n$output"
                );
            }
        }
    }

    /**
     * Test toString with aggregation enabled, but no asset handler configuration.
     */
    public function testToStringWithAggregationAndNoAssetHandler()
    {
        $theme = P4Cms_Theme::fetch('alternative');
        $theme->load();
        $view = $theme->getView();
        $this->_helper->setView($view)
                      ->setAggregateCss(true)
                      ->setDocumentRoot(SITES_PATH);
        $output = $this->_helper->toString();

        foreach ($theme->getStylesheets() as $stylesheet) {
            $this->assertTrue(
                strpos($output, $stylesheet['href']) !== false,
                "Stylesheet '". $stylesheet['href'] ."' should NOT have been aggregated,
                but appears in:\n$output"
            );
        }
    }
    /**
     * Test toString with aggregation enabled, but no document root configuration.
     */
    public function testToStringWithAggregationAndNoDocRoot()
    {
        $theme = P4Cms_Theme::fetch('alternative');
        $theme->load();
        $view = $theme->getView();
        $handlerOptions = array(
            'outputPath'   => TEST_DATA_PATH .'/resources',
            'documentRoot' => SITES_PATH
        );
        $this->_helper->setView($view)
                      ->setAggregateCss(true)
                      ->setAssetHandler(new P4Cms_AssetHandler_File($handlerOptions));
        $output = $this->_helper->toString();

        foreach ($theme->getStylesheets() as $stylesheet) {
            $this->assertTrue(
                strpos($output, $stylesheet['href']) !== false,
                "Stylesheet '". $stylesheet['href'] ."' should NOT have been aggregated,
                but appears in:\n$output"
            );
        }
    }
}
