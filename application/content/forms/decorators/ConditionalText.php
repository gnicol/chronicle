<?php
/**
 * Simple decorator to add (prepend or append) a text. Text is added only if the decorated
 * content (stripped by tags and whitespace) is not empty.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_Decorator_ConditionalText extends Zend_Form_Decorator_Abstract
{
    protected $_placement = 'PREPEND';

    /**
     * Return the value of the content added (prepend by default) by the text given in options.
     * Text is not added if content stripped by tags and whitespaces is empty.
     *
     * @param   string  $content  the content to decorate
     * @return  string  the decorated content
     */
    public function render($content)
    {
        if (!trim(strip_tags($content))) {
            return $content;
        }

        switch ($this->getPlacement()) {
            case self::APPEND:
                return $content . $this->getOption('text');
            case self::PREPEND:
                return $this->getOption('text') . $content;
            default:
                return $content;
        }
    }
}