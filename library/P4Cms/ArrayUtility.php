<?php
/**
 * Provides utility methods for manipulating arrays.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_ArrayUtility
{
    /**
     * Private constructor to prevent instances from being created.
     *
     * @codeCoverageIgnore
     */
    final private function __construct()
    {
    }

    /**
     * Compute the difference between two arrays.
     *
     * @param  array  $old  The original array.
     * @param  array  $new  The new array.
     * @return  list  $additions - An array of the items added to $old by $new
     *                $removals - An array of the items removed from $old by $new
     */
    public static function computeDiff($old, $new)
    {
        $removals = array_merge(array_diff($old, $new));
        $additions = array_merge(array_diff($new, $old));
        return array($additions, $removals);
    }

    /**
     * Merge two arrays recursively. Unlike array_merge_recursive, scalar values
     * will be overwritten, rather than converted into arrays, when they collide.
     *
     * @param   array   $array1     the first array
     * @param   array   $array2     the second array (has precedence)
     * @return  array   the merged result of array1 and array1.
     */
    public static function mergeRecursive(array $array1, array $array2)
    {
        if (is_array($array2)) {
            foreach ($array2 as $key => $val) {
                if (is_array($array2[$key])) {
                    $array1[$key] = (array_key_exists($key, $array1) && is_array($array1[$key]))
                                  ? static::mergeRecursive($array1[$key], $array2[$key])
                                  : $array2[$key];
                } else {
                    $array1[$key] = $val;
                }
            }
        }
        return $array1;
    }
}
