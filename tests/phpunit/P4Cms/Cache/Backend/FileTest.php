<?php
/**
 * Test methods for file cache backend.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Cache_Backend_FileTest extends TestCase
{
    /**
     * Test the constructor with various options
     */
    public function testConstructor()
    {
        $backend = new P4Cms_Cache_Backend_File;
        $this->assertSame(
            'zend_cache', 
            P4Cms_Cache_Backend_FileFriend::getOption($backend, 'file_name_prefix'), 
            'defaults'
        );
        
        $backend = new P4Cms_Cache_Backend_File(
            array(
                'namespace' => 'a test!'
            )
        );
        $this->assertSame(
            'a test!', 
            P4Cms_Cache_Backend_FileFriend::getOption($backend, 'namespace'), 
            'namespace only constructor'
        );
        $this->assertSame(
            'zend_cache_3d62eb7effce5e6005092aad777891aa', 
            P4Cms_Cache_Backend_FileFriend::getOption($backend, 'file_name_prefix'), 
            'namespace only constructor'
        );

        $backend = new P4Cms_Cache_Backend_File(
            array(
                'namespace' => 'a test!',
                'file_name_prefix' => 'file_prefix'
            )
        );
        $this->assertSame(
            'a test!', 
            P4Cms_Cache_Backend_FileFriend::getOption($backend, 'namespace'), 
            'namespace and prefix constructor'
        );
        $this->assertSame(
            'file_prefix_3d62eb7effce5e6005092aad777891aa', 
            P4Cms_Cache_Backend_FileFriend::getOption($backend, 'file_name_prefix'), 
            'namespace and prefix constructor'
        );
    }
    
    /**
     * Test setOption for file_name_prefix and namespace
     */
    public function testMutators()
    {
        $backend = new P4Cms_Cache_Backend_File;
        $this->assertSame(
            'zend_cache', 
            P4Cms_Cache_Backend_FileFriend::getOption($backend, 'file_name_prefix'), 
            'defaults'
        );

        $backend->setOption('file_name_prefix', 'test_prefix');
        $this->assertSame(
            'test_prefix', 
            P4Cms_Cache_Backend_FileFriend::getOption($backend, 'file_name_prefix'), 
            'after setting a prefix'
        );

        $backend->setOption('namespace', 'another test!');
        $this->assertSame(
            'test_prefix_c226b7d4e09432674fda0ba6d2d52dfc', 
            P4Cms_Cache_Backend_FileFriend::getOption($backend, 'file_name_prefix'), 
            'after setting a prefix'
        );
        
        $backend = new P4Cms_Cache_Backend_File;
        $backend->setOption('namespace', 'another test!');
        $this->assertSame(
            'zend_cache_c226b7d4e09432674fda0ba6d2d52dfc', 
            P4Cms_Cache_Backend_FileFriend::getOption($backend, 'file_name_prefix'), 
            'after setting a prefix'
        );
        $backend->setOption('file_name_prefix', 'test_prefix');
        $this->assertSame(
            'test_prefix_c226b7d4e09432674fda0ba6d2d52dfc', 
            P4Cms_Cache_Backend_FileFriend::getOption($backend, 'file_name_prefix'), 
            'after setting a prefix'
        );
    }
}
