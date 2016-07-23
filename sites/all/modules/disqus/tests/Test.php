<?php
/**
 * Test the Disqus module.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Disqus_Test_Test extends ModuleControllerTest
{
    protected $_disqusModule;
    protected $_view;

    /**
     * Perform setup
     */
    public function setUp()
    {
        parent::setUp();

        $this->_disqusModule = P4Cms_Module::fetch('Disqus');
        $this->_disqusModule->enable();
        $this->_disqusModule->load();

        $this->_view = Zend_Layout::getMvcInstance()->getView();
    }

    /**
     * Test logic adding conversations to rendered HTML. 
     */
    public function testRenderClose()
    {
        // prepare the shortname for the Disqus module
        $module = P4Cms_Module::fetch('Disqus');
        $config = $module->getConfig();
        $this->assertEmpty($config->shortName, 'Expected Disqus module not to be configured.');
        $config->shortName = 'test-shortname';
        $module->saveConfig($config);

        // test with an entry having an id
        $entry  = $this->_createTestEntry(true);
        $helper = $this->_view->getHelper('ContentEntry');
        $helper->setDefaults($entry, array());

        $actual = P4Cms_PubSub::filter('p4cms.content.render.close', '', $helper);
        $this->assertContains(
            '<div class="disqus-conversation-wrapper"',
            $actual,
            'Expected conversation container.'
        );

        // test again where the entry does not have an id
        $entry->setId(null);
        $helper->setDefaults($entry, array());
        $actual = P4Cms_PubSub::filter('p4cms.content.render.close', '', $helper);
        $this->assertEmpty($actual, 'Expected empty HTML when entry has no id.');
    }

    /**
     * Test that the Disqus content editing subform gets included.
     */
    public function testSubForms()
    {
        $entry  = $this->_createTestEntry(true);
        $form = new Content_Form_Content(array('entry' => $entry));
        $form->publishSubForms();
        $forms = $form->getSubForms();
        $this->assertTrue(
            array_key_exists(Disqus_Form_Content::FORM_KEY, $forms),
            'Expected a Disqus subform.'
        );
        $this->assertTrue(
            $forms[Disqus_Form_Content::FORM_KEY] instanceof Disqus_Form_Content,
            'Expected Disqus subform type'
        );
    }

    /**
     * Test that the Disqus subform populates correctly. 
     */
    public function testPopulate()
    {
        // test before Disqus module enabled
        $entry  = $this->_createTestEntry(true);
        $form = new Content_Form_Content(array('entry' => $entry));
        $values = array(
            'title' => 'Test 2',
        );
        $result = P4Cms_PubSub::publish('p4cms.content.form.populate', $form, $values);

        $subForm = $form->getSubForm(Disqus_Form_Content::FORM_KEY);
        $this->assertTrue($subForm instanceof Disqus_Form_Content, 'Expected Disqus subform type');
        $actual = $subForm->getValues();
        $this->assertSame(
            array(
                Disqus_Form_Content::FORM_KEY => array(
                    Disqus_Form_Content::SHOW_CONVERSATION_KEY  => true
                )
            ),
            $actual,
            'Expected subform values'
        );
    }

    /**
     * Create an entry for testing.
     *
     * @param   boolean $showConversation   optional - indicate if the Disqus conversation
     *                                      should be shown for the content entry
     * @param   string  $contentType        optional - content type of the entry to create
     * @return  P4Cms_Content               the created content entry
     */
    protected function _createTestEntry($showConversation = false, $contentType = 'basic-page')
    {
        $entry = new P4Cms_Content;
        P4Cms_Content_Type::installDefaultTypes();
        $entry->setContentType(P4Cms_Content_Type::fetch($contentType))
            ->setValue('title', 'Test Title')
            ->setValue('body', 'The body of the test')
            ->setId('test-entry-id')
            ->setValue(
                'disqus',
                array('showConversation' => $showConversation)
            )
            ->save('a test entry');

        return $entry;
    }
}