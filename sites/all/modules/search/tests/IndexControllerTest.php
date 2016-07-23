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
class Search_Test_IndexControllerTest extends ModuleControllerTest
{
    public  $bootstrap = array('Bootstrap', 'run');

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
        $this->utility->impersonate('anonymous');

        $this->dispatch('/search/index');
        $body = $this->response->getBody();
        $this->assertModule('search', 'Last module run should be content module.'. $body);
        $this->assertController('index', 'Expected controller'. $body);
        $this->assertAction('index', 'Expected action'. $body);

        // check that output looks sane.
        $this->assertQueryContentRegex(
            '#content h1',
            '/Search/',
            'Expect the page title.'
        );
    }

    /**
     * Test url format in the pagination.
     */
    public function testPaginationUrl()
    {
        // create several content entries
        for ($i = 1; $i <= 21; $i++) {
            $entry = new P4Cms_Content;
            $entry->setId("test$i")
                  ->setContentType('test-type-w-id')
                  ->setValue('title', "test $i")
                  ->setValue('body',  "body $i")
                  ->save();
        }

        $this->request->setMethod('GET');
        $this->request->setQuery(
            array(
                'query'  => 'test body',
                'page'   => '2'
            )
        );
        $this->dispatch('/search');
        $body = $this->response->getBody();

        $this->assertModule('search', 'Expected last module: ' . $body);
        $this->assertController('index', 'Expected last controller: ' . $body);
        $this->assertAction('index', 'Expected action: ' . $body);

        // check particular page link
        $this->assertQueryContentContains(
            "div.paginationControl a[href=\"/search?query=test+body&page=1\"]",
            '1'
        );

        // check previous page link
        $this->assertQueryContentContains(
            "div.paginationControl a[href=\"/search?query=test+body&page=1\"]",
            'Previous'
        );

        // check next page link
        $this->assertQueryContentContains(
            "div.paginationControl a[href=\"/search?query=test+body&page=3\"]",
            'Next'
        );
    }

    /**
     * Test the search queries.
     */
    public function testGoodQueryPost()
    {
        $this->utility->impersonate('anonymous');

        // make sure there are contents to search
        $this->_createContent();

        //test 1
        $query = 'body';
        $params = array(
            'query'          => $query,
        );
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/search');
        $responseBody = $this->response->getBody();
        $this->assertModule('search', 'Last module run should be content module.'. $responseBody);
        $this->assertController('index', 'Expected controller.'. $responseBody);
        $this->assertAction('index', 'Expected action.'. $responseBody);

        $this->assertResponseCode(200, 'Expected response code.'. $responseBody);
        $this->assertRegexp('#/id/test123#', $responseBody, 'Expected content id');
        $this->assertRegexp('#/id/test456#', $responseBody, 'Expected content id');

        //test 2
        $query = 'empty';
        $params = array(
            'query'          => $query,
        );
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/search');
        $responseBody = $this->response->getBody();
        $this->assertModule('search', 'Last module run should be content module.'. $responseBody);
        $this->assertController('index', 'Expected controller.'. $responseBody);
        $this->assertAction('index', 'Expected action.'. $responseBody);

        $this->assertResponseCode(200, 'Expected response code.'. $responseBody);
        $this->assertRegexp('/No matching documents./', $responseBody, 'Expected empty search results.');

    }
}
