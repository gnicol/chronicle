<?php
/**
 * Test File based Asset Handler.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_AssetHandler_FileTest extends TestCase
{
    /**
     * test constructor with and without options
     */
    public function testConstructor()
    {
        $handler = new P4Cms_AssetHandler_File;
        
        $this->assertSame(
            null,
            $handler->getOutputPath(),
            'output path starting value'
        );
        $this->assertSame(
            BASE_PATH,
            $handler->getBasePath(),
            'base path starting value'
        );
        
        $handler = new P4Cms_AssetHandler_File(
            array(
                'outputPath' => '/output/path',
                'basePath'   => '/base/path'
            )
        );
        
        $this->assertSame(
            '/output/path',
            $handler->getOutputPath(),
            'output path custom value'
        );
        $this->assertSame(
            '/base/path',
            $handler->getBasePath(),
            'base path custom value'
        );
    }
    
    /**
     * test get/set base path
     */
    public function testGetSetBasePath()
    {
        $handler = new P4Cms_AssetHandler_File;
        
        $this->assertSame(
            BASE_PATH,
            $handler->getBasePath(),
            'base path starting value'
        );
        
        $handler->setBasePath('/test/path');
        $this->assertSame(
            '/test/path',
            $handler->getBasePath(),
            'base path second value'
        );
        
        $handler->setBasePath(null);
        $this->assertSame(
            BASE_PATH,
            $handler->getBasePath(),
            'base path third value'
        );
    }
    
    /**
     * test invalid base path
     * 
     * @expectedException InvalidArgumentException
     */
    public function testInvalidBasePath()
    {
        $handler = new P4Cms_AssetHandler_File(array('basePath' => 12));
    }
    
    /**
     * test get/set output path
     */
    public function testGetSetOutputPath()
    {
        $handler = new P4Cms_AssetHandler_File;
        
        $this->assertSame(
            null,
            $handler->getOutputPath(),
            'output path starting value'
        );
        
        $handler->setOutputPath('/test/path');
        $this->assertSame(
            '/test/path',
            $handler->getOutputPath(),
            'output path second value'
        );
        
        $handler->setOutputPath(null);
        $this->assertSame(
            null,
            $handler->getOutputPath(),
            'output path third value'
        );
    }
    
    /**
     * test invalid output path
     * 
     * @expectedException InvalidArgumentException
     */
    public function testInvalidOutputPath()
    {
        $handler = new P4Cms_AssetHandler_File(array('outputPath' => 12));
    }
    
    /**
     * test exists
     */
    public function testExists()
    {
        $id      = 'foo';
        $handler = new P4Cms_AssetHandler_File;

        $this->assertFalse($handler->exists($id), 'no output path');
        
        $handler->setOutputPath(TEST_DATA_PATH . '/resources');
        $this->assertFalse($handler->exists($id), 'output path no file');
        
        touch($handler->getOutputPath() . '/' . $id);
        $this->assertTrue($handler->exists($id), 'output path with manual file');
    }
    
    /**
     * test put
     */
    public function testPut()
    {
        $id      = 'foo';
        $handler = new P4Cms_AssetHandler_File;
        $handler->setOutputPath(TEST_DATA_PATH . '/resources');

        $this->assertFalse($handler->exists($id), 'output path no file');
        
        $handler->put($id, "test");
        $this->assertTrue($handler->exists($id), 'output path with file');

        $this->assertSame('test', file_get_contents(TEST_DATA_PATH . "/resources/$id"), 'contents');
    }
    
    /**
     * test uri
     */
    public function testUri()
    {
        $front   = Zend_Controller_Front::getInstance();
        $request = new Zend_Controller_Request_Http;
        $handler = new P4Cms_AssetHandler_File;

        $request->setBaseUrl('/base');
        $front->setRequest($request);

        $this->assertFalse($handler->uri('foo'), 'no output path');
        
        $handler->setOutputPath(TEST_DATA_PATH . "/resources");
        
        $path  = '/base' . preg_replace('#.*(/tests/data/[0-9]+)#', '$1', TEST_DATA_PATH);
        $path .= '/resources/foo';
        $this->assertSame($path, $handler->uri('foo'), 'with output path');
        
        $handler->setBasePath(TEST_DATA_PATH);
        $this->assertSame('/base/resources/foo', $handler->uri('foo'), 'with output path and base path');

        $request->setBaseUrl('');
        $this->assertSame('/resources/foo', $handler->uri('foo'), 'empty request basepath');
    }

    /**
     * test is offsite
     */
    public function testIsOffsite()
    {
        $handler = new P4Cms_AssetHandler_File;
        $this->assertFalse($handler->isOffsite());
    }
}