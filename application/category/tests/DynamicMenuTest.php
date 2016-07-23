<?php
/**
 * Test the category module's dynamic menu handler.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Category_Test_DynamicMenuTest extends ModuleTest
{
    /**
     * Test menu expansion
     */
    public function testExpansion()
    {
        $this->utility->impersonate('anonymous');
        
        // make some categories.
        for ($i = 0; $i < 5; $i++) {
            Category_Model_Category::store(array('id' => "cat-$i", 'title' => 'cat-' . $i));
            Category_Model_Category::store(array('id' => "cat-$i/sub-cat", 'title' => 'sub-cat'));
            Category_Model_Category::store(array('id' => "cat-$i/sub-cat/sub-cat-sub", 'title' => 'sub-cat-sub'));
        }

        // make a dynamic category menu item.
        $item = new P4Cms_Navigation_Page_Dynamic;
        $item->setHandler('categories');

        // normalized options.
        $options = array(
            P4Cms_Menu::MENU_MAX_DEPTH       => null,
            P4Cms_Menu::MENU_MAX_ITEMS       => null,
            P4Cms_Menu::MENU_ROOT            => null,
            'includeEntries'                 => null,
        );

        // run expansion callback.
        $handler = P4Cms_Navigation_DynamicHandler::fetch('categories');
        $result  = $handler->callExpansionCallback($item, $options);

        // verify result can form a nav container.
        $menu = new P4Cms_Navigation($result);
        $this->assertTrue($menu->hasPages());

        // should be 5 top-level items.
        $this->assertSame(5, count($menu));

        // should be 15 overall.
        $count     = 0;
        $recursive = new RecursiveIteratorIterator($menu, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($recursive as $item) {
            $count++;
        }
        $this->assertSame(15, $count);

        // try again with max-items (category handler understands max-items).
        $options[P4Cms_Menu::MENU_MAX_ITEMS] = 5;
        $result = $handler->callExpansionCallback($item, $options);
        $menu   = new P4Cms_Navigation($result);

        // two top-level items, but all content included
        $this->assertEquals(5, count($menu), 'Expect 5 top-level menu items.');

        // 15 overall, max items has no effect at this level.
        $count     = 0;
        $recursive = new RecursiveIteratorIterator($menu, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($recursive as $item) {
            $count++;
        }
        $this->assertSame(15, $count);

        // try max-depth (depth of 1 means two levels, top level is depth zero).
        $options[P4Cms_Menu::MENU_MAX_DEPTH] = 1;
        $result = $handler->callExpansionCallback($item, $options);
        $menu   = new P4Cms_Navigation($result);

        // ensure depth is honored.
        $recursive = new RecursiveIteratorIterator($menu, RecursiveIteratorIterator::SELF_FIRST);
        foreach ($recursive as $item) {
            $this->assertTrue($recursive->getDepth() <= 1);
        }

        // max-items should have no effect at this level but depth did
        $count = 0;
        foreach ($recursive as $item) {
            $count++;
        }
        $this->assertSame(10, $count);

        // try rooting the menu.
        $options[P4Cms_Menu::MENU_ROOT]      = 'cat-2';
        $options[P4Cms_Menu::MENU_MAX_DEPTH] = null;
        $options[P4Cms_Menu::MENU_MAX_ITEMS] = null;
        $result = $handler->callExpansionCallback($item, $options);
        $menu   = new P4Cms_Navigation($result);

        // ensure only three items (cat-2, cat-2/sub-cat, cat-2/sub-cat/sub)
        $pages = $menu->getPages();
        $page  = current($pages);
        $this->assertSame(1, count($pages));
        $this->assertSame('cat-2', $page->expansionId);
        $pages = $page->getPages();
        $page  = current($pages);
        $this->assertSame(1, count($pages));
        $this->assertSame('cat-2/sub-cat', $page->expansionId);
        $pages = $page->getPages();
        $page  = current($pages);
        $this->assertSame(1, count($pages));
        $this->assertSame('cat-2/sub-cat/sub-cat-sub', $page->expansionId);
    }
}
