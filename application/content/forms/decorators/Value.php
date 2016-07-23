<?php
/**
 * Renders the value of an element wrapped in a span
 * with a 'value-node' class name to identify it in a
 * content element dijit.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_Decorator_Value extends Zend_Form_Decorator_Abstract
{
    protected $_htmlClassName       = "value-node";

    /**
     * Html tag for wrapping element with an array value.
     *
     * @var string
     */
    protected $_htmlArrayTag        = 'ul';

    /**
     * Html tag for wrapping array item for element with an array value.
     *
     * @var string
     */
    protected $_htmlArrayItemTag    = 'li';

    /**
     * Array with class names of elements that won't be decorated.
     *
     * @var array
     */
    protected $_skipElements = array (
        'Zend_Form_Element_Captcha'
    );

    /**
     * Return the value of the element for display
     * wrapped in a span tag w. a special class.
     *
     * @param   string  $content  The content to render.
     * @return  string
     */
    public function render($content)
    {
        $element        = $this->getElement();
        $value          = $element->getValue();
        $multiOptions   = $element->options;

        foreach ($this->_skipElements as $elementClass) {
            if ($element instanceof $elementClass) {
                return $content;
            }
        }

        // expand values from options for multi-option form elements
        // as value contains only option keys
        if (!empty($multiOptions)) {
            if (is_array($value)) {
                foreach ($value as $key => $item) {
                    $value[$key] = array_key_exists($item, $multiOptions)
                        ? $multiOptions[$item]
                        : $item;
                }
            } else {
                $value = array_key_exists($value, $multiOptions)
                    ? $multiOptions[$value]
                    : $value;
            }
        }

        if ($element->isArray() && is_array($value)) {
            $value = $this->_renderArray($value);
        } else {
            $value = "<span class='" . $this->_htmlClassName
                   . "'>" . $value . "</span>";
        }

        switch ($this->getPlacement()) {
            case self::APPEND:
                return $content . $value;
            case self::PREPEND:
                return $value . $content;
            default:
                return $value;
        }
    }

    /**
     * Return the value of the element with array value
     * for display wrapped in a span tag w. a special class.
     *
     * @param array $value Array to render
     * @return string
     */
    protected function _renderArray(array $value)
    {
        $html = '';
        foreach ($value as $key => $item) {
            if ($this->_htmlArrayItemTag) {
                $item = "<{$this->_htmlArrayItemTag}>$item</{$this->_htmlArrayItemTag}>";
            }
            $html .= $item;
        }
        if ($this->_htmlArrayTag) {
            $html = "<{$this->_htmlArrayTag} class='" . $this->_htmlClassName
                  . "'>$html</{$this->_htmlArrayTag}>";
        } else {
            $html = "<div class='" . $this->_htmlClassName
                  . "'>" . $html . "</div>";
        }

        return $html;
    }
}
