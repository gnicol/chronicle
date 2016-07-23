<?php
/**
 * Test the workflow model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Test_WorkflowTest extends ModuleTest
{
    /**
     * Test accessors.
     */
    public function testAccessors()
    {
        // add workflow record
        $modelData = array(
            'id'            => "test",
            'label'         => "test label",
            'description'   => "test description",
            'states'        => "[foo]\nlabel=foo state\n[bar]\nlabel=bar state"
        );
        Workflow_Model_Workflow::store($modelData);

        // fetch saved workflow
        $workflow = Workflow_Model_Workflow::fetch('test');

        $this->assertSame(
            $modelData['label'],
            $workflow->getLabel(),
            "Expected workflow label name."
        );
        $this->assertSame(
            $modelData['description'],
            $workflow->getDescription(),
            "Expected workflow description."
        );
        $this->assertSame(
            $modelData['states'],
            $workflow->getStatesAsIni(),
            "Expected workflow states in INI."
        );

        // getStates() should return array of state definitions
        $states = $workflow->getStates();
        $this->assertTrue(
            is_array($states),
            "Expected array returned by getState() method."
        );

        // getStateModels() should return iterator with workflow state objects
        $states = $workflow->getStateModels();
        $this->assertTrue(
            $states instanceof P4Cms_Model_Iterator,
            "Expected class type returned by getState() method."
        );

        $this->assertEquals(
            2,
            $states->count(),
            "Expected number of workflow states."
        );

        foreach ($states as $state) {
            $this->assertTrue(
                $state instanceof Workflow_Model_State,
                "Expected class type of workflow state item."
            );
        }
    }

    /**
     * Test mutators.
     */
    public function testMutators()
    {
        $workflow = new Workflow_Model_Workflow(array('id' => 'foo'));
        $workflow->setLabel('foo label')
                 ->setDescription('foo description')
                 ->save();

        // fetch and verify values
        $values = Workflow_Model_Workflow::fetch('foo')->getValues();
        $this->assertSame(
            'foo',
            $values['id'],
            "Expected workflow id."
        );
        $this->assertSame(
            'foo label',
            $values['label'],
            "Expected workflow label."
        );
        $this->assertSame(
            'foo description',
            $values['description'],
            "Expected workflow description."
        );
        $this->assertTrue(
            is_array($values['states']),
            "Expected type of states field #1."
        );

        // set states as string in INI format
        $workflow->setStates("[bar]\nlabel=test")
                 ->save();

        // fetch and verify states
        $values = Workflow_Model_Workflow::fetch('foo')->getValues();
        $this->assertTrue(
            is_array($values['states']),
            "Expected type of states field #2."
        );
        $this->assertEquals(
            1,
            count($values['states']),
            "Expected number of workflow states #2."
        );

        // verify that workflow state has reference to workflow
        $state = $workflow->getStateModels()->current();
        $this->assertTrue(
            $state->getValue('workflow') instanceof Workflow_Model_Workflow,
            "Expected type of state workflow field #2."
        );
        $this->assertSame(
            'foo',
            $state->getValue('workflow')->getId(),
            "Expected workflow state references correct workflow instance #2."
        );

        // set states as null
        $workflow->setStates(null)
                 ->save();

        // fetch and verify states
        $values = Workflow_Model_Workflow::fetch('foo')->getValues();
        $this->assertTrue(
            is_array($values['states']),
            "Expected type of states field."
        );
        $this->assertSame(
            array(),
            $values['states'],
            "Expected no states."
        );

        // set states as array
        $statesArray = array(
            'bar' => array(
                'label' => 'bar state',
                'foo'   => 'temp'
            ),
            'baz' => array(
                'label' => 'bazz'
            )
        );
        $workflow->setStates($statesArray)
                 ->save();

        // fetch and verify states
        $values = Workflow_Model_Workflow::fetch('foo')->getValues();
        $this->assertTrue(
            is_array($values['states']),
            "Expected type of states field #3."
        );
        $this->assertEquals(
            2,
            count($values['states']),
            "Expected number of workflow states #3."
        );

        // verify that workflow states have reference to workflow
        foreach ($workflow->getStateModels() as $state) {
            $this->assertTrue(
                $state->getValue('workflow') instanceof Workflow_Model_Workflow,
                "Expected type of state workflow field #3 ({$state->getId()})."
            );
            $this->assertSame(
                'foo',
                $state->getValue('workflow')->getId(),
                "Expected workflow state references correct workflow instance #3 ({$state->getId()})."
            );
        }
    }

    /**
     * Test hasState()/getState()/getStateModel() methods.
     */
    public function testGetState()
    {
        Workflow_Model_Workflow::store(
            array(
                'id'            => "test",
                'label'         => "test label",
                'description'   => "test description",
                'states'        => "[foo]\na=b\n[bar]a.1=c\na.2=de\nx=yz"
            )
        );

        $workflow = Workflow_Model_Workflow::fetch('test');

        $this->assertTrue(
            $workflow->hasState('foo'),
            "Expected 'foo' is one of the workflow states."
        );
        $this->assertSame(
            array('a' => 'b'),
            $workflow->getState('foo'),
            "Expected value returned for getState('foo')."
        );

        $this->assertTrue(
            $workflow->getStateModel('foo') instanceof Workflow_Model_State,
            "Expected class type of 'foo' state model."
        );

        $this->assertTrue(
            $workflow->hasState('bar'),
            "Expected 'bar' is one of the workflow states."
        );
        $this->assertSame(
            array('a' => array (1 => 'c', 2 => 'de'), 'x' => 'yz'),
            $workflow->getState('bar'),
            "Expected value returned for getState('bar')."
        );

        $this->assertTrue(
            $workflow->getStateModel('bar') instanceof Workflow_Model_State,
            "Expected class type of 'bar' state model."
        );

        $this->assertFalse(
            $workflow->hasState('noexist'),
            "Expected 'noexist' is not one of the workflow states."
        );

        try {
            $workflow->getState('noexist');
            $this->fail('Expected throwing an exception when tried to get non-existent state.');
        } catch (Workflow_Exception $e) {
            // expected exception
        }

        try {
            $workflow->getStateModel('noexist');
            $this->fail('Expected throwing an exception when tried to get non-existent state model.');
        } catch (Workflow_Exception $e) {
            // expected exception
        }
    }

    /**
     * Test getDefaultState() method.
     */
    public function testGetDefaultState()
    {
        Workflow_Model_Workflow::store(
            array(
                'id'            => "test1",
                'label'         => "test label",
                'states'        => "[first]\n[second]\n[third]\n"
            )
        );

        $defaultState = Workflow_Model_Workflow::fetch('test1')->getDefaultState();
        $this->assertTrue(
            $defaultState instanceof Workflow_Model_State,
            "Expected class type of default state #1."
        );
        $this->assertSame(
            'first',
            $defaultState->getId(),
            "Expected id of the default state #1."
        );

        Workflow_Model_Workflow::store(
            array(
                'id'            => "test2",
                'label'         => "test label",
                'description'   => "test description",
                'states'        => "[one]\ntwo=2\n[two]one=1\ntwo=2"
            )
        );

        $defaultState = Workflow_Model_Workflow::fetch('test2')->getDefaultState();
        $this->assertTrue(
            $defaultState instanceof Workflow_Model_State,
            "Expected class type of default state #2."
        );
        $this->assertSame(
            'one',
            $defaultState->getId(),
            "Expected id of the default state #2."
        );
    }

    /**
     * Test installation and removal of default workflows.
     */
    public function testInstallRemoveDefaults()
    {
        $this->assertSame(
            0,
            Workflow_Model_Workflow::count(),
            "Expected no workflows yet."
        );

        $package = P4Cms_Module::fetch('workflow');
        Workflow_Model_Workflow::installPackageDefaults($package);

        $this->assertSame(
            1,
            Workflow_Model_Workflow::count(),
            "Expected one workflow."
        );

        // ensure workflow has id 'simple'.
        $this->assertTrue(Workflow_Model_Workflow::exists('simple'));

        // verify installed workflow has three states:
        // Draft, Review, Published
        $workflow = Workflow_Model_Workflow::fetch('simple');
        $this->assertSame(
            $workflow->getStateModels()->invoke('getLabel'),
            array('Draft', 'Review', 'Published')
        );

        // remove defaults.
        Workflow_Model_Workflow::removePackageDefaults($package);
        $this->assertFalse(Workflow_Model_Workflow::exists('simple'));

        // ensure workflow is not removed if it was edited.
        Workflow_Model_Workflow::installPackageDefaults($package);
        $workflow = Workflow_Model_Workflow::fetch('simple');
        $workflow->setLabel('modified')->save();
        Workflow_Model_Workflow::removePackageDefaults($package);
        $this->assertTrue(Workflow_Model_Workflow::exists('simple'));
    }

    /**
     * Verify fetchTypeMap() method outputs correct content-type => workflow mapping.
     */
    public function testFetchTypeMap()
    {
        // add few workflows
        $workflowX = Workflow_Model_Workflow::store(
            array(
                'id'            => "workflow-x",
                'label'         => "test x",
                'states'        => "[foo]\na=b"
            )
        );
        $workflowY = Workflow_Model_Workflow::store(
            array(
                'id'            => "workflow-y",
                'label'         => "test y",
                'states'        => "[foo]\na=b"
            )
        );

        // add few content types
        P4Cms_Content_Type::store(
            array(
                'id'        => "type-a",
                'elements'  => "[id]\ntype=text",
                'workflow'  => "workflow-x"
            )
        );
        P4Cms_Content_Type::store(
            array(
                'id'        => "type-b",
                'elements'  => "[id]\ntype=text"
            )
        );
        P4Cms_Content_Type::store(
            array(
                'id'        => "type-c",
                'elements'  => "[id]\ntype=text",
                'workflow'  => "workflow-y"
            )
        );
        P4Cms_Content_Type::store(
            array(
                'id'        => "type-d",
                'elements'  => "[id]\ntype=text",
                'workflow'  => "workflow-x"
            )
        );

        $map      = Workflow_Model_Workflow::fetchTypeMap();
        $expected = array(
            'type-a' => $workflowX->toArray(),
            'type-c' => $workflowY->toArray(),
            'type-d' => $workflowX->toArray(),
        );

        $this->assertTrue(
            $map instanceof P4Cms_Model_Iterator,
            "Expected map instance type."
        );

        $this->assertSame(
            $expected,
            $map->toArray(),
            "Expected map array structure."
        );
    }

    /**
     * Test detectTransitionOn() method.
     */
    public function testDetectTransitionOn()
    {
        // create workflow with states a, b, c allowing following transitions:
        // a->b, a->c, b->c, c->a
        $workflow = Workflow_Model_Workflow::create(
            $this->_generateWorkflowDefinition(
                array(
                    'a' => array('b', 'c'),
                    'b' => array('c'),
                    'c' => array('a')
                )
            )
        );

        // create content type
        $type = P4Cms_Content_Type::store(
            array(
                'id'        => 'ct',
                'elements'  => array(
                    'title' => array('type' => 'text')
                )
            )
        );

        // define tests suite
        $tests = array(
            array(
                'savedValues'           => array(
                    'contentType'       => $type->getId(),
                ),
                'changedValues'         => array(
                    'workflowState'     => 'a'
                ),
                'expected'              => null,
                'message'               => __LINE__ . ": invalid from state, valid to state."
            ),
            array(
                'savedValues'           => array(
                    'contentType'       => $type->getId(),
                    'workflowState'     => 'a'
                ),
                'changedValues'         => array(
                    'workflowState'     => 'unknown'
                ),
                'expected'              => null,
                'message'               => __LINE__ . ": valid from state, invalid to state."
            ),
            array(
                'savedValues'           => array(
                    'contentType'       => $type->getId(),
                    'workflowState'     => 'notvalid'
                ),
                'changedValues'         => array(
                    'workflowState'     => 'unknown'
                ),
                'expected'              => null,
                'message'               => __LINE__ . ": invalid from state, invalid to state."
            ),
            array(
                'savedValues'           => array(
                    'contentType'       => $type->getId(),
                    'workflowState'     => 'a'
                ),
                'changedValues'         => array(
                    'workflowState'     => 'b'
                ),
                'expected'              => 'a to b',
                'message'               => __LINE__ . ": valid transiton a -> b."
            ),
            array(
                'savedValues'           => array(
                    'contentType'       => $type->getId(),
                    'workflowState'     => 'a'
                ),
                'changedValues'         => array(
                    'workflowState'     => 'a'
                ),
                'expected'              => null,
                'message'               => __LINE__ . ": same from- and to states."
            )
        );

        // run tests
        foreach ($tests as $test) {
            // create content
            $content = P4Cms_Content::store($test['savedValues']);

            // modify record values and save while in batch
            $adapter = $content->getAdapter();
            $adapter->beginBatch('test');
            $content->setValues($test['changedValues']);
            $content->save();

            // detect transition
            $transition = $workflow->detectTransitionOn($content);

            // get transition label if transition is object
            if ($transition instanceof Workflow_Model_Transition) {
                $transition = $transition->getLabel();
            }

            // verify transition
            $this->assertSame(
                $test['expected'],
                $transition,
                $test['message']
            );

            $adapter->revertBatch();
        }
    }

    /**
     * Test (set|get)StateOf() methods.
     */
    public function testGetSetStateOf()
    {
        // create workflow states a,b,c and with transitions a->b, a->c, b->c
        $workflow = Workflow_Model_Workflow::create(
            $this->_generateWorkflowDefinition(
                array(
                    'a' => array('b', 'c'),
                    'b' => array('c'),
                    'c' => array()
                )
            )
        );

        // create content type
        $type = P4Cms_Content_Type::store(
            array(
                'id'        => 'test',
                'elements'  => array(
                    'title' => array('type' => 'text')
                )
            )
        );

        $record = P4Cms_Content::create(
            array(
                'contentType'   => 'test',
                'workflowState' => 'b'
            )
        );

        // ensure exception is thrown when try to set invalid state
        try {
            $workflow->setStateOf($record, 'none');
        } catch (Workflow_Exception $e) {
            // expected exception
            $this->assertSame(
                "Cannot set state on the given record. State is undefined or governed by other workflow.",
                $e->getMessage(),
                "Expected invalid state exception"
            );
        } catch (Exception $e) {
            $this->fail("Unexpected exception thrown.");
        }

        $this->assertSame(
            'b',
            $workflow->getStateOf($record)->getId(),
            "Expected current state."
        );

        // ensure exception is thrown when invalid transition
        try {
            $workflow->setStateOf($record, 'a');
        } catch (Workflow_Exception $e) {
            // expected exception
            $this->assertSame(
                "Cannot set state on the given record. Not a valid transition.",
                $e->getMessage(),
                "Expected invalid transition exception"
            );
        } catch (Exception $e) {
            $this->fail("Unexpected exception thrown.");
        }

        $this->assertSame(
            'b',
            $workflow->getStateOf($record)->getId(),
            "Expected current state."
        );

        // set valid state
        $workflow->setStateOf($record, 'c');
        $this->assertSame(
            'c',
            $workflow->getStateOf($record)->getId(),
            "Expected current state."
        );
    }

    /**
     * Test getters/setters for manipulation with scheduled data.
     */
    public function testGetSetScheduledData()
    {
        $this->utility->impersonate('editor');

        // create workflow states a,b,c and with transitions a->b, a->c, b->c
        $workflow = Workflow_Model_Workflow::create(
            $this->_generateWorkflowDefinition(
                array(
                    'a' => array('b', 'c'),
                    'b' => array('c'),
                    'c' => array()
                )
            )
        );

        $record = P4Cms_Content::store(
            array(
                'id'            => 1,
                'contentType'   => 'test',
                'workflowState' => 'a'
            )
        );

        // ensure exception is thrown when try to set invalid scheduled state
        try {
            $workflow->setScheduledStateOf($record, 'none', 123);
        } catch (Workflow_Exception $e) {
            // expected exception
            $this->assertSame(
                "Cannot set state on the given record. State is undefined or governed by other workflow.",
                $e->getMessage(),
                "Expected invalid state exception"
            );
        } catch (Exception $e) {
            $this->fail("Unexpected exception thrown.");
        }

        // prepare timestamp points to the future
        $time = time() + 10;

        // ensure exception is thrown when timestamp has invalid format
        try {
            $workflow->setScheduledStateOf($record, 'b', "$time");
        } catch (InvalidArgumentException $e) {
            // expected exception
            $this->assertSame(
                "Cannot schedule transition. Time must be an integer timestamp in the future.",
                $e->getMessage(),
                "Expected invalid timestamp exception"
            );
        } catch (Exception $e) {
            $this->fail("Unexpected exception thrown.");
        }

        try {
            $workflow->setScheduledStateOf($record, 'b', -1);
        } catch (InvalidArgumentException $e) {
            // expected exception
            $this->assertSame(
                "Cannot schedule transition. Time must be an integer timestamp in the future.",
                $e->getMessage(),
                "Expected invalid timestamp exception"
            );
        } catch (Exception $e) {
            $this->fail("Unexpected exception thrown.");
        }

        // set valid scheduled transition
        $workflow->setScheduledStateOf($record, 'b', $time);
        $record->save();

        $record->fetch(1);
        $this->assertSame('a', $workflow->getStateOf($record)->getId(), 'Expected current state');
        $this->assertSame('b', $workflow->getScheduledStateOf($record)->getId(), 'Expected scheduled state');
        $this->assertSame($time, $workflow->getScheduledTimeOf($record), 'Expected scheduled time');
    }

    /**
     * Test for makeScheduledContentFilter() method.
     */
    public function testMakeScheduledContentFilter()
    {
        // these are unpublished entries, impersonate an editor so we can see them
        $this->utility->impersonate('editor');

        // create testing entries
        // +---------------+--------------+-----------------+-------------------+
        // | CONTENT TITLE | STATE        | SCHEDULED STATE | SCHEDULED TIME    |
        // +---------------+--------------+-----------------+-------------------+
        // | a             | draft        | review          |  1. 1.2010 12:00  |
        // | b             | draft        | published       |  1. 2.2010 15:00  |
        // | c             | draft        | review          |  1. 1.2011 12:00  |
        // | d             | draft        | -               | 31.12.2009 23:00  |
        // | e             | draft        | published       |                -  |
        // | f             | draft        | -               |                -  |
        // | g             | draft        | review          |  2. 1.2011 12:00  |
        // | h             | -            | published       |  2. 1.2012 12:00  |
        // +---------------+--------------+-----------------+-------------------+

        $entries = array(
            array('a', 'draft', 'review',    strtotime('2010-01-01 12:00')),
            array('b', 'draft', 'published', strtotime('2010-02-01 15:00')),
            array('c', 'draft', 'review',    strtotime('2011-01-01 12:00')),
            array('d', 'draft', '',          strtotime('2009-12-31 23:00')),
            array('e', 'draft', 'published', ''),
            array('f', 'draft', '',          ''),
            array('g', 'draft', 'review',    strtotime('2011-01-02 12:00')),
            array('h', '',      'published', strtotime('2012-01-02 12:00')),
        );

        // make a test workflow and type
        $workflow = Workflow_Model_Workflow::store(
            $this->_generateWorkflowDefinition(
                array('draft' => array('published'), 'published' => array('draft'))
            )
        );
        $type = new P4Cms_Content_Type;
        $type->setId('basic')
             ->setValue('workflow', $workflow->getId())
             ->save();

        foreach ($entries as $entry) {
            P4Cms_Content::store(
                array(
                    'contentType'            => 'basic',
                    'title'                  => $entry[0],
                    'workflowState'          => $entry[1],
                    'workflowScheduledState' => $entry[2],
                    'workflowScheduledTime'  => $entry[3]
                )
            );
        }

        // get all contents with scheduled transitions before 1999-01-01 00:00
        $query = P4Cms_Record_Query::create()
            ->addFilter(
                Workflow_Model_Workflow::makeScheduledContentFilter(strtotime('1999-01-01 00:00'))
            );

        $result = P4Cms_Content::fetchAll($query)->sortBy('title')->invoke('getTitle');
        $this->assertSame(
            array(),
            $result,
            'Expected entries filtered by scheduled transitions #1.'
        );

        // get all contents with scheduled transitions before 2010-02-01 14:59
        $query = P4Cms_Record_Query::create()
            ->addFilter(
                Workflow_Model_Workflow::makeScheduledContentFilter(strtotime('2010-02-01 14:59'))
            );

        $result = P4Cms_Content::fetchAll($query)->sortBy('title')->invoke('getTitle');
        $this->assertSame(
            array('a'),
            $result,
            'Expected entries filtered by scheduled transitions #2.'
        );

        // get all contents with scheduled transitions before 2011-01-01 00:00
        $query = P4Cms_Record_Query::create()
            ->addFilter(
                Workflow_Model_Workflow::makeScheduledContentFilter(strtotime('2011-01-01 00:00'))
            );

        $result = P4Cms_Content::fetchAll($query)->sortBy('title')->invoke('getTitle');
        $this->assertSame(
            array('a', 'b'),
            $result,
            'Expected entries filtered by scheduled transitions #3.'
        );

        // get all contents with scheduled transitions before 2012-01-01 23:00
        $query = P4Cms_Record_Query::create()
            ->addFilter(
                Workflow_Model_Workflow::makeScheduledContentFilter(strtotime('2012-01-01 23:00'))
            );

        $result = P4Cms_Content::fetchAll($query)->sortBy('title')->invoke('getTitle');
        $this->assertSame(
            array('a', 'b', 'c', 'g'),
            $result,
            'Expected entries filtered by scheduled transitions #4.'
        );

        // get all contents with scheduled transitions before 2025-01-01 17:45
        $query = P4Cms_Record_Query::create()
            ->addFilter(
                Workflow_Model_Workflow::makeScheduledContentFilter(strtotime('2025-01-01 17:45'))
            );

        $result = P4Cms_Content::fetchAll($query)->sortBy('title')->invoke('getTitle');
        $this->assertSame(
            array('a', 'b', 'c', 'g', 'h'),
            $result,
            'Expected entries filtered by scheduled transitions #5.'
        );
    }

    /**
     * Verify that content entries with an invalid content type are unpublished
     */
    public function testInvalidTypeIsUnpublished()
    {
        P4Cms_Content::store(
            array(
                'contentType'            => 'made-up',
                'title'                  => 'so fake',
                'workflowState'          => Workflow_Model_State::PUBLISHED
            )
        );
        $this->assertSame(
            0,
            P4Cms_Content::count(),
            'expected no hits when type does not exist'
        );

        // now add the type and try it with only the workflow missing
        $type = new P4Cms_Content_Type;
        $type->setId('made-up')
             ->setValue('workflow', 'fake')
             ->save();

        $this->assertSame(
            0,
            P4Cms_Content::count(),
            'expected no hits when workflow does not exist'
        );

        // try with all items but no published state
        $workflow = new Workflow_Model_Workflow;
        $workflow->setId('fake')
                 ->setValues($this->_generateWorkflowDefinition(array('draft' => array('review'))))
                 ->save();
        $this->assertSame(
            0,
            P4Cms_Content::count(),
            'expected no hits when workflow does not have a published state'
        );

        // test it works when we have a published state
        $workflow->setValues($this->_generateWorkflowDefinition(array('published' => array('super-published'))))
                 ->save();
        $this->assertSame(
            1,
            P4Cms_Content::count(),
            'expected a hit when everything is in place'
        );
    }

    /**
     * Helper method to generate simplified workflow definition.
     *
     * @param   array   $stateTransitions   list with state transitions
     * @return  array   simplified workflow definition
     */
    protected function _generateWorkflowDefinition(array $stateTransitions)
    {
        $definition = array();
        foreach ($stateTransitions as $state => $transitions) {
            $data = array(
                'label' => "$state state",
            );
            foreach ($transitions as $transition) {
                $data['transitions'][$transition] = array(
                    'label' => "$state to $transition"
                );
            }

            $definition['states'][$state] = $data;
        }

        return $definition;
    }

}
