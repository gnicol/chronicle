<?php
/**
 * Test the ConfigureForm and its validation.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Site_Test_ConfigureFormTest extends ModuleTest
{
    /**
     * Test form instantiation.
     */
    public function testFormCreation()
    {
        $form = new Site_Form_Configure;
        $this->assertTrue($form instanceof Zend_Form);
        $this->assertTrue($form->getElement('title') instanceof Zend_Form_Element);
        $this->assertTrue($form->getElement('description') instanceof Zend_Form_Element);
        $this->assertTrue($form->getElement('save') instanceof Zend_Form_Element);
    }

    /**
     * Test form validation.
     */
    public function testFormValidation()
    {
        $host = $this->_getRequestHttpHost();

        $tests = array(
            // valid cases
            array(
                'label'     => __LINE__ . ': valid values',
                'values'    => array(
                    'title'     => 'example.com',
                    'robots'    => "User-agent: *\nDisallow:"
                ),
                'valid'     => true
            ),

            // invalid cases
            array(
                'label'     => __LINE__ . ': no values',
                'values'    => array(),
                'valid'     => false,
                'errors'    => array(
                    'title'     => array('isEmpty' => "Value is required and can't be empty"),
                )
            ),
            array(
                'label'     => __LINE__ . ': robots.txt invalid',
                'values'    => array(
                    'title'     => 'test',
                    'robots'    => 'bogus'
                ),
                'valid'     => false,
                'errors'    => array(
                    'robots'    => array(
                        'directiveBeforeUserAgent'
                            => 'The User-agent directive must precede any other per-record directives.'
                    ),
                )
            ),
        );

        foreach ($tests as $test) {
            $form = new Site_Form_Configure;
            $form->setCsrfProtection(false);

            $this->assertEquals(
                $test['valid'],
                $form->isValid($test['values']),
                $test['label'] .': expected status. Errors: '. print_r($form->getErrors(), true)
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
            array('label' => __LINE__, 'title' => '',    'valid' => false, 'errors' => array('isEmpty')),
            array('label' => __LINE__, 'title' => ' ',   'valid' => false, 'errors' => array('isEmpty')),
        );

        foreach ($tests as $test) {
            $form = new Site_Form_Configure;
            $this->assertEquals(
                $test['valid'],
                $form->getElement('title')->isValid($test['title'])
            );
            $expectedErrors = $test['valid']
                ? array()
                : $test['errors'];
            $this->assertEquals(
                $expectedErrors,
                $form->getElement('title')->getErrors(),
                'Expected errors for title "' . $test['title'] .'"'
            );
        }
    }

    /**
     * A helper method to determine the request's hostname
     *
     * @return  string  The request's current hostname
     */
    protected function _getRequestHttpHost()
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        if (!$request instanceof Zend_Controller_Request_Http) {
            return false;
        }

        $host = $request->getHttpHost();
        if (preg_match('#:\d+$#', $host, $result) === 1) {
            $host = substr($host, 0, -strlen($result[0]));
        }
        return $host;
    }
}