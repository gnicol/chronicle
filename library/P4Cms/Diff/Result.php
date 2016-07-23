<?php
/**
 * Wrapper for result of simplediff.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Diff_Result
{
    protected   $_result    = null;
    protected   $_options   = null;

    /**
     * Create a new diff result from simplediff output.
     *
     * @param   array               $result     output from simplediff.
     * @param   P4Cms_Diff_Options  $options    original options given to compare.
     */
    public function __construct(array $result, P4Cms_Diff_Options $options)
    {
        $this->_result  = $result;
        $this->_options = $options;
    }

    /**
     * Access to raw simplediff result array.
     */
    public function getRawResult()
    {
        if (!is_array($this->_result)) {
            throw new P4Cms_Diff_Exception(
                "Cannot get results. Results have not been set."
            );
        }

        return $this->_result;
    }

    /**
     * Count all differences.
     *
     * @return  int     the count of all differences
     */
    public function getDiffCount()
    {
        return count(array_filter($this->getRawResult(), 'is_array'));
    }

    /**
     * Check if there are differences.
     *
     * @return  bool    true if there are diffs.
     */
    public function hasDiffs()
    {
        return (bool) $this->getDiffCount();
    }

    /**
     * Check if the only changes are whitespace changes.
     *
     * @param   bool    $semantic   optional - defaults to true - consider semantic
     *                              changes (e.g. splitting one word or joining two)
     *                              a non-whitespace change.
     */
    public function isWhitespaceChange($semantic = true)
    {
        // if no diffs, can't be whitespace change.
        if (!$this->hasDiffs()) {
            return false;
        }

        // check each diff chunk.
        foreach ($this->getDiffChunks() as $chunk) {
            if (!$chunk->isWhitespaceChange($semantic)) {
                return false;
            }
        }

        // all diff chunks are whitespace changes.
        return true;
    }

    /**
     * Get all value chunks. Includes values that are the same
     * between left and right as well those that differ.
     *
     * @return  array   list of value chunks (P4Cms_Diff_Chunk instances)
     */
    public function getChunks()
    {
        // convert raw result into chunks.
        $chunks = array();
        foreach ($this->getRawResult() as $rawChunk) {

            // accumulate consecutive unchanged values in a single chunk.
            $chunk = end($chunks);
            if ($chunk && $chunk->isSame() && is_string($rawChunk)) {
                $values   = $chunk->getRawValues();
                $values[] = $rawChunk;
                $chunk->setRawValues($values);
            } else {
                $chunks[] = new P4Cms_Diff_Chunk((array) $rawChunk);
            }

        }

        return $chunks;
    }

    /**
     * Get only diff chunks (blocks of values where left and right differ).
     *
     * @return  array   list of differing value chunks
     */
    public function getDiffChunks()
    {
        return array_filter(
            $this->getChunks(),
            function($chunk)
            {
                return !$chunk->isSame();
            }
        );
    }

    /**
     * Get the original options that produced this result
     * (ie. the options passed to the compare method).
     *
     * @return  P4Cms_Diff_Options  the original options.
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Get a specific option from the original options.
     *
     * @param   string  $name   the name of the option to get.
     * @return  mixed   the value of the option
     */
    public function getOption($name)
    {
        return $this->getOptions()->getOption($name);
    }
}
