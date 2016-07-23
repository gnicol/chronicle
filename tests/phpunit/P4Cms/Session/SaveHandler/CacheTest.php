<?php
/**
 * Test methods for the Cache Session Save Handler.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Session_SaveHandler_CacheTest extends TestCase
{
    /**
     * Test constructing the object with and without options
     */
    public function testConstructor()
    {
        $noArgs = new P4Cms_Session_SaveHandler_Cache;
        $this->assertTrue(
            $noArgs instanceof P4Cms_Session_SaveHandler_Cache,
            'expected matching class when no args passed'
        );

        $allArgs = new P4Cms_Session_SaveHandler_Cache(
            array(
                'idPrefix' => 'foo',
                'backend'  => array(
                    'name' => 'Black Hole'
                )
            )
        );
        $this->assertTrue(
            $allArgs instanceof P4Cms_Session_SaveHandler_Cache,
            'expected matching class when args passed'            
        );
        $this->assertSame(
            'foo',
            $allArgs->getIdPrefix(),
            'expected matching id prefix'
        );
        $this->assertTrue(
            $allArgs->getBackend() instanceof Zend_Cache_Backend_BlackHole,
            'expected proper backend when args passed'
        );
    }
    
    /**
     * Test the backend accessor/mutator with both good and bad values
     */
    public function testGetSetBackend()
    {
        // skip this test if no memcache or memcached extension present.
        if (!extension_loaded('memcache') && !extension_loaded('memcached')) {
            $this->markTestSkipped('Cannot test memcached session backend without a memcached extension');
        }
        
        $handler = new P4Cms_Session_SaveHandler_Cache;
        $this->assertSame(
            null,
            $handler->getBackend(),
            'expected matching default'
        );
        
        $handler->setBackend(new P4Cms_Cache_Backend_MemcachedTagged);
        $this->assertTrue(
            $handler->getBackend() instanceof P4Cms_Cache_Backend_MemcachedTagged,
            'expected class instance to take'
        );
        
        $handler->setBackend(array('name' => 'Black Hole'));
        $this->assertTrue(
            $handler->getBackend() instanceof Zend_Cache_Backend_BlackHole,
            'expected shorthand name in array to take'
        );
        
        $handler->setBackend(array('name' => 'Zend_Cache_Backend_File', 'customBackendNaming' => 1));
        $this->assertTrue(
            $handler->getBackend() instanceof Zend_Cache_Backend_File,
            'expected class name in array to take'
        );
        
        try {
            $handler->setBackend(12);
            $this->fail('expected exception on int');
        } catch (InvalidArgumentException $e) {
        }
        
        try {
            $handler->setBackend(false);
            $this->fail('expected exception on bool');
        } catch (InvalidArgumentException $e) {
        }
        
        try {
            $handler->setBackend('foo');
            $this->fail('expected exception on string');
        } catch (InvalidArgumentException $e) {
        }
        
        try {
            $handler->setBackend(new Exception);
            $this->fail('expected exception on invalid object');
        } catch (InvalidArgumentException $e) {
        }
    }

    /**
     * Test the id prefix accessor/mutator with both good and bad values
     */
    public function testGetSetIdPrefix()
    {
        $handler = new P4Cms_Session_SaveHandler_Cache;
        $this->assertSame(
            'session-',
            $handler->getIdPrefix(),
            'expected matching default'
        );

        $handler->setIdPrefix(null);
        $this->assertSame(
            null,
            $handler->getIdPrefix(),
            'expected null to work'
        );
        
        $handler->setIdPrefix('');
        $this->assertSame(
            '',
            $handler->getIdPrefix(),
            'expected empty string to work'
        );
        
        $handler->setIdPrefix('foo');
        $this->assertSame(
            'foo',
            $handler->getIdPrefix(),
            'expected string to work'
        );

        try {
            $handler->setIdPrefix(false);
            $this->fail('should have thrown on false');
        } catch (InvalidArgumentException $e) {
        }

        try {
            $handler->setIdPrefix(12);
            $this->fail('should have thrown on int');
        } catch (InvalidArgumentException $e) {
        }
    }
    
    /**
     * Some of the methods are just stubbed to satisfy 
     * the interface try them here.
     */
    public function testStubOpenCloseGcMethods()
    {
        $handler = new P4Cms_Session_SaveHandler_Cache;
        
        $this->assertTrue($handler->open(12, 22), 'expected open to work with int args');
        $this->assertTrue($handler->open('a', 'b'), 'expected open to work with string args');
        $this->assertTrue($handler->open(null, null), 'expected open to work with null args');
        
        $this->assertTrue($handler->close(), 'expected close to work');
        
        $this->assertTrue($handler->gc(12), 'expected open to work with int arg');
        $this->assertTrue($handler->gc('a'), 'expected open to work with string arg');
        $this->assertTrue($handler->gc(null), 'expected open to work with null arg');
    }
    
    /**
     * Verify the read/write operations work.
     */
    public function testReadWrite()
    {
        // set some constants for the test and make storage folder
        $cacheDir  = TEST_DATA_PATH . '/handler-cache/';
        $sessionId = 'abc123test4me';
        mkdir($cacheDir, 0777, true);
        
        // get a handler and backend for testing
        $handler = new P4Cms_Session_SaveHandler_Cache(
            array(
                'backend'  => array(
                    'name' => 'File',
                    'options' => array('cache_dir' => $cacheDir)
                )
            )
        );
        $backend = $handler->getBackend();
        
        // verify starting environment is correct
        $this->assertTrue(
            $handler->getBackend() instanceof Zend_Cache_Backend_File, 
            'expected file backend'
        );

        $this->assertSame(
            'session-',
            $handler->getIdPrefix(),
            'expected matching prefix'
        );
        
        
        // no cache entry should exist out of the gate
        $this->assertFalse(
            $backend->load($handler->getIdPrefix() . $sessionId),
            'expected no session data at start - raw access'
        );
        $this->assertFalse(
            $handler->read($sessionId),
            'expected handler to fail to read at start'
        );
        
        
        // after write we expect entry to be in storage
        $data = 'i am test data';
        $handler->write($sessionId, $data);
        $this->assertSame(
            $data,
            $backend->load($handler->getIdPrefix() . $sessionId),
            'expected matching session data after write - raw access'
        );
        $this->assertSame(
            $data,
            $handler->read($sessionId),
            'expected handler read to match after writing'
        );

        // try no id prefix
        $data .= '-2';
        $handler->setIdPrefix(null)->write($sessionId, $data);
        $this->assertSame(
            $data,
            $backend->load($sessionId),
            'expected matching session data after write - raw access'
        );
        $this->assertSame(
            $data,
            $handler->read($sessionId),
            'expected handler read to match after writing'
        );

        // try different id prefix
        $data .= '-3';
        $handler->setIdPrefix('custom')->write($sessionId, $data);
        $this->assertSame(
            $data,
            $backend->load('custom' . $sessionId),
            'expected matching session data after write - raw access'
        );
        $this->assertSame(
            $data,
            $handler->read($sessionId),
            'expected handler read to match after writing'
        );
    }
    
    /**
     * Verify destroy removes entry
     */
    public function testDestroy()
    {
        // set some constants for the test and make storage folder
        $cacheDir  = TEST_DATA_PATH . '/handler-cache/';
        $sessionId = 'abc123test4me';
        mkdir($cacheDir, 0777, true);
        
        // get a handler and backend for testing
        $handler = new P4Cms_Session_SaveHandler_Cache(
            array(
                'backend'  => array(
                    'name' => 'File',
                    'options' => array('cache_dir' => $cacheDir)
                )
            )
        );
        $backend = $handler->getBackend();
        
        // no cache entry should exist out of the gate
        $this->assertFalse(
            $backend->load($handler->getIdPrefix() . $sessionId),
            'expected no session data at start - raw access'
        );
        $this->assertFalse(
            $handler->read($sessionId),
            'expected handler to fail to read at start'
        );
        
        
        // after write we expect entry to be in storage
        $data = 'i am test data';
        $handler->write($sessionId, $data);
        $this->assertSame(
            $data,
            $backend->load($handler->getIdPrefix() . $sessionId),
            'expected matching session data after write - raw access'
        );
        $this->assertSame(
            $data,
            $handler->read($sessionId),
            'expected handler read to match after writing'
        );
        
        
        // no cache entry should exist after destroy
        $handler->destroy($sessionId);
        $this->assertFalse(
            $backend->load($handler->getIdPrefix() . $sessionId),
            'expected no session data after destroy - raw access'
        );
        $this->assertFalse(
            $handler->read($sessionId),
            'expected handler to fail after destroy'
        );
    }
}