<?php
/**
 * Test the history index controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class History_Test_IndexControllerTest extends ModuleControllerTest
{
    /**
     * Perform setup
     */
    public function setUp()
    {
        parent::setUp();
        $this->utility->impersonate('administrator');
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
     * Tests the index action of the history index controller.
     * Gets history with no specified content id, expecting error.
     * Gets history for newly created content, verifies data grid structure.
     * Gets history information for newly created content, verifies json content.
     * Gets history information for revised content, verifies json content.
     */
    public function testIndexAction()
    {
        $this->dispatch('/history/index/index');

        $this->assertModule('error', 'Expected error module.');
        $this->assertController('index', 'Expected index controller');
        $this->assertAction('error', 'Expected error action');

        list($type, $entry) = $this->_createTestTypeAndEntry();

        P4Cms_Record_RegisteredType::create()
                    ->setId('content')
                    ->setRecordClass('P4Cms_Content')
                    ->setUriCallback(
                        function($id, $action, $params)
                        {
                            return call_user_func(
                                P4Cms_Content::getUriCallback(),
                                P4Cms_Content::fetch($id, array('includeDeleted' => true)),
                                $action,
                                $params
                            );
                        }
                    );

        $this->dispatch('/history/index/index/format/partial/type/content/id/' . $entry->getId());

        $body = $this->getResponse()->getBody();

        $this->assertModule('history', 'Expected module, dispatch #1. '. $body);
        $this->assertController('index', 'Expected controller, dispatch #1 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #1 '. $body);

        // ensure table and dojo data elements exist
        $this->assertXpath('//div[@dojotype="dojox.data.QueryReadStore"]', 'Expected dojo.data div');
        $this->assertXpath('//table[@dojotype="p4cms.ui.grid.DataGrid"]', 'Expected dojox.grid table');

        // check initial JSON output
        $this->resetRequest()->resetResponse();

        $this->dispatch('/history/format/json/type/content/id/' . $entry->getId() . '?start=0&count=100');

        $body = $this->response->getBody();
        $this->assertModule('history', 'Expected module, dispatch #2. '. $body);
        $this->assertController('index', 'Expected controller, dispatch #2 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #2 '. $body);

        $data = Zend_Json::decode($body);

        $this->assertSame(1, count($data['items']), 'Expected one item');

        $this->assertSame($data['items'][0]['version'], '1', 'Expected version to match');
        $this->assertSame($data['items'][0]['user'], 'tester', 'Expected user to match');
        $this->assertSame($data['items'][0]['date'], 'just now', 'Expected date to match');
        $this->assertSame($data['items'][0]['description'], "a test entry\n", 'Expected description to match');

        // revise, save, and verify
        $entry->setValue('title', 'Revised Test Title')
             ->setValue('body', 'A different test body')
             ->setValue('abstract', 'Abstractification.');
        $entry->save('modified test entry');

        $this->resetRequest()->resetResponse();
        $this->dispatch('/history/format/json/type/content/id/' . $entry->getId() . '?start=0&count=100');

        $body = $this->response->getBody();
        $this->assertModule('history', 'Expected module, dispatch #2. '. $body);
        $this->assertController('index', 'Expected controller, dispatch #2 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #2 '. $body);

        $data = Zend_Json::decode($body);

        $this->assertSame(2, count($data['items']), 'Expected two items');

        $this->assertSame($data['items'][0]['version'], '2', 'Expected version to match');
        $this->assertSame($data['items'][0]['user'], 'tester', 'Expected user to match');
        $this->assertSame($data['items'][0]['date'], 'just now', 'Expected date to match');
        $this->assertSame(
            $data['items'][0]['description'],
            "modified test entry\n",
            'Expected description to match'
        );
    }

    /**
     * Tests the toolbar generation.
     *
     * @return void
     */
    public function testToolbarAction()
    {
        $this->dispatch('/history/index/toolbar/');

        $this->assertModule('error', 'Expected error module.');
        $this->assertController('index', 'Expected index controller');
        $this->assertAction('error', 'Expected error action');

        list($type, $entry) = $this->_createTestTypeAndEntry();

        P4Cms_Record_RegisteredType::create()
                    ->setId('content')
                    ->setRecordClass('P4Cms_Content')
                    ->setUriCallback(
                        function($id, $action, $params)
                        {
                            return call_user_func(
                                P4Cms_Content::getUriCallback(),
                                P4Cms_Content::fetch($id, array('includeDeleted' => true)),
                                $action,
                                $params
                            );
                        }
                    );

        $this->resetRequest()->resetResponse();

        $this->dispatch('/history/index/toolbar/format/partial/type/content/id/' . $entry->getId());

        $body = $this->getResponse()->getBody();

        $this->assertModule('history', 'Expected module, dispatch #1. '. $body);
        $this->assertController('index', 'Expected controller, dispatch #1 '. $body);
        $this->assertAction('toolbar', 'Expected action, dispatch #1 '. $body);

        $this->assertXpathContentContains(
            '//div[@dojotype="dijit.MenuItem"]',
            'Version 1 by tester just now',
            'Expected dijit menu item with version and user information.'
        );
        $this->assertQueryContentContains(
            'div.middle > span.change > span.action',
            'added',
            'Expected action.'
        );
        $this->assertQueryContentContains(
            'div.middle > span.change > span.user',
            'tester',
            'Expected user.'
        );
        $this->assertQueryContentContains(
            'div.middle > span.change > span.date',
            'just now',
            'Expected date.'
        );
        $this->assertQueryContentContains(
            'div.middle > span.change > span.description',
            'a test entry',
            'Expected entry.'
        );

        $this->assertQueryContentContains(
            'div',
            "p4cms.history.view('content', '1');",
            'Expected history list button javascript.'
        );
    }
}