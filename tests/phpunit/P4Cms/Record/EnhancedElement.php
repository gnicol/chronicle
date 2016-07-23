<?php
/**
 * Test implementation of a form element enhanced for use with records.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_EnhancedElement 
    extends     Zend_Form_Element_Text
    implements  P4Cms_Record_EnhancedElementInterface
{
    /**
     * Test implementation of populate record method that sets field metadata.
     * 
     * @param   P4Cms_Record    $record                 the record to set the value on
     * @return  P4Cms_Record_EnhancedElementInterface   provides fluent interface
     */
    public function populateRecord(P4Cms_Record $record) 
    {
        $record->setValue($this->getName(), $this->getValue());
        $record->setFieldMetadata($this->getName(), array('test'));
        
        return $this;
    }
    
    /**
     * Test implementation of populate from record method.
     *
     * @param   P4Cms_Record    $record                 the record to read the value from
     * @return  P4Cms_Record_EnhancedElementInterface   provides fluent interface
     */
    public function populateFromRecord(P4Cms_Record $record)
    {
        $this->setValue($record->getValue($this->getName()));
        $this->setAttrib('test', true);
        
        return $this;
    }
}
