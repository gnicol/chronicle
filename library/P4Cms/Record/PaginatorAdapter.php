<?php
/**
 * An adapter to assist Zend_Paginator in paginating records.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        document in greater detail.
 * @todo        add storage adapter here, or, even better,
 *              add it to query object which we already have.
 */
class P4Cms_Record_PaginatorAdapter implements Zend_Paginator_Adapter_Interface
{
    const       DEFAULT_PAGE_SIZE   = 25;
    protected   $_count             = null;
    protected   $_query             = null;

    /**
     * Accept a query object during construction.
     *
     * @param  P4Cms_Record_Query  $query  Optional - the query to use to select results.
     */
    public function __construct(P4Cms_Record_Query $query = null)
    {
        if (!isset($query)) {
            $query = new P4Cms_Record_Query;
        }

        $this->_query = $query;
    }

    /**
     * Set the query for fetching results to be paginated.
     *
     * @param P4Cms_Record_Query  $query  The query for results.
     */
    public function setQuery(P4Cms_Record_Query $query)
    {
        $this->_query = $query;
        $this->_count = null;
    }

    /**
     * Compute the count of items in the result.
     *
     * @return  integer  The count of items.
     */
    public function count()
    {
        if ($this->_count === null) {
            $recordClass  = $this->_query->getRecordClass();
            $this->_count = $recordClass::count($this->_query);
        }

        return $this->_count;
    }

    /**
     * Get the iterator items appearing on a particular 'page' of results.
     *
     * @param   integer  $offset            The index of the first result to return.
     * @param   integer  $itemCountPerPage  The count of results to return.
     * @return  P4Cms_Model_Iterator  An iterator containing the selected results.
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $query = clone $this->_query;

        // limit total number of rows.
        if (is_string($itemCountPerPage) || is_float($itemCountPerPage)) {
            $itemCountPerPage = (int) $itemCountPerPage;
        }
        if ($itemCountPerPage < 1) {
            $itemCountPerPage = static::DEFAULT_PAGE_SIZE;
        }
        $query->setMaxRows($itemCountPerPage);

        // ignore first 'offset' rows.
        $query->setStartRow($offset);

        $recordClass = $this->_query->getRecordClass();
        return $recordClass::fetchAll($query);
    }
}
