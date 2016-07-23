<?php
/**
 * Provides an implementation of a hierarchical category for testing.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Categorization_Dir extends P4Cms_Categorization_CategoryAbstract
{
    const   OPTION_DEREFERENCE  = 'dereference';

    /**
     * Specifies the root path for folder category entries.
     */
    protected static $_storageSubPath   = 'folders';

    /**
     * Specifies whether this category allows child categories.
     */
    protected static $_nestingAllowed   = true;

    /**
     * This function provides the tests access to any protected static functions.
     *
     * @param   string  $function   Name of function to be called on this object
     * @param   array|string    $params     Paramater(s) to pass, optional
     * @return  mixed   Return result of called function, False on error
     */
    public static function callProtectedStaticFunc($function, $params = array())
    {
        if (!is_array($params)) {
            $params = array($params);
        }
        return call_user_func_array('static::'.$function, $params);
    }

    /**
     * Retrieve the content entries within this category.
     * By default, this will return a list of unique entry identifiers sorted by 'title' field.
     *
     * @param   array   $options    options to influence fetching content entries:
     *                              OPTION_RECURSIVE   - whether to include entries in sub-categories
     *                              OPTION_DEREFERENCE - influence type of values returned in the
     *                                  output array; if true, then return entry objects, otherwise
     *                                  return entry ids
     *                              any other options will be passed to the record query
     * @return  array   all unique content entries within this category.
     */
    public function getEntries(array $options = array())
    {
        // convert $options to record query options
        $options['paths'] = parent::getEntries($options);

        // sort by title if sorting key is not set in options
        if (!isset($options['sortBy'])) {
            $options['sortBy'] = 'title';
        }

        // if not dereferencing, limit fields to id as we don't need other fields
        $dereference = isset($options[static::OPTION_DEREFERENCE])
            && $options[static::OPTION_DEREFERENCE];
        if (!$dereference) {
            $options['limitFields'] = P4Cms_Content::getIdField();
        }

        // remove special options to avoid possible interference with query options
        unset($options[static::OPTION_DEREFERENCE]);
        unset($options[static::OPTION_RECURSIVE]);

        // create record query with assembled options
        $query = new P4Cms_Record_Query($options);

        // get content entries via P4Cms_Content::fetchAll() to ensure that
        // all third-parties filtering via pub/sub is applied
        $entries = P4Cms_Content::fetchAll($query, $this->getAdapter());

        // if dereference, return shallow copy of the entries iterator,
        // otherwise return list of entries ids
        return $dereference ? $entries->toArray(true) : $entries->invoke('getId');
    }
}
