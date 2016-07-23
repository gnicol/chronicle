<?php
/**
 * This is the content delete form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_Delete extends P4Cms_Form
{
    /**
     * Defines the elements that make up the content delete form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui content-delete-form');

        // add a field to collect the entry ids to delete
        $this->addElement(
            'hidden',
            'ids',
            array(
                'required'  => true
            )
        );

        // add a blank note field to allow insert text later
        $this->addElement(
            'note',
            'note'
        );
        $this->getElement('note')
             ->getDecorator('htmlTag')
             ->setOption('class', 'delete-confirmation');

        // add a field to collect the comment
        $this->addElement(
            'textarea',
            'comment',
            array(
                'label'         => 'Comment',
                'description'   => "Shown in the version history.",
                'rows'          => 5
            )
        );

        // add delete button
        $this->addElement(
            'SubmitButton',
            'delete',
            array(
                'label'     => 'Delete',
                'required'  => false,
                'ignore'    => true,
                'class'     => 'preferred'
            )
        );

        $this->addDisplayGroup(
            array('delete'),
            'buttons',
            array('class' => 'buttons')
        );
    }
}