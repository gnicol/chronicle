<?php
/**
 * Test methods for the Loader class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_LoaderTest extends TestCase
{
    /**
     * Test autoload registration.
     */
    public function testRegisterAutoload()
    {
        // if registered, de-register it.
        if ($this->_isRegistered()) {
            $this->_disableAutoloader();
        }

        // test registration.
        $this->_enableAutoloader();
        $this->assertTrue(
            $this->_isRegistered(),
            "should be registered"
        );

        // test de-registration.
        $this->_disableAutoloader();
        $this->assertFalse(
            $this->_isRegistered(),
            "should no longer be registered"
        );

        // turn it back on - we need it for further testing of forms and models.
        $this->_enableAutoloader();
        $this->assertTrue(
            $this->_isRegistered(),
            "should be registered again"
        );
    }

    /**
     * Test loading a form class from a module.
     */
    public function testFormLoad()
    {
        // initialize a module with a form.
        P4Cms_Module::setCoreModulesPath(TEST_ASSETS_PATH . '/core-modules');
        P4Cms_Module::fetch('Core')->init();

        // if registered, de-register it.
        if ($this->_isRegistered()) {
            $this->_disableAutoloader();
        }

        $this->assertFalse(
            class_exists('Core_Form_TestForm'),
            "Core_Form_TestForm class should not be registered"
        );

        $this->_enableAutoloader();
        $this->assertTrue(
            class_exists('Core_Form_TestForm'),
            "Core_Form_TestForm class should be registered"
        );
    }

    /**
     * Test loading a form element class from a module.
     */
    public function testFormElementLoad()
    {
        // initialize a module with a form element.
        P4Cms_Module::setCoreModulesPath(TEST_ASSETS_PATH . '/core-modules');
        P4Cms_Module::fetch('Core')->init();

        // if registered, de-register it.
        if ($this->_isRegistered()) {
            $this->_disableAutoloader();
        }

        $this->assertFalse(
            class_exists('Core_Form_Element_TestElement'),
            "Core_Form_Decorator_TestElement class should not be registered"
        );

        $this->_enableAutoloader();
        $this->assertTrue(
            class_exists('Core_Form_Element_TestElement'),
            "Core_Form_Decorator_TestElement class should be registered"
        );
    }

    /**
     * Test loading a controller class from a module.
     */
    public function testControllerLoad()
    {
        // initialize a module with a controller.
        P4Cms_Module::setCoreModulesPath(TEST_ASSETS_PATH . '/core-modules');
        P4Cms_Module::fetch('Core')->init();

        // if registered, de-register it.
        if ($this->_isRegistered()) {
            $this->_disableAutoloader();
        }

        $this->assertFalse(
            class_exists('Core_FooController'),
            "Core_IndexController class should not be registered"
        );

        $this->_enableAutoloader();
        $this->assertTrue(
            class_exists('Core_FooController'),
            "Core_IndexController class should be registered"
        );
    }

    /**
     * Test loading a unrecognized type of class from a module.
     */
    public function testArbitraryLoad()
    {
        // initialize a module with a controller.
        P4Cms_Module::setCoreModulesPath(TEST_ASSETS_PATH . '/core-modules');
        P4Cms_Module::fetch('Core')->init();

        // if registered, de-register it.
        if ($this->_isRegistered()) {
            $this->_disableAutoloader();
        }

        $this->assertFalse(
            class_exists('Core_Some_Class'),
            "Core_Some_Class class should not be registered"
        );

        $this->_enableAutoloader();
        $this->assertTrue(
            class_exists('Core_Some_Class'),
            "Core_Some_Class class should be registered"
        );
    }

    /**
     * Test using class_exists simply returns false with the autoloader on and an invalid class
     * as input; Zend's native loader borks in this instance.
     */
    public function testClassExists()
    {
        // verify the class doesn't exist with the autoloader functionality disabled.
        $this->assertFalse(class_exists('sadfkanwakejbncasaaw', false));

        // utilize the error handler to generate a catchable exception on failure
        set_error_handler(create_function('$errno,$errstr', 'throw new Exception($errstr);'));

        try {
            // verify the class doesn't exist with the autoloader functionality enabled.
            $result = class_exists('sadfkanwakejbncasaaw');
            restore_error_handler();
            $this->assertFalse($result);
        } catch(Exception $e) {
            restore_error_handler();
            $this->assertTrue(false);
        }
    }

    /**
     * Enable/Register the P4Cms Autoloader
     */
    private function _enableAutoloader()
    {
        Zend_Loader_Autoloader::getInstance()->pushAutoloader(array('P4Cms_Loader', 'autoload'));
    }

    /**
     * Disable/Remove the P4Cms Autoloader
     */
    private function _disableAutoloader()
    {
        Zend_Loader_Autoloader::getInstance()->removeAutoloader(array('P4Cms_Loader', 'autoload'));
    }

    /**
     * Check if P4Cms_Loader is registered to autoload.
     *
     * @return  boolean true if loaded.
     */
    private function _isRegistered()
    {
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $registeredAutoloaders = $autoloader->getAutoloaders();

        foreach ($registeredAutoloaders as $registered) {
            if (is_array($registered) && $registered[0] == 'P4Cms_Loader') {
                return true;
            }
        }
        return false;
    }
}
