<?php
/**
 * Test methods for the P4Cms File Query class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_QueryTest extends TestCase
{
    /**
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();

        $adapter = new P4Cms_Record_Adapter;
        $adapter->setBasePath('//depot')
                ->setConnection($this->p4);
        P4Cms_Record::setDefaultAdapter($adapter);
    }

    /**
     * Test teardown.
     */
    public function tearDown()
    {
        P4Cms_Record::clearDefaultAdapter();

        parent::tearDown();
    }

    /**
     * Provide test array runner, as most of the included test methods
     * require almost identical infrastructure for checking success/failure.
     *
     * @param  array  $tests  The array of tests to run.
     * @param  array  $method The P4Cms_Record_Query method suffix, after 'get' and 'set'.
     */
    protected function _runTests($tests, $method)
    {
        $setMethod = "set$method";
        $getMethod = "get$method";
        foreach ($tests as $test) {
            $label = $test['label'];
            $query = new P4Cms_Record_Query;
            try {
                $query->$setMethod($test['argument']);
                if (isset($test['addFilter'])) {
                    foreach ((array) $test['addFilter'] as $filter) {
                        $query->addFilter($filter);
                    }
                }

                if ($test['error']) {
                    $this->fail("$label - unexpected success");
                } else {
                    $this->assertEquals(
                        $test['expected'],
                        $query->$getMethod(),
                        "$label - expected value"
                    );
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (PHPUnit_Framework_ExpectationFailedError $e) {
                $this->fail($e->getMessage());
            } catch (Exception $e) {
                if (!$test['error']) {
                    $this->fail("$label - Unexpected exception (". get_class($e) .') :'. $e->getMessage());
                } else {
                    list($class, $error) = each($test['error']);
                    $this->assertEquals(
                        $class,
                        get_class($e),
                        "$label - expected exception class: ". $e->getMessage()
                    );
                    $this->assertEquals(
                        $error,
                        $e->getMessage(),
                        "$label - expected exception message"
                    );
                }
            }
        }
    }

    /**
     * Test the out-of-the-box query behaviour.
     */
    public function testInitialConditions()
    {
        $query = new P4Cms_Record_Query;
        $this->assertTrue($query instanceof P4Cms_Record_Query, 'Expected class.');
        $array = $query->toArray();
        $this->assertEquals(
            array(
                P4_File_Query::QUERY_FILTER                  => null,
                P4_File_Query::QUERY_SORT_BY                 => null,
                P4_File_Query::QUERY_SORT_REVERSE            => false,
                P4_File_Query::QUERY_LIMIT_FIELDS            => null,
                P4_File_Query::QUERY_LIMIT_TO_CHANGELIST     => null,
                P4_File_Query::QUERY_LIMIT_TO_NEEDS_RESOLVE  => false,
                P4_File_Query::QUERY_LIMIT_TO_OPENED         => false,
                P4_File_Query::QUERY_START_ROW               => null,
                P4Cms_Record_Query::QUERY_MAX_ROWS           => null,
                P4Cms_Record_Query::QUERY_INCLUDE_DELETED    => false,
                P4Cms_Record_Query::QUERY_MAX_DEPTH          => null,
                P4Cms_Record_Query::QUERY_PATHS              => null,
                P4Cms_Record_Query::QUERY_IDS                => null,
                P4Cms_Record_Query::QUERY_RECORD_CLASS       => null,
            ),
            $query->toArray(),
            'Expected options from constructor as array'
        );

        $query = P4Cms_Record_Query::create();
        $this->assertTrue($query instanceof P4Cms_Record_Query, 'Expected class.');
        $array = $query->toArray();
        $this->assertEquals(
            array(
                P4_File_Query::QUERY_FILTER                  => null,
                P4_File_Query::QUERY_SORT_BY                 => null,
                P4_File_Query::QUERY_SORT_REVERSE            => false,
                P4_File_Query::QUERY_LIMIT_FIELDS            => null,
                P4_File_Query::QUERY_LIMIT_TO_CHANGELIST     => null,
                P4_File_Query::QUERY_LIMIT_TO_NEEDS_RESOLVE  => false,
                P4_File_Query::QUERY_LIMIT_TO_OPENED         => false,
                P4_File_Query::QUERY_START_ROW               => null,
                P4Cms_Record_Query::QUERY_MAX_ROWS           => null,
                P4Cms_Record_Query::QUERY_INCLUDE_DELETED    => false,
                P4Cms_Record_Query::QUERY_MAX_DEPTH          => null,
                P4Cms_Record_Query::QUERY_PATHS              => null,
                P4Cms_Record_Query::QUERY_IDS                => null,
                P4Cms_Record_Query::QUERY_RECORD_CLASS       => null
            ),
            $query->toArray(),
            'Expected options from create as array'
        );
    }

    /**
     * Test passing options to the query constructors.
     */
    public function testConstructorOptions()
    {
        $badOptions = array(
            P4_File_Query::QUERY_FILTER                  => -1,
            P4_File_Query::QUERY_SORT_BY                 => -1,
            P4_File_Query::QUERY_SORT_REVERSE            => -1,
            P4_File_Query::QUERY_LIMIT_FIELDS            => -1,
            P4_File_Query::QUERY_LIMIT_TO_CHANGELIST     => -1,
            P4_File_Query::QUERY_LIMIT_TO_NEEDS_RESOLVE  => -1,
            P4_File_Query::QUERY_LIMIT_TO_OPENED         => -1,
            P4_File_Query::QUERY_START_ROW               => -1,
            P4Cms_Record_Query::QUERY_MAX_ROWS           => -1,
            P4Cms_Record_Query::QUERY_INCLUDE_DELETED    => -1,
            P4Cms_Record_Query::QUERY_MAX_DEPTH          => -1,
            P4Cms_Record_Query::QUERY_PATHS              => array('filespec'),
            P4Cms_Record_Query::QUERY_IDS                => -1,
        );

        try {
            $query = new P4Cms_Record_Query($badOptions);
            $this->fail('Unexpected success with bad options.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(
                'Cannot set filter; argument must be a P4_File_Filter, a string, or null.',
                $e->getMessage(),
                'Expected exception'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected exception ('. get_class($e) .') :'. $e->getMessage());
        }

        $goodOptions = array(
            P4_File_Query::QUERY_FILTER                  => new P4_File_Filter('filter'),
            P4_File_Query::QUERY_SORT_BY                 => 'column',
            P4_File_Query::QUERY_SORT_REVERSE            => true,
            P4_File_Query::QUERY_LIMIT_FIELDS            => array('d'),
            P4_File_Query::QUERY_LIMIT_TO_CHANGELIST     => 5,
            P4_File_Query::QUERY_LIMIT_TO_NEEDS_RESOLVE  => true,
            P4_File_Query::QUERY_LIMIT_TO_OPENED         => true,
            P4_File_Query::QUERY_START_ROW               => 5,
            P4Cms_Record_Query::QUERY_MAX_ROWS           => 10,
            P4Cms_Record_Query::QUERY_INCLUDE_DELETED    => true,
            P4Cms_Record_Query::QUERY_MAX_DEPTH          => 3,
            P4Cms_Record_Query::QUERY_PATHS              => array('filespec'),
            P4Cms_Record_Query::QUERY_IDS                => array('1', '2'),
            P4Cms_Record_Query::QUERY_RECORD_CLASS       => null
        );
        $query = new P4Cms_Record_Query($goodOptions);
        $expected = $goodOptions;
        $expected[P4_File_Query::QUERY_SORT_BY] = array('column' => null);
        $this->assertEquals(
            $expected,
            $query->toArray(),
            'Expected options'
        );

        $query = P4Cms_Record_Query::create($goodOptions);
        $this->assertEquals(
            $expected,
            $query->toArray(),
            'Expected options'
        );
    }

    /**
     * Test get/set a query's Filter attribute.
     */
    public function testGetSetFilter()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'argument'  => null,
                'error'     => null,
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': bool',
                'argument'  => true,
                'error'     => array(
                    'InvalidArgumentException' => 
                        'Cannot set filter; argument must be a P4Cms_Record_Filter, an array, a string, or null.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': numeric',
                'argument'  => 1,
                'error'     => array(
                    'InvalidArgumentException' => 
                        'Cannot set filter; argument must be a P4Cms_Record_Filter, an array, a string, or null.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': string',
                'argument'  => 'foobar',
                'error'     => null,
                'expected'  => 'foobar'
            ),
            array(
                'label'     => __LINE__ .': array',
                'argument'  => array('fields' => array('foobar' => '')),
                'error'     => null,
                'expected'  => P4Cms_Record_Filter::create(array('fields' => array('foobar' => '')))->getExpression()
            ),
            array(
                'label'     => __LINE__ .': some object',
                'argument'  => new stdClass,
                'error'     => array(
                    'InvalidArgumentException' => 
                        'Cannot set filter; argument must be a P4Cms_Record_Filter, an array, a string, or null.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': P4Cms_Record_Filter',
                'argument'  => P4Cms_Record_Filter::create(),
                'error'     => null,
                'expected'  => '',
            ),
            array(
                'label'     => __LINE__ .': P4Cms_Record_Filter',
                'argument'  => P4Cms_Record_Filter::create()->add('field', 'value'),
                'error'     => null,
                'expected'  => 'attr-field~=\\^value\\$',
            ),
            array(
                'label'     => __LINE__ .': P4Cms_Record_Filter',
                'argument'  => P4Cms_Record_Filter::create()->add('field', 'value'),
                'addFilter' => array(
                    new P4Cms_Record_Filter
                ),
                'error'     => null,
                'expected'  => 'attr-field~=\\^value\\$',
            ),
            array(
                'label'     => __LINE__ .': P4Cms_Record_Filter',
                'argument'  => new P4Cms_Record_Filter,
                'addFilter' => array(
                    P4Cms_Record_Filter::create()->add('field', 'value')
                ),
                'error'     => null,
                'expected'  => '(attr-field~=\\^value\\$)',
            ),
            array(
                'label'     => __LINE__ .': P4Cms_Record_Filter',
                'argument'  => P4Cms_Record_Filter::create()->add('field', 'value'),
                'addFilter' => array(
                    new P4Cms_Record_Filter,
                    P4Cms_Record_Filter::create()->add('field2', 'value2')
                ),
                'error'     => null,
                'expected'  => 'attr-field~=\^value\$ & (attr-field2~=\^value2\$)'
            )
        );
        
        // This block is needed here because _runTests does not support non P4Cms_Record_Filter
        // objects for the argument.
        foreach ($tests as $test) {
            $label = $test['label'];
            $query = new P4Cms_Record_Query;
            try {
                $query->setFilter($test['argument']);
                if (isset($test['addFilter'])) {
                    foreach ((array) $test['addFilter'] as $filter) {
                        $query->addFilter($filter);
                    }
                }

                $expr = $query->getFilter() instanceof P4Cms_Record_Filter 
                      ? $query->getFilter()->getExpression() 
                      : $query->getFilter();
                $this->assertEquals(
                    $test['expected'],
                    $expr,
                    "$label - expected value"
                );
            } catch (Exception $e) {
                if ($test['error']) {
                    $this->assertEquals(
                        'Cannot set filter; argument must be a P4Cms_Record_Filter, an array, a string, or null.',
                        $test['error']['InvalidArgumentException'],
                        "$label - expected InvalidArgumentException"
                    );
                } else {
                    $this->fail($e->getMessage());
                }
            }
        }
    }

    /**
     * Test get/set a query's SortBy attribute.
     */
    public function testGetSetSortBy()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'argument'  => null,
                'error'     => null,
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': bool',
                'argument'  => true,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set sort by; argument must be an array, string, or null.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': numeric',
                'argument'  => 1,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set sort by; argument must be an array, string, or null.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': string',
                'argument'  => 'foobar',
                'error'     => null,
                'expected'  => array('foobar' => null),
            ),
            array(
                'label'     => __LINE__ .': array',
                'argument'  => array('foobar'),
                'error'     => null,
                'expected'  => array('foobar' => null),
            ),
            array(
                'label'     => __LINE__ .': object',
                'argument'  => new stdClass,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set sort by; argument must be an array, string, or null.'
                ),
                'expected'  => null,
            )
        );

        $this->_runTests($tests, 'SortBy');
    }

    /**
     * Test get/set a query's ReverseOrder attribute.
     */
    public function testGetSetReverseOrder()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'argument'  => null,
                'error'     => null,
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': boolean',
                'argument'  => true,
                'error'     => null,
                'expected'  => true,
            ),
            array(
                'label'     => __LINE__ .': numeric non-zero',
                'argument'  => 1,
                'error'     => null,
                'expected'  => true,
            ),
            array(
                'label'     => __LINE__ .': numeric zero',
                'argument'  => 0,
                'error'     => null,
                'expected'  => false,
            ),
            array(
                'label'     => __LINE__ .': string',
                'argument'  => 'foobar',
                'error'     => null,
                'expected'  => true,
            ),
            array(
                'label'     => __LINE__ .': string numeric',
                'argument'  => '1',
                'error'     => null,
                'expected'  => true,
            ),
            array(
                'label'     => __LINE__ .': array',
                'argument'  => array('foobar'),
                'error'     => null,
                'expected'  => true,
            ),
            array(
                'label'     => __LINE__ .': object',
                'argument'  => new stdClass,
                'error'     => null,
                'expected'  => true,
            ),
        );

        $this->_runTests($tests, 'ReverseOrder');
    }

    /**
     * Test get/set a query's StartRow attribute.
     */
    public function testGetSetStartRow()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'argument'  => null,
                'error'     => null,
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': boolean',
                'argument'  => true,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set start row; argument must be a positive integer or null.'
                ),
                'expected'  => true,
            ),
            array(
                'label'     => __LINE__ .': positive numeric',
                'argument'  => 1,
                'error'     => null,
                'expected'  => 1,
            ),
            array(
                'label'     => __LINE__ .': negative numeric',
                'argument'  => -1,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set start row; argument must be a positive integer or null.'
                ),
                'expected'  => false,
            ),
            array(
                'label'     => __LINE__ .': zero',
                'argument'  => 0,
                'error'     => null,
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': string',
                'argument'  => 'foobar',
                'error'     => null,
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': string numeric',
                'argument'  => '1',
                'error'     => null,
                'expected'  => 1,
            ),
            array(
                'label'     => __LINE__ .': array',
                'argument'  => array('foobar'),
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set start row; argument must be a positive integer or null.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': object',
                'argument'  => new stdClass,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set start row; argument must be a positive integer or null.'
                ),
                'expected'  => null,
            ),
        );

        $this->_runTests($tests, 'StartRow');
    }

    /**
     * Test get/set a query's IncludeDeleted attribute.
     */
    public function testGetSetIncludeDeleted()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'argument'  => null,
                'error'     => null,
                'expected'  => false,
            ),
            array(
                'label'     => __LINE__ .': boolean',
                'argument'  => true,
                'error'     => null,
                'expected'  => true,
            ),
            array(
                'label'     => __LINE__ .': positive numeric',
                'argument'  => 1,
                'error'     => null,
                'expected'  => true,
            ),
            array(
                'label'     => __LINE__ .': negative numeric',
                'argument'  => -1,
                'error'     => null,
                'expected'  => true,
            ),
            array(
                'label'     => __LINE__ .': zero',
                'argument'  => 0,
                'error'     => null,
                'expected'  => false,
            ),
            array(
                'label'     => __LINE__ .': string',
                'argument'  => 'foobar',
                'error'     => null,
                'expected'  => true,
            ),
            array(
                'label'     => __LINE__ .': string numeric',
                'argument'  => '1',
                'error'     => null,
                'expected'  => true,
            ),
            array(
                'label'     => __LINE__ .': array',
                'argument'  => array('foobar'),
                'error'     => null,
                'expected'  => true,
            ),
            array(
                'label'     => __LINE__ .': object',
                'argument'  => new stdClass,
                'error'     => null,
                'expected'  => true,
            ),
        );

        $this->_runTests($tests, 'IncludeDeleted');
    }

    /**
     * Test get/set a query's MaxDepth attribute.
     */
    public function testGetSetMaxDepth()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'argument'  => null,
                'error'     => null,
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': boolean',
                'argument'  => true,
                'error'     => array(
                    'InvalidArgumentException'
                        => 'Cannot set maximum depth; argument must be a non-negative integer or null.'
                ),
                'expected'  => true,
            ),
            array(
                'label'     => __LINE__ .': positive numeric',
                'argument'  => 1,
                'error'     => null,
                'expected'  => 1,
            ),
            array(
                'label'     => __LINE__ .': negative numeric',
                'argument'  => -1,
                'error'     => array(
                    'InvalidArgumentException'
                        => 'Cannot set maximum depth; argument must be a non-negative integer or null.'
                ),
                'expected'  => false,
            ),
            array(
                'label'     => __LINE__ .': zero',
                'argument'  => 0,
                'error'     => null,
                'expected'  => 0,
            ),
            array(
                'label'     => __LINE__ .': string',
                'argument'  => 'foobar',
                'error'     => null,
                'expected'  => 0,
            ),
            array(
                'label'     => __LINE__ .': string numeric',
                'argument'  => '1',
                'error'     => null,
                'expected'  => 1,
            ),
            array(
                'label'     => __LINE__ .': array',
                'argument'  => array('foobar'),
                'error'     => array(
                    'InvalidArgumentException'
                        => 'Cannot set maximum depth; argument must be a non-negative integer or null.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': object',
                'argument'  => new stdClass,
                'error'     => array(
                    'InvalidArgumentException'
                        => 'Cannot set maximum depth; argument must be a non-negative integer or null.'
                ),
                'expected'  => null,
            ),
        );

        $this->_runTests($tests, 'MaxDepth');
    }

    /**
     * Test get/set a query's MaxRows attribute.
     */
    public function testGetSetMaxRows()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'argument'  => null,
                'error'     => null,
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': boolean',
                'argument'  => true,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set max files; argument must be a positive integer or null.'
                ),
                'expected'  => true,
            ),
            array(
                'label'     => __LINE__ .': positive numeric',
                'argument'  => 1,
                'error'     => null,
                'expected'  => 1,
            ),
            array(
                'label'     => __LINE__ .': negative numeric',
                'argument'  => -1,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set max files; argument must be a positive integer or null.'
                ),
                'expected'  => false,
            ),
            array(
                'label'     => __LINE__ .': zero',
                'argument'  => 0,
                'error'     => null,
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': string',
                'argument'  => 'foobar',
                'error'     => null,
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': string numeric',
                'argument'  => '1',
                'error'     => null,
                'expected'  => 1,
            ),
            array(
                'label'     => __LINE__ .': array',
                'argument'  => array('foobar'),
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set max files; argument must be a positive integer or null.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': object',
                'argument'  => new stdClass,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set max files; argument must be a positive integer or null.'
                ),
                'expected'  => null,
            ),
        );

        $this->_runTests($tests, 'MaxRows');
    }

    /**
     * Test get/set query paths.
     */
    public function testGetSetPaths()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'argument'  => null,
                'error'     => null,
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': boolean',
                'argument'  => true,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set filespecs; argument must be a string, an array, or null.'
                ),
                'expected'  => true,
            ),
            array(
                'label'     => __LINE__ .': numeric',
                'argument'  => 1,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set filespecs; argument must be a string, an array, or null.'
                ),
                'expected'  => 1,
            ),
            array(
                'label'     => __LINE__ .': string',
                'argument'  => 'foobar',
                'error'     => null,
                'expected'  => array('foobar'),
            ),
            array(
                'label'     => __LINE__ .': array',
                'argument'  => array('foobar'),
                'error'     => null,
                'expected'  => array('foobar'),
            ),
            array(
                'label'     => __LINE__ .': hash',
                'argument'  => array('foobar' => 'testing'),
                'error'     => null,
                'expected'  => array('testing'),
            ),
            array(
                'label'     => __LINE__ .': object',
                'argument'  => new stdClass,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set filespecs; argument must be a string, an array, or null.'
                ),
                'expected'  => null,
            ),
        );

        $this->_runTests($tests, 'Paths');
    }

    /**
     * Test adding a path to a query.
     */
    public function testAddPath()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'argument'  => null,
                'error'     => array('InvalidArgumentException' => 'Cannot add path; argument must be a string.'),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': numeric',
                'argument'  => 1,
                'error'     => array('InvalidArgumentException' => 'Cannot add path; argument must be a string.'),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': string',
                'argument'  => 'string',
                'error'     => null,
                'expected'  => array('string'),
            ),
            array(
                'label'     => __LINE__ .': array',
                'argument'  => array('string'),
                'error'     => array('InvalidArgumentException' => 'Cannot add path; argument must be a string.'),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': object',
                'argument'  => new stdClass,
                'error'     => array('InvalidArgumentException' => 'Cannot add path; argument must be a string.'),
                'expected'  => null,
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $query = new P4Cms_Record_Query;
            try {
                $query->addPath($test['argument']);
                if ($test['error']) {
                    $this->fail("$label - unexpected success");
                } else {
                    $this->assertEquals(
                        $test['expected'],
                        $query->getPaths(),
                        "$label - expected value"
                    );
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (PHPUnit_Framework_ExpectationFailedError $e) {
                $this->fail($e->getMessage());
            } catch (Exception $e) {
                if (!$test['error']) {
                    $this->fail("$label - Unexpected exception (". get_class($e) .') :'. $e->getMessage());
                } else {
                    list($class, $error) = each($test['error']);
                    $this->assertEquals(
                        $class,
                        get_class($e),
                        "$label - expected exception class: ". $e->getMessage()
                    );
                    $this->assertEquals(
                        $error,
                        $e->getMessage(),
                        "$label - expected exception message"
                    );
                }
            }
        }

        $query = P4Cms_Record_Query::create()->addPath('one')->addPath('two')->addPath('three');
        $this->assertEquals(
            array('one', 'two', 'three'),
            $query->getPaths(),
            'Expected filespecs after add chain'
        );
    }

    /**
     * Test adding multiple paths to a query.
     */
    public function testAddPaths()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'argument'  => null,
                'error'     => array('InvalidArgumentException' => 'Cannot add paths; argument must be an array.'),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': numeric',
                'argument'  => 1,
                'error'     => array('InvalidArgumentException' => 'Cannot add paths; argument must be an array.'),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': string',
                'argument'  => 'string',
                'error'     => array('InvalidArgumentException' => 'Cannot add paths; argument must be an array.'),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': array',
                'argument'  => array('string'),
                'error'     => null,
                'expected'  => array('string'),
            ),
            array(
                'label'     => __LINE__ .': object',
                'argument'  => new stdClass,
                'error'     => array('InvalidArgumentException' => 'Cannot add paths; argument must be an array.'),
                'expected'  => null,
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $query = new P4Cms_Record_Query;
            try {
                $query->addPaths($test['argument']);
                if ($test['error']) {
                    $this->fail("$label - unexpected success");
                } else {
                    $this->assertEquals(
                        $test['expected'],
                        $query->getPaths(),
                        "$label - expected value"
                    );
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (PHPUnit_Framework_ExpectationFailedError $e) {
                $this->fail($e->getMessage());
            } catch (Exception $e) {
                if (!$test['error']) {
                    $this->fail("$label - Unexpected exception (". get_class($e) .') :'. $e->getMessage());
                } else {
                    list($class, $error) = each($test['error']);
                    $this->assertEquals(
                        $class,
                        get_class($e),
                        "$label - expected exception class: ". $e->getMessage()
                    );
                    $this->assertEquals(
                        $error,
                        $e->getMessage(),
                        "$label - expected exception message"
                    );
                }
            }
        }

        $query = new P4Cms_Record_Query;
        $query->addPaths(array('one', 'two', 'three'));
        $this->assertEquals(
            array('one', 'two', 'three'),
            $query->getPaths(),
            'Expected filespecs after initial add'
        );

        // add array containing a dupe
        $query->addPaths(array('two', 'four', 'five'));
        $this->assertEquals(
            array('one', 'two', 'three', 'two', 'four', 'five'),
            $query->getPaths(),
            'Expected filespecs after 2nd add'
        );
    }

    /**
     * Test query fstat flag generation.
     */
    public function testGetFstatFlags()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': fresh query',
                'query'     => new P4Cms_Record_Query,
                'expected'  => array('-F', '^headAction=...delete', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': with paths',
                'query'     => P4Cms_Record_Query::create()->addPath('one')->addPath('two'),
                'expected'  => array('-F', '^headAction=...delete', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': reversed order',
                'query'     => P4Cms_Record_Query::create()->setReverseOrder(true),
                'expected'  => array('-F', '^headAction=...delete', '-r', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': sort by fileSize',
                'query'     => P4Cms_Record_Query::create()->setSortBy(P4Cms_Record_Query::SORT_FILE_SIZE),
                'expected'  => array('-F', '^headAction=...delete', '-Ss', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': sort by fileType',
                'query'     => P4Cms_Record_Query::create()->setSortBy(P4Cms_Record_Query::SORT_FILE_TYPE),
                'expected'  => array('-F', '^headAction=...delete', '-St', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': sort by date',
                'query'     => P4Cms_Record_Query::create()->setSortBy(P4Cms_Record_Query::SORT_DATE),
                'expected'  => array('-F', '^headAction=...delete', '-Sd', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': sort by head rev',
                'query'     => P4Cms_Record_Query::create()->setSortBy(P4Cms_Record_Query::SORT_HEAD_REV),
                'expected'  => array('-F', '^headAction=...delete', '-Sr', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': sort by have rev',
                'query'     => P4Cms_Record_Query::create()->setSortBy(P4Cms_Record_Query::SORT_HAVE_REV),
                'expected'  => array('-F', '^headAction=...delete', '-Sh', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': max files',
                'query'     => P4Cms_Record_Query::create()->setMaxRows(7),
                'expected'  => array('-F', '^headAction=...delete', '-m', 7, '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': max depth',
                'query'     => P4Cms_Record_Query::create()->setMaxDepth(4),
                'expected'  => array('-F', '(^headAction=...delete) & ^depotFile=//depot/*/*/*/*/*/...', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': filter',
                'query'     => P4Cms_Record_Query::create()->setFilter('attr-title=test')
                                                      ->setMaxRows(7)
                                                      ->setMaxDepth(4)
                                                      ->setSortBy(P4Cms_Record_Query::SORT_HAVE_REV)
                                                      ->setReverseOrder(true),
                'expected'  => array(
                    '-F', '((attr-title=test) & ^headAction=...delete) & ^depotFile=//depot/*/*/*/*/*/...',
                    '-m', 7,
                    '-Sh',
                    '-r',
                    '-Oal'
                ),
            ),

            array(
                'label'     => __LINE__ .': all',
                'query'     => P4Cms_Record_Query::create()->setFilter('attr-title=test'),
                'expected'  => array('-F', '(attr-title=test) & ^headAction=...delete', '-Oal'),
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];

            $this->assertEquals(
                $test['expected'],
                $test['query']->toFileQuery()->getFstatFlags(),
                "$label - expected flags"
            );
        }
    }

    /**
     * Test variations of adding paths to a query.
     */
    public function testAddModes()
    {
        $query = new P4Cms_Record_Query;
        $this->assertEquals(null, $query->getPaths(), 'Expected paths after new object.');

        $query->addPath('one');
        $query->addPaths(array('two', 'three'));
        $this->assertEquals(
            array('one', 'two', 'three'),
            $query->getPaths(),
            'Expected paths after add without intersect (1).'
        );

        $query->addPaths(array('two', 'three', 'four'), true);
        $this->assertEquals(
            array('two', 'three'),
            $query->getPaths(),
            'Expected paths after add with intersect (1).'
        );

        $query->addPath('two', true);
        $this->assertEquals(
            array('two'),
            $query->getPaths(),
            'Expected paths after add with intersect (2).'
        );

        $query->addPath('one');
        $query->addPaths(array('two', 'three'));
        $this->assertEquals(
            array('two', 'one', 'two', 'three'),
            $query->getPaths(),
            'Expected paths after add without intersect (2).'
        );

        $query->addPath('five', true);
        $this->assertEquals(
            array(),
            $query->getPaths(),
            'Expected paths after add with intersect (3).'
        );
    }

    /**
     * Test conversion of query paths to filespecs.
     */
    public function testToFileQueryPathToFilespec()
    {
        $query = P4Cms_Record_Query::create()->addPath('one')->addPath('two');
        $this->assertSame(
            array('one', 'two'),
            $query->getPaths(),
            'Expected paths'
        );

        // should result in tmp label.
        $filespecs = $query->toFileQuery()->getFilespecs();
        $this->assertSame(1, count($filespecs));
        $this->assertRegExp("://depot/\.\.\.@~tmp.[0-9]+:", $filespecs[0]);
    }

    /**
     * Test removing a path from a query.
     */
    public function testRemovePath()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'argument'  => null,
                'error'     => array('InvalidArgumentException' => 'Cannot remove path; argument must be a string.'),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': numeric',
                'argument'  => 1,
                'error'     => array('InvalidArgumentException' => 'Cannot remove path; argument must be a string.'),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': string',
                'argument'  => 'string',
                'error'     => null,
                'expected'  => array(),
            ),
            array(
                'label'     => __LINE__ .': array',
                'argument'  => array('string'),
                'error'     => array('InvalidArgumentException' => 'Cannot remove path; argument must be a string.'),
                'expected'  => array('string'),
            ),
            array(
                'label'     => __LINE__ .': object',
                'argument'  => new stdClass,
                'error'     => array('InvalidArgumentException' => 'Cannot remove path; argument must be a string.'),
                'expected'  => null,
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $query = new P4Cms_Record_Query;
            try {
                $query->removePath($test['argument']);
                if ($test['error']) {
                    $this->fail("$label - unexpected success");
                } else {
                    $this->assertEquals(
                        $test['expected'],
                        $query->getPaths(),
                        "$label - expected value"
                    );
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (PHPUnit_Framework_ExpectationFailedError $e) {
                $this->fail($e->getMessage());
            } catch (Exception $e) {
                if (!$test['error']) {
                    $this->fail("$label - Unexpected exception (". get_class($e) .') :'. $e->getMessage());
                } else {
                    list($class, $error) = each($test['error']);
                    $this->assertEquals(
                        $class,
                        get_class($e),
                        "$label - expected exception class: ". $e->getMessage()
                    );
                    $this->assertEquals(
                        $error,
                        $e->getMessage(),
                        "$label - expected exception message"
                    );
                }
            }
        }

        $query = P4Cms_Record_Query::create()->addPaths(array('one', 'two', 'three', 'two', 'four'));
        $this->assertEquals(
            array('one', 'two', 'three', 'two', 'four'),
            $query->getPaths(),
            'Expected paths after add'
        );

        $query->removePath('two');
        $this->assertEquals(
            array('one', 'three', 'two', 'four'),
            $query->getPaths(),
            'Expected paths after remove'
        );
    }

    /**
     * Test removing multiple paths from a query.
     */
    public function testRemovePaths()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'argument'  => null,
                'error'     => array('InvalidArgumentException' => 'Cannot remove paths; argument must be an array.'),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': numeric',
                'argument'  => 1,
                'error'     => array('InvalidArgumentException' => 'Cannot remove paths; argument must be an array.'),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': string',
                'argument'  => 'string',
                'error'     => array('InvalidArgumentException' => 'Cannot remove paths; argument must be an array.'),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': array',
                'argument'  => array('string'),
                'error'     => null,
                'expected'  => array(),
            ),
            array(
                'label'     => __LINE__ .': object',
                'argument'  => new stdClass,
                'error'     => array('InvalidArgumentException' => 'Cannot remove paths; argument must be an array.'),
                'expected'  => null,
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $query = new P4Cms_Record_Query;
            try {
                $query->removePaths($test['argument']);
                if ($test['error']) {
                    $this->fail("$label - unexpected success");
                } else {
                    $this->assertEquals(
                        $test['expected'],
                        $query->getPaths(),
                        "$label - expected value"
                    );
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (PHPUnit_Framework_ExpectationFailedError $e) {
                $this->fail($e->getMessage());
            } catch (Exception $e) {
                if (!$test['error']) {
                    $this->fail("$label - Unexpected exception (". get_class($e) .') :'. $e->getMessage());
                } else {
                    list($class, $error) = each($test['error']);
                    $this->assertEquals(
                        $class,
                        get_class($e),
                        "$label - expected exception class: ". $e->getMessage()
                    );
                    $this->assertEquals(
                        $error,
                        $e->getMessage(),
                        "$label - expected exception message"
                    );
                }
            }
        }

        $query = P4Cms_Record_Query::create()->addPaths(array('one', 'two', 'three', 'two', 'four'));
        $this->assertEquals(
            array('one', 'two', 'three', 'two', 'four'),
            $query->getPaths(),
            'Expected paths after add'
        );

        $query->removePaths(array('two', 'four'));
        $this->assertEquals(
            array('one', 'three', 'two'),
            $query->getPaths(),
            'Expected paths after remove #1'
        );
    }

    /**
     * Test (set/get)LimitField() methods.
     */
    public function testGetSetLimitFields()
    {
        $record = P4Cms_Record::store(
            array(
                'id'        => 'test',
                'field1'    => 'a',
                'field2'    => 'b',
                'field3'    => 'c'
            )
        );

        $query = P4Cms_Record_Query::create()->setLimitFields(array('field2'));
        $this->assertSame(
            array('field2'),
            $query->getLimitFields(),
            "Expected limit fields."
        );

        $entry = P4Cms_Record::fetch('test', $query);
        $this->assertSame(
            array('id', 'field2'),
            $entry->getFields(),
            'Expected only selected fields are returned.'
        );

        $this->assertSame(
            array('id' => 'test', 'field2' => 'b'),
            $entry->getValues(),
            'Expected only values for selected fields are returned.'
        );

        $this->assertSame(
            null,
            $entry->getValue('field3'),
            "Expected return default value for field not set in limti fields."
        );

        // verify that limitFields option is not directly applied to the file query
        $options = array('limitFields' => 'field1');
        $query   = new P4Cms_Record_Query($options);

        $this->assertSame(
            'field1',
            $query->getLimitFields(),
            "Expected limitFields value set on record query."
        );

        // verify that record fields are converted to the file fields (depotFile field is always present)
        $fileQuery = $query->toFileQuery();
        $this->assertSame(
            array('depotFile', 'attr-field1'),
            $fileQuery->getLimitFields(),
            "Expected limitFields value set on file query."
        );

        // verify that id and file content fields are not converted to the fiel fields
        $limitFields = array('field3', P4Cms_Content::getIdField(), P4Cms_Content::getFileContentField());
        $query       = new P4Cms_Record_Query(array('limitFields' => $limitFields));
        
        $this->assertSame(
            $limitFields,
            $query->getLimitFields(),
            "Expected limitFields value set on record query #2."
        );

        $fileQuery = $query->toFileQuery('P4Cms_Content');
        $this->assertSame(
            array('depotFile', 'attr-field3'),
            $fileQuery->getLimitFields(),
            "Expected limitFields value set on file query #2."
        );
    }
}