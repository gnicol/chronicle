<?php
/**
 * Test the SiteForm and its validation.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Setup_Test_SiteFormTest extends ModuleTest
{
    /**
     * Test form instantiation.
     */
    public function testFormCreation()
    {
        $form = new Setup_Form_Site;
        $this->assertTrue($form instanceof Zend_Form);
        $this->assertTrue($form->getElement('title') instanceof Zend_Form_Element);
        $this->assertTrue($form->getElement('urls') instanceof Zend_Form_Element);
        $this->assertTrue($form->getElement('description') instanceof Zend_Form_Element);
        $this->assertTrue($form->getElement('create') instanceof Zend_Form_Element);
    }

    /**
     * Test form validation.
     */
    public function testFormValidation()
    {
        $urls  = P4Cms_Site::fetchActive()->getConfig()->getUrls();
        $tests = array(
            // valid cases
            array(
                'label'     => __LINE__ . ': valid values',
                'values'    => array(
                    'title' => 'example.com',
                    'urls'  => 'example.com',
                ),
                'valid'     => true
            ),

            // invalid cases
            array(
                'label'     => __LINE__ . ': no values',
                'values'    => array(),
                'valid'     => false,
                'errors'    => array(
                    'title' => array('isEmpty' => "Value is required and can't be empty"),
                    'urls'  => array('isEmpty' => "Value is required and can't be empty"),
                )
            ),
            array(
                'label'     => __LINE__ . ': site already exists',
                'values'    => array(
                    'title' => 'test',
                    'urls'  => 'example.com',
                ),
                'valid'     => false,
                'errors'    => array(
                    'title' => array(
                        "The site title you provided appears to be taken. Please choose a different title."
                    ),
                )
            ),
            array(
                'label'     => __LINE__ . ': site address already exists',
                'values'    => array(
                    'title' => 'example.com',
                    'urls'  => $urls[0],
                ),
                'valid'     => false,
                'errors'    => array(
                    'urls' => array("The site address '$urls[0]' you provided appears to be taken. "
                         . "Please choose a different address."),
                )
            ),
        );

        foreach ($tests as $test) {
            $form = new Setup_Form_Site;
            $form->setConnection($this->p4);
            $form->setCsrfProtection(false);

            $this->assertEquals(
                $test['valid'],
                $form->isValid($test['values']),
                $test['label'] .': expected status'
            );

            $expectedErrors = $test['valid']
                ? array()
                : $test['errors'];
            $this->assertEquals(
                $expectedErrors,
                $form->getMessages(),
                $test['label'] . ': expected error messages'
            );
        }

        // verify that site cannot be created if there is a depot with name
        // matching the site title/id
        $depot = new P4_Depot($this->p4);
        $depot->setValues(
            array(
                'Depot'     => P4Cms_Site::SITE_PREFIX . 'mysite',
                'Type'      => 'local',
                'Map'       => 'mysite/...'
            )
        )->save();

        $form = new Setup_Form_Site;
        $form->setConnection($this->p4);
        $form->setCsrfProtection(false);

        $test = array(
            'title' => 'mysite',
            'urls'  => 'mysite'
        );

        $this->assertFalse(
            $form->isValid($test),
            "Expected form is invalid if there is an existing depot with the site title."
        );
    }

    /**
     * Test title validation.
     */
    public function testTitleValidation()
    {
        $tests = array(
            // valid cases
            array('label' => __LINE__, 'title' => 'example.com', 'valid' => true),
            array('label' => __LINE__, 'title' => 'foobar',      'valid' => true),
            array('label' => __LINE__, 'title' => '..',          'valid' => true),
            array('label' => __LINE__, 'title' => '"',           'valid' => true),
            array('label' => __LINE__, 'title' => "'",           'valid' => true),
            array('label' => __LINE__, 'title' => 'a b',         'valid' => true),
            array('label' => __LINE__, 'title' => '/',           'valid' => true),
            array('label' => __LINE__, 'title' => '\\',          'valid' => true),
            array('label' => __LINE__, 'title' => '@',           'valid' => true),
            array('label' => __LINE__, 'title' => '#',           'valid' => true),
            array('label' => __LINE__, 'title' => '*',           'valid' => true),
            array('label' => __LINE__, 'title' => '...',         'valid' => true),
            array('label' => __LINE__, 'title' => '%%1',         'valid' => true),

            // invalid cases
            array('label' => __LINE__, 'title' => '',  'valid' => false, 'errors' => array('isEmpty')),
            array('label' => __LINE__, 'title' => ' ', 'valid' => false, 'errors' => array('isEmpty')),
        );

        foreach ($tests as $test) {
            $form = new Setup_Form_Site;
            $this->assertEquals(
                $test['valid'],
                $form->getElement('title')->isValid($test['title']),
                'Unexpected status for '. $test['label'] .': "'. $test['title'] .'"'
            );

            $expectedErrors = $test['valid']
                ? array()
                : $test['errors'];
            $this->assertEquals(
                $expectedErrors,
                $form->getElement('title')->getErrors(),
                'Expected errors for '. $test['label'] .': "'. $test['title'] .'"'
            );
        }
    }

    /**
     * Test URL validation.
     */
    public function testUrlValidation()
    {
        $tests = array(
            // valid cases
            array('urls' => 'example.com',                  'valid' => true),
            array('urls' => "example.com\nwww.example.com", 'valid' => true),
            array('urls' => ' example.com',                 'valid' => true),

            // invalid cases
            array('urls' => '', 'valid' => false, 'errors' => array('isEmpty')),
        );

        foreach ($tests as $test) {
            $form = new Setup_Form_Site;
            $this->assertEquals(
                $test['valid'],
                $form->getElement('urls')->isValid($test['urls'])
            );
            $expectedErrors = $test['valid']
                ? array()
                : $test['errors'];
            $this->assertEquals(
                $expectedErrors,
                $form->getElement('urls')->getErrors(),
                'Expected errors for urls "' . $test['urls'] .'"'
            );
        }
    }

    /**
     * Test default value for the site title field.
     */
    public function testTitleFieldDefault()
    {
        $tests = array(
            array(
                'label'     => __LINE__ . ': non-http request',
                'type'      => 'nonhttp',
                'uri'       => '',
                'host'      => 'example.com',
                'expected'  => false
            ),
            array(
                'label'     => __LINE__ . ': http request',
                'type'      => 'http',
                'uri'       => 'http://example.com/',
                'host'      => 'example.com',
                'expected'  => 'example.com'
            ),
            array(
                'label'     => __LINE__ . ': http+port request',
                'type'      => 'http',
                'uri'       => 'http://example.com:8080/',
                'host'      => 'example.com:8080',
                'expected'  => 'example.com'
            ),
        );

        // prep for test execution
        $front = Zend_Controller_Front::getInstance();
        $originalRequest = $front->getRequest();
        $originalHost = $_SERVER['HTTP_HOST'];

        foreach ($tests as $test) {
            // setup a request object for the front controller
            if ($test['type'] == 'http') {
                $request = new Zend_Controller_Request_Http($test['uri']);
            } else {
                $request = new Zend_Controller_Request_Simple;
            }
            $front->setRequest($request);
            // override the notion of the current host.
            $_SERVER['HTTP_HOST'] = $test['host'];

            $form = new Setup_Form_Site;
            $this->assertEquals(
                $test['expected'],
                $form->getValue('title'),
                $test['label'] .":Expected hostname given '". $test['host'] ."'"
            );
        }

        // cleanup after test execution
        $_SERVER['HTTP_HOST'] = $originalHost;
        if ($originalRequest) {
            $front->setRequest($originalRequest);
        }
    }

    /**
     * Test _getDefaultUrls helper method.
     */
    public function testUrlFieldDefaults()
    {
        $tests = array(
            array(
                'label'     => __LINE__ . ': non-http request',
                'type'      => 'nonhttp',
                'uri'       => '',
                'host'      => 'example.com',
                'expected'  => false
            ),
            array(
                'label'     => __LINE__ . ': http request',
                'type'      => 'http',
                'uri'       => 'http://example.com/',
                'host'      => 'example.com',
                'expected'  => "example.com\nwww.example.com"
            ),
            array(
                'label'     => __LINE__ . ': http www request',
                'type'      => 'http',
                'uri'       => 'http://www.example.com/',
                'host'      => 'www.example.com',
                'expected'  => "www.example.com\nexample.com"
            ),
            array(
                'label'     => __LINE__ . ': http+port request',
                'type'      => 'http',
                'uri'       => 'http://example.com:8080/',
                'host'      => 'example.com:8080',
                'expected'  => "example.com:8080\nwww.example.com:8080"
            ),
        );

        // prep for test execution
        $front = Zend_Controller_Front::getInstance();
        $originalRequest = $front->getRequest();
        $originalHost = $_SERVER['HTTP_HOST'];

        foreach ($tests as $test) {
            // setup a request object for the front controller
            if ($test['type'] == 'http') {
                $request = new Zend_Controller_Request_Http($test['uri']);
            } else {
                $request = new Zend_Controller_Request_Simple;
            }
            $front->setRequest($request);
            // override the notion of the current host.
            $_SERVER['HTTP_HOST'] = $test['host'];

            $form = new Setup_Form_Site;
            $this->assertEquals(
                $test['expected'],
                $form->getValue('urls'),
                $test['label'] .": Expected sites given '". $test['host'] ."'"
            );
        }

        // cleanup after test execution
        $_SERVER['HTTP_HOST'] = $originalHost;
        if ($originalRequest) {
            $front->setRequest($originalRequest);
        }
    }
}
