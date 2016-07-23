<?php
/**
 * Enhanced version of Zend's menu helper. 
 *  - Adds support for a 'click' property (onClick attribute) on pages
 *  - Adds ability to pass menu id and options directly
 *  - Adds ability to specify before and after label text for menu items
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_View_Helper_Menu extends Zend_View_Helper_Navigation_Menu
{
    protected   $_options       = array();

    /**
     * This is the menu helper entry point. It has been extended to
     * add support for passing a menu object or id (string) as well as
     * options.
     * 
     * If the menu exists, it will be fetched; otherwise, an empty 
     * container will be used.
     *
     * @param   mixed   $container      optional - menu or container to operate on
     * @param   array   $options        options to apply when rendering this menu
     * @return  Menu_View_Helper_Menu   fluent interface, returns self
     */
    public function menu($container = null, array $options = array())
    {
        if ($container && !$container instanceof Zend_Navigation_Container) {
            if (!$container instanceof P4Cms_Menu && P4Cms_Menu::exists($container)) {
                $container = P4Cms_Menu::fetch($container);
            }
            if ($container instanceof P4Cms_Menu) {
                // tag the page cache so it can be appropriately cleared later
                if (P4Cms_Cache::canCache('page')) {
                    P4Cms_Cache::getCache('page')
                        ->addTag('p4cms_menu')
                        ->addTag('p4cms_menu_' . bin2hex($container->getId()));
                }

                $container = $container->getExpandedContainer();
            } else {
                $container = new Zend_Navigation;
            }
        }
        
        if (null !== $container) {
            $this->setContainer($container);
        }

        // keep options around for render time.        
        $this->_options = $options;

        return $this;
    }
   
    /**
     * Renders menu - extended to pass instance options to renderMenu method.
     *
     * If a partial view is registered in the helper, the menu will be rendered
     * using the given partial script. If no partial is registered, the menu
     * will be rendered as an 'ul' element by the helper's internal method.
     *
     * @param  Zend_Navigation_Container $container  [optional] container to
     *                                               render. Default is to
     *                                               render the container
     *                                               registered in the helper.
     * @return string                                helper output
     */
    public function render(Zend_Navigation_Container $container = null)
    {
        if ($partial = $this->getPartial()) {
            return $this->renderPartial($container, $partial);
        } else {
            return $this->renderMenu($container, $this->_options);
        }
    }

    /**
     * Replaces parent htmlify so that we can produce customized markup
     * for page types that require it.
     *
     * Overrides {@link Zend_View_Helper_Navigation_Menu::htmlify()}.
     *
     * @param  Zend_Navigation_Page $page  page to generate HTML for
     * @return string                      HTML string for the given page
     */
    public function htmlify($page)
    {
        // get label and title for translating
        $label = $page->getLabel();
        $title = $page->getTitle();

        // translate label and title?
        if ($this->getUseTranslator() && $t = $this->getTranslator()) {
            if (is_string($label) && !empty($label)) {
                $label = $t->translate($label);
            }
            if (is_string($title) && !empty($title)) {
                $title = $t->translate($title);
            }
        }

        // always provide a type class for styling purposes.
        $typeClass = 'type-' . strtolower(end(explode('_', get_class($page))));

        // get attribs for element
        $attribs = array(
            'id'        => $page->getId(),
            'title'     => $title,
            'class'     => trim($page->getClass() . " " . $typeClass),
            'onclick'   => $page->get("onClick"),
        );

        // does page have a href?
        $beforeLabel = $afterLabel = '';
        if ($href = $page->getHref()) {
            $element = 'a';
            $attribs['href'] = $href;
            $attribs['target'] = $page->getTarget();
            $beforeLabel = array_key_exists('beforeLabel', $this->_options)
                ? $this->_options['beforeLabel']
                : '';
            $afterLabel = array_key_exists('afterLabel', $this->_options)
                ? $this->_options['afterLabel']
                : '';
        } else {
            $element = 'span';
        }

        return '<' . $element . $this->_htmlAttribs($attribs) . '>'
             . $beforeLabel . $this->view->escape($label) . $afterLabel
             . '</' . $element . '>';
    }

    /**
     * Extends the parent helper renderer to capture the options so that they
     * can be re-used by the htmlify method.
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
        
        return parent::renderMenu($container, $options);
    }
}
