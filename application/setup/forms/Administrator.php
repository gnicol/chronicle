<?php
/**
 * This is the Perforce server setup form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Setup_Form_Administrator extends P4Cms_Form
{
    const DEFAULT_ADMIN_NAME = 'admin';

    private $_p4Port;
    private $_serverType;
    private $_hasExternalAuth = false;

    /**
     * Defines the elements that make up the Administrator form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui administrator-form');

        // form should submit on enter
        $this->setAttrib('submitOnEnter', true);

        // set the method for the display form to POST
        $this->setMethod('post');

        // getRequestHost will return false for non-http requests;
        // fallback to hostname if this is the case or if we got an IP
        // back (which wouldn't produce a valid default email address)
        $defaultHost = Setup_Form_Site::getRequestHost();
        if (!$defaultHost || preg_match('/^[0-9\.]+$/', $defaultHost)) {
            $defaultHost = gethostname();
        }

        $defaultEmail = static::DEFAULT_ADMIN_NAME . '@' . $defaultHost;

        // add a field to collect the perforce user, updating email field on change, but only
        // if the user hasn't customized the email
        $this->addElement(
            'text',
            'user',
            array(
                'label'         => 'User Name',
                'required'      => true,
                'validators'    => array(array('SpecName')),
                'onChange'      => <<<EOT
                if (!dojo.byId('email')) return;

                var email = dojo.byId('email').value;

                if (email == ''
                    || (this.previousName == undefined && email == '$defaultEmail')
                    || email == this.previousName+'@$defaultHost'
                 ) {
                    dojo.byId('email').value = this.value + '@$defaultHost';
                 }

                 this.previousName = this.value;
EOT
            )
        );

        if ($this->getServerType() == Setup_Form_Storage::SERVER_TYPE_NEW) {
            // add a field to collect the admin password
            $this->addElement(
                'text',
                'email',
                array(
                    'label'         => 'Email',
                    'required'      => true,
                    'value'         => $defaultEmail,
                    'validators'    => array(
                        array('EmailAddress', false, Zend_Validate_Hostname::ALLOW_LOCAL)
                    )
                )
            );
        }

        // add a field to collect the perforce password.
        $this->addElement(
            'password',
            'password',
            array(
                'label'         => 'Password',
                'value'         => '',
            )
        );

        if ($this->getServerType() == Setup_Form_Storage::SERVER_TYPE_NEW) {
            // for new servers, provide a default username, which the user can replace
            $element = $this->getElement('user');
            $element->setValue(static::DEFAULT_ADMIN_NAME);

            // for new servers, the security counter will be set to 2, requiring a strong password
            $this->getElement('password')
                 ->addValidator('StrongPassword')
                 ->setRequired(true);

            // add a field to confirm the password.
            $this->addElement(
                'password',
                'passwordConfirm',
                array(
                    'label'     => 'Confirm Password',
                    'value'     => '',
                    'required'  => true,
                )
            );
        }

        // if we are connected to a P4 server with external authentication,
        // the chronicle user password needs to be collected for later use.
        if ($this->_hasExternalAuth) {
            // add a note field
            $this->addElement(
                'note',
                'note',
                array(
                    'value'  => "Your Perforce Server uses external authentication. If the user "
                             .  "'chronicle' does not already exist in your external authentication "
                             .  "system, add it, then enter the user's password below.",
                )
            );
            $this->getElement('note')
                 ->removeDecorator('label')
                 ->getDecorator('htmlTag')
                 ->setOption('class', 'external-auth');

            $this->addElement(
                'password',
                'systemPassword',
                array('label' => "Password")
            );
            $this->getElement('systemPassword')
                 ->getDecorator('label')
                 ->setOption(
                    'helpUri',
                    Zend_Controller_Front::getInstance()->getBaseUrl()
                    . '/' . Ui_Controller_Helper_HelpUrl::HELP_BASE_URL . '/'
                    . 'users.external_auth.html'
                 );

            $this->addDisplayGroup(
                array('note', 'systemPassword'),
                'externalAuth',
                array(
                    'legend'      => "Enter password for the system user 'chronicle':",
                    'class'       => 'external-auth'
                )
            );
        }

        // add the submit button
        $this->addElement(
            'SubmitButton',
            'continue',
            array(
                'label'     => 'Continue',
                'class'     => 'button-large preferred',
                'ignore'    => true
            )
        );
        $this->addElement(
            'SubmitButton',
            'goback',
            array(
                'label'     => 'Go Back',
                'class'     => 'button-large',
                'ignore'    => true
            )
        );

        // put the button in a fieldset.
        $this->addDisplayGroup(
            array('continue', 'goback'),
            'buttons',
            array('class' => 'buttons')
        );
    }

    /**
     * Check the license quota.
     *
     * @param   string   $serverLicense  serverLicense field from $p4->info()
     * @param   int      $users          count of users from $p4->users()
     * @return  boolean  true if license has room for more users
     * @todo    switch to using "p4 license" and check things like file quota
     */
    public function isP4LicenseQuotaSufficient($serverLicense, $users)
    {
        $licenses = false;
        if (preg_match("/([0-9]+) users?/", $serverLicense, $matches)) {
            $licenses = intval($matches[1]);
        }

        if ($licenses && $licenses <= $users) {
            $this->getElement('user')->addError(
                "Can't create a new site on this server. All available licenses are in use."
            );

            return false;
        }

        return true;
    }

    /**
     * Override isValid to check connection parameters.
     *
     * @param   array       $data   the field values to validate.
     * @return  boolean     true if the form values are valid.
     */
    public function isValid($data)
    {
        // do basic validation.
        if (!parent::isValid($data)) {
            return false;
        }

        // if serverType is 'new', nothing more to validate
        if (isset($this->_serverType)
            && $this->_serverType === Setup_Form_Storage::SERVER_TYPE_NEW
        ) {
            // make sure that the password and confirmation match
            $password = isset($data['password']) ? $data['password'] : null;
            $confirm  = isset($data['passwordConfirm']) ? $data['passwordConfirm'] : null;
            if ($password != $confirm) {
                $this->getElement('passwordConfirm')->addError("The two passwords do not match.");
                return false;
            }
            return true;
        }

        // try to login to perforce to test the connection parameters.
        try {
            $p4 = P4_Connection::factory(
                $this->_p4Port,
                $this->getValue('user'),
                null,
                $this->getValue('password')
            );
            $p4->login();
        } catch (P4_Connection_ConnectException $e) {
            $this->getElement('user')->addError("Unable to connect to server on '" . $this->_p4Port . "'.");
            return false;
        } catch (P4_Connection_LoginException $e) {
            if ($e->getCode() === P4_Connection_LoginException::IDENTITY_NOT_FOUND) {
                $this->getElement('user')->addError("Login failed. Unknown user.");
            } else if ($e->getCode() === P4_Connection_LoginException::CREDENTIAL_INVALID) {
                $this->getElement('password')->addError("Login failed. Invalid password.");
            } else {
                $this->getElement('user')->addError(
                    "Login failed. Please try again with a different username/password,"
                    . " or review the application log for more details."
                );
            }
            return false;
        }

        // check access level (must be super).
        try {
            $p4->run('protect', '-o');
        } catch (P4_Connection_CommandException $e) {
            if (stristr($e->getMessage(), "You don't have permission")) {
                $this->getElement('user')->addError("This user does not have permission to create sites.");
                return false;
            } else {
                throw $e;
            }
        }

        // check license quota.
        $result = $p4->run('users');
        $users  = count($result->getData());
        $info   = $p4->getInfo();
        if (!$this->isP4LicenseQuotaSufficient($info['serverLicense'], $users)) {
            return false;
        }

        // verify the 'chronicle' user is available if this is the initial setup.
        // if the application has no perforce resource, we assume initial setup.
        $bootstrap = Zend_Controller_Front::getInstance()->getParam("bootstrap");
        $perforce  = $bootstrap ? $bootstrap->getResource('perforce') : null;
        if (!$perforce && P4_User::exists(Setup_IndexController::P4D_USER, $p4)) {
            $this->getElement('user')->addError(
                "Can't create a new site on this server. The 'chronicle' user is already in use."
            );
            return false;
        }

        // verify chronicle password if it is required
        $valid = true;
        if (isset($data['systemPassword'])) {
            $password            = $data['systemPassword'];
            $systemUserId        = Setup_IndexController::P4D_USER;
            $isSystemUserPresent = P4_User::exists($systemUserId, $p4);

            // if we have external authentication and user doesn't exist, we
            // create the system user temporarily
            if ($this->_hasExternalAuth && !$isSystemUserPresent) {
                $user = new P4_User($p4);
                $user->setId($systemUserId)
                     ->setFullName($systemUserId)
                     ->setEmail($systemUserId)
                     ->save();
            }

            // try to login as system user
            try {
                $systemP4 = P4_Connection::factory($p4->getPort(), $systemUserId, null, $password);
                $systemP4->login();
            } catch (P4_Connection_LoginException $e) {
                // login failed
                $valid = false;
                $this->getElement('systemPassword')->addError("Login failed. Invalid password.");
            }

            // remove temporarily created system user
            if (!$isSystemUserPresent && isset($user)) {
                $user->delete();
            }
        }

        // passed all checks.
        return $valid;
    }

    /**
     * Set the port of the Perforce server for this site.
     *
     * @param   string  $port   the perforce server to connect to.
     */
    public function setP4Port($port)
    {
        $this->_p4Port = $port;
    }

    /**
     * Retrieve the current serverType value.
     *
     * @return  string  the current serverType.
     */
    public function getServerType()
    {
        return $this->_serverType;
    }

    /**
     * Set the type of Perforce server for this site (local/existing).
     *
     * @param   string  $type   the type of server to connect to.
     * @return  Content_Form_Content  provide a fluent interface.
     */
    public function setServerType($type)
    {
        $this->_serverType = $type;
        return $this;
    }

    /**
     * Set whether the target server connection is using external authentication.
     *
     * @param   bool    $hasExternalAuth    whether the target server is using external
     *                                      authentication.
     * @return  Content_Form_Content        provide a fluent interface.
     */
    public function setHasExternalAuth($hasExternalAuth)
    {
        $this->_hasExternalAuth = $hasExternalAuth;
        return $this;
    }
}
