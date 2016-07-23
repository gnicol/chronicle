<?php
/**
 * Extended P4Cms_User class to provide granting additional privileges on the fly.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Cron_Model_User extends P4Cms_User
{
    protected   $_allowed = array();

    /**
     * Extended parent to also consider additionally granted access when evaluating if this user is
     * allowed access to the given resource.
     *
     * @param   P4Cms_Acl_Resource|string           $resource   the resource to check access to.
     * @param   P4Cms_Acl_Privilege|string|null     $privilege  optional - the privilege to check.
     * @param   P4Cms_Acl|null                      $acl        optional - the acl to check against.
     *                                                          defaults to the currently active acl.
     * @return  bool    true if the user is allowed access to the resource.
     */
    public function isAllowed($resource, $privilege = null, P4Cms_Acl $acl = null)
    {
        $resourceId = $resource instanceof Zend_Acl_Resource_Interface
            ? $resource->getResourceId()
            : $resource;

        // check for special (runtime) access.
        foreach ($this->_allowed as $allowed) {
            if ($allowed['resource'] !== $resourceId) {
                continue;
            }
            if (!$allowed['privilege'] || in_array($privilege, $allowed['privilege'])) {
                return true;
            }
        }

        return parent::isAllowed($resource, $privilege, $acl);
    }

    /**
     * Grant additonal access to a given resource and (optionally) a given privilege on
     * the resource for this user.
     *
     * @param   string              $resource   resource to grant access for
     * @param   string|array|null   $privilege  resource privilege to grant access for
     * @return  Cron_Model_User     provides fluent interface
     */
    public function allow($resource, $privilege = null)
    {
        $this->_allowed[] = array('resource' => $resource, 'privilege' => (array) $privilege);

        return $this;
    }
}