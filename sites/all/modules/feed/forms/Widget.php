<?php
/**
 * This is the RSS/Atom widget configuration form.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Feed_Form_Widget extends P4Cms_Form_SubForm
{
    /**
     * Defines the elements that make up the widget config form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // identify as feed config form
        $this->setAttrib('class', 'feed-config-form');

        // add element to collect the feed url
        $this->addElement(
            'text',
            'feedUrl',
            array(
                'label'         => 'Feed URL',
                'required'      => true,
                'description'   => 'Both RSS and Atom feeds are supported.'
            )
        );

        // add element to collect information whether feed url should be shown
        $this->addElement(
            'checkbox',
            'showFeedUrl',
            array(
                'label'         => 'Show Source URL'
            )
        );

        // add element to collect information whether feed items dates will be shown
        $this->addElement(
            'checkbox',
            'showDate',
            array(
                'label'         => 'Show Dates',
            )
        );

        // add element to collect information whether feed items descriptions should be shown
        $this->addElement(
            'checkbox',
            'showDescription',
            array(
                'label'         => 'Show Descriptions',
            )
        );

        // put the checkbox inputs before their labels
        P4Cms_Form::moveCheckboxLabel($this->getElement('showFeedUrl'));
        P4Cms_Form::moveCheckboxLabel($this->getElement('showDate'));
        P4Cms_Form::moveCheckboxLabel($this->getElement('showDescription'));

        // add element to limit number of displayed items
        $options = array('' => 'Unlimited')
                 + array_combine(range(1, 10),       range(1, 10))
                 + array_combine(range(15, 30, 5),   range(15, 30, 5));
        $this->addElement(
            'select',
            'maxItems',
            array(
                'label'         => 'Maximum Items',
                'description'   => "Enter the maximum number of items to display.",
                'multiOptions'  => $options,
                'value'         => 10
            )
        );
    }
}