<?php
/**
 * Test methods for the Form class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_FormTest extends TestCase
{
    /**
     * Test instantiating a form.
     */
    public function testInstantiation()
    {
        $form = new P4Cms_Form();
        $this->assertTrue($form instanceof P4Cms_Form, 'Expected class');
    }

    /**
     * Verify that the default decorators were loaded properly.
     */
    public function testDecorators()
    {
        $form = new P4Cms_Form();
        $decorators = $form->getDecorators();

        $this->assertEquals(
            array(
                'Zend_Form_Decorator_FormElements',
                'P4Cms_Form_Decorator_HtmlTag',
                'P4Cms_Form_Decorator_Errors',
                'P4Cms_Form_Decorator_Csrf',
                'Zend_Dojo_Form_Decorator_DijitForm'
            ),
            array_keys($form->getDecorators()),
            'Expected default decorators.'
        );
    }

    /**
     * Test csrf functionality.
     */
    public function testCsrf()
    {
        $form = new P4Cms_Form();
        $csrfToken = P4Cms_Form::getCsrfToken();
        $this->assertFalse($form->hasCsrfProtection(), 'Expect csrf protection to be off for anonymous.');
        $this->assertTrue(empty($csrfToken), 'Expect token to be empty.');

        $user = new P4Cms_User;
        $user->setId('tester');
        P4Cms_User::setActive($user);

        $csrfToken = P4Cms_Form::getCsrfToken();
        $this->assertTrue($form->hasCsrfProtection(), 'Expect csrf protection to be on by default.');
        $this->assertFalse(empty($csrfToken), 'Expect token to not be empty.');

        $form->setCsrfProtection(false);
        $this->assertFalse($form->hasCsrfProtection(), 'Expect CSRF protection to be off.');
    }

    /**
     * Test set default values on the form.
     */
    public function testSetDefaults()
    {
        $form = new P4Cms_Form;
        $form->addElement('text', 'foo');

        // add a sub-form with the element having the same name
        $subForm = new P4Cms_Form_SubForm;
        $subForm->addElement('text', 'bar');

        $form->addSubForm($subForm, 'sub');

        // set defaults to the form
        $defaults = array(
            'bar'   => 'bar_value'
        );
        $form->setDefaults($defaults);

        // ensure bar element was not populated
        $values = $form->getValues();
        $this->assertSame(
            null,
            $values['sub']['bar'],
            "Expected subForm 'bar' element has not been populated."
        );

        // set defaults with proper sub-form key
        $defaults = array(
            'sub'   => array(
                'bar'   => 'bar_value2'
            )
        );
        $form->setDefaults($defaults);

        // ensure bar element was populated in this case
        $values = $form->getValues();
        $this->assertSame(
            'bar_value2',
            $values['sub']['bar'],
            "Expected subForm 'bar' element has been populated."
        );
    }
}
