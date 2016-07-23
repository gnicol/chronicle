<?php
/**
 * View helper for escaping untrusted data before inserting them into an html common attributes.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_EscapeAttr extends P4Cms_View_Helper_EscapeAbstract
{
    protected $_safeChars = array(',', '.', '-', '_');

    /**
     * Extend constructor to initilize list of illegal characters.
     */
    public function __construct()
    {
        // set list with illegal characters represented by their ordinal numbers
        for ($i = 1; $i < 32; $i++) {
            if ($i != 9     // tab
                && chr($i) != "\n"
                && chr($i) != "\r"
            ) {
                $this->_illegalOrd[] = $i;
            }
        }

        parent::__construct();
    }

    /**
     * Returns escaped value that can be safely inserted into an html common attribute.
     *
     * Untrusted data should be escaped before inserting them
     * into html common attributes:
     *
     *  <div attr=...ESCAPE_ATTR...>content</div>     inside unquoted attribute
     *  <div attr='...ESCAPE_ATTR...'>content</div>   inside single quoted attribute
     *  <div attr="...ESCAPE_ATTR...">content</div>   inside double quoted attribute
     *
     * This should not be used for complex attributes like href,
     * src, style, or any of the event handlers like onmouseover.
     *
     * @param   string  $value      Value to escape.
     * @return  string              Escaped value safe to insert into an html attribute.
     */
    public function escapeAttr($value)
    {
        return $this->_encode($value);
    }

    /**
     * Convert character represented by its ordinal value into &#xHH; format.
     *
     * @param int $ordinalValue     Ordinal value to format.
     */
    protected function _format($ordinalValue)
    {
        $hex = dechex($ordinalValue);
        return '&#x' . $hex . ';';
    }
}
