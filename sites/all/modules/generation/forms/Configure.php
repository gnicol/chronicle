<?php
/**
 * This form holds the configuration options for the content generation module.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Generation_Form_Configure extends P4Cms_Form
{
    /**
     * Defines the elements that make up the config form.
     */
    public function init()
    {
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui content-generation-form');

        // set the method for the form to POST
        $this->setMethod('post');

        // user input to determine how many content entries to create.
        $this->addElement(
            'text',
            'count',
            array(
                'label'         => 'Content Count',
                'required'      => true,
                'validators'    => array('Digits'),
                'description'   => 'Enter the number of content entries to create.'
            )
        );

        $this->addElement(
            'SubmitButton',
            'generate',
            array(
                'label'     => 'Generate',
                'required'  => false,
                'ignore'    => true
            )
        );

        // put the buttons in a fieldset.
        $this->addDisplayGroup(
            array('generate'),
            'buttons',
            array('class' => 'buttons')
        );
    }
}
