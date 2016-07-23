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
class Search_Test_ManageControllerTest extends ModuleControllerTest
{
    public $bootstrap = array('Bootstrap', 'run');

    /**
     * Perform setup
     */
    public function setUp()
    {
        parent::setUp();

        $searchModule = P4Cms_Module::fetch('Search');
        $searchModule->enable();
        $searchModule->load();

        P4Cms_Content_Type::installDefaultTypes();

        // install default ACL
        $acl = P4Cms_Site::fetchActive()->getAcl();
        $acl->installDefaults()->save();

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
     * Create several test content entries.
     */
    protected function _createContent()
    {
        // test type content
        $entry = new P4Cms_Content;
        $entry->setId('test123')
              ->setContentType('test-type')
              ->setValue('title', 'My Title')
              ->setValue('body',  'My content body')
              ->save();

        // test type w. id content
        $entry = new P4Cms_Content;
        $entry->setId('test456')
              ->setContentType('test-type-w-id')
              ->setValue('title', 'My Title')
              ->setValue('body',  'My content body')
              ->save();

        // test type w. file
        $entry = new P4Cms_Content;
        $entry->setId('test789')
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
        $entry->setId('test5309')
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
     * Test view action.
     */
    public function testIndex()
    {
        $this->utility->impersonate('administrator');

        $this->dispatch('/search/manage');
        $body = $this->response->getBody();
        $this->assertModule('search', 'Last module run should be content module.' . $body);
        $this->assertController('manage', 'Expected controller' . $body);
        $this->assertAction('index', 'Expected action' . $body);

        // check that output looks sane.
        $this->assertQueryContentRegex(
            '#layout-top h1',
            '/Manage Search/',
            'Expect the page title.'
        );
    }

    /**
     * Test the search queries.
     */
    public function testGoodConfigPost()
    {
        $this->utility->impersonate('administrator');

        $maxBufferedDocs = '1000';
        $maxMergeDocs    = '12000000';
        $mergeFactor     = '100';
        $params = array(
            'maxBufferedDocs' => $maxBufferedDocs,
            'maxMergeDocs'    => $maxMergeDocs,
            'mergeFactor'     => $mergeFactor,
        );
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/search/manage');
        $responseBody = $this->response->getBody();
        $this->assertModule('search', 'Last module run should be content module.' . $responseBody);
        $this->assertController('manage', 'Expected controller.' . $responseBody);
        $this->assertAction('index', 'Expected action.' . $responseBody);

        $this->assertRedirectTo('/search/manage', 'Expected redirecting back to manage page.');

        $this->assertSame($maxBufferedDocs, Search_Module::getMaxBufferedDocs());
        $this->assertSame($maxMergeDocs, Search_Module::getMaxMergeDocs());
        $this->assertSame($mergeFactor, Search_Module::getMergeFactor());
    }

    /**
     * Test non integer tunables.
     */
    public function testBadConfigPost()
    {
        $this->utility->impersonate('administrator');

        // test 1
        $maxBufferedDocs = 'string';
        $maxMergeDocs    = '12000000';
        $mergeFactor     = '100';
        $params = array(
            'maxBufferedDocs' => $maxBufferedDocs,
            'maxMergeDocs'    => $maxMergeDocs,
            'mergeFactor'     => $mergeFactor,
        );
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/search/manage');
        $responseBody = $this->response->getBody();
        $this->assertModule('search', 'Last module run should be content module.' . $responseBody);
        $this->assertController('manage', 'Expected controller.' . $responseBody);
        $this->assertAction('index', 'Expected action.' . $responseBody);

        $this->assertResponseCode(400, 'Expected response code.' . $responseBody);

        // verify there is an error message
        $this->assertQueryContentContains(
            'ul.errors',
            "'$maxBufferedDocs' does not appear to be an integer"
        );

        $this->resetRequest();
        $this->resetResponse();

        // test 2
        $maxBufferedDocs = '2222';
        $maxMergeDocs    = '12000000ss';
        $mergeFactor     = '100';
        $params = array(
            'maxBufferedDocs' => $maxBufferedDocs,
            'maxMergeDocs'    => $maxMergeDocs,
            'mergeFactor'     => $mergeFactor,
        );
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/search/manage');
        $responseBody = $this->response->getBody();
        $this->assertModule('search', 'Last module run should be content module.' . $responseBody);
        $this->assertController('manage', 'Expected controller.' . $responseBody);
        $this->assertAction('index', 'Expected action.' . $responseBody);

        $this->assertResponseCode(400, 'Expected response code.' . $responseBody);

        // verify there is an error message
        $this->assertQueryContentContains(
            'ul.errors',
            "'$maxMergeDocs' does not appear to be an integer"
        );

        $this->resetRequest();
        $this->resetResponse();

        // test 3
        $maxBufferedDocs = '2222';
        $maxMergeDocs    = '12000000';
        $mergeFactor     = '100.01';
        $params = array(
            'maxBufferedDocs' => $maxBufferedDocs,
            'maxMergeDocs'    => $maxMergeDocs,
            'mergeFactor'     => $mergeFactor,
        );
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/search/manage');
        $responseBody = $this->response->getBody();
        $this->assertModule('search', 'Last module run should be content module.' . $responseBody);
        $this->assertController('manage', 'Expected controller.' . $responseBody);
        $this->assertAction('index', 'Expected action.' . $responseBody);

        $this->assertResponseCode(400, 'Expected response code.' . $responseBody);

        // verify there is an error message
        $this->assertQueryContentContains(
            'ul.errors',
            "'$mergeFactor' does not appear to be an integer"
        );
    }
}
