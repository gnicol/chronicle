<?php
/**
 * Derivative of dojo view helper that renders dijits.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_CustomDijit extends Zend_Dojo_View_Helper_CustomDijit
{
    /**
     * Create a layout container
     *
     * @param  int         $id      Identifier of container
     * @param  string      $content Content to display
     * @param  array       $params  Parameters for container
     * @param  array       $attribs HTML attributes to set
     * @param  string|null $dijit   Optional Dijit to use
     * @return string
     */
    protected function _createLayoutContainer($id, $content, array $params, array $attribs, $dijit = null)
    {
        if (!array_key_exists('id', $attribs)) {
            $attribs['id'] = $id;
        }
        $attribs = $this->_prepareDijit($attribs, $params, 'layout', $dijit);

        $nodeType = $this->getRootNode();
        $html = '<' . $nodeType . $this->_htmlAttribs($attribs) . '>'
              . $content
              . "</$nodeType>\n";

        return $html;
    }
}
