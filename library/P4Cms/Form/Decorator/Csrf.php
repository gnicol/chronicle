<?php
/**
 * Inserts the form's csrf token as a hidden input (provided
 * the form has csrf protection enabled).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_Decorator_Csrf extends Zend_Form_Decorator_Abstract
{
    /**
     * If the csrf protection has been enabled, add a hidden field to the form.
     *
     * @param   string   $content   previously rendered content string, may be empty
     * @return  string
     */
    public function render($content)
    {
        // only take effect for forms with csrf protection enabled.
        $form = $this->getElement();
        if (!$form instanceof P4Cms_Form || !$form->hasCsrfProtection()) {
            return $content;
        }

        // Cancel page caching as we are using a CSRF token.
        if (P4Cms_Cache::canCache('page')) {
            P4Cms_Cache::getCache('page')->cancel();
        }

        // generate hidden csrf token field.
        $html = '<input type="hidden" name="' . P4Cms_Form::CSRF_TOKEN_NAME
              . '" value="' .  P4Cms_Form::getCsrfToken() . '" />';

        return $this->getPlacement() == static::APPEND
            ? $content . $this->getSeparator() . $html
            : $html . $this->getSeparator() . $content;
    }
}
