<?php
/**
 * Extends Zend's errors decorator to skip any non-string errors
 * (e.g. nested errors). This fixes a problem where Zend's decorator
 * can choke on form errors because they may be two levels deep.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_Decorator_Errors extends Zend_Form_Decorator_Abstract
{
    /**
     * Render errors
     *
     * @param   string  $content  The content to include in errors.
     * @return  string  The formatted error output.
     */
    public function render($content)
    {
        $element = $this->getElement();
        $view    = $element->getView();
        if (null === $view) {
            return $content;
        }

        // remove errors that are not strings.
        $errors = $element->getMessages();
        foreach ($errors as $key => $error) {
            if (!is_string($error)) {
                unset($errors[$key]);
            }
        }

        if (empty($errors)) {
            return $content;
        }

        $separator = $this->getSeparator();
        $placement = $this->getPlacement();
        $errors    = $view->formErrors($errors, $this->getOptions());

        switch ($placement) {
            case self::APPEND:
                return $content . $separator . $errors;
            case self::PREPEND:
                return $errors . $separator . $content;
        }
    }
}
