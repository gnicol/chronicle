<?php
/**
 * Extends the head title helper to insert a separator between
 * the prefix/postfix and the title.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_HeadTitle extends Zend_View_Helper_HeadTitle
{
    /**
     * Registry key for placeholder
     * @var string
     */
    protected $_regKey = 'P4Cms_View_Helper_HeadTitle';

    /**
     * Turn helper into string - overrides parent to put the separator
     * between title parts and prefix/postfix.
     *
     * @param  string|null  $indent     how much to indent the output
     * @param  string|null  $locale     the locale to inform translation
     * @return string       the head title in a title tag.
     */
    public function toString($indent = null, $locale = null)
    {
        $indent = (null !== $indent)
                ? $this->getWhitespace($indent)
                : $this->getIndent();

        $items = array();

        if ($this->_translate && $translator = $this->getTranslator()) {
            foreach ($this as $item) {
                $items[] = $translator->translate($item, $locale);
            }
        } else {
            foreach ($this as $item) {
                $items[] = $item;
            }
        }

        $separator = $this->getSeparator();
        $output = '';
        if (($prefix = $this->getPrefix())) {
            array_unshift($items, $prefix);
        }
        if (($postfix = $this->getPostfix())) {
            array_push($items, $postfix);
        }
        $output .= implode($separator, $items);

        $output = ($this->_autoEscape) ? $this->_escape($output) : $output;

        return $indent . '<title>' . $output . '</title>';
    }

    /**
     * Strip title tag from output.
     *
     * @param   string  $output     the output to strip title tags from
     */
    protected function _stripTitleTag($output)
    {
        return preg_replace('/<\/?title>/i', '', $output);
    }
}
