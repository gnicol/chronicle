<?php
/**
 * Content sub-form to provide comment field used for changelist description.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_Save extends P4Cms_Form_SubForm
{
    /**
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // set the title of this form.
        $this->setLegend('Save');

        // add a comment element to provide a description that will appear in the version history.
        $this->addElement(
            'textarea',
            'comment',
            array(
                'label'         => 'Comment',
                'description'   => "Shown in the version history.",
                'rows'          => 3,
                'ignore'        => true
            )
        );

        // add a save button.
        $this->addElement(
            'SubmitButton',
            'save',
            array(
                'class'     => 'content-save-button preferred',
                'label'     => 'Save',
                'required'  => false,
                'ignore'    => true
            )
        );

        // put the save button in a fieldset at the bottom of the form.
        $this->addDisplayGroup(
            array('save'),
            'buttons',
            array(
                'class'     => 'buttons',
                'order'     => '1000'
            )
        );
    }
}