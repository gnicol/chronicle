<?php
/**
 * Derivative of dojo submitButton view helper. Uses SingleClickButton dijit unless
 * 'singleClick' attribute is set to false.
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_SubmitButton extends Zend_Dojo_View_Helper_SubmitButton
{
    /**
     * Overrides parent method to use p4cms.ui.SingleClickButton dijit by default.
     * To use default submitButton dijit, 'singleClick' attribute must be set to false.
     * 
     * @param  string   $id         Zend provides no documentation.
     * @param  string   $value      Zend provides no documentation.
     * @param  array    $params     Parameters to use for dijit creation.
     * @param  array    $attribs    HTML attributes.
     * @return string               Zend provides no documentation.
     */
    public function submitButton($id, $value = null, array $params = array(), array $attribs = array())
    {
        // use SingleClickButton dijit as long as singleClick attrib is not set or is set to true
        if (!isset($attribs['singleClick']) || $attribs['singleClick']) {
            $this->_dijit  = 'p4cms.ui.SingleClickButton';
            $this->_module = 'p4cms.ui.SingleClickButton';
            unset($attribs['singleClick']);
        }

        return parent::submitButton($id, $value, $params, $attribs);
    }
}
