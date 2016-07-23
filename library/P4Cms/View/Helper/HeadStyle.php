<?php
/**
 * Extends the head style helper to add media queries support.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_HeadStyle extends Zend_View_Helper_HeadStyle
{
    /**
     * Convert content and attributes into valid style tag
     *
     * We add the item's media value to _mediaTypes and then call the parent method.
     * This way, the media type validation in the parent is ignored.
     *
     * @param   stdClass    $item   Item to render
     * @param   string      $indent Indentation to use
     * @return  string
     */
    public function itemToString(stdClass $item, $indent)
    {
        $value = isset($item->attributes['media']) ? $item->attributes['media'] : '';
        if (false === strpos($value, ',')) {
            $this->_mediaTypes[] = $value;
        } else {
            foreach (explode(',', $value) as $type) {
                $this->_mediaTypes[] = trim($type);
            }
        }
        
        return parent::itemToString($item, $indent);
    }
}
