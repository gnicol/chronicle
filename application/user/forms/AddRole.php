<?php
/**
 * This is the add user form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class User_Form_AddRole extends P4Cms_Form
{
    const       E_ROLE_EXISTS               = "Role '%s' already exists.";
    const       E_ROLE_ADMINISTRATOR_EMPTY  = "The administrator role must have at least one user.";

    protected   $_uniqueIdRequired          = true;

    /**
     * Defines the elements that make up the role form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui role-form role-add-form');

        // set the method for the form to POST
        $this->setMethod('post');

        // add a field to collect the role name.
        $this->addElement(
            'text',
            'id',
            array(
                'label'         => 'Name',
                'required'      => true,
                'filters'       => array('StringTrim'),
                'validators'    => array('GroupName'),
                'size'          => 30,
                'order'         => 10
            )
        );

        // add a field to collect users to assign this role to (doesn't include system user).
        $users = P4Cms_User::fetchAll()->invoke('getId');

        $options = count($users) ? array_combine($users, $users) : array();
        $this->addElement(
            'MultiCheckbox',
            'users',
            array(
                'multiOptions'  => $options,
                'label'         => 'Users',
                'order'         => 20
            )
        );

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
     * Override isValid to ensure given role does not already exist.
     *
     * @param   array       $data   the field values to validate.
     * @return  boolean             true if the form values are valid.
     */
    public function isValid($data)
    {
        $valid = parent::isValid($data);

        if ($this->_uniqueIdRequired && isset($data['id'])) {
            if (P4Cms_Acl_Role::exists(($data['id']))) {
                $this->getElement('id')->addError(
                    sprintf(self::E_ROLE_EXISTS, $data['id'])
                );
                $valid = false;
            }
        }

        // administrator role must have at least one user
        if (isset($data['id'])
             && $data['id'] === P4Cms_Acl_Role::ROLE_ADMINISTRATOR
             && (!isset($data['users']) || empty($data['users']))
        ) {
            $this->getElement('users')->addError(self::E_ROLE_ADMINISTRATOR_EMPTY);
            $valid = false;
        }

        return $valid;
    }
}
