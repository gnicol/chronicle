<?php
/**
 * Enhances ContextSwitch to automatically initialize context
 * if contexts are defined in the action controller and initContext
 * has not been called explicitly. Automatically adds unknown
 * contexts using the context name as the view script suffix
 * (ie. no need to pre-declare formats, just use them).
 *
 * In addition, allows controller to specify which request methods
 * a given context is valid for by expanding the contexts definition
 * to accommodate a list of http request methods for each context.
 * For example:
 *
 *  $contexts = array(
 *      'action-one'   => array('json'),
 *      'action-two'   => array('json' => 'post'),
 *      'action-three' => array('json' => array('post', 'put')
 *  );
 *
 * The above contexts definition indicates that action-one supports
 * the json context for all request methods; whereas 'action-two'
 * only supports json for http post requests and 'action-three'
 * supports json for post and put.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Controller_Action_Helper_ContextSwitch
    extends Zend_Controller_Action_Helper_ContextSwitch
{
    protected   $_autoInit              = true;
    protected   $_contextKey            = 'normalizedContexts';
    protected   $_complexContextKey     = 'contexts';

    /**
     * Automatically initialize context prior to dispatch.
     */
    public function preDispatch()
    {
        // disabled layouts if auto init/disable is on and a format
        // is specified. in particular this makes partial mostly work
        // for actions that don't directly support it.
        if ($this->_autoInit
            && $this->getAutoDisableLayout()
            && $this->getRequest()->getParam($this->getContextParam())
        ) {
            $layout = Zend_Layout::getMvcInstance();
            if (null !== $layout) {
                $layout->disableLayout();
            }
        }

        // init contexts if auto-init true and controller defines contexts.
        if ($this->_autoInit && !empty($this->getActionController()->contexts)) {
            // disable automatic json serialization when auto initializing.
            $this->setAutoJsonSerialization(false);
            $this->initContext();
            $this->_autoInit = true;
        }

        // make the helper easier to access from the controller.
        $this->getActionController()->contextSwitch = $this;

        return parent::preDispatch();
    }

    /**
     * Extend init context to disable auto-init when called.
     *
     * @param  mixed  $format  The default context.
     * @throws Zend_Controller_Action_Exception
     * @return void
     */
    public function initContext($format = null)
    {
        // normalize controller contexts for parent.
        $controller = $this->getActionController();
        $contextKey = $this->_complexContextKey;
        $normalized = array();
        $contexts   = isset($controller->$contextKey)
            ? $controller->$contextKey
            : array();
        foreach ($contexts as $action => $actionContexts) {
            if ($actionContexts === true) {
                $normalized[$action] = true;
                continue;
            }
            $normalized[$action] = array();
            foreach ($actionContexts as $key => $value) {
                $context                = is_string($key) ? $key : $value;
                $normalized[$action][]  = $context;

                // automatically add any missing contexts.
                if (!$this->hasContext($context)) {
                    $this->addContext($context, array('suffix' => $context));
                }
            }
        }

        // copy normalized contexts to standard context key.
        $contextKey = $this->_contextKey;
        $controller->$contextKey = $normalized;

        $this->_autoInit = false;
        return parent::initContext($format);
    }

    /**
     * Extend hasActionContext to be aware of request method limitations.
     *
     * @param  string       $action   Action to evaluate.
     * @param  string|array $context  Context to evaluate.
     * @throws Zend_Controller_Action_Exception
     * @return boolean
     */
    public function hasActionContext($action, $context)
    {
        $result = parent::hasActionContext($action, $context);

        // if action doesn't have this context, exit early.
        if (!$result) {
            return false;
        }

        // ensure that context is valid for the current request method.
        $contextKey = $this->_complexContextKey;
        $contexts   = $this->getActionController()->$contextKey;

        // if action contexts is true, request method doesn't matter.
        if ($contexts[$action] === true) {
            return true;
        }

        // check for request method limit on this action/context.
        if (array_key_exists($context, $contexts[$action])) {
            $requestMethod = $this->getRequest()->getMethod();
            $actionMethods = (array) $contexts[$action][$context];
            foreach ($actionMethods as $method) {
                if (strtoupper($method) === $requestMethod) {
                    return true;
                }
            }
            return false;
        }

        // must be valid.
        return true;
    }
}
