<?php
/**
 * Test the comment module index controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Comment_Test_IndexControllerTest extends ModuleControllerTest
{
    protected $_commentModule;

    /**
     * Perform setup
     */
    public function setUp()
    {
        parent::setUp();

        $this->_commentModule = P4Cms_Module::fetch('Comment');
        $this->_commentModule->enable();
        $this->_commentModule->load();
    }

    /**
     * Create a type and a entry for testing.
     *
     * @param integer $includeId Flag whether to include id
     */
    public function _createTestTypeAndEntry($includeId = false)
    {
        $elements = array(
            'title' => array(
                'type'      => 'text',
                'options'   => array('label' => 'Title', 'required' => true),
            ),
            'body'  => array(
                'type'      => 'textarea',
                'options'   => array('label' => 'Body'),
            ),
            'abstract'  => array(
                'type'      => 'textarea',
                'options'   => array('label' => 'Abstract'),
            ),
        );
        if ($includeId) {
            $elements['id'] = array(
                'type'      => 'text',
                'options'   => array('label' => 'ID', 'required' => true)
            );
        }
        $type = new P4Cms_Content_Type;
        $type->setId("test-type")
             ->setLabel("Test Type")
             ->setElements($elements)
             ->setValue('icon', file_get_contents(TEST_ASSETS_PATH . '/images/content-type-icon.png'))
             ->setFieldMetadata('icon', array("mimeType" => "image/png"))
             ->save();

        $entry = new P4Cms_Content;
        $entry->setContentType($type)
             ->setValue('title', 'Test Title')
             ->setValue('body', 'The body of the test')
             ->setValue('abstract', 'abstract this');
        if ($includeId) {
            $entry->setId('theId');
        }
        $entry->save('a test entry');

        return array($type, $entry);
    }

    /**
     * Utility function to create a pending comment.
     *
     * @param   string  $commentContent     The string to use for the comment body.
     * @return  string  The id of the newly created comment.
     */
    public function _createComment($commentContent)
    {
        list($type, $entry) = $this->_createTestTypeAndEntry();

        $comment = new Comment_Model_Comment;
        $id      = trim('content/' . $entry->getId(), '\\/') . '/'
                 . (string) new P4Cms_Uuid;

        $params = array(
            'path'          => 'content%2F' . $entry->getId(),
            'comment'       => $commentContent
        );

        $comment->setValues($params)
                ->setValue('user',     P4Cms_User::fetchActive()->getId())
                ->setValue('postTime', time())
                ->setValue('status',   Comment_Model_Comment::STATUS_PENDING)
                ->setId($id)
                ->save();

        return $id;
    }

    /**
     * Test the post action without a path specified
     */
    public function testEmptyCommentList()
    {
        $this->utility->impersonate('anonymous');

        list($type, $entry) = $this->_createTestTypeAndEntry(true);

        $this->dispatch('/content/view/id/'. $entry->getId());
        $body = $this->response->getBody();

        $this->assertModule('content', __LINE__ .': Last module run should be content module.' . $body);
        $this->assertController('index', __LINE__ .': Expected controller' . $body);
        $this->assertAction('view', __LINE__ .': Expected action' . $body);

        $this->assertQuery('div.comments', 'Expected comments container.' . $body);
        $this->assertQueryContentContains('span.comment-count', '(0)', 'Expected zero comments.' . $body);
        $this->assertNotQueryContentContains(
            'ul.comment-list',
            'li',
            'Expected empty comment list.' . $body
        );
        $this->assertQuery(
            'div'
            . '[href="/comment/post/format/partial?path=content%2F'.$entry->getId().'"]',
            'Expected form content pane with correct href attribute.' . $body
        );
    }

    /**
     * Test getting the form for authenticated users.
     */
    public function testGetAuthenticatedForm()
    {
        $this->utility->impersonate('author');

        list($type, $entry) = $this->_createTestTypeAndEntry(true);
        $this->dispatch('/comment/post/format/partial?path=content%2F'.$entry->getId());
        $body = $this->response->getBody();

        $this->assertQueryContentContains('h2', 'Post a Comment', 'Expected comment header.' . $body);
        $this->assertQuery('form.comment-form', 'Expected comment form. ' . $body);
        $this->assertQuery('dl.zend_form_dojo', 'Expected zend form elemement list.' . $body);
        $this->assertQuery('input#path[type="hidden"]', 'Expected path input element. ' .$body);
        $this->assertQuery('textarea#comment', 'Expected comment textarea element. ' .$body);
    }

    /**
     * Test getting the form for anonymous users.
     */
    public function testGetAnonymousForm()
    {
        $this->utility->impersonate('anonymous');

        list($type, $entry) = $this->_createTestTypeAndEntry(true);
        $this->dispatch('/comment/post/format/partial?path=content%2F'.$entry->getId());
        $body = $this->response->getBody();

        $this->assertQueryContentContains('h2', 'Post a Comment', 'Expected comment header.' . $body);
        $this->assertQuery('form.comment-form', 'Expected comment form. ' . $body);
        $this->assertQuery('dl.zend_form_dojo', 'Expected zend form elemement list.' . $body);
        $this->assertQuery('input#path[type="hidden"]', 'Expected path input element. ' .$body);
        $this->assertQuery('input#name[type="text"]', 'Expected name input element. ' .$body);
        $this->assertQuery('input#email[type="text"]', 'Expected email input element. ' .$body);
        $this->assertQuery('textarea#comment', 'Expected comment textarea element. ' .$body);
        $this->assertQuery('dd#captcha-element', 'Expected captcha element. ' .$body);
    }

    /**
     * Test adding a comment.
     */
    public function testAddMemberComment()
    {
        $this->utility->impersonate('author');

        list($type, $entry) = $this->_createTestTypeAndEntry();

        $params = array(
            'path'          => 'content%2F' . $entry->getId(),
            'comment'       => 'Test comment.'
        );

        $this->request->setMethod('POST');
        $this->request->setPost($params);

        $path = 'content%2F' . $entry->getId();

        $this->dispatch('/comment/post/format/partial?path=' . $path);
        $body = $this->response->getBody();

        $this->assertModule('comment', 'Expected comment module. ' . $body);
        $this->assertController('index', 'Expected index controller. ' . $body);
        $this->assertACtion('post', 'Expected post action. ' . $body);

        $filter   = new P4Cms_Record_Filter;
        $filter->add('status', Comment_Model_Comment::STATUS_APPROVED);
        $comments = Comment_Model_Comment::fetchAll(
            array(
                'paths'  => array('content/' . $entry->getId() . '/...')
            )
        );

        $this->assertSame(count($comments), 1, 'Expected one comment, found ' . count($comments). '.');
    }

    /**
     * Test deleting conent; verify by attempting to fetch.
     */
    public function testDeleteContent()
    {
        $id = $this->_createComment('Test comment, to be deleted.');

        $this->utility->impersonate('administrator');

        $this->request->setMethod('POST');
        $this->request->setPost(array('id' => $id));

        $this->dispatch('/comment/index/delete/');
        $body = $this->response->getBody();

        $this->assertModule('comment', 'Expected comment module. ' . $body);
        $this->assertController('index', 'Expected index controller. ' . $body);
        $this->assertACtion('delete', 'Expected delete action. ' . $body);

        try {
            $comment = Comment_Model_Comment::fetch($id);
        }
        catch (P4Cms_Record_NotFoundException $e) {
            // expected exception
            return;
        }

        $this->fail('Expected P4Cms_Record_NotFoundException was not raised.');
    }

    /**
     * Test the manage interface by loading it and verifying that it contains what we expect.
     */
    public function testManageGrid()
    {
        $this->utility->impersonate('administrator');

        $this->dispatch('/comment/index/moderate/');
        $body = $this->response->getBody();

        $this->assertModule('comment', 'Expected comment module. ' . $body);
        $this->assertController('index', 'Expected index controller. ' . $body);
        $this->assertACtion('moderate', 'Expected moderate action. ' . $body);

        $this->assertXpath(
            '//div[@dojotype="dojox.data.QueryReadStore"]',
            'Expected dojo.data div.' . $body
        );
        $this->assertXpath(
            '//table[@dojotype="p4cms.ui.grid.DataGrid" and @jsid="p4cms.comment.grid.instance"]',
            'Expected dojox.grid table.' . $body
        );
    }

    /**
     * Test the content that populates the manage data grid
     */
    public function testManageGridContent()
    {
        $this->utility->impersonate('administrator');

        $commentCount = 5;
        for ($x = 0; $x < $commentCount; $x++) {
            $this->_createComment('Test comment #' . $x . '.');
        }

        $this->dispatch('/comment/index/moderate/format/json');
        $body = $this->response->getBody();

        $this->assertModule('comment', 'Expected comment module. ' . $body);
        $this->assertController('index', 'Expected index controller. ' . $body);
        $this->assertACtion('moderate', 'Expected moderate action. ' . $body);

        $data = Zend_Json::decode($body);

        $this->assertSame(
            $commentCount,
            count($data['items']),
            'Expected ' . $commentCount . ' comments.'
        );
    }

    /**
     * Test changing the status of a comment from pending to approved
     */
    public function testStatusChange()
    {
        $this->utility->impersonate('administrator');

        $id = $this->_createComment('Test Content');

        $this->request->setMethod('POST');
        $this->request->setPost(
            array('id' => $id, 'state' =>  Comment_Model_Comment::STATUS_APPROVED)
        );

        $this->dispatch('/comment/index/status');
        $body = $this->response->getBody();

        $comment = Comment_Model_Comment::fetch($id);

        $this->assertSame(
            $comment->getValue('status'),
            Comment_Model_Comment::STATUS_APPROVED,
            'Expected comment to be approved'
        );
    }

    /**
     * Test upvote.
     */
    public function testVoteUpAction()
    {
        $this->utility->impersonate('administrator');

        $id = $this->_createComment('Test Content');

        $this->dispatch('/comment/index/vote-up/format/json?id=' . $id);
        $body = $this->response->getBody();

        $this->assertModule('comment', 'Expected comment module. ' . $body);
        $this->assertController('index', 'Expected index controller. ' . $body);
        $this->assertACtion('vote-up', 'Expected moderate action. ' . $body);

        $comment = Comment_Model_Comment::fetch($id);
        $this->assertSame(
            $comment->getValue('votes'),
            1,
            'Expected comment to be approved'
        );
    }

    /**
     * Test downvote.
     */
    public function testVoteDownAction()
    {
        $this->utility->impersonate('administrator');

        $id = $this->_createComment('Test Content');

        $this->dispatch('/comment/index/vote-down/format/json?id=' . $id);
        $body = $this->response->getBody();

        $this->assertModule('comment', 'Expected comment module. ' . $body);
        $this->assertController('index', 'Expected index controller. ' . $body);
        $this->assertACtion('vote-down', 'Expected moderate action. ' . $body);

        $comment = Comment_Model_Comment::fetch($id);
        $this->assertSame(
            $comment->getValue('votes'),
            -1,
            'Expected comment to be approved'
        );
    }

    /**
     * Test getting comments a user has voted on for a given path.
     */
    public function testFetchVotedComments()
    {
        $this->utility->impersonate('member');

        $user = $this->p4->getUser();
        $path = 'test/path';

        // create a handful of comments.
        for ($i = 0; $i < 5; $i++) {
            Comment_Model_Comment::store(
                array(
                    'id'        => $path . '/' . $i,
                    'comment'   => 'testing'
                )
            );
        }

        // user should not have voted at all yet.
        $voted = Comment_Model_Comment::fetchVotedComments($user, $path);
        $this->assertSame(0, $voted->count());

        // vote once.
        $this->dispatch('/comment/index/vote-up/format/json?id=' . $path . '/0');
        $voted = Comment_Model_Comment::fetchVotedComments($user, $path);
        $this->assertSame(1, $voted->count());

        // vote again.
        $this->dispatch('/comment/index/vote-up/format/json?id=' . $path . '/1');
        $voted = Comment_Model_Comment::fetchVotedComments($user, $path);
        $this->assertSame(2, $voted->count());

        // verify path counts.
        $voted = Comment_Model_Comment::fetchVotedComments($user, $path . '/woozle');
        $this->assertSame(0, $voted->count());
    }
}
