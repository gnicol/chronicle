<?php
/**
 * View helper for escaping untrusted data before inserting them into a javascript data values.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_EscapeJs extends P4Cms_View_Helper_EscapeAbstract
{
    protected $_safeChars = array(',', '.', '_');

    /**
     * Returns escaped value that can be safely inserted into a javascript data value.
     *
     * Untrusted data should be escaped before inserting them
     * into html javascript data values:
     *
     *  <script>alert('...ESCAPE_JS...')</script>     inside a quoted string
     *  <script>x='...ESCAPE_JS...'</script>          one side of a quoted expression
     *  <div onmouseover="x='...ESCAPE_JS...'"</div>  inside quoted event handler
     *
     * @param   string  $value      Value to escape.
     * @return  string              Escaped value safe to insert into a javascript data value.
     */
    public function escapeJs($value)
    {
        return $this->_encode($value);
    }

    /**
     * Convert character represented by its ordinal value into
     * \xHH if ordinal value less than 256 or into
     * \uHHHH if ordinal value > 255.
     *
     * @param int $ordinalValue     Ordinal value to format.
     */
    protected function _format($ordinalValue)
    {
        $hex = strtoupper(dechex($ordinalValue));
        if ($ordinalValue < 256) {
            $padLength = 2;
            $prefix    = "\\x";
        } else {
            $padLength = 4;
            $prefix    = "\\u";
        }
        return $prefix . str_pad($hex, $padLength, "0", STR_PAD_LEFT);
    }
}
