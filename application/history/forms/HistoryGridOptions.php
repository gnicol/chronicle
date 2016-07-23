<?php
/**
 * This is the History grid options form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class History_Form_HistoryGridOptions extends Ui_Form_GridOptions
{
    protected $_record  = null;
    protected $_changes = null;

    /**
     * Return the list of changes for the record that has been set for
     * this form (or null if no record has been set).
     * List with changes is stored in memory until the record is changed
     * or reset.
     *
     * @return  P4_Model_Iterator|null      the list of changes or null
     */
    public function getChanges()
    {
        if ($this->_changes === null && $this->_record !== null) {
            $this->_changes = $this->_record->toP4File()->getChanges();
        }

        return $this->_changes;
    }

    /**
     * Set record for this form.
     *
     * @param P4Cms_Record $record  record the form is constructed for.
     */
    public function setRecord(P4Cms_Record $record = null)
    {
        $this->_record = $record;

        // reset changes to force re-generating list of changes at next getChanges() call
        $this->_changes = null;
    }

    /**
     * Return record the form is constructed for.
     *
     * @return P4Cms_Record|null    record for this form or null
     */
    public function getRecord()
    {
        return $this->_record;
    }
}
