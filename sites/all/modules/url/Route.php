<?php
/**
 * A route that matches/assembles url paths using custom url records.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Url_Route implements Zend_Controller_Router_Route_Interface
{
    protected   $_currentUrl    = null;
    protected   $_matchDeleted  = true;

    /**
     * Look for a custom url.
     *
     * @param   string|Zend_Controller_Request_Abstract     $match  either http request or the url
     *                                                              path to lookup params for
     * @return  array|false                                 params for the given path or false
     *                                                      if no matching record found.
     */
    public function match($match)
    {
        // set request and path based on the type of the $match parameter
        $request = $match instanceof Zend_Controller_Request_Abstract ? $match : null;
        $path    = $request ? $request->getPathInfo() : $match;

        try {
            // look for a custom url matching path
            // if match deleted is true, honors even deleted url records.
            $this->_currentUrl = Url_Model_Url::fetch(
                $path, array('includeDeleted' => $this->_matchDeleted)
            );

            $params = $this->_currentUrl->getParams();

            // set action from the request if present
            if ($request && $request->getParam('action')) {
                $params['action'] = $request->getParam('action');
            }

            return $params;
        } catch (P4Cms_Record_NotFoundException $e) {
            return false;
        }
    }

    /**
     * Get the last matched url record.
     *
     * @return  Url_Model_Url|null  the last matched url or null if none matched.
     */
    public function getCurrentUrl()
    {
        return $this->_currentUrl;
    }

    /**
     * Attempt to return a path for the given route parameters.
     * If no custom url is defined for the given params ('data')
     * throws an exception.
     *
     * @param   array   $data                   the route parameters to assemble path for.
     * @param   bool    $reset                  this parameter has no effect - we always reset
     * @param   bool    $encode                 this parameter has no effect - paths are always encoded
     * @return  string                          the assembled/matching url path.
     * @throws  P4Cms_Record_NotFoundException  if no path can be found for the given params.
     */
    public function assemble($data = array(), $reset = true, $encode = false)
    {
        return Url_Model_Url::fetchByParams($data)->getPath();
    }

    /**
     * Control whether or not route will match on deleted custom urls.
     * Default behavior is to match on deleted urls (to avoid breaking links).
     *
     * @param   bool        $matchDeleted   true to match deleted; false to ignore.
     * @return  Url_Route   provides fluent interface.
     */
    public function setMatchDeleted($matchDeleted)
    {
        $this->_matchDeleted = (bool) $matchDeleted;

        return $this;
    }

    /**
     * Implemented out of obligation as required by the interface.
     * Always returns a new instance of this route class.
     *
     * @param   Zend_Config     $config     route configuration information (we ignore)
     * @return  Url_Route       a new instance of this route class.
     */
    public static function getInstance(Zend_Config $config)
    {
        return new static;
    }

    /**
     * Get the version of the route. We do this to make the rewrite router passing
     * the whole request to our match() method.
     *
     * @return  integer     route version
     */
    public function getVersion()
    {
        return 2;
    }
}