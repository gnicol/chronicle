<?php
/**
 * This is the add user form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class User_Form_Add extends P4Cms_Form
{
    const       E_USER_EXISTS           = "User '%s' already exists.";
    const       E_PASSWORDS_MISMATCH    = "The two passwords do not match";
    const       E_ROLE_REQUIRED         = "'%s' role is required.";

    protected   $_uniqueIdRequired      = true;
    protected   $_requireAdministrator  = false;

    /**
     * Overwrite construct to set form options.
     *
     * @param  array|Zend_Config|null $options  Zend provides no documentation for this param.
     */
    public function __construct($options = null)
    {
        if (isset($options['requireAdministrator'])) {
            $this->_requireAdministrator = (bool) $options['requireAdministrator'];
            unset($options['requireAdministrator']);
        }

        parent::__construct($options);
    }

    /**
     * Defines the elements that make up the edit form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui user-form user-add-form');

        // set the method for the form to POST
        $this->setMethod('post');

        // add a field to collect the user name.
        $this->addElement(
            'text',
            'id',
            array(
                'label'         => 'Username',
                'required'      => true,
                'filters'       => array('StringTrim'),
                'validators'    => array('UserName'),
                'size'          => 30,
                'order'         => 10
            )
        );

        // add a field to collect the user's email
        $this->addElement(
            'text',
            'email',
            array(
                'label'         => 'Email Address',
                'required'      => true,
                'filters'       => array('StringTrim'),
                'validators'    => array('EmailAddress'),
                'size'          => 30,
                'order'         => 20
            )
        );

        // add a field to collect the user's name
        $this->addElement(
            'text',
            'fullName',
            array(
                'label'         => 'Full Name',
                'required'      => true,
                'filters'       => array('StringTrim'),
                'size'          => 30,
                'order'         => 30
            )
        );

        // add a field to collect the user's password
        $this->addElement(
            'password',
            'password',
            array(
                'label'         => 'Password',
                'size'          => 30,
                'order'         => 40
            )
        );

        $this->addElement(
            'password',
            'passwordConfirm',
            array(
                'label'         => 'Confirm Password',
                'size'          => 30,
                'order'         => 45,
                'ignore'        => true
            )
        );

        // if user is allowed to manage roles, add a field to collect roles
        // (don't show virtual roles)
        if (P4Cms_User::hasActive() 
            && P4Cms_User::fetchActive()->isAllowed('users', 'manage-roles')
        ) {
            $roles = P4Cms_Acl_Role::fetchAll(
                array(P4Cms_Acl_Role::FETCH_HIDE_VIRTUAL => true),
                $this->getStorageAdapter()
            )->invoke('getId');

            $options = count($roles) ? array_combine($roles, $roles) : array();
            $this->addElement(
                'MultiCheckbox',
                'roles',
                array(
                    'multiOptions'  => $options,
                    'label'         => 'Roles',
                    'order'         => 50,
                    'ignore'        => true
                )
            );
        }

        // if security level > 0, strong passwords are required.
        $connection = $this->getStorageAdapter()->getConnection();
        if ($connection->getSecurityLevel() > 0) {
            $this->getElement('password')
                 ->addValidator('StrongPassword')
                 ->setRequired(true);
            $this->getElement('passwordConfirm')
                 ->setRequired(true);
        }

        // add the submit button
        $this->addElement(
            'SubmitButton',
            'save',
            array(
                'label'     => 'Save',
                'class'     => 'preferred',
                'required'  => false,
                'ignore'    => true
            )
        );
        
        // put the button in a fieldset.
        $this->addDisplayGroup(
            array('save'),
            'buttons',
            array(
                'class' => 'buttons',
                'order' => 100
            )
        );

    }

    /**
     * Override isValid to validate password confirmation and to
     * ensure given username does not already exist.
     *
     * @param   array       $data   the field values to validate.
     * @return  boolean     true if the form values are valid.
     */
    public function isValid($data)
    {
        $valid = parent::isValid($data);

        $password = isset($data['password']) ? $data['password'] : null;
        $confirm  = isset($data['passwordConfirm']) ? $data['passwordConfirm'] : null;
        if ($password != $confirm) {
            $this->getElement('passwordConfirm')->addError(
                self::E_PASSWORDS_MISMATCH
            );
            $valid = false;
        }

        if ($this->_uniqueIdRequired && isset($data['id'])) {
            if (P4_User::exists(($data['id']))) {
                $this->getElement('id')->addError(
                    sprintf(self::E_USER_EXISTS, $data['id'])
                );
                $valid = false;
            }
        }

        // if administrator role is required ensure that role is selected
        if ($this->_requireAdministrator
            && (!isset($data['roles'])
            || !in_array(P4Cms_Acl_Role::ROLE_ADMINISTRATOR, $data['roles']))
        ) {
            $this->getElement('roles')->addError(
                sprintf(self::E_ROLE_REQUIRED, P4Cms_Acl_Role::ROLE_ADMINISTRATOR)
            );
            $valid = false;
        }

        return $valid;
    }
}
