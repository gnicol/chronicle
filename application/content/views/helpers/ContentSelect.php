<?php
/**
 * Renders a content select control. Allows the user to select one or more
 * content entries. The content select control is a dijit that supports
 * both single and multi-select (toggled via multiple = true/false).
 * Callers can also specify browse options to influence the content select
 * dialog that appears when the browse button is clicked.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        browse options not json encoding
 */
class Content_View_Helper_ContentSelect extends Zend_Dojo_View_Helper_Dijit
{
    /**
     * Dijit being used
     * @var string
     */
    protected $_dijit  = 'p4cms.content.ContentSelect';

    /**
     * Dojo module to use
     * @var string
     */
    protected $_module = 'p4cms.content.ContentSelect';

    /**
     * Render the content select markup needed to declare the dijit.
     *
     * @param   string  $id       Zend provides no documentation for this param.
     * @param   string  $value    Zend provides no documentation for this param.
     * @param   array   $params   Zend provides no documentation for this param.
     * @param   array   $attribs  Zend provides no documentation for this param.
     * @return  string
     */
    public function contentSelect($id, $value = null, $params = array(), $attribs = array())
    {
        $attribs['name'] = $id;
        if (!array_key_exists('id', $attribs)) {
            $attribs['id'] = $id;
        }

        // normalize values to an array of arrays - each with an 'id'
        $value = Content_Form_Element_ContentSelect::normalizeValue($value);

        // lookup entry data (titles, content types) for selected content ids.
        if ($value) {
            $params['entryData'] = array();
            $entries             = P4Cms_Content::fetchAll(
                array(
                    'ids'         => Content_Form_Element_ContentSelect::extractIds($value),
                    'limitFields' => array('title', 'contentType')
                )
            );

            foreach ($entries as $entry) {
                $params['entryData'][$entry->getId()] = array(
                    'title'     => $entry->getTitle(),
                    'type'      => $entry->getContentTypeId()
                );
            }
        }

        // flatten the browse options, otherwise there are issues with
        // the multidimensional associative array in javascript
        if (array_key_exists('browseOptions', $attribs)) {
            $filter = new P4Cms_Filter_FlattenArray;
            $params['browseOptions'] = $filter->filter($attribs['browseOptions']);
        }

        // json-encode select parameters - prepare dijit is supposed to do this
        // but it corrupts values because it does some very silly quote mangling.
        $jsonParams            = array('browseOptions', 'multiple', 'selected', 
                                       'entryData', 'validTypes', 'extraFields');
        $params['selected']    = $value;
        $params['validTypes']  = isset($attribs['validTypes'])  ? $attribs['validTypes']  : null;
        $params['extraFields'] = isset($attribs['extraFields']) ? $attribs['extraFields'] : null;
        foreach ($jsonParams as $param) {
            if (array_key_exists($param, $params)) {
                $params[$param] = Zend_Json::encode($params[$param]);
            }
        }

        // get the attributes ready for use in a dijit
        $attribs = $this->_prepareDijit($attribs, $params, 'element');

        return "<div " . $this->_htmlAttribs($attribs) . "></div>";
    }
}
