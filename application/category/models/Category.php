<?php
/**
 * Implements categorization of content primarily for navigation.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Category_Model_Category extends P4Cms_Categorization_CategoryAbstract
{
    const   OPTION_DEREFERENCE  = 'dereference';

    /**
     * Specifies the root path for folder category entries.
     */
    protected static $_storageSubPath = 'categories';

    /**
     * Specifies whether this category allows child categories.
     */
    protected static $_nestingAllowed = true;

    /**
     * Specify the fields for category metadata.
     */
    protected static $_fields         = array(
        'title'         => array(
            'accessor'  => 'getTitle',
            'mutator'   => 'setTitle'
        ),
        'description'   => array(
            'accessor'  => 'getDescription',
            'mutator'   => 'setDescription'
        ),
        'indexContent'  => array(
            'accessor'  => 'getIndexContent',
            'mutator'   => 'setIndexContent'
        )
    );
    protected static $_idField        = 'id';

    /**
     * Get the content entry id used as an index page for this category or null
     * if utilizing the default presentation.
     *
     * @return string|null  The index content entries ID, or null for default presentation.
     */
    public function getIndexContent()
    {
        return $this->_getValue('indexContent');
    }

    /**
     * Update the index content with a new value.
     *
     * @param   string  $indexContent    The new content
     * @return  P4Cms_Category  To maintain a fluent interface
     */
    public function setIndexContent($indexContent)
    {
        return $this->_setValue('indexContent', $indexContent);
    }

    /**
     * Get the URI to view this category.
     *
     * @return  string  the URI to view this category.
     */
    public function getUri()
    {
        $params = array(
            'module'        => 'category',
            'controller'    => 'index',
            'action'        => 'index',
            'category'      => $this->getId(),
        );

        $router = Zend_Controller_Front::getInstance()->getRouter();
        return $router->assemble($params, 'category', null, false);
    }

    /**
     * Check if the category specifies a valid index content page.
     *
     * @return  bool    true if index content is specified; false otherwise.
     */
    public function hasIndexContent()
    {
        $id = $this->_getValue('indexContent');
        return (strlen($id) && P4Cms_Content::exists($id));
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