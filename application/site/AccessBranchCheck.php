<?php
/**
 * A controller plugin to handle the access branch acl check.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Site_AccessBranchCheck extends Zend_Controller_Plugin_Abstract
{
    protected   $_hasChecked = false;

    /**
     * On the very first route shutdown, we check if the current user has
     * permission to access this branch. We do this after the route has
     * been determined because we want to allow anonymous users to reach
     * certain actions (so that they can authenticate).
     *
     * @param   Zend_Controller_Request_Abstract    $request    the request being routed.
     * @return  void
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        // we only check once per request.
        if ($this->_hasChecked) {
            return;
        }
        $this->_hasChecked = true;

        // don't allow access to a branch via a invalid branch specifier.
        // mostly this just prevents the user from thinking they are accessing
        // the '-foo-' branch when in fact that branch doesn't exist and they
        // are seeing the '-live-' branch as a fallback.
        if (P4Cms_Site::hasActive()
            && $request instanceof P4Cms_Controller_Request_Http
            && $request->getBranchName()
            && $request->getBranchName() !== P4Cms_Site::fetchActive()->getBranchBasename()
        ) {
            throw new P4Cms_PageNotFoundException;
        }

        // if we don't have an active user we must be testing or
        // have otherwise bypassed proper bootstrap, bail out.
        if (!P4Cms_User::hasActive()) {
            return;
        }

        // we don't enforce the access branch permission on the
        // login and switch branch actions (otherwise users could
        // never get to this branch).
        $user    = P4Cms_User::fetchActive();
        $allowed = array('user/index/login', 'site/branch/switch');
        $action  = $request->getModuleName() . '/'
                 . $request->getControllerName() . '/'
                 . $request->getActionName();
        if ($user->isAnonymous() && in_array($action, $allowed)) {
            return;
        }

        // don't enforce the access branch permission if the
        // branch resource doesn't exist in the acl table.
        // (e.g. running initial setup, or outdated acl)
        if (!P4Cms_Acl::fetchActive()->has('branch')) {
            return;
        }

        // verify that the user has permission to access this branch.
        if (!$user->isAllowed('branch', 'access')) {
            throw new P4Cms_AccessDeniedException(
                "You do not have permission to access this branch."
            );
        }
    }
}