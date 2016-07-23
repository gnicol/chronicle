<?php
/**
 * Test methods for the record class when used directly.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_GenericTest extends TestCase
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
        $record = new P4Cms_Record;
        $this->assertTrue($record instanceof P4Cms_Record, 'Expected object type');

        $this->assertSame(
            '//depot',
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
            'Expected null id in new object'
        );

        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'id'        => null,
                'expect'    => null,
            ),
            array(
                'label'     => __LINE__ .': numeric',
                'id'        => 3,
                'expect'    => '3',
            ),
            array(
                'label'     => __LINE__ .': int string',
                'id'        => '2',
                'expect'    => '2',
            )
        );
        foreach ($tests as $test) {
            $label = $test['label'];
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
                'label'     => __LINE__ .': backslash',
                'id'        => '\\'
            ),
            array(
                'label'     => __LINE__ .': asterix',
                'id'        => '*',
            ),
            array(
                'label'     => __LINE__ .': hash',
                'id'        => '#',
            )
        );
        foreach ($tests as $test) {
            $label = $test['label'];
            try {
                $record->setId($test['id']);
                $this->fail("$label - expected exception.");
            } catch (InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }
    }

    /**
     * Test ability to handle large record attributes.
     */
    public function testLargeRecordAttributes()
    {
        $id = 'test/large-attributes';
        $content = '0123456789abcdef';
        for ($count = 0; $count < 6; $count++) {
            $content = $content . $content . $content . $content;
        }
        $record = new P4Cms_Record;
        $record->setId($id)
               ->setValue('large', $content)
               ->save();

        $verify = P4Cms_Record::fetch($id);
        $this->assertSame($content, $verify->getValue('large'), 'expected title');
    }

    /**
     * Test record handling, ie: fetch(), fetchAll, save(), accessors/mutators, and delete().
     */
    public function testHandling()
    {
        // confirm that no records exist, yet.
        $records = P4Cms_Record::fetchAll();
        $this->assertSame(0, count($records), "P4Cms_Record: Expected fetchAll count with 0 records");

        // test a non-existant record
        $id = 1234567890;
        try {
            $record = P4Cms_Record::fetch($id);
            $this->fail("P4Cms_Record: Unexpected success fetching a non-existant record.");
        } catch (P4Cms_Record_NotFoundException $e) {
            $this->assertSame(
                "Cannot fetch record '$id'. Record does not exist.",
                $e->getMessage(),
                "P4Cms_Record: Expected exception fetching a non-existant record."
            );
        } catch (Exception $e) {
            $this->fail(
                "P4Cms_Record: Unexpected exception fetching a non-existant record: "
                . $e->getMessage()
            );
        }

        // make a new record
        $record = new P4Cms_Record;

        // modify and save the record
        $content = 'New content.';
        $record->setValue('content', $content);
        $this->assertSame($content, $record->getValue('content'), "P4Cms_Record: Expected new content");
        $record->setId('test/record')->save('Saving new content');
        $recordId = $record->getId();

        // fetch it again
        $record = P4Cms_Record::fetch($recordId);
        $this->assertSame($recordId, $record->getId(), "P4Cms_Record: Expected id");
        $this->assertSame($content, $record->getValue('content'), "P4Cms_Record: Expected content");

        // confirm that fetchAll retrieves the same record
        $records = P4Cms_Record::fetchAll();
        $this->assertSame(1, count($records), "P4Cms_Record: Expected fetchAll count with 1 records");
        $this->assertSame($recordId, $records->first()->getId(), "P4Cms_Record: Expected fetchAll #1 id");
        $this->assertSame(
            $content,
            $records->first()->getValue('content'),
            "P4Cms_Record: Expected fetchAll #1 content"
        );

        // make a second new record
        $record2  = new P4Cms_Record;
        $content2 = 'Content #2';
        $record2->setValue('content', $content2);
        $record2->setId('test/record2')->save('Saving second content.');
        $record2Id = $record2->getId();

        // fetch the second record and verify it
        $record2 = P4Cms_Record::fetch($record2Id);
        $this->assertSame($record2Id, $record2->getId(), "P4Cms_Record: Expected second record id");
        $this->assertSame(
            $content2,
            $record2->getValue('content'),
            "P4Cms_Record: Expected second record content"
        );

        // now make sure fetchAll retrieves them both
        $records = P4Cms_Record::fetchAll();
        $this->assertSame(
            2,
            count($records),
            "P4Cms_Record: Expected fetchAll count with 2 records"
        );

        $this->assertSame($recordId, $records->first()->getId(), "P4Cms_Record: Expected fetchAll #2 id #1");
        $this->assertSame(
            $content,
            $records->first()->getValue('content'),
            "P4Cms_Record: Expected fetchAll #2 content #1"
        );

        $this->assertSame($record2Id, $records->last()->getId(), "P4Cms_Record: Expected fetchAll #2 id #2");
        $this->assertSame(
            $content2,
            $records->last()->getValue('content'),
            "P4Cms_Record: Expected fetchAll #2 content #2"
        );

        // modify second record
        $content2 = 'Modified content #2';
        $record2->setValue('content', $content2);
        $record2->save('Update content #2');

        $record2 = P4Cms_Record::fetch($record2Id);
        $this->assertSame(
            $content2,
            $record2->getValue('content'),
            'P4Cms_Record: Expected second record modified content'
        );

        // test modification without description
        $content2 = 'Modified content #3';
        $record2->setValue('content', $content2);
        $record2->save();

        $record2 = P4Cms_Record::fetch($record2Id);
        $this->assertSame(
            $content2,
            $record2->getValue('content'),
            'P4Cms_Record: Expected second record modified content'
        );

        // delete the second record
        $record2->delete();
        try {
            $record = P4Cms_Record::fetch($record2Id);
            $this->fail("P4Cms_Record: Unexpected success fetching deleted record.");
        } catch (P4Cms_Record_NotFoundException $e) {
            $this->assertSame(
                "Cannot fetch record '$record2Id'. Record does not exist.",
                $e->getMessage(),
                "P4Cms_Record: Expected exception fetching deleted record."
            );
        } catch (Exception $e) {
            $this->fail(
                "P4Cms_Record: Unexpected exception fetching deleted record: "
                . $e->getMessage()
            );
        }

        // confirm that fetchAll retrieves the remaining record
        $records = P4Cms_Record::fetchAll();
        $this->assertSame(1, count($records), "P4Cms_Record: Expected fetchAll count with only 1 record");
        $this->assertSame($recordId, $records->first()->getId(), "P4Cms_Record: Expected fetchAll #3 id");
        $this->assertSame(
            $content,
            $records->first()->getValue('content'),
            "P4Cms_Record: Expected fetchAll #3 content"
        );

        // test deleting a record a second time
        try {
            $record2->delete('delete me again');
            $this->fail("P4Cms_Record: Unexpected success deleting record for second time.");
        } catch (P4_File_Exception $e) {
            $this->assertSame(
                "Failed to open file for delete: //depot/test/record2 - file(s) not on client.",
                $e->getMessage(),
                "P4Cms_Record: Expected error trying to delete a record a second time"
            );
        } catch (Exception $e) {
            $this->fail("P4Cms_Record: Unexpected exception deleting record for second time: ". $e->getMessage());
        }
    }

    /**
     * Test id exists method.
     */
    public function testIdExists()
    {
        $this->assertFalse(
            P4Cms_Record::exists(1),
            'Id should not exist (line: ' . __LINE__. ').'
        );
        $this->assertFalse(
            P4Cms_Record::exists(1, array('includeDeleted' => true)),
            'Id should not exist (line: ' . __LINE__. ').'
        );

        $record = new P4Cms_Record;
        $record->setId('one');
        $record->save();

        $this->assertTrue(
            P4Cms_Record::exists('one'),
            'Id should exist (line: ' . __LINE__. ').'
        );
        $this->assertTrue(
            P4Cms_Record::exists('one', array('includeDeleted' => true)),
            'Id should exist (line: ' . __LINE__. ').'
        );

        $record->delete();

        $this->assertFalse(
            P4Cms_Record::exists('one'),
            'Id should not exist (line: ' . __LINE__. ').'
        );
        $this->assertTrue(
            P4Cms_Record::exists('one', array('includeDeleted' => true)),
            'Id should exist (line: ' . __LINE__. ').'
        );
    }

    /**
     * Test field metadata capability.
     */
    public function testFieldMetadata()
    {
        $record = new P4Cms_Record;
        $record->setId('one')
               ->setValue('title', 'test-title')
               ->setValue('content', 'test-content')
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
            "Expected no metadata for content field."
        );

        // ensure in-memory metadata works.
        $testData = array('test-title-key' => 'test-title-metadata');
        $record->setFieldMetadata('title', $testData);
        $this->assertSame(
            $testData,
            $record->getFieldMetadata('title'),
            "Title field metadata did not match before save."
        );

        // ensure metadata can be saved.
        $record->save();
        $this->assertSame(
            $testData,
            $record->getFieldMetadata('title'),
            "Title field metadata did not match after save."
        );

        // ensure saved metadata can be retrieved.
        $record = P4Cms_Record::fetch($record->getId());
        $this->assertSame(
            $testData,
            $record->getFieldMetadata('title'),
            "Title field metadata did not match after retrieval."
        );
    }

    /**
     * Test fetchAll with case-matching filters.
     */
    public function testFetchAllCaseMatching()
    {
        $record = new P4Cms_Record;
        $record->setId('one')->setValue('field', 'test')->save('one');
        $record->setId('two')->setValue('field', 'Test')->save('two');
        $record->setId('three')->setValue('field', 'teSt')->save('three');

        $records = P4Cms_Record::fetchAll();
        $this->assertEquals(3, count($records), 'Expected 3 records after creation.');

        $query = P4Cms_Record_Query::create(
            array('filter' => P4Cms_Record_Filter::create()->add('field', 'test'))
        );
        $records = P4Cms_Record::fetchAll($query);
        $this->assertEquals(1, count($records), 'Expected 1 record for "test"');
        $this->assertEquals('one', $records->first()->getId(), 'Expected id for "test"');

        $query = P4Cms_Record_Query::create(
            array('filter' => P4Cms_Record_Filter::create()->add('field', 'Test'))
        );
        $records = P4Cms_Record::fetchAll($query);
        $this->assertEquals(1, count($records), 'Expected 1 record for "Test"');
        $this->assertEquals('two', $records->first()->getId(), 'Expected id for "Test"');

        $query = P4Cms_Record_Query::create(
            array(
                'filter' => P4Cms_Record_Filter::create()->add(
                    'field', 'test', P4Cms_Record_Filter::COMPARE_REGEX
                )
            )
        );
        $records = P4Cms_Record::fetchAll($query);
        $this->assertEquals(1, count($records), 'Expected 1 records for "~test"');

        $query = P4Cms_Record_Query::create(
            array(
                'filter' => P4Cms_Record_Filter::create()->add(
                    'field', 'test', P4Cms_Record_Filter::COMPARE_REGEX, null, true
                )
            )
        );
        $records = P4Cms_Record::fetchAll($query);
        $this->assertEquals(3, count($records), 'Expected 3 records for "~test"');
    }

    /**
     * Test fetchAll with null attributes.
     */
    public function testFetchAllWithNullAttributes()
    {
        $record = new P4Cms_Record;
        $record->setId('one')->setValue('field', 'test')->save('one');
        $record->setId('two')->setValue('field', '')->save('two');
        $record->setId('three')->setValue('field', null)->save('two');

        $records = P4Cms_Record::fetchAll();
        $this->assertSame(3, count($records), 'Expected 3 records after creation.');

        $query = P4Cms_Record_Query::create(
            array('filter' => P4Cms_Record_Filter::create()->add('field', ''))
        );
        $records = P4Cms_Record::fetchAll($query);
        $this->assertEquals(2, count($records), 'Expected 2 records with empty string');

        $query = P4Cms_Record_Query::create(
            array('filter' => P4Cms_Record_Filter::create()->add('field', null))
        );
        $records = P4Cms_Record::fetchAll($query);
        $this->assertEquals(2, count($records), 'Expected 2 records with null');
    }

    /**
     * Test fetchAll with various kinds of matching.
     */
    public function testFetchAllFilterVariations()
    {
        $variations = array(
            'bare'              => 'test',
            'caps'              => 'TesT',
            'caret'             => 'tes^t',
            'curlies'           => 't{e}st',
            'dollar'            => 't$est',
            'leadAsterisk'      => '*test',
            'leadCurly'         => '{test',
            'leadDots'          => '...test',
            'leadleadAsterisk'  => '*test*',
            'leadtrailDots'     => '...test...',
            'newline'           => "te\nst",
            'no-e'              => 'tst',
            'pipe'              => 'te|st',
            'question'          => 'te?st',
            'repeats'           => 'teessstttt',
            'return'            => "te\rst",
            'rounds'            => 't(e)st',
            'squares'           => 'te[s]t',
            'trailAsterisk'     => 'test*',
            'trailCurly'        => 'test}',
            'trailDots'         => 'test...',
        );

        $tests = array(
            array(
                'label'     => __LINE__ .': no filter',
                'filter'    => null,
                'expected'  => array_values($variations),
            ),
        );

        // setup test records and compose direct-match tests
        $record = new P4Cms_Record;
        foreach ($variations as $id => $value) {
            $record->setId($id)->setValue('field', $value)->save($id);

            $tests[] = array(
                'label'     => __LINE__ .": = $value ($id)",
                'filter'    => P4Cms_Record_Filter::create()->add('field', $value),
                'expected'  => array($value),
            );
        }

        // append the regex tests
        $tests = array_merge(
            $tests,
            array(
                array(
                    'label'     => __LINE__ .': ~ te?st',
                    'filter'    => P4Cms_Record_Filter::create()
                                   ->add('field', '^te?st$', P4Cms_Record_Filter::COMPARE_REGEX),
                    'expected'  => array('test', 'tst'),
                ),
                array(
                    'label'     => __LINE__ .': ~ t.*t',
                    'filter'    => P4Cms_Record_Filter::create()
                                   ->add('field', '^t.*t$', P4Cms_Record_Filter::COMPARE_REGEX, null, false),
                    'expected'  => array(
                        'test', 'tes^t', 't{e}st', 't$est', "te\nst", 'tst', 'te|st',
                        'te?st', 'teessstttt', "te\rst", 't(e)st', 'te[s]t',
                    ),
                ),
                array(
                    'label'     => __LINE__ .': ~ (T|t)es(T|t)',
                    'filter'    => P4Cms_Record_Filter::create()
                                   ->add('field', '^(T|t)es(T|t)$', P4Cms_Record_Filter::COMPARE_REGEX, null, false),
                    'expected'  => array('test', 'TesT'),
                ),
                array(
                    'label'     => __LINE__ .': ~ ...',
                    'filter'    => P4Cms_Record_Filter::create()
                                   ->add('field', '^...$', P4Cms_Record_Filter::COMPARE_REGEX),
                    'expected'  => array('tst'),
                ),
                array(
                    'label'     => __LINE__ .': ~ t[es].*t case',
                    'filter'    => P4Cms_Record_Filter::create()
                                   ->add('field', '^t[es].*t$', P4Cms_Record_Filter::COMPARE_REGEX, null, false),
                    'expected'  => array(
                        'test', 'tes^t', "te\nst", 'tst', 'te|st', 'te?st',
                        'teessstttt', "te\rst", 'te[s]t'
                    ),
                ),
                array(
                    'label'     => __LINE__ .': ~ t[es].*t nocase',
                    'filter'    => P4Cms_Record_Filter::create()
                                   ->add('field', '^t[es].*t$', P4Cms_Record_Filter::COMPARE_REGEX, null, true),
                    'expected'  => array(
                        'test', 'TesT', 'tes^t', "te\nst", 'tst', 'te|st', 'te?st',
                        'teessstttt', "te\rst", 'te[s]t'
                    ),
                ),
                array(
                    // p4d's regex parser does not support square brackets in character classes.
                    'label'     => __LINE__ .': ~ t[es\\[\\]]+t nocase',
                    'filter'    => P4Cms_Record_Filter::create()
                                   ->add('field', '^t[es\\[\\]]+t$', P4Cms_Record_Filter::COMPARE_REGEX),
                    'expected'  => array(),
                ),
                array(
                    // p4d's regex parser does not support ranged quantifiers.
                    'label'     => __LINE__ .': ~ te{2}st',
                    'filter'    => P4Cms_Record_Filter::create()
                                   ->add('field', '^te{2}st$', P4Cms_Record_Filter::COMPARE_REGEX),
                    'expected'  => array(),
                ),
                array(
                    'label'     => __LINE__ .': ~ contains "es"',
                    'filter'    => P4Cms_Record_Filter::create()
                                   ->add('field', 'es', P4Cms_Record_Filter::COMPARE_CONTAINS),
                    'expected'  => array(
                        'test', 'TesT', 'tes^t', 't$est', '*test', '{test', '...test',
                        '*test*', '...test...', 'teessstttt', 'test*', 'test}', 'test...'
                    ),
                ),
                array(
                    'label'     => __LINE__ .': ~ does not contain "e"',
                    'filter'    => P4Cms_Record_Filter::create()
                                   ->add('field', 'e', P4Cms_Record_Filter::COMPARE_NOT_CONTAINS),
                    'expected'  => array('tst')
                ),
                array(
                    'label'     => __LINE__ .': ~ contains "st" case-insensitive',
                    'filter'    => P4Cms_Record_Filter::create()
                                   ->add('field', 'st', P4Cms_Record_Filter::COMPARE_CONTAINS, null, true),
                    'expected'  => array(
                        'test', 'TesT', 't{e}st', 't$est', '*test', '{test', '...test',
                        '*test*', '...test...', "te\nst", 'tst', 'te|st', 'te?st',
                        'teessstttt', "te\rst", 't(e)st', 'test*', 'test}', 'test...'
                    ),
                ),
            )
        );

        foreach ($tests as $test) {
            $label = $test['label'];

            $filter = $test['filter'];
            $query = P4Cms_Record_Query::create(array('filter' => $filter));
            $records = P4Cms_Record::fetchAll($query);
            $values = $this->_collectRecordValues($records, 'field');
            $this->assertEquals($test['expected'], $values, "$label - Expected results, using filter: $filter");
        }
    }

    /**
     * Collect a list of values from a set of record objects.
     *
     * @param  array|iterator  $records  The list of records to collect values from.
     * @param  string          $field    The field within records from which to collect values.
     * @return  array  The list of requested values from the set of provided records.
     */
    private function _collectRecordValues($records, $field)
    {
        $values = array();
        foreach ($records as $record) {
            $values[] .= $record->getValue($field);
        }

        return $values;
    }

    /**
     * Test the fetch|count by path option.
     */
    public function testFetchAllAndCountByPath()
    {
        // create some records.
        $record = new P4Cms_Record;
        $record->setId('foo/one')->save();
        $record->setId('foo/two')->save();
        $record->setId('bar/one')->save();
        $record->setId('bar/two')->save();
        $record->setId('foo/bar/one')->save();
        $record->setId('foo/bar/two')->save();

        // test w.out fetch by path.
        $records = P4Cms_Record::fetchAll();
        $this->assertSame(6, count($records), 'Expected 6 records.');

        $count = P4Cms_Record::count();
        $this->assertSame(6, $count, 'Expected a count of 6 records.');

        // test w. various paths.
        $tests = array(
            array(
                'query' => P4Cms_Record_Query::create()->addPath('...'),
                'count' => 6
            ),
            array(
                'query' => P4Cms_Record_Query::create(),
                'count' => 6
            ),
            array(
                'query' => P4Cms_Record_Query::create()->addPath('foo/...'),
                'count' => 4
            ),
            array(
                'query' => P4Cms_Record_Query::create()->addPath('bar/*'),
                'count' => 2
            ),
            array(
                'query' => P4Cms_Record_Query::create()->addPath('foo/*'),
                'count' => 2
            ),
            array(
                'query' => P4Cms_Record_Query::create()->addPath('...foo/...'),
                'count' => 4
            ),
            array(
                'query' => P4Cms_Record_Query::create()->addPath('...two'),
                'count' => 3
            ),
            array(
                'query' => P4Cms_Record_Query::create()->addPaths(array('foo/*', 'bar/*')),
                'count' => 4
            )
        );

        foreach ($tests as $try => $test) {
            $records = P4Cms_Record::fetchAll($test['query']);
            $this->assertSame($test['count'], count($records), 'Expected '. $test['count'] .' records on try: ' . $try);
            $count = P4Cms_Record::count($test['query']);
            $this->assertSame($test['count'], $count, 'Expected count of '. $test['count'] .' records on try: ' . $try);
        }
    }

    /**
     * Test the fetch|count max depth option.
     */
    public function testFetchAllAndCountMaxDepth()
    {
        // create some records.
        $record = new P4Cms_Record;
        $record->setId('one')->save();
        $record->setId('foo/one')->save();
        $record->setId('foo/bar/one')->save();
        $record->setId('foo/bar/baz/one')->save();
        $record->setId('foo/baz/fee/two')->save();
        $record->setId('foo/bar/baz/bof/one')->save();
        $record->setId('two')->save();
        $record->setId('bar/two')->save();
        $record->setId('bar/bof/two')->save();

        // test w.out max depth.
        $records = P4Cms_Record::fetchAll();
        $this->assertSame(9, count($records), 'Expected 9 records.');
        $count = P4Cms_Record::count();
        $this->assertSame(9, $count, 'Expected 9 records.');

        // test w. various depths.
        $tests = array(
            array(
                'depth'   => -1,
                'invalid' => true
            ),
            array(
                'depth' => 0,
                'count' => 2
            ),
            array(
                'depth' => 1,
                'count' => 4
            ),
            array(
                'depth' => 2,
                'count' => 6
            ),
            array(
                'depth' => 3,
                'count' => 8
            ),
            array(
                'depth' => 4,
                'count' => 9
            ),
            array(
                'depth' => 5,
                'count' => 9
            )
        );

        foreach ($tests as $test) {
            try {
                $query = P4Cms_Record_Query::create()->setMaxDepth($test['depth']);
                $records = P4Cms_Record::fetchAll($query);
                if (isset($test['invalid'])) {
                    $this->fail('Expected invalid argument exception');
                }

                $this->assertSame($test['count'], count($records), 'Expected '. $test['count'] .' records.');

                $count = P4Cms_Record::count($query);
                $this->assertSame($test['count'], $count, 'Expected count of '. $test['count'] .'records.');
            } catch (InvalidArgumentException $e) {
                if (isset($test['invalid'])) {
                    $this->assertTrue(true);
                } else {
                    throw $e;
                }
            }
        }
    }

   /**
     * Test the fetch|count by path option.
     */
    public function testFetchPaginated()
    {
        // create some records.
        $record = new P4Cms_Record;
        $record->setId('foo/one')->save();
        $record->setId('foo/two')->save();
        $record->setId('bar/one')->save();
        $record->setId('bar/two')->save();
        $record->setId('foo/bar/one')->save();
        $record->setId('foo/bar/two')->save();

        $query = new P4Cms_Record_Query;
        $query->setStartRow(0)
              ->setMaxRows(2);

        $records = P4Cms_Record::fetchAll($query);
        $this->assertSame(2, count($records));
        $this->assertSame(
            array('bar/one', 'bar/two'),
            $records->invoke('getId')
        );

        $query->setStartRow(2);
        $records = P4Cms_Record::fetchAll($query);
        $this->assertSame(2, count($records));
        $this->assertSame(
            array('foo/bar/one', 'foo/bar/two'),
            $records->invoke('getId')
        );

        $query->setStartRow(4)
              ->setMaxRows(4);
        $records = P4Cms_Record::fetchAll($query);
        $this->assertSame(2, count($records));
        $this->assertSame(
            array('foo/one', 'foo/two'),
            $records->invoke('getId')
        );

        $query->setStartRow(5);
        $records = P4Cms_Record::fetchAll($query);
        $this->assertSame(1, count($records));
        $this->assertSame(
            array('foo/two'),
            $records->invoke('getId')
        );
    }

    /**
     * Test creating a batch.
     */
    public function testBeginBatch()
    {
        // verify not in a batch.
        $adapter = P4Cms_Record::getDefaultAdapter();
        $this->assertFalse($adapter->inBatch());

        // start a batch.
        $adapter->beginBatch('test batch');
        $this->assertTrue($adapter->inBatch());
        $this->assertSame(1, $adapter->getBatchId(), "Expected batch id to be first change.");

        // verify desc.
        $this->assertSame(
            "test batch\n",
            P4_Change::fetch(1)->getDescription(),
            "Expected same description as passed to begin batch."
        );
    }

    /**
     * Test committing a batch.
     */
    public function testCommitBatch()
    {
        // start a batch.
        $adapter = P4Cms_Record::getDefaultAdapter();
        $adapter->beginBatch('test batch');

        // create some records.
        $record = new P4Cms_Record;
        $record->setId('foo/one')->save();
        $record->setId('foo/two')->save();
        $record->setId('bar/one')->save();
        $record->setId('bar/two')->save();
        $record->setId('foo/bar/one')->save();
        $record->setId('foo/bar/two')->save();

        // verify no submitted changes yet.
        $changes = P4_Change::fetchAll(array(P4_Change::FETCH_BY_STATUS => 'submitted'));
        $this->assertTrue(count($changes) == 0);

        // commit batch.
        $adapter->commitBatch('test commit');

        // verify one submitted change.
        $changes = P4_Change::fetchAll(array(P4_Change::FETCH_BY_STATUS => 'submitted'));
        $this->assertTrue(count($changes) == 1);

        // verify desc.
        $this->assertSame(
            "test commit\n",
            P4_Change::fetch(1)->getDescription(),
            "Expected same description as passed to commit batch."
        );

        // ensure all records were submitted.
        $this->assertSame(
            6,
            P4Cms_Record::fetchAll()->count(),
            "Expected 6 records in one change."
        );

        // start a new batch.
        $adapter = P4Cms_Record::getDefaultAdapter();
        $adapter->beginBatch('test delete');

        // delete some records.
        $record = new P4Cms_Record;
        $record->setId('foo/one')->delete();
        $record->setId('foo/two')->delete();
        $record->setId('bar/one')->delete();

        // verify no new submitted changes.
        $changes = P4_Change::fetchAll(array(P4_Change::FETCH_BY_STATUS => 'submitted'));
        $this->assertTrue(count($changes) == 1);

        // commit batch.
        $adapter->commitBatch();

        // ensure deletes were committed.
        $this->assertSame(
            3,
            P4Cms_Record::fetchAll()->count(),
            "Expected 3 records remaining."
        );

        // verify not in batch.
        $this->assertFalse($adapter->inBatch());
    }

    /**
     * Test reverting a batch.
     */
    public function testRevertBatch()
    {
        // start a batch.
        $adapter = P4Cms_Record::getDefaultAdapter();
        $adapter->beginBatch('test batch');

        // create some records.
        $record = new P4Cms_Record;
        $record->setId('foo/one')->save();
        $record->setId('foo/two')->save();
        $record->setId('bar/one')->save();

        // verify records open in pending change.
        $change = P4_Change::fetch($adapter->getBatchId());
        $this->assertSame(3, count($change->getFiles()), "Expected three files in change.");

        // revert batch.
        $id = $adapter->getBatchId();
        $adapter->revertBatch();

        // verify change gone.
        $this->assertFalse(P4_Change::exists($id));

        // verify not in batch.
        $this->assertFalse($adapter->inBatch());

        // verify no open files.
        $query = P4_File_Query::create()
                 ->addFilespec('//...')
                 ->setLimitToOpened(true);
        $this->assertSame(
            0,
            P4_File::fetchAll($query)->count(),
            "Expected no open files."
        );
    }

    /**
     * Test commit of empty batch.
     */
    public function testCommitEmptyBatch()
    {
        $adapter = P4Cms_Record::getDefaultAdapter();
        $adapter->beginBatch('test batch');

        try {
            $adapter->commitBatch();
            $this->assertTrue(true);
        } catch (Exception $e) {
            throw $e;
            $this->fail("Unexpected exception committing empty batch.");
        }
    }

    /**
     * Test mixed save/delete of records inside batch.
     */
    public function testSaveDeleteInBatch()
    {
        // save a couple of records outside batch.
        $record = new P4Cms_Record;
        $record->setId('one')->save();
        $record->setId('two')->save();

        $adapter = P4Cms_Record::getDefaultAdapter();
        $adapter->beginBatch('test batch');

        // save existing record (edit).
        $record = new P4Cms_Record;
        $record->setId('one')->save();

        // now attempt delete of same.
        try {
            $record->delete();
            $this->assertTrue(true);
        } catch (Exception $e) {
            throw $e;
            $this->fail("Unexpected exception.");
        }

        // delete existing record.
        $record = new P4Cms_Record;
        $record->setId('two')->delete();

        // now attempt save of same.
        try {
            $record->save();
            $this->assertTrue(true);
        } catch (Exception $e) {
            throw $e;
            $this->fail("Unexpected exception.");
        }

        // save a new record (add).
        $record = new P4Cms_Record;
        $record->setId('three')->save();

        // now attempt delete of same.
        try {
            $record->delete();
            $this->assertTrue(true);
        } catch (Exception $e) {
            throw $e;
            $this->fail("Unexpected exception.");
        }

        // commit batch.
        $adapter->commitBatch();

        // record 1 and 3 should not exist.
        $this->assertFalse(P4Cms_Record::exists('one'));
        $this->assertFalse(P4Cms_Record::exists('three'));

        // record 2 should exist.
        $this->assertTrue(P4Cms_Record::exists('two'));
    }

    /**
     * Test the static store method.
     */
    public function testStoreAndRemove()
    {
        P4Cms_Record::store(array('id' => 'one', 'foo' => 'bar'));
        P4Cms_Record::store(array('id' => 'two', 'zig' => 'zag'));

        $this->assertSame(
            2,
            P4Cms_Record::fetchAll()->count(),
            "Expected two records in storage."
        );

        $one = P4Cms_Record::fetch('one');
        $two = P4Cms_Record::fetch('two');

        $this->assertSame(
            array('id' => 'one', 'foo' => 'bar'),
            $one->getValues()
        );

        $this->assertSame(
            array('id' => 'two', 'zig' => 'zag'),
            $two->getValues()
        );

        // ensure store updates existing records.
        $record = P4Cms_Record::store('one');
        $this->assertTrue($record instanceof P4Cms_Record);

        P4Cms_Record::store(array('id' => 'two', 'bob' => 'a person'));
        $record = P4Cms_Record::fetch('two');
        $this->assertTrue($record instanceof P4Cms_Record);
        $this->assertSame(
            array('id' => 'two', 'bob' => 'a person'),
            $record->getValues()
        );

        P4Cms_Record::remove('one');
        $this->assertSame(
            1,
            P4Cms_Record::fetchAll()->count(),
            "Expected one record in storage."
        );
    }

    /**
     * Test auto-id generation.
     */
    public function testAutoId()
    {
        // ensure create with no id produces a new UUID'd record.
        $uuid   = new P4Cms_Uuid;
        $record = P4Cms_Record::store();
        $this->assertTrue($uuid->isValid($record->getId()));
        $this->assertTrue($record->exists($record->getId()));
    }

    /**
     * Test rollback with delete @ head.
     */
    public function testRollbackWithHeadDeleted()
    {
        $record = P4Cms_Record::store(
            array('id' => 1, 'foo' => 'bar')
        );

        $record->delete();

        $record = P4Cms_Record::fetch('1#1')->save();
    }

    /**
     * Test setting values from a form.
     */
    public function testSetValuesFromForm()
    {
        $form = new Zend_Form;
        $form->addElement('text', 'foo')
             ->addElement('text', 'bar')
             ->addElement('text', 'baz');

        $record = new P4Cms_Record;
        $record->setValues($form);

        $this->assertSame(
            $record->getValues(),
            array('id' => null) + $form->getValues()
        );
    }

    /**
     * Test setting values from a form w. record enhanced element.
     */
    public function testSetValuesFromFormEnhanced()
    {
        $form    = new Zend_Form;
        $element = new P4Cms_Record_EnhancedElement('foo');
        $form->addElement($element)
             ->addElement('text',   'bar')
             ->addElement('text',   'baz');

        $record = new P4Cms_Record;
        $record->setValues($form);

        $this->assertSame(
            $record->getValues(),
            array('id' => null) + $form->getValues()
        );

        $this->assertSame($record->getFieldMetadata('foo'), array('test'));
    }

    /**
     * Test opposite of above - populating element from a record.
     */
    public function testPopulateFormEnhanced()
    {
        $form    = new P4Cms_Form;
        $element = new P4Cms_Record_EnhancedElement('foo');
        $form->addElement($element)
             ->addElement('text',   'bar')
             ->addElement('text',   'baz');

        $record = new P4Cms_Record;
        $record->setValues(array('foo' => 1, 'bar' => 2, 'baz' => 3));

        $this->assertNull($element->getAttrib('test'));
        $form->populate($record);
        $this->assertSame(
            array('id' => null) + $form->getValues(),
            $record->getValues()
        );
        $this->assertTrue($element->getAttrib('test'));
    }
}
