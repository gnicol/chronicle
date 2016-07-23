<?php
/**
 * This is the content type form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_Type extends P4Cms_Form_PubSubForm
{
    /**
     * Layouts we don't want to show the user.
     * @var array
     */
    protected   $_systemLayouts = array(
        'manage-layout',
        'error-layout',
        'setup-layout'
    );

    /**
     * Defines the elements that make up the content type form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // set the pub/sub topic so others can influence form
        $this->setTopic('p4cms.content.type.form');

        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui content-type-form');

        // set the method for the form to POST
        $this->setMethod('post');

        // add a field to collect the type id.
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

        // add a field to collect the type label.
        $this->addElement(
            'text',
            'label',
            array(
                'label'         => 'Label',
                'required'      => true,
                'filters'       => array('StringTrim')
            )
        );


        // add a field to pick/enter the group
        // ensure it starts empty to avoid defaulting to the top item
        $options = array('' => '');

        // if we have any know groups; tack them on as well
        $groups  = array_keys(P4Cms_Content_Type::fetchGroups());
        if (count($groups)) {
            $options += array_combine($groups, $groups);
        }

        $this->addElement(
            'ComboBox',
            'group',
            array(
                'label'         => 'Group',
                'required'      => true,
                'filters'       => array('StringTrim'),
                'multiOptions'  => $options,
                'dijitParams'   => array('Validator' => "return true;") // disable the client side validation
            )
        );

        // add a field to collect the icon.
        $this->addElement(
            'ImageFile',
            'icon',
            array(
                'label'         => 'Icon',
                'required'      => false
            )
        );

        // add a field to collect the type description.
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

        // add a field to collect the field element definitions.
        $this->addElement(
            'textarea',
            'elements',
            array(
                'label'         => 'Elements',
                'required'      => true,
                'rows'          => 20,
                'cols'          => 80,
                'description'   => "Define the fields that make up this content type."
            )
        );

        // @todo: this URI should be in a config file somewhere.
        $this->getElement('elements')
             ->getDecorator('label')
             ->setOption(
                'helpUri',
                Zend_Controller_Front::getInstance()->getBaseUrl()
                . '/' . Ui_Controller_Helper_HelpUrl::HELP_BASE_URL . '/'
                . 'content.type.elements.html'
             );

        // collect all non-system layouts.
        $layouts = array();
        $view    = $this->getView();
        foreach ($view->getScriptPaths() as $path) {
            if (!is_dir($path)) {
                continue;
            }
            $files = new DirectoryIterator($path);
            foreach ($files as $file) {
                if (preg_match('/-layout.phtml$/', $file->getBasename())) {
                    $value = $file->getBasename('.phtml');
                    if (!in_array($value, $this->_systemLayouts)) {
                        $label = ucwords(str_replace("-", " ", $value));
                        $layouts[$value] = $label;
                    }
                }
            }
        }

        // add option to select the view layout.
        $this->addElement(
            'select',
            'layout',
            array(
                'label'         => 'Layout',
                'multiOptions'  => $layouts,
                'description'   => "Select the layout to display content in."
            )
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

        // put the button in a fieldset.
        $this->addDisplayGroup(
            array('save'),
            'buttons',
            array('class' => 'buttons')
        );

        return parent::init();
    }

    /**
     * Extend isValid to ensure that the elements definition
     * can be used to construct a form.
     *
     * @param   array   $data   the data to validate.
     * @return  boolean
     * @todo    validate element decorators, filters, validators and display decorators.
     */
    public function isValid($data)
    {
        if (!parent::isValid($data)) {
            return false;
        }

        // attempt to parse elements as INI format.
        try {
            $type = new P4Cms_Content_Type;
            $type->setElementsFromIni($data['elements']);
            $elements = $type->getElements();
        } catch (Zend_Config_Exception $e) {
            $this->getElement('elements')->addError(
                "Invalid format. Please use the INI format to define elements."
            );
            return false;
        }

        // ensure form element names are valid as record field names.
        $invalid = array();
        foreach (array_keys($elements) as $name) {
            $validator = new P4Cms_Validate_ContentTypeElementName;
            if (!$validator->isValid($name)) {

                // prepare validator messages for display (ensure first
                // letter is lower case and remove trailing periods).
                $messages = array();
                foreach ($validator->getMessages() as $message) {
                    $messages[] = lcfirst(preg_replace("/\.$/", "", $message));
                }

                $invalid[] = $name . " (" . implode(", ", $messages) . ")";
            }
        }
        if (!empty($invalid)) {
            $this->getElement('elements')->addError(
                "One or more of the elements you defined have invalid names: "
                . implode(", ", $invalid) . "."
            );
            return false;
        }

        // set the elements on a type object and test if they are valid.
        $type = new P4Cms_Content_Type;
        $type->setElements($elements);
        if (!$type->hasValidElements()) {
            $this->getElement('elements')->addError(implode("\n", $type->getValidationErrors()));
            return false;
        }

        return true;
    }
}
