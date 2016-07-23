<?php
/**
 * This is the Site/Branch grid options form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Site_Form_BranchGridOptions extends Ui_Form_GridOptions
{
    protected $_items = null;

    /**
     * Set the site/branch items for this form.
     *
     * @param P4Cms_Model_Iterator|null     $items  the sites and branches for this grid
     */
    public function setItems(P4Cms_Model_Iterator $items = null)
    {
        $this->_items = $items;

        return $this;
    }

    /**
     * Return the list of sites/branches for this grid or null.
     *
     * @return P4Cms_Model_Iterator|null    site/branches or null
     */
    public function getItems()
    {
        return $this->_items;
    }
}
