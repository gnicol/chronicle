<?php
/**
 * Proxy for an acl assert class that might not exist.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Acl_Assert_Proxy implements Zend_Acl_Assert_Interface
{
    protected   $_assertClass    = null;

    /**
     * Create a new acl assert proxy.
     *
     * @param   string  $assertClass    the name of the class to proxy for.
     */
    public function __construct($assertClass)
    {
        $this->_assertClass = $assertClass;
    }

    /**
     * Tries to instantiate the assertion class we are proxying for.
     * If the class exists and implements the assertion interface,
     * returns result of assert(); otherwise returns false.
     *
     * @param  Zend_Acl                    $acl         the acl instance
     * @param  Zend_Acl_Role_Interface     $role        the role to check access for
     * @param  Zend_Acl_Resource_Interface $resource    the resource.
     * @param  string                      $privilege   the privilege.
     * @return boolean  true if the assertion class exists and asserts true.
     */
    public function assert(
        Zend_Acl $acl,
        Zend_Acl_Role_Interface $role = null,
        Zend_Acl_Resource_Interface $resource = null,
        $privilege = null)
    {
        // check if assert class we are proxying for exists.
        if (!class_exists($this->_assertClass)) {
            return false;
        }

        // instantiate the assertion
        $assertClass = new $this->_assertClass;

        // verify class implements assertion interface.
        if (!$assertClass instanceof Zend_Acl_Assert_Interface) {
            return false;
        }

        return $assertClass->assert($acl, $role, $resource, $privilege);
    }
}
