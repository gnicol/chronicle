<?php
/**
 * Collection of named diff results.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Diff_ResultCollection extends ArrayIterator
{
    protected   $_options   = null;

    /**
     * Create a new diff result collection from array of results.
     *
     * @param   array                           $results    array of diff results.
     * @param   P4Cms_Diff_OptionsCollection    $options    original options collection.
     */
    public function __construct(array $results, P4Cms_Diff_OptionsCollection $options)
    {
        $this->_options = $options;
        parent::__construct($results);
    }

    /**
     * Count all of the differences across all diff results.
     *
     * @return  int     the total count of differences.
     */
    public function getDiffCount()
    {
        $count = 0;
        foreach ($this as $result) {
            if ($result instanceof P4Cms_Diff_Result) {
                $count += $result->getDiffCount();
            }
        }

        return $count;
    }

    /**
     * Determine if there are any differences in this collection.
     *
     * @return  bool    true if there are differences; false otherwise.
     */
    public function hasDiffs()
    {
        return (bool) $this->getDiffCount();
    }

    /**
     * Get the original options collection that produced this result
     * (ie. the options passed to the compare method).
     *
     * @return  P4Cms_Diff_OptionsCollection    the original options collection.
     */
    public function getOptionsCollection()
    {
        return $this->_options;
    }
}
