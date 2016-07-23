<?php
/**
 * Test the PerforceForm and its validation.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Setup_Test_AdministratorFormTest extends ModuleTest
{
    /**
     * Test form instantiation.
     */
    public function testFormCreation()
    {
        // test all possible elements
        $form = new Setup_Form_Administrator(array('serverType' => Setup_Form_Storage::SERVER_TYPE_NEW));
        $this->assertTrue($form instanceof Zend_Form, 'form class');
        $this->assertTrue($form->getElement('user')            instanceof Zend_Form_Element, 'user element');
        $this->assertTrue($form->getElement('password')        instanceof Zend_Form_Element, 'password element');
        $this->assertTrue($form->getElement('passwordConfirm') instanceof Zend_Form_Element, 'pw confirm element');
        $this->assertTrue($form->getElement('continue')        instanceof Zend_Form_Element, 'continue element');
        $this->assertTrue($form->getElement('goback')          instanceof Zend_Form_Element, 'goback element');

        // when using an existing server, the confirm password element should not exist
        $form = new Setup_Form_Administrator(array('serverType' => Setup_Form_Storage::SERVER_TYPE_EXISTING));
        $this->assertTrue($form instanceof Zend_Form, 'form class');
        $this->assertTrue($form->getElement('user')            instanceof Zend_Form_Element, 'user element');
        $this->assertTrue($form->getElement('password')        instanceof Zend_Form_Element, 'password element');
        $this->assertEquals($form->getElement('passwordConfirm'), null, 'pw confirm element');
        $this->assertTrue($form->getElement('continue')        instanceof Zend_Form_Element, 'continue element');
        $this->assertTrue($form->getElement('goback')          instanceof Zend_Form_Element, 'goback element');
    }

    /**
     * Test user validation
     *
     * @todo: test case were user is ''... should be an error?
     */
    public function testUserValidation()
    {
        $tests = array(
            // valid cases
            array('user' => $this->utility->getP4Params('user'),        'valid' => true),
            array('user' => $this->utility->getP4Params('user') . '..', 'valid' => true),
            array('user' => $this->utility->getP4Params('user') . '.',  'valid' => true),
            array('user' => $this->utility->getP4Params('user') . "'",  'valid' => true),
            array('user' => $this->utility->getP4Params('user') . '"',  'valid' => true),
            array('user' => $this->utility->getP4Params('user') . ',',  'valid' => true),
            array('user' => $this->utility->getP4Params('user') . ':',  'valid' => true),
            array('user' => $this->utility->getP4Params('user') . ';',  'valid' => true),

            // invalid cases
            array('user' => 'john doe',   'valid' => false, 'error' => array('hasSpaces')),
            array('user' => '12345',      'valid' => false, 'error' => array('isNumeric')),
            array('user' => 12345,        'valid' => false, 'error' => array('invalidType')),
            array('user' => 'jdoe@',      'valid' => false, 'error' => array('revision')),
            array('user' => 'jdoe...foo', 'valid' => false, 'error' => array('wildcards')),
            array('user' => '#jdoe',      'valid' => false, 'error' => array('revision')),
            array('user' => 'jdoe%%1',    'valid' => false, 'error' => array('positional'))
        );

        foreach ($tests as $test) {
            $form = new Setup_Form_Administrator;
            $this->assertEquals(
                $test['valid'],
                $form->getElement('user')->isValid($test['user'])
            );
            $expectedErrors = ($test['valid'] == false)
                ? $test['error']
                : array();
            $this->assertEquals(
                $expectedErrors,
                $form->getElement('user')->getErrors(),
                'Expected error message'
            );
        }
    }

    /**
     * Test form validation.
     */
    public function testFormValidation()
    {
        $tests = array(
            array(
                'label'     => __LINE__ . ': no values, new server',
                'options'   => array(
                    'serverType' => Setup_Form_Storage::SERVER_TYPE_NEW
                ),
                'values'    => array(),
                'valid'     => false,
                'errors'    => array(
                    'user'          => array(
                        'isEmpty'   => "Value is required and can't be empty"
                    ),
                    'email'          => array(
                        'isEmpty'   => "Value is required and can't be empty"
                    ),
                    'password'          => array(
                        'isEmpty'       => "Value is required and can't be empty"
                    ),
                    'passwordConfirm'   => array(
                        'isEmpty'       => "Value is required and can't be empty"
                    )
                )
            ),
            array(
                'label'     => __LINE__ . ': no values, existing server',
                'options'   => array(
                    'serverType' => Setup_Form_Storage::SERVER_TYPE_EXISTING
                ),
                'values'    => array(),
                'valid'     => false,
                'errors'    => array(
                    'user'          => array(
                        'isEmpty'   => "Value is required and can't be empty"
                    )
                )
            ),
            array(
                'label'     => __LINE__ . ': existing server, unknown user',
                'options'   => array(
                    'serverType' => Setup_Form_Storage::SERVER_TYPE_EXISTING,
                    'p4Port'     => $this->utility->getP4Params('port'),
                ),
                'values'    => array(
                    'user'          => 'jdoe',
                    'password'      => 'secret'
                ),
                'valid'     => false,
                'errors'    => array(
                    'user'  => array("Login failed. Unknown user.")
                )
            ),
            array(
                'label'     => __LINE__ . ': existing server, user valid, bad password',
                'options'   => array(
                    'serverType' => Setup_Form_Storage::SERVER_TYPE_EXISTING,
                    'p4Port'     => $this->utility->getP4Params('port'),
                ),
                'values'    => array(
                    'user'          => $this->utility->getP4Params('user'),
                    'password'      => 'secret',
                ),
                'valid'     => false,
                'errors'    => array(
                    'password'  => array("Login failed. Invalid password.")
                )
            ),
            array(
                'label'     => __LINE__ . ': new server, password mismatch',
                'options'   => array(
                    'serverType' => Setup_Form_Storage::SERVER_TYPE_NEW,
                ),
                'values'    => array(
                    'user'              => 'fred',
                    'email'             => 'fred@hostname',
                    'password'          => 'secretPW1',
                    'passwordConfirm'   => 'barney'
                ),
                'valid'     => false,
                'errors'    => array(
                    'passwordConfirm'  => array("The two passwords do not match.")
                )
            ),
            array(
                'label'     => __LINE__ . ': new server, all valid',
                'options'   => array(
                    'serverType' => Setup_Form_Storage::SERVER_TYPE_NEW,
                ),
                'values'    => array(
                    'user'              => $this->utility->getP4Params('user'),
                    'email'             => $this->utility->getP4Params('user') . '@test-host.com',
                    'password'          => $this->utility->getP4Params('password'),
                    'passwordConfirm'   => $this->utility->getP4Params('password')
                ),
                'valid'     => true,
                'errors'    => array()
            ),
            array(
                'label'     => __LINE__ . ': existing server, all valid',
                'options'   => array(
                    'serverType' => Setup_Form_Storage::SERVER_TYPE_EXISTING,
                    'p4Port'     => $this->utility->getP4Params('port'),
                ),
                'values'    => array(
                    'user'              => $this->utility->getP4Params('user'),
                    'password'          => $this->utility->getP4Params('password'),
                ),
                'valid'     => true,
                'errors'    => array()
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $form = new Setup_Form_Administrator($test['options']);
            $form->setCsrfProtection(false);
            $this->assertEquals(
                $test['valid'],
                $form->isValid($test['values']),
                "$label: expected status - ". print_r($form->getErrorMessages(), true)
            );
            $expectedErrors = $test['valid'] ? array() : $test['errors'];
            $this->assertEquals($expectedErrors, $form->getMessages(), "$label: expected errors");
        }
    }

    /**
     * Test license quota validation.
     */
    public function testIsP4LicenseQuotaSufficient()
    {
        $tests = array(
            // valid cases
            array(
                'license' => 'none',
                'users'   => 1,
                'valid'   => true
            ),
            array(
                'license' => 'Perforce Software 200 users (expires 2010/08/03)',
                'users'   => 1,
                'valid'   => true
            ),
            array(
                'license' => 'Perforce Software 200 users (expires 2010/08/03)',
                'users'   => 199,
                'valid'   => true
            ),
            array(
                'license' => 'none',
                'users'   => 2,
                'valid'   => true
            ),
            array(
                'license' => '',
                'users'   => 1,
                'valid'   => true
            ),

            // invalid cases
            array(
                'license' => 'Perforce Software 200 users (expires 2010/08/03)',
                'users'   => 200,
                'valid'   => false
            ),
            array(
                'license' => 'Perforce Software 200 users (expires 2010/08/03)',
                'users'   => 201,
                'valid'   => false
            ),
            array(
                'license' => '1 user',
                'users'   => 1,
                'valid'   => false
            ),
        );

        foreach ($tests as $test) {
            $form = new Setup_Form_Administrator;
            $this->assertEquals(
                $test['valid'],
                $form->isP4LicenseQuotaSufficient($test['license'], $test['users']),
                "license [{$test['license']}]; users [{$test['users']}] valid? [{$test['valid']}]"
            );
            $expectedErrors = ($test['valid'] == false)
                ? array("Can't create a new site on this server. All available licenses are in use.")
                : array();
            $this->assertEquals(
                $expectedErrors,
                $form->getElement('user')->getMessages(),
                'Expected error message'
            );
        }
    }

}
