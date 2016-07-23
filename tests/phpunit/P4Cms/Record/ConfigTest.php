<?php
/**
 * Test methods for the Record Config class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_ConfigTest extends TestCase
{
    /**
     * Set the default storage adapter to use.
     */
    public function setUp()
    {
        parent::setUp();

        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($this->p4)
                ->setBasePath("//depot");
        P4Cms_Record::setDefaultAdapter($adapter);
    }

    /**
     * Clear default storage adapter.
     */
    public function tearDown()
    {
        P4Cms_Record::clearDefaultAdapter();

        parent::tearDown();
    }

    /**
     * Test set config.
     */
    public function testSetGetConfig()
    {
        $record = new P4Cms_Record_Config;

        // ensure get works even without set.
        $config = $record->getConfig();
        $this->assertTrue(
            $config instanceof Zend_Config,
            'Get config should always return a Zend_Config obj.'
        );
        $configArray = $record->getConfigAsArray();
        $this->assertTrue(
            is_array($configArray),
            'Get config as array should return array.'
        );

        // ensure basic set/get works.
        $config = new Zend_Config(array('foo'=>'bar', 'xyz' => 123));
        $record->setConfig($config);
        $this->assertSame(
            $config,
            $record->getConfig(),
            'Expected config objects to be the same.'
        );

        // test that retrieving a named configuration item works.
        $this->assertSame(123, $record->getConfig('xyz'), 'Expected value for xyz.');

        // test that retrieving a name but nonexistant configuration item works.
        $this->assertSame('default', $record->getConfig('abc', 'default'), 'Expected default value for abc.');

        // ensure set only accepts zend_config obj and null.
        try {
            $record->setConfig('kalsdjf');
            $this->fail("Set config should fail on bad input.");
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
        try {
            $record->setConfig(null);
            $this->assertTrue(true);
        } catch (InvalidArgumentException $e) {
            $this->fail("Set config should not fail on null input.");
        }

        // ensure set from array takes array.
        $configArray = array('test' => 'value');
        $record->setConfigFromArray($configArray);
        $this->assertSame(
            $configArray,
            $record->getConfigAsArray(),
            "Expected config arrays to be the same."
        );
    }

    /**
     * Test save config.
     */
    public function testSaveFetchConfig()
    {
        // ensure non-existant record does not exist.
        $this->assertFalse(
            P4Cms_Record_Config::exists('test'),
            "Expected 'test' record to not exist."
        );

        // create/save record.
        $config = new Zend_Config(array('foo'=>'bar'));
        $record = new P4Cms_Record_Config;
        $record->setId('test')
               ->setConfig($config)
               ->save();

        // ensure record still returns correct config data.
        $this->assertSame(
            $config->toArray(),
            $record->getConfigAsArray(),
            "Expected config objects to be the same."
        );

        // ensure record exists.
        $this->assertTrue(
            P4Cms_Record_Config::exists('test'),
            "Expected 'test' record to exist post save."
        );

        // ensure fetch works.
        $record = P4Cms_Record_Config::fetch('test');
        $this->assertSame(
            $config->toArray(),
            $record->getConfigAsArray(),
            "Expected fetch to produce same config as was saved."
        );

        // create/save another record.
        $config = new Zend_Config(array('baz'=>'bof'));
        $record = new P4Cms_Record_Config;
        $record->setId('test2')
               ->setConfig($config)
               ->save();

        // ensure two records now.
        $records = P4Cms_Record_Config::fetchAll();
        $this->assertSame(
            2,
            count($records),
            "Expected two records."
        );
    }
}
