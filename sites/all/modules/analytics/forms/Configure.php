<?php
/**
 * This is the analytics module config form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Analytics_Form_Configure extends P4Cms_Form
{

    /**
     * Defines the elements that make up the config form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui');

        // set the method for the form to POST
        $this->setMethod('post');

        // add a field to collect the api key.
        $this->addElement(
            'text',
            'accountNumber',
            array(
                'label'         => 'Site Profile Id',
                'required'      => true,
                'description'   => 'Your Google Analytics site profile identifier has the format UA-XXXXX-X.',
                'filters'       => array('StringTrim')
            )
        );

        // @todo: this URI should be in a config file somewhere.
        $this->getElement('accountNumber')
             ->getDecorator('label')
             ->setOption(
                'helpUri',
                Zend_Controller_Front::getInstance()->getBaseUrl()
                . '/' . Ui_Controller_Helper_HelpUrl::HELP_BASE_URL . '/'
                . 'analytics.html#analytics.module.configure'
             );

        $this->addElement(
            'multiCheckbox',
            'customVars',
            array(
                'label'         => 'Tracking Variables',
                'multiOptions'  => array(
                    'userId'        => 'Include Active User',
                    'userRole'      => 'Include Active Role',
                    'contentId'     => 'Include Content Id',
                    'contentType'   => 'Include Content Type'
                )
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

    /**
     * Extend isValid to verify values
     *
     * @param   array    $data      array of submitted form values
     * @return  boolean             whether or not the form data is valid
     */
    public function isValid($data)
    {
        if (!parent::isValid($data)) {
            return false;
        }

        if (!preg_match("/UA-\w{4,10}-\d{1,4}/is", $data['accountNumber'])) {
            $this->getElement('accountNumber')->addError('Invalid account number format.');
            return false;
        }

        return true;
    }
}
