<?php
/**
 * Workflow add/edit form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Form_Workflow extends P4Cms_Form
{
    /**
     * Define the elements that make up the workflow form.
     */
    public function init()
    {
        // form should use p4cms-ui styles
        $this->setAttrib('class', 'p4cms-ui workflow-form');

        // set the method for the form to POST
        $this->setMethod('post');

        // add a field to collect the type id
        $this->addElement(
            'text',
            'id',
            array(
                'label'         => 'Id',
                'required'      => true,
                'filters'       => array('StringTrim'),
                'validators'    => array('RecordId')
            )
        );

        // add a field to collect the workflow label
        $this->addElement(
            'text',
            'label',
            array(
                'label'         => 'Label',
                'required'      => true,
                'filters'       => array('StringTrim')
            )
        );

        // add a field to collect the workflow description
        $this->addElement(
            'textarea',
            'description',
            array(
                'label'         => 'Description',
                'rows'          => 3,
                'cols'          => 80,
                'required'      => false,
                'filters'       => array('StringTrim')
            )
        );

        // add a field to collect the workflow states definitions
        $this->addElement(
            'textarea',
            'states',
            array(
                'label'         => 'States',
                'required'      => true,
                'rows'          => 20,
                'cols'          => 80,
                'description'   => "Define the states that make up this workflow."
            )
        );

        // @todo: update URI when workflow documentation is in place (see job045174)
        $this->getElement('states')
             ->getDecorator('label')
             ->setOption(
                'helpUri',
                Zend_Controller_Front::getInstance()->getBaseUrl()
                . '/' . Ui_Controller_Helper_HelpUrl::HELP_BASE_URL . '/'
                . 'workflows.configure.html'
             );

        // add the submit button
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

        // put buttons in a fieldset
        $this->addDisplayGroup(
            array('save'),
            'buttons',
            array('class' => 'buttons')
        );
    }

    /**
     * Extend isValid to ensure that the states definition
     * can be used to construct a form.
     *
     * @param   array   $data   Data to validate.
     * @return  boolean
     */
    public function isValid($data)
    {
        if (!parent::isValid($data)) {
            return false;
        }

        // attempt to parse states as INI format
        try {
            $workflow = new Workflow_Model_Workflow;
            $workflow->setStatesFromIni($data['states']);
            $states   = $workflow->getStates();
        } catch (Zend_Config_Exception $e) {
            $this->getElement('states')->addError(
                "Invalid format. Please use the INI format to define states."
            );
            return false;
        }

        // require at least one state defined
        $definedStatesCount = 0;
        foreach ($states as $stateValues) {
            // consider state as defined if (and only if) stateValues is an array
            // (i.e. state is defined as a section in states INI config)
            if (is_array($stateValues)) {
                $definedStatesCount++;
            }
        }
        if ($definedStatesCount < 1) {
            $this->getElement('states')->addError(
                "At least one state has to be defined."
            );
            return false;
        }

        return true;
    }
}
