<?php
/**
 * Override content's DisplayImage decorator to do the following:
 *  - set 'noSize' and 'asBackground' options to be true by default if they are not provided
 *  - add javascript to copy orientation ('portrait', 'landscape' or 'square') from the
 *    image container into the parent p4cms.content.Element container (if there is one)
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Theme_Decorator_DisplayImage extends Content_Form_Decorator_DisplayImage
{
    /**
     * Overrides content module's image decorator for tablet theme.
     *
     * @param   string  $label      the label to include in the tag.
     * @param   array   $params     the paramaters to provide to the Uri function
     * @return  string  the rendered html tag.
     */
    protected function _renderHtmlTag($label, $params)
    {
        // if not provided, set 'noSize' and 'asBackground' options to true by default
        $noSize = $this->getOption('noSize');
        $asBg   = $this->getOption('asBackground');
        $this->setOption('noSize',       $noSize !== null ? $noSize : true);
        $this->setOption('asBackground', $asBg   !== null ? $asBg   : true);

        $html = parent::_renderHtmlTag($label, $params);

        // add javascript to capture the orientation of the image and apply it
        // as a class on the containing list-item (if in a multi-list) or the
        // wrapping content-element dijit if there is only one list item.
        $html .= "<script type='text/javascript'>"
               . "dojo.query('.image[orientation]').forEach(function(image){"
               . "    var ul      = new dojo.NodeList(image).closest('ul.content-list')[0];"
               . "    var li      = new dojo.NodeList(image).closest('ul.content-list li')[0];"
               . "    var count   = ul && dojo.query('li', ul).length;"
               . "    var element = new dojo.NodeList(image).closest('.content-element')[0];"
               . "    if (count === 1 && element) {"
               . "        dojo.removeClass(element, ['portrait', 'landscape', 'square']);"
               . "        dojo.addClass(element, dojo.attr(image, 'orientation'));"
               . "    }"
               . "    if (li) {"
               . "        dojo.removeClass(li, ['portrait', 'landscape', 'square']);"
               . "        dojo.addClass(li, dojo.attr(image, 'orientation'));"
               . "    }"
               . "});"
               . "</script>";

        return $html;
    }
}