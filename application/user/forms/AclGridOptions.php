<?php
/**
 * This is the acl grid options form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class User_Form_AclGridOptions extends Ui_Form_GridOptions
{
    protected $_acl = null;

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
     * Get the ACL instance in use by this form.
     *
     * @return  P4Cms_Acl               the acl in use by the form.
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
}
