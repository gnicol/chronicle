<?php
/**
 * View and edit the configuration for the current site.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Site_IndexController extends Zend_Controller_Action
{
    /**
     * Work common to all module actions.
     */
    public function init()
    {
        // use management layout.
        $this->_helper->layout->setLayout('manage-layout');
    }

    /**
     * Show current configuration
     */
    public function configAction()
    {
        // enforce permissions.
        $this->_helper->acl->check('site', 'configure');

        $request    = $this->getRequest();
        $activeSite = P4Cms_Site::fetchActive();
        $form       = new Site_Form_Configure;

        // setup view.
        $view       = $this->view;
        $view->form = $form;
        $view->site = $activeSite;
        $view->headTitle()->set('General Settings');

        // always populate the form from storage initially
        $form->populate($activeSite->getConfig()->getValues());

        // specify the appropriate help URL
        $this->getHelper('helpUrl')->setUrl('sites.management.html');

        // re-populate and validate from request if posted.
        if ($request->isPost()) {
            // if form is invalid, set response code and exit
            if (!$form->isValid($request->getPost())) {
                $this->getResponse()->setHttpResponseCode(400);
                $view->errors = $form->getMessages();
                return;
            }

            // update the site and save it.
            $activeSite->getConfig()
                       ->setValues($form->getValues())
                       ->save();

            // clear cache, as site settings can have a broad impact.
            P4Cms_Cache::clean();

            // set notification message
            $view->message = "Site settings have been updated.";

            // notify user and redirect to type list.
            P4Cms_Notifications::add(
                $view->message,
                P4Cms_Notifications::SEVERITY_SUCCESS
            );
            $this->redirector->gotoSimple('config');
        }
    }

    /**
     * Emit the configured robots.txt definition.
     */
    public function robotsAction()
    {
        $activeSite = P4Cms_Site::fetchActive();
        $this->view->robotstxt = $activeSite->getConfig()->getValue('robots');
        $this->_helper->layout->disableLayout();
        $this->getResponse()->setHeader('Content-Type', 'text/plain');
    }
}