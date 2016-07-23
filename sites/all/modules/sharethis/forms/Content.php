<?php
/**
 * A form to control how ShareThis container will behave per-content-entry.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Sharethis_Form_Content extends P4Cms_Form_SubForm
{
    /**
     * Defines the elements of the content sharethis sub-form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        $this->setLegend('ShareThis');

        $this->addElement(
            'checkbox',
            'showButtons',
            array(
                'label' => 'Show ShareThis Buttons'
            )
        );

        // put the checkbox input before the label
        P4Cms_Form::moveCheckboxLabel($this->getElement('showButtons'));
    }
}
