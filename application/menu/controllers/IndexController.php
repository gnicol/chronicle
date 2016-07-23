<?php
/**
 * Provides server-side menu support.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_IndexController extends Zend_Controller_Action
{
    public $contexts = array(
        'sitemap' => array('partial', 'xml'),
    );

    /**
     * Serve site map created from the 'sitemap' menu.
     */
    public function sitemapAction()
    {
        // set page title
        $this->view->headTitle()->set('Sitemap');

        // get sitemap menu from storage or supply blank one if it doesn't exists
        $menu = P4Cms_Menu::exists('sitemap')
            ? P4Cms_Menu::fetch('sitemap')
            : new P4Cms_Menu;

        // tag the page cache so it can be appropriately cleared later
        if (P4Cms_Cache::canCache('page')) {
            P4Cms_Cache::getCache('page')
                ->addTag('p4cms_menu')
                ->addTag('p4cms_menu_' . bin2hex('sitemap'));
        }

        $this->view->menu    = $menu;
        $this->view->sitemap = $menu->getExpandedContainer();
    }
}