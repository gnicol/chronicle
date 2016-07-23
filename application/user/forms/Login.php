<?php
/**
 * This is the user login form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class User_Form_Login extends P4Cms_Form
{
    protected   $_acl   = null;

    /**
     * Defines the elements that make up the login form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui login-form p4cms-user-login');
        
        // form should submit on enter
        $this->setAttrib('submitOnEnter', true);

        // set the method for the form to POST
        $this->setMethod('post');

        // add a field to collect the user name.
        $this->addElement(
            'text',
            'user',
            array(
                'label'         => 'User',
                'required'      => true,
                'description'   => "Enter your email address or username.",
                'filters'       => array('StringTrim'),
                'size'          => 20
            )
        );

        // add a field to collect the user's password
        $this->addElement(
            'password',
            'password',
            array(
                'label'         => 'Password',
                'required'      => false,
                'size'          => 20
            )
        );

        // add the submit button
        $this->addElement(
            'SubmitButton',
            'login',
            array(
                'label'     => 'Login',
                'required'  => false,
                'ignore'    => true,
                'class'     => 'preferred'
            )
        );
        $buttons = array('login');

        // add an 'add user' button, if we have appropriate permissions
        $user = P4Cms_User::fetchActive();
        if ($user->isAllowed('users', 'add', $this->getAcl()) && P4_User::isAutoUserCreationEnabled()) {
            $this->addElement(
                'Button',
                'addNewUser',
                array(
                    'label'     => 'New User',
                    'class'     => 'add-button',
                    'ignore'    => true,
                    'onclick'   => 'window.location = p4cms.url({module: "user", controller: "index", action: "add"});',
                )
            );
            $buttons[] = 'addNewUser';
        }

        // put the button in a fieldset.
        $this->addDisplayGroup(
            $buttons,
            'buttons',
            array('class' => 'buttons')
        );
    }

    /**
     * Set the ACL instance to use.
     *
     * @param   P4Cms_Acl   $acl    the acl instance to use.
     */
    public function setAcl(P4Cms_Acl $acl)
    {
        $this->_acl = $acl;
    }

    /**
     * Get the ACL instance in use by this form.
     *
     * @return  P4Cms_Acl               the acl in use by the form.
     * @throws  P4Cms_Acl_Exception     if no acl has been set.
     */
    public function getAcl()
    {
        if (!$this->_acl instanceof P4Cms_Acl) {
            throw new P4Cms_Acl_Exception(
                "Cannot get acl. No acl has been set."
            );
        }

        return $this->_acl;
    }
}
