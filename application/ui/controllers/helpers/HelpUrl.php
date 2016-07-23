<?php
/**
 * Action helper to specify a custom help URL.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Ui_Controller_Helper_HelpUrl extends Zend_Controller_Action_Helper_Abstract
{
    const       HELP_COOKIE         = 'help-page';
    const       HELP_BASE_URL       = 'docs/manual';
    const       HELP_DEFAULT_PAGE   = 'introduction.overview.html';

    protected   $_url               = null;

    /**
     * Sets a custom URL to use for Help dialogs.
     *
     * @param   string      $url                the URL to use when help dialogs are to be opened.
     * @return  Ui_Controller_Helper_HelpUrl    provide a fluent interface.
     */
    public function setUrl($url)
    {
        $this->_url = $url;
        return $this;
    }

    /**
     * Retrieves a custom URL to use for application help.
     * If the instance URL is unset, we fall back to a cookie-stored URL,
     * and if that is unset, we fall back to a default page.
     * 
     * @return  string  the current help url.
     */
    public function getUrl()
    {
        $url      = $this->_url;
        $request  = Zend_Controller_Front::getInstance()->getRequest();
        $baseUrl  = $request->getBaseUrl();
        $helpBase = static::HELP_BASE_URL;
        
        // if a context-specific url is set, use it.
        if ($url && P4Cms_Uri::isRelativeUri($url)) {
            $url = implode('/', array($baseUrl, $helpBase, $url));
        }

        // get the URL from the cookie, if not set
        $url = $url ?: $request->getCookie(static::HELP_COOKIE);

        // use the default URL, if still not set
        $url = $url ?: implode('/', array($baseUrl, $helpBase, static::HELP_DEFAULT_PAGE));

        return $url;
    }
}
