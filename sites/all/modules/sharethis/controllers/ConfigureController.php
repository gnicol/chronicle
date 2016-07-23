<?php
/**
 * Manages the ShareThis module configuration.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Sharethis_ConfigureController extends Zend_Controller_Action
{
    public $contexts = array(
        'index' => array('partial', 'json')
    );

    /**
     * Show a module configuration form.
     */
    public function indexAction()
    {
        // enforce permissions.
        $this->acl->check('sharethis', 'manage');

        $request = $this->getRequest();
        $module  = P4Cms_Module::fetch('Sharethis');
        $form    = new Sharethis_Form_Configure;

        // set up view
        $view               = $this->view;
        $view->form         = $form;
        $view->headTitle()->set('Configure ShareThis');

        // use manage layout for traditional contexts
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->getHelper('layout')->setLayout('manage-layout');
        }

        // populate form from the request if posted, otherwise from the storage
        $form->populate(
            $request->isPost()
            ? $request->getParams()
            : $module->getConfig()->toArray()
        );

        // save configuration if posted and valid
        if ($request->isPost() && $form->isValid($request->getParams())) {
            // save module config
            $module->saveConfig($form->getValues());

            // add notification message
            P4Cms_Notifications::add(
                "ShareThis configuration has been successfully updated.",
                P4Cms_Notifications::SEVERITY_SUCCESS
            );

            // redirecte for traditional requests
            if (!$this->contextSwitch->getCurrentContext()) {
                $this->redirector->gotoSimple('index', 'module', 'site');
            }
        } else if ($request->isPost()) {
            $this->getResponse()->setHttpResponseCode(400);
            $view->errors = $form->getMessages();
        }
    }
}