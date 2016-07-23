<?php
/**
 * Base class for workflow conditions. Workflow conditions control which 
 * workflow transitions are valid for a given record.
 * 
 * This abstract class provides basic handling of options (curtesy of
 * the plugin abstract class) and the ability to negate a condition 
 * (via the 'negate' option). Sub-classes must implement _evaluate().
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
abstract class Workflow_ConditionAbstract
    extends     Workflow_PluginAbstract
    implements  Workflow_ConditionInterface
{
    const       OPTION_NEGATE   = 'negate';
    
    /**
     * Determine if this condition is satisified for the given transition
     * and record.
     * 
     * Proxies to the _evaluate() method (must be implemented by sub-class)
     * to perform actual work of evaluating the condition. This provides 
     * built-in support for a negate option (i.e. returns inverted value 
     * if condition is negated).
     * 
     * See Workflow_ConditionInterface for further commentary.
     *
     * @param   Workflow_Model_Transition   $transition     transition to evaluate this condition for.
     * @param   P4Cms_Record                $record         record to evaluate this condition for.
     * @param   array|null                  $pending        optional - updated values to consider.
     * @return  boolean                     true if this condition is satisfied for
     *                                      transition and record, flase otherwise.
     */
    public function evaluate(
        Workflow_Model_Transition $transition, 
        P4Cms_Record $record, 
        array $pending = null)
    {
        $isConditionMet = $this->_evaluate($transition, $record, $pending);
        return $this->isNegated() ? !$isConditionMet : $isConditionMet;
    }

    /**
     * Detrmine if condition is negated, i.e. if 'negate' option is present and set to true.
     *
     * @return  boolean     true if condition is negated, false otherwise.
     */
    public function isNegated()
    {
        return isset($this->_options[static::OPTION_NEGATE]) 
            && $this->_options[static::OPTION_NEGATE];
    }

    /**
     * This is the real workhorse to evaluate if condition is satisfied 
     * for the given transition in context with given record. Must be 
     * implemented by sub-class.
     * 
     * See Workflow_ConditionInterface for further commentary.
     * 
     * @param   Workflow_Model_Transition   $transition     transition to evaluate this condition for.
     * @param   P4Cms_Record                $record         record to evaluate this condition for.
     * @param   array|null                  $pending        optional - updated values to consider.
     * @return  boolean                     true if this condition is satisfied for transition and
     *                                      record, flase otherwise.
     */
    abstract protected function _evaluate(
        Workflow_Model_Transition $transition, 
        P4Cms_Record $record,
        array $pending = null);
}