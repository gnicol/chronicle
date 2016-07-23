<?php
/**
 * Test the content type controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Test_TypeControllerTest extends ModuleControllerTest
{
    /**
     * Test the index action.
     */
    public function testIndex()
    {
        $this->utility->impersonate('administrator');

        $this->dispatch('/content/type/index');
        $this->assertModule('content', 'Expected module.');
        $this->assertController('type', 'Expected controller');
        $this->assertAction('index', 'Expected action');

        // ensure no types are listed yet.
        $body = $this->response->getBody();
        $this->assertQuery("#layout-main div.data-grid", "Expected content type list container.". $body);

        // ensure add link appears.
        $this->assertQuery("button[class='add-button']", "Expected add button.". $body);

        $this->resetRequest()->resetResponse();
        $this->dispatch('/content/type/format/json');
        $this->assertModule('content', 'Expected module.');
        $this->assertController('type', 'Expected controller');
        $this->assertAction('index', 'Expected action');

        $body = $this->response->getBody();
        $body = json_decode($body);
        $this->assertEquals(count($body->items), 0, "Expected no content types.");

        // create several content types.
        for ($i = 1; $i <= 10; $i++) {
            $type = new P4Cms_Content_Type;
            $type->setId("test-type-$i")
                 ->setLabel("Test Type $i")
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
                 ->save();
        }

        // ensure 10 content type entries are listed.
        $this->resetRequest()->resetResponse();
        $this->dispatch('/content/type/format/json');
        $this->assertModule('content', 'Expected module.');
        $this->assertController('type', 'Expected controller');
        $this->assertAction('index', 'Expected action');

        $body = $this->response->getBody();
        $body = json_decode($body);
        $this->assertEquals(count($body->items), 10, "Expected 10 content types.");
    }

    /**
     * Test the add action.
     */
    public function testAdd()
    {
        $this->utility->impersonate('administrator');

        $this->dispatch('/content/type/add');
        $this->assertModule('content', 'Expected module.');
        $this->assertController('type', 'Expected controller');
        $this->assertAction('add', 'Expected action');

        // ensure that form inputs are presented correctly.
        $this->assertQuery("#layout-main form.content-type-form", "Expected add form.");
        $this->assertQuery("input[name='id']", "Expected id input.");
        $this->assertQuery("input[name='label']", "Expected label input.");
        $this->assertQuery("input[type='file']", "Expected icon file upload input.");
        $this->assertQuery("textarea[name='description']", "Expected description input.");
        $this->assertQuery("textarea[name='elements']", "Expected elements input.");
        $this->assertQuery("input[type='submit']", "Expected submit button.");

        // ensure labels are present.
        $labels = array(
            'id'            => 'Id',
            'label'         => 'Label',
            'icon'          => 'Icon',
            'description'   => 'Description',
            'elements'      => 'Elements'
        );
        foreach ($labels as $field => $label) {
            $this->assertQueryContentContains("label[for='$field']", $label, "Expected $field label.");
        }
    }

    /**
     * Test bogus post to add.
     */
    public function testBadAddPost()
    {
        $this->utility->impersonate('administrator');
        
        // form request without required fields.
        $this->request->setMethod('POST');
        $this->request->setPost('description',  'test description');
        $this->dispatch('/content/type/add');
        $this->assertModule('content', 'Expected module.');
        $this->assertController('type', 'Expected controller');
        $this->assertAction('add', 'Expected action');

        // check for form w. errors.
        $this->assertQuery("#layout-main form.content-type-form", "Expected add form.");
        $this->assertQueryCount("ul.errors", 4, "Expected four errors.");

        // ensure description value was preserved.
        $this->assertQueryContentContains("textarea", "test description");
    }

    /**
     * Test good post to add.
     */
    public function testGoodAddPost()
    {
        $this->utility->impersonate('administrator');
        
        // form request without required fields.
        $this->request->setMethod('POST');
        $this->request->setPost('id',       'test-type');
        $this->request->setPost('label',    'Test Type');
        $this->request->setPost('group',    'test');
        $this->request->setPost('elements', "[some_field]\ntype=text");

        // fake the icon input field (w. no file selected).
        $this->utility->simulateEmptyFileInput('icon');

        $this->dispatch('/content/type/add');
        $this->assertModule('content', 'Expected module.');
        $this->assertController('type', 'Expected controller');
        $this->assertAction('add', 'Expected action');

        // expect redirect to index.
        $this->assertRedirectTo('/type', 'Expect redirect to content type index.');

        // check for saved content type entry.
        $this->assertTrue(P4Cms_Content_Type::exists('test-type'), "Expected type to be saved.");
        $type = P4Cms_Content_Type::fetch('test-type');
        $this->assertSame(
            'test-type',
            $type->getId(),
            "Expected same content type as was posted."
        );
        $this->assertSame(
            'Test Type',
            $type->getLabel(),
            "Expected same label as was posted."
        );
        $this->assertSame(
            "[some_field]\ntype=text",
            $type->getElementsAsIni(),
            "Expected same elements as were posted."
        );

        $this->resetRequest()
             ->resetResponse();

         // test that id must be unique (can't add same type twice).
        $this->request->setMethod('POST');
        $this->request->setPost('id',       'test-type');
        $this->request->setPost('label',    'Test Type');
        $this->request->setPost('group',    'test');
        $this->request->setPost('elements', "[some-field]\ntype=text");
        $this->dispatch('/content/type/add');
        $this->assertQueryCount("ul.errors", 1, "Expected id error.");
    }

    /**
     * Test edit with bad type id.
     */
    public function testEditBadId()
    {
        $this->utility->impersonate('administrator');
        
        $this->request->setParam('id', '123');

        $this->dispatch('/content/type/edit');
        $this->assertModule('error', 'Expected module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('error', 'Expected action');
    }

    /**
     * Test edit with legit type id.
     */
    public function testEditGoodId()
    {
        $this->utility->impersonate('administrator');
        
        // create content entry to be edited.
        $this->_createContentType();

        $this->request->setParam('id', 'test-type');
        $this->dispatch('/content/type/edit');
        $this->assertModule('content', 'Expected module.');
        $this->assertController('type', 'Expected controller');
        $this->assertAction('edit', 'Expected action');
    }

    /**
     * Test bogus post to edit.
     */
    public function testBadEditPost()
    {
        $this->utility->impersonate('administrator');
        
        // create content type to be edited.
        $this->_createContentType();

        // form request without required field (elements).
        $this->request->setMethod('POST');
        $this->request->setPost('id',       'test-type');
        $this->request->setPost('label',    'Edited');
        $this->dispatch('/content/type/edit');

        $this->assertModule('content', 'Expected module.');
        $this->assertController('type', 'Expected controller');
        $this->assertAction('edit', 'Expected action');

        // check for form w. errors.
        $this->assertQuery("#layout-main form", "Expected edit form.");
        $this->assertQueryCount("ul.errors", 2, "Expected two errors.");

        // ensure label value was preserved.
        $this->assertQuery("input[value='Edited']");
    }

    
    /**
     * Test good post to edit.
     */
    public function testGoodEditPost()
    {
        $this->utility->impersonate('administrator');
        
        // create content type to be edited.
        $this->_createContentType();

        // form request without required fields.
        $this->request->setMethod('POST');
        $this->request->setPost('id',       'test-type');
        $this->request->setPost('label',    'Test Type');
        $this->request->setPost('group',    'Test Group');
        $this->request->setPost('elements', "[some_field]\ntype=text");

        // fake the icon input field (w. no file selected).
        $this->utility->simulateEmptyFileInput('icon');

        $this->dispatch('/content/type/edit');
        $this->assertModule('content', 'Expected module.');
        $this->assertController('type', 'Expected controller');
        $this->assertAction('edit', 'Expected action');

        // expect redirect to index.
        $this->assertRedirectTo('/type', 'Expect redirect to content type index.');

        // check for saved content type entry.
        $this->assertTrue(P4Cms_Content_Type::exists('test-type'), "Expected type to be saved.");
        $type = P4Cms_Content_Type::fetch('test-type');
        $this->assertSame(
            'test-type',
            $type->getId(),
            "Expected same content type as was posted."
        );
        $this->assertSame(
            'Test Type',
            $type->getLabel(),
            "Expected same label as was posted."
        );
        $this->assertSame(
            "[some_field]\ntype=text",
            $type->getElementsAsIni(),
            "Expected same elements as were posted."
        );
    }

    /**
     * Test deleting.
     */
    public function testDelete()
    {
        $this->utility->impersonate('administrator');
        
        // create content type to be deleted.
        $this->_createContentType();

        $this->request->setParam('id', 'test-type');
        $this->dispatch('/content/type/delete');
        $this->assertModule('content', 'Expected module.');
        $this->assertController('type', 'Expected controller');
        $this->assertAction('delete', 'Expected action');
        
        // expect redirect to index.
        $this->assertRedirectTo('/type', 'Expect redirect to content type index.');

        // ensure content gone.
        $this->assertFalse(
            P4Cms_Content_Type::exists('test-type'),
            "Expected content type id not to exist post delete."
        );
    }

    /**
     * Test that icon is served correctly.
     */
    public function testIcon()
    {
        $this->utility->impersonate('anonymous');

        // create content type w. icon.
        $this->_createContentType();

        $this->request->setParam('id', 'test-type');
        $this->dispatch('/content/type/icon');
        $this->assertModule('content', 'Expected module.');
        $this->assertController('type', 'Expected controller');
        $this->assertAction('icon', 'Expected action');

        $this->assertHeader('content-type', 'image/png');
        $this->assertSame(
            file_get_contents(TEST_ASSETS_PATH . "/images/content-type-icon.png"),
            $this->response->getBody(),
            "Expected icon file data."
        );
    }

    /**
     * Create a test content type.
     */
    protected function _createContentType()
    {
        $type = new P4Cms_Content_Type;
        $type->setId("test-type")
             ->setLabel("Test Type")
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
    }
}
