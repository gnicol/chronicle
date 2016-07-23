<?php
/**
 * Test methods for static cache class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Cache_Test extends TestCase
{
    /**
     * Ensure handling of manager works.
     */
    public function testHasSetGetManager()
    {
        // should be no manager initially.
        $this->assertFalse(P4Cms_Cache::hasManager());
        try {
            P4Cms_Cache::getManager();
            $this->fail();
        } catch (Exception $e) {
            $this->assertTrue($e instanceof P4Cms_Cache_Exception);
        }

        // set should reject invalid inputs.
        $inputs = array(false, array(), 'bad', 0);
        foreach ($inputs as $input) {
            try {
                P4Cms_Cache::setManager($input);
                $this->fail();
            } catch (Exception $e) {
                $this->assertTrue($e instanceof PHPUnit_Framework_Error);
            }
        }

        // now add a manager.
        $manager = new P4Cms_Cache_Manager;
        P4Cms_Cache::setManager($manager);

        // ensure we can get it out.
        $this->assertTrue(P4Cms_Cache::hasManager());
        $this->assertSame($manager, P4Cms_Cache::getManager());

        // clear it.
        P4Cms_Cache::setManager(null);
        $this->assertFalse(P4Cms_Cache::hasManager());
    }

    /**
     * Exercise saving and loading data from cache.
     */
    public function testLoadSave()
    {
        // ensure load and save are safe to use without a manager set.
        $data = array(1, 2, 3);
        P4Cms_Cache::save($data, 'test');
        $this->assertFalse(P4Cms_Cache::load('test'));

        // now add a manager, but no cache template.
        $manager = new P4Cms_Cache_Manager;
        P4Cms_Cache::setManager($manager);

        // we expect the same behavior with a manager.
        P4Cms_Cache::save($data, 'test');
        $this->assertFalse(P4Cms_Cache::load('test'));

        // now add a cache template.
        mkdir(TEST_DATA_PATH . '/cache', 0755);
        $manager->setCacheTemplate(
            'default',
            array(
                "frontend"  => array(
                    "name"      => "Core",
                    "options"   => array("automatic_serialization" => true)
                ),
                "backend"  => array(
                    "name"      => "File",
                    "options"   => array("cache_dir" => DATA_PATH . '/cache')
                ),
            )
        );

        // we expect to get data out now.
        P4Cms_Cache::save($data, 'test');
        $this->assertSame($data, P4Cms_Cache::load('test'));

        // trying w. a non-existent named template
        P4Cms_Cache::save($data, 'test', array(), null, null, 'non-existent');
        $this->assertFalse(P4Cms_Cache::load('test', 'non-existent'));
    }

    /**
     * Test removing item when no cache is configured
     */
    public function testRemoveWithNoCache()
    {
        // take it out.
        $this->assertFalse(
            P4Cms_Cache::remove('test')
        );
    }

    /**
     * Test removing items from cache.
     */
    public function testSaveRemove()
    {
        // configure cache
        mkdir(TEST_DATA_PATH . '/cache', 0755);
        $manager = new P4Cms_Cache_Manager;
        $manager->setCacheTemplate(
            'default',
            array(
                "frontend"  => array(
                    "name"      => "Core",
                    "options"   => array("automatic_serialization" => true)
                ),
                "backend"  => array(
                    "name"      => "File",
                    "options"   => array("cache_dir" => DATA_PATH . '/cache')
                ),
            )
        );
        P4Cms_Cache::setManager($manager);


        // put some data in the cache.
        P4Cms_Cache::save(array(1, 2, 3), 'test');
        $this->assertTrue(P4Cms_Cache::load('test') !== false);

        // take it out.
        P4Cms_Cache::remove('test');
        $this->assertTrue(P4Cms_Cache::load('test') === false);
    }

    /**
     * Test clean'ing when no cache is configured
     */
    public function testCleanWithNoCache()
    {
        // take it out.
        $this->assertFalse(
            P4Cms_Cache::clean()
        );
    }

    /**
     * Test clean bad template
     */
    public function testCleanBadTag()
    {
        $this->_addTestEntries();

        $this->assertFalse(
            P4Cms_Cache::clean('all', 12),
            'Expected bad tag exception to be silently handled'
        );
    }

    /**
     * Test clean bad template
     */
    public function testCleanBadTemplate()
    {
        $this->_addTestEntries();

        $this->assertFalse(
            P4Cms_Cache::clean('all', '', 'does_not_exist'),
            'Expected made up entry to fail silently'
        );
    }

    /**
     * Test clean method
     */
    public function testSaveClean()
    {
        $this->_addTestEntries();

        P4Cms_Cache::clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, 'tag1', 'default');
        $this->assertSame(
            array('test4'),
            P4Cms_Cache::getCache('default')->getIds(),
            'Expected matching ids in default cache after clearing tag1'
        );
        $this->assertSame(
            array('alt', 'alt2', 'alt3', 'alt4'),
            P4Cms_Cache::getCache('alternate')->getIds(),
            'Expected matching ids in alternate cache after clearing tag1'
        );

        P4Cms_Cache::clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, 'tag2');
        $this->assertSame(
            array('test4'),
            P4Cms_Cache::getCache('default')->getIds(),
            'Expected matching ids in default cache after clearing tag2'
        );
        $this->assertSame(
            array('alt3', 'alt4'),
            P4Cms_Cache::getCache('alternate')->getIds(),
            'Expected matching ids in alternate cache after clearing tag2'
        );

        P4Cms_Cache::clean();
        $this->assertSame(
            array(),
            P4Cms_Cache::getCache('default')->getIds(),
            'Expected matching ids in default cache after clearing all'
        );
        $this->assertSame(
            array(),
            P4Cms_Cache::getCache('alternate')->getIds(),
            'Expected matching ids in alternate cache after clearing all'
        );
    }

    /**
     * Test the can cache method
     */
    public function testCanCache()
    {
        $this->assertFalse(P4Cms_Cache::canCache());

        // now add a manager, but no cache template.
        $manager = new P4Cms_Cache_Manager;
        P4Cms_Cache::setManager($manager);

        $this->assertFalse(P4Cms_Cache::canCache());

        // now add a cache template.
        $manager->setCacheTemplate(
            'default',
            array(
                "frontend"  => array(
                    "name"      => "Core",
                    "options"   => array("automatic_serialization" => true)
                ),
                "backend"  => array(
                    "name"      => "File",
                    "options"   => array("cache_dir" => DATA_PATH . '/cache')
                ),
            )
        );

        $this->assertTrue(P4Cms_Cache::canCache());

        // still can't cache to a non-existent template.
        $this->assertFalse(P4Cms_Cache::canCache('woozle'));
    }

    /**
     * Test retrieval of a made up cache template
     */
    public function testBadGetCache()
    {
        $this->_addTestEntries();
        
        $this->assertFalse(
            P4Cms_Cache::getCache('does_not_exist'),
            'Expected to get a false return code for made up cache'
        );
    }

    /**
     * Setup caching and add some test entries
     */
    protected function _addTestEntries()
    {
        // configure cache
        mkdir(TEST_DATA_PATH . '/cache/default',   0755, true);
        mkdir(TEST_DATA_PATH . '/cache/alternate', 0755, true);
        $manager = new P4Cms_Cache_Manager;
        $manager->setCacheTemplate(
            'default',
            array(
                "frontend"  => array(
                    "name"      => "Core",
                    "options"   => array("automatic_serialization" => true)
                ),
                "backend"  => array(
                    "name"      => "File",
                    "options"   => array("cache_dir" => DATA_PATH . '/cache/default')
                ),
            )
        );
        $manager->setCacheTemplate(
            'alternate',
            array(
                "frontend"  => array(
                    "name"      => "Core",
                    "options"   => array("automatic_serialization" => true)
                ),
                "backend"  => array(
                    "name"      => "File",
                    "options"   => array("cache_dir" => DATA_PATH . '/cache/alternate')
                ),
            )
        );
        P4Cms_Cache::setManager($manager);

        // put some data in the default cache.
        P4Cms_Cache::save(array(1),          'test',  array('tag1', 'tag2'));
        P4Cms_Cache::save(array(1, 2),       'test2', array('tag1', 'tag2'));
        P4Cms_Cache::save(array(1, 2, 3),    'test3', array('tag1'));
        P4Cms_Cache::save(array(1, 2, 3, 4), 'test4');

        // put some data in the alternate cache.
        P4Cms_Cache::save(array('a'),                'alt',  array('tag1', 'tag2'), false, 8, 'alternate');
        P4Cms_Cache::save(array('a', 'b'),           'alt2', array('tag1', 'tag2'), false, 8, 'alternate');
        P4Cms_Cache::save(array('a', 'b', 'c'),      'alt3', array('tag1'),         false, 8, 'alternate');
        P4Cms_Cache::save(array('a', 'b', 'c', 'd'), 'alt4', array(),               false, 8, 'alternate');

        // verify it looks sane
        $this->assertTrue(P4Cms_Cache::canCache(), 'expected cancache to work against default');
        $this->assertTrue(P4Cms_Cache::canCache('alternate'), 'expected cancache to work against alternate');

        $this->assertSame(
            array('test', 'test2', 'test3', 'test4'),
            P4Cms_Cache::getCache('default')->getIds(),
            'Expected matching IDs in default cache'
        );
        $this->assertSame(
            array('alt', 'alt2', 'alt3', 'alt4'),
            P4Cms_Cache::getCache('alternate')->getIds(),
            'Expected matching IDs in alternate cache'
        );

        $this->assertSame(
            array('test', 'test2', 'test3'),
            P4Cms_Cache::getCache('default')->getIdsMatchingTags(array('tag1')),
            'Expected matching IDs when limiting by tag on default cache'
        );
        $this->assertSame(
            array('alt', 'alt2'),
            P4Cms_Cache::getCache('alternate')->getIdsMatchingTags(array('tag2')),
            'Expected matching IDs when limiting by tag on alternate cache'
        );
    }
}
