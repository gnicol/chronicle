<?php
/**
 * Test the menu widget controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_Test_WidgetControllerTest extends ModuleControllerTest
{
    /**
     * Test the index action with empty menu
     */
    public function testEmptyMenu()
    {
        $this->utility->impersonate('administrator');

        P4Cms_Widget::installDefaults();

        $widget = P4Cms_Widget::factory('menu/widget');
        $widget->setValue('region', 'test')->save();

        $this->dispatch('/widget/region/test/widget/' . $widget->getId());
        $this->assertModule('widget', 'Expected module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('index', 'Expected action');

        $this->assertQuery('div.widget-menu-widget', 'Expected menu widget div');
        $this->assertQueryContentContains(
            'div#widget-' . $widget->getId() . '-content',
            'No items to display.',
            'Expected no items message.'
        );
    }

    /**
     * Test index action with default menu
     */
    public function testDefaultMenu()
    {
        $this->utility->impersonate('administrator');

        P4Cms_Widget::installDefaults();

        P4Cms_Menu::installDefaultMenus();

        $widget = P4Cms_Widget::factory('menu/widget');
        $widget->setValue('region', 'test')->save();

        $widget->setConfigFromArray(array('menu' => 'manage-toolbar'))->save();

        $this->dispatch('/widget/region/test/widget/' . $widget->getId());

        $this->assertModule('widget', 'Expected module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('index', 'Expected action');

        $this->assertQuery('div.widget-menu-widget', 'Expected menu widget div');
        $this->assertXpath(
            '//div[@id="widget-' . $widget->getId() . '-content"]',
            'Expected menu widget content div'
        );
    }

    /**
     * Test getting the subform
     */
    public function testGetConfigSubForm()
    {
        $this->utility->impersonate('administrator');

        P4Cms_Widget::installDefaults();

        P4Cms_Menu::installDefaultMenus();

        $widget = P4Cms_Widget::factory('menu/widget');
        $widget->setValue('region', 'test')->save();

        $this->dispatch('/widget/index/configure/region/test/widget/' . $widget->getId());

        $this->assertModule('widget', 'Expected module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('configure', 'Expected action');

        $this->assertXpath('//select[@name="config[menu]"]', 'Expected menu select element');
        $this->assertXpath('//select[@name="config[root]"]', 'Expected root select element');
        $this->assertXpath('//select[@name="config[maxDepth]"]', 'Expected maxDepth select element');
        $this->assertXpath('//select[@name="config[maxItems]"]', 'Expected maxItems select element');

    }
}
