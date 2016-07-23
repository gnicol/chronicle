<?php
/**
 * Test the menu breadcrumbs viewhelper.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_Test_BreadcrumbsHelperTest extends ModuleTest
{
    /**
     * Test setup.
     */
    public function setup()
    {
        parent::setup();
        
        $this->_view = Zend_Layout::getMvcInstance()->getView();
        $this->utility->impersonate('administrator');
    }

    /**
     * Test instantiation.
     */
    public function testInstantiation()
    {
        $helper = new Menu_View_Helper_Breadcrumbs;
        $helper->setView($this->_view);
        $this->assertTrue($helper instanceof Menu_View_Helper_Breadcrumbs, 'Expected class');
    }
    
    /**
     * Test htmlify() without a page.
     * 
     * @expectedException PHPUnit_Framework_Error
     */
    public function testHtmlifyWithoutPage()
    {
        $helper = new Menu_View_Helper_Breadcrumbs;
        $helper->setView($this->_view);
        
        $helper->htmlify();
    }

    /**
     * Test htmlify() with a page.
     * 
     */
    public function testHtmlifyWithPage()
    {
        $helper = new Menu_View_Helper_Breadcrumbs;
        $helper->setView($this->_view);
        
        $pages = array(
            array(
                'type'    => 'uri',
                'options' => array(
                    'id'    => 'menu_1',
                    'label' => 'Menu Item 1',
                ),
                'result'  => 'Menu Item 1',
            ),
            array(
                'type'    => 'uri',
                'options' => array(
                    'id'    => 'menu_2',
                    'label' => 'Menu Item 2',
                    'uri'   => 'menu_2'
                ),
                'result'  => '<a id="breadcrumbs-menu_2" href="menu_2">Menu Item 2</a>', 
            ),
            array(
                'type'    => 'mvc',
                'options' => array(
                    'id'    => 'menu_3',
                    'label' => 'Menu Item 3',
                ),
                'result'  => '<a id="breadcrumbs-menu_3" href="/">Menu Item 3</a>', 
            ),
            array(
                'type'    => 'mvc',
                'options' => array(
                    'id'     => 'menu_4',
                    'label'  => 'Menu Item 4',
                    'module' => 'module_4'
                ),
                'result'  => '<a id="breadcrumbs-menu_4" href="/module_4">Menu Item 4</a>', 
            ),
            array(
                'type'    => 'mvc',
                'options' => array(
                    'id'         => 'menu_5',
                    'label'      => 'Menu Item 5',
                    'module'     => 'module5',
                    'controller' => 'controller5'
                ),
                'result'  => '<a id="breadcrumbs-menu_5" href="/module5/controller5">Menu Item 5</a>', 
            ),
            array(
                'type'    => 'mvc',
                'options' => array(
                    'id'         => 'menu_6',
                    'label'      => 'Menu Item 6',
                    'module'     => 'module6',
                    'controller' => 'controller6',
                    'action'     => 'action6'
                ),
                'result'  => '<a id="breadcrumbs-menu_6" href="/module6/controller6/action6">Menu Item 6</a>', 
            ),
        );
        
        foreach ($pages as $page) {
            switch ($page['type']) {
                case 'uri':
                    $p = new Zend_Navigation_Page_Uri($page['options']);
                    break;
                case 'mvc':
                    $p = new Zend_Navigation_Page_Mvc($page['options']);
                    break;
                default:
                    break;
            }
            
            $this->assertSame($page['result'], $helper->htmlify($p));
        }
    }
}