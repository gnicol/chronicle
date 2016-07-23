<?php
/**
 * Provides a menu widget for use in regions.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_WidgetController extends P4Cms_Widget_ControllerAbstract
{
    /**
     * Display the menu
     */
    public function indexAction()
    {
        // make the options available to the view.
        $options = $this->getOptions()->toArray();
        $this->view->widgetOptions = $options;

        // try to fetch menu items for the view.
        try {
            $menu = P4Cms_Menu::fetchMenuOrHandlerAsMenu($this->getOption('menu'));

            // tag the page cache so it can be appropriately cleared later
            if (P4Cms_Cache::canCache('page')) {
                P4Cms_Cache::getCache('page')
                    ->addTag('p4cms_menu')
                    ->addTag('p4cms_menu_' . bin2hex($menu->getId()));
            }

            $this->view->menu      = $menu;
            $this->view->menuItems = $menu->getExpandedContainer($options);
        } catch (Exception $e) {
            // menu id appears invalid - no valid menu to display.
        }
    }

    /**
     * Get config sub-form to present additional options when
     * configuring a widget of this type.
     *
     * @param   P4Cms_Widget                        $widget     the widget instance being configured.
     * @param   Zend_Controller_Request_Abstract    $request    the request values.
     * @return  Zend_Form_SubForm|null  the sub-form to integrate into the default
     *                                  widget config form or null for no sub-form.
     */
    public static function getConfigSubForm($widget, $request)
    {
        $form = new Menu_Form_Widget;

        $config    = $request->getParam('config');
        $menuId    = isset($config['menu']) ? $config['menu'] : $widget->getConfig('menu');
        
        // if the menu we are dealing with is a dynamic entry
        // allow the dynamic handler a chance to adjust the form.
        $handlerId = P4Cms_Menu::isDynamicHandlerId($menuId);
        if ($handlerId && P4Cms_Navigation_DynamicHandler::exists($handlerId)) {
            $handler = P4Cms_Navigation_DynamicHandler::fetch($handlerId);
            $form    = $handler->prepareForm($form);
        }
        
        return $form;
    }
}
