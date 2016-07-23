<?php
/**
 * Test the PerforceForm and its validation.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Setup_Test_StorageFormTest extends ModuleTest
{
    /**
     * Test form instantiation.
     */
    public function testFormCreation()
    {
        $form = new Setup_Form_Storage;
        $this->assertTrue($form instanceof Zend_Form);
        $this->assertTrue($form->getElement('serverType')   instanceof Zend_Form_Element, 'serverType element');
        $this->assertTrue($form->getElement('port')         instanceof Zend_Form_Element, 'port element');
        $this->assertTrue($form->getElement('continue')     instanceof Zend_Form_Element, 'continue element');
        $this->assertTrue($form->getElement('goback')       instanceof Zend_Form_Element, 'goback element');
    }

    /**
     * Test port validation
     */
    public function testPortValidation()
    {
        $tests = array(
            // valid cases
            array('port' => '1666',        'valid' => true),

            // invalid cases
            array('port' => 'alskdfj',     'valid' => false, 'error' => array('invalidPort')),
            array('port' => '',            'valid' => false, 'error' => array('isEmpty')),
            array('port' => 'some-domain', 'valid' => false, 'error' => array('invalidPort')),
            array('port' => ':1666',       'valid' => false, 'error' => array('invalidHost'))
        );

        foreach ($tests as $test) {
            $form = new Setup_Form_Storage;
            $this->assertEquals(
                $test['valid'],
                $form->getElement('port')->isValid($test['port'])
            );
            $expectedErrors = ($test['valid'] == false)
                ? $test['error']
                : array();
            $this->assertEquals(
                $expectedErrors,
                $form->getElement('port')->getErrors(),
                "Expected error message for port='" . $test['port'] ."'"
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
                'label'     => __LINE__ . ': no values',
                'values'    => array(),
                'valid'     => false,
                'errors'    => array(
                    'serverType'    => array(
                        'isEmpty'   => "Value is required and can't be empty"
                    ),
                    'port'          => array(
                        'isEmpty'   => "Value is required and can't be empty"
                    )
                )
            ),
            array(
                'label'     => __LINE__ . ': properly formed values, but non-existent server',
                'values'    => array(
                    'serverType'    => 'existing',
                    'port'          => 'alasdjkf:1666',
                ),
                'valid'     => false,
                'errors'    => array(
                    'port'  => array("Unable to connect to server on 'alasdjkf:1666'.")
                )
            ),
            array(
                'label'     => __LINE__ . ': all valid',
                'values'    => array(
                    'serverType'    => 'existing',
                    'port'          => $this->utility->getP4Params('port'),
                ),
                'valid'     => true,
                'errors'    => array()
            ),
            array(
                'label'     => __LINE__ . ': new server w. p4d installed',
                'values'    => array(
                    'serverType'    => 'new'
                ),
                'valid'     => true,
                'hideP4d'   => false,
                'errors'    => array()
            ),
            array(
                'label'     => __LINE__ . ': new server w.out p4d installed',
                'values'    => array(
                    'serverType'    => 'new'
                ),
                'valid'     => false,
                'hideP4d'   => true,
                'errors'    => array(
                    'serverType' => array('Cannot create a local depot. A valid Perforce Server is not installed.')
                )
            ),
        );

        foreach ($tests as $test) {
            // hide p4d.
            if (isset($test['hideP4d']) && $test['hideP4d']) {
                $path = getenv('PATH');
                putenv('PATH=');
            }

            $label = $test['label'];
            $form = new Setup_Form_Storage;
            $form->setCsrfProtection(false);
            $this->assertEquals($test['valid'], $form->isValid($test['values']), "$label: expected status");
            $expectedErrors = $test['valid'] ? array() : $test['errors'];
            $this->assertEquals($expectedErrors, $form->getMessages(), "$label: expected errors");

            // restore p4d.
            if (isset($test['hideP4d']) && $test['hideP4d']) {
                putenv('PATH=' . $path);
            }
        }
    }

    /**
     * Test server version validation.
     */
    public function testIsP4ServerVersionValid()
    {
        // please ensure you match the currentMajorVersion to MIN_P4_VERSION's YYYY.N in the IndexController
        $priorMajorVersion   = '2011.1';
        $currentMajorVersion = '2012.1';
        $nextMajorVersion    = '2012.2';

        $currentMajorVersionYear = substr($currentMajorVersion, 0, 4);

        $tests = array(
            // basic valid case
            array(
                'server' => "P4D/LINUX26X86_64/{$currentMajorVersion}/12345 (12345)",
                'valid'  => true
            ),
            // due to the way version_compare() works, the P in Prep is considered greater than just a #
            array(
                'server' => "P4D/LINUX26X86_64/{$currentMajorVersion}.PREP-TEST_ONLY/12345 (12345)",
                'valid'  => true
            ),
            // all types of next major versions should be valid
            array(
                'server' => "P4D/LINUX26X86_64/{$nextMajorVersion}.MAIN-TEST_ONLY/12345 (12345)",
                'valid'  => true
            ),
            array(
                'server' => "P4D/LINUX26X86_64/${nextMajorVersion}.PREP-TEST_ONLY/12345 (12345)",
                'valid'  => true
            ),
            array(
                'server' => "P4D/LINUX26X86_64/${nextMajorVersion}.BETA/12345 (12345)",
                'valid'  => true
            ),
            array(
                'server' => "P4D/LINUX26X86_64/${nextMajorVersion}/12345 (12345)",
                'valid'  => true
            ),

            // if the MIN_P4_VERSION is a GA version (i.e.: no trailing .z.z.z), mark these following
            // three cases as invalid; if MIN_P4_VERSION is non-GA, mark them as valid
            array(
                'server' => "P4D/LINUX26X86_64/${currentMajorVersion}.BETA/12345 (12345)",
                'valid'  => false
            ),
            array(
                'server' => "P4D/LINUX26X86_64/${currentMajorVersion}.MAIN-TEST_ONLY/219690 (2009/10/19)",
                'valid'  => false
            ),

            // invalid cases no matter what MIN_P4_VERSION is
            array(
                'server' => "P4D/LINUX26X86_64/{$currentMajorVersionYear}.0/12345 (12345)",
                'valid'  => false
            ),
            array(
                'server' => "P4D/LINUX26X86_64/{$currentMajorVersionYear}/12345 (12345)",
                'valid'  => false
            ),
            array(
                'server' => "P4D/LINUX26X86_64/{$priorMajorVersion}/12345 (12345)",
                'valid'  => false
            ),
            array(
                'server' => "P4D/LINUX26X86_64/{$priorMajorVersion}.BETA/12345 (12345)",
                'valid'  => false
            ),
            array(
                'server' => "P4D/LINUX26X86_64/${priorMajorVersion}.PREP/12345 (12345)",
                'valid'  => false
            ),
            array(
                'server' => "P4D/LINUX26X86_64/${priorMajorVersion}.MAIN/219690 (2009/10/19)",
                'valid'  => false
            ),
            array(
                'server' => "P4D/LINUX26X86_64/2010.1/12345 (12345)",
                'valid'  => false
            ),
            array(
                'server' => "${currentMajorVersion}",
                'valid'  => false
            ),
            array(
                'server' => "${nextMajorVersion}",
                'valid'  => false
            ),
            array(
                'server' => '',
                'valid'  => false
            ),
        );

        foreach ($tests as $test) {
            $form = new Setup_Form_Storage;
            $this->assertEquals(
                $test['valid'],
                $form->isP4ServerVersionValid($test['server']),
                "Unexpected validity for ". $test['server']
            );
        }
    }

    /**
     * Ensure server type option reflects availability of 'p4d'.
     */
    public function testNewServerOption()
    {
        // ensure 'new' server is default.
        $form = new Setup_Form_Storage;
        $this->assertSame($form->getValue('serverType'), 'new');

        // now 'hide' p4d.
        $path = getenv('PATH');
        putenv('PATH=');

        $form = new Setup_Form_Storage;
        $this->assertSame($form->getValue('serverType'), 'existing');
        $this->assertSame(
            $form->getElement('serverType')->getAttrib('disable'),
            array('new')
        );
        putenv('PATH=' . $path);
    }
}
