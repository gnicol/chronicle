<?php
/**
 * Specialized version of the rewrite router with a few modified behaviors:
 *
 * Knowledge of the convention for embedding a particular site branch name
 * in the request. Strips the branch name from the request path info prior
 * to matching/routing urls and injects it prior to assembling them.
 *
 * Overrides the Zend_Controller_Router_Rewrite's assemble method so that
 * when an explicit router name is not provided, the 'default' route is used.
 * Normally, once a named route is used, it will be used for subsequent
 * URL generation unless another explicitly named route is specified. This
 * means that when the default route is desired for URL generation, it would
 * have had to be explicitly set, which is undesired.
 *
 * Also, always resets url parameters (except for module, controller and
 * action) to avoid bugs that tend to occur when the current request
 * parameters bleed into the next request.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Controller_Router_Rewrite extends Zend_Controller_Router_Rewrite
{
    /**
     * Specialized to strip an embedded branch name from the request's
     * path info prior to matching on requests.
     *
     * @param   Zend_Controller_Request_Abstract    $request    the request to route
     * @return  Zend_Controller_Request_Abstract    the request with route params set.
     */
    public function route(Zend_Controller_Request_Abstract $request)
    {
        // if request does not contain an embedded branch name, let parent route it as-is.
        if (!$request instanceof P4Cms_Controller_Request_Http || !$request->getBranchName()) {
            return parent::route($request);
        }

        // request contains an embedded branch name, we need to strip it
        // from the path before the parent router can match against it.
        $path = $request->getPathInfo();
        $request->setPathInfo($request->getBranchlessPath());

        // let parent route the request now that we have stripped the branch
        $request = parent::route($request);

        // restore original path.
        $request->setPathInfo($path);

        return $request;
    }

    /**
     * Extended to provide several special behaviors:
     *
     *  - Injects the branch name into the assembled url if present.
     *  - Overrides the parent assemble such that when a specific
     *    name is not provided, the default route is used.
     *  - Also, always resets url parameters (except for module,
     *    controller and action).
     *
     * @param   array   $userParams Options passed by a user used to override parameters
     * @param   mixed   $name       The name of a Route to use
     * @param   bool    $reset      Reset ALL url parameters including module, controller, action.
     * @param   bool    $encode     Tells to encode URL parts on output
     * @return  string  Resulting absolute URL path
     * @throws  Zend_Controller_Router_Exception
     */
    public function assemble($userParams, $name = null, $reset = false, $encode = true)
    {
        if ($name == null) {
            $name = 'default';
        }

        // merge global module, controller and action params into
        // user params unless reset is explicitly true.
        if ($reset !== true) {
            $globalParams = array_intersect_key(
                array_flip(array('module', 'controller', 'action')),
                $this->_globalParams
            );
            $userParams = array_merge($globalParams, $userParams);
        }

        // if request has no branch, parent can take it from here.
        $request = $this->getFrontController()->getRequest();
        if (!$request instanceof P4Cms_Controller_Request_Http || !$request->getBranchName()) {
            return parent::assemble($userParams, $name, true, $encode);
        }

        // request contains an embedded branch name, we want to inject it into the
        // assembled url - the easiest way to do this is to modify the base url.
        $baseUrl = $request->getBaseUrl();
        $request->setBaseUrl($request->getBranchBaseUrl());

        $url = parent::assemble($userParams, $name, true, $encode);

        // restore original base url.
        $request->setBaseUrl($baseUrl);

        return $url;
    }

    /**
     * Add route to the route chain. Extended to support prepending the route.
     * The last route wins, prepending gives the route lower priority.
     *
     * If route contains method setRequest(), it is initialized with a request object
     *
     * @param   string                                  $name       Name of the route
     * @param   Zend_Controller_Router_Route_Interface  $route      Instance of the route
     * @param   bool                                    $prepend    optional - defaults to false
     *                                                              prepend lowers the priority
     * @return  Zend_Controller_Router_Rewrite
     */
    public function addRoute($name, Zend_Controller_Router_Route_Interface $route, $prepend = false)
    {
        parent::addRoute($name, $route);

        // if prepend is specified, move route to the beginning of the array.
        if ($prepend) {
            $this->_routes = array($name => $route) + $this->_routes;
        }

        return $this;
    }
}
