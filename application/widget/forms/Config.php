<?php
/**
 * This is the widget configuration form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widget_Form_Config extends P4Cms_Form
{
    /**
     * Defines the elements that make up the widget config form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('class', 'p4cms-ui multi-part widget-config');

        // add hidden region/widget id fields.
        $this->addElement('hidden', 'region');
        $this->addElement('hidden', 'widget');

        // add a field for the widget name.
        $this->addElement(
            'text',
            'title',
            array(
                'label'         => 'Title',
                'required'      => true
            )
        );

        // add a field for the widget name.
        $this->addElement(
            'checkbox',
            'showTitle',
            array(
                'label'         => 'Show Title'
            )
        );

        // add a field to change the weight of the widget.
        $this->addElement(
            'select',
            'order',
            array(
                'label'         => 'Order',
                'value'         => 0,
                'description'   => "Adjust the position of this widget in the region.",
                'multiOptions'  => array_combine(range(-10, 10), range(-10, 10))
            )
        );

        // add a field to allow users to specify a CSS class.
        $this->addElement(
            'text',
            'class',
            array(
                'label'         => 'CSS Class',
                'description'   => "Specify a CSS class to customize the appearance of this widget.",
                'filters'       => array('StringTrim'),
                'validators'    => array('CssClass')
            )
        );

        // add a field to load the widget out-of-band (asynchronously).
        $this->addElement(
            'checkbox',
            'asynchronous',
            array(
                'label'         => 'Load Asynchronously',
                'description'   => "Load this widget after the rest of the page.",
                'checked'       => false
            )
        );

        // put the general options in a fieldset.
        $this->addDisplayGroup(
            array('title', 'showTitle', 'order', 'class', 'asynchronous'),
            'general',
            array('legend' => 'General Options', 'order' => 1)
        );

        // add cancel/save buttons.
        $this->addElement(
            'Button',
            'cancel',
            array(
                'label'     => 'Cancel',
                'ignore'    => true
            )
        );
        $this->addElement(
            'SubmitButton',
            'save',
            array(
                'label'     => 'Save',
                'class'     => 'preferred',
                'ignore'    => true
            )
        );

        // put the buttons in a fieldset.
        $this->addDisplayGroup(
            array('save', 'cancel'),
            'buttons',
            array('class' => 'buttons', 'order' => 100)
        );
    }
}
