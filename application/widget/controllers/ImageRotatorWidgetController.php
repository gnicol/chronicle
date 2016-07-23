<?php
/**
 * A widget that displays an arbitrary selection of images in a region.
 * The images can be updated (replaced).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widget_ImageRotatorWidgetController extends P4Cms_Widget_ControllerAbstract
{
    /**
     * Output the widgets html
     */
    public function indexAction()
    {
        $widget = $this->_getWidget();
        $images = $this->getOption('images');
        $images = $images ? $images->toArray() : null;

        // setup the view.
        $this->view->widget = $widget;
        $this->view->images = Content_Form_Element_ContentSelect::normalizeValue($images);
        $this->view->ids    = Content_Form_Element_ContentSelect::extractIds($images);
    }

    /**
     * Get config sub-form to present additional options when
     * configuring a widget of this type.
     *
     * @param   P4Cms_Widget            $widget     the widget instance being configured.
     * @return  Zend_Form_SubForm       the sub-form to integrate into the default
     *                                  widget config form or null for no sub-form.
     */
    public static function getConfigSubForm($widget)
    {
        return new Widget_Form_ImageRotatorWidget;
    }
}
