<?php
/**
 * Test the MVC menu item form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_Test_MenuItemMvcFormTest extends ModuleControllerTest
{
    /**
     * Test handling when params is an array instead of a config string
     */
    public function testSetDefaultsParamsArray()
    {
        $form = new Menu_Form_MenuItemMvc();
        
        $defaults = array(
            'menuId'    => 'testmenu',
            'id'        => 'testitem',
            'uuid'      => 'testitem',
            'label'     => 'testItem',
            'position'  => 'under',
            'location'  => 'testmenu',
            'type'      => 'P4Cms_Navigation_Page_Mvc',
            'action'    => 'menu/index/index',
            'params'    => array(
                'format'    => 'json'
            )
        );
        
        $form->setDefaults($defaults);
        $params = $form->getValue('params');
        
        $this->assertTrue(is_string($params));
    }
    
    /**
     * Test handling when route is split into its parts instead of a single action string
     */
    public function testSetDefaultsRouteSplit()
    {
        $form = new Menu_Form_MenuItemMvc();
        
        $module     = 'menu';
        $controller = 'index';
        $action     = 'index';
        
        $defaults = array(
            'menuId'    => 'testmenu',
            'id'        => 'testitem',
            'uuid'      => 'testitem',
            'label'     => 'testItem',
            'position'  => 'under',
            'location'  => 'testmenu',
            'type'      => 'P4Cms_Navigation_Page_Mvc',
            'module'    => $module,
            'controller'=> $controller,
            'action'    => $action,
            'params'    => array(
                'format'    => 'json'
            )
        );
        
        $form->setDefaults($defaults);
        $value = $form->getValue('action');
        
        $this->assertEquals($value, $module . '/' . $controller . '/' . $action);
    }
    
    /**
     * Test handling when params are all strings
     */
    public function testGetValues()
    {
        $form = new Menu_Form_MenuItemMvc();
        
        $module     = 'menu';
        $controller = 'index';
        $action     = 'index';
        
        $defaults = array(
            'menuId'    => 'testmenu',
            'id'        => 'testitem',
            'uuid'      => 'testitem',
            'label'     => 'testItem',
            'position'  => 'under',
            'location'  => 'testmenu',
            'type'      => 'P4Cms_Navigation_Page_Mvc',
            'action'    => $module . '/' . $controller . '/' . $action,
            'params'    => 'format=json'
        );
        
        $formatArray = array('format' => 'json');
        
        $form->setDefaults($defaults);
        $values = $form->getValues();
        
        $this->assertEquals($values['module'], $module);
        $this->assertEquals($values['controller'], $controller);
        $this->assertEquals($values['action'], $action);
        $this->assertEquals($values['params'], $formatArray);
        $this->assertEquals($values['label'], $defaults['label']);
    }
}