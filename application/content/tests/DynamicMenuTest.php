<?php
/**
 * Test the content module's dynamic menu handler.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Test_DynamicMenuTest extends ModuleTest
{
    /**
     * Test expanding the menu
     */
    public function testExpansion()
    {
        $this->utility->impersonate('anonymous');

        // install default content types.
        P4Cms_Content_Type::installDefaultTypes();

        // make some pages.
        for ($i = 0; $i < 10; $i++) {
            $page = P4Cms_Content::store(
                array(
                    'title'         => "test $i",
                    'contentType'   => 'image'
                )
            );
        }

        // make a dynamic content menu item.
        $item = new P4Cms_Navigation_Page_Dynamic;
        $item->setHandler('content');

        // normalized options.
        $options = array(
            P4Cms_Menu::MENU_MAX_DEPTH => null,
            P4Cms_Menu::MENU_MAX_ITEMS => null,
            P4Cms_Menu::MENU_ROOT      => null,
        );

        // run expansion callback.
        $handler = P4Cms_Navigation_DynamicHandler::fetch('content');
        $result  = $handler->callExpansionCallback($item, $options);

        // verify result can form a nav container.
        $menu = new P4Cms_Navigation($result);
        $this->assertTrue($menu->hasPages());
        $this->assertSame(10, count($menu));

        // try again with max-items (content handler understands max-items).
        $options[P4Cms_Menu::MENU_MAX_ITEMS] = 5;
        $result = $handler->callExpansionCallback($item, $options);
        $menu   = new P4Cms_Navigation($result);
        $this->assertTrue($menu->hasPages());
        $this->assertSame(5, count($menu));
    }
}
