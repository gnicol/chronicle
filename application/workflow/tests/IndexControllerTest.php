<?php
/**
 * Test the workflow index controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Test_IndexControllerTest extends ModuleControllerTest
{
    /**
     * Test manage workflows grid.
     */
    public function testIndexAction()
    {
        $this->utility->impersonate('editor');

        // verify that workflows grid is accessible
        $this->dispatch('/workflow');

        $this->assertModule('workflow', 'Expected module for dispatching /workflow action.');
        $this->assertController('index', 'Expected controller for dispatching /workflow action.');
        $this->assertAction('index', 'Expected action for dispatching /workflow action.');

        // verify that table and dojo data elements exist
        $this->assertXpath('//div[@dojotype="dojox.data.QueryReadStore"]', 'Expected dojo.data div');
        $this->assertXpath(
            '//table[@dojotype="p4cms.ui.grid.DataGrid" and @jsid="p4cms.workflow.grid.instance"]',
            'Expected dojox.grid table'
        );

        // verify add and reset buttons appear
        // verify save and restore buttons appear
        $this->assertQueryContentContains(
            "div.button button.add-button",
            "Add Workflow",
            "Expected existence of Add Workflow button."
        );
        $this->assertQueryContentContains(
            "div.button button",
            "Reset to Defaults",
            "Expected existence of Reset to Defaults button."
        );

        // check initial JSON output
        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/workflow/format/json');
        $body = $this->response->getBody();
        $this->assertModule('workflow', 'Expected module, dispatch #2. '. $body);
        $this->assertController('index', 'Expected controller, dispatch #2 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #2 '. $body);

        // verify there are no items in the grid
        $data = Zend_Json::decode($body);
        $this->assertSame(
            array(),
            $data['items'],
            'Expected no workflows.'
        );

        // add few workflows and verify they appear
        $this->_createWorkflow('test');
        $this->_createWorkflow('foo');

        $this->resetRequest()
             ->resetResponse();
        // sort by label
        $this->dispatch('/workflow/format/json?sort=label');
        $body = $this->response->getBody();
        $this->assertModule('workflow', 'Expected module, dispatch #3. '. $body);
        $this->assertController('index', 'Expected controller, dispatch #3 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #3 '. $body);

        $data = Zend_Json::decode($body);
        $expected = array(
            0 => array(
                'id'            => 'foo',
                'label'         => 'foo label',
                'description'   => 'foo description',
                'states'        => array(),
                'contentTypes'  => array()
            ),
            1 => array(
                'id'            => 'test',
                'label'         => 'test label',
                'description'   => 'test description',
                'states'        => array(),
                'contentTypes'  => array()
            )
        );

        $this->assertEquals(
            $expected,
            $data['items'],
            'Expected 2 workflows.'
        );
    }

    /**
     * Test the add action.
     */
    public function testAdd()
    {
        $this->utility->impersonate('editor');

        // verify form markup for different contexts
        $contexts = array(null, 'partial');
        foreach ($contexts as $context) {
            $format = $context ? "/format/$context" : "";

            $this->resetRequest()
                 ->resetResponse();            
            $this->dispatch('/workflow/add' . $format);

            $this->assertModule('workflow', 'Expected module.');
            $this->assertController('index', 'Expected controller');
            $this->assertAction('add', 'Expected action');

            // ensure that form inputs are presented correctly
            $this->assertQuery("form.workflow-form",                "Expected add form.");
            foreach ($this->_getWorkflowFormElementsType() as $field => $type) {
                $query = $type !== 'textarea' ? 'input' : $type;
                $this->assertQuery("{$query}[name='$field']",       "Expected '$field' input.");
            }
            $this->assertQuery("input[type='submit']",              "Expected submit button.");

            // ensure labels are present.
            $labels = array(
                'id'            => 'Id',
                'label'         => 'Label',
                'description'   => 'Description',
                'states'        => 'States'
            );
            foreach ($labels as $field => $label) {
                $this->assertQueryContentContains("label[for='$field']", $label, "Expected $field label.");
            }
        }
    }

    /**
     * Test bogus post to add.
     */
    public function testBadAddPost()
    {
        $this->utility->impersonate('editor');

        // add workflow to allow testing for unique id
        $this->_createWorkflow('test');

        $tests   = $this->_getTestWorkflowFormBadData();
        $tests[] = array(
            'values'        => array(
                'id'            => 'test',
                'label'         => 'foo',
                'description'   => 'bar',
                'states'        => "[baz] label = baz",
            ),
            'errorsCount'   => 1,
            'message'       => 'Id already exists'
        );

        // loop throught all tests
        foreach ($tests as $test) {
            $this->_verifyBadPost('/workflow/add', 'add', $test);
        }
    }

    /**
     * Test good post to add.
     */
    public function testGoodAddPost()
    {
        $this->utility->impersonate('editor');

        $tests = $this->_getTestWorkflowFormGoodData();

        // loop throught all tests
        foreach ($tests as $test) {
            $this->_verifyGoodPost('/workflow/add', 'add', $test);
        }
    }

    /**
     * Test bad post to edit.
     */
    public function testBadEditPost()
    {
        $this->utility->impersonate('editor');

        $workflow = $this->_createWorkflow('foo');
        $workflow->setValue('states', '[foo]\nlabel=foo label')
                 ->save();

        $tests = $this->_getTestWorkflowFormBadData();

        // loop throught all tests
        foreach ($tests as $test) {
            // skip tests with 1 error due to wrong id as id for edit is provided from url
            if (isset($test['idError']) && $test['idError'] === true) {
                $test['errorsCount']--;
            }

            if ($test['errorsCount'] == 0) {
                continue;
            }

            $this->_verifyBadPost('/workflow/edit/id/foo', 'edit', $test);
        }
    }

    /**
     * Test edit with bad workflow id.
     */
    public function testBadEditId()
    {
        $this->utility->impersonate('editor');

        $this->request->setParam('id', 'noexist');
        $this->dispatch('/workflow/edit');
        $this->assertModule('error',     'Expected module.');
        $this->assertController('index', 'Expected controller.');
        $this->assertAction('error',     'Expected action.');
    }

    /**
     * Test good post to edit.
     */
    public function testGoodEditPost()
    {
        $this->utility->impersonate('editor');

        $workflow = $this->_createWorkflow('bar');
        $workflow->setValue('states', '[bar]\nlabel=bar label')
                 ->save();

        $tests = $this->_getTestWorkflowFormGoodData();

        // loop throught all tests
        foreach ($tests as $test) {
            $this->_verifyGoodPost('/workflow/edit/id/bar', 'edit', $test);
        }
    }

    /**
     * Test delete action with invalid id.
     */
    public function testDeleteInvalidId()
    {
        $this->utility->impersonate('editor');

        $this->request->setMethod('POST');
        $this->dispatch('/workflow/delete/id/does-not-exist');
        $this->assertModule('error',        __LINE__ .': Last module run should be error module.');
        $this->assertController('index',    __LINE__ .': Expected controller');
        $this->assertAction('error',        __LINE__ .': Expected action');
    }

    /**
     * Test delete action with anonymous access.
     */
    public function testDeleteByAnonymous()
    {
        $this->_createWorkflow('test');

        $this->request->setMethod('POST');
        $this->request->setParam('id', 'test');
        $this->dispatch('/workflow/delete');
        $this->assertModule('error',            __LINE__ .': Last module run should be error module.');
        $this->assertController('index',        __LINE__ .': Expected controller');
        $this->assertAction('access-denied',    __LINE__ .': Expected action');

        // verify workflow record has not been deleted
        $this->assertTrue(
            Workflow_Model_Workflow::exists('test'),
            "Expected test workflow has not been deleted."
        );
    }

    /**
     * Test deleting an invalid request method
     */
    public function testDeleteInvalidRequestMethod()
    {
        $this->_createWorkflow('foo');
        
        $this->request->setMethod('GET');
        $this->dispatch('/workflow/delete/id/foo');
        $this->assertModule('error', 'Expected error module.');
    }

    /**
     * Test deleting workflow.
     */
    public function testDelete()
    {
        $this->utility->impersonate('editor');
        $this->_createWorkflow('delete');
        $this->_createWorkflow('no-delete');

        $this->request->setMethod('POST');
        $this->request->setParam('id', 'delete');
        $this->dispatch('/workflow/delete');

        $this->assertModule('workflow',     'Expected module.');
        $this->assertController('index',    'Expected controller');
        $this->assertAction('delete',       'Expected action');

        // should redirect to the previous page (base url in this case)
        $this->assertRedirectTo('/workflow/manage', 'Expect redirect to manage page.');

        // verify workflow records after delete
        $this->assertFalse(
            Workflow_Model_Workflow::exists('delete'),
            "Expected delete workflow has been deleted."
        );
        $this->assertTrue(
            Workflow_Model_Workflow::exists('no-delete'),
            "Expected no-delete workflow has not been deleted."
        );
    }

    /**
     * Test deleting workflow in json context.
     */
    public function testDeleteJson()
    {
        $this->utility->impersonate('editor');
        $this->_createWorkflow('del');
        $this->_createWorkflow('del1');

        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'id' =>     'del1',
                'format' => 'json'
            )
        );
        $this->dispatch('/workflow/delete');

        $this->assertModule('workflow',     'Expected module.');
        $this->assertController('index',    'Expected controller');
        $this->assertAction('delete',       'Expected action');

        // when delete in context, no redirecting should be made
        $this->assertNotRedirect(
            "Expected user is not redirected after workflow delete if within a context."
        );

        $responseBody = $this->response->getBody();
        $this->assertEquals(
            Zend_Json::encode(array('id' => 'del1')),
            $responseBody,
            __LINE__ .': Expected json output.'
        );
        
        // verify workflow records after delete
        $this->assertFalse(
            Workflow_Model_Workflow::exists('del1'),
            "Expected del1 workflow has been deleted."
        );
        $this->assertTrue(
            Workflow_Model_Workflow::exists('del'),
            "Expected del workflow has not been deleted."
        );
    }

    /**
     * Test workflows reset action.
     */
    public function testReset()
    {
        $this->utility->impersonate('editor');

        $workflows = array('workflow1', 'workflow2');
        foreach ($workflows as $workflow) {
            $this->_createWorkflow($workflow);
        }

        // ensure worflows are there
        foreach ($workflows as $workflow) {
            $this->assertTrue(
                Workflow_Model_Workflow::exists($workflow),
                "Expected '$workflow' workflow exists."
            );
        }

        // reset workflows
        $this->dispatch('/workflow/reset');

        $this->assertModule('workflow',     'Expected module when workflow reset.');
        $this->assertController('index',    'Expected controller when workflow reset.');
        $this->assertAction('reset',        'Expected action when workflow reset.');

        // ensure there are only default workflows
        $defaultWorkflows = Workflow_Model_Workflow::fetchAll();
        $this->assertEquals(
            1,
            $defaultWorkflows->count(),
            "Expected 1 workflow after reset."
        );
        $this->assertTrue(
            Workflow_Model_Workflow::exists('simple'),
            "Expected presence of 'Simple' workflow after reset"
        );
    }

    /**
     * Return array with types of workflow form elements.
     */
    protected function _getWorkflowFormElementsType()
    {
        return array(
            'id'            => 'text',
            'label'         => 'text',
            'description'   => 'textarea',
            'states'        => 'textarea'
        );
    }

    /**
     * Create sample workflow record.
     *
     * @param   string          $id         Id of created workflow.
     * @return  Workflow_Model_Workflow     Instance of newly created workflow.
     */
    protected function _createWorkflow($id)
    {
        return Workflow_Model_Workflow::store(
            array(
                'id'            => "$id",
                'label'         => "$id label",
                'description'   => "$id description"
            )
        );
    }

    /**
     * Return list with bogus data suitable for testing the workflow form.
     */
    protected function _getTestWorkflowFormBadData()
    {
        // following data are suitable for testing both add and edit actions,
        // however as id is provided from url for edit action, there must be
        // an extra flag (idError = true) set if id error contributes to the
        // total amount of errors (errorsCount)
        $data = array(
            array(
                'values'        => array(),
                'errorsCount'   => 3,
                'idError'       => true,
                'message'       => 'Missing required fields #1'
            ),
            array(
                'values'        => array(
                    'description'   => 'test desc'
                ),
                'errorsCount'   => 3,
                'idError'       => true,
                'message'       => 'Missing required fields #2'
            ),
            array(
                'values'        => array(
                    'id'            => 'foo'
                ),
                'errorsCount'   => 2,
                'message'       => 'Missing required fields #3'
            ),
            array(
                'values'        => array(
                    'label'         => 'foo',
                    'description'   => 'foo desc',
                    'states'        => "[foo] label = bar",
                ),
                'errorsCount'   => 1,
                'idError'       => true,
                'message'       => 'Missing id'
            ),
            array(
                'values'        => array(
                    'id'            => '#$%^',
                    'label'         => 'foo',
                    'states'        => '[a]'
                ),
                'errorsCount'   => 1,
                'idError'       => true,
                'message'       => 'Wrong id #1'
            ),
            array(
                'values'        => array(
                    'id'            => '@1',
                    'label'         => 'foo',
                    'description'   => 'bar',
                    'states'        => '[baz]'
                ),
                'errorsCount'   => 1,
                'idError'       => true,
                'message'       => 'Wrong id #2'
            ),
            array(
                'values'        => array(
                    'id'            => 'spaces not allowed',
                    'label'         => 'foo',
                    'description'   => 'foo desc',
                    'states'        => "[foo] label = bar",
                ),
                'errorsCount'   => 1,
                'idError'       => true,
                'message'       => 'Wrong id #3'
            ),
            array(
                'values'        => array(
                    'id'            => 'bar',
                    'label'         => 'Bar',
                    'description'   => 'bar desc',
                    'states'        => "state",
                ),
                'errorsCount'   => 1,
                'message'       => 'Wrong states field (at least one state has to be defined)'
            ),
            array(
                'values'        => array(
                    'id'            => 'bar',
                    'label'         => 'Bar',
                    'description'   => 'bar desc',
                    'states'        => "label=w",
                ),
                'errorsCount'   => 1,
                'message'       => 'Wrong states field (at least one state has to be defined)'
            ),
            array(
                'values'        => array(
                    'id'            => 'baz',
                    'label'         => 'Baz baz',
                    'description'   => 'bazzzZZZzzz',
                    'states'        => "[state label = baz",
                ),
                'errorsCount'   => 1,
                'message'       => 'Wrong states field (not a valid INI format)'
            )
        );
        return $data;
    }

    /**
     * Return list with good data suitable for testing the workflow form.
     */
    protected function _getTestWorkflowFormGoodData()
    {
        $data = array(
            array(
                'values'        => array(
                    'id'            => 'foo',
                    'label'         => 'foo label',
                    'states'        => "[foo] label = bar",
                ),
            ),
            array(
                'values'        => array(
                    'id'            => 'bar',
                    'label'         => 'bar label',
                    'description'   => 'bar desc',
                    'states'        => "[bar]\nlabel = baz\n[foo]\nlabel = another baz",
                ),
            ),
            array(
                'values'        => array(
                    'id'            => 'baz',
                    'label'         => 'baz label',
                    'states'        => "[123] a=b",
                ),
            ),
            array(
                'values'        => array(
                    'id'            => 'test',
                    'label'         => 'x',
                    'description'   => 'y',
                    'states'        => "[ab c]",
                ),
            )
        );
        return $data;
    }

    /**
     * Dispatch to provided url with provided post data and verify that form contains errors.
     * 
     * @param string    $url        Url to dispatch to.
     * @param string    $action     Workflow action.
     * @param array     $data       Array containing post data, expected number of errors
     *                              and message for assert output.
     */
    protected function _verifyBadPost($url, $action, array $data)
    {
        $this->resetRequest()
             ->resetResponse();
        $this->request->setMethod('POST');
        $this->request->setPost($data['values']);

        $this->dispatch($url);
        $this->assertModule('workflow',     'Expected module.');
        $this->assertController('index',    'Expected controller');
        $this->assertAction($action,        'Expected action');
        $this->assertResponseCode(400,      'Expected bad request response code.');

        // check form errors
        $this->assertQuery("#layout-main form.workflow-form", "Expected add form.");
        $this->assertQueryCount(
            "ul.errors",
            $data['errorsCount'],
            "Expected {$data['errorsCount']} form errors for test '{$data['message']}'."
        );

        // edit form should always preserve id from url
        if ($action === 'edit') {
            $data['values']['id'] = $this->getRequest()->getParam('id');
        }

        // ensure posted data were preserved            
        $elementTypes = $this->_getWorkflowFormElementsType();
        foreach ($data['values'] as $field => $value) {
            $query = $elementTypes[$field] == 'textarea'
                ? "dd textarea[name='$field']"
                : "dd#$field-element";
            $this->assertQueryContentContains(
                "form.workflow-form $query",
                $value,
                "Expected preserving of value '$value' for '$field' field for test '{$data['message']}'."
            );
        }
    }

    /**
     * Dispatch to provided url with provided post data and verify that form values have been saved.
     *
     * @param string    $url        Url to dispatch to.
     * @param string    $action     Workflow action.
     * @param array     $data       Array containing post data.
     */
    protected function _verifyGoodPost($url, $action, array $data)
    {
        $this->resetRequest()
             ->resetResponse();
        $this->request->setMethod('POST');
        $this->request->setPost($data['values']);

        $this->dispatch($url);
        $this->assertModule('workflow',     'Expected module.');
        $this->assertController('index',    'Expected controller');
        $this->assertAction($action,        'Expected action');

        // expect redirect to previous page (base url)
        $this->assertRedirectTo('/workflow/manage', 'Expect redirect to manage page.');

        // edit form should always preserve id from url
        if ($action === 'edit') {
            $data['values']['id'] = $this->getRequest()->getParam('id');
        }

        // verify workflow record has been saved
        $this->assertTrue(
            Workflow_Model_Workflow::exists($data['values']['id']),
            "Expected workflow record has been saved/updated."
        );

        // verify model values
        $workflow = Workflow_Model_Workflow::fetch($data['values']['id']);
        foreach ($data['values'] as $field => $value) {
            // states are by default returned as list of state models
            $workflowValue = $field == 'states'
                ? $workflow->getStatesAsIni()
                : $workflow->getValue($field);

            $this->assertSame(
                $value,
                $workflowValue,
                "Expected value of '$field' returned by workflow model."
            );
        }
    }
}