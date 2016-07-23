<?php
/**
 * This is the edit user form. Extends from add user form
 * to provide special behavior when editing.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class User_Form_Edit extends User_Form_Add
{
    const       E_INVALID_PASSWORD  = "Current password is incorrect.";

    protected   $_uniqueIdRequired  = false;
    protected   $_needOldPassword   = true;
    protected   $_canChangePassword = true;

    /**
     * Overwrite construct to set if old password input is neccessary for setting up new password.
     *
     * @param  array|Zend_Config|null $options  Zend provides no documentation for this param.
     */
    public function __construct($options = null)
    {
        if (isset($options['needOldPassword'])) {
            $this->_needOldPassword = (bool) $options['needOldPassword'];
            unset($options['needOldPassword']);
        }

        parent::__construct($options);
    }

    /**
     * Modifies the elements defined by the add form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        parent::init();

        // remove the display group that the add form uses to display nicely in a dialog
        $this->removeDisplayGroup('general');

        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui user-form user-edit-form');

        // disable username change option
        $this->getElement('id')
             ->setAttrib('disabled', true);

        // add change password checkbox before password fields
        $this->addElement(
            'checkbox',
            'changePassword',
            array(
                'label'         => 'Change Password',
                'onClick'       => "if (this.checked) {"
                                .  " p4cms.ui.show('fieldset-passwords');"
                                .  "} else {"
                                .  " p4cms.ui.hide('fieldset-passwords');"
                                .  "}",
                'order'         => $this->getElement('password')->getOrder()-2,
                'ignore'        => true
            )
        );

        // add current password field if needed and prepare display group elements array
        $groupElements = array(
            'password',
            'passwordConfirm'
        );
        if ($this->_needOldPassword && $this->_canChangePassword) {
            $this->addElement(
                'password',
                'currentPassword',
                array(
                    'label'         => 'Current Password',
                    'required'      => true,
                    'size'          => 30,
                    'ignore'        => true
                )
            );

            array_unshift($groupElements, 'currentPassword');
        }

        // if password cannot be changed (due to external authentication),
        // we replace the password fields with a note field
        $classes = 'passwords';
        $order      = $this->getElement('password')->getOrder()-1;
        if (!$this->_canChangePassword) {
            // add a note field
            $this->addElement(
                'note',
                'note',
                array(
                    'value'  => "<li>Your Perforce Server is using external authentication. "
                             .  "Please change the user's password in the external authentication system.</li>",
                )
            );
            $this->getElement('note')
                 ->removeDecorator('label')
                 ->getDecorator('htmlTag')
                 ->setOption('class', 'errors')
                 ->setOption('tag', 'ul');

            $groupElements = array('note');
            $classes      .= ' external-auth';
            $this->removeElement('password');
            $this->removeElement('passwordConfirm');
        }

        $this->addDisplayGroup(
            $groupElements,
            'passwords',
            array('class' => $classes, 'order' => $order)
        );

        // create a button to delete this user.
        $this->addElement(
            "ConfirmTooltipButton",
            "delete",
            array(
                'label'                 => 'Delete',
                'content'               => 'Are you sure you want to delete this user?',
                'actionButtonOptions'   => Zend_Json::encode(array('label' => 'Delete User')),
                'actionSingleClick'     => 'true',
                'ignore'                => true,
                'onConfirm'             => "
                    // create mock form to post data of the user to delete
                    var form = dojo.create('form', {
                        action: p4cms.url({
                            module: 'user',
                            action: 'delete'
                        }),
                        method: 'post'
                    });

                    // add form field(s) with data to post
                    dojo.place(dojo.create('input', {
                        type: 'hidden',
                        name: 'id',
                        value: dojo.query('form.user-form input[name=id]')[0].value
                    }), form);

                    // place form to body domnode otherwise it may not be
                    // submittable in some browsers (FF)
                    dojo.place(form, dojo.body());

                    // submit the form and let user controller to do the work
                    form.submit();
                "
            )
        );

        // add delete button to button fieldset.
        $this->getDisplayGroup('buttons')->addElement(
            $this->getElement('delete')
        );
    }

    /**
     * Override isValid to verify existing password if change password is checked.
     *
     * @param   array       $data   the field values to validate.
     * @return  boolean     true if the form values are valid.
     */
    public function isValid($data)
    {
        // if password cannot be changed, the password fields are all removed
        // we skip these.
        if ($this->_canChangePassword) {
            // disable automatic insertion of the NotEmpty validator, otherwise
            // users with no password won't be able to set up new one.
            if ($this->_needOldPassword) {
                $this->getElement('currentPassword')
                     ->setAutoInsertNotEmptyValidator(false);
            }

            if (empty($data['changePassword'])) {
                $this->_disablePasswordValidation();
                $data['password']            = null;
                $data['passwordConfirm']     = null;
                if ($this->_needOldPassword) {
                    $data['currentPassword'] = null;
                }
            }
        }

        // if passwords cannot be changed, make sure the changePassword element is not checked
        // and the description is hidden
        if (!empty($data['changePassword']) && !$this->_canChangePassword) {
            $data['changePassword'] = null;
            $this->getDisplayGroup('passwords')->setAttrib('style', 'opacity: 0; display: none;');
        }

        $valid = parent::isValid($data);

        // verify current password if element exists
        if ($this->getElement('currentPassword') !== null) {
            $user = P4Cms_User::fetch($this->getElement('id')->getValue());
            if (!empty($data['changePassword']) && !$user->isPassword($data['currentPassword'])) {
                $this->getElement('currentPassword')->addError(
                    self::E_INVALID_PASSWORD
                );
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Set if the password can be changed with the P4 server we are connected to.
     *
     * @param   bool    $canChangePassword  whether we can change the password.
     * @return  User_Form_Edit              provide a fluent interface
     */
    public function setCanChangePassword($canChangePassword)
    {
        $this->_canChangePassword = $canChangePassword;
        return $this;
    }

    /**
     * Remove all validators from password and passwordConfirm fields
     */
    protected function _disablePasswordValidation()
    {
        $this->getElement('password')
             ->clearValidators()
             ->setAutoInsertNotEmptyValidator(false);

        $this->getElement('passwordConfirm')
             ->clearValidators()
             ->setAutoInsertNotEmptyValidator(false);

        if ($this->_needOldPassword) {
            $this->getElement('currentPassword')
                 ->clearValidators()
                 ->setAutoInsertNotEmptyValidator(false);
        }
    }
}
