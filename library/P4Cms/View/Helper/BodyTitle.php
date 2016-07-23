<?php
/**
 * Essentially the head title helper under a different name.
 * Falls back to the first component of the headTitle helper
 * if no body title is explicitly set.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_BodyTitle extends P4Cms_View_Helper_HeadTitle
{
    /**
     * Registry key for placeholder
     * @var string
     */
    protected $_regKey = 'P4Cms_View_Helper_BodyTitle';

    /**
     * Expose the head title helper under a different name.
     *
     * @param   string  $title              a title to set.
     * @param   string  $setType            whether to set, prepend or append the title.
     * @return  Zend_View_Helper_HeadTitle  provides fluent interface.
     */
    public function bodyTitle($title = null, $setType = null)
    {
        return parent::headTitle($title, $setType);
    }

    /**
     * Turn helper into string - overrides parent to strip the
     * title tag and fall back to the head title if no body title
     * has been set.
     *
     * @param  string|null  $indent     how much to indent the output
     * @param  string|null  $locale     the locale to inform translation
     * @return string       the body title.
     */
    public function toString($indent = null, $locale = null)
    {
        $output = $this->_stripTitleTag(parent::toString($indent, $locale));
        if (!$output) {
            $output = $this->_getLeadingHeadTitle();
            $output = ($this->_autoEscape) ? $this->_escape($output) : $output;
        }

        return $output;
    }

    /**
     * Get the first component of the head title.
     *
     * @return  string  the leading head title.
     */
    protected function _getLeadingHeadTitle()
    {
        $headTitle = $this->view->getHelper('headTitle');
        foreach ($headTitle as $title) {
            return $title;
        }

        return '';
    }
}
