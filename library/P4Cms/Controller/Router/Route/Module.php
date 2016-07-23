<?php
/**
 * Overrides the Zend module router to support shorter and cleaner urls.
 * With Zend's default router, the default ('index') controller must always
 * be specified when an action is specified. This derivative allows the
 * 'index' controller to be left out when specifying an action, provided
 * that the action itself does not resolve to a controller. This derivative
 * also allows the 'index' action to be left out, provided that any following
 * parameters do not resolve to an action.
 *
 * For example: '/default/foo' is equivalent to '/default/index/index/foo'
 * provided that there is no 'foo' controller in the default module,
 * or 'foo' action in the default module.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Controller_Router_Route_Module extends Zend_Controller_Router_Route_Module
{
    /**
     * Matches a user submitted path. Assigns and returns an array 
     * of variables on a successful match.
     * 
     * Extends Zend's module route to support ommitting the default module,
     * default controller and default action from the path.
     * 
     * @param   string  $path     Path used to match against this routing map.
     * @param   bool    $partial  Zend provides no documentation for this param.
     * @return  array   An array of assigned values or a false on a mismatch
     */
    public function match($path, $partial = false)
    {
        $this->_setRequestKeys();
        $this->_values = array();

        if (!$partial) {
            $path = trim($path, self::URI_DELIMITER);
        } else {
            $this->setMatchedPath($path);
        }
        
        // an empty path matches defaults.
        if ($path === '') {
            return $this->getDefaults();
        }
        
        // break path into segments.
        $path = explode(self::URI_DELIMITER, $path);
        
        $defaultModule     = $this->getDefault($this->_moduleKey);
        $defaultController = $this->getDefault($this->_controllerKey);
        $defaultAction     = $this->getDefault($this->_actionKey);
        
        // if the first path segment does not match a valid module, 
        // it must match a controller in the default module or a action 
        // in the default module's default controller.
        if (!$this->isValidModule($path[0])) {
            if ($this->isValidController($path[0], $defaultModule)) {
                array_unshift($path, $defaultModule);
            } else if ($this->isValidAction($path[0], $defaultController, $defaultModule)) {
                array_unshift($path, $defaultController);
                array_unshift($path, $defaultModule);
            } else {
                return false;
            }
        }
        
        // if the second path segment doesn't match a valid controller, 
        // assume the default controller in that module.
        if (!isset($path[1]) || !$this->isValidController($path[1], $path[0])) {
            array_splice($path, 1, 0, array($defaultController));
        }
        
        // if the third path segment doesn't match a valid action, 
        // assume the default action in that module/controller.
        if (!isset($path[2]) || !$this->isValidAction($path[2], $path[1], $path[0])) {
            array_splice($path, 2, 0, array($defaultAction));
        }
        
        // verify we now reference a valid module/controller/action
        if ($this->isValidAction($path[2], $path[1], $path[0])) {
            $values = array(
                $this->_moduleKey     => $path[0],
                $this->_controllerKey => $path[1],
                $this->_actionKey     => $path[2]
            );
        } else {
            return false;
        }
        
        // process all remaining path segments as parameters.
        $params = array();
        for ($i = 3; $i < count($path); $i = $i + 2) {
            $key   = urldecode($path[$i]);
            $value = isset($path[$i + 1]) ? urldecode($path[$i + 1]) : null;
            $params[$key] = (isset($params[$key])
                ? (array_merge((array) $params[$key], array($value)))
                : $value);
        }
        
        $this->_values = $values + $params;
        return $this->_values + $this->_defaults;
    }

    /**
     * Determine if the given module is valid. 
     * Alias for dispatcher's isValidModule().
     * 
     * @param   string  $module     the (route-formatted) module to check for.
     * @return  bool    true if the module exists, false otherwise.
     */
    public function isValidModule($module)
    {
        return $this->_dispatcher->isValidModule($module);
    }

    /**
     * Determine if the given controller exists in the given module.
     * 
     * @param   string  $controller     the (route-formatted) controller to check for.
     * @param   string  $module         the (route-formatted) module to look in.
     * @return  bool    true if the controller exists, false otherwise.
     */
    public function isValidController($controller, $module)
    {
        if (!$this->isValidModule($module)) {
            return false;
        }
        
        $dispatcher = $this->_dispatcher;
        $controller = $dispatcher->formatControllerName($controller);
        $className  = $dispatcher->formatClassName(ucfirst($module), $controller);

        // verify controller is valid, return false otherwise.
        return class_exists($className) 
            && is_subclass_of($className, 'Zend_Controller_Action_Interface');
    }

    /**
     * Determine if the given action/module/controller represent a
     * valid action in the system. The identified controller must exist
     * and the action must match a method in the controller.
     *
     * @param   string  $action         the (route-formatted) action to check for
     * @param   string  $controller     the (route-formatted) controller containing the action
     * @param   string  $module         the (route-formatted) module containing the controller
     * @return  bool    true if the action exists.
     */
    public function isValidAction($action, $controller, $module)
    {
        if (!$this->isValidModule($module) 
            || !$this->isValidController($controller, $module)
        ) {
            return false;
        }
        
        $dispatcher = $this->_dispatcher;
        $action     = $dispatcher->formatActionName($action);
        $controller = $dispatcher->formatControllerName($controller);
        $className  = $dispatcher->formatClassName(ucfirst($module), $controller);

        // determine whether the action exists
        return method_exists($className, $action);
    }
    
    /**
     * Override parent implementation to produce shorter paths.
     * Specifically, excludes the controller segment if it is the
     * default controller and there is no action segment or the
     * action does not match a controller.
     *
     * @param   array   $data     An array of variable and value pairs used as parameters.
     * @param   bool    $reset    Whether to reset the current params.
     * @param   bool    $encode   Zend provides no documentation for this param.
     * @param   bool    $partial  Zend provides no documentation for this param.
     * @return  string  Route path with user submitted parameters
     */
    public function assemble($data = array(), $reset = false, $encode = true, $partial = false)
    {
        // move parameters to the query string if:
        // - any parameters contain a slash '/'
        //   (this is a work-around for apache whereby it chokes on %2f in path info)
        // - any parameters are a single dot '.'
        //   (work-around to prevent situations where uri segments containing only
        //   a single dot are dropped; seems its not connected with the apache)
        $queryParams = array();
        foreach ($data as $key => $value) {
            if (strpos((string) $key, '/') !== false
                || strpos((string) $value, '/') !== false
                || $key === '.'
                || $value === '.'
            ) {
                $queryParams[$key] = $value;
                unset($data[$key]);
            }
        }

        $path = parent::assemble($data, $reset, $encode, $partial);

        // blend given router params with current params and defaults.
        $params = (!$reset) ? $this->_values : array();
        foreach ($data as $key => $value) {
            if ($value !== null) {
                $params[$key] = $value;
            } elseif (isset($params[$key])) {
                unset($params[$key]);
            }
        }
        $params += $this->_defaults;

        $segments = explode(self::URI_DELIMITER, $path);
        // while attempting to remove segments, if a non-default module is being used,
        // removals must begin at the second segment.
        $offset = 0;
        if (isset($params[$this->_moduleKey])
            && $params[$this->_moduleKey] !== $this->_defaults[$this->_moduleKey]
        ) {
            $offset = 1;
        }

        $controller = isset($segments[0 + $offset]) ? $segments[0 + $offset] : null;
        $action     = isset($segments[1 + $offset]) ? $segments[1 + $offset] : null;
        $pathParam  = isset($segments[2 + $offset]) ? $segments[2 + $offset] : null;

        // determine if the action can be removed
        if (isset($action) && !$this->_isActionParamRequired($params, $action, $pathParam)) {
            $segments[1 + $offset] = null;
            $action = $pathParam;
        }

        // determine if the controller can be removed
        if (isset($controller) && !$this->_isControllerParamRequired($params, $controller, $action)) {
            $segments[$offset] = null;
        }

        // compose possibly-revised path
        $newSegments = array();
        foreach ($segments as $segment) {
            if (isset($segment)) {
                $newSegments[] = $segment;
            }
        }
        $path = implode(self::URI_DELIMITER, $newSegments);

        // tack on any query params.
        if (count($queryParams)) {
            $path .= "?" . http_build_query($queryParams);
        }

        return $path;
    }

    /**
     * Get the route formatted (lower-case, dash-separated) version
     * of a CamelCase identifier.
     *
     * @param   string  $param  the camel case string.
     * @return  string  the route-formatted version.
     */
    public static function formatRouteParam($param)
    {
        return strtolower(preg_replace("/(.)([A-Z])/", "\\1-\\2", $param));
    }

    /**
     * Determine if given route parameters are dispatchable.
     * This is accomplished by creating and populating a request
     * object with params and passing it to the dispatcher's
     * isDispatchable() method.
     *
     * @param   array   $params     the route parameters to check.
     * @return  bool    true if the route params are dispatchable.
     */
    protected function _isDispatchable($params)
    {
        // construct request object from route params.
        $request = new Zend_Controller_Request_Http;
        $moduleName = '';
        $actionName = '';
        foreach ($params as $param => $value) {
            $request->setParam($param, $value);
            if ($param === $this->_moduleKey) {
                $request->setModuleName($value);
                $moduleName = $value;
            }
            if ($param === $this->_controllerKey) {
                $request->setControllerName($value);
            }
            if ($param === $this->_actionKey) {
                $request->setActionName($value);
                $actionName = $value;
            }
        }

        $dispatcher   = $this->_dispatcher;
        $dispatchable = $dispatcher->isDispatchable($request);

        // if dispatchable, we now want to determine whether the specified
        // action exists.
        if ($dispatchable and $actionName) {
            $controllerClass = $dispatcher->getControllerClass($request);
            if (!$controllerClass) {
                $controllerClass = $dispatcher->getDefaultControllerClass($request);
            }
            if (!$moduleName) {
                $moduleName = $this->_defaults[$this->_moduleKey];
            }
            $className = $dispatcher->formatClassName(ucfirst($moduleName), $controllerClass);

            // if controller class doesn't exist, attempt to include it.
            if (!class_exists($className)) {
                $file = $dispatcher->getDispatchDirectory()
                      . '/' . $dispatcher->classToFilename($controllerClass);
                if (Zend_Loader::isReadable($file)) {
                    include $file;
                }
            }

            // verify controller is valid, return false otherwise.
            if (!class_exists($className) ||
                !is_subclass_of($className, 'Zend_Controller_Action_Interface')
            ) {
                return false;
            }

            // determine whether the action exists
            $action = $dispatcher->getActionMethod($request);
            $dispatchable = method_exists($className, $action) ? true : false;
        }

        return $dispatchable;
    }

    /**
     * Determine if the controller parameter is required to assemble
     * the path. Not required if controller parameter is the default
     * controller and there is no action parameter or the action can
     * not be confused with a controller.
     *
     * @param   array   $params      the route parameters to test against.
     * @param   string  $controller  the controller to consider
     * @param   string  $action      the action within the controller
     * @return  bool    true if the controller is required.
     */
    protected function _isControllerParamRequired($params, $controller, $action)
    {
        // required if not default.
        if ($params[$this->_controllerKey] !== $this->_defaults[$this->_controllerKey]) {
            return true;
        }

        // not required if no action component.
        if (!isset($action)) {
            return false;
        }

        // required if action name matches a controller name.
        // we test this by setting the controller parameter to
        // the given action and seeing if it is dispatchable.
        $params[$this->_controllerKey] = $action;
        unset($params[$this->_actionKey]);
        if ($this->_isDispatchable($params)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the action parameter is required to assemble the
     * path. Not required if action parameter is the default
     * action and there are no more parameters or the following
     * parameter cannot be confused with a controller.
     *
     * @param   array   $params     the route parameters to test against.
     * @param   string  $action     the action to consider
     * @param   string  $pathParam  the current path (or path fragment)
     * @return  bool    true if the action is required.
     */
    protected function _isActionParamRequired($params, $action, $pathParam)
    {
        // required if not default.
        if ($params[$this->_actionKey] !== $this->_defaults[$this->_actionKey]) {
            return true;
        }

        // not required if no following parameters
        if (!isset($pathParam)) {
            return false;
        }

        // required if action name matches a controller name.
        // we test this by setting the controller parameter to
        // the given action and seeing if it is dispatchable.
        $params[$this->_actionKey] = $pathParam;
        if ($this->_isDispatchable($params)) {
            return true;
        }

        return false;
    }
}
