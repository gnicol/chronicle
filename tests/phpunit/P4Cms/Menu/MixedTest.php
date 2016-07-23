<?php
/**
 * Test the menu mixed model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Menu_MixedTest extends TestCase
{
    /**
     * Verify the ID is read only
     *
     * @expectedException P4Cms_Model_Exception
     */
    public function testSetId()
    {
        $mixed = new P4Cms_Menu_Mixed;
        $mixed->setId('foo');
    }

    /**
     * Verify getId correctly reflects menu/menuItem id
     */
    public function testGetId()
    {
        $mixed = new P4Cms_Menu_Mixed;

        $this->assertSame(
            null,
            $mixed->getId(),
            'Expected matching starting id'
        );

        $menuItem = new Zend_Navigation_Page_Uri;
        $menuItem->uuid = 'menuItemId';
        $mixed->setMenuItem($menuItem);
        
        $this->assertSame(
            null,
            $mixed->getId(),
            'Expected matching id with just a menu item set'
        );

        $menu = new P4Cms_Menu;
        $menu->setId('menuId');
        $mixed->setMenu($menu);

        $this->assertSame(
            'menuId/menuItemId',
            $mixed->getId(),
            'Expected matching id with menu and menu item'
        );

        $mixed->setMenuItem(null);

        $this->assertSame(
            'menuId',
            $mixed->getId(),
            'Expected matching id with just a menu'
        );
    }

    /**
     * Exercise the get/set Type methods
     */
    public function testGetSetType()
    {
        $mixed = new P4Cms_Menu_Mixed;

        $this->assertSame(
            null,
            $mixed->getType(),
            'Expected matching starting state'
        );
        
        $mixed->setMenuItem(new Zend_Navigation_Page_Uri);
    
        $this->assertSame(
            'Zend_Navigation_Page_Uri',
            $mixed->getType(),
            'Expected matching default after assigning menu item'
        );

        $mixed->setMenu(new P4Cms_Menu)->setMenuItem(null);
        $this->assertSame(
            'P4Cms_Menu',
            $mixed->getType(),
            'Expected set value even with no menu item'
        );
    }

    /**
     * Test the get/set Depth methods
     */
    public function testGetSetDepth()
    {
        $mixed = new P4Cms_Menu_Mixed;

        $this->assertSame(
            0,
            $mixed->getDepth(),
            'Expected matching default value'
        );

        $mixed = new P4Cms_Menu_Mixed(array('depth' => 10));

        $this->assertSame(
            10,
            $mixed->getDepth(),
            'Expected passing value to constructor to work'
        );

        $this->assertSame(
            2,
            $mixed->setDepth(2)->getDepth(),
            'Expected low value to work'
        );

        $this->assertSame(
            -2,
            $mixed->setDepth(-2)->getDepth(),
            'Expected negative value to work'
        );

        $this->assertSame(
            64,
            $mixed->setDepth(64)->getDepth(),
            'Expected larger value to work'
        );

        try {
            $mixed->setDepth('low');
            $this->fail('Expected InvalidArgumentException when setting invalid depth');
        }
        catch (InvalidArgumentException $e) {
            $this->assertEquals(
                $e->getMessage(),
                'Depth must be an int',
                'Expected exception message did not appear'
            );
        }
    }

    /**
     * Test bad call to setDepth
     *
     * @expectedException InvalidArgumentException
     */
    public function testBadSetDepth()
    {
        $mixed = new P4Cms_Menu_Mixed;
        $mixed->setDepth(false);
    }

    /**
     * Test the get parent id method
     */
    public function testGetParentId()
    {
        $mixed = new P4Cms_Menu_Mixed;

        $this->assertSame(
            null,
            $mixed->getParentId(),
            'Expected matching starting value'
        );

        $menuItem = new Zend_Navigation_Page_Uri;
        $menuItem->uuid = 'parentItem';
        $mixed->setParentMenuItem($menuItem);

        $this->assertSame(
            null,
            $mixed->getParentId(),
            'Expected matching value with just parent menu item'
        );

        $menu = new P4Cms_Menu;
        $menu->setId('menu');
        $mixed->setMenu($menu);

        $this->assertSame(
            'menu/parentItem',
            $mixed->getParentId(),
            'Expected matching value with menu and parent menu item'
        );

        $menuItem = new Zend_Navigation_Page_Uri;
        $menuItem->uuid = 'menuItem';
        $mixed->setMenuItem($menuItem);

        $this->assertSame(
            'menu/parentItem',
            $mixed->getParentId(),
            'Expected matching value with menu, menu item and parent menu item'
        );

        $mixed->setParentMenuItem();
        $this->assertSame(
            'menu',
            $mixed->getParentId(),
            'Expected matching value with just a menu and menu item'
        );

        $mixed->setMenuItem();
        $this->assertSame(
            null,
            $mixed->getParentId(),
            'Expected matching value with just a menu'
        );
    }

    /**
     * Test getting the parent container
     */
    public function testGetParentContainer()
    {
        $mixed = new P4Cms_Menu_Mixed;

        $this->assertSame(
            null,
            $mixed->getParentContainer(),
            'Expected null parent container'
        );

        $menuItem = new Zend_Navigation_Page_Uri;
        $menuItem->label = 'parentMenuItem';
        $mixed->setParentMenuItem($menuItem);

        // expect parent menu item
        $this->assertSame(
            'parentMenuItem',
            $mixed->getParentContainer()->getLabel(),
            'Expected matching label for parent container'
        );

        $menu = new P4Cms_Menu;
        $menu->setId('testMenu');
        $mixed->setMenu($menu);
        $mixed->setParentMenuItem(null);

        // expect default menu container
        $this->assertSame(
            'P4Cms_Navigation',
            get_class($mixed->getParentContainer()),
            'Expected parent container to be of class P4Cms_Navigation'
        );
    }

    /**
     * Test the get label method
     */
    public function testGetLabel()
    {
        $mixed = new P4Cms_Menu_Mixed;

        $this->assertSame(
            '',
            $mixed->getLabel(),
            'Expected matching starting value'
        );

        $menuItem = new Zend_Navigation_Page_Uri;
        $menuItem->label = 'parentMenuItem';
        $mixed->setParentMenuItem($menuItem);

        $this->assertSame(
            '',
            $mixed->getLabel(),
            'Expected matching value with just parent menu item'
        );

        $menu = new P4Cms_Menu;
        $mixed->setMenu($menu);

        $this->assertSame(
            '',
            $mixed->getLabel(),
            'Expected matching value with just parent menu item with no id'
        );

        $menu->setId('menu');
        $this->assertSame(
            'Menu',
            $mixed->getLabel(),
            'Expected matching value with menu and parent menu item'
        );

        $menuItem = new Zend_Navigation_Page_Uri;
        $menuItem->label = 'menuItem';
        $mixed->setMenuItem($menuItem);

        $this->assertSame(
            'menuItem',
            $mixed->getLabel(),
            'Expected matching value with menu, menu item and parent menu item'
        );
    }

    /**
     * test (get|set|has)Menu and getMenuId methods
     */
    public function testMenuMethods()
    {
        $mixed = new P4Cms_Menu_Mixed;

        $this->assertSame(
            null,
            $mixed->getMenu(),
            'Expected matching starting state'
        );

        $this->assertFalse(
            $mixed->hasMenu(),
            'Expected no menu to start'
        );

        $this->assertSame(
            null,
            $mixed->getMenuId(),
            'Expected matching starting id'
        );

        $menu = new P4Cms_Menu;
        $menu->setId("menu");
        $mixed->setMenu($menu);

        $this->assertTrue(
            $mixed->hasMenu(),
            'Expected menu after setting to be known'
        );

        $this->assertSame(
            'menu',
            $mixed->getMenu()->getId(),
            'Expected matching id from get menu'
        );

        $this->assertSame(
            'menu',
            $mixed->getMenuId(),
            'Expected matching id from get menu id'
        );

        $mixed->setMenu(null);

        $this->assertSame(
            null,
            $mixed->getMenu(),
            'Expected match after blanking menu'
        );
    }

    /**
     * test (get|set|has)MenuItem and getMenuItemId methods
     */
    public function testMenuItemMethods()
    {
        $mixed = new P4Cms_Menu_Mixed;

        $this->assertSame(
            null,
            $mixed->getMenuItem(),
            'Expected matching starting state'
        );

        $this->assertFalse(
            $mixed->hasMenuItem(),
            'Expected no menu item to start'
        );

        $this->assertSame(
            null,
            $mixed->getMenuItemId(),
            'Expected matching starting id'
        );

        $menuItem = new Zend_Navigation_Page_Uri;
        $menuItem->uuid = "menuItem";
        $mixed->setMenuItem($menuItem);

        $this->assertTrue(
            $mixed->hasMenuItem(),
            'Expected menu item after setting to be known'
        );

        $this->assertSame(
            'menuItem',
            $mixed->getMenuItem()->uuid,
            'Expected matching id from get menu item'
        );

        $this->assertSame(
            'menuItem',
            $mixed->getMenuItemId(),
            'Expected matching id from get menu item id'
        );

        $mixed->setMenuItem(null);

        $this->assertSame(
            null,
            $mixed->getMenuItem(),
            'Expected match after blanking menu item'
        );

        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($this->p4)
                ->setBasePath("//depot/records");
        P4Cms_Record::setDefaultAdapter($adapter);

        $menu = new P4Cms_Menu;
        $menu->setId('test');

        $container = new P4Cms_Navigation;
        $container->addPage(
            array(
                'label'      => 'Users',
                'uri'        => 'http://google.com',
                'uuid'       => 'usersItem',
                'pages'      => array(
                    array(
                        'label'     => 'Users Sub 0',
                        'uri'       => 'http://google.com?q=0',
                        'uuid'      => 'usersSubItem0',
                    ),
                    array(
                        'label'     => 'Users Sub 1',
                        'uri'       => 'http://google.com?q=1',
                        'uuid'      => 'usersSubItem1',
                    )
                )
            )
        );
        
        $menu->setContainer($container)->save();

        $mixed = P4Cms_Menu::fetchMixed();
        $item  = $mixed->search('id', 'test/usersSubItem0', array(P4Cms_Model_Iterator::FILTER_COPY))->first();

        $this->assertSame(
            $item->getPreviousMenuItem(),
            null,
            'Expected null id for previous menu item'
        );

        // get the latter of the two items
        $item = $item->getNextMenuItem();

        $this->assertSame(
            $item->getId(),
            'test/usersSubItem1',
            'Expected matching id for next menu item'
        );

        $this->assertSame(
            $item->getNextMenuItem(),
            null,
            'Expected null id for next menu item'
        );

        $this->assertSame(
            $item->getPreviousMenuItem()->getId(),
            'test/usersSubItem0',
            'Expected matching id for previous menu item'
        );
    }

    /**
     * test (get|set|has)ParentMenuItem methods
     */
    public function testParentMenuItemMethods()
    {
        $mixed = new P4Cms_Menu_Mixed;

        $this->assertSame(
            null,
            $mixed->getParentMenuItem(),
            'Expected matching starting state'
        );

        $this->assertFalse(
            $mixed->hasParentMenuItem(),
            'Expected no parent menu item to start'
        );

        $menuItem = new Zend_Navigation_Page_Uri;
        $menuItem->uuid = "menuItem";
        $mixed->setParentMenuItem($menuItem);

        $this->assertTrue(
            $mixed->hasParentMenuItem(),
            'Expected parent menu item after setting to be known'
        );

        $this->assertSame(
            'menuItem',
            $mixed->getParentMenuItem()->uuid,
            'Expected matching id from get parent menu item'
        );

        $mixed->setParentMenuItem(null);

        $this->assertSame(
            null,
            $mixed->getParentMenuItem(),
            'Expected match after blanking parent menu item'
        );

        $page = new P4Cms_Navigation_Page_Dynamic;
        $page->setLabel('testPage');
        $mixed->setParentMenuItem($page);

        $this->assertSame(
            'testPage',
            $mixed->getParentContainer()->getLabel(),
            'Expecting matching id from parent container'
        );
    }
}