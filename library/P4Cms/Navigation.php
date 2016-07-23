<?php
/**
 * Extend Zend_Navigation to return pages in sorted order.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Navigation extends Zend_Navigation
{
    /**
     * Extend parent to return pages in sorted order.
     *
     * @return  array   Zend_Navigation_Page instances
     */
    public function getPages()
    {
        $this->_sort();
        $pages = array();
        foreach ($this->_index as $hash => $order) {
            $pages[] = $this->_pages[$hash];
        }
        return $pages;
    }

    /**
     * Add a page to the raw navigation container in this menu.
     *
     * @param   array|Zend_Navigation_Page|Zend_Config  $page   a page to add to the menu.
     * @return  P4Cms_Navigation    provides fluent interface.
     */
    public function addPage($page)
    {
        parent::addPage(static::inferPageType($page));

        return $this;
    }

    /**
     * Adds several pages at once
     * Extends parent to accept a navigation container.
     *
     * @param  array|Zend_Config|Zend_Navigation_Container  $pages   pages to add
     * @return Zend_Navigation_Container  fluent interface, returns self
     * @throws Zend_Navigation_Exception  if $pages is not array or Zend_Config
     */
    public function addPages($pages)
    {
        if ($pages instanceof Zend_Navigation_Container) {
            $pages = iterator_to_array($pages);
        }

        return parent::addPages($pages);
    }

    /**
     * A function to infer types of navigation items so that specifying
     * the type is optional - operates recursively (on sub-pages).
     *
     * @param   mixed   $page   a page definition array to set the type on.
     *                          note: non-array inputs are returned as-is.
     * @return  mixed   the given page definition updated with types set.
     */
    public static function inferPageType($page)
    {
        if (!is_array($page)) {
            return $page;
        }

        // if page has an invalid type, clear it.
        if (isset($page['type']) && !class_exists($page['type'])) {
            unset($page['type']);
        }

        // if page doesn't have a valid type, detect one.
        // note: we record that the type was inferred for future
        // reference (e.g. so we know if it's ok to re-assess the type)
        if (!isset($page['type'])) {
            $page['type']         = static::_detectPageType($page);
            $page['typeInferred'] = true;
        }

        // process sub pages if any
        if (array_key_exists('pages', $page) && is_array($page['pages'])) {
            foreach ($page['pages'] as $key => $subPage) {
                $page['pages'][$key] = static::inferPageType($subPage);
            }
        }

        return $page;
    }

    /**
     * Utility method to assist with expanding macros in page properties.
     * Only expands macros in given value if page has expandMacros = true
     *
     * @param   string                  $value  the value to expand macros in
     * @param   Zend_Navigation_Page    $page   the page for context
     * @return  string                  the value with macros expanded (if enabled)
     */
    public static function expandMacros($value, Zend_Navigation_Page $page)
    {
        if (!$page->get('expandMacros')) {
            return $value;
        }

        $macro = new P4Cms_Filter_Macro;
        $macro->setContext(array('page' => $page));

        return $macro->filter($value);
    }

    /**
     * Determine the appropriate page type for a given page definition.
     *
     * @param   array   $page   array definition of a page.
     * @return  string  the detected page type.
     */
    protected static function _detectPageType(array $page)
    {
        // if the entry specifies a 'handler', it is a dynamic menu item.
        if (isset($page['handler'])) {
            return 'P4Cms_Navigation_Page_Dynamic';
        }

        // a contentId param intidactes content page type.
        if (isset($page['contentId'])) {
            return 'P4Cms_Navigation_Page_Content';
        }

        // any mvc parameters indicate mvc page type.
        if (isset($page['action'])
            || isset($page['controller'])
            || isset($page['module'])
            || isset($page['route'])
        ) {
            return 'P4Cms_Navigation_Page_Mvc';
        }

        // presence of uri param indicates uri type.
        if (isset($page['uri'])) {
            return 'P4Cms_Navigation_Page_Uri';
        }

        // a label of only -'s, it is a seperator.
        if (isset($page['label']) && preg_match('/^-+$/', $page['label'])) {
            return 'P4Cms_Navigation_Page_Separator';
        }

        // fallback to heading type.
        return 'P4Cms_Navigation_Page_Heading';
    }
}
