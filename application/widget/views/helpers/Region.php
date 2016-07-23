<?php
/**
 * View helper that renders a region using a specified template.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widget_View_Helper_Region extends Zend_View_Helper_Action
{
    /**
     * Render the named/given region.
     *
     * @param   string  $region     the id of a region to render.
     * @param   string  $template   the template to use.
     * @return  string  the rendered region output.
     */
    public function region($region, $template = 'region.phtml')
    {
        $view          = $this->view;
        $view->region  = $region;
        $view->widgets = P4Cms_Widget::fetchByRegion($region);

        // tag the page cache so it can be appropriately cleared later
        if (P4Cms_Cache::canCache('page')) {
            P4Cms_Cache::getCache('page')->addTag('p4cms_region_'      . bin2hex($region));
        }

        // make the widget context available to the view
        $widgetContext = new P4Cms_Controller_Action_Helper_WidgetContext;
        $view->widgetContext = $widgetContext->getEncodedValues();

        return $view->render($template);
    }
}
