<?php
/**
 * Handles configuration of the Disqus module.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Disqus_ConfigureController extends Zend_Controller_Action
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
        $module             = P4Cms_Module::fetch('Disqus');
        $form               = new Disqus_Form_Configure;
        $this->view->form   = $form;
        $request            = $this->getRequest();

        $this->view->headTitle()->set('Configure Disqus');

        // populate form from the request if posted, otherwise from the storage
        $form->populate(
            $request->isPost()
            ? $request->getParams()
            : $module->getConfig()->toArray()
        );
        
        // use manage layout for traditional contexts
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->getHelper('layout')->setLayout('manage-layout');
        }

        // save configuration if posted and valid
        if ($request->isPost() && $form->isValid($request->getParams())) {
            // save configuration
            $module->saveConfig($form->getValues());

            P4Cms_Notifications::add(
                'Disqus configuration stored.',
                P4Cms_Notifications::SEVERITY_SUCCESS
            );

            // redirect for traditional requests
            if (!$this->contextSwitch->getCurrentContext()) {
                $this->redirector->gotoSimple('index', 'module', 'site');
            }
        } else if ($request->isPost()) {
            $this->getResponse()->setHttpResponseCode(400);
            $this->view->errors = $form->getMessages();
        }
    }
}