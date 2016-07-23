<?php
/**
 * Extended version of the standard http request object with specialized
 * knowledge of the convention for embedding a site branch name in path
 * info (e.g. '/-dev-/module/controller/action').
 *
 * See P4Cms_Site for additional information.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Controller_Request_Http extends Zend_Controller_Request_Http
{
    protected   $_branch    = false;
    protected   $_pathInfo  = null;

    /**
     * Get the branch name in this request's path info (if one is present).
     * For example: '/-dev-/path' would produce 'dev'.
     *
     * @return  string  the branch id or null if none specified.
     */
    public function getBranchName()
    {
        if ($this->_branch === false) {
            $this->_branch = preg_match('#^/-([^/]+)-#', $this->getPathInfo(), $matches)
                ? $matches[1]
                : null;
        }

        return $this->_branch;
    }

    /**
     * Get the base url with the branch embedded (if one is present).
     *
     * @return  string  the base url including the branch id.
     */
    public function getBranchBaseUrl()
    {
        $baseUrl = trim($this->getBaseUrl(), '/');

        return $this->getBranchName()
            ? $baseUrl . '/-' . $this->getBranchName() . '-'
            : $baseUrl;
    }

    /**
     * Get the request path info, but with the embedded branch name removed.
     *
     * @return  string  the path info without the branch name.
     */
    public function getBranchlessPath()
    {
        $pattern = '#^/?-' . $this->getBranchName() . '-/?#';
        return preg_replace($pattern, '', $this->getPathInfo());
    }

    /**
     * Extended to fix a problem where explicitly setting the
     * path to an empty string always re-generated the path from
     * server/environment variables.
     *
     * @return  string  everything between the BaseUrl and QueryString.
     */
    public function getPathInfo()
    {
        if ($this->_pathInfo === null) {
            $this->setPathInfo();
        }

        return $this->_pathInfo;
    }
}
