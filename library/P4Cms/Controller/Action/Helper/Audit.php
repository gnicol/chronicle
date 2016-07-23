<?php
/**
 * Logs first action dispatch with details of the active site, 
 * the user that invoked the action and request params.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Controller_Action_Helper_Audit extends Zend_Controller_Action_Helper_Abstract
{
    // the log level at which to write the audit information
    protected   $_auditLogLevel         = P4Cms_Log::INFO;

    // whether or not we have logged the dispatch
    protected   $_loggedDispatch        = false;

    // Holds the information gleaned from the initial dispatch, used in postDispatch for constructing
    // the message.
    protected   $_initialDispatch       = array();

    // A default whitelist of parameters to log.
    protected   $_loggedParams          = array('format', 'id');

    /**
     * Record the module, controller, and action at the time of the first dispatch, as they may
     * be changed later.
     *
     * @return void return early if we have already logged the intial dispatch.
     */
    public function init()
    {
        // only log the first dispatch.
        if (empty($this->_initialDispatch)) {
            $this->_initialDispatch = array(
                'module'        => $this->getRequest()->getModuleName(),
                'controller'    => $this->getRequest()->getControllerName(),
                'action'        => $this->getRequest()->getActionName()
            );
        }
    }

    /**
     * Log the action, the site, the user, the method, and requested params.
     *
     * @return void Return early if we have already logged for this dispatch.
     */
    public function postDispatch()
    {
        
        // only log the first dispatch.
        if ($this->_loggedDispatch) {
            return;
        }
        
        // log the action being dispatched.
        $message = "Dispatch: "
                 . $this->_initialDispatch['module']
                 . '/' . $this->_initialDispatch['controller']
                 . '/' . $this->_initialDispatch['action'];
        
        // incorporate active site id
        $site     = P4Cms_Site::hasActive()
            ? P4Cms_Site::fetchActive()->getId()
            : '<none>';
        $message .= ", Site: " . $site; 
        
        // incorporate active user id
        $user     = P4Cms_User::hasActive() && !P4Cms_User::fetchActive()->isAnonymous()
            ? P4Cms_User::fetchActive()->getId()
            : '<anonymous>';
        $message .= ", User: " . $user;
        
        // incorporate request method and parameters.
        $requestParams    = $this->getRequest()->getParams();
        $params           = array();

        foreach ($this->getLoggedParams() as $param) {
            if (array_key_exists($param, $requestParams)) {
                $params[] = $param . '=' . $requestParams[$param];
            }
        }
        
        $message .= ", Method: " . $this->getRequest()->getMethod();
        $message .= ", Params: " . implode(', ', $params);        
        
        P4Cms_Log::log($message, $this->_auditLogLevel);
        
        $this->_loggedDispatch = true;
    }

    /**
     * Adds a parameter to the whitelist of parameters to write to the log message.
     *
     * @param string $param  A single parameter to add.
     */
    public function addLoggedParam($param)
    {
        if (!in_array($param, $this->_loggedParams)) {
            $this->_loggedParams[] = $param;
        }
    }

    /**
     * Adds a list of parameters to the whitelist of parameters to write to the log message.
     * 
     * @param array $params A list of parameters to add.
     */
    public function addLoggedParams($params)
    {
        foreach ($params as $param) {
            $this->addLoggedParam($param);
        }
    }

    /**
     * Returns the current whitelist of parameters that will be written to the log message.
     *
     * @return array The current whitelist of parameters.
     */
    public function getLoggedParams()
    {
        return $this->_loggedParams;
    }

    /**
     * Sets the whitelist of paramters that will be written to the log message.
     *
     * @param array $params The list of parameters to set.
     */
    public function setLoggedParams($params)
    {
        $this->_loggedParams = array();
        $this->addLoggedParams($params);
    }
    
}
