<?php
/**
 * A sample workflow action that does nothing.
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Workflow_Action_Noop extends Workflow_ActionAbstract
{
    /**
     * Invoke this action for the given transition and record.
     *
     * @param   Workflow_Model_Transition   $transition     transition to invoke this action for.
     * @param   P4Cms_Record                $record         record to invoke this action for.
     * @return  Workflow_ActionInterface    provides fluent interface.
     */
    public function invoke(Workflow_Model_Transition $transition, P4Cms_Record $record)
    {
        return $this;
    }
}