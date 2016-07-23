<?php
/**
 * Test methods for the record class when used directly.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_PubSubTest extends TestCase
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
     * Ensure get/has topic work as expected.
     */
    public function testTopic()
    {
        // ensure get-topic throws if no topic.
        $record = new P4Cms_Record_PubSubRecord;
        $this->assertFalse($record->hasTopic());
        try {
            $record->getTopic();
            $this->fail("Get topic should throw if no topic set.");
        } catch (P4Cms_Record_Exception $e) {
            $this->assertTrue(true);
        }

        // ensure we can get-topic when set.
        $record = new P4Cms_Record_ImplementationPubSub;
        $this->assertTrue($record->hasTopic());
        $this->assertSame($record->getTopic(), 'p4cms.record.test');
    }

    /**
     * Test save events
     */
    public function testSave()
    {
        // ensure no record initially.
        $this->assertSame(P4Cms_Record_ImplementationPubSub::count(), 0);

        $record = new P4Cms_Record_ImplementationPubSub;
        $record->setId(1)
               ->setValue('foo', 'bar');

        $test = $this;

        // ensure pre-save subscribers get the pre-save record.
        P4Cms_PubSub::subscribe(
            $record->getTopic() . ".preSave",
            function($record) use ($test)
            {
                $test->assertTrue($record instanceof P4Cms_Record_PubSubRecord);
                $test->assertSame('1', $record->getId());
                $test->assertSame($record->foo, 'bar');
                $test->assertTrue($record->getAdapter()->getBatchId() > 0);

                $record->calledPreSave = true;
            }
        );

        // ensure post-save subscribers get the post-save record.
        P4Cms_PubSub::subscribe(
            $record->getTopic() . ".postSave",
            function($record) use ($test)
            {
                $test->assertTrue($record instanceof P4Cms_Record_PubSubRecord);
                $test->assertSame($record->getId(), '1');
                $test->assertSame($record->foo, 'bar');
                $test->assertTrue($record->getAdapter()->getBatchId() > 0);

                $record->calledPostSave = true;
            }
        );

        $record->save();

        // ensure both of our callbacks were called.
        $this->assertTrue($record->calledPreSave);
        $this->assertTrue($record->calledPostSave);

        // ensure record was actually saved.
        $this->assertSame(P4Cms_Record_ImplementationPubSub::count(), 1);
    }

    /**
     * Test query event
     */
    public function testQuery()
    {
        $record = new P4Cms_Record_ImplementationPubSub;
        $record->setId(1)
               ->setValue('foo', 'bar')
               ->save();

        // count every time query is called.
        $test = $this;
        P4Cms_PubSub::subscribe(
            $record->getTopic() . ".query",
            function($query, $adapter) use ($test)
            {
                $test->queryCount++;
                $test->assertTrue($query   instanceof P4Cms_Record_Query);
                $test->assertTrue($adapter instanceof P4Cms_Record_Adapter);
            }
        );

        $this->assertTrue(P4Cms_Record_ImplementationPubSub::exists(1));
        $this->assertSame(1, P4Cms_Record_ImplementationPubSub::count());
        $this->assertTrue(
            P4Cms_Record_ImplementationPubSub::fetch(1) instanceof P4Cms_Record_ImplementationPubSub
        );
        $this->assertTrue(
            P4Cms_Record_ImplementationPubSub::fetchAll() instanceof P4Cms_Model_Iterator
        );

        $this->assertSame($this->queryCount, 4);

        // ensure we can influence results.
        P4Cms_PubSub::subscribe(
            $record->getTopic() . ".query",
            function($query, $adapter) use ($test)
            {
                $query->addFilter(
                    P4Cms_Record_Filter::create()->add('foo', 'baz')
                );
            }
        );

        $this->assertFalse(P4Cms_Record_ImplementationPubSub::exists(1));
        $this->assertSame(0, P4Cms_Record_ImplementationPubSub::count());
    }

    /**
     * Test delete event
     */
    public function testDelete()
    {
        // ensure no record initially.
        $this->assertSame(P4Cms_Record_ImplementationPubSub::count(), 0);

        $record = new P4Cms_Record_ImplementationPubSub;
        $record->setId(1)
               ->setValue('foo', 'bar')
               ->save();

        $test = $this;

        // ensure delete subscribers get the record.
        P4Cms_PubSub::subscribe(
            $record->getTopic() . ".delete",
            function($record) use ($test)
            {
                $test->assertTrue($record instanceof P4Cms_Record_PubSubRecord);
                $test->assertSame($record->id,  '1');
                $test->assertSame($record->foo, 'bar');
                $test->assertTrue($record->getAdapter()->getBatchId() > 0);

                $record->calledDelete = true;
            }
        );

        $record->delete();

        // ensure both of our callbacks were called.
        $this->assertTrue($record->calledDelete);

        // ensure record was actually deleted.
        $this->assertSame(0, P4Cms_Record_ImplementationPubSub::count());
    }
}
