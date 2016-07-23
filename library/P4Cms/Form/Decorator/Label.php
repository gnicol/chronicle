<?php
/**
 * Extends Zend_Form_Decorator_Label to exclude labelling of
 * hidden form elements.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_Decorator_Label extends Zend_Form_Decorator_Label
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

        //if escaping label, store it, escape label, turn it off
        $shouldEscape = $this->getOption('escape');

        if ($this->getOption('helpUri') && $shouldEscape !== false) {
            // disables escaping so we can add html
            // if escaping has not been turned off, escape the label first
            $originalLabel = parent::getLabel();
            $label = $this->getElement()->getView()->escape($originalLabel);

            $this->setOption('escape', false);
            $this->getElement()->setLabel($label);
        }
        $content = parent::render($content);

        //after parent:: render, restore to previous
        if ($this->getOption('helpUri') && $shouldEscape) {
            $this->setOption('escape', true);
            $this->getElement()->setLabel($originalLabel);
        }
        return $content;
    }

    /**
     * Renders a label and optionally include a link to the appropriate help documentation.
     *
     * @return  string  The rendered label.
     */
    public function getLabel()
    {
        $label = parent::getLabel();
        if ($this->getOption('helpUri')) {
            $helpUri = (string) $this->getOption('helpUri');
            $this->removeOption('helpUri');

            $label = $label . $this->getSeparator()
                   . '<a class="helpLink" href="' . $helpUri
                   .'" target="_blank">(?)</a>';
        }
        return $label;
    }

    /**
     * Get class with which to define label
     *
     * Appends 'disabled' to the class returned from the Zend decorator for
     * disabled elements
     *
     * @return string
     */
    public function getClass()
    {
        $class   = parent::getClass();
        $element = $this->getElement();
        if ($element->getAttrib('disabled') || $element->getAttrib('disable')) {
            $class .= " disabled";
        }
        if ($element->getAttrib('readOnly') || $element->getAttrib('readonly')) {
            $class .= " readonly";
        }

        return $class;
    }
}
