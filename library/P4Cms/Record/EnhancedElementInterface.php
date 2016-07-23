<?php
/**
 * Provides a contract for form elements to be enhanced for use 
 * with records:
 *
 *  - Specifies a method to populate a record from a form element
 *    which allows the element to make decisions and modify other 
 *    aspects of the record object (e.g. set metadata).
 *  - Specifies a method to populate the form element from a record
 *    with the benefit of reading other aspects of the record.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
interface P4Cms_Record_EnhancedElementInterface
{
    /**
     * Set the element value on the given record. Allows for decision
     * making and modification of other aspects of the record (such as 
     * setting metadata).
     * 
     * @param   P4Cms_Record    $record                 the record to set the value on
     * @return  P4Cms_Record_EnhancedElementInterface   provides fluent interface
     */
    public function populateRecord(P4Cms_Record $record);
    
    /**
     * Set the element value from the given record. Allows for decision
     * making and consideration of other aspects of the record.
     * 
     * @param   P4Cms_Record    $record                 the record to read the value from
     * @return  P4Cms_Record_EnhancedElementInterface   provides fluent interface
     */
    public function populateFromRecord(P4Cms_Record $record);    
}
