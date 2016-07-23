<?php
/**
 * Test the Disqus module view helper.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Disqus_Test_ViewHelperTest extends ModuleControllerTest
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
     * Test the post action without a path specified
     */
    public function testConversationOutput()
    {
        $this->utility->impersonate('anonymous');

        // test the conversation helper without short name and without show conversations
        $module = P4Cms_Module::fetch('Disqus');
        $config = $module->getConfig();
        $this->assertEmpty($config->shortName, 'Expected Disqus module not to be configured.');

        $entry  = $this->_createTestEntry(false);
        $helper = new Disqus_View_Helper_Conversation;
        $helper->setView($this->_view);
        $output = $helper->conversation($entry);
        $this->assertEmpty(
            $output,
            'Expected empty output - no shortName, showConversation = false.'. $output
        );

        // test again without shortname and with show conversations
        $entry->setValue('disqus', array(Disqus_Form_Content::SHOW_CONVERSATION_KEY => true));
        $output = $helper->conversation($entry);
        $this->assertEmpty(
            $output,
            'Expected empty output - no shortName, showConversation = true.'. $output
        );

        // test again with shortname and without show conversations
        $config->shortName = 'test-shortname';
        $module->saveConfig($config);
        $entry->setValue('disqus', array(Disqus_Form_Content::SHOW_CONVERSATION_KEY => false));
        $output = $helper->conversation($entry);
        $this->assertEmpty(
            $output,
            'Expected empty output - shortName, showConversation = false.'. $output
        );

        // test again with shortname and with show conversations
        $entry->setValue('disqus', array(Disqus_Form_Content::SHOW_CONVERSATION_KEY => true));
        $output = $helper->conversation($entry);
        $this->assertContains(
            '<div class="disqus-conversation-wrapper"',
            $output,
            'Expected conversation container.'
        );
        $this->assertContains(
            'disqus_identifier = "'. $entry->getId() .'"',
            $output,
            "Expected Disqus identifier in javascript"
        );
        $expectedUrl = $this->_view->escapeJs($this->_view->serverUrl() . $entry->getUri());
        $this->assertContains(
            'disqus_url        = "'. $expectedUrl .'"',
            $output,
            "Expected Disqus URL in javascript"
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
                Disqus_Form_Content::FORM_KEY,
                array(Disqus_Form_Content::SHOW_CONVERSATION_KEY => $showConversation)
            )
            ->save('a test entry');

        return $entry;
    }
}