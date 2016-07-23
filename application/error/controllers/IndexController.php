<?php
/**
 * Handle errors/exceptions that occur while running the application.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Error_IndexController extends Zend_Controller_Action
{
    public  $contexts = array(
        'error'             => array('partial', 'json', 'dojoio'),
        'access-denied'     => array('partial', 'json', 'dojoio'),
        'page-not-found'    => array('partial', 'json', 'dojoio')
    );

    /**
     * Don't use layouts when displaying errors.
     * If an error occurs during layout rendering,
     * the error page can't be rendered in a layout
     * therefore, we don't use them here.
     */
    public function init()
    {
        $this->getHelper('layout')->disableLayout();

        // honor the current context.
        $contextSwitch  = $this->getHelper('contextSwitch');
        $currentContext = $contextSwitch->getCurrentContext();
        if ($currentContext && $contextSwitch->hasContext($currentContext)) {
            $contextSwitch->initContext($currentContext);
        }
    }

    /**
     * Alias for error action.
     */
    public function indexAction()
    {
        $this->_forward('error');
    }

    /**
     * Called by the "ErrorHandler" when an error/exception has been encountered.
     */
    public function errorAction()
    {
        // Grab the error object from the request
        $errors = $this->_getParam('error_handler');

        // log the error.
        P4Cms_Log::logException("Application error.", $errors->exception);

        // $errors will be an object set as a parameter of the request object,
        // type is a property
        $errorType = $errors->type === Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER
            ? get_class($errors->exception)
            : $errors->type;

        // handle error according to error type or exception class
        switch($errorType) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case 'P4Cms_PageNotFoundException':
                $this->_forward('page-not-found');
                break;

            case 'P4Cms_AccessDeniedException':
                $this->_forward('access-denied');
                break;

            default:
                $this->getResponse()->setHttpResponseCode(500);
                $this->view->message = 'Application error';
        }

        // pass the environment to the view script so we can conditionally
        // display more/less information
        $this->view->env       = $this->getInvokeArg('env');

        // pass the exception message to the view.
        $this->view->message   = $errors->exception->getMessage();

        // pass the actual exception object to the view
        $this->view->exception = $errors->exception;

        // pass the request to the view
        $this->view->request   = $errors->request;

        // set the title
        $this->view->headTitle()->set('Error');
    }

    /**
     * Present a 'page not found' page.
     */
    public function pageNotFoundAction()
    {
        $this->view->requestUri = $this->getRequest()->getRequestUri();

        // 404 error - not found
        $this->getResponse()->setHttpResponseCode(404);

        // set the title
        $this->view->headTitle()->set('Page Not Found');
    }

    /**
     * Present a 'access denied' page.
     */
    public function accessDeniedAction()
    {
        // 403 error - forbidden
        $this->getResponse()->setHttpResponseCode(403);

        // set the title
        $this->view->headTitle()->set('Access Denied');
    }
}
