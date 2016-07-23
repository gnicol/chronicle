<?php
/**
 * Derivative of dojo form view helper. Makes it possible to change the form
 * dojo type by setting dojoType in attribs.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_Form extends Zend_Dojo_View_Helper_Form
{
    /**
     * Dijit being used
     * @var string
     */
    protected $_dijit  = 'p4cms.ui.Form';

    /**
     * Module being used
     * @var string
     */
    protected $_module = 'p4cms.ui.Form';

    /**
     * Take dijit (dojoType) from attribs if specified and dijit arg is null.
     *
     * @param  array  $attribs Array of HTML attributes
     * @param  array  $params  Parameters to pass along to parent
     * @param  string $type    Type to pass along to parent
     * @param  string $dijit   Dijit type to use (otherwise, pull from attribs, then $_dijit)
     * @return array
     */
    protected function _prepareDijit(array $attribs, array $params, $type, $dijit = null)
    {
        if ($dijit === null && isset($attribs['dojoType'])) {
            $dijit = $attribs['dojoType'];
        }

        return parent::_prepareDijit($attribs, $params, $type, $dijit);
    }
}
