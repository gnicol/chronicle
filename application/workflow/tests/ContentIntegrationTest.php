<?php
/**
 * Test that workflow is integrated with content as expected.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Test_ContentIntegrationTest extends ModuleControllerTest
{
    /**
     * All tests here need content types, workflows and 'editor' access.
     */
    public function setUp()
    {
        parent::setUp();

        // install default content types and workflows.
        P4Cms_Content_Type::installDefaultTypes();
        Workflow_Model_Workflow::installDefaultWorkflows();
    }

    /**
     * Ensure workflow sub-form is present when adding content
     * that has associated workflow
     */
    public function testContentSubForm()
    {
        $this->utility->impersonate('editor');

        $this->dispatch('/content/add/type/basic-page');

        // ensure workflow sub-form is here.
        $this->assertQuery('.workflow-sub-form');

        // basic page uses 'simple workflow', default state is 'draft'.
        $this->assertQuery('#workflow-state-draft[checked="checked"]');

        // ensure transitions are present also.
        $workflow = Workflow_Model_Workflow::fetch('simple');
        $state    = $workflow->getStateModel('draft');
        foreach ($state->getTransitions() as $transition => $details) {
            $this->assertQuery('#workflow-state-' . $transition);
        }
    }

    /**
     * Ensure that if there are elements in the sub-forms with names matching
     * entry fields, then they are not populated with those values.
     */
    public function testSubFormElementsCollision()
    {
        $this->utility->impersonate('editor');

        // save entry with a comment field that collides with
        // comment input element in the workflow sub-form
        $entry = P4Cms_Content::store(
            array(
                'id'            => 'test',
                'contentType'   => 'basic-page',
                'title'         => 'test-title',
                'workflowState' => 'published',
                'comment'       => 'comment_test'
            )
        );

        // ensure workflow comment field is not populated with comment_test value
        // when editing a content that has associated workflow
        $this->dispatch('/content/edit/id/' . $entry->getId());

        $this->assertNotQueryContentContains(
            "textarea#workflow-comment",
            'comment_test',
            "Expected workflow comment doesn't contain entry's comment value."
        );
    }

    /**
     * Ensure saving content, saves state.
     */
    public function testContentSave()
    {
        $this->utility->impersonate('editor');

        $this->request->setMethod('post')
                      ->setPost('format',      'json')
                      ->setPost('contentType', 'basic-page')
                      ->setPost('title',       'test-title')
                      ->setPost('body',        'test-body')
                      ->setPost(
                        'workflow',
                        array(
                            'state'     => 'review',
                            'scheduled' => 'false',
                            'comment'   => 'test'
                        )
                      );

        $this->dispatch('/content/add/');

        $data    = Zend_Json::decode($this->getResponse()->getBody());
        $content = P4Cms_Content::fetch($data['contentId']);
        $change  = $content->toP4File()->getChange();
        $this->assertSame('review', $content->getValue('workflowState'));
    }

    /**
     * Ensure scheduled data are saved when saving entry with a scheduled transition.
     */
    public function testContentSaveScheduled()
    {
        $this->utility->impersonate('editor');

        // set a timestamp that is in the future where the day, month and year are not the
        // same as today (i.e. it must be at least one day from now); we set today + 2 days
        $timestamp = time() + 172800;

        $this->request->setMethod('post')
                      ->setPost('format',      'json')
                      ->setPost('contentType', 'basic-page')
                      ->setPost('title',       'test-title')
                      ->setPost('body',        'test-body')
                      ->setPost(
                        'workflow',
                        array(
                            'state'           => 'review',
                            'scheduled'       => 'true',
                            'scheduledDate'   => date('Y-m-d', $timestamp),
                            'scheduledTime'   => '07:00'
                        )
                      );

        $this->dispatch('/content/add/');

        $data    = Zend_Json::decode($this->getResponse()->getBody());
        $content = P4Cms_Content::fetch($data['contentId']);
        $this->assertSame('draft',  $content->getValue('workflowState'));
        $this->assertSame('review', $content->getValue('workflowScheduledState'));
        $this->assertSame(
            strtotime(date('Y-m-d 07:00', $timestamp)),
            (int) $content->getValue('workflowScheduledTime')
        );
    }

    /**
     * Ensure editing shows current state.
     */
    public function testContentEdit()
    {
        $this->utility->impersonate('editor');

        // ensure current state is checked if entry has no scheduled transition
        $entry = $this->_makeContentEntry('published');
        $this->dispatch('/content/edit/id/' . $entry->getId());
        $this->assertQuery('#workflow-state-published[checked="checked"]');
    }

    /**
     * Ensure edition shows scheduled state if entry has scheduled transition.
     */
    public function testScheduledContentEdit()
    {
        $this->utility->impersonate('editor');

        // if entry has scheduled transition, ensure that target state of scheduled transition is
        // checked
        $entry = $this->_makeContentEntry('review', '', 'draft', '1');
        $this->dispatch('/content/edit/id/' . $entry->getId());
        $this->assertQuery('#workflow-state-draft[checked="checked"]');
    }

    /**
     * Test content access for anonymous role.
     */
    public function testAccessAnonymous()
    {
        $published   = $this->_makeContentEntry('published');
        $unPublished = $this->_makeContentEntry();

        // ensure anonymous users can see published content
        // but cannot see un-published content
        $this->utility->impersonate('anonymous');
        $this->assertTrue(P4Cms_Content::exists($published->getId()));
        $this->assertFalse(P4Cms_Content::exists($unPublished->getId()));
    }

    /**
     * Test content access for editor role.
     */
    public function testAccessEditor()
    {
        $unPublished = $this->_makeContentEntry();

        // ensure un-published content is accessible to editors
        $this->utility->impersonate('editor');
        $acl = P4Cms_Site::fetchActive()->getAcl();
        $this->assertTrue(P4Cms_Content::exists($unPublished->getId()));
    }

    /**
     * Test content access for member role.
     */
    public function testAccessMember()
    {
        $published   = $this->_makeContentEntry('published', 'foo');
        $unPublished = $this->_makeContentEntry();

        $this->utility->impersonate('member');
        $userId = P4Cms_User::fetchActive()->getId();

        // ensure un-published content is accessible by members only if they own it
        $this->assertFalse(P4Cms_Content::exists($unPublished->getId()));
        $unPublished->setOwner($userId)->save();
        $this->assertTrue(P4Cms_Content::exists($unPublished->getId()));

        // ensure that member cannot see 'publish' option if not having 'publish' permission
        $acl = P4Cms_Site::fetchActive()->getAcl();
        $acl->allow('member', 'content', array('add', 'edit', 'edit-all'));

        $this->dispatch('/add/type/basic-page');
        $this->_verifyWorkflowStatesRadio(array('draft', 'review'), 'draft');

        // add publish permission and verify that user can see publish option
        $acl->allow('member', 'content', array('publish'));
        $this->resetRequest()->resetResponse();
        $this->dispatch('/add/type/basic-page');
        $this->_verifyWorkflowStatesRadio(array('draft', 'review', 'published'), 'draft');

        // ensure that member, although lacking 'access-unpublished' permission, can
        // unpublish content if is an owner
        $this->resetRequest()->resetResponse();
        $this->dispatch('/content/index/edit/id/' . $published->getId());

        // not an owner - should see only published state
        $this->_verifyWorkflowStatesRadio(array('published'), 'published');

        // add 'access-unpublished' and verify again
        $acl->allow('member', 'content', array('access-unpublished'));
        $this->resetRequest()->resetResponse();
        $this->dispatch('/content/index/edit/id/' . $published->getId());
        $this->_verifyWorkflowStatesRadio(array('draft', 'review', 'published'), 'published');

        // remove access-published permission, but make user owner and verify again
        $acl->deny('member', 'content', array('access-unpublished'));
        $published->setOwner($userId)->save();
        $this->resetRequest()->resetResponse();
        $this->dispatch('/content/index/edit/id/' . $published->getId());
        $this->_verifyWorkflowStatesRadio(array('draft', 'review', 'published'), 'published');
    }

    /**
     * Verify transition conditions. Dispatch to content add/edit action to simulate
     * creating/updating content entry when certain transition conditions apply.
     */
    public function testWorkflowConditions()
    {
        // create workflow we will test against -
        // modify simple workflow to remove published to draft transition
        $workflow       = Workflow_Model_Workflow::fetch('simple');
        $workflowStates = $workflow->getStates();
        unset($workflowStates['published']['transitions']['draft']);
        $workflow->setStates($workflowStates)
                 ->save();

        // set necessary permissions
        $this->utility->impersonate('editor');

        // verify all transitions are available when adding a new content
        $this->dispatch('/add/type/basic-page');
        $this->_verifyWorkflowStatesRadio(array('draft', 'review', 'published'), 'draft');

        // disable promoting to review
        $states = $workflowStates;
        $states['draft']['transitions']['review']['conditions'] = array('false');
        $workflow->setStates($states)
                 ->save();

        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/add/type/basic-page');
        $this->_verifyWorkflowStatesRadio(array('draft', 'published'), 'draft');

        // test with edit action
        $entry = $this->_makeContentEntry('review');
        $workflow->setStates($workflowStates)
                 ->save();
        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/content/index/edit/id/' . $entry->getId());
        $this->_verifyWorkflowStatesRadio(array('draft', 'review', 'published'), 'review');

        // add Contains condition
        $states = $workflowStates;
        $states['review']['transitions']['published']['conditions'] = array(
            'contains' => array(
                'string'    => 'find me'
            )
        );
        $workflow->setStates($states)
                 ->save();

        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/content/index/edit/id/' . $entry->getId());
        $this->_verifyWorkflowStatesRadio(array('draft', 'review'), 'review');

        // modify entry to satisfy the condition
        $entry = P4Cms_Content::fetch($entry->getId());
        $entry->setValue('body', 'Now body contains find me string.')
              ->save();

        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/content/index/edit/id/' . $entry->getId());
        $this->_verifyWorkflowStatesRadio(array('draft', 'review', 'published'), 'review');

        // try to modify content via post to ensure transition conditions must be met
        // also ensure conditions are evaluated agains posted data
        $this->resetRequest()
             ->resetResponse();

        $params = array(
            'contentType'   => 'basic-page',
            'title'         => 'test-title',
            'body'          => 'new body',
            'workflow'      => array(
                'state'     => 'published',
                'scheduled' => 'false'
            )
        );
        $this->request->setParam('id', $entry->getId());
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/content/edit/format/dojoio/id/' . $entry->getId());

        // verify entry has not been modified
        $entry = P4Cms_Content::fetch($entry->getId());
        $this->assertSame(
            'review',
            $entry->getValue('workflowState'),
            "Expected workflow state has not changed as transition is invalid."
        );

        // try again with no changes made so it should work
        $this->resetRequest()
             ->resetResponse();

        $params = array(
            'contentType'   => 'basic-page',
            'title'         => 'test-title',
            'body'          => 'changed body to contain find me string',
            'workflow'      => array(
                'state'     => 'published',
                'scheduled' => 'false'
            )
        );
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/content/edit/format/dojoio/id/' . $entry->getId());

        $responseBody = $this->response->getBody();
        $this->assertModule('content', 'Last module run should be content module.'. $responseBody);
        $this->assertController('index', 'Expected controller.'. $responseBody);
        $this->assertAction('edit', 'Expected action.'. $responseBody);

        // verify entry has been modified
        $entry = P4Cms_Content::fetch($entry->getId());
        $this->assertSame(
            'published',
            $entry->getValue('workflowState'),
            "Expected workflow state has been changed as transition is valid."
        );

        // check state options
        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/content/index/edit/id/' . $entry->getId());
        $this->_verifyWorkflowStatesRadio(array('review', 'published'), 'published');
    }

    /**
     * Verify transition actions. Dispatch to content edit action to simulate
     * updating content entry and ensure transition actions are invoked.
     */
    public function testWorkflowActions()
    {
        // test with simple workflow added by SendEmail action to a certain transitions
        $workflow       = Workflow_Model_Workflow::fetch('simple');
        $workflowStates = $workflow->getStates();

        // add sendEmail action to the review->published and review->draft transitions
        $workflowStates['review']['transitions']['published']['actions'] = array(
            'sendEmail' => array(
                'to'        => 'published@email.com',
                'subject'   => 'content published'
            )
        );
        $workflowStates['review']['transitions']['draft']['actions'] = array(
            'sendEmail' => array(
                'to'        => 'draft@email.com',
                'subject'   => 'content demoted to draft'
            )
        );
        $workflow->setStates($workflowStates)
                 ->save();

        // set custom email transport to prevent from sending real emails and to allow
        // checking what emails would be sent
        $transport = new Workflow_Test_MailTransport;
        Zend_Mail::setDefaultTransport($transport);

        // set necessary permissions
        $this->utility->impersonate('editor');

        // create new entry
        $entry = $this->_makeContentEntry();

        // edit entry to promote to review state
        $params = array(
            'contentType'   => 'basic-page',
            'title'         => 'test-title',
            'workflow'      => array(
                'state'     => 'review',
                'scheduled' => 'false'
            )
        );
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/content/edit/format/dojoio/id/' . $entry->getId());

        // ensure entry has been modified
        $entry = P4Cms_Content::fetch($entry->getId());
        $this->assertSame(
            'review',
            $entry->getValue('workflowState'),
            "Expected workflow state has been changed to review."
        );

        // ensure email has not been sent
        $this->assertSame(
            0,
            count($transport->getSentMails()),
            "Expected no sendEmail action has occured."
        );

        // publish content
        $transport->reset();
        $this->resetRequest()
             ->resetResponse();

        $params = array(
            'contentType'   => 'basic-page',
            'title'         => 'test-title',
            'workflow'      => array(
                'state'     => 'published',
                'scheduled' => 'false'
            )
        );
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/content/edit/format/dojoio/id/' . $entry->getId());

        // ensure entry has been updated
        $entry = P4Cms_Content::fetch($entry->getId());
        $this->assertSame(
            'published',
            $entry->getValue('workflowState'),
            "Expected workflow state has been changed to published."
        );

        // ensure correct email has been sent
        $sentEmails = $transport->getSentMails();
        $this->assertSame(
            1,
            count($sentEmails),
            "Expected sendEmail action has occured."
        );

        // verify email recipient and subject
        $this->assertSame(
            'published@email.com',
            $sentEmails[0]['to'],
            "Expected email recipient."
        );
        $this->assertSame(
            'content published',
            $sentEmails[0]['subject'],
            "Expected email subject."
        );

        // ensure actions are not invoked if transition conditions are not met
        $workflowStates['published']['transitions']['review']['actions'] = array(
            'sendEmail'     => array(
                'to'        => 'demote@email.com',
                'subject'   => 'published -> review'
            )
        );
        $workflowStates['published']['transitions']['review']['conditions'] = array('false');
        $workflow->setStates($workflowStates)
                 ->save();

        // try to demote content to review
        $transport->reset();
        $this->resetRequest()
             ->resetResponse();

        $params = array(
            'contentType'   => 'basic-page',
            'title'         => 'test-title',
            'workflow'      => array(
                'state'     => 'review',
                'scheduled' => 'false'
            )
        );
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/content/edit/format/dojoio/id/' . $entry->getId());

        // ensure entry has not been modified
        $entry = P4Cms_Content::fetch($entry->getId());
        $this->assertSame(
            'published',
            $entry->getValue('workflowState'),
            "Expected workflow state has not been changed to review."
        );

        // ensure email has not been sent
        $sentEmails = $transport->getSentMails();
        $this->assertSame(
            0,
            count($sentEmails),
            "Expected sendEmail action has not occured."
        );
    }

    /**
     * Verify filtering content data grid by workflow states.
     */
    public function testFilterByWorkflow()
    {
        $this->utility->impersonate('administrator');

        $published = Workflow_Model_State::PUBLISHED;
        $time      = time() + 1000;

        // remove workflows/content types
        foreach (Workflow_Model_Workflow::fetchAll() as $workflow) {
            $workflow->delete();
        }
        foreach (P4Cms_Content_Type::fetchAll() as $type) {
            $type->delete();
        }

        // create workflows and content types with the following mapping:
        // --------------------------------------------------------
        // | CONTENT TYPE  | WORKFLOW  | STATES                   |
        // --------------------------------------------------------
        // | basic         | simple    | draft, published         |
        // | basic2        | simple    | draft, published         |
        // | simple        |           | -                        |
        // | private       | private   | draft                    |
        // | blog          | blog      | draft, review, published |
        // |               | unused    | draft, review, published |
        // --------------------------------------------------------
        Workflow_Model_Workflow::store(
            array(
                'id'            => "simple",
                'label'         => "simple workflow",
                'states'        => "[draft]\n"
                                .  "label=Draft\n"
                                .  "[$published]\n"
                                .  "label=Published"
            )
        );
        Workflow_Model_Workflow::store(
            array(
                'id'            => "private",
                'label'         => "private workflow",
                'states'        => "[draft]\n"
                                .  "label=Draft\n"
            )
        );
        Workflow_Model_Workflow::store(
            array(
                'id'            => "blog",
                'label'         => "blog workflow",
                'states'        => "[draft]\n"
                                .  "label=Draft\n"
                                .  "[review]\n"
                                .  "label=Review\n"
                                .  "[$published]\n"
                                .  "label=Published"
            )
        );
        Workflow_Model_Workflow::store(
            array(
                'id'            => "unused",
                'label'         => "unused workflow",
                'states'        => "[draft]\n"
                                .  "label=Draft\n"
                                .  "[review]\n"
                                .  "label=Review\n"
                                .  "[$published]\n"
                                .  "label=Published"
            )
        );
        P4Cms_Content_Type::store(
            array(
                'id'            => "basic",
                'label'         => "basic content type",
                'group'         => "misc",
                'workflow'      => "simple",
                'elements'      => "[title]\n"
                                .  "type=text"
            )
        );
        P4Cms_Content_Type::store(
            array(
                'id'            => "basic2",
                'label'         => "basic2 content type",
                'group'         => "misc",
                'workflow'      => "simple",
                'elements'      => "[title]\n"
                                .  "type=text"
            )
        );
        P4Cms_Content_Type::store(
            array(
                'id'            => "simple",
                'label'         => "simple content type",
                'group'         => "misc",
                'elements'      => "[title]\n"
                                .  "type=text"
            )
        );
        P4Cms_Content_Type::store(
            array(
                'id'            => "private",
                'label'         => "private content type",
                'group'         => "misc",
                'workflow'      => "private",
                'elements'      => "[title]\n"
                                .  "type=text"
            )
        );
        P4Cms_Content_Type::store(
            array(
                'id'            => "blog",
                'label'         => "blog content type",
                'group'         => "misc",
                'workflow'      => "blog",
                'elements'      => "[title]\n"
                                .  "type=text"
            )
        );

        // create contents with following relations to the workflows (dot denotes the same value as in the cell above):
        // -------------------------------------------------------------------------------------------------------
        // | CONTENT TITLE | CONTENT TYPE | WORKFLOW | STATE ASSIGNED | STATE SCHEDULED | STATE AS IT APPEARS    |
        // -------------------------------------------------------------------------------------------------------
        // | blog-draft    | blog         | blog     | draft          | -               | draft                  |
        // | blog-draft2   | .            | .        | draft          | review          | draft     -> review    |
        // | blog-review   | .            | .        | review         | published       | review    -> published |
        // | blog-pub      | .            | .        | published      | -               | published              |
        // | blog-abc      | .            | .        | abc            |                 | draft                  |
        // | simple        | simple       | -        | -              | -               | published              |
        // | simple-draft  | simple       | -        | draft          | review          | published              |
        // | simple2-draft | simple       | -        | draft          | published       | published              |
        // | basic-draft   | basic        | simple   | draft          | published       | draft     -> published |
        // | basic-pub     | .            | .        | published      | draft           | published -> draft     |
        // | basic-review  | .            | .        | review         | -               | draft                  |
        // | basic-xyz     | .            | .        | xyz            | -               | draft                  |
        // | basic2-draft  | basic2       | .        | draft          | -               | draft                  |
        // | private-draft | private      | private  | draft          | -               | draft                  |
        // | private-pub   | .            | .        | published      | -               | draft                  |
        // -------------------------------------------------------------------------------------------------------
        P4Cms_Content::store(
            array(
                'contentType'            => 'private',
                'title'                  => 'private-draft',
                'workflowState'          => 'draft'
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'private',
                'title'                  => 'private-pub',
                'workflowState'          => $published
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'basic2',
                'title'                  => 'basic2-draft',
                'workflowState'          => 'draft'
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'basic',
                'title'                  => 'basic-draft',
                'workflowState'          => 'draft',
                'workflowScheduledState' => $published,
                'workflowScheduledTime'  => $time
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'basic',
                'title'                  => 'basic-pub',
                'workflowState'          => $published,
                'workflowScheduledState' => 'draft',
                'workflowScheduledTime'  => $time
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'basic',
                'title'                  => 'basic-review',
                'workflowState'          => 'review'
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'basic',
                'title'                  => 'basic-xyz',
                'workflowState'          => 'xyz'
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'blog',
                'title'                  => 'blog-draft',
                'workflowState'          => 'draft'
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'blog',
                'title'                  => 'blog-draft2',
                'workflowState'          => 'draft',
                'workflowScheduledState' => 'review',
                'workflowScheduledTime'  => $time
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'blog',
                'title'                  => 'blog-pub',
                'workflowState'          => $published
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'blog',
                'title'                  => 'blog-review',
                'workflowState'          => 'review',
                'workflowScheduledState' => $published,
                'workflowScheduledTime'  => $time
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'blog',
                'title'                  => 'blog-abc',
                'workflowState'          => 'abc'
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'simple',
                'title'                  => 'simple',
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'simple',
                'title'                  => 'simple-draft',
                'workflowState'          => 'draft',
                'workflowScheduledState' => 'review',
                'workflowScheduledTime'  => $time,
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'simple',
                'title'                  => 'simple2-draft',
                'workflowState'          => 'draft',
                'workflowScheduledState' => $published,
                'workflowScheduledTime'  => $time,
            )
        );

        // test json data for browse action when provided with various options for filtering by workflow
        $this->dispatch('/browse/format/json');
        $body     = $this->getResponse()->getBody();
        $data     = Zend_Json::decode($body);
        $expected = P4Cms_Content::fetchAll()->invoke('getTitle');

        $this->_verifyTitleValuesMatch($data['items'], $expected);

        // filter to keep only published
        $this->_verifyWorkflowFilter(
            array(
                'workflow'  => 'onlyPublished'
            ),
            array(
                array('blog-pub', 'simple', 'simple-draft', 'basic-pub', 'simple2-draft'),
                array('blog-review', 'basic-draft')
            )
        );

        // filter to keep only unpublished
        $this->_verifyWorkflowFilter(
            array(
                'workflow'  => 'onlyUnpublished'
            ),
            array(
                array('blog-draft', 'blog-draft2', 'blog-review', 'blog-abc', 'basic-draft',
                'basic-review', 'basic-xyz', 'basic2-draft', 'private-draft', 'private-pub'),
                array('blog-draft2', 'basic-pub')
            )
        );

        // filter to keep only items under simple workflow
        $this->_verifyWorkflowFilter(
            array(
                'workflow'  => 'userSelected',
                'states'    =>  array('simple/*')
            ),
            array(
                array('basic-draft', 'basic-pub', 'basic-review', 'basic-xyz', 'basic2-draft'),
                array('basic-draft', 'basic-pub')
            )
        );

        // filter to keep only items under blog workflow with published state
        $this->_verifyWorkflowFilter(
            array(
                'workflow'  => 'userSelected',
                'states'    =>  array('blog/' . $published),
            ),
            array(
                array('blog-pub'),
                array('blog-review')
            )
        );

        // filter to keep only items under blog workflow with draft state
        // or simple workflow with published state
        $this->_verifyWorkflowFilter(
            array(
                'workflow'  => 'userSelected',
                'states'    =>  array(
                    'blog/draft',
                    'simple/' . $published
                )
            ),
            array(
                array('blog-draft', 'blog-draft2', 'blog-abc', 'basic-pub'),
                array('basic-draft')
            )
        );

        // filter to keep only items under 'unused' workflow
        $this->_verifyWorkflowFilter(
            array(
                'workflow'  => 'userSelected',
                'states'    => array('unused/*'),
            ),
            array(
                array(),
                array()
            )
        );

        // filter to keep only items under private workflow having draft state
        $this->_verifyWorkflowFilter(
            array(
                'workflow'  => 'userSelected',
                'states'    =>  array('private/draft'),
            ),
            array(
                array('private-draft', 'private-pub'),
                array()
            )
        );

        // filter to keep only items under simple or private workflow with draft state
        $this->_verifyWorkflowFilter(
            array(
                'workflow'  => 'userSelected',
                'states'    =>  array(
                    'simple/draft',
                    'private/draft'
                )
            ),
            array(
                array('basic-draft', 'basic-review', 'basic-xyz', 'basic2-draft', 'private-draft', 'private-pub'),
                array('basic-pub')
            )
        );

        // if no selection is provided, ensure all items are shown
        $this->_verifyWorkflowFilter(
            array(
                'workflow'  => 'userSelected'
            ),
            array(
                P4Cms_Content::fetchAll()->invoke('getTitle'),
                P4Cms_Content::fetchAll()->invoke('getTitle')
            )
        );
    }

    /**
     * Test that proper icon for category is rendered (i.e. depending on category content).
     */
    public function testCategoryIcons()
    {
        // create entry in unpublished state
        $entry = $this->_makeContentEntry('draft', 'foo');

        // create category A with sub-category B containing the entry
        $categoryA = Category_Model_Category::store(
            array(
                'id'            => 'a',
                'title'         => 'A',
            )
        );
        $categoryB = Category_Model_Category::create(
            array(
                'id'            => 'a/b',
                'title'         => 'B',
            )
        )->addEntry($entry->getId())->save();

        // verify that member see icon representing empty folder for category B
        // as member cannot see unpublished content
        $this->utility->impersonate('member');

        $this->dispatch('/category/view/a');
        $this->assertQueryContentRegex("a[href='/category/view/a/b']", "/src=.+folder\-64x64/");

        // add access to unpublished content and verify category icon again
        $acl = P4Cms_Site::fetchActive()->getAcl();
        $acl->allow('member', 'content', array('access-unpublished'));

        $this->resetRequest()->resetResponse();
        $this->dispatch('/category/view/a');
        $this->assertQueryContentRegex("a[href='/category/view/a/b']", "/src=.+folder\-content\-64x64/");
    }

    /**
     * Helper function to verify filtering by workflow.
     * It automatically processes 3 cases - when filters are applied to the current,
     * scheduled or both states.
     *
     * Workflow data passed in the parameter are assumed to contain values from the
     * worfklow form, except 'targetState' that is added automatically.
     *
     * Expected entries are assumed to contain 2 or 3 lists of expected entry titles
     * when filters (represented by workflow data) are applied to the current state,
     * to the scheduled state and optionally to the both. If thirs list is omitted then
     * it will be calculated as union of the two for current and scheduled.
     *
     * @param   array   $workflowData       values of the workflow form representing filters.
     * @param   array   $expectedEntries    2- or 3-dimensional array containing lists
     *                                      of expected entry titles when filters are applied
     *                                      to the, in order, current state, scheduled state
     *                                      and both.
     */
    protected function _verifyWorkflowFilter(array $workflowData, array $expectedEntries)
    {
        $expectedEntries = array_values($expectedEntries);
        if (count($expectedEntries) === 2) {
            // add expected result for applied to 'current or scheduled' state as
            // union of the two
            $expectedEntries[] = array_merge(
                $expectedEntries[0],
                array_diff($expectedEntries[1], $expectedEntries[0])
            );
        }

        $targetStates = array('current', 'scheduled', 'either');
        foreach ($expectedEntries as $key => $expected) {
            // prepare post data and inject target state
            $postData                            = array('workflow' => $workflowData);
            $postData['workflow']['targetState'] = $targetStates[$key];

            // prepare request
            $this->resetRequest()->resetResponse();
            $this->request
                ->setMethod('post')
                ->setPost($postData);

            // dispatch
            $this->dispatch('/browse/format/json');
            $body     = $this->getResponse()->getBody();
            $data     = Zend_Json::decode($body);

            // verify response
            $this->_verifyTitleValuesMatch($data['items'], $expected);
        }
    }

    /**
     * Verifies that titles in $source array match values in $titleValues.
     *
     * @param   array   $source             array to compare with $titleValues, assuming array items contain
     *                                      'title' key
     * @param   array   $titleValues        array to compare to $source
     */
    protected function _verifyTitleValuesMatch(array $source, array $titleValues)
    {
        // extract title values from source
        $sourceTitle = array_map(
            function($item)
            {
                return isset($item['title']) ? $item['title'] : null;
            },
            $source
        );

        $match = (count($source) === count($titleValues))
            && count(array_diff($titleValues, $sourceTitle)) === 0;

        $this->assertTrue(
            $match,
            "Expected items after filtering by worfklow:"
            . "\nFiltered entry titles: " . print_r($sourceTitle, true)
            . "\nExpected entry titles: " . print_r($titleValues, true)
        );
    }

    /**
     * Create a test content entry in the given state and optionally set scheduled transition data.
     *
     * @param   string          $state          optional - the state to put the entry in.
     * @param   string          $owner          optional - username to set as entry owner.
     * @param   string          $scheduledState optional - state of the scheduled transition to
     *                                          set the entry on.
     * @param   string          $scheduledTime  optional - time of the scheduled transition to
     *                                          set the entry on.
     * @return  P4Cms_Content   created content entry instance
     */
    protected function _makeContentEntry($state = 'draft', $owner = '', $scheduledState = null,
        $scheduledTime = null
    )
    {
        return P4Cms_Content::store(
            array(
                'contentType'               => 'basic-page',
                'contentOwner'              => $owner,
                'title'                     => 'test-title',
                'body'                      => 'test-body',
                'workflowState'             => $state,
                'workflowScheduledState'    => $scheduledState,
                'workflowScheduledTime'     => $scheduledTime
            )
        );
    }

    /**
     * Verify that all expected states are present in the workflow sub-form as state radio options.
     * If selected state parameter is present, also ensure selected option.
     *
     * @param   array   $expectedStates     list with states for state radio element.
     * @param   string  $selectedState      state option assumed to be selected.
     */
    protected function _verifyWorkflowStatesRadio(array $expectedStates, $selectedState = null)
    {
        $statesImploded = implode(', ', $expectedStates);

        // verify number of radio options
        $expectedTransitionsCount = count($expectedStates);
        $this->assertQueryCount(
            '#workflow-state-element input[type="radio"]',
            $expectedTransitionsCount,
            "Expected state radio has " . $expectedTransitionsCount . " options: " . $statesImploded . "."
        );

        // verify radio option values
        $message = 'Expected states: [' . $statesImploded . ']';
        if ($selectedState) {
            $message .= ' with [' . $selectedState . '] selected';
        }
        foreach ($expectedStates as $state) {
            $query = '#workflow-state-element input[type="radio"][value="' . $state . '"]';
            if ($selectedState === $state) {
                $query .= '[checked="checked"]';
            }
            $this->assertQuery($query, $message);
        }
    }
}