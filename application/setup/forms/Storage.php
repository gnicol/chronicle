<?php
/**
 * This is the Perforce server setup form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Setup_Form_Storage extends P4Cms_Form
{
    const SERVER_TYPE_NEW         = 'new';
    const SERVER_TYPE_EXISTING    = 'existing';

    /**
     * Defines the elements that make up the Perforce server form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui storage-form');

        // form should submit on enter
        $this->setAttrib('submitOnEnter', true);

        // set the method for the display form to POST
        $this->setMethod('post');

        // add option
        $this->addElement(
            'radio',
            'serverType',
            array(
                'multiOptions'  => array(
                    static::SERVER_TYPE_NEW
                        => 'In a new Perforce Server on the same machine as Chronicle',
                    static::SERVER_TYPE_EXISTING
                        => 'In a new depot on an existing Perforce Server'
                ),
                'value'         => static::SERVER_TYPE_NEW,
                'required'      => true,
                'onClick'       => "if (this.value == '" . static::SERVER_TYPE_NEW . "') {\n"
                                .  " p4cms.ui.hide('fieldset-existingServer');\n"
                                .  "} else {\n"
                                .  " p4cms.ui.show('fieldset-existingServer');\n"
                                .  " dojo.query('#port-element input')[0].focus();\n"
                                .  "}"
            )
        );

        // if a valid p4d is installed, default serverType to 'new'.
        // otherwise, disable the 'new' option.
        $serverType = $this->getElement('serverType');
        if ($this->isP4dInstalled() && $this->isP4dValid()) {
            $serverType->setValue(static::SERVER_TYPE_NEW);
        } else {
            $serverType->setValue(static::SERVER_TYPE_EXISTING)
                       ->setAttrib('disable', array(static::SERVER_TYPE_NEW));
        }

        // add a field to collect the perforce server port.
        $this->addElement(
            'text',
            'port',
            array(
                'label'         => 'Server Address',
                'value'         => 'perforce:1666',
                'required'      => true,
                'description'   => "Enter the host name and port of your Perforce Server (e.g. localhost:1666)",
                'validators'    => array(array('Port'))
            )
        );

        // put the port, user and password in a display group.
        $this->addDisplayGroup(
            array('port'),
            'existingServer',
            array('class' => 'existing-server')
        );

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
     * Check the P4 server version.
     *
     * @param   string   $serverVersion  P4 server version string
     * @return  boolean  true if the server version is high enough
     */
    public function isP4ServerVersionValid($serverVersion)
    {
        $minVersion  = strtolower(Setup_IndexController::MIN_P4_VERSION);
        $versionBits = explode("/", $serverVersion);
        $p4dVersion  = 0;
        if (array_key_exists('2', $versionBits)) {
            $p4dVersion = $versionBits[2];
        }

        return (version_compare(strtolower($p4dVersion), $minVersion) < 0) ? false : true;
    }

    /**
     * Override isValid to check connection parameters.
     *
     * @param   array       $data   the field values to validate.
     * @return  boolean     true if the form values are valid.
     */
    public function isValid($data)
    {
        // if serverType is 'new', ensure p4d is installed and valid.
        if (isset($data['serverType'])
            && $data['serverType'] === static::SERVER_TYPE_NEW
        ) {
            if ($this->isP4dInstalled() && $this->isP4dValid()) {
                // nothing more to validate.
                return true;
            } else {
                $this->getElement('serverType')->addError(
                    "Cannot create a local depot. A valid Perforce Server is not installed."
                );
                return false;
            }
        }

        // do basic validation.
        if (!parent::isValid($data)) {
            return false;
        }

        // Since we presumably have a valid port, now we need to 'connect' and determine
        // whether the target server is sufficiently new to host the application.
        $info = array();
        try {
            // note: auto user creation does not appear to be triggered for the info command
            // but should that change, we create a highly-unlikely username for the connection test.
            $username = md5(mt_rand());
            $p4 = P4_Connection::factory($this->getValue('port'), $username);
            $info = $p4->getInfo();
        } catch (P4_Connection_ConnectException $e) {
            // prime error with a generic message
            $error = "Unable to connect to server on '" .  $this->getValue('port') . "'.";

            // if the issue is related to the ssl library version, provide a detailed error
            if (preg_match('/SSL library must be at least version [0-9\.]+/', $e->getMessage(), $matches)) {
                $error = 'Unable to connect. ' . $matches[0];
            }

            $this->getElement('port')->addError($error);

            return false;
        }

        // check server version.
        if (!$this->isP4ServerVersionValid($info['serverVersion'])) {
            $this->getElement('port')->addError(
                "This server version is not supported. It is version "
                . $info['serverVersion']
                . ". Version "
                . Setup_IndexController::MIN_P4_VERSION
                . " or greater is required."
            );
            return false;
        }

        // verify the 'chronicle' user is available if this is the initial setup.
        // if the application has no perforce resource, we assume initial setup.
        try {
            $bootstrap = Zend_Controller_Front::getInstance()->getParam("bootstrap");
            $perforce  = $bootstrap ? $bootstrap->getResource('perforce') : null;
            if (!$perforce && P4_User::exists(Setup_IndexController::P4D_USER, $p4)) {
                $this->getElement('port')->addError(
                    "The 'chronicle' user is already in use on this server."
                );
                return false;
            }
        } catch (P4_Exception $e) {
            // this check will fail if auto-user creation is disabled,
            // but we check it again during admin form validation.
        }

        // passed all checks.
        return true;
    }

    /**
     * Check if the Perforce Server daemon is available.
     *
     * @return  boolean     true if p4d is installed.
     */
    public function isP4dInstalled()
    {
        exec(Setup_IndexController::P4D_BINARY .' -V 2>&1', $output, $return);

        if ($return === 0) {
            return true;
        }
    }

    /**
     * Check if the Perforce Server daemon is the correct version.
     *
     * @return  boolean     true if the correct p4d version is installed.
     */
    public function isP4dValid()
    {
        exec(Setup_IndexController::P4D_BINARY .' -V 2>&1', $output, $return);

        if (preg_match(":P4D/[^/]*/([^/]+)/:i", implode("\n", $output), $matches)) {
            return $this->isP4ServerVersionValid($matches[0]);
        }
        return false;
    }
}
