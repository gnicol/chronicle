<?php
/**
 * View helper that renders a widget using a specified template.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widget_View_Helper_Widget extends Zend_View_Helper_Action
{
    /**
     * Render the given widget using the specified template.
     *
     * @param   P4Cms_Widget    $widget     the widget to display.
     * @param   string          $template   optional - the view script to render to.
     */
    public function widget($widget, $template = 'widget.phtml')
    {
        $view         = $this->view;
        $view->widget = $widget;

        // get resource and privilege of the widget type if they have been set.
        try {
            $resource  = $widget->getType()->getValue('resource');
            $privilege = $widget->getType()->getValue('privilege');
        } catch (Exception $e) {
            $resource = null;
        }

        // don't render widget if user is not allowed to see it.
        if ($resource && P4Cms_User::hasActive()) {
            $user = P4Cms_User::fetchActive();
            if (!$user->isAllowed($resource, $privilege)) {
                return null;
            }
        }

        // tag the page cache so it can be appropriately cleared later
        if (P4Cms_Cache::canCache('page')) {
            P4Cms_Cache::getCache('page')->addTag('p4cms_widget_' . bin2hex($widget->getId()));
        }

        return $view->render($template);
    }
}
