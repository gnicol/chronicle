<?php
/**
 * Test the menu manage controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_Test_ManageControllerTest extends ModuleControllerTest
{
    /**
     * Impersonate the administrator for all manage tests.
     */
    public function setUp()
    {
        parent::setUp();

        $this->utility->impersonate('administrator');
    }

    /**
     * Test the index action.
     */
    public function testIndex()
    {
        $this->dispatch('/menu/manage/index');
        $this->assertModule('menu', 'Expected module.');
        $this->assertController('manage', 'Expected controller');
        $this->assertAction('index', 'Expected action');

        // verify that table and dojo data elements exist
        $this->assertXpath('//div[@dojotype="dojox.data.QueryReadStore"]', 'Expected dojo.data div');
        $this->assertXpath(
            '//table[@dojotype="p4cms.ui.grid.DataGrid" and @jsid="p4cms.menu.grid.instance"]',
            'Expected dojox.grid table'
        );

        // create several menus and items.
        $items = array();
        for ($i = 1; $i <= 10; $i++) {
            $pages = array(
                'label'      => "Item $i",
                'uri'        => "http://$i.com",
                'order'      => $i,
                'uuid'       => "menuItem$i",
                'pages'      => array(
                    array(
                        'label'      => "Nested Item $i",
                        'uri'        => 'http://google2.com',
                        'order'      => $i,
                        'uuid'       => "menuSubItem$i"
                    )
                )
            );

            $menu = new P4Cms_Menu;
            $menu->setId("menu$i")
                 ->setContainer(new P4Cms_Navigation(array($pages)))
                 ->save();

            $items[] = $menu;
        }

        // check JSON output
        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/menu/manage/format/json');
        $body = $this->response->getBody();
        $this->assertModule('menu', 'Expected module, dispatch #2. '. $body);
        $this->assertController('manage', 'Expected controller, dispatch #2 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #2 '. $body);

        $data = Zend_Json::decode($body);

        // verify number of items
        $this->assertSame(
            count(P4Cms_Menu::fetchMixed()),
            $data['numRows'],
            'Expected number of items'
        );

        // verify item values
        $items = P4Cms_Menu::fetchMixed();
        foreach ($data['items'] as $dataItem) {
            // find the item by id in the full list
            $item = $items->filter('id', $dataItem['id'], P4Cms_Model_Iterator::FILTER_COPY)
                          ->first()
                          ->toArray();

            // we expect the dataItem may have additional fields, only compare those that
            // exist in the original item
            $this->assertSame(
                $item,
                array_intersect($dataItem, $item),
                'Expected item values'
            );
        }
    }

    /**
     * Test the index action, this time using the type filter
     *
     * Create a menu with two menu items in it.  Filter for a dynamic type, to satisfy code
     * coverage.
     */
    public function testFilteredIndex()
    {
        // create menu
        $menu = new P4Cms_Menu;
        $menu->setValues(
            array(
                'label' => 'test menu',
                'id'    => 'testmenu'
            )
        )->save();

        // create dynamic menu item
        $dynamicItem = array(
            'menuId'    => 'testmenu',
            'id'        => 'testitem',
            'uuid'      => 'testitem',
            'position'  => 'under',
            'location'  => 'testmenu',
            'type'      => 'P4Cms_Navigation_Page_Dynamic',
            'handler'   => 'user.login-logout',
            'maxDepth'  => 'Unlimited',
            'maxItems'  => 'Unlimited'
        );

        $dynamicItem = P4Cms_Navigation::inferPageType($dynamicItem);
        $dynamicItem = Zend_Navigation_Page::factory($dynamicItem);

        $uriItem = array(
            'menuId'    => 'testmenu',
            'id'        => 'testitem2',
            'uuid'      => 'testitem2',
            'label'     => 'testItem2',
            'position'  => 'under',
            'location'  => 'testmenu',
            'type'      => 'Zend_Navigation_Page_Uri',
            'uri'       => 'http://perforce.com',
        );

        $uriItem = P4Cms_Navigation::inferPageType($uriItem);
        $uriItem = Zend_Navigation_Page::factory($uriItem);

        $menu->getContainer()->addPages(array($dynamicItem, $uriItem));
        $menu->save();

        $this->dispatch('/menu/manage/format/json?type[display][]=P4Cms_Navigation_Page_Dynamic/user.login-logout');
        $body = $this->response->getBody();

        $this->assertModule('menu', 'Expected menu module. '. $body);
        $this->assertController('manage', 'Expected manage controller '. $body);
        $this->assertAction('index', 'Expected index action '. $body);

        $data = Zend_Json::decode($body);
        $this->assertEquals($data['numRows'], 2, 'Expected two rows: menu and menu item.' . $body);

        $this->assertEquals($data['items'][0]['type'], 'P4Cms_Menu', 'Expected Menu item.' . $body);
        $this->assertEquals(
            $data['items'][1]['type'],
            'P4Cms_Navigation_Page_Dynamic',
            'Expected dynamic page item.' . $body
        );
    }

    /**
     * Test adding a menu.
     */
    public function testGoodAdd()
    {
        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'label' => 'test menu',
                'id'    => 'testmenu'
            )
        );

        $this->dispatch('/menu/manage/add/');

        $this->assertModule('menu', 'Expected menu module.');
        $this->assertController('manage', 'Expected manage controller.');
        $this->assertAction('add', 'Expected add action.');

        $this->assertXpath(
            '//input[@id="label" and @value="test menu"]',
            'Expected label element with value test menu'
        );

        $this->assertXpath(
            '//input[@id="id" and @value="testmenu"]',
            'Expected id element with value testmenu'
        );
    }

    /**
     * Test error handling when required data is missing.
     */
    public function testBadAdd()
    {
        $this->request->setMethod('POST');
        $this->request->setPost(
            array()
        );

        $this->dispatch('/menu/manage/add/');

        $this->assertModule('menu', 'Expected menu module.');
        $this->assertController('manage', 'Expected manage controller.');
        $this->assertAction('add', 'Expected add action.');

        $this->assertQueryContentContains(
            'dd#label-element > ul.errors > li',
            "Value is required and can't be empty",
            'Expected required label element message.'
        );

        $this->assertQueryContentContains(
            'dd#id-element ul.errors > li',
            "Value is required and can't be empty",
            'Expected required id element message.'
        );
    }

    /**
     * Test error handling when adding duplicate id.
     */
    public function testDuplicateAdd()
    {
        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'label' => 'test menu',
                'id'    => 'testmenu'
            )
        );

        $this->dispatch('/menu/manage/add/');

        $this->assertModule('menu', 'Expected menu module.');
        $this->assertController('manage', 'Expected manage controller.');
        $this->assertAction('add', 'Expected add action.');

        $this->resetRequest();
        $this->resetResponse();

        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'label' => 'test menu',
                'id'    => 'testmenu'
            )
        );

        $this->dispatch('/menu/manage/add/');

        $this->assertModule('menu', 'Expected menu module.');
        $this->assertController('manage', 'Expected manage controller.');
        $this->assertAction('add', 'Expected add action.');

        $this->assertQueryContentContains(
            'ul.errors > li',
            'The specified ID is already in use.',
            'Expected no items message.'
        );
    }

    /**
     * Test edit with valid values
     */
    public function testGoodEdit()
    {
        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'label' => 'test menu',
                'id'    => 'testmenu'
            )
        );

        $this->dispatch('/menu/manage/add/');

        $this->resetRequest();
        $this->resetResponse();

        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'label' => 'revised test menu',
                'id'    => 'testmenu'
            )
        );

        $this->dispatch('/menu/manage/edit/id/testmenu');

        $this->assertModule('menu', 'Expected menu module.');
        $this->assertController('manage', 'Expected manage controller.');
        $this->assertAction('edit', 'Expected edit action.');
    }

    /**
     * Test edit with bad values to verify error handling
     */
    public function testBadEdit()
    {
        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'label' => 'test menu',
                'id'    => 'testmenu'
            )
        );

        $this->dispatch('/menu/manage/add/');

        $this->resetRequest();
        $this->resetResponse();

        $this->request->setMethod('POST');
        $this->request->setPost(array());

        $this->dispatch('/menu/manage/edit/id/testmenu');

        $this->assertModule('menu', 'Expected menu module.');
        $this->assertController('manage', 'Expected manage controller.');
        $this->assertAction('edit', 'Expected edit action.');

        $this->assertQueryContentContains(
            'dd#label-element > ul.errors > li',
            "Value is required and can't be empty",
            'Expected required label element message.'
        );
    }

    /**
     * Test a bad delete action - post is not a valid method for this action.
     */
    public function testBadDelete()
    {
        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'label' => 'test menu',
                'id'    => 'testmenu'
            )
        );

        $this->dispatch('/menu/manage/add/');

        $this->resetRequest();
        $this->resetResponse();

        $this->dispatch('/menu/manage/delete/id/testmenu');

        $this->assertModule('error', 'Expected error module.');
        $this->assertController('index', 'Expected index controller.');
        $this->assertAction('access-denied', 'Expected access denied action.');
    }

    /**
     * Test a valid delete action.
     */
    public function testGoodDelete()
    {
        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'label' => 'test menu',
                'id'    => 'testmenu'
            )
        );

        $this->dispatch('/menu/manage/add/');

        $this->resetRequest();
        $this->resetResponse();

        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'id'    => 'testmenu'
            )
        );

        $this->dispatch('/menu/manage/delete/format/json');

        $this->assertModule('menu', 'Expected menu module.');
        $this->assertController('manage', 'Expected index controller.');
        $this->assertAction('delete', 'Expected delete action.');
    }

    /**
     * Test the reset action by adding a menu, verifying it's there, then resetting and verifying
     * it's not.
     */
    public function testReset()
    {
        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'label' => 'test menu',
                'id'    => 'testmenu'
            )
        );

        $this->dispatch('/menu/manage/add/');

        $this->resetRequest();
        $this->resetResponse();

        $this->dispatch('/menu/manage/index/');

        $this->assertXpath('//div[@dojotype="dojox.data.QueryReadStore"]', 'Expected dojo.data div');
        $this->assertXpath(
            '//table[@dojotype="p4cms.ui.grid.DataGrid" and @jsid="p4cms.menu.grid.instance"]',
            'Expected dojox.grid table'
        );
        $this->assertXpath('//input[@value="testmenu"]', 'Expected testmenu menu entry');

        $this->resetRequest();
        $this->resetResponse();

        $this->dispatch('/menu/manage/reset/');

        $this->assertModule('menu', 'Expected menu module.');
        $this->assertController('manage', 'Expected index controller.');
        $this->assertAction('reset', 'Expected reset action.');

        $this->resetRequest();
        $this->resetResponse();

        $this->dispatch('/menu/manage/index/');

        $this->assertXpath('//div[@dojotype="dojox.data.QueryReadStore"]', 'Expected dojo.data div');
        $this->assertXpath(
            '//table[@dojotype="p4cms.ui.grid.DataGrid" and @jsid="p4cms.menu.grid.instance"]',
            'Expected dojox.grid table'
        );
        $this->assertNotXpath('//input[@value="testmenu"]', 'Did not expect testmenu menu entry');
    }

    /**
     * Test the add item action by adding an item, then verifying it's there.
     */
    public function testGoodAddItemAction()
    {
        $menu = new P4Cms_Menu;
        $menu->setValues(
            array(
                'label' => 'test menu',
                'id'    => 'testmenu'
            )
        )->save();

        // test with non-post request, expect form
        $this->dispatch('/menu/manage/add-item/menuId/testmenu');

        $this->assertModule('menu', 'Expected menu module.');
        $this->assertController('manage', 'Expected manage controller.');
        $this->assertAction('add-item', 'Expected add-item action.');

        $this->assertXpath('//form[@class="p4cms-ui menu-item-form"]', 'Expected menu item form.');

        $this->resetRequest()->resetResponse();

        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'menuId'    => 'testmenu',
                'label'     => 'testItem',
                'position'  => 'under',
                'location'  => 'testmenu',
                'type'      => 'Zend_Navigation_Page_Uri',
                'uri'       => 'http://perforce.com',
                'target'    => '_self',
                'class'     => 'foobar'
            )
        );

        $this->dispatch('/menu/manage/add-item/');

        $this->assertModule('menu', 'Expected menu module.');
        $this->assertController('manage', 'Expected index controller.');
        $this->assertAction('add-item', 'Expected reset action.');

        $menu = P4Cms_Menu::fetch('testmenu');
        $item = $menu->getContainer()->findBy('label', 'testItem');

        $this->assertTrue(
            $item instanceof Zend_Navigation_Page_Uri,
            'Expected proper menu item object type.'
        );

        $this->assertEquals(
            $item->getUri(),
            'http://perforce.com',
            'Expected saved uri value to match.'
        );

        $this->assertEquals(
            $item->getParent(),
            $menu->getContainer(),
            'Expected menu item to have correct parent container.'
        );

        // test adding with no menu
        $this->resetRequest()->resetResponse();

        $this->dispatch('/menu/manage/add-item/');

        $this->assertModule('menu', 'Expected menu module.');
        $this->assertController('manage', 'Expected manage controller.');
        $this->assertAction('add-item', 'Expected add-item action.');

        // verify that location option is blank by default
        $this->assertQuery(
            'select#location option[@value=""][@selected="selected"]',
            'Expected menu item form.'
        );

        // test adding with no menu again with posted data and verify menu item is saved correctly
        $this->resetRequest()->resetResponse();

        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'menuId'    => '',
                'label'     => 'fooItem',
                'position'  => 'under',
                'location'  => 'testmenu',
                'type'      => 'Zend_Navigation_Page_Uri',
                'uri'       => 'http://foo.com',
            )
        );

        $this->dispatch('/menu/manage/add-item/');

        $this->assertModule('menu', 'Expected menu module.');
        $this->assertController('manage', 'Expected index controller.');
        $this->assertAction('add-item', 'Expected reset action.');

        $menu = P4Cms_Menu::fetch('testmenu');
        $item = $menu->getContainer()->findBy('label', 'fooItem');

        $this->assertTrue(
            $item instanceof Zend_Navigation_Page_Uri,
            'Expected proper menu item object type.'
        );

        $this->assertEquals(
            $item->getUri(),
            'http://foo.com',
            'Expected saved uri value to match.'
        );

        $this->assertEquals(
            $item->getParent(),
            $menu->getContainer(),
            'Expected menu item to have correct parent container.'
        );
    }

    /**
     * Test the order functionalty by adding multiple idems in different positions, then
     * verifying.
     */
    public function testItemOrder()
    {
        $menu = new P4Cms_Menu;
        $menu->setValues(
            array(
                'label' => 'test menu',
                'id'    => 'testmenu'
            )
        )->save();

        $item = array(
            'menuId'    => 'testmenu',
            'label'     => 'testItem #0',
            'position'  => 'under',
            'location'  => 'testmenu',
            'type'      => 'Zend_Navigation_Page_Uri',
            'uri'       => 'http://perforce.com',
            'target'    => '_self',
            'class'     => 'foobar'
        );

        $this->request->setMethod('POST');
        $this->request->setPost($item);

        $this->dispatch('/menu/manage/add-item/');

        $this->assertModule('menu', 'Expected menu module.');
        $this->assertController('manage', 'Expected index controller.');
        $this->assertAction('add-item', 'Expected reset action.');

        $menu = P4Cms_Menu::fetch('testmenu');
        $underItem = $menu->getContainer()->findBy('label', 'testItem #0');

        // verify the item exists, has the correct parent
        $this->assertNotNull($underItem, 'Expected menu item with label "testItem #0" to be not null');

        $this->assertEquals(
            $underItem->getParent(),
            $menu->getContainer(),
            'Expected menu item to have correct parent container.'
        );

        $this->resetRequest()->resetResponse();

        $item['label']    = 'testItem #1';
        $item['position'] = 'after';
        $item['location'] = 'testmenu/' . $underItem->uuid;

        $this->request->setMethod('POST');
        $this->request->setPost($item);

        $this->dispatch('/menu/manage/add-item/');

        $responseBody = $this->getResponse()->getBody();

        $this->assertModule('menu', 'Expected menu module.' . $responseBody);
        $this->assertController('manage', 'Expected index controller.' . $responseBody);
        $this->assertAction('add-item', 'Expected reset action.' . $responseBody);

        $menu = P4Cms_Menu::fetch('testmenu');
        $afterItem  = $menu->getContainer()->findBy('label', 'testItem #1');

        // verify item and parent
        $this->assertNotNull($afterItem, 'Expected menu item with label "testItem #1" to be not null');

        $this->assertEquals(
            $afterItem->getParent(),
            $menu->getContainer(),
            'Expected menu item to have correct parent container.'
        );

        $this->assertGreaterThan(
            $underItem->getOrder(),
            $afterItem->getOrder(),
            'Expected the order for ' . $afterItem->getLabel() . ' to be greater than that of '
            . $underItem->getLabel() . '.'
        );

        $this->resetRequest()->resetResponse();

        $items = $menu->getContainer()->findAllBy('class', 'foobar');

        $item['label']    = 'testItem #2';
        $item['position'] = 'before';
        $item['location'] = 'testmenu/' . $afterItem->uuid;

        $this->request->setMethod('POST');
        $this->request->setPost($item);

        $this->dispatch('/menu/manage/add-item/');

        $this->assertModule('menu', 'Expected menu module.');
        $this->assertController('manage', 'Expected index controller.');
        $this->assertAction('add-item', 'Expected reset action.');

        // refetch $afterItem - should be updated to reflect its new position
        $menu = P4Cms_Menu::fetch('testmenu');
        $afterItem  = $menu->getContainer()->findBy('label', 'testItem #1');
        $beforeItem = $menu->getContainer()->findBy('label', 'testItem #2');

        $this->assertNotNull($beforeItem, 'Expected menu item with label "testItem #2" to be not null');

        $this->assertEquals(
            $beforeItem->getParent(),
            $menu->getContainer(),
            'Expected menu item to have correct parent container.'
        );

        $this->assertGreaterThan(
            $beforeItem->getOrder(),
            $afterItem->getOrder(),
            'Expected the order for ' . $afterItem->getLabel() . ' to be greater than that of '
            . $beforeItem->getLabel() . '.'
        );
    }

    /**
     * Test the add item action by adding an item with no menu specified and with invalid form
     * data (missing required field), then verifying the error.
     */
    public function testBadAddItemAction()
    {
        // test with invalid form data
        $parentMenu = new P4Cms_Menu;
        $parentMenu->setValues(
            array(
                'label' => 'test menu',
                'id'    => 'testmenu'
            )
        )->save();

        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'menuId'    => 'testmenu',
                'label'     => '',
                'position'  => 'under',
                'location'  => 'parent',
                'location'  => 'testmenu',
                'type'      => 'Zend_Navigation_Page_Uri',
                'uri'       => 'http://perforce.com',
                'target'    => '_self',
                'class'     => 'foobar'
            )
        );

        $this->dispatch('/menu/manage/add-item/menuId/testmenu');

        $this->assertModule('menu', 'Expected menu module.');
        $this->assertController('manage', 'Expected manage controller.');
        $this->assertAction('add-item', 'Expected add-item action.');
        $this->assertResponseCode(400, 'Expected bad request response code.');
    }

    /**
     * Test unsuccessful edit item actions.
     */
    public function testBadEditItemAction()
    {
        // create item to edit
        $menu = new P4Cms_Menu;
        $menu->setValues(
            array(
                'label' => 'test menu',
                'id'    => 'testmenu'
            )
        )->save();

        $itemValues = array(
            'menuId'    => 'testmenu',
            'id'        => 'testitem',
            'uuid'      => 'testitem',
            'label'     => 'testItem',
            'position'  => 'under',
            'location'  => 'testmenu',
            'type'      => 'Zend_Navigation_Page_Uri',
            'uri'       => 'http://perforce.com',
            'target'    => '_self',
            'class'     => 'foobar'
        );

        $item = P4Cms_Navigation::inferPageType($itemValues);
        $item = Zend_Navigation_Page::factory($item);

        $menu->getContainer()->addPage($item);
        $menu->save();

        // test edit item, with invalid data
        $itemValues['label'] = '';

        $this->getRequest()->setMethod('POST');
        $this->getRequest()->setPost($itemValues);

        $this->dispatch('/menu/manage/edit-item/menuId/testmenu/id/testitem');

        $responseBody = $this->getResponse()->getBody();

        $this->assertModule('menu', 'Expected menu module.' . $responseBody);
        $this->assertController('manage', 'Expected index controller.' . $responseBody);
        $this->assertAction('edit-item', 'Expected index action.' . $responseBody);

        $this->assertResponseCode(400, 'Expected bad request response code.');
        $this->assertQueryContentContains(
            'dd#label-element ul.errors > li',
            "Value is required and can't be empty",
            'Expected required id element message.' . $responseBody
        );
    }

    /**
     * Test successful edit item actions.
     */
    public function testGoodEditItemAction()
    {
        // verify the form when editing a menu item
        $menu = new P4Cms_Menu;
        $menu->setValues(
            array(
                'label' => 'test menu',
                'id'    => 'testmenu'
            )
        )->save();

        $itemValues = array(
            'menuId'    => 'testmenu',
            'id'        => 'testitem',
            'uuid'      => 'testitem',
            'label'     => 'testItem',
            'position'  => 'under',
            'location'  => 'testmenu',
            'type'      => 'Zend_Navigation_Page_Uri',
            'uri'       => 'http://perforce.com',
            'target'    => '_self',
            'class'     => 'foobar'
        );

        $item = P4Cms_Navigation::inferPageType($itemValues);
        $item = Zend_Navigation_Page::factory($item);

        $menu->getContainer()->addPage($item);
        $menu->save();

        $this->dispatch('/menu/manage/edit-item/menuId/testmenu/id/testitem');

        $responseBody = $this->getResponse()->getBody();

        $this->assertModule('menu', 'Expected menu module.' . $responseBody);
        $this->assertController('manage', 'Expected index controller.' . $responseBody);
        $this->assertAction('edit-item', 'Expected index action.' . $responseBody);

        $this->assertXpath(
            '//form[@class="p4cms-ui menu-item-form"]',
            "Expected item edit form." . $responseBody
        );

        // confirm item is loaded into the form
        $this->assertQuery(
            "input[name='uuid'][type='hidden'][value='testitem']",
            "Expected accountNumber input." . $responseBody
        );

        $this->assertQuery(
            "input[name='menuId'][type='hidden'][value='testmenu']",
            "Expected accountNumber input." . $responseBody
        );

        $this->resetRequest()->resetResponse();

        // modify the menu item that was saved earlier, save the form, and verify the change
        $itemValues['label'] = 'ModifiedTestItem';

        $this->getRequest()->setMethod('POST');
        $this->getRequest()->setPost($itemValues);

        $this->dispatch('/menu/manage/edit-item/menuId/testmenu/id/testitem');

        $responseBody = $this->getResponse()->getBody();

        $this->assertModule('menu', 'Expected menu module.' . $responseBody);
        $this->assertController('manage', 'Expected index controller.' . $responseBody);
        $this->assertAction('edit-item', 'Expected index action.' . $responseBody);

        // verify saved change
        $menu = P4Cms_Menu::fetch('testmenu');
        $item = $menu->getContainer()->findBy('uuid', 'testitem');
        $this->assertEquals($item->label, 'ModifiedTestItem', 'Expected test item label to be modified.');
    }

    /**
     * Test the delete item functionality when an invalid method is used.
     *
     * Note that when this test and testDeleteItemInvalidId are run together as one test,
     * it exposes issues with the error handler and results in a failure of the assertions
     * in testDeleteItemInvalidId as the exeception is not handled properly.
     */
    public function testDeleteItemNoPost()
    {
        // attempt via get, expect error
        $this->dispatch('/menu/manage/delete-item/menuId/testmenu');
        $responseBody = $this->getResponse()->getBody();

        $this->assertModule('error', 'Expected error module.' . $responseBody);
        $this->assertController('index', 'Expected index controller.' . $responseBody);
        $this->assertAction('access-denied', 'Expected index action.' . $responseBody);

        $this->assertRegexp(
            '/Deleting menu items is not permitted in this context\./',
            $responseBody,
            'Expected error message regarding invalid context.'
        );
    }

    /**
     * Test the delete item functionality when an invalid id is provided via the correct
     * method; expect an exception.
     *
     * See comments on testDeleteItemNoPost.
     */
    public function testDeleteItemInvalidId()
    {
        // dispatch via post with nonexistant id, expect exception
        $parentMenu = new P4Cms_Menu;
        $parentMenu->setValues(
            array(
                'label' => 'test menu',
                'id'    => 'testmenu'
            )
        )->save();

        $this->getRequest()->setMethod('POST');
        $this->getRequest()->setPost(
            array(
                'id' => 'nonexistant_id'
            )
        );

        $this->dispatch('/menu/manage/delete-item/menuId/testmenu/');

        $responseBody = $this->getResponse()->getBody();

        $this->assertModule('error', 'Expected error module.' . $responseBody);
        $this->assertController('index', 'Expected index controller.' . $responseBody);
        $this->assertAction('error', 'Expected error action.' . $responseBody);

        $this->assertRegexp(
            '/P4Cms_Record_NotFoundException/',
            $responseBody,
            'Expected P4Cms_Record_NotFoundException to be thrown'
        );
    }

    /**
     * Test a valid item deletion, expect success.
     */
    public function testItemDeleteValid()
    {
        $parentMenu = new P4Cms_Menu;
        $parentMenu->setValues(
            array(
                'label' => 'test menu',
                'id'    => 'testmenu'
            )
        )->save();

        // via post with existing id, expect success
        $item = array(
            'menuId'    => 'testmenu',
            'id'        => 'testitem',
            'uuid'      => 'testitem',
            'label'     => 'testItem',
            'position'  => 'under',
            'location'  => 'testmenu',
            'type'      => 'Zend_Navigation_Page_Uri',
            'uri'       => 'http://perforce.com',
            'target'    => '_self',
            'class'     => 'foobar'
        );

        $item = P4Cms_Navigation::inferPageType($item);
        $item = Zend_Navigation_Page::factory($item);

        $parentMenu->getContainer()->addPage($item);
        $parentMenu->save();

        $menu = P4Cms_Menu::fetch('testmenu');
        $item = $menu->getContainer()->findOneBy('uuid', 'testitem');

        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'id' => 'testitem'
            )
        );

        $this->dispatch('/menu/manage/delete-item/menuId/testmenu/format/json');

        $responseBody = $this->getResponse()->getBody();

        $this->assertModule('menu', 'Expected menu module.' . $responseBody);
        $this->assertController('manage', 'Expected index controller.' . $responseBody);
        $this->assertAction('delete-item', 'Expected index action.' . $responseBody);

        $menu = P4Cms_Menu::fetch('testmenu');
        $item = $menu->getContainer()->findOneBy('id', 'testitem');
        $this->assertEquals(null, $item, 'Expected null item.');
    }

    /**
     * Test the item form action by iterating over the possible types
     * and verifying that the form is adjusted accordingly.
     */
    public function testItemFormAction()
    {
        $item = array(
            'menuId'    => 'testmenu',
            'position'  => 'under',
            'location'  => 'testmenu',
        );

        $pageTypeTestData = array(
            'P4Cms_Navigation_Page_Mvc' => array(
                "option[value='P4Cms_Navigation_Page_Mvc'][selected='selected']",
                "select[name='action']",
                "textarea[name='params']"
            ),
            'P4Cms_Navigation_Page_Content' => array(
                "option[value='P4Cms_Navigation_Page_Content'][selected='selected']",
                "div[id='contentId']"
            ),
            'P4Cms_Navigation_Page_Heading' => array(
                "option[value='P4Cms_Navigation_Page_Heading'][selected='selected']"
            ),
            'Zend_Navigation_Page_Uri' => array(
                "option[value='Zend_Navigation_Page_Uri'][selected='selected']",
                "input[type='text'][name='uri']",
                "select[name='target']"
            ),
            'P4Cms_Navigation_Page_Dynamic/categories' => array(
                'option[value*="P4Cms_Navigation_Page_Dynamic"][selected="selected"]',
                "select[name='maxDepth']",
                "select[name='maxItems']",
                "input[name='includeEntries']"
            ),
        );

        foreach ($pageTypeTestData as $pageType => $queries) {
            $this->getRequest()->setMethod('POST');
            $this->getRequest()->setPost(
                array_merge($item, array('type' => $pageType))
            );

            $this->dispatch('/menu/manage/item-form/');

            $responseBody = $this->getResponse()->getBody();

            foreach ($queries as $query) {
                $this->assertQuery(
                    $query,
                    "Query $query failed for menu item type $pageType." . $responseBody
                );
            }

            $this->resetRequest()->resetResponse();
        }
    }
}