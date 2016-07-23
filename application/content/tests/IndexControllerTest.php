<?php
/**
 * Test the content index controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 *
 * @todo test add/edit with file fields
 */
class Content_Test_IndexControllerTest extends ModuleControllerTest
{
    public $bootstrap = array('Bootstrap', 'run');

    /**
     * Perform setup
     */
    public function setUp()
    {
        parent::setUp();
        P4Cms_Content_Type::installDefaultTypes();

        // ensure a type is present for testing.
        $type = new P4Cms_Content_Type;
        $type->setId('test-type')
             ->setLabel('Test Type')
             ->setElements(
                array(
                    "title" => array(
                        "type"      => "text",
                        "options"   => array("label" => "Title", "required" => true)
                    ),
                    "body"  => array(
                        "type"      => "textarea",
                        "options"   => array("label" => "Body")
                    )
                )
             )
             ->setValue('icon', file_get_contents(TEST_ASSETS_PATH . "/images/content-type-icon.png"))
             ->setFieldMetadata('icon', array("mimeType" => "image/png"))
             ->setValue('group', 'test')
             ->save();

        // ensure a type w. id is present for testing.
        $type = new P4Cms_Content_Type;
        $type->setId('test-type-w-id')
             ->setLabel('Test Type')
             ->setElements(
                array(
                    "id" => array(
                        "type"      => "text",
                        "options"   => array("label" => "Title", "required" => true)
                    ),
                    "title" => array(
                        "type"      => "text",
                        "options"   => array("label" => "Title", "required" => true)
                    ),
                    "body"  => array(
                        "type"      => "textarea",
                        "options"   => array("label" => "Body")
                    )
                )
             )
             ->setValue('icon', file_get_contents(TEST_ASSETS_PATH . "/images/content-type-icon.png"))
             ->setFieldMetadata('icon', array("mimeType" => "image/png"))
             ->setValue('group', 'test')
             ->save();

        // ensure a type w. a file is present for testing.
        $type = new P4Cms_Content_Type;
        $type->setId('test-type-w-file')
             ->setLabel('Test Type')
             ->setElements(
                array(
                    "title" => array(
                        "type"      => "text",
                        "options"   => array("label" => "Title", "required" => true)
                    ),
                    "name"  => array(
                        "type"      => "file",
                        "options"   => array("label" => "File")
                    )
                )
             )
             ->setValue('icon', file_get_contents(TEST_ASSETS_PATH . "/images/content-type-icon.png"))
             ->setFieldMetadata('icon', array("mimeType" => "image/png"))
             ->setValue('group', 'test2')
             ->save();
    }

