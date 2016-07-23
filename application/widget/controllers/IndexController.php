<?php
/**
 * Manages user-interactions with widgets.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widget_IndexController extends Zend_Controller_Action
{
    public $contexts = array(
        'index'     => array('partial'),
        'add'       => array('partial', 'json'),
        'configure' => array('partial', 'dojoio'),
        'form'      => array('partial')
    );

    /**
     * Prepare the widget view with context data.
     *
     * @todo make more defensive when context does not decode properly.
     */
    public function init()
    {
        parent::init();

        // handle any provided widgetContext
        $this->widgetContext->setEncodedValues($this->getRequest()->getParam('widgetContext'));

        // make the widget context available to the view
        $this->view->widgetContext = $this->widgetContext->getEncodedValues();

        $this->getHelper('audit')->addLoggedParams(array('widget', 'region', 'type'));
    }

    /**
     * Render the specified widget id.
     */
    public function indexAction()
    {
        // force partial context.
        $this->contextSwitch->initContext('partial');

        // fetch requested widget and render.
        $request = $this->getRequest();
        $widget  = P4Cms_Widget::fetch($request->widget);

        $this->view->widget = $widget;
    }

    /**
     * Delete the posted widget.
     */
    public function deleteAction()
    {
        // enforce permissions.
        $this->acl->check('widgets', 'manage');

        // disable rendering for this action.
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        // only respond to post requests.
        $request = $this->getRequest();
        if (!$request->isPost()) {
            throw new P4Cms_Widget_Exception(
                'Cannot delete widget. Request was not a valid HTTP POST.'
            );
        }

        // do the delete.
        $widget = P4Cms_Widget::fetch($request->widget);
        $region = $widget->getValue('region');
        $widget->delete("Deleted widget");

        // clear any cached entries related to this region
        P4Cms_Cache::clean(
            Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
            array(
                'p4cms_region',
                'p4cms_region_' . bin2hex($region),
                'p4cms_widget_' . bin2hex($widget->getId())
            )
        );
    }

    /**
     * Add the posted widget to the posted region.
     * Responds with the id of the newly added widget.
     */
    public function addAction()
    {
        // enforce permissions.
        $this->acl->check('widgets', 'manage');

        // if request was posted, perform add.
        // otherwise, present list of widgets to add.
        $request = $this->getRequest();
        if ($request->isPost()) {
            // enforce json context
            $this->contextSwitch->initContext('json');

            $widget = P4Cms_Widget::factory($request->type);
            $widget->setValue('region', $request->region)
                   ->save();

            // clear any cached entries related to this region
            P4Cms_Cache::clean(
                Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
                array('p4cms_region', 'p4cms_region_' . bin2hex($request->region))
            );

            $this->view->widget = $widget;
        } else {
            // enforce partial context
            $this->contextSwitch->initContext('partial');

            $this->view->types  = P4Cms_Widget_Type::fetchAll();
            $this->view->region = $request->region;
        }
    }

    /**
     * Get the config form for the given widget.
     */
    public function configureAction()
    {
        // default to partial context.
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->contextSwitch->initContext('partial');
        }

        // enforce permissions.
        $this->acl->check('widgets', 'manage');

        // get the widget to be configured and its config form.
        $request = $this->getRequest();
        $widget  = P4Cms_Widget::fetch($request->widget);
        $type    = $widget->getType();
        $form    = $this->_getConfigForm($widget);

        // setup view
        $this->view->form = $form;
        $this->view->type = $type;

        // populate form from request if posted, otherwise from storage.
        if ($request->isPost()) {
            $form->populate($request->getPost());
        } else {
            $values = $widget->toArray();
            $values['widget'] = $widget->id;
            if (isset($values['config']) && $values['config'] instanceof Zend_Config) {
                $values['config'] = $values['config']->toArray();
            }
            $form->populate($values);
        }

        // if form has been posted and is valid, save form values to widget.
        // otherwise, populate form from widget and render form.
        if ($request->isPost() && $form->isValid($request->getPost())) {

            // call the widgets controller to do any final touchups and save.
            call_user_func(
                array($type->getControllerClassName(), 'saveConfigForm'),
                $form,
                $widget
            );

            // clear any cached entries related to this region or widget
            P4Cms_Cache::clean(
                Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
                array(
                    'p4cms_region',
                    'p4cms_region_' . bin2hex($widget->getValue('region')),
                    'p4cms_widget_' . bin2hex($widget->getId())
                )
            );
        }
    }

    /**
     * Renders the config form for the requested widget.
     * Forces the 'partial' request context.
     * Though the form is populated it is not validated.
     *
     * Intended for refreshing the form to update fields
     * that depend on user supplied values.
     */
    public function formAction()
    {
        // explicitly set partial context for all requests.
        $this->contextSwitch->initContext('partial');

        // enforce permissions.
        $this->acl->check('widgets', 'manage');

        // get the widget to be configured and its config form.
        $request = $this->getRequest();
        $widget  = P4Cms_Widget::fetch($request->widget);
        $type    = $widget->getType();
        $form    = $this->_getConfigForm($widget);

        // populate the form
        $form->populate($request->getParams());

        // setup view
        $this->view->form = $form;
    }

    /**
     * Restore the default widgets.
     */
    public function resetAction()
    {
        // enforce permissions.
        $this->acl->check('widgets', 'manage');

        // clean out existing widgets
        P4Cms_Widget::fetchAll()->invoke('delete');

        // re-install default types
        P4Cms_Widget::installDefaults();

        P4Cms_Notifications::add(
            'Widgets Reset',
            P4Cms_Notifications::SEVERITY_SUCCESS
        );

        $this->redirector->gotoUrl($this->getRequest()->getBaseUrl());
    }

    /**
     * Get the general widget config form with this widget's
     * sub-form added in if one is provided.
     *
     * @param   P4Cms_Widget        $widget     the widget model being configured
     * @return  Widget_Form_Config  the complete widget config form.
     */
    protected function _getConfigForm($widget)
    {
        $form = new Widget_Form_Config;

        // try to get custom sub-form for this widget.
        try {
            $type    = $widget->getType();
            $subForm = call_user_func(
                array($type->getControllerClassName(), "getConfigSubForm"),
                $widget,
                $this->getRequest()
            );

            // ensure sub-form is valid.
            if ($subForm instanceof Zend_Form_SubForm) {
                $subForm->setLegend($type->label . ' Options');
                $subForm->addDecorator('Fieldset')->addDecorator('DtDdWrapper');
                $form->addSubForm($subForm, 'config', 2);
            } else if ($subForm !== null) {
                P4Cms_Log::log(
                    "Widget (" . $type->getId() . ") produced an invalid config sub-form.",
                    P4Cms_Log::ERR
                );
            }
        } catch (Exception $e) {
            P4Cms_Log::logException(
                "Failed to get widget config sub-form from '" . $type->getControllerClassName() . "'.",
                $e
            );
        }

        // prep form
        $form->setAttrib('id', 'form');
        $form->setIdPrefix('widget-' . $widget->id . '-config-');

        return $form;
    }
}
