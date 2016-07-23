<?php
/**
 * Handles configuration of the flickr module.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Flickr_ConfigureController extends Zend_Controller_Action
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
        $module  = P4Cms_Module::fetch('Flickr');
        $request = $this->getRequest();
        $form    = new Flickr_Form_Configure;

        // set up view
        $view               = $this->view;
        $view->form         = $form;
        $view->headTitle()->set('Configure Flickr');

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

        // save configuration if posted and valid
        if ($request->isPost() && $form->isValid($request->getPost())) {
            // save module config
            $module->saveConfig($form->getValues());

            // add notification message
            P4Cms_Notifications::add(
                "Flickr API configuration stored.",
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