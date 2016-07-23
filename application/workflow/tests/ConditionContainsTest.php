<?php
/**
 * Test workflow 'Contains' condition class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Test_ConditionContainsTest extends ModuleTest
{
    /**
     * Test evaluate() method.
     */
    public function testEvaluate()
    {
        // create few records to test with
        $recordA = P4Cms_Record::create(
            array(
                'field1' => 'test field1 value',
                'field2' => 'abc123 baz',
                'field3' => 'foo bar'
            )
        );
        $recordA->setFieldMetadata('field1', array('type' => 'foo', 'mimeType' => 'bogus'));

        $recordB = P4Cms_Record::create(
            array(
                'field1' => 'test field1 value',
                'fieldY' => 'abc123 baz',
                'fieldZ' => 'foo bar'
            )
        );
        $recordB->setFieldMetadata('field1', array('mimeType' => 'text/ascii'));

        $recordC = P4Cms_Record::create(
            array(
                'r'     => 'test fieldr value',
                's'     => 'abc123 baz',
                't'     => 'foo bar'
            )
        );
        $recordC->setFieldMetadata('s', array('mimeType' => 'foo'));

        // define tests
        $tests = array(
            array(
                'options'       => array(),
                'expected'      => array(),
                'message'       => __LINE__ . ": no options"
            ),
            array(
                'options'       => array(
                    'fields'    => array('o1', 'o2', 'o3')
                ),
                'expected'      => array(),
                'message'       => __LINE__ . ": bogus fields"
            ),
            array(
                'options'       => array(
                    'string'    => 'test1a'
                ),
                'expected'      => array(),
                'message'       => __LINE__ . ": literal match"
            ),
            array(
                'options'       => array(
                    'string'    => 'test'
                ),
                'expected'      => array('b', 'c'),
                'message'       => __LINE__ . ": literal match"
            ),
            array(
                'options'       => array(
                    'string'    => 'TEST',
                    'fields'    => 'field1'
                ),
                'expected'      => array('b'),
                'message'       => __LINE__ . ": literal match, specified fields"
            ),
            array(
                'options'       => array(
                    'pattern'   => '/./'
                ),
                'expected'      => array('a', 'b', 'c'),
                'message'       => __LINE__ . ": regex match"
            ),
            array(
                'options'       => array(
                    'pattern'   => '/\d{1,}/'
                ),
                'expected'      => array('a', 'b'),
                'message'       => __LINE__ . ": regex match"
            ),
            array(
                'options'       => array(
                    'pattern'   => '/./',
                    'fields'    => array('fieldY', 't', 'foo', 'bar')
                ),
                'expected'      => array('b', 'c'),
                'message'       => __LINE__ . ": regex match, specified fields"
            ),
            array(
                'options'       => array(
                    'pattern'   => '/./',
                    'string'    => 'string-that-is-not-present',
                ),
                'expected'      => array('a', 'b', 'c'),
                'message'       => __LINE__ . ": regex and literal match"
            ),
            array(
                'options'       => array(
                    'pattern'   => '/\d{100}/',
                    'string'    => 'foo',
                ),
                'expected'      => array('a', 'b', 'c'),
                'message'       => __LINE__ . ": regex and literal match"
            ),
            array(
                'options'       => array(
                    'pattern'   => '/\d\w/',
                    'string'    => 'd1 val',
                ),
                'expected'      => array('a', 'b'),
                'message'       => __LINE__ . ": regex and literal match"
            ),
            array(
                'options'       => array(
                    'pattern'   => '/\d\w/',
                    'string'    => 'd1 val',
                    'fields'    => array('fieldQ')
                ),
                'expected'      => array(),
                'message'       => __LINE__ . ": regex and literal match, selected fields"
            ),
            array(
                'options'       => array(
                    'pattern'   => '/\d\w/',
                    'string'    => 'd1 val',
                    'fields'    => array('field1', 's')
                ),
                'expected'      => array('b'),
                'message'       => __LINE__ . ": regex and literal match, selected fields"
            )
        );

        // instantiate condition contains class
        $class     = Workflow_Module::getPluginLoader('condition')->load('contains');
        $condition = new $class;
        
        // create records array for the runner
        $records   = array('a' => $recordA, 'b' => $recordB, 'c' => $recordC);

        // run tests
        foreach ($tests as $test) {
            $condition->setOptions($test['options']);
            $this->assertSame(
                $test['expected'],
                $this->_evaluateRunner($records, $condition),
                'Line ' . $test['message']
            );
        }
    }

    /**
     * Helper function to filter records array by condition evaluated in their context.
     * Returns array with record keys where given condition is met when evaluated in
     * record context.
     *
     * @param   array                       $records        records to filter by condition.
     * @param   Workflow_ConditionAbstract  $condition      condition to be evaluated in
     *                                                      record context.
     * @return  array                       filtered array with records by given condition.
     */
    protected function _evaluateRunner(array $records, Workflow_ConditionAbstract $condition)
    {
        $filteredRecords = array_filter(
            $records,
            function ($record) use ($condition)
            {
                return $condition->evaluate(new Workflow_Model_Transition, $record);
            }
        );

        return array_keys($filteredRecords);
    }
}