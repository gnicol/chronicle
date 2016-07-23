<?php
/**
 * A form to control how a Disqus conversation behaves per-content-entry.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Disqus_Form_Content extends P4Cms_Form_SubForm
{
    const   FORM_KEY                = 'disqus';
    const   SHOW_CONVERSATION_KEY   = 'showConversation';

    protected static  $_defaultOptions  = array(
        Disqus_Form_Content::SHOW_CONVERSATION_KEY => true
    );

    /**
     * Defines the elements of the content Disqus sub-form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        $this->setLegend('Disqus');

        $this->addElement(
            'checkbox',
            static::SHOW_CONVERSATION_KEY,
            array(
                'label' => 'Show Conversations',
            )
        );

        // put the checkbox input before the label.
        P4Cms_Form::moveCheckboxLabel($this->getElement(static::SHOW_CONVERSATION_KEY));
    }
}