<?php
/**
 * A widget that displays an arbitrary iframe in a region.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widget_IframeWidgetController extends P4Cms_Widget_ControllerAbstract
{
    /**
     * Output the widget's HTML
     */
    public function indexAction()
    {
        $this->view->widget = $this->_getWidget();
    }

    /**
     * Get config sub-form to present additional options when
     * configuring a widget of this type.
     *
     * @param   P4Cms_Widget            $widget     the widget instance being configured.
     * @return  Zend_Form_SubForm|null  the sub-form to integrate into the default
     *                                  widget config form or null for no sub-form.
     */
    public static function getConfigSubForm($widget)
    {
        return new Widget_Form_IframeWidget;
    }
}
