<?php
/**
 * Check if the active user can edit the given content resource.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Acl_Assert_CanEdit implements Zend_Acl_Assert_Interface
{
    protected   $_privilege    = 'edit';
    protected   $_privilegeAll = 'edit-all';

    /**
     * Checks if the active user can edit the given content resource.
     * Exists to aggregate the edit-all and edit-own privileges.
     *
     * @param  Zend_Acl                    $acl         the acl instance
     * @param  Zend_Acl_Role_Interface     $role        the role to check access for
     * @param  Zend_Acl_Resource_Interface $resource    the resource (should be content/*)
     * @param  string                      $privilege   the privilege (should be edit)
     * @return boolean  true if the given role can edit the given content resource
     *                  false if not allowed or if resource/privilege are not content/edit.
     */
    public function assert(
        Zend_Acl $acl,
        Zend_Acl_Role_Interface $role = null,
        Zend_Acl_Resource_Interface $resource = null,
        $privilege = null)
    {
        // early exit if resource is not content or privilege is not edit.
        if (!preg_match('#^content(/.*)?$#', $resource->getResourceId())
            || $privilege !== $this->_privilege
        ) {
            return false;
        }

        // true if role is allowed to edit all content.
        if ($acl->isAllowed($role, 'content', $this->_privilegeAll)) {
            return true;
        }

        // true if role is allowed to edit-own and user owns this content.
        $isOwner = new Content_Acl_Assert_IsOwner;
        if ($acl->isAllowed($role, 'content', $this->_privilege . '-own')
            && $isOwner->assert($acl, $role, $resource, $privilege)
        ) {
            return true;
        } else {
            return false;
        }
    }
}
