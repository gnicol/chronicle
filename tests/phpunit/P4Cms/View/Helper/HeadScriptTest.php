<?php
/**
 * Test methods for the aggregation capabilities of head script helper.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_HeadScriptTest extends TestCase
{
    /**
     * Test get/set document root.
     */
    public function testGetSetDocumentRoot()
    {
        $helper = new P4Cms_View_Helper_HeadScript;
        
        $documentRoot = $helper->getDocumentRoot();
        $this->assertEquals(null, $documentRoot, 'Expected initial document root');

        // set a unix-like path
        $helper->setDocumentRoot('/a/b/c/');
        $documentRoot = $helper->getDocumentRoot();
        $this->assertEquals('/a/b/c', $documentRoot, 'Expected unix-like document root');

        // set a windows-like path
        $helper->setDocumentRoot('\\a\\b\\c\\');
        $documentRoot = $helper->getDocumentRoot();
        $this->assertEquals('\\a\\b\\c', $documentRoot, 'Expected windows-like document root');
    }

    /**
     * Test get/set output path.
     */
    public function testGetSetAssetHandler()
    {
        $helper = new P4Cms_View_Helper_HeadScript;
        
        $assetHandler = $helper->getAssetHandler();
        $this->assertEquals(null, $assetHandler, 'Expected initial asset handler');

        $helper->setAssetHandler(new P4Cms_AssetHandler_File);
        $assetHandler = $helper->getAssetHandler();
        $this->assertTrue($assetHandler instanceof P4Cms_AssetHandler_File, 'expected matching handler');
    }

    /**
     * Test toString without any scripts.
     */
    public function testToStringNoScripts()
    {
        $helper = new P4Cms_View_Helper_HeadScript;
        $output = $helper->toString();
        $this->assertSame('', $output);
    }

    /**
     * Test toString with scripts.
     */
    public function testToStringWithScripts()
    {
        $helper  = new P4Cms_View_Helper_HeadScript;
        $scripts = array_keys($this->_getTestScripts());
        foreach ($scripts as $script) {
            $helper->appendFile($script);
        }
        
        $output = $helper->toString();

        // ensure scripts output as normal.
        foreach ($scripts as $script) {
            $this->assertTrue(
                strpos($output, $script) !== false,
                'Expected script "'. $script .'" in: '. $output
            );
        }
    }

    /**
     * Test toString with aggregation enabled.
     */
    public function testToStringWithAggregation()
    {
        $helper = new P4Cms_View_Helper_HeadScript;
        
        $handlerOptions = array(
            'outputPath'   => TEST_DATA_PATH .'/resources',
            'documentRoot' => TEST_ASSETS_PATH
        );
        $helper->setAggregateJs(true)
               ->setAssetHandler(new P4Cms_AssetHandler_File($handlerOptions))
               ->setDocumentRoot(TEST_ASSETS_PATH);
        
        $scripts = $this->_getTestScripts();
        foreach ($scripts as $script => $aggregate) {
            $helper->appendFile($script);
        }

        $output = $helper->toString();
        
        foreach ($this->_getTestScripts() as $script => $aggregate) {
            $this->assertTrue(
                preg_match("@$script@", $output) !== $aggregate,
                "Script '". $script ."' should ". (!$aggregate ? 'NOT' : '')
                . " have been aggregated."
            );
        }
        
        // ensure we have an aggregate file.
        $this->assertTrue(preg_match('@/resources/[a-z0-9]{32}\.js@', $output, $matches) === 1);
        
        // ensure file contains expected data.
        $contents = file_get_contents(TEST_DATA_PATH . $matches[0]);
        $this->assertSame(
            "console.log('foo');\nconsole.log('bar');\n",
            $contents
        );
    }

    /**
     * Test toString with aggregation enabled, but no asset handler configuration.
     */
    public function testToStringWithAggregationAndNoAssetHandler()
    {
        $helper = new P4Cms_View_Helper_HeadScript;
        $helper->setAggregateJs(true)
               ->setDocumentRoot(TEST_ASSETS_PATH);
        
        $scripts = array_keys($this->_getTestScripts());
        foreach ($scripts as $script) {
            $helper->appendFile($script);
        }
        
        $output = $helper->toString();
        foreach ($scripts as $script) {
            $this->assertTrue(
                strpos($output, $script) !== false,
                "Script '". $script ."' should NOT have been aggregated,
                but does not appear in:\n$output"
            );
        }
    }
    /**
     * Test toString with aggregation enabled, but no document root configuration.
     */
    public function testToStringWithAggregationAndNoDocRoot()
    {
        $helper = new P4Cms_View_Helper_HeadScript;
        
        $handlerOptions = array(
            'outputPath'   => TEST_DATA_PATH .'/resources',
            'documentRoot' => TEST_ASSETS_PATH
        );
        $helper->setAggregateJs(true)
               ->setAssetHandler(new P4Cms_AssetHandler_File($handlerOptions));

        $scripts = array_keys($this->_getTestScripts());
        foreach ($scripts as $script) {
            $helper->appendFile($script);
        }
        
        $output = $helper->toString();
        foreach ($scripts as $script) {
            $this->assertTrue(
                strpos($output, $script) !== false,
                "Script '". $script ."' should NOT have been aggregated,
                but does not appear in:\n$output"
            );
        }
    }

    /**
     * Scripts to test aggregation with.
     * 
     * @return  array   scripts with files as keys and if they should be aggregated as values.
     */
    protected function _getTestScripts()
    {
        return array(
            '/files/foo.js'               => true,
            '/files/bar.js'               => true,
            'http://google.com/script.js' => false
        );
    }
}
