<?php
/**
 * The form providing configuration for import processing.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Search_Form_Manage extends P4Cms_Form
{
    /**
     * Defines the elements that make up the import form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui search-form-manage multi-part');

        // set the method for the display form to POST
        $this->setMethod('post');

        // add a field to allow users specify the MaxBufferedDocs
        $this->addElement(
            'text',
            'maxBufferedDocs',
            array(
                'label'         => 'Buffer Limit',
                'description'   => "The maximum number of documents buffered in memory at one time.",
                'filters'       => array('StringTrim'),
                'validators'    => array('Int', 'NotEmpty'),
                'value'         => Search_Module::getMaxBufferedDocs(),
                'size'          => 20,
            )
        );

        // add a field to allow users specify the MaxMergeDocs
        $this->addElement(
            'text',
            'maxMergeDocs',
            array(
                'label'         => 'Merge Limit',
                'description'   => 'The maximum number of documents merged into an index segment by auto-optimization.',
                'filters'       => array('StringTrim'),
                'validators'    => array('Int'),
                'value'         => Search_Module::getMaxMergeDocs(),
                'size'          => 20,
            )
        );

        // add a field to allow users specify the MergeFactor
        $this->addElement(
            'text',
            'mergeFactor',
            array(
                'label'         => 'Merge Factor',
                'description'   => 'Increasing this number decreases the frequency of auto-optimization.',
                'filters'       => array('StringTrim'),
                'validators'    => array('Int', 'NotEmpty'),
                'value'         => Search_Module::getMergeFactor(),
                'size'          => 20,
            )
        );

        $this->addElement(
            'SubmitButton',
            'Save',
            array(
                'class'     => 'preferred',
                'ignore'    => true
            )
        );

        // put the config elements in general display group
        $this->addDisplayGroup(
            array('maxBufferedDocs', 'maxMergeDocs', 'mergeFactor'),
            'general',
            array(
                'legend' => 'General Options',
                'order'  => 1
            )
        );

        // put the button in a fieldset
        $this->addDisplayGroup(
            array('Save'),
            'buttons',
            array(
                'class' => 'buttons',
                'order' => 100
            )
        );
    }
}
