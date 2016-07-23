<?php
/**
 * Workflow state model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Model_State extends P4Cms_Model
{
    const   PUBLISHED               = 'published';
    const   RECORD_FIELD            = 'workflowState';
    const   RECORD_SCHEDULED_FIELD  = 'workflowScheduledState';
    const   RECORD_TIME_FIELD       = 'workflowScheduledTime';


    protected static    $_fields    = array(
        'label'         => array(
            'accessor'  => 'getLabel'
        ),
        'transitions'   => array(
            'accessor'  => 'getTransitions'
        ),
        'workflow'      => array(
            'accessor'  => 'getWorkflow',
            'mutator'   => 'setWorkflow'
        )
    );

    /**
     * Get the current label.
     *
     * @return  string  the current label or id if label is empty or not a string.
     */
    public function getLabel()
    {
        $label = $this->_getValue('label');
        return is_string($label) && strlen($label) ? $label : (string) $this->getId();
    }

    /**
     * Get the workflow that this state belongs to.
     *
     * @return  Workflow_Model_Workflow     workflow that this state belongs to
     * @throws  Workflow_Exception          if no valid workflow has been set
     */
    public function getWorkflow()
    {
        if (!$this->_getValue('workflow') instanceof Workflow_Model_Workflow) {
            throw new Workflow_Exception(
                "Cannot get workflow for state. No workflow has been set."
            );
        }

        return $this->_getValue('workflow');
    }

    /**
     * Set a workflow that is partially made up by this state.
     * 
     * @param   Workflow_Model_Workflow $workflow   workflow that is made up (partially)
     *                                              by this state.
     */
    public function setWorkflow(Workflow_Model_Workflow $workflow)
    {
        $this->_setValue('workflow', $workflow);
    }

    /**
     * Get list of transitions attached to this state.
     *
     * @return  array   the list of transitions (transitions definitions).
     */
    public function getTransitions()
    {
        $transitions = $this->_getValue('transitions');
        $transitions = is_array($transitions) ? $transitions : array();
        
        // return only the transitions that have valid target states.
        return array_intersect_key(
            $transitions, 
            $this->getWorkflow()->getStates()
        );
    }

    /**
     * Get the transitions attached to this state as models.
     *
     * @return  P4Cms_Model_Iterator    transitions of this state.
     */
    public function getTransitionModels()
    {
        $transitions = new P4Cms_Model_Iterator();
        foreach ($this->getTransitions() as $name => $values) {
            $transitions[] = $this->getTransitionModel($name);
        }

        return $transitions;
    }
    
    /**
     * Get specified transition.
     *
     * @param   string  $name       transition name.
     * @return  array               field details for the named transition.
     * @throws  Workflow_Exception  if transition is not found between
     *                              transitions defined for this state.
     */
    public function getTransition($name)
    {
        $transitions = $this->getTransitions();

        if (!array_key_exists($name, $transitions)) {
            throw new Workflow_Exception(
                "Transition '$name' not found among the transitions for this state."
            );
        }

        return is_array($transitions[$name]) ? $transitions[$name] : array();
    }

    /**
     * Check if state has the specified transition.
     *
     * @param   string  $name       transition name.
     * @return  bool    true if state defines given transition, false otherwise.
     */
    public function hasTransition($name)
    {
        try {
            $this->getTransition($name);
            return true;
        } catch (Workflow_Exception $e) {
            return false;
        }
    }
    
    /**
     * Get specified transition from this state as model.
     *
     * @param   string  $name               transition name.
     * @return  Workflow_Model_Transition   state transition model.
     */
    public function getTransitionModel($name)
    {
        // create transition model
        $transition = new Workflow_Model_Transition($this->getTransition($name));
        $transition->setId($name)
                   ->setFromState($this)
                   ->setToState($this->getWorkflow()->getStateModel($name));

        return $transition;
    }

    /**
     * Get the transitions that are valid for the given record 
     * (as determined by transition conditions). 
     * 
     * There is the option of passing an array of pending values to 
     * consider when evaluating conditions. These would typically come
     * from a request as the user makes changes to the record.
     *
     * @param   P4Cms_Record            $record     the record to evaulate for context.
     * @param   array|null              $pending    optional - updated values to consider.
     * @return  P4Cms_Model_Iterator    a list of allowed transitions for this record.
     */
    public function getValidTransitionsFor(P4Cms_Record $record, array $pending = null)
    {
        $transitions = new P4Cms_Model_Iterator;
        foreach ($this->getTransitionModels() as $transition) {
            if ($transition->areConditionsMetFor($record, $pending)) {
                $transitions[] = $transition;
            }
        }

        return $transitions;
    }
}
