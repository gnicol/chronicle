<?php
/**
 * Test the Analytics configure controller.
 * 
 * Because the analytics service is outside of our control, all we can test is that the
 * analytics code was injected accurately - we cannot test that it is working.
 * Cannot easily test content id and type here, see SnippetAdditionTest.php
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Analytics_Test_LoadSnippetTest extends ModuleTest
{
    /**
     * Perform setup
     */
    public function setUp()
    {
        $this->view = new Zend_View;
        Zend_Dojo::enableView($this->view);
        
        parent::setUp();
        
        if (!defined('TEST_ACCOUNT_NUMBER')) {
            define('TEST_ACCOUNT_NUMBER', 'UA-XXXXX-1');
        }
    }
    
    /**
     * Test of loading the module
     */
    public function testLoadModule()
    {
        $this->utility->impersonate('administrator');

        $analyticsModule = P4Cms_Module::fetch('Analytics');
        $analyticsModule->enable();
        
        $analyticsModule->saveConfig(
            new Zend_Config(
                array(
                    'accountNumber' => TEST_ACCOUNT_NUMBER, 
                    'customVars'    => array('userId', 'userRole', 'contentId', 'contentType')
                )
            )
        );
        
        // load module, verify that page contains expected.
        $analyticsModule->load();
        
        $headContent = Zend_Layout::getMvcInstance()->getView()->headScript()->toString();
        
        // verify account number, custom variables, and values
        $tests = array(
            "/_gaq\.push\(\['_setAccount', '" . TEST_ACCOUNT_NUMBER . "'\]\);/",
            "/\['_setCustomVar', 0, 'userId', 'mweiss'\]/",
            "/\['_setCustomVar', 1, 'userRole', 'administrator'\]/"
        );
        
        foreach ($tests as $regex) {
            $this->assertRegExp(
                $regex, 
                $headContent, 
                'Could not match regex with head content ' . $headContent
            );
        }
    }
}