<?php
/**
 * Format a passed filesize in a friendlier manner.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_FileSize extends Zend_Dojo_View_Helper_Dojo
{
    /**
     * Filesize suffixes
     * @var array
     */
    protected static $_suffixes = array('', 'K', 'M', 'G', 'T', 'P');

    /**
     * Takes the passed filesize and converts it to a friendlier format,
     * such as:
     * 12k
     * 100M
     *
     * @param   string|int  $size       the value to format
     * @param   int         $precision  precision of the result; defaults to 2
     * @return  string                  the formatted string.
     */
    public function fileSize($size, $precision = 2)
    {
        $result = $size;
        $index = 0;
        while ($result > 1024 && $index++ < count(static::$_suffixes)) {
            $result = $result / 1024;
        }

        // no fractions for less than 1K
        if ($index == 0) {
            return sprintf('%d B', $result);
        }

        return sprintf('%1.' . $precision . 'f %sB', $result, static::$_suffixes[$index]);
    }
}
