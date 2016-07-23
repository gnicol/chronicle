<?php
/**
 * Test the workflow state model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Test_StateTest extends ModuleTest
{
    /**
     * Test accessors.
     */
    public function testAccessors()
    {
        $workflowData = array(
            'id'            => 'workflow_test',
            'label'         => 'test Workflow',
            'states'        => array(
                'test'          => array(
                    'label'         => 'test label',
                    'transitions'   => array(
                        'foo'       => array('label' => 'promote to foo'),
                        'bar'       => array('label' => 'promote to bar'),
                        'baz'       => array('label' => 'promote to baz')
                    )
                ),
                'foo'           => array(
                    'label'         => 'foo state',
                ),
                'bar'           => array(
                    'label'         => 'bar state',
                ),
                'baz'           => array(
                    'label'         => 'baz state',
                )
            )
        );
        $workflow = Workflow_Model_Workflow::store($workflowData);

        // get state object
        $state = $workflow->getStateModel('test');

        $this->assertSame(
            'test',
            $state->getId(),
            "Expected state id."
        );
        $this->assertSame(
            'test label',
            $state->getLabel(),
            "Expected state label name."
        );
        $this->assertSame(
            $workflowData['states']['test']['transitions'],
            $state->getTransitions(),
            "Expected state transitions."
        );
        $this->assertTrue(
            $state->getWorkflow() instanceof Workflow_Model_Workflow,
            "Expected state workflow class type."
        );

        // getTransitionModels() should return iterator with transition objects
        $transitions = $state->getTransitionModels();
        $this->assertTrue(
            $transitions instanceof P4Cms_Model_Iterator,
            "Expected class type returned by getTransitions() method."
        );
        $this->assertEquals(
            3,
            $transitions->count(),
            "Expected number of state transitions."
        );

        foreach ($transitions as $transition) {
            $this->assertTrue(
                $transition instanceof Workflow_Model_Transition,
                "Expected class type of state transition item."
            );
        }

        // fetch transition
        $this->assertSame(
            array('label' => 'promote to bar'),
            $state->getTransition('bar'),
            "Expected empty array when fetch bar transition."
        );
        $this->assertSame(
            array('label' => 'promote to baz'),
            $state->getTransition('baz'),
            "Expected empty array when fetch baz transition."
        );

        // fetch transition model
        $this->assertSame(
            'promote to bar',
            $state->getTransitionModel('bar')->getlabel(),
            "Expected value when fetch bar transition model."
        );
        $this->assertSame(
            'promote to baz',
            $state->getTransitionModel('baz')->getlabel(),
            "Expected value when fetch baz transition model."
        );

        // verify getTransition() throws exception if transition doesn't exist
        try {
            $state->getTransition('noexist');
            $this->fail("Unexpected getTransition() method behavior - should throw an exception.");
        } catch (Workflow_Exception $e) {
            // expected exception
        }

        // verify that getWorkflow() throws an exception if workflow is not set
        $state = Workflow_Model_State::create(
            array(
                'label'         => 'test state',
                'transitions'   => array(
                    'foo'       => array('label' => 'promote to foo'),
                    'bar'       => array('label' => 'promote to bar')
                )
            )
        );

        try {
            $state->getWorkflow();
            $this->fail("Unexpected getWorkflow() method behavior - should throw an exception.");
        } catch (Workflow_Exception $e) {
            // expected exception
        }

        // verify that getTransitions() return only transitions that have valid target state
        $workflow = Workflow_Model_Workflow::create(
            array(
                'states' => array('bar' => array())
            )
        );

        $state->setWorkflow($workflow);
        $this->assertSame(
            array('bar'),
            array_keys($state->getTransitions()),
            "Expected no transiitons as foo is not a valid state of the associated workflow."
        );
    }

    /**
     * Tets hasTransition() method.
     */
    public function testHasTransition()
    {
        $workflow = new Workflow_Model_Workflow;
        $state    = Workflow_Model_State::create(
            array(
                'label'         => 'foo',
                'transitions'   => array(
                    'foo'       => array('label' => 'bar'),
                    'bar'       => array('label' => 'foo')
                ),
                'gg'            => array(
                    'ff'
                ),
                'workflow'      => $workflow
            )
        );

        // hasTransition considers only valid transitions
        $this->assertFalse(
            $state->hasTransition('foo'),
            "Expected state has no transition 'foo' as 'foo' is not defined by the governing workflow."
        );

        $workflow->setStates(array('foo' => array(), 'bar' => array()));
        $this->assertTrue(
            $state->hasTransition('foo'),
            "Expected state has transition 'foo'."
        );
        $this->assertTrue(
            $state->hasTransition('bar'),
            "Expected state has transition 'bar'."
        );
        $this->assertFalse(
            $state->hasTransition('foobar'),
            "Expected state has no transition 'foobar'."
        );
    }
}