<?php
/**
 * Provide a common interface for workflow conditions. Workflow conditions 
 * control which workflow transitions are valid for a given record.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
interface Workflow_ConditionInterface
{
    /**
     * Get options attached to the condition.
     *
     * @return  array   condition options.
     */
    public function getOptions();

    /**
     * Set options for this condition.
     *
     * @param   array   $options    condition options to set.
     */
    public function setOptions(array $options);

    /**
     * Evaluate if this condition is satisfied for given transition and record.
     *
     * There is the option of passing an array of pending values to 
     * consider when evaluating conditions. These would typically come
     * from a request as the user makes changes to the record.
     *
     * As the author of a condition class, you are encouraged to check for 
     * these pending values to ensure the conditions are evaluated against 
     * the most recent data (unless you are specifically concerned with 
     * evaulating stored data).
     * 
     * @param   Workflow_Model_Transition   $transition     transition to evaluate this condition for.
     * @param   P4Cms_Record                $record         record to evaluate this condition for.
     * @param   array|null                  $pending        optional - updated values to consider.
     * @return  boolean                     true if this condition is satisfied for
     *                                      transition and record, false otherwise.
     */
    public function evaluate(
        Workflow_Model_Transition $transition, 
        P4Cms_Record $record,
        array $pending = null);
}