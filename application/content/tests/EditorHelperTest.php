<?php
/**
 * Test methods for the content editor view helper.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Test_EditorHelperTest extends ModuleTest
{
    /**
     * Test setup.
     */
    public function setup()
    {
        $this->view = new Zend_View;
        Zend_Dojo::enableView($this->view);
        parent::setup();
    }

    /**
     * Test instantiation.
     */
    public function testInstantiation()
    {
        $helper = new Content_View_Helper_Editor;
        $this->assertTrue($helper instanceof Content_View_Helper_Editor, 'Expected class');
    }

    /**
     * Verify non-nested options get properly output
     */
    public function testExtraPluginOutput()
    {
        $helper = new Content_View_Helper_Editor();
        $helper->setView($this->view);
        
        $output = $helper->editor(
            'test', null,
            array(
                'extraPlugins' => array('test0', 'test1')
            ),
            array()
        );

        $this->assertRegExp("/extraPlugins=..'test0','test1'../", $output);
        $this->assertRegExp("/dojoType=.p4cms.content.Editor/", $output);
    }

    
}
