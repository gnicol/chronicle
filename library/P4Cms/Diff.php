<?php
/**
 * Use Paul Butler's Simple Diff Algorithm
 * https://github.com/paulgb/simplediff
 */
defined('LIBRARY_PATH')
    or define('LIBRARY_PATH', dirname(__DIR__));
require_once LIBRARY_PATH . '/simplediff/simplediff.php';

/**
 * Object oriented interface to Paul Butler's simple diff.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Diff
{
    /**
     * Compare two values using the most appropriate comparison
     * method for the value type. If the values are of differing
     * types, the left value will be cast to the type of the right.
     *
     * @param   mixed               $left       the left-hand input
     * @param   mixed               $right      the right-hand input
     * @param   P4Cms_Diff_Options  $options    options to augment comparison behavior.
     * @return  P4Cms_Diff_Result   list of differences.
     */
    public function compare($left, $right, P4Cms_Diff_Options $options = null)
    {
        // normalize options.
        $options = !is_null($options) ? $options : new P4Cms_Diff_Options;
        
        // cast unsupported types to string.
        $types = array('array', 'string');
        $left  = !in_array(gettype($left),  $types) ? (string) $left  : $left;
        $right = !in_array(gettype($right), $types) ? (string) $right : $right;

        // ensure left/right types are the same.
        settype($left, gettype($right));
        
        // use appropriate comparison function.
        switch (gettype($right))
        {
            case 'string':
                if ($options->isBinaryDiff()) {
                    return $this->compareBinaries($left, $right, $options);
                } else {
                    return $this->compareStrings($left, $right, $options);
                }
            case 'array':
                return $this->compareArrays($left, $right, $options);
        }
    }

    /**
     * Compare two arrays.
     *
     * @param   array               $left       the left-hand array
     * @param   array               $right      the right-hand array
     * @param   P4Cms_Diff_Options  $options    options to augment comparison behavior.
     * @return  P4Cms_Diff_Result   list of differences
     */
    public function compareArrays(array $left, array $right, P4Cms_Diff_Options $options = null)
    {
        // normalize options.
        $options = !is_null($options) ? $options : new P4Cms_Diff_Options;

        // run simplediff.
        $result = diff($left, $right);

        // simplediff seems to report empty difference blocks
        // (no deletion, no insertion), filter out these artifacts.
        $artifact = array('d' => array(), 'i' => array());
        $result   = array_filter(
            $result,
            function($diff) use ($artifact)
            {
                return $diff !== $artifact;
            }
        );
        
        return new P4Cms_Diff_Result($result, $options);
    }

    /**
     * Compare two strings. Splits on lines by default.
     *
     * @param   string              $left       the left-hand string
     * @param   string              $right      the right-hand string
     * @param   P4Cms_Diff_Options  $options    options to augment comparison behavior.
     * @return  P4Cms_Diff_Result   list of differences
     */
    public function compareStrings($left, $right, P4Cms_Diff_Options $options = null)
    {
        // normalize options.
        $options = !is_null($options) ? $options : new P4Cms_Diff_Options;

        // determine what pattern and flags to use to split input strings.
        $args  = is_array($options->getSplitArgs())
            ? $options->getSplitArgs()
            : array(P4Cms_Diff_Options::PATTERN_LINES, 0);

        $left  = preg_split($args[0], $left, -1, $args[1]);
        $right = preg_split($args[0], $right, -1, $args[1]);

        return $this->compareArrays($left, $right, $options);
    }

    /**
     * Compare two binary values. Unlike compare strings, the left/right
     * values are not exploded prior to calling compareArrays().
     *
     * @param   string              $left       the left-hand binary string
     * @param   string              $right      the right-hand binary string
     * @param   P4Cms_Diff_Options  $options    options to augment comparison behavior.
     * @return  P4Cms_Diff_Result   list of differences
     */
    public function compareBinaries($left, $right, P4Cms_Diff_Options $options = null)
    {
        return $this->compareArrays(
            strlen($left)  ? array($left)  : array(),
            strlen($right) ? array($right) : array(),
            $options
        );
    }

    /**
     * Compare two fielded models. Walks over every field in each model
     * and compares the values. Puts results in a diff result collection.
     *
     * @param   P4Cms_Model                     $left       the left hand model
     * @param   P4Cms_Model                     $right      the right-hand model
     * @param   P4Cms_Diff_OptionsCollection    $options    per-field options to augment compare.
     * @return  P4Cms_Diff_ResultCollection     collection with one diff result per field.
     */
    public function compareModels(
        P4Cms_Model $left,
        P4Cms_Model $right,
        P4Cms_Diff_OptionsCollection $options = null)
    {
        // normalize options to a collection.
        $options = !is_null($options) ? $options : new P4Cms_Diff_OptionsCollection;

        // get list of all unique fields across models.
        $fields = array_unique(array_merge($left->getFields(), $right->getFields()));

        // compare each field value.
        $results = array();
        foreach ($fields as $field) {

            // normalize field options.
            $fieldOptions = isset($options[$field]) 
                ? $options[$field]
                : new P4Cms_Diff_Options;

            // skip diffing undesired fields
            if ($fieldOptions->isSkipped()) {
                continue;
            }

            // do the compare.
            $results[$field] = $this->compare(
                $left->hasField($field)  ? $left->getValue($field)  : null,
                $right->hasField($field) ? $right->getValue($field) : null,
                $fieldOptions
            );
        }

        return new P4Cms_Diff_ResultCollection($results, $options);
    }
}
