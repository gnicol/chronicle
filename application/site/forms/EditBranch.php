<?php
/**
 * Form to edit site branches.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Site_Form_EditBranch extends Site_Form_Branch
{
    /**
     * Setup form to collect branch information.
     * Customise the parent label for the edit form.
     */
    public function init()
    {
        parent::init();
        
        $this->getElement('parent')->setLabel('Parent');
    }
}