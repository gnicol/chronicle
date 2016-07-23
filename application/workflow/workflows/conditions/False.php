<?php
/**
 * A sample workflow condition that always evaluates to false.
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Workflow_Condition_False extends Workflow_ConditionAbstract
{
    /**
     * Always return false.
     * 
     * @param   Workflow_Model_Transition   $transition     transition to evaluate this condition for.
     * @param   P4Cms_Record                $record         record to evaluate this condition for.
     * @param   array|null                  $pending        optional - updated values 
     *                                                      to consider.
     * @return  bool                        false no matter what.
     */
    protected function _evaluate(
        Workflow_Model_Transition $transition, 
        P4Cms_Record $record,
        array $pending = null)
    {
        return false;
    }
}