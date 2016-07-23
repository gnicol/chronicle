<?php
/**
 * Displays a Disqus conversation.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Disqus_View_Helper_Conversation extends Zend_View_Helper_Abstract
{
    /**
     * Render a Disqus conversation for the given content entry.
     *
     * @param   P4Cms_Content   $entry      content entry for the conversation
     * @param   string          $template   optional - name of template file to render to.
     * @return  string          the rendered Disqus conversation.
     */
    public function conversation(P4Cms_Content $entry, $template = 'conversation.phtml')
    {
        $module = P4Cms_Module::fetch('Disqus');
        $config = $module->getConfig()->toArray();
        $config = Disqus_Form_Configure::getNormalizedOptions($config);

        // prepare options for this module saved by the entry
        $options = (array) $entry->getValue(Disqus_Form_Content::FORM_KEY);

        //get the shortname from the module config and add it to the template options
        $shortNameKey = Disqus_Form_Configure::SHORT_NAME;
        $options[$shortNameKey] = isset($config[$shortNameKey]) ? $config[$shortNameKey] : '';

        // provide the entry's id and URL to the view
        $options['identifier']  = $entry->getId();
        $options['url']         = $this->view->serverUrl() . $entry->getUri();

        // decide whether to show Disqus conversation by:
        // 1. checking the value saved by the entry;
        // 2. using to default setting for the entry's content type defined in
        // the module's configuration
        $showConversation = isset($options[Disqus_Form_Content::SHOW_CONVERSATION_KEY])
            ? (bool) $options[Disqus_Form_Content::SHOW_CONVERSATION_KEY]
            : in_array($entry->getContentTypeId(), $config['contentTypes']);

        // if we're not showing the Disqus conversation, or we have no shortname,
        // nothing to do.
        if (!$showConversation || strlen($options[$shortNameKey]) == 0) {
            return '';
        }

        return $this->view->partial($template, $options);
    }
}