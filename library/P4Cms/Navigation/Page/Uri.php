<?php
/**
 * Extends Zend_Navigation_Page_Uri to add macro support.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Navigation_Page_Uri extends Zend_Navigation_Page_Uri
{
    /**
     * Returns page label with support for macros.
     *
     * @return  string  page label or null
     */
    public function getLabel()
    {
        return P4Cms_Navigation::expandMacros(parent::getLabel(), $this);
    }

    /**
     * Returns page title with support for macros.
     *
     * @return  string|null     page title or null
     */
    public function getTitle()
    {
        return P4Cms_Navigation::expandMacros(parent::getTitle(), $this);
    }

    /**
     * Returns href for this page with support for macros
     *
     * @return  string  the href
     */
    public function getHref()
    {
        return P4Cms_Navigation::expandMacros(parent::getHref(), $this);
    }

    /**
     * Returns an array representation of the page
     *
     * @return  array   associative array containing all page properties
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            array(
                'label' => $this->_label,
                'title' => $this->_title
            )
        );
    }
}