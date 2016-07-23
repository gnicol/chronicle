<?php
/**
 * Handles configuration of the Analytics module.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Analytics_ConfigureController extends Zend_Controller_Action
{
    public $contexts = array(
        'index' => array('partial', 'json')
    );

    /**
     * Show form, persist into module config
     * (fetch module, set config)
     */
    public function indexAction()
    {
        $request = $this->getRequest();
        $form    = new Analytics_Form_Configure;
        $module  = P4Cms_Module::fetch('Analytics');

        // set up view
        $view               = $this->view;
        $view->form         = $form;
        $view->headTitle()->set('Configure Analytics');

        // use manage layout for traditional contexts
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->getHelper('layout')->setLayout('manage-layout');
        }

        // populate form from the request if posted, otherwise from the storage
        $form->populate(
            $request->isPost()
            ? $request->getPost()
            : $module->getConfig()->toArray()
        );

        // if request, save; if error, show; if not, show form
        if ($request->isPost() && $form->isValid($request->getPost())) {
            // save configuration
            $module->saveConfig($form->getValues());

            // add notification message
            P4Cms_Notifications::add(
                "Analytics configuration stored.",
                P4Cms_Notifications::SEVERITY_SUCCESS
            );

            // redirect for traditional requests
            if (!$this->contextSwitch->getCurrentContext()) {
                $this->redirector->gotoSimple('index', 'module', 'site');
            }
        } else if ($request->isPost()) {
            $this->getResponse()->setHttpResponseCode(400);
            $view->errors = $form->getMessages();
        }
    }
}