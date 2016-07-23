<?php
/**
 * Test the manage content form.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Test_ManageContentFormTest extends ModuleTest
{
    /**
     * Test generating workflow state options.
     */
    public function testStateOptions()
    {
        // create several workflows
        Workflow_Model_Workflow::store(
            array(
                'id'        => 'w1',
                'label'     => 'workflow 1',
                'states'    => array(
                    'a' => array('label' => 'W1 A'),
                    'b' => array('label' => 'B'),
                    'c' => array('label' => 'W1 C'),
                )
            )
        );
        Workflow_Model_Workflow::store(
            array(
                'id'        => 'w2',
                'label'     => 'workflow 2',
                'states'    => array(
                    'c' => array('label' => 'W2 C'),
                    'b' => array('label' => 'B'),
                    'x' => array('label' => 'W2 X'),
                    'y' => array('label' => 'W2 Y'),
                )
            )
        );
        Workflow_Model_Workflow::store(
            array(
                'id'        => 'w3',
                'label'     => 'workflow 3',
                'states'    => array(
                    'b' => array('label' => 'W3 B'),
                    'x' => array('label' => 'W3 X'),
                    'z' => array('label' => 'W3 Z'),
                )
            )
        );
        Workflow_Model_Workflow::store(
            array(
                'id'        => 'w4',
                'label'     => 'workflow 4',
                'states'    => array(
                    'x' => array('label' => 'W4 X'),
                    'y' => array('label' => 'W4 Y'),
                    'z' => array('label' => 'W4 Z'),
                    'u' => array('label' => 'W4 U'),
                    'v' => array('label' => 'W4 V'),
                )
            )
        );

        // define tests
        $tests = array(
            array(
                'line'              => __LINE__,
                'workflows'         => array(),
                'expectedStates'    => array()
            ),
            array(
                'line'              => __LINE__,
                'workflows'         => null,
                'expectedStates'    => array()
            ),
            array(
                'line'              => __LINE__,
                'workflows'         => '',
                'expectedStates'    => array()
            ),
            array(
                'line'              => __LINE__,
                'workflows'         => array(null),
                'expectedStates'    => array()
            ),
            array(
                'line'              => __LINE__,
                'workflows'         => array('not-exist', 'undefined'),
                'expectedStates'    => array()
            ),
            array(
                'line'              => __LINE__,
                'workflows'         => array('w1'),
                'expectedStates'    => array(
                    'a' => 'W1 A',
                    'b' => 'B',
                    'c' => 'W1 C'
                )
            ),
            array(
                'line'              => __LINE__,
                'workflows'         => 'w1',
                'expectedStates'    => array(
                    'a' => 'W1 A',
                    'b' => 'B',
                    'c' => 'W1 C'
                )
            ),
            array(
                'line'              => __LINE__,
                'workflows'         => array('w1', 'w1', 'w1'),
                'expectedStates'    => array(
                    'a' => 'W1 A',
                    'b' => 'B',
                    'c' => 'W1 C'
                )
            ),
            array(
                'line'              => __LINE__,
                'workflows'         => array('w1', 'w2'),
                'expectedStates'    => array(
                    'b' => 'B',
                    'c' => 'W1 C / W2 C'
                )
            ),
            array(
                'line'              => __LINE__,
                'workflows'         => array('w1', 'w4'),
                'expectedStates'    => array()
            ),
            array(
                'line'              => __LINE__,
                'workflows'         => array('w2', 'w3'),
                'expectedStates'    => array(
                    'b' => 'B / W3 B',
                    'x' => 'W2 X / W3 X'
                )
            ),
            array(
                'line'              => __LINE__,
                'workflows'         => array('w2', 'w3', 'w4'),
                'expectedStates'    => array(
                    'x' => 'W2 X / W3 X / W4 X'
                )
            )
        );

        // run tests
        foreach ($tests as $test) {
            $form = new Workflow_Form_ManageContent(array('workflows' => $test['workflows']));
            $this->assertSame(
                $test['expectedStates'],
                $form->getStateOptions(),
                "Unexpected state options for test on line: " . $test['line']
            );
        }
    }
}