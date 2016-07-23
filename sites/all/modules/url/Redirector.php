<?php
/**
 * A controller plugin to handle redirecting outdated custom-urls to 
 * more permanent locations. This allows us to honor old (deleted)
 * url paths and redirect the user to a more appropriate path.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Url_Redirector extends Zend_Controller_Plugin_Abstract
{
    /**
     * After the request is routed, check if we routed against an old (deleted) 
     * url path and if so, redirect (301) to a more permanent url.
     *
     * @param   Zend_Controller_Request_Abstract    $request    the request being routed.
     * @return  void
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        // nothing to do if our route is not in effect.
        $front  = Zend_Controller_Front::getInstance();
        $router = $front->getRouter();
        if (!$router->hasRoute(Url_Module::ROUTE)) {
            return;
        }
     
        // if routed url is deleted, redirect to a more permanent location.
        $route  = $router->getRoute(Url_Module::ROUTE);
        $url    = $route->getCurrentUrl();
        if ($url && $url->isDeleted() && $url->getPath() === trim($request->getPathInfo(), '/')) {
            $redirectUrl = null;
            $redirector  = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');

            // look for a better (non-deleted) custom url for these params.
            try {
                $redirectUrl = Url_Model_Url::fetchByParams($url->getParams())->getPath();
                if (count($request->getQuery())) {
                    $redirectUrl .= '?' . http_build_query($request->getQuery());
                }
                $redirector->gotoUrlAndExit(
                    $request->getBaseUrl() . '/' . $redirectUrl,
                    array('code' => 301)
                );
            } catch (P4Cms_Record_NotFoundException $e) {
                // no better url for these params.
            }
        }
    }
}