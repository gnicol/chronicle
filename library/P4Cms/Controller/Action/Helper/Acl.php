<?php
/**
 * Provides convenient access to acl facilities from controllers.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Controller_Action_Helper_Acl extends Zend_Controller_Action_Helper_Abstract
{
    protected   $_acl   = null;

    /**
     * Provide easy access to the helper from the controller.
     */
    public function preDispatch()
    {
        $this->getActionController()->acl = $this;
    }

    /**
     * Set the ACL instance to use.
     *
     * @param   P4Cms_Acl   $acl    the acl instance to use.
     */
    public function setAcl(P4Cms_Acl $acl)
    {
        $this->_acl = $acl;
    }

    /**
     * Get the ACL instance in use by this helper.
     *
     * @return  P4Cms_Acl               the acl in use by the helper.
     * @throws  P4Cms_Acl_Exception     if no acl has been set.
     */
    public function getAcl()
    {
        if (!$this->_acl instanceof P4Cms_Acl) {
            throw new P4Cms_Acl_Exception(
                "Cannot get acl. No acl has been set."
            );
        }

        return $this->_acl;
    }

    /**
     * Verify that the user is allowed access to the given
     * resource/privilege, throws an exception if not allowed.
     *
     * @param   P4Cms_Acl_Resource|string       $resource   the resource to verify access to.
     * @param   P4Cms_Acl_Privilege|string|null $privilege  the privilege to verify access to.
     * @param   P4Cms_User|null                 $user       optional - the user to check access for
     *                                                      defaults to the current active user.
     * @param   string|null                     $msg        optional - custom message for thrown
     *                                                      P4Cms_AccessDeniedException exception.
     * @param   P4Cms_Acl|null                  $acl        optional - the acl to check access against.
     * @throws  P4Cms_AccessDeniedException                 if user is not allowed access to the
     *                                                      resource.
     */
    public function check($resource, $privilege = null, P4Cms_User $user = null, $msg = null, P4Cms_Acl $acl = null)
    {
        if (!$this->isAllowed($resource, $privilege, $user, $acl)) {
            throw new P4Cms_AccessDeniedException(
                $msg ?: "You do not have permission to: $privilege/$resource."
            );
        }
    }

    /**
     * Determine if the user is allowed access to the given resource/privilege
     *
     * @param   P4Cms_Acl_Resource|string       $resource   the resource to verify access to.
     * @param   P4Cms_Acl_Privilege|string|null $privilege  the privilege to verify access to.
     * @param   P4Cms_User|null                 $user       optional - the user to check access for
     *                                                      defaults to the current active user.
     * @param   P4Cms_Acl|null                  $acl        optional - the acl to check access against.
     * @return  bool    true if the user is allowed access; false otherwise.
     */
    public function isAllowed($resource, $privilege = null, P4Cms_User $user = null, P4Cms_Acl $acl = null)
    {
        $acl  = $acl  ?: $this->getAcl();
        $user = $user ?: P4Cms_User::fetchActive();

        return $user->isAllowed($resource, $privilege, $acl);
    }
}
