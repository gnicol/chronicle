<?php
/**
 * Extends Zend_Form_Decorator_DtDdWrapper to eliminate
 * the <dt> tag (it is really just a <dd> wrapper now).
 *
 * Also, identifies sub-forms and display groups with a
 * 'fieldset' css class so that they can be styled
 * separately.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_Decorator_DtDdWrapper extends Zend_Form_Decorator_DtDdWrapper
{
    /**
     * Extend render to eliminate spurious dt tag.
     *
     * @param   string  $content  the content to render.
     * @return  string
     */
    public function render($content)
    {
        $elementName = $this->getElement()->getName();
        $isFieldset  = ($this->getElement() instanceof Zend_Form_DisplayGroup ||
                        $this->getElement() instanceof Zend_Form_SubForm);

        $html  = '<dd id="' . $elementName . '-element" ';
        $html .= ($isFieldset) ? ' class="display-group" ' : '';
        $html .= '>' . $content . '</dd>';

        return $html;
    }
}
