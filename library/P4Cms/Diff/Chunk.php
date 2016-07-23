<?php
/**
 * Wrapper for chunks of values in a diff result.
 * Each chunk is either a consecutive block of
 * unchanged values, or a block of deleted/inserted
 * values.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Diff_Chunk
{
    const       TYPE_SAME       = 'same';
    const       TYPE_INSERT     = 'insert';
    const       TYPE_DELETE     = 'delete';
    const       TYPE_CHANGE     = 'change';
    
    const       SIDE_LEFT       = 'left';
    const       SIDE_RIGHT      = 'right';

    protected   $_values        = null;

    /**
     * Create a new chunk from a list of unchanged (flat) or
     * changed (multi-dimensional deleted/inserted) values.
     *
     * @param   array   $values     optional - flat array for chunks of unchanged values
     *                              multi-dimensional deleted/inserted array for chunks
     *                              of changed values.
     */
    public function __construct(array $values = null)
    {
        $this->_values = is_array($values) ? $values : array();
    }

    /**
     * Get the raw values array.
     * 
     * Flat for chunks of unchanged values, multi-dimensional
     * (deleted/inserted) for chunks of changed values.
     * 
     * @return  array   raw values in this chunk.
     */
    public function getRawValues()
    {
        return $this->_values;
    }

    /**
     * Set the raw values array.
     *
     * Flat for chunks of unchanged values, multi-dimensional
     * (deleted/inserted) for chunks of changed values.
     *
     * @param   array   $values     optional - flat array for chunks of unchanged values
     *                              multi-dimensional deleted/inserted array for chunks
     *                              of changed values.
     * @return  P4Cms_Diff_Chunk    provides fluent interface.
     */
    public function setRawValues(array $values)
    {
        $this->_values = $values;
    }

    /**
     * Get the left-hand values for this diff chunk.
     *
     * @param   null|int            $index  optional - the index of the value to get.
     * @return  array|string|null   if no index is specified, returns an array of
     *                              (zero or more) values - otherwise returns the
     *                              value at $index (null if no value at index).
     */
    public function getLeft($index = null)
    {
        return $this->_getSide(static::SIDE_LEFT, $index);
    }

    /**
     * Get the right-hand values for this diff chunk.
     *
     * @param   null|int            $index  optional - the index of the value to get.
     * @return  array|string|null   if no index is specified, returns an array of
     *                              (zero or more) values - otherwise returns the
     *                              value at $index (null if no value at index).
     */
    public function getRight($index = null)
    {
        return $this->_getSide(static::SIDE_RIGHT, $index);
    }

    /**
     * Get the highest number of values in left/right. Left and right
     * may have a varying number of values; this will return whichever
     * is greater.
     *
     * @return  int     the highest count of values in left/right.
     */
    public function getMaxValueCount()
    {
        return max(
            array(
                count($this->getLeft()),
                count($this->getRight())
            )
        );
    }

    /**
     * Get the type of this chunk: same, insert, delete, change.
     *
     * @return  string  the type of this chunk.
     */
    public function getChunkType()
    {
        $values = $this->_values;
        
        if (!isset($values['i'], $values['d'])) {
            return static::TYPE_SAME;
        }

        if (empty($values['d'])) {
            return static::TYPE_INSERT;
        }

        if (empty($values['i'])) {
            return static::TYPE_DELETE;
        }

        return static::TYPE_CHANGE;
    }

    /**
     * Check if this diff chunk is of type same.
     *
     * @return  bool    true if this is an same chunk; false otherwise.
     */
    public function isSame()
    {
        return $this->getChunkType() === static::TYPE_SAME;
    }

    /**
     * Check if this diff chunk is of type insert.
     *
     * @return  bool    true if this is an insert chunk; false otherwise.
     */
    public function isInsert()
    {
        return $this->getChunkType() === static::TYPE_INSERT;
    }

    /**
     * Check if this diff chunk is of type delete.
     *
     * @return  bool    true if this is an delete chunk; false otherwise.
     */
    public function isDelete()
    {
        return $this->getChunkType() === static::TYPE_DELETE;
    }

    /**
     * Check if this diff chunk is of type change.
     *
     * @return  bool    true if this is an change chunk; false otherwise.
     */
    public function isChange()
    {
        return $this->getChunkType() === static::TYPE_CHANGE;
    }

    /**
     * Check if this diff chunk a whitespace only change.
     *
     * @param   bool    $semantic   optional - defaults to true - consider semantic
     *                              changes (e.g. splitting one word or joining two)
     *                              a non-whitespace change.
     * @return  bool    true if this chunk only differs by whitespace.
     */
    public function isWhitespaceChange($semantic = true)
    {
        if ($this->isSame()) {
            return false;
        }

        // use the semantic flag to control whether we collapse
        // all whitespace or maintain word/line boundaries with a space.
        $semantic = $semantic ? " " : "";

        $left  = implode($semantic, $this->getLeft());
        $right = implode($semantic, $this->getRight());

        // normalize whitespace.
        $left  = trim(preg_replace("/\s*/", $semantic, $left));
        $right = trim(preg_replace("/\s*/", $semantic, $right));

        return $left == $right;
    }

    /**
     * Compare the left/right values at the specified index.
     * Useful for sub-line diffing. Splits on characters by default.
     * 
     * @param   int                 $index      the index of the left/right values to get.
     * @param   P4Cms_Diff_Options  $options    options to augment comparison behavior.
     * @return  P4Cms_Diff_Result   the result of the comparison.
     */
    public function getSubDiff($index, P4Cms_Diff_Options $options = null)
    {
        // normalize options.
        $options = !is_null($options) ? $options : new P4Cms_Diff_Options;

        // set default split args (split on chars).
        if (!$options->getSplitArgs()) {
            $options->setSplitArgs(
                P4Cms_Diff_Options::PATTERN_CHARS,
                PREG_SPLIT_NO_EMPTY
            );
        }

        $diff  = new P4CMs_Diff;
        $left  = $this->getLeft($index);
        $right = $this->getRight($index);

        return $diff->compare($left, $right, $options);
    }

    /**
     * Get the left or right-hand set of values for this diff chunk.
     *
     * @param   string              $side   left or right-hand side.
     * @param   null|int            $index  optional - the index of the value to get.
     * @return  array|string|null   if no index is specified, returns an array of
     *                              (zero or more) values - otherwise returns the
     *                              value at $index (null if no value at index).
     */
    protected function _getSide($side, $index = null)
    {
        $values = !$this->isSame()
            ? $this->_values[($side == static::SIDE_LEFT ? 'd' : 'i')]
            : $this->_values;

        if ($index !== null) {
            return isset($values[$index]) ? $values[$index] : null;
        }

        return $values;
    }
}
