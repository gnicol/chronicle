<?php
/**
 * This is the menu form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_Form_Menu extends P4Cms_Form
{
    /**
     * Defines the elements that make up the menu form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui menu-form');

        // set the method for the form to POST
        $this->setMethod('post');

        // add a field to collect the menu title.
        $validator = new P4Cms_Validate_RecordId;
        $validator->setAllowForwardSlash(false);
        $this->addElement(
            'text',
            'id',
            array(
                'label'         => 'Id',
                'required'      => true,
                'filters'       => array('StringTrim'),
                'validators'    => array($validator)
            )
        );

        // add a field to collect the menu label.
        $this->addElement(
            'text',
            'label',
            array(
                'label'         => 'Label',
                'required'      => true,
                'filters'       => array('StringTrim')
            )
        );

        // add a field to change the order of the menu with respect to other menus
        $this->addElement(
            'select',
            'order',
            array(
                'label'         => 'Order',
                'value'         => 0,
                'description'   => "Adjust the position of this menu when managing menus.",
                'multiOptions'  => array_combine(range(-10, 10), range(-10, 10))
            )
        );

        // add a field to control if this menu is shown when editing content
        $this->addElement(
            'checkbox',
            'showInContentForm',
            array(
                'label'         => 'Show In Content Form',
                'value'         => false,
                'description'   => "Show when managing menus via the content form."
            )
        );

        // add the submit button
        $this->addElement(
            'SubmitButton',
            'save',
            array(
                'label'     => 'Save',
                'required'  => false,
                'class'     => 'preferred',
                'ignore'    => true
            )
        );

        // put the button in a fieldset.
        $this->addDisplayGroup(
            array('save'),
            'buttons',
            array('class' => 'buttons')
        );
    }
}