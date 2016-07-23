<?php
/**
 * Test methods for the record class when sub-classed.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_Test extends TestCase
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
     * Test instantiate records.
     */
    public function testInstantiate()
    {
        $record = new P4Cms_Record_Implementation;
        $this->assertTrue($record instanceof P4Cms_Record, 'Expected object type');

        $this->assertSame(
            '//depot/records',
            $record->getStoragePath(),
            'Expected storage path'
        );

        $record = P4Cms_Record_Implementation::create();
        $this->assertTrue($record instanceof P4Cms_Record, 'Expected object type');

        $this->assertSame(
            '//depot/records',
            $record->getStoragePath(),
            'Expected storage path'
        );
    }

    /**
     * Test setId().
     */
    public function testSetId()
    {
        $record = new P4Cms_Record;
        $this->assertSame(
            null,
            $record->getId(),
            'Expected id in new record object'
        );

        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'record'    => $record,
                'id'        => null,
                'expect'    => null,
            ),
            array(
                'label'     => __LINE__ .': numeric',
                'record'    => $record,
                'id'        => 3,
                'expect'    => '3',
            ),
            array(
                'label'     => __LINE__ .': int string',
                'record'    => $record,
                'id'        => '2',
                'expect'    => '2',
            ),
            array(
                'label'     => __LINE__ .': zero',
                'record'    => $record,
                'id'        => 0,
                'expect'    => '0',
            ),
            array(
                'label'     => __LINE__ .': string',
                'record'    => $record,
                'id'        => 'laksdjf',
                'expect'    => 'laksdjf',
            ),
        );
        foreach ($tests as $test) {
            $label = $test['label'];
            $record = $test['record'];
            $record->setId($test['id']);
            $this->assertSame(
                $test['expect'],
                $record->getId(),
                "$label - expected id"
            );
        }

        // test invalid ids.
        $tests = array(
            array(
                'label'     => __LINE__ .': negative',
                'record'    => $record,
                'id'        => -3,
                'error'     => 'Cannot set id. Given id is invalid.',
            ),
            array(
                'label'     => __LINE__ .': array',
                'record'    => $record,
                'id'        => array('2'),
                'error'     => 'Cannot set id. Given id is invalid.',
            ),
            array(
                'label'     => __LINE__ .': invalid id',
                'record'    => $record,
                'id'        => 'in%valid',
                'error'     => 'Cannot set id. Given id is invalid.',
            ),
        );
        foreach ($tests as $test) {
            $label = $test['label'];
            $record = $test['record'];
            try {
                $record->setId($test['id']);
                $this->fail("$label - expected exception.");
            } catch (InvalidArgumentException $e) {
                $this->assertSame(
                    $test['error'],
                    $e->getMessage(),
                    "$label - expected message."
                );
            }
        }
    }

    /**
     * Test handling of implementations with/without fileContentField specified.
     */
    public function testImplementationHandling()
    {
        $classes = array(
            'P4Cms_Record_Implementation',
            'P4Cms_Record_ImplementationNoFileContentField',
        );

        foreach ($classes as $class) {
            $this->recordHandlingTests($class);
            $this->tearDown();
            $this->setUp();
        }
    }

    /**
     * Test record handling, ie: fetch(), fetchAll, save(), accessors/mutators, and delete().
     *
     * @param  string  $class  Implementation class to use for testing.
     */
    protected function recordHandlingTests($class)
    {
        // confirm that no records exist, yet.
        $records = $class::fetchAll();
        $this->assertSame(0, count($records), "$class: Expected fetchAll count with 0 records");

        // test a non-existant record
        $id = 1234567890;
        try {
            $record = $class::fetch($id);
            $this->fail("$class: Unexpected success fetching a non-existant record.");
        } catch (P4Cms_Record_NotFoundException $e) {
            $this->assertSame(
                "Cannot fetch record '$id'. Record does not exist.",
                $e->getMessage(),
                "$class: Expected exception fetching a non-existant record."
            );
        } catch (Exception $e) {
            $this->fail(
                "$class: Unexpected exception fetching a non-existant record: "
                . $e->getMessage()
            );
        }

        // make a new record
        $record = new $class;
        $this->assertSame('Record Title', $record->getTitle(), 'Expected default title');
        $this->assertSame('Record content.', $record->getContent(), 'Expected default content');

        // modify and save the record
        $content = 'New content.';
        $record->setContent($content);
        $this->assertSame($content, $record->getContent(), "$class: Expected new content");
        $record->save('Saving new content');
        $recordId = $record->getId();

        // fetch it again
        $record = $class::fetch($recordId);
        $this->assertSame($recordId, $record->getId(), "$class: Expected id");
        $this->assertSame('Record Title', $record->getTitle(), "$class: Expected title");
        $this->assertSame($content, $record->getContent(), "$class: Expected fetched content");

        // confirm that fetchAll retrieves the same record
        $records = $class::fetchAll();
        $this->assertSame(1, count($records), "$class: Expected fetchAll count with 1 records");
        $this->assertSame($recordId, $records->first()->getId(), "$class: Expected fetchAll #1 id");
        $this->assertSame('Record Title', $records->first()->getTitle(), "$class: Expected fetchAll #1 title");
        $this->assertSame($content, $records->first()->getContent(), "$class: Expected fetchAll #1 content");

        // make a second new record
        $record2 = new $class;
        $title2 = 'Second Title';
        $content2 = 'Content #2';
        $record2->setTitle($title2);
        $record2->setContent($content2);
        $record2->save('Saving second content.');
        $record2Id = $record2->getId();

        // fetch the second record and verify it
        $record2 = $class::fetch($record2Id);
        $this->assertSame($record2Id, $record2->getId(), "$class: Expected second record id");
        $this->assertSame($title2, $record2->getTitle(), "$class: Expected second record title");
        $this->assertSame($content2, $record2->getContent(), "$class: Expected second record content");

        // now make sure fetchAll retrieves them both
        $records = $class::fetchAll();
        $records->sortBy('content', array('DESC'));

        $this->assertSame(2, count($records), "$class: Expected fetchAll count with 2 records");

        $this->assertSame($recordId, $records->first()->getId(), "$class: Expected fetchAll #2 id #1");
        $this->assertSame('Record Title', $records->first()->getTitle(), "$class: Expected fetchAll #2 title #1");
        $this->assertSame($content, $records->first()->getContent(), "$class: Expected fetchAll #2 content #1");

        $this->assertSame($record2Id, $records->last()->getId(), "$class: Expected fetchAll #2 id #2");
        $this->assertSame($title2, $records->last()->getTitle(), "$class: Expected fetchAll #2 title #2");
        $this->assertSame($content2, $records->last()->getContent(), "$class: Expected fetchAll #2 content #2");

        // modify second record
        $content2 = 'Modified content #2';
        $record2->setContent($content2);
        $record2->save('Update content #2');

        $record2 = $class::fetch($record2Id);
        $this->assertSame($content2, $record2->getContent(), "$class: Expected second record modified content");

        // test modification without description
        $content2 = 'Modified content #3';
        $record2->setContent($content2);
        $record2->save();

        $record2 = $class::fetch($record2Id);
        $this->assertSame($content2, $record2->getContent(), "$class: Expected second record modified content");

        // delete the second record
        $record2->delete();
        try {
            $record = $class::fetch($record2Id);
            $this->fail("$class: Unexpected success fetching deleted record.");
        } catch (P4Cms_Record_NotFoundException $e) {
            $this->assertSame(
                "Cannot fetch record '$record2Id'. Record does not exist.",
                $e->getMessage(),
                "$class: Expected exception fetching deleted record."
            );
        } catch (Exception $e) {
            $this->fail(
                "$class: Unexpected exception fetching deleted record: "
                . $e->getMessage()
            );
        }

        // confirm that fetchAll retrieves the remaining record
        $records = $class::fetchAll();
        $this->assertSame(1, count($records), "$class: Expected fetchAll count with only 1 record");
        $this->assertSame($recordId, $records->first()->getId(), "$class: Expected fetchAll #3 id");
        $this->assertSame('Record Title', $records->first()->getTitle(), "$class: Expected fetchAll #3 title");
        $this->assertSame($content, $records->first()->getContent(), "$class: Expected fetchAll #3 content");

        // test deleting a record a second time
        try {
            $record2->delete('delete me again');
            $this->fail("$class: Unexpected success deleting record for second time.");
        } catch (P4_File_Exception $e) {
            $this->assertSame(
                "Failed to open file for delete: //depot/records/" . $record2Id . " - file(s) not on client.",
                $e->getMessage(),
                "$class: Expected error trying to delete a record a second time"
            );
        } catch (Exception $e) {
            $this->fail("$class: Unexpected exception deleting record for second time: ". $e->getMessage());
        }
    }

    /**
     * Test id exists method.
     */
    public function testIdExists()
    {
        $this->assertFalse(
            P4Cms_Record_Implementation::exists(1),
            'Id should not exist (line: ' . __LINE__. ').'
        );
        $this->assertFalse(
            P4Cms_Record_Implementation::exists(1, array('includeDeleted' => true)),
            'Id should not exist (line: ' . __LINE__. ').'
        );

        $record = new P4Cms_Record_Implementation;
        $record->setTitle('test');
        $record->save();

        $this->assertTrue(
            P4Cms_Record_Implementation::exists($record->getId()),
            'Id should exist (line: ' . __LINE__. ').'
        );
        $this->assertTrue(
            P4Cms_Record_Implementation::exists($record->getId(), array('includeDeleted' => true)),
            'Id should exist (line: ' . __LINE__. ').'
        );

        $record->delete();

        $this->assertFalse(
            P4Cms_Record_Implementation::exists($record->getId()),
            'Id should not exist (line: ' . __LINE__. ').'
        );
        $this->assertTrue(
            P4Cms_Record_Implementation::exists($record->getId(), array('includeDeleted' => true)),
            'Id should exist (line: ' . __LINE__. ').'
        );

        $this->assertFalse(
            P4Cms_Record_Implementation::exists(''),
            'Id should not exist (line: ' . __LINE__. ').'
        );
    }

    /**
     * Test setValue with no defined fields.
     */
    public function testAddingFields()
    {
        $record = new P4Cms_Record;

        // test setting a valid field name
        $value = 'bob';
        $record->setValue('mytest', $value);
        $this->assertEquals($value, $record->getValue('mytest'));

        // test setting an invalid field name
        try {
            $record->setValue('_invalid', $value);
            $this->fail('Unexpected success.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (Exception $e) {
            $this->assertSame(
                'P4Cms_Record_Exception',
                get_class($e),
                'Expected exception:'. $e->getMessage()
            );
            $this->assertEquals(
                "Cannot set value. Field '_invalid' is not a valid field name.",
                $e->getMessage(),
                'Expected error.'
            );
        }
    }

    /**
     * Test setting values during construction.
     */
    public function testConstructorValues()
    {
        $values = array(
            'title'     => 'test title',
            'content'   => 'the test content',
        );
        $record = new P4Cms_Record_Implementation($values);

        $this->assertEquals(
            array('id' => null) + $values,
            $record->getValues(),
            'Expected values'
        );
    }

    /**
     * Test getFileContentField with no file content field.
     */
    public function testGetFileContentFieldWithoutField()
    {
        $record = new P4Cms_Record_ImplementationNoFileContentField;
        try {
            $data = $record->getFileContentField('doesnotexist');
            $this->fail('Unexpected success.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (Exception $e) {
            $this->assertSame(
                'P4Cms_Record_Exception',
                get_class($e),
                'Expected exception.'
            );
            $this->assertEquals(
                'Cannot get the file content field. No field is mapped to the file.',
                $e->getMessage(),
                'Expected error.'
            );
        }
    }

    /**
     * Test getStoragePath when the adapter has no base path set.
     */
    public function testGetStoragePathWithoutBasePath()
    {
        $record = new P4Cms_Record_Implementation;
        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($record->getAdapter()->getConnection());

        try {
            $path = $record->getStoragePath($adapter);
            $this->fail('Unexpected success.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (Exception $e) {
            $this->assertSame(
                'P4Cms_Record_Exception',
                get_class($e),
                'Expected exception.'
            );
            $this->assertEquals(
                'Cannot get base path. No base path is set.',
                $e->getMessage(),
                'Expected error.'
            );
        }
    }

    /**
     * Test get/set/clear/has adapter.
     */
    public function testGetSetClearHasAdapter()
    {
        $record = new P4Cms_Record_ImplementationNoDefaultAdapter;

        // initial getAdapter should fail, as one has not yet been set.
        $adapter = null;
        try {
            $adapter = $record->getAdapter();
            $this->fail('Unexpected success getting adapter.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (Exception $e) {
            $this->assertSame(
                'P4Cms_Record_Exception',
                get_class($e),
                'Expected exception getting adapter.'
            );
            $this->assertEquals(
                'Cannot get storage adapter. Adapter has not been set.',
                $e->getMessage(),
                'Expected error getting adapter.'
            );
        }

        // check hasAdapter
        $this->assertFalse($record->hasAdapter(), 'Expected no adapter');

        // try setting an invalid adapter.
        try {
            $record->setAdapter($adapter);
            $this->fail('Unexpected success setting adapter.');
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        // try setting a valid adapter
        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($this->p4)
                ->setBasePath('//depot/records');
        $record->setAdapter($adapter);
        $this->assertTrue($record->hasAdapter(), 'Expected an adapter now');
        $recordAdapter = $record->getAdapter();
        $this->assertSame($adapter, $recordAdapter, 'Expected adapter');

        // try clearing the adapter.
        $record->clearAdapter();
        $this->assertFalse($record->hasAdapter(), 'Expected no adapter after clear');
    }

    /**
     * Test has/clear/set defaultAdapter
     */
    public function testHasClearSetDefaultAdapter()
    {
        $record = new P4Cms_Record_Implementation;
        $this->assertTrue($record->hasDefaultAdapter(), 'Expected a default adapter');
        $record->clearDefaultAdapter();
        $this->assertFalse($record->hasDefaultAdapter(), 'Expected a default adapter');

        // try setting an invalid default adapter.
        $adapter = null;
        try {
            $record->setDefaultAdapter($adapter);
            $this->fail('Unexpected success setting default adapter.');
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        // try setting a valid default adapter
        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($this->p4)
                ->setBasePath('//depot/records');
        $record->setDefaultAdapter($adapter);
        $this->assertTrue($record->hasDefaultAdapter(), 'Expected a default adapter now');
        $recordAdapter = $record->getDefaultAdapter();
        $this->assertSame($adapter, $recordAdapter, 'Expected default adapter');
    }

    /**
     * Test remove.
     */
    public function testRemove()
    {
        // test initial conditions
        $this->assertFalse(
            P4Cms_Record::exists('test2', array('includeDeleted' => true)),
            'Expect record to not exist initially'
        );
        $records = P4Cms_Record_Implementation::fetchAll();
        $this->assertEquals(0, count($records), 'Expected no records');

        // create some records
        $record = new P4Cms_Record_Implementation;
        $record->setId('test1')->setTitle('test1')->save();
        $this->assertTrue(P4Cms_Record_Implementation::exists('test1'), 'Expect record 1 to exist after save');
        $record->setId('test2')->setTitle('test2')->save();
        $this->assertTrue(P4Cms_Record_Implementation::exists('test2'), 'Expect record 2 to exist after save');
        $record->setId('test3')->setTitle('test3')->save();
        $this->assertTrue(P4Cms_Record_Implementation::exists('test3'), 'Expect record 3 to exist after save');
        $records = P4Cms_Record_Implementation::fetchAll();
        $this->assertEquals(3, count($records), 'Expected three records');

        // remove a record
        $record->remove('test2');
        $this->assertTrue(P4Cms_Record_Implementation::exists('test1'), 'Expect record 1 to exist after remove');
        $this->assertFalse(P4Cms_Record_Implementation::exists('test2'), 'Expect record 2 to not exist after remove');
        $this->assertTrue(P4Cms_Record_Implementation::exists('test3'), 'Expect record 3 to exist after remove');
        $records = P4Cms_Record_Implementation::fetchAll();
        $this->assertEquals(2, count($records), 'Expected two records');
    }

    /**
     * Test field metadata capability.
     */
    public function testFieldMetadata()
    {
        $record = new P4Cms_Record_Implementation;
        $record->setTitle('test-title')
               ->setContent('test-content')
               ->save();

        // ensure record has no field metadata.
        $this->assertSame(
            array(),
            $record->getFieldMetadata('title'),
            "Expected no metadata for title field."
        );

        // ensure record has no field metadata.
        $this->assertSame(
            array(),
            $record->getFieldMetadata('content'),
            "Expected no metadata for title field."
        );

        // test that metadata content has to be an array or null
        $expectedErrMessageHead =
            'Argument 2 passed to P4Cms_Record::setFieldMetadata() must be an array';
        try {
            $record->setFieldMetadata('content', 'abc 123');
            $this->fail('Unexpected success on setting string metadata.');
        } catch (Exception $e) {
            $this->assertSame(
                $expectedErrMessageHead,
                substr($e->getMessage(), 0, strlen($expectedErrMessageHead)),
                'Expected exception when trying to set non-array meta-data'
            );
        }

        try {
            $record->setFieldMetadata('content', 120);
            $this->fail('Unexpected success on setting numeric metadata.');
        } catch (Exception $e) {
            $this->assertSame(
                $expectedErrMessageHead,
                substr($e->getMessage(), 0, strlen($expectedErrMessageHead)),
                'Expected exception when trying to set non-array meta-data'
            );
        }

        try {
            $record->setFieldMetadata('content');
            $this->assertSame(
                array(),
                $record->getFieldMetadata('content'),
                'Empty array should be returned if metadata value is omitted'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected fail when omitting metadata value.');
        }

        try {
            $record->setFieldMetadata('content', null);
            $this->assertSame(
                array(),
                $record->getFieldMetadata('content'),
                'Null should be returned if metadata value is omitted'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected fail on setting null metadata.');
        }

        // test fetching metadata for a field that does not exist
        try {
            $data = $record->getFieldMetadata('doesnotexist');
            $this->fail('Unexpected success fetching metadata from non-existant field.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (Exception $e) {
            $this->assertSame(
                'P4Cms_Record_Exception',
                get_class($e),
                'Expected exception fetching metadata from non-existant field.'
            );
            $this->assertEquals(
                'Cannot get field metadata for a non-existant field.',
                $e->getMessage(),
                'Expected error message fetching metadata from non-existant field.'
            );
        }

        // ensure in-memory metadata works.
        $titleTestData = array('test-title-key' => 'test-title-metadata');
        $record->setFieldMetadata('title', $titleTestData);
        $this->assertSame(
            $titleTestData,
            $record->getFieldMetadata('title'),
            "Title field metadata did not match before save."
        );

        // ensure in-memory structured metadata works.
        $contentTestData = array(
            'foo'   => 'one',
            'bar'   => 2,
            'baz'   => 3.14,
            'test'  => array(
                'filename'  => 'test.jpg',
                'filesize'  => 12345,
            ),
        );
        $record->setFieldMetadata('content', $contentTestData);
        $this->assertSame(
            $contentTestData,
            $record->getFieldMetadata('content'),
            "Content field metadata did not match before save."
        );

        // ensure metadata can be saved.
        $record->save();
        $this->assertSame(
            $titleTestData,
            $record->getFieldMetadata('title'),
            "Title field metadata did not match after save."
        );
        $this->assertSame(
            $contentTestData,
            $record->getFieldMetadata('content'),
            "Content field metadata did not match after save."
        );

        // ensure saved metadata can be retrieved.
        $record = P4Cms_Record_Implementation::fetch($record->getId());
        $this->assertSame(
            $titleTestData,
            $record->getFieldMetadata('title'),
            "Title field metadata did not match after retrieval."
        );
        $this->assertSame(
            $contentTestData,
            $record->getFieldMetadata('content'),
            "Content field metadata did not match after retrieval."
        );


        // verify data round-trip more exhaustively
        $tests = array(
            array(
                'label'     => __LINE__ .' null',
                'data'      => null,
                'expected'  => array(),
            ),
            array(
                'label'     => __LINE__ .' int',
                'data'      => array(123),
                'expected'  => array(123),
            ),
            array(
                'label'     => __LINE__ .' negative int',
                'data'      => array(-456),
                'expected'  => array(-456),
            ),
            array(
                'label'     => __LINE__ .' float',
                'data'      => array(123.456),
                'expected'  => array(123.456),
            ),
            array(
                'label'     => __LINE__ .' negative float',
                'data'      => array(-456.789),
                'expected'  => array(-456.789),
            ),
            array(
                'label'     => __LINE__ .' string',
                'data'      => array('abc123'),
                'expected'  => array('abc123')
            ),
            array(
                'label'     => __LINE__ .' bool',
                'data'      => array(true),
                'expected'  => array(true)
            ),
            array(
                'label'     => __LINE__ .' array',
                'data'      => array('one' => 1, 'two' => 'second', 'three' => array('foo' => 'bar')),
                'expected'  => array('one' => 1, 'two' => 'second', 'three' => array('foo' => 'bar'))
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $record->setFieldMetadata('title', $test['data']);
            $actual = $record->getFieldMetadata('title');
            $this->assertSame(
                $test['expected'],
                $actual,
                "$label - expected data"
            );
        }
    }

    /**
     * Verify toP4File and fromP4File behave
     */
    public function testToFromFile()
    {
        $record = new P4Cms_Record_Implementation;
        $record->setTitle('test-title')
               ->setContent('test-content')
               ->save();

        $file = $record->toP4File();
        $this->assertTrue(
            $file  instanceof P4_File,
            'expected file to be instance of P4_File'
        );
        $this->assertSame(
            '//depot/records/' . $record->getId(),
            $file->getFilespec(),
            'expected matching filespec'
        );

        // ensure file is a copy not a reference
        $file->setFilespec('//depot/made/up/stuff');

        $this->assertSame(
            '//depot/records/' . $record->getId(),
            $record->toP4file()->getFilespec(),
            'expected records file to be unaffected by changes to "toP4File" instance'
        );


        // verify we can go the other direction
        $file   = P4_File::fetch('//depot/records/' . $record->getId());
        $record = P4Cms_Record_Implementation::fromP4File($file);

        $this->assertSame(
            'test-title',
            $record->getTitle(),
            'fromP4File expected matching title'
        );
        $this->assertSame(
            'test-content',
            $record->getContent(),
            'fromP4File expected matching content'
        );
    }

    /**
     * Test the relation of the record's adapter and the connection of the associated p4 file.
     */
    public function testAdapter()
    {
        // create adapter to test with
        $connection = P4_Connection::factory('port');
        $adapter    = new P4Cms_Record_Adapter;
        $adapter->setBasePath('/')
                ->setConnection($connection);

        $record = new P4Cms_Record(array('id' => 'foo'), $adapter);

        // ensure record's adapter and associated file have the same connection
        $this->assertSame(
            $connection,
            $record->toP4File()->getConnection(),
            "Expected connection of the p4 file."
        );

        // change the record's adapter and verify the connection of the file gets updated
        $newConnection = P4_Connection::factory('port1');
        $newAdapter    = new P4Cms_Record_Adapter;
        $newAdapter->setBasePath('/')
                   ->setConnection($newConnection);
        $record->setAdapter($newAdapter);

        $this->assertSame(
            $newConnection,
            $record->toP4File()->getConnection(),
            "Expected new connection of the p4 file."
        );
    }

    /**
     * Test retrieving previor revisions of a given file
     */
    public function testFetchRevision()
    {
        $record = new P4Cms_Record_Implementation;
        $record->setId(1)
               ->setTitle('test-title0')
               ->setContent('test-content0')
               ->save();

        $this->assertSame(
            '1',
            $record->getId(),
            'expected matching Id'
        );

        for ($i=1; $i <= 10; $i++) {
            $record->setTitle('test-title'.$i)
                   ->setContent('test-content'.$i)
                   ->save();
        }

        $this->assertSame(
            11,
            count($record->toP4File()->getChanges()),
            'expected matching number of changes'
        );

        for ($i=1; $i <= 11; $i++) {
            $record = P4Cms_Record_Implementation::fetch("1#$i");

            $this->assertSame(
                'test-title'.($i-1),
                $record->getTitle(),
                'expected matching title for revision '.$i
            );
        }
    }

    /**
     * Test id encoding facility.
     */
    public function testIdEncoding()
    {
        P4Cms_Record_Implementation::setEncodeIds(true);

        // try storing something with a zany id.
        $record = P4Cms_Record_Implementation::store(
            array(
                'id'    => '123#foo',
                'foo'   => 'bar'
            )
        );

        $file = $record->toP4File();
        $this->assertSame(
            bin2hex('123#foo'),
            $file->getBasename()
        );

        P4Cms_Record_Implementation::setEncodeIds(false);
    }

    /**
     * Test zombie attributes
     */
    public function testZombieValues()
    {
        $record = new P4Cms_Record_Implementation;
        $record->setValues(
            array('id' => 'one', 'title' => 'test', 'content' => 'test', 'order' => 3)
        );
        $record->save();
        $record->delete();

        $record = new P4Cms_Record_Implementation;
        $record->setValues(
            array('id' => 'one', 'content' => 'test2')
        );
        $record->save();

        $record = P4Cms_Record_Implementation::fetch('one');
        $this->assertSame(
            array('id' => 'one', 'title' => 'Record Title', 'content' => 'test2'),
            $record->getValues()
        );
    }
}
