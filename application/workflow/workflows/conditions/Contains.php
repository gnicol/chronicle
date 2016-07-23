<?php
/**
 * A workflow condition to check if given input is contained in record field
 * values. Returns true if input is found in at least one record field, false
 * otherwise.
 * 
 * Input is specified via condition options:
 * 
 * fields  - list with record fields to include in search (all fields by default)
 * string  - for literal match (case in-sensitive)
 * pattern - for regex comparison
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Workflow_Condition_Contains extends Workflow_ConditionAbstract
{
    /**
     * Looks for given string or regex pattern (passed via options) in record field
     * values. Returns true if input is found in at least one field, otherwise return
     * false.
     *
     * File content (if set) field and all non-textual fields are excluded.
     * 
     * If both string and patterns options are set, then condition is evaluated as
     * true if either one comparison is successful.
     *
     * @param   Workflow_Model_Transition   $transition     transition to evaluate 
     *                                                      this condition for.
     * @param   P4Cms_Record                $record         record to evaluate this  
     *                                                      condition for in context.
     * @param   array|null                  $pending        optional - updated values 
     *                                                      to consider.
     * @return  bool                        true if input is found in at least one
     *                                      record field value, false otherwise.
     */
    protected function _evaluate(
        Workflow_Model_Transition $transition, 
        P4Cms_Record $record,
        array $pending = null)
    {
        // get selected record fields (all fields if no option provided)
        if ($this->getOption('fields')) {
            // remove non-existant fields
            $fields = array_intersect((array) $this->getOption('fields'), $record->getFields());
        } else {
            $fields = $record->getFields();
        }

        // exclude all non-textual fields
        foreach ($fields as $key => $field) {
            $metadata = $record->getFieldMetadata($field);
            if (isset($metadata['mimeType']) && strpos($metadata['mimeType'], 'text/') !== 0) {
                unset($fields[$key]);
            }
        }

        // search for string/regex input in record fields
        $string  = (string) $this->getOption('string');
        $pattern = (string) $this->getOption('pattern');
        foreach ($fields as $field) {
            
            // take value from pending values array if present, else from record.
            $value = isset($pending[$field]) 
                ? $pending[$field]
                : $record->getValue($field);

            // compare against string (literal match)
            if ($string !== '' && stripos($value, $string) !== false) {
                return true;
            }

            // compare against regex pattern
            if ($pattern !== '' && preg_match($pattern, $value) > 0) {
                return true;
            }
        }

        // input not found
        return false;
    }
}