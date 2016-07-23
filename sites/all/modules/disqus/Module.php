<?php
/**
 * The Disqus module provides a conversation facility.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Disqus_Module extends P4Cms_Module_Integration
{
    /**
     * When this module loads, subscribe to content rendering to
     * render comments with content entries where appropriate.
     * Also subscribes to content editing (forms) to include options
     * to control comments on each content entry.
     */
    public static function load()
    {
        P4Cms_PubSub::subscribe('p4cms.content.render.close',
            function($html, $helper)
            {
                $entry = $helper->getEntry();

                // if we don't have an entry id or the entry being rendered
                // isn't the default we won't append the Disqus conversation
                if (!$entry->getId()
                    || $entry->getId() != $helper->getDefaultEntry()->getId()
                ) {
                    return $html;
                }

                // let Disqus view helper take care of the rest.
                $html = $helper->getView()->conversation($entry) . $html;

                return $html;
            }
        );

        // participate in content editing by providing a subform.
        P4Cms_PubSub::subscribe('p4cms.content.form.subForms',
            function()
            {
                return new Disqus_Form_Content(
                    array(
                        'name'  => Disqus_Form_Content::FORM_KEY
                    )
                );
            }
        );

        // participate in populating the Disqus content sub-form, to fill in default
        // values based on the module configuration if entry doesn't specify them
        P4Cms_PubSub::subscribe('p4cms.content.form.populate',
            function(Content_Form_Content $form, array $values)
            {
                // early exit if Disqus subform doesn't exist
                $disqusForm = $form->getSubForm(Disqus_Form_Content::FORM_KEY);
                if (!$disqusForm) {
                    return;
                }

                // get the entry type the content form is constructed for
                try {
                    $entry       = $form->getEntry();
                    $contentType = $entry->getContentTypeId();
                } catch (Content_Exception $e) {
                    $contentType = null;
                }

                // set showConversation value from module config if not present in values
                if ($contentType &&
                    !isset($values[Disqus_Form_Content::FORM_KEY][Disqus_Form_Content::SHOW_CONVERSATION_KEY])) {

                    // get module config
                    $module = P4Cms_Module::fetch('Disqus');
                    $config = $module->getConfig()->toArray();
                    $config = Disqus_Form_Configure::getNormalizedOptions($config);

                    // populate showConversation value
                    $showConversation = in_array($contentType, $config['contentTypes']);
                    $disqusForm
                        ->getElement(Disqus_Form_Content::SHOW_CONVERSATION_KEY)
                        ->setValue($showConversation);
                }
            }
        );

        $view = Zend_Layout::getMvcInstance()->getView();
        $view->addScriptPath(__DIR__ . '/views/scripts/');
    }
}
