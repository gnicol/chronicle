<?php
/**
 * Provide a common interface for workflow actions. Workflow actions 
 * allow for automated tasks when a record under workflow changes state
 * (for example, sending email notifications).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
interface Workflow_ActionInterface
{
    /**
     * Get options attached to the action.
     *
     * @return  array   action options.
     */
    public function getOptions();

    /**
     * Set options for this action.
     *
     * @param   array   $options    action options to set.
     */
    public function setOptions(array $options);

    /**
     * Invoke this action for the given transition and record.
     *
     * @param   Workflow_Model_Transition   $transition     transition to invoke this action for.
     * @param   P4Cms_Record                $record         record to invoke this action for.
     * @return  Workflow_ActionInterface    provides fluent interface.
     */
    public function invoke(Workflow_Model_Transition $transition, P4Cms_Record $record);
}