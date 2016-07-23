<?php
/**
 * Extends Zend_Form_Decorator_HtmlTag to exclude tagging of
 * hidden form elements.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_Decorator_HtmlTag extends Zend_Form_Decorator_HtmlTag
{
    /**
     * Extend render to exclude hidden and hash elements.
     *
     * @param   string  $content  The content to render.
     * @return  string
     */
    public function render($content)
    {
        if ($this->getElement() instanceof Zend_Form_Element_Hidden
            || $this->getElement() instanceof Zend_Form_Element_Hash
        ) {
            return $content;
        }
        return parent::render($content);
    }
}
