<?php
/**
 * This is sub-form for filtering by workflow states utilized by the data grid.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Form_GridStateFilter extends P4Cms_Form_SubForm
{
    const OPTION_ONLY_PUBLISHED     = 'onlyPublished';
    const OPTION_ONLY_UNPUBLISHED   = 'onlyUnpublished';
    const OPTION_USER_SELECTED      = 'userSelected';

    /**
     * Initialize workflow states form.
     */
    public function init()
    {
        $this->setName('workflow')
             ->setOrder(17);

        // add select box with options the filters will be applied to
        $this->addElement(
            'Select',
            'targetState',
            array(
                'label'         => 'Workflow',
                'multiOptions'  => array(
                    'current'   => 'Current Status',
                    'scheduled' => 'Scheduled Status',
                    'either'    => 'Current or Scheduled Status'
                ),
                'autoApply'     => true
            )
        );

        // add radio with published/unpublished/user-defined workflow state options
        $this->addElement(
            'Radio',
            'workflow',
            array(
                'multiOptions'  => array(
                    ''                              => 'Any State',
                    static::OPTION_ONLY_PUBLISHED   => 'Published Content',
                    static::OPTION_ONLY_UNPUBLISHED => 'Unpublished Content',
                    static::OPTION_USER_SELECTED    => 'Specific Workflow States'
                ),
                'autoApply'     => true,
                'value'         => '',
                'onClick'       => ""
                                . "if (this.value == '" . static::OPTION_USER_SELECTED . "') {\n"
                                .  " p4cms.ui.show('fieldset-workflowStates');\n"
                                .  "} else {\n"
                                .  " p4cms.ui.hide('fieldset-workflowStates');\n"
                                .  "}"
            )
        );

        // add a field to collect all workflow states organized by workflows
        $this->addElement(
            'NestedCheckbox',
            'states',
            array(
                'multiOptions'  => $this->_getWorkflowStatesOptions(),
                'autoApply'     => true,
                'onClick'       => "
                    if (this.value.slice(-2) == '/*') {
                        p4cms.ui.toggleChildCheckboxes(this);
                    } else {
                        p4cms.ui.toggleParentCheckbox(this);
                    }
                "
            )
        );

        // put states in a separated group
        $this->addDisplayGroup(
            array('states'),
            'workflowStates',
            array('class' => 'workflow-states hidden')
        );
    }

    /**
     * Returns list of selected values from 'states' element as an array containing
     * workflow => workflow states.
     *
     * @return array    list of selected workflow states grouped by workflows.
     */
    public function getSelectedStates()
    {
        $statesElement = $this->getElement('states');
        $states        = $statesElement->getValue();

        // if no states selected, return an empty array
        if (!is_array($states)) {
            return array();
        }

        $workflowStates = array();
        foreach ($states as $state) {
            $stateValue = substr($state, -2) == '/*'
                ? array_keys($statesElement->getMultiOption(substr($state, 0, -1)))
                : array($state);

            foreach ($stateValue as $workflowStatePair) {
                // $workflowStatePair should contain workflow/state
                if (strpos($workflowStatePair, '/') === false) {
                    continue;
                }

                list($workflow, $state) = explode('/', $workflowStatePair);

                if (!isset($workflowStates[$workflow])) {
                    $workflowStates[$workflow] = array($state);
                } else if (!in_array($state, $workflowStates[$workflow])) {
                    $workflowStates[$workflow][] = $state;
                }
            }
        }

        return $workflowStates;
    }

    /**
     * Return list with workflow states organized by worfkflows suitable for using
     * as options for NestedCheckbox form element.
     *
     * @return array    list with workflow states options.
     */
    protected function _getWorkflowStatesOptions()
    {
        $workflows = Workflow_Model_Workflow::fetchAll();

        // create array with workflow states organized by workflows:
        // [<WORKFLOW:ID>/*] => <WORKFLOW:NAME>
        // [<WORKFLOW:ID>/]  => Array(
        //    [<WORKFLOW:ID>/<WORKFLOW_STATE:ID>] => <WORKFLOW_STATE:LABEL>
        // )
        // where WORKFLOW loops through all workflows and
        // WORKFLOW_STATE loops through all states for the given workflow        
        $options = array();
        foreach ($workflows as $workflow) {
            $prefix = $workflow->getId() . '/';

            // add workflow item
            $options[$prefix . '*'] = $workflow->getLabel();

            // add all states for the workflow (in sorted order)
            $states = $workflow->getStateModels();
            foreach ($states as $state) {
                $options[$prefix][$prefix . $state->getId()] = $state->getLabel();
            }
        }

        return $options;
    }
}