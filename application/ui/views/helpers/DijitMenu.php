<?php
/**
 * Implementation of the Zend Navigation HelperAbstract that provides
 * output in p4cms.ui.Menu / dijit.MenuItem format.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Ui_View_Helper_DijitMenu
    extends Zend_View_Helper_Navigation_HelperAbstract
{
    protected static    $_menuDijit          = 'p4cms.ui.Menu';
    protected static    $_menuItemDijit      = 'dijit.MenuItem';
    protected static    $_popupMenuItemDijit = 'dijit.PopupMenuItem';
    protected static    $_menuSeparatorDijit = 'dijit.MenuSeparator';

    /**
     * View helper entry point:
     * Retrieves helper and optionally sets container to operate on
     *
     * @param  Zend_Navigation_Container $container  [optional] container to
     *                                               operate on
     * @return Zend_View_Helper_Navigation_Menu      fluent interface,
     *                                               returns self
     */
    public function menu(Zend_Navigation_Container $container = null)
    {
        if (null !== $container) {
            $this->setContainer($container);
        }

        return $this;
    }

    /**
     * Used to render pages with sub-pages. Will return the HTML representing the
     * passed page entry.
     *
     * @param  Zend_Navigation_Page $page  page to generate HTML for
     * @return string                      HTML string for the given page
     */
    protected function _htmlifySubMenu($page)
    {
        // render a PopupMenuItem and our label if children present
        return '<div dojotype="' . static::$_popupMenuItemDijit . '">'
             . '<span>' . $this->view->escape($page->getLabel()) . '</span>';
    }

    /**
     * Used to render seperator pages. Will return the HTML representing the
     * passed page entry.
     *
     * @param  Zend_Navigation_Page $page  page to generate HTML for
     * @return string                      HTML string for the given page
     */
    protected function _htmlifySeparator($page)
    {
        if (($onShow = $page->get('onShow'))) {
            $onShow = '<script type="dojo/connect" event="onShow" args="menuItem,menu">'
                     . $onShow
                     . '</script>';
        }

        return '<div dojotype="' . static::$_menuSeparatorDijit . '">' . $onShow . '</div>';
    }

    /**
     * Returns an HTML string containing an 'div' element for the given page.
     * If an 'onClick' property is present it will be rendered out and the
     * href will be ignored. If no onClick is set the href will be converted
     * to javascript and rendered.
     * The 'class' property will appear as 'iconClass'.
     * If specified, the 'onShow' property will be rendered.
     *
     * Overrides {@link Zend_View_Helper_Navigation_Abstract::htmlify()}.
     *
     * @param  Zend_Navigation_Page $page  page to generate HTML for
     * @return string                      HTML string for the given page
     */
    protected function _htmlifyPage($page)
    {
        // get label and title for translating
        $label = $page->getLabel();
        $title = $page->getTitle();

        // get attribs for element
        $attribs = array(
            'id'        => $page->getId(),
            'iconClass' => $page->getClass(),
            'disabled'  => $page->disabled
        );

        // if no onClick is set but we have an href, convert to js
        if (!($onClick = $page->get('onClick')) && $page->getHref()) {
            $onClick = 'window.location = ' . Zend_Json::encode($page->getHref());
        }

        if ($onClick) {
            $onClick = '<script type="dojo/connect" event="onClick">'
                     . $onClick
                     . '</script>';
        }

        if (($onShow = $page->get('onShow'))) {
            $onShow = '<script type="dojo/connect" event="onShow" args="menuItem,menu">'
                     . $onShow
                     . '</script>';
        }

        return '<div dojotype="' . static::$_menuItemDijit . '"'
             . $this->_htmlAttribs($attribs) . '>'
             . $onClick
             . $onShow
             . $this->view->escape($label)
             . '</div>';
    }

    /**
     * This function detect what type of page is passed and then calls through
     * to htmlify(Separator|Page|SubMenu).
     *
     * Overrides {@link Zend_View_Helper_Navigation_Abstract::htmlify()}.
     *
     * @param  Zend_Navigation_Page $page  page to generate HTML for
     * @return string                      HTML string for the given page
     */
    public function htmlify(Zend_Navigation_Page $page)
    {
        if ($page->hasChildren()) {
            return $this->_htmlifySubMenu($page);
        } else if ($page instanceof P4Cms_Navigation_Page_Separator) {
            return $this->_htmlifySeparator($page);
        } else {
            return $this->_htmlifyPage($page);
        }
    }

    /**
     * Normalizes given render options
     *
     * @param  array $options  [optional] options to normalize
     * @return array           normalized options
     */
    protected function _normalizeOptions(array $options = array())
    {
        if (isset($options['indent'])) {
            $options['indent'] = $this->_getWhitespace($options['indent']);
        } else {
            $options['indent'] = $this->getIndent();
        }

        if (!isset($options['attribs']) || !is_array($options['attribs'])) {
            $options['attribs'] = array();
        }

        if (array_key_exists('minDepth', $options)) {
            if (null !== $options['minDepth']) {
                $options['minDepth'] = (int) $options['minDepth'];
            }
        } else {
            $options['minDepth'] = $this->getMinDepth();
        }

        if ($options['minDepth'] < 0 || $options['minDepth'] === null) {
            $options['minDepth'] = 0;
        }

        if (array_key_exists('maxDepth', $options)) {
            if (null !== $options['maxDepth']) {
                $options['maxDepth'] = (int) $options['maxDepth'];
            }
        } else {
            $options['maxDepth'] = $this->getMaxDepth();
        }

        return $options;
    }

    /**
     * Renders a normal menu (called from {@link renderMenu()})
     *
     * @param  Zend_Navigation_Container $container   container to render
     * @param  string                    $indent      initial indentation
     * @param  array                     $attribs     attribs for the outer-most Menu div
     * @param  int|null                  $minDepth    minimum depth
     * @param  int|null                  $maxDepth    maximum depth
     * @return string
     */
    protected function _renderMenu(Zend_Navigation_Container $container,
                                   $indent,
                                   $attribs,
                                   $minDepth,
                                   $maxDepth)
    {
        $html = '';

        // pull the wrapper class out of the attributes
        // so we can add it to submenus as well
        $wrapperClass = isset($attribs['wrapperClass']) ? $attribs['wrapperClass'] : '';

        // find deepest active
        if (($found = $this->findActive($container, $minDepth, $maxDepth))) {
            $foundPage = $found['page'];
            $foundDepth = $found['depth'];
        } else {
            $foundPage = null;
        }

        // create iterator
        $iterator = new RecursiveIteratorIterator($container,
                            RecursiveIteratorIterator::SELF_FIRST);
        if (is_int($maxDepth)) {
            $iterator->setMaxDepth($maxDepth);
        }

        // iterate container
        $prevDepth = -1;
        foreach ($iterator as $page) {
            $depth = $iterator->getDepth();
            $isActive = $page->isActive(true);
            if ($depth < $minDepth || !$this->accept($page)) {
                // page is below minDepth or not accepted by acl/visibilty
                continue;
            }

            // make sure indentation is correct
            $depth   -= $minDepth;
            $myIndent = $indent . str_repeat('        ', $depth);

            if ($depth > $prevDepth) {
                // start new menu tag
                $attribs['wrapperClass'] = $wrapperClass . ' level-' . $depth;
                $html .= $myIndent . '<div dojoType="' .  static::$_menuDijit . '"'
                      . $this->_htmlAttribs($attribs) . '>' .  self::EOL;
                $attribs = array();
            } else if ($prevDepth > $depth) {
                // close menu tags until we're at current depth
                for ($i = $prevDepth; $i > $depth; $i--) {
                    $ind = $indent . str_repeat('        ', $i);
                    $html .= $ind . '</div>' . self::EOL;

                    // also close the popupMenuItem
                    $html .= $myIndent . '        ' . '</div>' . self::EOL;
                }
            }

            // render the actual item if no children
            $html .= $myIndent . '        ' . $this->htmlify($page) . self::EOL;

            // store as previous depth for next iteration
            $prevDepth = $depth;
        }

        if ($html) {
            // done iterating container; close open div tags
            for ($i = $prevDepth+1; $i > 0; $i--) {
                $myIndent = $indent . str_repeat('        ', $i-1);
                $html .= $myIndent . '</div>' . self::EOL;

                // also close the popupMenuItem if we are a sub-menu
                if ($i > 1) {
                    $html .= $myIndent . '</div>' . self::EOL;
                }
            }
            $html = rtrim($html, self::EOL);
        }

        return $html;
    }

    /**
     * Renders helper
     *
     * Renders a Menu dijit 'div' for the given $container with child MenuItem divs
     * for any pages present. If $container is not given, the container registered in the
     * helper will be used.
     *
     * Available $options:
     *  indent
     *  attribs - html attribs that will apply to the outer-most Menu div
     *  minDepth
     *  maxDepth
     *
     * @param  Zend_Navigation_Container $container  [optional] container to
     *                                               create menu from. Default
     *                                               is to use the container
     *                                               retrieved from
     *                                               {@link getContainer()}.
     * @param  array                     $options    [optional] options for
     *                                               controlling rendering
     * @return string                                rendered menu
     */
    public function renderMenu(Zend_Navigation_Container $container = null,
                               array $options = array())
    {
        if (null === $container) {
            $container = $this->getContainer();
        }

        $options = $this->_normalizeOptions($options);

        $html = $this->_renderMenu(
            $container,
            $options['indent'],
            $options['attribs'],
            $options['minDepth'],
            $options['maxDepth']
        );

        return $html;
    }

    /**
     * Renders menu
     *
     * Implements {@link Zend_View_Helper_Navigation_Helper::render()}.
     *
     * see renderMenu()
     *
     * @param  Zend_Navigation_Container $container  [optional] container to
     *                                               render. Default is to
     *                                               render the container
     *                                               registered in the helper.
     * @return string                                helper output
     */
    public function render(Zend_Navigation_Container $container = null)
    {
        return $this->renderMenu($container);
    }
}
