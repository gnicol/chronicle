<?php
/**
 * This form provides a facility to upload data from a wordpress export.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Wpimport_Form_Configure extends P4Cms_Form
{
    /**
     * Defines the elements that make up the config form.
     */
    public function init()
    {
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui wordpress-import-form');

        // handle file upload
        $this->setAttrib('enctype', 'multipart/form-data');

        // set the method for the form to POST
        $this->setMethod('post');

        // file specification
        $this->addElement(
            'file',
            'importfile',
            array(
                'label'         => 'WordPress XML File',
                'required'      => true,
                'description'   => 'Select the exported WordPress XML file to import into Chronicle.',
                'validators'    => array('Extension' => array('xml'))
            )
        );

        // fix odd wording on error message
        // "File 'foobar.ext' has a false extension" by default.
        $this->getElement('importfile')->getValidator('Extension')->setMessage(
            "File '%value%' does not appear to be an xml file.",
            Zend_Validate_File_Extension::FALSE_EXTENSION
        );

        $this->addElement(
            'SubmitButton',
            'import',
            array(
                'label'     => 'Import',
                'required'  => false,
                'ignore'    => true
            )
        );

        // put the buttons in a fieldset.
        $this->addDisplayGroup(
            array('import'),
            'buttons',
            array('class' => 'buttons')
        );
    }
}
