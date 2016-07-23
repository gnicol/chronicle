<?php
/**
 * Check if the active user owns the given content resource.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Acl_Assert_IsOwner implements Zend_Acl_Assert_Interface
{
    /**
     * Checks if the active user owns the given content resource.
     *
     * @param  Zend_Acl                    $acl         the acl instance
     * @param  Zend_Acl_Role_Interface     $role        the role to check access for
     * @param  Zend_Acl_Resource_Interface $resource    the resource (should be content/*)
     * @param  string                      $privilege   the privilege (should be edit)
     * @return boolean  true if the active user owns the given content resource.
     */
    public function assert(
        Zend_Acl $acl,
        Zend_Acl_Role_Interface $role = null,
        Zend_Acl_Resource_Interface $resource = null,
        $privilege = null)
    {
        // early exit if resource doesn't match content/*
        if (!preg_match('#^content/(.+)$#', $resource->getResourceId(), $matches)) {
            return false;
        }

        // grab active user, or return false if no named active user.
        if (P4Cms_User::hasActive() && !P4Cms_User::fetchActive()->isAnonymous()) {
            $user = P4Cms_User::fetchActive();
        } else {
            return false;
        }

        // fetch content entry, or return false if entry does not exist.
        $id      = $matches[1];
        $options = array('includeDeleted' => true);
        if (P4Cms_Content::exists($id, $options)) {
            $entry = P4Cms_Content::fetch($id, $options);
        } else {
            return false;
        }

        // check if user is owner of content entry.
        if ($entry->getOwner() == $user->getId()) {
            return true;
        } else {
            return false;
        }
    }
}
