<?php
/**
 * Model for a transition (permits moving from one state to another).
 *
 * Transitions are defined by the workflow. Each state may have zero or
 * more transitions to other states. Transitions can also define conditions
 * that govern whether or not the transition is allowed. 
 * 
 * See Workflow_Model_Workflow for more details.
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Model_Transition extends P4Cms_Model
{
    protected static    $_fields    = array(
        'label'         => array(
            'accessor'  => 'getLabel'
        ),
        'workflow'      => array(
            'accessor'  => 'getWorkflow'
        ),
        'fromState'     => array(
            'accessor'  => 'getFromState',
            'mutator'   => 'setFromState'
        ),
        'toState'       => array(
            'accessor'  => 'getToState',
            'mutator'   => 'setToState'
        ),
        'conditions'    => array(
            'accessor'  => 'getConditions'
        ),
        'actions'    => array(
            'accessor'  => 'getActions'
        ),
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
     * Return state model that this transition moves from.
     *
     * @return  Workflow_Model_State    state that this transition moves from.
     */
    public function getFromState()
    {
        if (!$this->_getValue('fromState') instanceof Workflow_Model_State) {
            throw new Workflow_Exception(
                "Cannot get state the transition moves from. No 'from' state has been set."
            );
        }

        return $this->_getValue('fromState');
    }

    /**
     * Set state this transition moves from.
     *
     * @param   Workflow_Model_State    $state  state model this transition moves from.
     */
    public function setFromState(Workflow_Model_State $state)
    {
        // set workflow from the state model
        $this->_setWorkflowFromState($state);

        return $this->_setValue('fromState', $state);
    }

    /**
     * Return state model that this transition moves to.
     *
     * @return  Workflow_Model_State    state that this transition moves to.
     */
    public function getToState()
    {
        if (!$this->_getValue('toState') instanceof Workflow_Model_State) {
            throw new Workflow_Exception(
                "Cannot get state the transition moves to. No 'to' state has been set."
            );
        }

        return $this->_getValue('toState');
    }

    /**
     * Set state this transition moves to.
     *
     * @param   Workflow_Model_State    $state  state model this transition moves to.
     */
    public function setToState(Workflow_Model_State $state)
    {
        // set workflow from the state model
        $this->_setWorkflowFromState($state);

        return $this->_setValue('toState', $state);
    }

    /**
     * Determine if conditions are satisfied for this transition and record.
     * 
     * If this transition has no conditions specified it evaluates as true,
     * except for content records for which we also verify that the user has 
     * permission to publish content if to-state is 'published'
     *
     * There is the option of passing an array of pending values to 
     * consider when evaluating conditions. These would typically come
     * from a request as the user makes changes to the record.
     *
     * @param   P4Cms_Record    $record     the record to evaluate for context.
     * @param   array|null      $pending    optional - updated values to consider.
     * @return  bool            true if conditions for this transition are 
     *                          satisfied for given record, false otherwise.
     */
    public function areConditionsMetFor(P4Cms_Record $record, array $pending = null)
    {
        // perform special checks for content type records
        if ($record instanceof P4Cms_Content) {

            // deny if no active user
            if (!P4Cms_User::hasActive()) {
                return false;
            }

            $user      = P4Cms_User::fetchActive();
            $toPublish = $this->getToState()->getId() === Workflow_Model_State::PUBLISHED;

            // deny if to-state is published and user doesn't have permission to publish
            if ($toPublish && !$user->isAllowed('content', 'publish')) {
                return false;
            }

            // deny if to-state is not published and user doesn't have access
            // to unpublished content
            // limit this check to only those entries having the owner, as otherwise
            // some of valid transitions will be filtered out when adding a new content
            // (assuming that current user will become the owner of an 'un-owned' content
            // after save)
            if (!$toPublish
                && strlen($record->getOwner())
                && !$user->isAllowed('content', 'access-unpublished')
                && $record->getOwner() !== $user->getId()
            ) {
                return false;
            }
        }

        // loop through all conditions and evaluate them in context
        foreach ($this->getConditions() as $condition) {
            // early exit if condition is not met
            if (!$condition->evaluate($this, $record, $pending)) {
                return false;
            }
        }

        // all conditions are met (or this transition has no conditions)
        return true;
    }
    
    /**
     * Invoke actions for this transition on the given record.
     *
     * @param   P4Cms_Record    $record     the record to invoke actions on.
     * @return  Workflow_Model_Transition   provides fluent interface.
     */
    public function invokeActionsOn(P4Cms_Record $record)
    {
        // loop through all actions and invoke them in context
        foreach ($this->getActions() as $action) {
            $action->invoke($this, $record);
        }

        return $this;
    }
    
    /**
     * Get a workflow that this transition is part of.
     *
     * @return  Workflow_Model_Workflow     workflow this transition is part of.
     * @throws  Workflow_Exception          if no valid workflow has been set.
     */
    public function getWorkflow()
    {
        if (!$this->_getValue('workflow') instanceof Workflow_Model_Workflow) {
            throw new Workflow_Exception(
                "Cannot get workflow for transition. No workflow has been set."
            );
        }

        return $this->_getValue('workflow');
    }

    /**
     * Set a workflow governing this transition from the given state. If
     * workflow has been already set to this model, it also checks if given
     * state is goverened by this workflow.
     *
     * @param   Workflow_Model_State    $state  state to get workflow from.
     * @throws  Workflow_Exception      if workflow governing the state
     *                                  and this transition are not same.
     */
    protected function _setWorkflowFromState(Workflow_Model_State $state)
    {
        // get workflow from the state model and check if it is the same
        // as workflow set to this transition (if applicable)
        $workflow = $state->getWorkflow();
        if ($this->_getValue('workflow') instanceof Workflow_Model_Workflow
            && $this->_getValue('workflow') !== $workflow
        ) {
            throw new Workflow_Exception(
                "State must refer to the same workflow as the transition."
            );
        }

        return $this->_setValue('workflow', $workflow);
    }
    
    /**
     * Get the conditions placed on this transition.
     * 
     * @return  array   a list of condition objects
     */
    public function getConditions()
    {
        return $this->_getPlugins('condition');
    }

    /**
     * Get the actions to invoke when this transition occurs.
     * 
     * @return  array   a list of action objects
     */
    public function getActions()
    {
        return $this->_getPlugins('action');
    }
    
    /**
     * Get the defined plugins of the given type for this transition.
     * 
     * @param   string  $type   the type of plugins to get
     * @return  array   instances of the defined plugins of the given type
     */
    protected function _getPlugins($type)
    {
        $definitions = $this->_getValue($type . 's');
        if (!is_array($definitions)) {
            return array();
        }
        
        $plugins = array();
        $loader  = Workflow_Module::getPluginLoader($type);
        foreach ($definitions as $key => $definition) {
            
            // get the short name of the plugin class - four cases:
            //  1. definition is a string, we take that to be the name.
            //  2. definition is an array with an explicit $type element
            //     (e.g. 'condition' or 'action'), we use this value.
            //  3. definition is an array without an explicit $type element 
            //     and key is a string, we take the key to be the name.
            //  4. none of the above - we skip it.
            if (is_string($definition)) {
                $name = $definition;
            } else if (isset($definition[$type])) {
                $name = $definition[$type];
            } else if (is_array($definition) && is_string($key)) {
                $name = $key;
            } else {
                continue;
            }

            // attempt to resolve the condition class.
            $class = $loader->load($name, false);
            
            // skip invalid plugin classes
            $interface = 'Workflow_' . ucfirst($type) . 'Interface';
            if (!$class 
                || !class_exists($class) 
                || !in_array($interface, class_implements($class))
            ) {
                continue;
            }
            
            // pull options from the definition - options can be specified at
            // the top-level of the definition, or under 'options' (latter wins)
            $options = array();
            if (is_array($definition)) {
                $definition['options'] = isset($definition['options'])
                    ? (array) $definition['options']
                    : array();
                
                $exclude = array($type => null, 'options' => null);
                $options = array_diff_key($definition, $exclude);
                $options = array_merge($options, $definition['options']);
            }
            
            // make the plugin object
            $plugins[] = new $class($options);
        }
        
        return $plugins;
    }
}
