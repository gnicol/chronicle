<?php
/**
 * Derivative of dojo button view helper. Allows to use SingleClickButton dijit if
 * 'singleClick' attribute is set to true.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_Button extends Zend_Dojo_View_Helper_Button
{
    /**
     * Overrides parent method to use p4cms.ui.SlowButton dijit instead of
     * standard form button dijit if 'preventMultiClick' attribute is set
     * to true.
     *
     * @param  string   $id         Zend provides no documentation.
     * @param  string   $value      Zend provides no documentation.
     * @param  array    $params     Parameters to use for dijit creation.
     * @param  array    $attribs    HTML attributes.
     * @return string               Zend provides no documentation.
     */
    public function button($id, $value = null, array $params = array(), array $attribs = array())
    {
        // if singleClick attrib is set to true use SingleClickButton dijit
        if (isset($attribs['singleClick']) && $attribs['singleClick']) {
            $this->_dijit  = 'p4cms.ui.SingleClickButton';
            $this->_module = 'p4cms.ui.SingleClickButton';
            unset($attribs['singleClick']);
        }

        return parent::button($id, $value, $params, $attribs);
    }
}
