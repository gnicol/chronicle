<?php
/**
 * Enhances Zend_Controller_Action to provide additional
 * functionality that is specific to widget controllers.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
abstract class P4Cms_Widget_ControllerAbstract extends Zend_Controller_Action
{
    /**
     * Prepare the widget view with context data.
     *
     * @todo make more defensive when context does not decode properly.
     */
    public function init()
    {
        parent::init();

        // handle any provided widgetContext
        $this->widgetContext->setEncodedValues(
            $this->getRequest()->getParam('widgetContext')
        );

        // make the widget context available to the view
        $this->view->widgetContext = $this->widgetContext->getEncodedValues();
    }

    /**
     * Get a saved option for this widget instance.
     *
     * @param   string  $option     the name of the option to get.
     * @param   mixed   $default    optional - a default value to return if no value is set.
     * @return  mixed   the value of the named option.
     */
    public function getOption($option, $default = null)
    {
        return $this->_getWidget()->getConfig($option, $default);
    }

    /**
     * Get all saved options for this widget instance.
     *
     * @return  array   all of the saved options.
     */
    public function getOptions()
    {
        return $this->_getWidget()->getConfig();
    }

    /**
     * Get config sub-form to present additional options when
     * configuring a widget of this type.
     *
     * The config form will then be integrated into the default
     * widget config form to present additional options to the user
     * when configuring the widget. The saved form values be passed
     * as additional request parameters to actions on the widget
     * controller.
     *
     * @param   P4Cms_Widget                        $widget     the widget instance being configured.
     * @param   Zend_Controller_Request_Abstract    $request    the request values.
     * @return  Zend_Form_SubForm|null  the sub-form to integrate into the default
     *                                  widget config form or null for no sub-form.
     */
    public static function getConfigSubForm($widget, $request)
    {
        return null;
    }

    /**
     * Updates the widget model with the passed configuration form
     * values and writes out the new settings to storage.
     *
     * @param   Zend_Form       $form       form with new config values
     * @param   P4Cms_Widget    $widget     widget to save
     */
    public static function saveConfigForm($form, $widget)
    {
        $widget->setValues($form->getValues())->save();

        // clear any cached entries related to this widget
        P4Cms_Cache::clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            'p4cms_widget_' . bin2hex($widget->getId())
        );
    }

    /**
     * Get the widget model associated with this widget
     * controller instance.
     *
     * @return  P4Cms_Widget    the associated widget model.
     */
    protected function _getWidget()
    {
        // the associated widget model is passed in via
        // the request parameters - it could be a widget
        // model instance, or string id in the latter case
        $request = $this->getRequest();
        $widget  = $request->getParam('widget');
        if (!$widget instanceof P4Cms_Widget) {
            $widget = P4Cms_Widget::fetch($widget);
        }

        return $widget;
    }
}
