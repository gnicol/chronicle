<?php
/**
 * Simply renders the value of an element.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_Decorator_Value extends Zend_Form_Decorator_Abstract
{
    /**
     * Return the value of the element for display.
     * Default behavior is to replace existing content, but
     * placement can be set to append or prepend instead.
     *
     * @param   string  $content  The content to render.
     * @return  string
     */
    public function render($content)
    {
        switch ($this->getPlacement()) {
            case self::APPEND:
                return $content . $this->getElement()->getValue();
            case self::PREPEND:
                return $this->getElement()->getValue() . $content;
            default:
                return $this->getElement()->getValue();
        }
    }
}
