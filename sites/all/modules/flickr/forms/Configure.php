<?php
/**
 * This is the flickr module config form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Flickr_Form_Configure extends P4Cms_Form
{

    /**
     * Defines the elements that make up the config form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui category-form');

        // set the method for the form to POST
        $this->setMethod('post');

        // add a field to collect the api key.
        $this->addElement(
            'text',
            'key',
            array(
                'label'         => 'Flickr API Key',
                'required'      => true,
                'description'   => 'Enter your Flickr API key.',
                'filters'       => array('StringTrim')
            )
        );

        $this->addElement(
            'SubmitButton',
            'save',
            array(
                'label'     => 'Save',
                'required'  => false,
                'ignore'    => true,
                'class'     => 'preferred'
            )
        );

        // put the buttons in a fieldset.
        $this->addDisplayGroup(
            array('save'),
            'buttons',
            array('class' => 'buttons')
        );
    }
}
