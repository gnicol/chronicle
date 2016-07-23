<?php
/**
 * Wrapper for diff comparison options. Accepts arbitrary
 * name/value options to allow options to influence later
 * consumption of differences.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Diff_Options extends ArrayIterator
{
    const   SPLIT_ARGS      = 'splitArgs';
    const   SKIP_DIFF       = 'skipDiff';
    const   BINARY_DIFF     = 'binaryDiff';

    const   PATTERN_LINES   = '/\r\n|\n|\r/';
    const   PATTERN_CHARS   = '//u';
    
    /**
     * Set a named diff option.
     *
     * @param   string  $name       the name of the option to set.
     * @param   mixed   $value      the option value to set.
     * @return  P4Cms_Diff_Options  provides fluent interface.
     */
    public function setOption($name, $value)
    {
        $this->offsetSet($name, $value);

        return $this;
    }

    /**
     * Get a named diff option.
     *
     * @param   string  $name   the name of the option to get.
     * @return  mixed   the value of the named option - null if no such option.
     */
    public function getOption($name)
    {
        return $this->offsetExists($name) ? $this->offsetGet($name) : null;
    }

    /**
     * Set the preg split pattern and flags to use when comparing strings.
     *
     * @param   string      $pattern    a preg_split compatible pattern.
     * @param   int|null    $flags      optional - flags to pass to preg_split
     */
    public function setSplitArgs($pattern, $flags)
    {
        $this->setOption(
            static::SPLIT_ARGS,
            array($pattern, $flags)
        );
    }

    /**
     * Get the split pattern to use when comparing strings.
     *
     * @return  array  the pattern and flags to use with preg_split.
     */
    public function getSplitArgs()
    {
        return $this->getOption(static::SPLIT_ARGS);
    }

    /**
     * Skip this diff. Useful when comparing models with multiple fields.
     *
     * @param   bool    $skip   true to skip diff.
     */
    public function setSkipped($skip)
    {
        $this->setOption(static::SKIP_DIFF, $skip);
    }

    /**
     * Check if this diff is to be skipped.
     *
     * @return  bool    true if this diff should be skipped.
     */
    public function isSkipped()
    {
        return (bool) $this->getOption(static::SKIP_DIFF);
    }

    /**
     * Perform binary diff (no string splitting).
     *
     * @param   bool    $binary     true to do binary diff.
     */
    public function setBinaryDiff($binary)
    {
        $this->setOption(static::BINARY_DIFF, $binary);
    }

    /**
     * Check if this is a binary diff (no string splitting).
     *
     * @return  bool    true if this diff should be done as binary.
     */
    public function isBinaryDiff()
    {
        return (bool) $this->getOption(static::BINARY_DIFF);
    }
}
