<?php
/**
 * Test methods for the P4 File Query class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_File_QueryTest extends TestCase
{
    /**
     * Provide test array runner, as most of the included test methods
     * require almost identical infrastructure for checking success/failure.
     *
     * @param  array  $tests  The array of tests to run.
     * @param  array  $method The P4_File_Query method suffix, after 'get' and 'set'.
     */
    protected function _runTests($tests, $method)
    {
        $setMethod = "set$method";
        $getMethod = "get$method";
        foreach ($tests as $test) {
            $label = $test['label'];
            $query = new P4_File_Query;
            try {
                $query->$setMethod($test['argument']);
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
     * Test out-of-the-box behaviour of filter constructors.
     */
    public function testInitialConditions()
    {
        $query = new P4_File_Query;
        $this->assertTrue($query instanceof P4_File_Query, 'Expected class.');
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
                P4_File_Query::QUERY_MAX_FILES               => null,
                P4_File_Query::QUERY_START_ROW               => null,
                P4_File_Query::QUERY_FILESPECS               => null,
            ),
            $query->toArray(),
            'Expected options as array'
        );

        $query = P4_File_Query::create();
        $this->assertTrue($query instanceof P4_File_Query, 'Expected class.');
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
                P4_File_Query::QUERY_MAX_FILES               => null,
                P4_File_Query::QUERY_START_ROW               => null,
                P4_File_Query::QUERY_FILESPECS               => null,
            ),
            $query->toArray(),
            'Expected options as array'
        );
    }

    /**
     * Test behaviour of filter constructors.
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
            P4_File_Query::QUERY_MAX_FILES               => -1,
            P4_File_Query::QUERY_START_ROW               => -1,
            P4_File_Query::QUERY_FILESPECS               => -1,
        );

        try {
            $query = new P4_File_Query($badOptions);
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
            P4_File_Query::QUERY_MAX_FILES               => 10,
            P4_File_Query::QUERY_START_ROW               => 5,
            P4_File_Query::QUERY_FILESPECS               => array('filespec'),
        );
        $query = new P4_File_Query($goodOptions);
        $expected = $goodOptions;
        $expected[P4_File_Query::QUERY_SORT_BY] = array('column' => null);
        $this->assertEquals(
            $expected,
            $query->toArray(),
            'Expected options'
        );

        $query = P4_File_Query::create($goodOptions);
        $this->assertEquals(
            $expected,
            $query->toArray(),
            'Expected options'
        );
    }

    /**
     * Test get/set Filter attribute.
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
                        'Cannot set filter; argument must be a P4_File_Filter, a string, or null.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': numeric',
                'argument'  => 1,
                'error'     => array(
                    'InvalidArgumentException' => 
                        'Cannot set filter; argument must be a P4_File_Filter, a string, or null.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': string',
                'argument'  => 'foobar',
                'error'     => null,
                'expected'  => 'foobar',
            ),
            array(
                'label'     => __LINE__ .': array',
                'argument'  => array('foobar'),
                'error'     => array(
                    'InvalidArgumentException' => 
                        'Cannot set filter; argument must be a P4_File_Filter, a string, or null.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': some object',
                'argument'  => new stdClass,
                'error'     => array(
                    'InvalidArgumentException' => 
                        'Cannot set filter; argument must be a P4_File_Filter, a string, or null.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': P4_File_Filter',
                'argument'  => P4Cms_Record_Filter::create(),
                'error'     => null,
                'expected'  => '',
            ),
            array(
                'label'     => __LINE__ .': P4_File_Filter',
                'argument'  => P4Cms_Record_Filter::create()->add('field', 'value'),
                'error'     => null,
                'expected'  => 'attr-field~=\\^value\\$',
            ),
            array(
                'label'     => __LINE__ .': P4_File_Filter',
                'argument'  => P4Cms_Record_Filter::create()
                    ->add('field', 'value')
                    ->addSubFilter(new P4Cms_Record_Filter),
                'error'     => null,
                'expected'  => 'attr-field~=\\^value\\$',
            ),
            array(
                'label'     => __LINE__ .': P4_File_Filter',
                'argument'  => P4Cms_Record_Filter::create()
                    ->addSubFilter(new P4Cms_Record_Filter)
                    ->add('field', 'value'),
                'error'     => null,
                'expected'  => 'attr-field~=\\^value\\$',
            ),
            array(
                'label'     => __LINE__ .': P4_File_Filter',
                'argument'  => P4Cms_Record_Filter::create()
                    ->addSubFilter(new P4Cms_Record_Filter)
                    ->add('field', 'value')
                    ->addSubFilter(new P4Cms_Record_Filter),
                'error'     => null,
                'expected'  => 'attr-field~=\\^value\\$',
            )
        );

        // This block is needed here because _runTests does not support non P4_File_Filter 
        // objects for the argument.
        foreach ($tests as $test) {
            $label = $test['label'];
            $query = new P4_File_Query;
            try {
                $query->setFilter($test['argument']);
                if (isset($test['addFilter'])) {
                    foreach ((array) $test['addFilter'] as $filter) {
                        $query->addFilter($filter);
                    }
                }

                $expr = $query->getFilter() instanceof P4_File_Filter 
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
                        'Cannot set filter; argument must be a P4_File_Filter, a string, or null.',
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
     * Test get/set SortBy attribute.
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
                'label'     => __LINE__ .': array with null',
                'argument'  => array(null),
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set sort by; invalid sort clause provided.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': array with numeric',
                'argument'  => array(1),
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set sort by; invalid sort clause provided.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': array with object',
                'argument'  => array(new stdClass),
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set sort by; invalid sort clause provided.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': array, 1 clause, good string field',
                'argument'  => array('foobar'),
                'error'     => null,
                'expected'  => array('foobar' => null),
            ),
            array(
                'label'     => __LINE__ .': array, 1 clause, good array',
                'argument'  => array('foobar'),
                'error'     => null,
                'expected'  => array('foobar' => null),
            ),
            array(
                'label'     => __LINE__ .': array, 1 clause, null string field',
                'argument'  => array(null => null),
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set sort by; invalid field name in clause #1.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': array, 1 clause, bad string field',
                'argument'  => array('#foobar'),
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set sort by; invalid field name in clause #1.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': array, 1 clause, field with bogus options',
                'argument'  => array('foobar' => new stdClass()),
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set sort by; invalid sort options in clause #1.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': array, 1 clause, field with unknown option',
                'argument'  => array('foobar' => array('fred')),
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set sort by; invalid sort options in clause #1.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': array, 1 clause, field with overlapping a|d options',
                'argument'  => array(
                    'foobar' => array(P4_File_Query::SORT_ASCENDING, P4_File_Query::SORT_DESCENDING)
                ),
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set sort by; invalid sort options in clause #1.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': array, 1 clause, field with valid options',
                'argument'  => array(
                    'foobar' => array(P4_File_Query::SORT_DESCENDING)
                ),
                'error'     => null,
                'expected'  => array('foobar' => array(P4_File_Query::SORT_DESCENDING)),
            ),
            array(
                'label'     => __LINE__ .': array, 2 clauses, 1 array + 1 string',
                'argument'  => array(
                    'foobar' => array(P4_File_Query::SORT_DESCENDING),
                    'oobleck'
                ),
                'error'     => null,
                'expected'  => array(
                    'foobar' => array(P4_File_Query::SORT_DESCENDING),
                    'oobleck' => null
                ),
            ),
            array(
                'label'     => __LINE__ .': array, 3 clauses',
                'argument'  => array('foobar', 'test', 'oobleck'),
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set sort by; argument contains more than 2 clauses.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': object',
                'argument'  => new stdClass,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set sort by; argument must be an array, string, or null.'
                ),
                'expected'  => null,
            ),
        );

        $this->_runTests($tests, 'SortBy');
    }

    /**
     * Test get/set ReverseOrder attribute.
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
     * Test get/set LimitFields attribute.
     */
    public function testGetSetLimitFields()
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
                        => 'Cannot set limiting fields; argument must be a string, an array, or null.'
                ),
                'expected'  => true,
            ),
            array(
                'label'     => __LINE__ .': numeric',
                'argument'  => 1,
                'error'     => array(
                    'InvalidArgumentException'
                        => 'Cannot set limiting fields; argument must be a string, an array, or null.'
                ),
                'expected'  => true,
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
                'label'     => __LINE__ .': object',
                'argument'  => new stdClass,
                'error'     => array(
                    'InvalidArgumentException'
                        => 'Cannot set limiting fields; argument must be a string, an array, or null.'
                ),
                'expected'  => null,
            ),
        );

        $this->_runTests($tests, 'LimitFields');
    }

    /**
     * Test get/set LimitToNeedsResolve attribute.
     */
    public function testGetSetLimitToNeedsResolve()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'argument'  => null,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set limit to needs resolve; argument must be a boolean.'
                ),
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
                'expected'  => false,
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
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set limit to needs resolve; argument must be a boolean.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': object',
                'argument'  => new stdClass,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set limit to needs resolve; argument must be a boolean.'
                ),
                'expected'  => null,
            ),
        );

        $this->_runTests($tests, 'LimitToNeedsResolve');
    }

    /**
     * Test get/set LimitToOpened attribute.
     */
    public function testGetSetLimitToOpened()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'argument'  => null,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set limit to opened files; argument must be a boolean.'
                ),
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
                'expected'  => false,
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
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set limit to opened files; argument must be a boolean.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': object',
                'argument'  => new stdClass,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set limit to opened files; argument must be a boolean.'
                ),
                'expected'  => null,
            ),
        );

        $this->_runTests($tests, 'LimitToOpened');
    }

    /**
     * Test get/set LimitToChangelist attribute.
     */
    public function testGetSetLimitToChangelist()
    {
        $change = new P4_Change;
        $change->setId(123);

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
                    'InvalidArgumentException' => 'Cannot set limit to changelist; argument must be a changelist id,'
                        . ' a P4_Change object, or null.'
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
                    'InvalidArgumentException' => 'Cannot set limit to changelist; argument must be a changelist id,'
                        . ' a P4_Change object, or null.'
                ),
                'expected'  => false,
            ),
            array(
                'label'     => __LINE__ .': zero',
                'argument'  => 0,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set limit to changelist; argument must be a changelist id,'
                        . ' a P4_Change object, or null.'
                ),
                'expected'  => 0,
            ),
            array(
                'label'     => __LINE__ .': string',
                'argument'  => 'foobar',
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set limit to changelist; argument must be a changelist id,'
                        . ' a P4_Change object, or null.'
                ),
                'expected'  => 0,
            ),
            array(
                'label'     => __LINE__ .': default changelist',
                'argument'  => 'default',
                'error'     => null,
                'expected'  => 'default',
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
                    'InvalidArgumentException' => 'Cannot set limit to changelist; argument must be a changelist id,'
                        . ' a P4_Change object, or null.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': some object',
                'argument'  => new stdClass,
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set limit to changelist; argument must be a changelist id,'
                        . ' a P4_Change object, or null.'
                ),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': P4_Change object',
                'argument'  => $change,
                'error'     => null,
                'expected'  => 123,
            ),
        );

        $this->_runTests($tests, 'LimitToChangelist');
    }

    /**
     * Test get/set StartRow attribute.
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
     * Test get/set MaxFiles attribute.
     */
    public function testGetSetMaxFiles()
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

        $this->_runTests($tests, 'MaxFiles');
    }

    /**
     * Test get/set of filespecs.
     */
    public function testGetSetFilespecs()
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

        $this->_runTests($tests, 'Filespecs');
    }

    /**
     * Test addFilespec().
     */
    public function testAddFilespec()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'argument'  => null,
                'error'     => array('InvalidArgumentException' => 'Cannot add filespec; argument must be a string.'),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': numeric',
                'argument'  => 1,
                'error'     => array('InvalidArgumentException' => 'Cannot add filespec; argument must be a string.'),
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
                'error'     => array('InvalidArgumentException' => 'Cannot add filespec; argument must be a string.'),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': object',
                'argument'  => new stdClass,
                'error'     => array('InvalidArgumentException' => 'Cannot add filespec; argument must be a string.'),
                'expected'  => null,
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $query = new P4_File_Query;
            try {
                $query->addFilespec($test['argument']);
                if ($test['error']) {
                    $this->fail("$label - unexpected success");
                } else {
                    $this->assertEquals(
                        $test['expected'],
                        $query->getFilespecs(),
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

        $query = P4_File_Query::create()->addFilespec('one')->addFilespec('two')->addFilespec('three');
        $this->assertEquals(
            array('one', 'two', 'three'),
            $query->getFilespecs(),
            'Expected filespecs after add chain'
        );
    }

    /**
     * Test addFilespecs().
     */
    public function testAddFilespecs()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'argument'  => null,
                'error'     => array('InvalidArgumentException' => 'Cannot add filespecs; argument must be an array.'),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': numeric',
                'argument'  => 1,
                'error'     => array('InvalidArgumentException' => 'Cannot add filespecs; argument must be an array.'),
                'expected'  => null,
            ),
            array(
                'label'     => __LINE__ .': string',
                'argument'  => 'string',
                'error'     => array('InvalidArgumentException' => 'Cannot add filespecs; argument must be an array.'),
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
                'error'     => array('InvalidArgumentException' => 'Cannot add filespecs; argument must be an array.'),
                'expected'  => null,
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $query = new P4_File_Query;
            try {
                $query->addFilespecs($test['argument']);
                if ($test['error']) {
                    $this->fail("$label - unexpected success");
                } else {
                    $this->assertEquals(
                        $test['expected'],
                        $query->getFilespecs(),
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

        $query = new P4_File_Query;
        $query->addFilespecs(array('one', 'two', 'three'));
        $this->assertEquals(
            array('one', 'two', 'three'),
            $query->getFilespecs(),
            'Expected filespecs after initial add'
        );

        // add array containing a dupe
        $query->addFilespecs(array('two', 'four', 'five'));
        $this->assertEquals(
            array('one', 'two', 'three', 'two', 'four', 'five'),
            $query->getFilespecs(),
            'Expected filespecs after 2nd add'
        );
    }

    /**
     * Test getFstatFlags().
     */
    public function testGetFstatFlags()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': fresh query',
                'query'     => new P4_File_Query,
                'expected'  => array('-Oal'),
            ),
            array(
                'label'     => __LINE__ .': reversed order',
                'query'     => P4_File_Query::create()->setReverseOrder(true),
                'expected'  => array('-r', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': sort by fileSize',
                'query'     => P4_File_Query::create()->setSortBy(P4_File_Query::SORT_FILE_SIZE),
                'expected'  => array('-Ss', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': sort by fileType',
                'query'     => P4_File_Query::create()->setSortBy(P4_File_Query::SORT_FILE_TYPE),
                'expected'  => array('-St', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': sort by date',
                'query'     => P4_File_Query::create()->setSortBy(P4_File_Query::SORT_DATE),
                'expected'  => array('-Sd', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': sort by head rev',
                'query'     => P4_File_Query::create()->setSortBy(P4_File_Query::SORT_HEAD_REV),
                'expected'  => array('-Sr', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': sort by have rev',
                'query'     => P4_File_Query::create()->setSortBy(P4_File_Query::SORT_HAVE_REV),
                'expected'  => array('-Sh', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': sort by attribute',
                'query'     => P4_File_Query::create()->setSortBy(array('field')),
                'expected'  => array('-S', 'attr-field=a', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': sort by attribute, then date',
                'query'     => P4_File_Query::create()->setSortBy(array('field', P4_File_Query::SORT_DATE)),
                'expected'  => array('-S', 'attr-field=a,REdate=a', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': sort by attribute descending, then date',
                'query'     => P4_File_Query::create()->setSortBy(
                    array(
                        'field' => array(P4_File_Query::SORT_DESCENDING),
                        P4_File_Query::SORT_DATE
                    )
                ),
                'expected'  => array('-S', 'attr-field=d,REdate=a', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': limit to opened',
                'query'     => P4_File_Query::create()->setLimitToOpened(true),
                'expected'  => array('-Ro', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': limit to needs resolve',
                'query'     => P4_File_Query::create()->setLimitToNeedsResolve(true),
                'expected'  => array('-Ru', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': limit to changelist 1',
                'query'     => P4_File_Query::create()->setLimitToChangelist(1),
                'expected'  => array('-e', 1, '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': limit to default changelist',
                'query'     => P4_File_Query::create()->setLimitToChangelist('default'),
                'expected'  => array('-e', 'default', '-Ro', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': limit fields',
                'query'     => P4_File_Query::create()->setLimitFields(array('depotFile', 'headRev')),
                'expected'  => array('-T', 'depotFile headRev', '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': max files',
                'query'     => P4_File_Query::create()->setMaxFiles(7),
                'expected'  => array('-m', 7, '-Oal'),
            ),
            array(
                'label'     => __LINE__ .': filter',
                'query'     => P4_File_Query::create()->setFilter('attr-title=test')
                                                      ->setMaxFiles(7)
                                                      ->setLimitFields(array('depotFile', 'headRev'))
                                                      ->setLimitToChangelist(1)
                                                      ->setLimitToOpened(true)
                                                      ->setLimitToNeedsResolve(true)
                                                      ->setSortBy(P4_File_Query::SORT_HAVE_REV)
                                                      ->setReverseOrder(true),
                'expected'  => array(
                    '-F', 'attr-title=test',
                    '-T', 'depotFile headRev',
                    '-m', 7,
                    '-e', 1,
                    '-Ro',
                    '-Ru',
                    '-Sh',
                    '-r',
                    '-Oal'
                ),
            ),

            array(
                'label'     => __LINE__ .': all',
                'query'     => P4_File_Query::create()->setFilter('attr-title=test'),
                'expected'  => array('-F', 'attr-title=test', '-Oal'),
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];

            $this->assertEquals(
                $test['expected'],
                $test['query']->getFstatFlags(),
                "$label - expected flags"
            );
        }
    }
}
