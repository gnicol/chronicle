<?php
/**
 * This is the add user form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class User_Form_EditRole extends User_Form_AddRole
{
    protected $_uniqueIdRequired = false;

    /**
     * Defines the elements that make up the role form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        parent::init();
        
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui role-form role-edit-form');

        // disable changing role name
        $this->getElement('id')
             ->setAttrib('disabled', true);
    }
}
