<?php
/**
 * View helper that renders the managment toolbar.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Ui_View_Helper_Toolbar
    extends Menu_View_Helper_Menu
{

    protected static    $_toolbarDijit       = 'p4cms.ui.toolbar.Toolbar';
    protected static    $_pageDijit          = 'p4cms.ui.toolbar.MenuButton';
    protected static    $_dropDownDijit      = 'p4cms.ui.toolbar.DropDownMenuButton';
    protected $_displayContext;

    /**
     * This is the toolbar helper entry point. It has been extended to
     * check ACL for the 'access-toolbar' permission and to default the
     * menu container to the 'manage-toolbar' menu.
     *
     * @param   mixed   $container          optional - menu or container to operate on
     *                                      defaults to 'manage-toolbar' menu
     * @param   array   $options            options to apply when rendering this menu,
     *                                      includes optional displayContext string to
     *                                      filter items that should only appear in certain
     *                                      contexts eg. only show 'Back to Website' in
     *                                      'manage' context.
     * @return  Ui_View_Helper_Toolbar      fluent interface, returns self
     */
    public function toolbar($container = null, array $options = array())
    {
        if (!P4Cms_User::fetchActive()->isAllowed('site', 'access-toolbar')) {
            return null;
        }

        if (!$container) {
            $container = 'manage-toolbar';
        }

        return parent::menu($container, $options);
    }

    /**
     * Replaces parent htmlify so that we can use the pageDijit dojoType
     * for constructing the menus
     *
     * @param  Zend_Navigation_Page $page  page to generate HTML for
     * @return string                      HTML string for the given page
     */
    public function htmlify($page)
    {
        // only dijitize top level menu items
        if ($page->getParent() instanceof Zend_Navigation_Page) {
            return parent::htmlify($page);
        }

        // always provide a type class for styling purposes.
        $typeClass = 'type-' . strtolower(end(explode('_', get_class($page))));

        // grab any popups for rendering
        $popupMenu = '';
        if ($page->useDropDown && $page->hasPages()) {
            $dijitMenu = $this->view->navigation()->findHelper('dijitMenu');
            $popupMenu = $dijitMenu->renderMenu(
                $page,
                array(
                    'attribs' => array(
                        'leftClickToOpen'   => 'true',
                        'style'             => 'display:none;',
                        'wrapperClass'      => 'toolbar-popup-menu'
                    )
                )
            );

            // remove the pages so they won't be rendered
            $page->removePages();
        }

        // if we have a popup, use the dropdown menu, otherwise the normal menu
        $dojoType = $popupMenu ? static::$_dropDownDijit : static::$_pageDijit;

        // get attribs for element
        $attribs = array(
            'id'                    => $page->getId(),
            'dojoType'              => $dojoType,
            'iconClass'             => $page->getClass(),
            'menuAlign'             => $page->align,
            'class'                 => trim($typeClass),
            'displayContext'        => $page->context,
            'onClick'               => $page->onClick,
            'closeOnBlur'           => $page->closeOnBlur,
            'title'                 => $page->title,
            'onActivate'            => $page->onActivate,
            'onDeactivate'          => $page->onDeactivate,
            'onDrawerLoad'          => $page->onDrawerLoad,
        );

        // provide different elements for links vs other menu items
        $element = 'span';
        if ($page->getHref()) {
            $element            = 'a';
            $attribs['href']    = $this->view->escape($page->getHref());
            $attribs['target']  = $page->getTarget();
        }

        return '<' . $element . ' '
             . $this->_htmlAttribs($attribs) . '>'
             . $this->view->escape($page->getLabel())
             . '</' . $element . '>'
             . $popupMenu;
    }

    /**
     * Extends the parent helper renderer to use the toolbarDijit dojoType
     * for constructing the toolbar
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
        $this->_options = $options = $this->_normalizeOptions($options);

        // read collapsed/expanded state out of cookie.
        $request     = Zend_Controller_Front::getInstance()->getRequest();
        $isCollapsed = (bool) $request->getCookie('toolbar_isCollapsed');
        $options['dockConfig']['isCollapsed'] = $isCollapsed;

        $dijitAttribs = array(
            'dojoType'          => static::$_toolbarDijit,
            'displayContext'    => $options['displayContext'],
            'dockConfig'        => Zend_Json::encode($options['dockConfig']),
            'class'             => 'p4cms-ui manage-toolbar toolbar-disabled',
            'style'             => $isCollapsed ? "display: none;" : ""
        );

        return '<div ' . $this->_htmlAttribs($dijitAttribs) . '>'
            . parent::renderMenu($container, $options)
            . '</div>';
    }

    /**
     * Normalizes given render options
     *
     * @param  array $options  [optional] options to normalize
     * @return array           normalized options
     */
    protected function _normalizeOptions(array $options = array())
    {
        if (!isset($options['displayContext'])) {
            $options['displayContext'] = '';
        }
        if (!isset($options['dockConfig'])) {
            $options['dockConfig'] = null;
        }

        return parent::_normalizeOptions($options);
    }
}
