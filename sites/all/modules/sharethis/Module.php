<?php
/**
 * The Sharethis module provides facility for sharing a content through popular
 * social media services. To each content entry, module appends a container with
 * buttons provided by the ShareThis service.
 *
 * General options (buttons style, which sharing services to show and ShareThis
 * publisher key for tracking analytics) are handled by the module configuration.
 *
 * Additionally, module configuration also specifies content types with visible
 * ShareThis container by default (i.e. if the entry doesn't specify whether to
 * show the container or not). This global settings may be however overriden by
 * individual content entries.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Sharethis_Module extends P4Cms_Module_Integration
{
    /**
     * When this module loads, subscribe to content rendering
     * to append ShareThis container with buttons to the entry.
     */
    public static function load()
    {
        // append ShareThis buttons after the content entry
        P4Cms_PubSub::subscribe('p4cms.content.render.close',
            function($html, $helper)
            {
                // early exit if we have no entry id
                $entry = $helper->getEntry();
                if (!$entry->getId()) {
                    return $html;
                }

                // get module config
                $module = P4Cms_Module::fetch('Sharethis');
                $config = $module->getConfig()->toArray();
                $config = Sharethis_Form_Configure::getNormalizedOptions($config);

                // prepare options for this module saved by the entry
                $options = (array) $entry->getValue('sharethis');

                // decide whether to show ShareThis container - honor the value
                // saved by the entry;
                // if its not available, then decide according to default visibility
                // for the entry's content type defined in the module's configuration
                $showContainer = isset($options['showButtons'])
                    ? (bool) $options['showButtons']
                    : in_array($entry->getContentTypeId(), $config['contentTypes']);

                // prepend sharethis container
                if ($showContainer) {
                    $html = $helper->getView()->sharethis($config) . $html;
                }

                return $html;
            }
        );

        // participate in content editing by providing a subform
        P4Cms_PubSub::subscribe('p4cms.content.form.subForms',
            function(Content_Form_Content $form)
            {
                return new Sharethis_Form_Content(
                    array(
                        'name' => 'sharethis'
                    )
                );
            }
        );

        // participate in populating the sharethis content sub-form, to fill in default
        // values based on the module configuration if entry doesn't specify them
        P4Cms_PubSub::subscribe('p4cms.content.form.populate',
            function(Content_Form_Content $form, array $values)
            {
                // early exit if sharethis subform doesn't exist
                $sharethisForm = $form->getSubForm('sharethis');
                if (!$sharethisForm) {
                    return;
                }

                // get the entry type the content form is constructed for
                try {
                    $entry       = $form->getEntry();
                    $contentType = $entry->getContentTypeId();
                } catch (Content_Exception $e) {
                    $contentType = null;
                }

                // set showButtons value from module config if not present in values
                if ($contentType && !isset($values['sharethis']['showButtons'])) {
                    // get module config
                    $module = P4Cms_Module::fetch('Sharethis');
                    $config = $module->getConfig()->toArray();
                    $config = Sharethis_Form_Configure::getNormalizedOptions($config);

                    // populate showButtons value
                    $showButtons = in_array($contentType, $config['contentTypes']);
                    $sharethisForm->getElement('showButtons')->setValue($showButtons);
                }
            }
        );
    }
}