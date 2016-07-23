<?php
/**
 * Extends Zend's breadcrumbs helper to support passing a menu object or id.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_View_Helper_Breadcrumbs extends Zend_View_Helper_Navigation_Breadcrumbs
{
    /**
     * This is the breadcrumbs helper entry point. It has been extended to
     * add support for passing a menu object or id (string).
     *
     * If the menu exists, it will be fetched; otherwise, an empty 
     * container will be used.
     *
     * @param   mixed   $container              optional - menu or container to operate on
     * @return  Menu_View_Helper_Breadcrumbs    fluent interface, returns self
     */
    public function breadcrumbs($container = null)
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

        return $this;
    }

    /**
     * Extends the parent htmlify method to deal with the pages without
     * a corresponding page to link to, e.g. heading menu items.
     *
     * @param  Zend_Navigation_Page $page  page to generate HTML for
     * @return string                      HTML string for the given page
     */
    public function htmlify(Zend_Navigation_Page $page)
    {
        // if there is a url, use the parent's htmlify method
        if ($page->getHref()) {
            return parent::htmlify($page);
        }

        // if there is NO url defined, don't add the <a> tag.
        // get label for translating
        $label = $page->getLabel();

        if ($this->getUseTranslator() && $t = $this->getTranslator()) {
            if (is_string($label) && !empty($label)) {
                $label = $t->translate($label);
            }
        }

        return $this->view->escape($label);
    }
}