    /**
     * Test view action.
     */
    public function testIndex()
    {
        $this->utility->impersonate('anonymous');

        $this->dispatch('/content/index');
        $body = $this->response->getBody();
        $this->assertModule('content', 'Last module run should be content module.'. $body);
        $this->assertController('index', 'Expected controller'. $body);
        $this->assertAction('index', 'Expected action'. $body);

        // check that output looks sane.
        $this->assertQueryContentRegex(
            '#content p',
            '/This site does not contain any content/',
            'Expect the no content paragraph.'
        );

        // create a content entry, and make sure it appears in the index.
        list($type, $entry) = $this->_createTestTypeAndEntry();

        $this->resetRequest()
             ->resetResponse();

        $this->dispatch('/content/index');
        $this->assertModule('content', 'Last module run should be content module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('index', 'Expected action');

        // check that output looks correct.
        $this->assertQueryContentContains(
            '#content ul.content-list li h2 a',
            $entry->getValue('title'),
            'Expect the correct title.'
        );
        $this->assertQueryContentContains(
            '#content ul.content-list li p.content-excerpt',
            $entry->getValue('body'),
            'Expect the correct excerpt.'
        );
    }

    /**
     * Test the manage action.
     */
    public function testManage()
    {
        $this->utility->impersonate('administrator');

        $this->dispatch('/content/manage');
        $body = $this->response->getBody();
        $this->assertModule('content', 'Expected module, dispatch #1. '. $body);
        $this->assertController('index', 'Expected controller, dispatch #1 '. $body);
        $this->assertAction('manage', 'Expected action, dispatch #1 '. $body);

        // ensure table and dojo data elements exist
        $this->assertXpath('//div[@dojotype="dojox.data.QueryReadStore"]', 'Expected dojo.data div');
        $this->assertXpath('//table[@dojotype="p4cms.ui.grid.DataGrid"]', 'Expected dojox.grid table');

        // ensure add content button appears
        $this->assertXpath('//button[@class="add-button"]', 'Expected add button. '. $body);

        // check initial JSON output
        $this->resetRequest()->resetResponse();
        $this->dispatch('/content/browse/format/json');
        $body = $this->response->getBody();
        $this->assertModule('content', 'Expected module, dispatch #2. '. $body);
        $this->assertController('index', 'Expected controller, dispatch #2 '. $body);
        $this->assertAction('browse', 'Expected action, dispatch #2 '. $body);

        $data = Zend_Json::decode($body);
        $this->assertSame(
            array(),
            $data['items'],
            'Expected no items'
        );

        // create several content entries.
        $expected = array();
        $order = array (1, 2, 3, 4, 5, 6, 7, 8, 9);
        foreach ($order as $i) {
            $entry = new P4Cms_Content;
            $entry->setId("test$i")
                  ->setValue('contentType', 'test-type')
                  ->setValue('title',       "title $i")
                  ->setValue('file',        str_repeat('.', $i))
                  ->save();

            $expected[] = array(
                'id'            => "test$i",
                'title'         => "title $i",
                'type'          => array(
                                     "label"       => $entry->getContentType()->getLabel(),
                                     "description" => $entry->getContentType()->getDescription(),
                                     "fields"      => $entry->getContentType()->getElementNames()
                                 ),
                'icon'          => '/type/icon/id/test-type',
                'excerpt'       => "",
                "#REdate"       => "just now",
                'rawDate'       => $entry->getModTime(),
                'deleted'       => '',
                'version'       => "1",
                'privileges'    => P4Cms_User::fetchActive()->getAllowedPrivileges("content/$i"),
            );
        }

        // check again and ensure entries appear.
        $this->resetRequest()->resetResponse();
        $this->request->setParam('sort', 'title');
        $this->dispatch('/content/browse/format/json');
        $body = $this->response->getBody();
        $this->assertModule('content', 'Expected module, dispatch #3. '. $body);
        $this->assertController('index', 'Expected controller, dispatch #3 '. $body);
        $this->assertAction('browse', 'Expected action, dispatch #3 '. $body);

        $body = $this->response->getBody();
        $data = Zend_Json::decode($body);

        // ensure that all entries in expected are contained in data items - we cannot
        // compare whole arrays as there might be items added by other modules
        foreach ($expected as $key => $expectedValues) {
            $this->assertEquals(
                $expectedValues,
                array_intersect($expectedValues, $data['items'][$key]),
                "Expected items for index: $key"
            );
        }
    }

    /**
     * Test the view action.
     */
    public function testView()
    {
        $this->utility->impersonate('anonymous');

        $this->dispatch('/content/view/id/1/does not exist');
        $this->assertModule('error', __LINE__ .': Last module run should be error module.');
        $this->assertController('index', __LINE__ .': Expected controller');
        $this->assertAction('page-not-found', __LINE__ .': Expected action');

        $this->resetRequest()->resetResponse();

        list($type, $entry) = $this->_createTestTypeAndEntry();
        $this->dispatch('/content/view/id/'. $entry->getId());
        $this->assertModule('content', __LINE__ .': Last module run should be content module.');
        $this->assertController('index', __LINE__ .': Expected controller');
        $this->assertAction('view', __LINE__ .': Expected action');

        $this->assertQuery(
            'div#content-entry-1[contentType="' . $type->getId() . '"]',
            __LINE__ .': Expected content-type to be specified in entry widget'
        );
        $this->assertQueryContentContains(
            'div[elementName="title"]',
            $entry->getValue('title'),
            __LINE__ .': Expected title element.'
        );
        $this->assertQueryContentContains(
            'div[elementName="body"]',
            $entry->getValue('body'),
            __LINE__ .': Expected body element.'
        );
        $this->assertQueryContentContains(
            'div[elementName="abstract"]',
            $entry->getValue('abstract'),
            __LINE__ .': Expected abstract element.'
        );
    }

    /**
     * Test data are escaped in the view according to attached display filters.
     */
    public function testSecurity()
    {
        $this->utility->impersonate('author');

        // create content type for testing with display filters
        $elements = array(
            'id'    => array(
                'type'      => 'text'
            ),
            'title' => array(
                'type'      => 'text',
                'options'   => array('label' => 'Title', 'required' => true),
                'display'   => array('filters' => array("HtmlSpecialChars"))
            ),
            'body'  => array(
                'type'      => 'textarea',
                'options'   => array('label' => 'Body')
            )
        );
        $type = new P4Cms_Content_Type;
        $type->setId("test-type")
             ->setLabel("Test Type")
             ->setElements($elements)
             ->save();

        // ensure content is saved into perforce unescaped
        $title  = "Escape test <script> a ( / & 1";
        $body   = "<a>1 & 2</a>";
        $params = array(
            'contentType'   => 'test-type',
            'id'            => 'test1',
            'title'         => $title,
            'body'          => $body,
            'format'        => 'dojoio'
        );
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/content/add');
        $responseBody = $this->response->getBody();
        $this->assertModule('content',   'Expected module; '. $responseBody);
        $this->assertController('index', 'Expected controller; '. $responseBody);
        $this->assertAction('add',       'Expected action; '. $responseBody);

        $entry = P4Cms_Content::fetch('test1');
        $this->assertSame(
            $title,
            $entry->getValue('title'),
            "Expected content entry title value."
        );
        $this->assertSame(
            $body,
            $entry->getValue('body'),
            "Expected content entry body value."
        );

        // ensure data in the view are escaped according to filters
        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/content/view/id/test1');
        $responseBody = $this->response->getBody();
        $this->assertModule('content',   'Expected module; '. $responseBody);
        $this->assertController('index', 'Expected controller; '. $responseBody);
        $this->assertAction('view',      'Expected action; '. $responseBody);

        // ensure title output is escaped
        $this->assertQueryContentRegex(
            'div[elementName="title"] span',
            "/Escape test &lt;script&gt; a \( \/ &amp; 1/",
            __LINE__ .': Expected title element value.'
        );

        // ensure no escaping is done on body as no display filters have been set
        // @note we parse the output directly as assertQueryContentRegex from some reason
        // requires pattern to be /<a>1 &amp; 2<\/a>/ although the output doesn't contain &amp;
        $pathPattern  = '<div[^>]+elementName="body"';
        $valuePattern = '<a>1 & 2<\/a>';
        $this->assertTrue(
            preg_match("/{$pathPattern}[^\/]+{$valuePattern}/", $responseBody) === 1,
            __LINE__ .': Expected body element value.'
        );
    }

    /**
     * Test viewing content.
     */
    public function testGoodView()
    {
        $this->utility->impersonate('anonymous');

        // create content entry to be viewed.
        $this->_createContent();

        $this->request->setParam('id', 'test567');
        $this->dispatch('/content/view');
        $responseBody = $this->getResponse()->getBody();
        $this->assertModule('content', 'Expected module.'. $responseBody);
        $this->assertController('index', 'Expected controller'. $responseBody);
        $this->assertAction('view', 'Expected action'. $responseBody);

        // check that correct data is displayed.
        $this->assertQueryContentRegex('div[@elementname="title"]', '/My Title/', $responseBody);
        $this->assertQueryContentRegex('div[@elementname="body"]',  '/My content body/', $responseBody);
    }

    /**
     * Test viewing non-existant content.
     */
    public function testBadView()
    {
        $this->utility->impersonate('anonymous');

        // test view w. invalid id.
        $this->request->setParam('id', 'test567');
        $this->dispatch('/content/view');
        $responseBody = $this->getResponse()->getBody();
        $this->assertModule('error', 'Expected module.'. $responseBody);
        $this->assertController('index', 'Expected controller.'. $responseBody);
        $this->assertAction('page-not-found', 'Expected action.'. $responseBody);
    }

    /**
     * Test add action without post, manipulating the type param.
     */
    public function testAddNoPostTypeParam()
    {
        $this->utility->impersonate('author');

        // test without a type
        $this->dispatch('/content/add');
        $this->assertModule('content', 'Last module run should be content module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('choose-type', 'Expected action');

        $this->resetRequest()->resetResponse();

        $this->request->setParam('type', 'doesnotexist');
        $this->dispatch('/content/add');
        $this->assertModule('content', 'Last module run should be content module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('choose-type', 'Expected action');
    }

    /**
     * Test add action without post, using an existing type.
     */
    public function testAddNoPostTypeExists()
    {
        $this->utility->impersonate('author');

        // create a type
        list($type, $entry) = $this->_createTestTypeAndEntry();

        $this->request->setParam('type', $type->getId());
        $this->dispatch('/content/add');
        $this->assertModule('content', 'Last module run should be content module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('add', 'Expected action');

        // check that output looks correct.
        $this->assertQuery(
            'div#content-entry-[contentType="test-type"]',
            'Expected content-type to be specified in entry widget'
        );
        $this->assertQuery(
            'div[elementName="body"]',
            'Expected body element.'
        );
        $this->assertQuery(
            'div[elementName="abstract"]',
            'Expected abstract element.'
        );
    }

    /**
     * Test choose type directly.
     */
    public function testChooseType()
    {
        $this->dispatch('/content/choose-type');
        $this->assertModule('content', 'Expected module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('choose-type', 'Expected action');

        $body = $this->response->getBody();
        $this->assertQuery('div[title="Pages"]', 'Expect to find Pages group');
        $this->assertRegexp(
            '/<a href="\/add\/type\/test-type">Test Type<\/a>/',
            $body,
            "Expected to find test type.\n$body"
        );
        $this->assertRegexp(
            '/<a href="\/add\/type\/basic-page">Basic Page<\/a>/',
            $body,
            "Expected to find page module default type.\n$body"
        );
        $this->assertRegexp('/<body/', "Expect a body tag in this request\n$body");
    }

    /**
     * Test add action with bad details.
     */
    public function testAddBadPost()
    {
        $this->utility->impersonate('author');

        // create a type
        list($type, $entry) = $this->_createTestTypeAndEntry();

        $params = array(
            'contentType'   => $type->getId(),
            'title'         => '',
            'body'          => '',
            'format'        => 'dojoio'
        );

        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/content/add');
        $this->assertModule('content', 'Last module run should be content module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('add', 'Expected action');

        $body = $this->response->getBody();
        $this->assertRegexp('/title[^}]*isEmpty/', $body, 'Expect id validation failure');

    }

    /**
     * Test add action with good details.
     */
    public function testAddGoodPost()
    {
        $this->utility->impersonate('author');

        list($type, $entry) = $this->_createTestTypeAndEntry();

        $title = 'Second Title';
        $body = 'The 2nd body.';
        $params = array(
            'contentType'   => $type->getId(),
            'title'         => $title,
            'body'          => $body,
            'format'        => 'json',
            'comment'       => 'user note'
        );
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/content/add');

        $response = Zend_Json::decode($this->response->getBody());
        $this->assertModule('content', 'Last module run should be content module.');
        $this->assertController('index', 'Expected controller.');
        $this->assertAction('add', 'Expected action.');

        $this->assertResponseCode(200, 'Expected response code.');
        $this->assertTrue(isset($response['contentId']), 'Expected content id');

        $fetched = P4Cms_Content::fetch($response['contentId']);
        $change  = $fetched->toP4File()->getChange();
        $this->assertSame($title, $fetched->getValue('title'), 'Expected title in saved content');
        $this->assertSame($body, $fetched->getValue('body'), 'Expected body in saved content');
        $this->assertSame('user note', trim($change->getDescription()), 'Expected change description');
    }

    /**
     * Test add action with good details, and a content type that has an id.
     */
    public function testAddGoodPostWithId()
    {
        $this->utility->impersonate('author');

        list($type, $entry) = $this->_createTestTypeAndEntry(true);

        $title = 'Second Title';
        $body = 'The 2nd body.';
        $newId = 'newId';
        $params = array(
            'contentType'   => $type->getId(),
            'id'            => 'newId',
            'title'         => $title,
            'body'          => $body,
            'format'        => 'dojoio'
        );
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/content/add');
        $responseBody = $this->response->getBody();
        $this->assertModule('content', 'Last module run should be content module.'. $responseBody);
        $this->assertController('index', 'Expected controller.'. $responseBody);
        $this->assertAction('add', 'Expected action.'. $responseBody);

        $this->assertResponseCode(200, 'Expected response code.'. $responseBody);
        $this->assertRegexp('/contentId.+'. $newId .'/', $responseBody, 'Expected content id');

        $fetched = P4Cms_Content::fetch($newId);
        $this->assertSame($title, $fetched->getValue('title'), 'Expected title in saved content');
        $this->assertSame($body, $fetched->getValue('body'), 'Expected body in saved content');
    }

    /**
     * Test edit action with no post.
     */
    public function testEditNoPost()
    {
        $this->utility->impersonate('administrator');

        list($type, $entry) = $this->_createTestTypeAndEntry();

        $this->request->setParam('id', $entry->getId());
        $this->dispatch('/content/edit');
        $this->assertModule('content', 'Last module run should be content module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('edit', 'Expected action');

        $responseBody = $this->response->getBody();
        $this->assertRegexp(
            "/p4cms.content.startEdit/",
            $responseBody,
            'Expect JS edit mode enable code'.$responseBody
        );
        $this->assertQuery(
            'div#content-entry-' . $entry->getId() . '[contentType="' . $type->getId() . '"]',
            'Expected content-type to be specified in entry widget'
        );

        $this->assertQuery(
            'div[elementName="title"]',
            'Expected title element.'
        );
        $this->assertQueryContentContains(
            'div[elementName="body"]',
            $entry->getValue('body'),
            'Expected body element.'
        );
        $this->assertQueryContentContains(
            'div[elementName="abstract"]',
            $entry->getValue('abstract'),
            'Expected abstract element.'
        );
    }

    /**
     * Test edit action with a bad post.
     */
    public function testEditBadPost()
    {
        $this->utility->impersonate('administrator');

        list($type, $entry) = $this->_createTestTypeAndEntry();

        $title = 'Another Title';
        $body = 'The second body.';
        $params = array(
            'title'     => '',
            'body'      => '',
            'format'    => 'dojoio'
        );
        $this->request->setParam('id', $entry->getId());
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/content/edit');
        $this->assertModule('content', 'Last module run should be content module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('edit', 'Expected action');

        $responseBody = $this->response->getBody();
        $this->assertRegexp('/title[^}]*isEmpty/', $responseBody, 'Expect title validation failure');
    }

    /**
     * Test edit action with a good post.
     */
    public function testEditGoodPost()
    {
        $this->utility->impersonate('administrator');

        list($type, $entry) = $this->_createTestTypeAndEntry();

        $title = 'Another Title';
        $body = 'The second body.';
        $params = array(
            'contentType'   => $type->getId(),
            'id'            => $entry->getId(),
            'title'         => $title,
            'body'          => $body,
            'format'        => 'dojoio'
        );
        $this->request->setParam('id', $entry->getId());
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/content/edit');
        $responseBody = $this->response->getBody();
        $this->assertModule('content', 'Last module run should be content module.'. $responseBody);
        $this->assertController('index', 'Expected controller.'. $responseBody);
        $this->assertAction('edit', 'Expected action.'. $responseBody);

        $this->assertResponseCode(200, 'Expected response code.'. $responseBody);
        $this->assertRegexp('/contentId.+'. $entry->getId() .'/', $responseBody, 'Expected content id');

        $fetched = P4Cms_Content::fetch($entry->getId());
        $this->assertSame($title, $fetched->getValue('title'), 'Expected title in saved content');
        $this->assertSame($body, $fetched->getValue('body'), 'Expected body in saved content');
    }

    /**
     * Test edit action with a good post, content type uses an id field
     */
    public function testEditGoodPostWithId()
    {
        $this->utility->impersonate('administrator');

        list($type, $entry) = $this->_createTestTypeAndEntry(true);

        $title = 'Another Title';
        $body = 'The second body.';
        $params = array(
            'contentType'   => $type->getId(),
            'id'            => $entry->getId(),
            'title'         => $title,
            'body'          => $body,
            'format'        => 'dojoio'
        );
        $this->request->setParam('id', $entry->getId());
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/content/edit');
        $responseBody = $this->response->getBody();
        $this->assertModule('content', 'Last module run should be content module.'. $responseBody);
        $this->assertController('index', 'Expected controller.'. $responseBody);
        $this->assertAction('edit', 'Expected action.'. $responseBody);

        $this->assertResponseCode(200, 'Expected response code.'. $responseBody);
        $this->assertRegexp('/contentId.+'. $entry->getId() .'/', $responseBody, 'Expected content id');

        $fetched = P4Cms_Content::fetch($entry->getId());
        $this->assertSame($title, $fetched->getValue('title'), 'Expected title in saved content');
        $this->assertSame($body, $fetched->getValue('body'), 'Expected body in saved content');
    }

    /**
     * Test delete action.
     */
    public function testDeleteInvalidId()
    {
        $this->utility->impersonate('administrator');

        $this->request->setMethod('POST');
        $this->request->setPost(array('ids' => array('not-exist')));
        $this->dispatch('/content/delete/format/json');

        $this->assertModule('content', __LINE__ .': Last module run should be content module.');
        $this->assertController('index', __LINE__ .': Expected controller');
        $this->assertAction('delete', __LINE__ .': Expected action');

        // ensure no entries have been deleted
        $response = Zend_Json::decode($this->response->getBody());
        $this->assertSame(
            0,
            count($response['deletedIds']),
            "Expected no entries have been deleted."
        );
    }

    /**
     * Test deleting an invalid request method
     */
    public function testDeleteInvalidRequestMethod()
    {
        list($type, $entry) = $this->_createTestTypeAndEntry();
        $this->request->setMethod('GET');
        $this->dispatch('/content/delete/id/'. $entry->getId());
        $this->assertModule('error', 'Expected error module.');
    }

    /**
     * Test performing a standard delete
     */
    public function testStandardDelete()
    {
        $this->utility->impersonate('administrator');

        list($type, $entry) = $this->_createTestTypeAndEntry();
        $this->request->setMethod('POST');
        $this->dispatch('/content/delete/id/'. $entry->getId());
        $responseBody = $this->getResponse()->getBody();
        $this->assertModule('content', __LINE__ .': Last module run should be content module.'. $responseBody);
        $this->assertController('index', __LINE__ .': Expected controller'. $responseBody);
        $this->assertAction('delete', __LINE__ .': Expected action'. $responseBody);

        // expect redirect to manage index.
        $this->assertRedirectTo('/', __LINE__ .': Expect redirect to content manage index.'. $responseBody);
    }

    /**
     * Test performing delete in json context
     */
    public function testJsonDelete()
    {
        $this->utility->impersonate('administrator');

        list($type, $entry) = $this->_createTestTypeAndEntry();

        $this->request->setMethod('POST');
        $this->request->setPost(array('ids' => array(1)));
        $this->dispatch('/content/delete/format/json');

        $this->assertModule('content', __LINE__ .': Last module run should be content module.');
        $this->assertController('index', __LINE__ .': Expected controller');
        $this->assertAction('delete', __LINE__ .': Expected action');

        // ensure 1 entry have been deleted
        $response = Zend_Json::decode($this->response->getBody());
        $this->assertSame(
            array('1'),
            $response['deletedIds'],
            "Expected entry '1' has been deleted."
        );

        // ensure that entry id can be passed via 'id' parameter as well
        $entry->setId(7);
        $entry->save();

        $this->resetRequest()->resetResponse();
        $this->request->setMethod('POST');
        $this->dispatch('/content/delete/id/7/format/json');

        $this->assertModule('content', __LINE__ .': Last module run should be content module.');
        $this->assertController('index', __LINE__ .': Expected controller');
        $this->assertAction('delete', __LINE__ .': Expected action');

        // ensure 1 entry have been deleted
        $response = Zend_Json::decode($this->response->getBody());
        $this->assertSame(
            array('7'),
            $response['deletedIds'],
            "Expected entry '7' has been deleted."
        );
    }

    /**
     * Test delete multiple entries in batch.
     */
    public function testMultipleDelete()
    {
        $this->utility->impersonate('editor');

        // create 5 test entries with ids 1 to 5
        list($type, $entry) = $this->_createTestTypeAndEntry();
        for ($i = 2; $i <= 5; $i++) {
            $entry->setId($i);
            $entry->save();
        }

        // ensure that if no ids are passed, no entries will be deleted
        $this->request->setMethod('POST');
        $this->dispatch('/content/delete/format/json');

        $response = Zend_Json::decode($this->response->getBody());
        $this->assertSame(
            0,
            count($response['deletedIds']),
            "Expected no entries were deleted."
        );
        $this->assertSame(
            5,
            P4Cms_Content::fetchAll()->count(),
            "Expected no entries were deleted."
        );

        // delete entries 1,3, and 4
        $this->resetRequest()->resetResponse();

        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'ids'     => array(1, 3, 4),
                'comment' => 'delete 3 entries in a batch'
            )
        );
        $this->dispatch('/content/delete/format/json');

        $response = Zend_Json::decode($this->response->getBody());

        $this->assertSame(
            array('1', '3', '4'),
            $response['deletedIds'],
            "Expected entries 1,3 and 4 have been deleted."
        );
        $this->assertSame(
            2,
            P4Cms_Content::fetchAll()->count()
        );

        // verify that all entries were deleted in the same changelist
        $entry1 = P4Cms_Content::fetch('1', array('includeDeleted' => true));
        $entry3 = P4Cms_Content::fetch('3', array('includeDeleted' => true));
        $entry4 = P4Cms_Content::fetch('4', array('includeDeleted' => true));

        $this->assertSame(
            $entry1->toP4File()->getChange()->getId(),
            $entry3->toP4File()->getChange()->getId(),
            "Expected entries 1,3 were submitted in the same change."
        );
        $this->assertSame(
            $entry1->toP4File()->getChange()->getId(),
            $entry4->toP4File()->getChange()->getId(),
            "Expected entries 1,4 were submitted in the same change."
        );

        // ensure no other files were submitted in the same changelist
        $this->assertSame(
            3,
            count($entry1->toP4File()->getChange()->getFiles()),
            "Expected no other entries were submitted in the same change."
        );

        // verify that comment was saved in changelist decription
        $this->assertSame(
            "delete 3 entries in a batch\n",
            $entry1->toP4File()->getChange()->getDescription(),
            "Expected changelist description"
        );
    }

    /**
     * Test finding valid images in content.
     */
    public function testValidImage()
    {
        $this->utility->impersonate('anonymous');

        // create content entry to be downloaded.
        $this->_createContent();

        $this->request->setParam('id', 'test867-5309');
        $this->dispatch('/content/image/');
        $this->assertModule('content',   'Expected module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('image',     'Expected action');

        // ensure content delivered.
        $this->assertSame(
            $this->response->getBody(),
            "test image content"
        );

        // check headers.
        $headers = $this->response->sendHeaders();
        $this->assertSame(
            $headers['content-type'],
            'Content-Type: image/jpg',
            'Expected content type'
        );
        $this->assertFalse(
            array_key_exists('content-disposition', $headers),
            'Expect content-disposition to not exist'
        );
    }

    /**
     * Test finding invalid images in content.
     */
    public function testInvalidImage()
    {
        $this->utility->impersonate('anonymous');

        // create content entry to be downloaded.
        $this->_createContent();

        $this->request->setParam('id', 'test890');
        $this->dispatch('/content/image/');

        // verify that an invalid image gives a 404 response
        $response = $this->response->getHttpResponseCode();

        $this->assertSame(
            $response,
            404
        );
    }

    /**
     * Test downloading.
     */
    public function testDownload()
    {
        $this->utility->impersonate('anonymous');

        // create content entry to be deleted.
        $this->_createContent();

        $this->request->setParam('id', 'test890');
        $this->dispatch('/content/download');
        $this->assertModule('content',   'Expected module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('download',  'Expected action');

        // ensure content delivered.
        $this->assertSame(
            $this->response->getBody(),
            "test file content"
        );

        // check headers.
        $headers = $this->response->sendHeaders();
        $this->assertSame(
            $headers['content-type'],
            'Content-Type: text/plain'
        );
        $this->assertSame(
            $headers['content-disposition'],
            'Content-Disposition: attachment; filename="myfile.txt"'
        );
    }

    /**
     * Test image action with requested transformations.
     */
    public function testImageAdjust()
    {
        // as this will properly work only with some image driver, we skip this test
        // in the case when no image driver can be created or no jpeg support
        try {
            P4Cms_Image_Driver_Factory::create();
        } catch (P4Cms_Image_Exception $e) {
            $this->markTestSkipped("No image drivers available.");
        }

        $image = new P4Cms_Image;
        if (!$image->isSupportedType('jpeg')) {
            $this->markTestSkipped("Unsupported jpeg image format.");
        }

        $this->utility->impersonate('anonymous');

        // create record with a real image (200x46 pixels)
        $imageData = @file_get_contents(TEST_ASSETS_PATH . '/images/perforce-logo.jpg');
        $entry     = new P4Cms_Content;
        $entry->setId('image-test')
              ->setContentType('test-type-w-file')
              ->setValue('title', 'Test Image')
              ->setValue('file',  $imageData)
              ->setFieldMetadata(
                'file',
                array('filename' => 'image.jpg', 'mimeType' => 'image/jpg')
              )
              ->save();

        $tests = array(
            array(
                'params'            => array(),
                'outputDimensions'  => array(
                    'width'     => 200,
                    'height'    => 46
                ),
                'line'              => __LINE__
            ),
            array(
                'params'            => array(
                    'sharpen'   => '1'
                ),
                'outputDimensions'  => array(
                    'width'     => 200,
                    'height'    => 46
                ),
                'line'              => __LINE__
            ),
            array(
                'params'            => array(
                    'width'     => '150'
                ),
                'outputDimensions'  => array(
                    'width'     => 150,
                    'height'    => 35
                ),
                'line'              => __LINE__
            ),
            array(
                'params'            => array(
                    'height'    => '23'
                ),
                'outputDimensions'  => array(
                    'width'     => 100,
                    'height'    => 23
                ),
                'line'              => __LINE__
            ),
            array(
                'params'            => array(
                    'width'     => '73',
                    'height'    => '127'
                ),
                'outputDimensions'  => array(
                    'width'     => 73,
                    'height'    => 127
                ),
                'line'              => __LINE__
            ),
            array(
                'params'            => array(
                    'maxWidth'  => '100'
                ),
                'outputDimensions'  => array(
                    'width'     => 100,
                    'height'    => 23
                ),
                'line'              => __LINE__
            ),
            array(
                'params'            => array(
                    'maxWidth'  => '500'
                ),
                'outputDimensions'  => array(
                    'width'     => 200,
                    'height'    => 46
                ),
                'line'              => __LINE__
            ),
            array(
                'params'            => array(
                    'maxHeight' => '30'
                ),
                'outputDimensions'  => array(
                    'width'     => 130,
                    'height'    => 30
                ),
                'line'              => __LINE__
            ),
            array(
                'params'            => array(
                    'maxHeight' => '500'
                ),
                'outputDimensions'  => array(
                    'width'     => 200,
                    'height'    => 46
                ),
                'line'              => __LINE__
            ),
            array(
                'params'            => array(
                    'width'     => '300',
                    'maxWidth'  => '152'
                ),
                'outputDimensions'  => array(
                    'width'     => 152,
                    'height'    => 35
                ),
                'line'              => __LINE__
            ),
            array(
                'params'            => array(
                    'height'    => '300',
                    'maxHeight' => '152'
                ),
                'outputDimensions'  => array(
                    'width'     => 661,
                    'height'    => 152
                ),
                'line'              => __LINE__
            ),
            array(
                'params'            => array(
                    'height'    => '92',
                    'maxWidth'  => '300'
                ),
                'outputDimensions'  => array(
                    'width'     => 300,
                    'height'    => 69
                ),
                'line'              => __LINE__
            ),
            array(
                'params'            => array(
                    'width'     => '150',
                    'maxHeight' => '50'
                ),
                'outputDimensions'  => array(
                    'width'     => 150,
                    'height'    => 35
                ),
                'line'              => __LINE__
            ),
            array(
                'params'            => array(
                    'width'     => '450',
                    'height'    => '150',
                    'maxWidth'  => '300',
                    'maxHeight' => '100'
                ),
                'outputDimensions'  => array(
                    'width'     => 300,
                    'height'    => 100
                ),
                'line'              => __LINE__
            ),
            array(
                'params'            => array(
                    'width'     => '450',
                    'height'    => '150',
                    'maxWidth'  => '300',
                    'maxHeight' => '55'
                ),
                'outputDimensions'  => array(
                    'width'     => 165,
                    'height'    => 55
                ),
                'line'              => __LINE__
            )
        );

        // run tests
        foreach ($tests as $test) {
            $this->resetRequest()->resetResponse();

            $params = array_merge(
                array('id' => 'image-test'),
                $test['params']
            );
            $this->request->setParams($params);
            $this->dispatch('/content/image/');

            $this->assertModule('content',   'Expected module.');
            $this->assertController('index', 'Expected controller');
            $this->assertAction('image',     'Expected action');

            // check dimensions of the output image
            $dimensions = $image
                ->setData($this->getResponse()->getBody())
                ->getImageSize();

            $this->assertSame(
                $test['outputDimensions']['width'],
                $dimensions['width'],
                "Line {$test['line']}: Expected output image width."
            );
            $this->assertSame(
                $test['outputDimensions']['height'],
                $dimensions['height'],
                "Line {$test['line']}: Expected output image height."
            );
        }
    }

    /**
     * Test the form action.
     *
     * @todo add tests that exercise the add/edit modes better.
     */
    public function testFormAction()
    {
        // ensure no entry/type id causes error.
        $this->dispatch('/content/form');
        $responseBody = $this->getResponse()->getBody();
        $this->assertModule('error', 'Last module run should be error module.'. $responseBody);
        $this->assertController('index', 'Expected controller.'. $responseBody);
        $this->assertAction('error', 'Expected action.'. $responseBody);
        $this->assertResponseCode(500, 'Expected response code with no id.'. $responseBody);
        $this->assertRegExp(
            '/Cannot get content form. Content type is invalid or missing./',
            $responseBody,
            'Expected error message.'
        );
        $this->resetRequest()->resetResponse();

        // again with a content id
        list($type, $entry) = $this->_createTestTypeAndEntry(true);
        $this->dispatch('/content/form/id/'. $entry->getId());
        $responseBody = $this->getResponse()->getBody();
        $this->assertModule('content', __LINE__ .'Last module run should be content module.'. $responseBody);
        $this->assertController('index', __LINE__ .'Expected controller.'. $responseBody);
        $this->assertAction('form', __LINE__ .'Expected action.'. $responseBody);

        $this->assertResponseCode(200, __LINE__ .'Expected response code with id.'. $responseBody);
        $this->assertQuery(
            'input[id="title"]',
            __LINE__ .': Expected title element.'
        );
        $this->assertQueryContentContains(
            'textarea[id="body"]',
            $entry->getValue('body'),
            __LINE__ .': Expected body element.'
        );
        $this->assertQueryContentContains(
            'textarea[id="abstract"]',
            $entry->getValue('abstract'),
            __LINE__ .': Expected abstract element.'
        );

        $this->resetRequest()->resetResponse();

        // again with a content type specified
        $this->dispatch('/content/form/contentType/'. $type->getId());
        $responseBody = $this->getResponse()->getBody();
        $this->assertModule('content', __LINE__ .': Expected module.'. $responseBody);
        $this->assertController('index', __LINE__ .': Expected controller.'. $responseBody);
        $this->assertAction('form', __LINE__ .': Expected action.'. $responseBody);

        $this->assertResponseCode(200, __LINE__ .'Expected response code with type.'. $responseBody);
        $this->assertQuery(
            'input[id="title"]',
            __LINE__ .': Expected title element.'. $responseBody
        );
        $this->assertQuery(
            'textarea[id="body"]',
            __LINE__ .': Expected body element.'. $responseBody
        );
        $this->assertQuery(
            'textarea[id="abstract"]',
            __LINE__ .': Expected abstract element.'. $responseBody
        );
    }

    /**
     * Test validateField w. no params.
     */
    public function testValidateFieldNoParams()
    {
        $this->utility->impersonate('editor');

        list($type, $entry) = $this->_createTestTypeAndEntry();

        $this->dispatch('/content/validate-field');
        $this->assertModule('error');
        $this->assertController('index');
        $this->assertAction('error');

        $responseBody = $this->getResponse()->getBody();
        $this->assertRegexp('/P4Cms_Content_Exception/', $responseBody);
    }

    /**
     * Test validateField w. bad content type.
     */
    public function testValidateFieldBadContentType()
    {
        $this->utility->impersonate('editor');

        list($type, $entry) = $this->_createTestTypeAndEntry();

        $this->getRequest()->setParam('contentType', 'doesnotexist');
        $this->getRequest()->setParam('field', 'fieldName');
        $this->dispatch('/content/validate-field');
        $this->assertModule('error');
        $this->assertController('index');
        $this->assertAction('error');

        $responseBody = $this->getResponse()->getBody();
        $this->assertRegexp('/Cannot fetch record \'doesnotexist\'. Record does not exist./', $responseBody);
    }

    /**
     * Test validate w. non-existent field
     */
    public function testValidateFieldBadField()
    {
        $this->utility->impersonate('editor');

        list($type, $entry) = $this->_createTestTypeAndEntry();

        $this->getRequest()->setParams(
            array(
                'contentType'   => $type->getId(),
                'field'         => 'doesnotexist',
                'value'         => ''
            )
        );
        $this->dispatch('/content/validate-field');
        $this->assertModule('error');
        $this->assertController('index');
        $this->assertAction('error');

        $responseBody = $this->getResponse()->getBody();
        $this->assertRegexp('/P4Cms_Content_Exception/', $responseBody);
    }

    /**
     * Test validate w. bad value
     */
    public function testValidateFieldBadValue()
    {
        $this->utility->impersonate('editor');

        list($type, $entry) = $this->_createTestTypeAndEntry();

        $this->getRequest()->setParams(
            array(
                'contentType'   => $type->getId(),
                'field'         => 'title',
                'value'         => ''
            )
        );
        $this->dispatch('/content/validate-field');
        $this->assertModule('content');
        $this->assertController('index');
        $this->assertAction('validate-field');

        $responseBody = $this->getResponse()->getBody();
        $responseData = Zend_Json::decode($responseBody);
        $this->assertFalse($responseData['isValid']);
        $this->assertSame(1, count($responseData['errors']));
    }

    /**
     * Test validate w. a good value.
     */
    public function testValidateFieldGoodValue()
    {
        $this->utility->impersonate('editor');

        list($type, $entry) = $this->_createTestTypeAndEntry();

        $this->getRequest()->setParams(
            array(
                'contentType'   => $type->getId(),
                'field'         => 'title',
                'value'         => 'good title'
            )
        );
        $this->dispatch('/content/validate-field');
        $this->assertModule('content');
        $this->assertController('index');
        $this->assertAction('validate-field');

        $responseBody = $this->getResponse()->getBody();
        $responseData = Zend_Json::decode($responseBody);
        $this->assertTrue($responseData['isValid']);
        $this->assertSame(0, count($responseData['errors']));
        $this->assertSame(1, preg_match('/good title/', $responseData['displayValue']));
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
        } else {
            $entry->setId(1);
        }
        $entry->save('a test entry');

        return array($type, $entry);
    }

    /**
     * Create several test content entries.
     */
    protected function _createContent()
    {
        // test type content
        $entry = new P4Cms_Content;
        $entry->setId('test567')
              ->setContentType('test-type')
              ->setValue('title', 'My Title')
              ->setValue('body',  'My content body')
              ->save();

        // test type w. id content
        $entry = new P4Cms_Content;
        $entry->setId('test123')
              ->setContentType('test-type-w-id')
              ->setValue('title', 'My Title')
              ->setValue('body',  'My content body')
              ->save();

        // test type w. file
        $entry = new P4Cms_Content;
        $entry->setId('test890')
              ->setContentType('test-type-w-file')
              ->setValue('title', 'My Title')
              ->setValue('file',  'test file content')
              ->setFieldMetadata(
                'file',
                array('filename' => 'myfile.txt', 'mimeType' => 'text/plain')
              )
              ->save();

        // test type w. image
        $entry = new P4Cms_Content;
        $entry->setId('test867-5309')
              ->setContentType('test-type-w-file')
              ->setValue('title', 'Test Image')
              ->setValue('file',  'test image content')
              ->setFieldMetadata(
                'file',
                array('filename' => 'image.jpg', 'mimeType' => 'image/jpg')
              )
              ->save();
    }

    /**
     * Test getting content sub-forms.
     */
    public function testSubForm()
    {
        $this->utility->impersonate('author');

        // create a category so the sub form will contain one.
        Category_Model_Category::store('test-category');
        $type = new P4Cms_Content_Type;
        $type->setId("test-type")
             ->setLabel("Test Type")
             ->save();

        // ensure fetching category sub-form works as expected.
        $this->request->setParam('contentType', 'test-type')
                      ->setParam('form', 'category');

        $this->dispatch('/content/index/sub-form');
        $this->assertQuery('fieldset#fieldset-category');
        $this->assertQuery('input[name="category[categories][]"]');
        $this->assertQuery('input[value="test-category"]');
    }

    /**
     * Test view script selection (more specific to less specific).
     * The 'default' test theme has a specific view for content
     * id #2 and for content type id 'test-type-2'.
     */
    public function testViewScriptSelection()
    {
        $this->utility->impersonate('anonymous');

        $this->_createTestTypeAndEntry();

        // first entry should get default view.
        $this->dispatch('content/view/id/1');
        $this->assertQuery("div#content-entry-1");

        // reset for next test.
        $this->resetRequest()->resetResponse();

        // test again with a new type that has a custom view.
        $type = P4Cms_Content_Type::fetch('test-type');
        $type->setId('test-type-2')->save();
        $entry = P4Cms_Content::fetch(1);
        $entry->setContentType('test-type-2')->save();

        // updated entry should get the type specific view.
        $this->dispatch('content/view/id/1');
        $this->assertQuery("div#type-specific");

        // reset for next test.
        $this->resetRequest()->resetResponse();

        // test again with a new entry that has a custom view.
        $entry = P4Cms_Content::fetch(1);
        $entry->setId(2)->save();

        // new entry should get the entry specific view.
        $this->dispatch('content/view/id/2');
        $this->assertQuery("div#entry-specific");

        // reset for next test.
        $this->resetRequest()->resetResponse();

        // test again with type that has a custom view.
        $entry->setContentType('test-type-2')->save();

        // updated entry should still get the entry specific view.
        $this->dispatch('content/view/id/2');
        $this->assertQuery("div#entry-specific");
    }

    /**
     * Test default layout.
     */
    public function testDefaultLayoutSelection()
    {
        $this->utility->impersonate('anonymous');

        $this->_createTestTypeAndEntry();

        // should use default layout.
        $this->dispatch('content/view/id/1');
        $this->assertQuery(".default-layout");
    }

    /**
     * Test valid custom layout.
     */
    public function testCustomLayoutSelection()
    {
        $this->utility->impersonate('anonymous');

        $this->_createTestTypeAndEntry();
        $type = P4Cms_Content_Type::fetch('test-type');
        $type->setLayout('blank-layout')->save();

        // should use blank layout.
        $this->dispatch('content/view/id/1');
        $this->assertQuery(".blank-layout");
    }

    /**
     * Test invalid layout fallback to default.
     */
    public function testBadLayoutSelection()
    {
        $this->utility->impersonate('anonymous');

        $this->_createTestTypeAndEntry();
        $type = P4Cms_Content_Type::fetch('test-type');
        $type->setLayout('dasfsadf')->save();

        // should use default layout.
        $this->dispatch('content/view/id/1');
        $this->assertQuery(".default-layout");
    }

    /**
     * Test creating a proper batch
     */
    public function testGoodBatch()
    {
        $this->utility->impersonate('administrator');

        list($type, $entry) = $this->_createTestTypeAndEntry();

        // create a test category.
        Category_Model_Category::store(array('id' => 'test'));

        // count the number of changes pre-save.
        $changeCount = $changes = P4_Change::fetchAll()->count();

        $title  = 'Second Title';
        $body   = 'The 2nd body.';
        $params = array(
            'contentType'   => $type->getId(),
            'title'         => $title,
            'body'          => $body,
            'format'        => 'dojoio',
            'category'      => array(
                'categories' => array('test')
            )
        );
        $this->request->setMethod('POST');
        $this->request->setPost($params);

        $this->dispatch('/content/add');

        // ensure save produced a single change with multiple files.
        $changes = P4_Change::fetchAll();
        $change  = P4_Change::fetch($changeCount + 1);
        $this->assertSame(
            count($changes),
            $changeCount + 1,
            "Expected save to produce one change."
        );
        $this->assertTrue(count($change->getFiles()) > 1);
    }

    /**
     * Test creating a bad batch
     */
    public function testBadBatch()
    {
        $this->utility->impersonate('member');

        list($type, $entry) = $this->_createTestTypeAndEntry();

        P4Cms_PubSub::subscribe(
            'p4cms.content.record.preSave',
            function()
            {
                throw new Exception("Take that batch!");
            }
        );

        $title  = 'Second Title';
        $body   = 'The 2nd body.';
        $params = array(
            'contentType'   => $type->getId(),
            'title'         => $title,
            'body'          => $body,
            'format'        => 'dojoio'
        );
        $this->request->setMethod('POST');
        $this->request->setPost($params);

        try {
            $this->dispatch('/content/add');
            $this->fail("Expected exception.");
        } catch (Exception $e) {

            // ensure batch reverted.
            $adapter = P4Cms_Record::getDefaultAdapter();
            $this->assertFalse($adapter->inBatch());

        }
    }

    /**
     * test the opened action
     */
    public function testOpenedAction()
    {
        $this->utility->impersonate('administrator');

        list($type, $entry) = $this->_createTestTypeAndEntry();

        $this->request->setParam('id', $entry->getId());
        $this->dispatch('/content/opened');
        $this->assertModule('content', 'Last module run should be content module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('opened', 'Expected action');

        $data = Zend_Json_Decoder::decode($this->response->getBody());
        unset($data['change']['Date']);
        unset($data['change']['Client']);
        unset($data['change']['Change']);
        $this->assertSame(
            array(
                'change' => array(
                    'User'          => 'tester',
                    'Status'        => 'submitted',
                    'Type'          => 'public',
                    'Description'   => "a test entry\n",
                    'JobStatus'     => NULL,
                    'Jobs'          => array(),
                    'Files'         => array('//chronicle-test/live/content/1#1')
                ),
                'status' => array(
                    'Version'       => '1',
                    'Action'        => 'add'
                ),
            ),
            $data
        );
    }
}
