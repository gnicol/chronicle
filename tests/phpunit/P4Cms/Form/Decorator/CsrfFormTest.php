<?php
/**
 * Test methods for the P4Cms Form Decorator
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_Decorator_CsrfFormTest extends TestCase
{
    /**
     * Test form render.
     */
    public function testAnonymousRender()
    {
        $form = new P4Cms_Form();
        $content = $form->render();
        $this->assertEquals(
            '<dl class="zend_form_dojo">' . PHP_EOL . '</dl>',
            $content
        );
    }

    /**
     * Test form render.
     */
    public function testRender()
    {
        // setup an active user to simulate someone being logged in
        $user = new P4Cms_User;
        $user->setId('foo');
        P4Cms_User::setActive($user);

        $form = new P4Cms_Form();
        $content = $form->render();

        // verify csrf token is present by default
        $this->assertEquals(
            '<input type="hidden" name="'
            . P4Cms_Form::CSRF_TOKEN_NAME . '" value="' . P4Cms_Form::getCsrfToken()
            . '" />' . PHP_EOL . '<dl class="zend_form_dojo">' . PHP_EOL
            . '</dl>',
            $content
        );

        $form->setCsrfProtection(false);
        $content = $form->render();
        $this->assertEquals(
            '<dl class="zend_form_dojo">' . PHP_EOL . '</dl>',
            $content
        );
    }
}
