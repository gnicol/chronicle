<?php
/**
 * This is the workflow form for changing workflow state on multiple entries in a batch.
 *
 * Workflow state options are calculated from given workflows as their intersection, i.e.
 * all workflows states, determined by their ids, defined in all workflows in the list will
 * be rendered. All different labels will be joined together (if two or more workflows use
 * different labels for the same state).
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Form_ManageContent extends Workflow_Form_EditContent
{
    protected $_workflows;

    /**
     * Defines the elements that make up the workflow form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        parent::init();

        $this->setIsArray(false);

        // form should use p4cms-ui styles
        $this->setAttrib('class', 'p4cms-ui change-status');

        // change label for 'state' radio element and make it required.
        $this->getElement('state')
             ->setLabel('Change Status To')
             ->setRequired(true);

        // select 'Now' option for the 'scheduled' element by default
        $this->getElement('scheduled')->setValue('false');

        // add a field to collect the entry ids to change workflow on
        $this->addElement(
            'hidden',
            'ids',
            array(
                'required'  => true
            )
        );

        $this->addElement(
            'textarea',
            'comment',
            array(
                'label'         => 'Comment',
                'description'   => "Shown in the version history.",
                'rows'          => 3
            )
        );

        // add save button
        $this->addElement(
            'SubmitButton',
            'save',
            array(
                'label'     => 'Change Status',
                'required'  => false,
                'ignore'    => true,
                'class'     => 'preferred'
            )
        );

        $this->addDisplayGroup(
            array('save'),
            'buttons',
            array('class' => 'buttons')
        );
    }

    /**
     * Set content entries options.
     *
     * @param   array                   $options    Zend provides no documentation for this.
     * @return  Workflow_Form_ManageContent         provides fluent interface.
     */
    public function setOptions(array $options)
    {
        if (isset($options['workflows'])) {
            $this->_workflows = (array) $options['workflows'];
            unset($options['workflows']);
        }

        return parent::setOptions($options);
    }

    /**
     * Override parent to return null as we cannot determine the current state.
     *
     * @return  null    indicating current state cannot be determined
     */
    public function getCurrentState()
    {
        return null;
    }

    /**
     * Return state options as set of states that are present in all workflows
     * this form is constructed for. All different state labels of the same state,
     * for such states defined in all workflows, will be merged together (separated
     * by slashes).
     *
     * @return  array   list of states present in all workflows
     */
    public function getStateOptions()
    {
        $states      = array();
        $labels      = array();
        $workflowIds = (array) $this->_workflows;

        // remove any empty values from the workflow list
        $workflowIds = array_filter($workflowIds);

        // fetch workflow models
        $workflows = Workflow_Model_Workflow::fetchAll(array('ids' => $workflowIds));

        // iterate over all workflows and keep only states that are defined in each workflow
        foreach ($workflows as $workflow) {
            $workflowStates = $workflow->getStateModels();

            // keep only states defined by the $workflow
            // in the first run, set states to all states of the $workflow
            $states = empty($states)
                ? $workflowStates->invoke('getId')
                : array_intersect($states, $workflowStates->invoke('getId'));

            // if states is empty, exit as it means that workflows have no common state
            if (empty($states)) {
                break;
            }

            // add state labels into the labels array (labels of the same state
            // defined in different workflows can be different)
            foreach ($workflowStates as $state) {
                $stateId    = $state->getId();
                $stateLabel = $state->getLabel();
                if (!isset($labels[$stateId]) || !in_array($stateLabel, $labels[$stateId])) {
                    $labels[$stateId][] = $stateLabel;
                }
            }
        }

        // assemble options from the states iterator (now containing only states
        // defined in all involved workflows)
        // concatenate labels (if there are more for the same state) by slashes
        $options = array();
        foreach ($states as $state) {
            $options[$state] = implode(' / ', $labels[$state]);
        }

        return $options;
    }
}