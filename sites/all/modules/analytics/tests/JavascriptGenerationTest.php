<?php
/**
 * Test the Analytics module output, verify that content id and type are being added properly.
 *
 * Because the analytics service is outside of our control, all we can test is that the
 * analytics code was injected accurately - we cannot test that it is working.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Analytics_Test_JavascriptGenerationTest extends ModuleControllerTest
{
    protected $_analyticsModule;

    /**
     * Perform setup
     */
    public function setUp()
    {
        parent::setUp();

        if (!defined('TEST_ACCOUNT_NUMBER')) {
            define('TEST_ACCOUNT_NUMBER', 'UA-XXXXX-1');
        }
    }

    /**
     * Create a type and a entry for testing.
     */
    public function _createTestTypeAndEntry()
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
            'id'        => array(
                'type'      => 'text',
                'options'   => array('label' => 'ID', 'required' => true)
            )
        );

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
             ->setValue('abstract', 'abstract this')
             ->setId('theId');

        $entry->save('a test entry');

        return array($type, $entry);
    }

    /**
     * Test the generation of the javascript code
     */
    public function testJavascriptGenaration()
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

        list($type, $entry) = $this->_createTestTypeAndEntry();

        $analyticsModule->load();

        $this->dispatch('/content/view/id/' . $entry->getId());

        $body = $this->getResponse()->getBody();

        // verify account number, custom variables, and values
        $tests = array(
            "/_gaq\.push\(\['_setAccount', '" . TEST_ACCOUNT_NUMBER . "'\]\);/",
            "/\['_setCustomVar', 0, 'userId', 'mweiss'\]/",
            "/\['_setCustomVar', 1, 'userRole', 'administrator'\]/",
            "/\['_setCustomVar', 2, 'contentId', dojo.attr\(entry, 'contentId'\)\]/",
            "/\['_setCustomVar', 3, 'contentType', dojo.attr\(entry, 'contentType'\)\]/"
        );

        foreach ($tests as $regex) {
            $this->assertRegExp(
                $regex,
                $body,
                'Could not match regex with head content ' . $body
            );
        }
    }
}