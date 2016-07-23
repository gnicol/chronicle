<?php
/**
 * Test methods for memcached tagged cache backend.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Cache_Backend_MemcachedTaggedTest extends TestCase
{
    protected   $_backend;

    /**
     * Checks if we can determine the memcached host and skips tests if we cannot.
     * If we do have a host this sets up the backend for the specified host/port.
     */
    public function setUp()
    {
        // pull in MEMCACHED_HOST from environment if needed
        if (!defined('MEMCACHED_HOST') && getenv('P4CMS_TEST_MEMCACHED_HOST')) {
            define('MEMCACHED_HOST', getenv('P4CMS_TEST_MEMCACHED_HOST'));
        }

        // if MEMCACHED_HOST still not defined, warn the tester.
        if (!defined('MEMCACHED_HOST')) {
            $this->markTestSkipped('The MEMCACHED_HOST is not defined.');
            return;
        }

        parent::setUp();

        // host is allowed to contain a port; generate a valid server array
        $host   = explode(':', MEMCACHED_HOST, 2);
        $server = array('host' => $host[0]);
        if (isset($host[1])) {
            $server['port'] = $host[1];
        }

        // create and clean the backend
        $this->_backend = new P4Cms_Cache_Backend_MemcachedTagged(
            array(
                'namespace' => 'a test!',
                'servers'   => $server
            )
        );
        $this->_backend->clean();
    }

    /**
     * Do a very basic set/get test
     */
    public function testSetGet()
    {
        $this->_backend->save('i am some data!', 'id1');
        $this->assertSame(
            'i am some data!',
            $this->_backend->load('id1'),
            'expected matching result for un-tagged entry'
        );

        $this->_backend->save('i am also data!', 'id2', array('foo', 'bar'));
        $this->assertSame(
            'i am also data!',
            $this->_backend->load('id2'),
            'expected matching result for tagged entry'
        );
    }

    /**
     * Create a number of entries and verify clearing by
     * various tags works correctly.
     */
    public function testTagClearing()
    {
        $this->_backend->save('d-n1', 'n1');
        $this->_backend->save('d-t1', 't1', array('foo', 'bar', 'boo'));
        $this->_backend->save('d-t2', 't2', array('foo', 'biz', 'bar'));
        $this->_backend->save('d-t3', 't3', array('foo', 'bang', 'bar'));
        $this->_backend->save('d-t4', 't4', array('bang'));

        $this->assertSame(
            array(false, 'd-n1', 'd-t1', 'd-t2', 'd-t3', 'd-t4'),
            array_map(array($this->_backend, 'load'), array('notreal', 'n1', 't1', 't2', 't3', 't4')),
            'Expected matching values after save'
        );

        $this->_backend->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('madeup'));
        $this->assertSame(
            array(false, 'd-n1', 'd-t1', 'd-t2', 'd-t3', 'd-t4'),
            array_map(array($this->_backend, 'load'), array('notreal', 'n1', 't1', 't2', 't3', 't4')),
            'Expected matching values cleaning by made up tag'
        );

        $this->_backend->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('bang'));
        $this->assertSame(
            array(false, 'd-n1', 'd-t1', 'd-t2', false, false),
            array_map(array($this->_backend, 'load'), array('notreal', 'n1', 't1', 't2', 't3', 't4')),
            'Expected matching values cleaning by bang tag'
        );

        $this->_backend->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('biz', 'boo'));
        $this->assertSame(
            array(false, 'd-n1', false, false, false, false),
            array_map(array($this->_backend, 'load'), array('notreal', 'n1', 't1', 't2', 't3', 't4')),
            'Expected matching values cleaning by fiz and boo tags'
        );

        $this->_backend->clean();
        $this->assertSame(
            array(false, false, false, false, false, false),
            array_map(array($this->_backend, 'load'), array('notreal', 'n1', 't1', 't2', 't3', 't4')),
            'Expected matching values after full clean'
        );
    }
}