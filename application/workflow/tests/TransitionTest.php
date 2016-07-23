<?php
/**
 * Test the workflow state model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Test_TransitionTest extends ModuleTest
{
    /**
     * Test get(id|label|worfklow) methods.
     * Other accessors are tested in separate functions.
     */
    public function testAccessors()
    {
        $workflow   = new Workflow_Model_Workflow;
        $transition = new Workflow_Model_Transition(
            array(
                'label'     => 'sample transition',
                'fromState' => Workflow_Model_State::create(
                    array('workflow' => $workflow, 'label' => 'foo')
                ),
                'toState'   => Workflow_Model_State::create(
                    array('workflow' => $workflow, 'label' => 'bar')
                )
            )
        );

        // id
        $this->assertSame(
            null,
            $transition->getId(),
            "Expected transition id."
        );

        // label
        $this->assertSame(
            'sample transition',
            $transition->getLabel(),
            "Expected transition label name."
        );

        // workflow
        $this->assertSame(
            $workflow,
            $transition->getWorkflow(),
            "Expected transition workflow."
        );

        // ensure exception is thrown if try to access workflow that has not been set
        $transitionNoWorkflow = new Workflow_Model_Transition(array('label' => 'foo'));
        try {
            $transitionNoWorkflow->getWorkflow();
            $this->fail("Unexpected success when getting non-existing workflow");
        } catch (Workflow_Exception $e) {
            // expected exception
        }                
    }

    /**
     * Test areConditionsMetFor() method.
     */
    public function testAreConditionsMetFor()
    {
        // create records for conditions context
        $recordA = P4Cms_Record::create(
            array(
                'field1' => 'this field has bogus mime type',
                'field2' => 'abc123 foo bar',
            )
        );
        $recordA->setFieldMetadata('field1', array('type' => 'foo', 'mimeType' => 'bogus'));

        $recordB = P4Cms_Record::create(
            array(
                'field1' => 'this field has text mime type',
                'fieldY' => 'xyz123 baz',
                'fieldZ' => 'qwerty baz'
            )
        );
        $recordB->setFieldMetadata('field1', array('mimeType' => 'text/ascii'));

        $recordC = P4Cms_Record::create(
            array(
                'dim'     => '100x200 foo units',
            )
        );

        // define tests
        $tests = array(
            array(
                'conditions'        => array(),
                'expectedRecords'   => array('a', 'b', 'c'),
                'message'           => __LINE__ . ': no conditions'
            ),
            array(
                'conditions'        => array(
                    'contains'      => array(
                        'string'    => 'field'
                    )
                ),
                'expectedRecords'   => array('b'),
                'message'           => __LINE__ . ': single contains condition'
            ),
            array(
                'conditions'        => array(
                    'contains'      => array(
                        'string'    => '1'
                    ),
                    'contains'      => array(
                        'string'    => 'foo'
                    )
                ),
                'expectedRecords'   => array('a', 'c'),
                'message'           => __LINE__ . ': two contains conditions'
            ),
            array(
                'conditions'        => array(
                    'contains'      => array(
                        'pattern'   => '/./'
                    ),
                    'false'
                ),
                'expectedRecords'   => array(),
                'message'           => __LINE__ . ': false and contains conditions'
            ),
            array(
                'conditions'        => array(
                    'contains'      => array(
                        'pattern'   => '/./'
                    ),
                    'false'         => array(
                        'negate'    => true
                    )
                ),
                'expectedRecords'   => array('a', 'b', 'c'),
                'message'           => __LINE__ . ': negate false and contains conditions'
            ),
            array(
                'conditions'        => array(
                    'contains'      => array(
                        'pattern'   => '/\dx/'
                    )
                ),
                'expectedRecords'   => array('c'),
                'message'           => __LINE__ . ': regex contains condition'
            ),
            array(
                'conditions'        => array(
                    'contains'      => array(
                        'pattern'   => '/./',
                        'fields'    => array('dim', 'fieldY', 'fieldZ', 'fieldA')
                    ),
                    'contains'      => array(
                        'string'    => 't',
                        'fields'    => array('dim', 'field*')
                    )
                ),
                'expectedRecords'   => array('c'),
                'message'           => __LINE__ . ': two contains condition with literal/regex patterns'
            )
        );

        // create records array for the runner
        $records = array('a' => $recordA, 'b' => $recordB, 'c' => $recordC);

        // run tests
        foreach ($tests as $test) {
            $transition = new Workflow_Model_Transition(array('conditions' => $test['conditions']));
            $this->assertSame(
                $test['expectedRecords'],
                $this->_recordsTransitionRunner($records, $transition),
                'Line ' . $test['message']
            );
        }

        // test publish content permission
        $workflow = Workflow_Model_Workflow::create(
            array(
                'states'    => array(
                    'foo'   => array(
                        'transitions'   => array(
                            'published' => array(
                                'label' => 'publish'
                            )
                        )
                    ),
                    'published'         => array(),
                )
            )
        );
        $transition = $workflow->getStateModel('foo')->getTransitionModel('published');

        // verify that publishing a content is denied if no active user
        P4Cms_User::clearActive();
        $this->assertFalse(
            $transition->areConditionsMetFor(new P4Cms_Content),
            "Expected false value if no active user."
        );

        // verify that publishing a content is denied if active user has not publish content permission
        $joe = P4Cms_User::create(
            array(
                'id'        => 'joe',
                'fullName'  => 'Mr Joe',
                'email'     => 'joe@email.com'
            )
        )->save();
        $acl = P4Cms_Acl::fetchActive();
        $acl->installDefaults()->save();
        $role = P4Cms_Acl_Role::create(array('id' => 'foo', 'users' => array('joe')))->save();
        $acl->addRole($role);
        P4Cms_User::setActive($joe);

        $this->assertFalse(
            $transition->areConditionsMetFor(new P4Cms_Content),
            "Expected false value if active user doesn't have permission to publish a content."
        );
        // verify that publish content permission is evaluated only for content records
        $this->assertTrue(
            $transition->areConditionsMetFor(new P4Cms_Record),
            "Expected true value for generic records."
        );

        // add publish content permission to the user and verify that conditions are met
        $acl->allow($role, 'content', 'publish');
        $this->assertTrue(
            $transition->areConditionsMetFor(new P4Cms_Content),
            "Expected true value if active user has permission to publish a content."
        );
    }

    /**
     * Test getting conditions from a transition object.
     */
    public function testGetConditions()
    {
        $transition = new Workflow_Model_Transition(
            array(
                'conditions' => array(
                    'bogus',
                    'false',
                    'false'     => array('foo' => 'bar'),
                    'other'     => array('condition' => 'false', 'foo' => 'bar'),
                    'another'   => array(
                        'condition' => 'false',
                        'foo'       => 'bar',
                        'options'   => array('foo' => 'baz')
                    )
                )
            )
        );

        $conditions = $transition->getConditions();
        $this->assertTrue(is_array($conditions));
        $this->assertSame(4, count($conditions));

        // ensure all conditions are condition objects.
        foreach ($conditions as $condition) {
            $this->assertTrue($condition instanceof Workflow_ConditionInterface);
        }

        // verify options were loaded correctly.
        $this->assertSame(array(), $conditions[0]->getOptions());
        $this->assertSame(array('foo' => 'bar'), $conditions[1]->getOptions());
        $this->assertSame(array('foo' => 'bar'), $conditions[2]->getOptions());
        $this->assertSame(array('foo' => 'baz'), $conditions[3]->getOptions());
    }

    /**
     * Test getting actions from a transition object.
     */
    public function testGetActions()
    {
        $transition = new Workflow_Model_Transition(
            array(
                'actions' => array(
                    'bogus',
                    'noop',
                    'noop'       => array('foo' => 'bar'),
                    'other'      => array('action' => 'noop', 'foo' => 'bar'),
                    'another'    => array(
                        'action'    => 'noop',
                        'foo'       => 'bar',
                        'options'   => array('foo' => 'baz')
                    )
                )
            )
        );

        $actions = $transition->getActions();
        $this->assertTrue(is_array($actions));
        $this->assertSame(4, count($actions));

        // ensure all actions are action objects.
        foreach ($actions as $action) {
            $this->assertTrue($action instanceof Workflow_ActionInterface);
        }

        // verify options were loaded correctly.
        $this->assertSame(array(), $actions[0]->getOptions());
        $this->assertSame(array('foo' => 'bar'), $actions[1]->getOptions());
        $this->assertSame(array('foo' => 'bar'), $actions[2]->getOptions());
        $this->assertSame(array('foo' => 'baz'), $actions[3]->getOptions());
    }

    /**
     * Test invokeActions() method.
     */
    public function testInvokeActionsOn()
    {
        // define test suite (test with sendEmail action)
        $tests = array(
            array(
                'actions'           => array(),
                'expected'          => array(
                    'emailsSent'    => 0
                ),
                'message'           => __LINE__ . ': no actions'
            ),
            array(
                'actions'           => array(
                    'email'         => array(
                        'action'    => 'sendEmail',
                        'noize'     => array(),
                        'options'   => array()
                    )
                ),
                'expected'          => array(
                    'emailsSent'    => 0
                ),
                'message'           => __LINE__ . ': no recipient set'
            ),
            array(
                'actions'           => array(
                    'email'         => array(
                        'action'    => 'sendEmail',
                        'noize'     => 'foo-bar-baz',
                        'options'   => array('to' => 'foo@email.com')
                    )                    
                ),
                'expected'          => array(
                    'emailsSent'    => 1,
                    'recipients'    => array('foo@email.com')
                ),
                'message'           => __LINE__ . ': 1 email'
            ),
            array(
                'actions'           => array(
                    'email1'        => array(
                        'action'    => 'sendEmail',
                        'noize'     => '123abc',
                        'options'   => array('to' => array('foo@email.com', 'bar@email.com'))
                    ),
                    'email2'        => array(
                        'action'    => 'sendEmail',
                        'noize'     => 'xyz',
                        'options'   => array('to' => 'abc@xyz.com')
                    )
                ),
                'expected'          => array(
                    'emailsSent'    => 2,
                    'recipients'    => array('foo@email.com,bar@email.com', 'abc@xyz.com')
                ),
                'message'           => __LINE__ . ': 2 emails'
            ),
            array(
                'actions'           => array(
                    'email1'        => array(
                        'action'    => 'sendEmail',
                        'noize'     => null,
                        'options'   => array('to' => array('foo@abc.com'))
                    ),
                    'email2'        => array(
                        'action'    => 'sendEmail',
                        'noize'     => 'xyz',
                        'options'   => array()
                    )
                ),
                'expected'          => array(
                    'emailsSent'    => 1,
                    'recipients'    => array('foo@abc.com')
                ),
                'message'           => __LINE__ . ': 2 emails, but 1 missing recipient'
            ),
        );

        // set custom mail transport to actually not sent any real emails, but
        // check emails registered by sent() action instead
        $transport = new Workflow_Test_MailTransport;
        Zend_Mail::setDefaultTransport($transport);

        // prepare workflow transition model
        $workflow   = new Workflow_Model_Workflow;
        $transition = new Workflow_Model_Transition(
            array(
                'fromState' => Workflow_Model_State::create(
                    array('workflow' => $workflow, 'label' => 'foo')
                ),
                'toState' => Workflow_Model_State::create(
                    array('workflow' => $workflow, 'label' => 'bar')
                )
            )
        );        

        // run tests
        foreach ($tests as $test) {
            $transport->reset();
            $transition->setValue('actions', $test['actions']);
            $transition->invokeActionsOn(new P4Cms_Record);

            // get sent emails register
            $sentEmails = $transport->getSentMails();

            // check number of emails sent
            $this->assertSame(
                $test['expected']['emailsSent'],
                count($sentEmails),
                "Expected number of sent emails."
            );

            // if there were emails sent, check recipients
            if (count($sentEmails)) {
                foreach (array_values($sentEmails) as $index => $email) {
                    $this->assertSame(
                        $test['expected']['recipients'][$index],
                        $email['to'],
                        "Expected email recipient match. Line: " . $test['message']
                    );
                }
            }
        }
    }

    /**
     * Test (setFrom|getFrom|setTo|getTo)State() methods.
     */
    public function testSetGetFromToState()
    {
        $transition = new Workflow_Model_Transition;
        $workflow   = new Workflow_Model_Workflow;
        $fromState  = Workflow_Model_State::create(
            array(
                'label'     => 'from',
                'workflow'  => $workflow
            )
        );
        $toState    = Workflow_Model_State::create(
            array(
                'label'     => 'to',
                'workflow'  => $workflow
            )
        );

        // set/get from state
        $transition->setFromState($fromState);
        $this->assertSame(
            $fromState,
            $transition->getFromState(),
            "Expected transition from state."
        );

        // set/get to state
        $transition->setToState($toState);
        $this->assertSame(
            $toState,
            $transition->getToState(),
            "Expected transition to state."
        );

        // try to set from/to state governed by a different workflow
        try {
            $transition->setToState(
                Workflow_Model_State::create(
                    array(
                        'label'     => 'to',
                        'workflow'  => new Workflow_Model_Workflow
                    )
                )
            );
            $this->fail("Unexpected transition creation - should throw an exception.");
        } catch (Workflow_Exception $e) {
            // expected exception
        }
        try {
            $transition->setFromState(
                Workflow_Model_State::create(
                    array(
                        'label'     => 'from',
                        'workflow'  => new Workflow_Model_Workflow
                    )
                )
            );
            $this->fail("Unexpected transition creation - should throw an exception.");
        } catch (Workflow_Exception $e) {
            // expected exception
        }

        // test exceptions when accessing non-existing states
        $transition = new Workflow_Model_Transition(
            array(
                'toState' => $toState
            )
        );
        try {
            $transition->getFromState();
            $this->fail("Unexpected success when getting non-existing from state");
        } catch (Workflow_Exception $e) {
            // expected exception
        }

        $transition = new Workflow_Model_Transition(
            array(
                'fromState' => $fromState
            )
        );
        try {
            $transition->getToState();
            $this->fail("Unexpected success when getting non-existing to state");
        } catch (Workflow_Exception $e) {
            // expected exception
        }
    }

    /**
     * Helper function to filter records array by allowed transitions evaluated in
     * their context.
     * Returns array with keys of records where conditions are met for given transition
     * in record context.
     *
     * @param   array                       $records        records to filter by condition.
     * @param   Workflow_Model_Transition   $transition     transition to evaluate in record context.
     * @return  array                       filtered array with records allowed by transtion.
     */
    protected function _recordsTransitionRunner(array $records, Workflow_Model_Transition $transition)
    {
        $filteredRecords = array_filter(
            $records,
            function ($record) use ($transition)
            {
                return $transition->areConditionsMetFor($record);
            }
        );

        return array_keys($filteredRecords);
    }
}