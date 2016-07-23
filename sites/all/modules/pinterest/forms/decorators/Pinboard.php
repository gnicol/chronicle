<?php
/**
 * Renders the value of an element as a pinboard.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Pinterest_Form_Decorator_Pinboard extends Zend_Form_Decorator_Abstract
{
    /**
     * Render out the contents of the specified pinboard.
     *
     * @param   string  $content  The id of the pinboard to render.
     * @return  string
     */
    public function render($content)
    {
        $element = $this->getElement();
        $value   = trim($element->getValue(), '/ ');

        // return early if we don't have a valid looking user/board
        if ($value && !preg_match('#^[a-z0-9\-_]+/[a-z0-9\-_]+$#', $value)) {
            return "Pinboard IDs must be in the format 'user/board' and can only contain A-Z, 0-9, /, - and _";
        }

        // get the pins from pinterest's rss feed for the specified user/board
        try {
            $feed = $value
                ? Zend_Feed_Reader::import('http://pinterest.com/' . $value . '/rss')
                : array();
        } catch (Zend_Feed_Exception $e) {
            return "The specified pinboard does not exist.";
        }

        $result = "";
        foreach ($feed as $entry) {
            $result .= "<div class='pin'>";

            // extract the description and absolutize urls
            // we are being quite trusting that pinterest won't XSS us here
            $description = $entry->getDescription();
            $description = preg_replace("#(href|src)=(.)/#", "$1=$2http://pinterest.com/", $description);
            $result     .= $description . "</div>";
        }

        return $result;
    }
}
