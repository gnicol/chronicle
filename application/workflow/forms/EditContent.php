<?php
/**
 * This is the workflow form to display while editing content.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Form_EditContent extends P4Cms_Form_SubForm
{
    const   E_INVALID_DATE      = "Date is invalid or unset.";
    const   E_INVALID_TIME      = "Time is invalid or unset.";
    const   E_INVALID_TIMEDATE  = "Scheduled status changes must be in the future.";

    protected $_entry;
    protected $_workflow;

    /**
     * Defines the elements that make up the workflow form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // set the title of this form.
        $this->setLegend('Workflow');

        $this->addElement(
            'hidden',
            'currentState',
            array(
                'value'  => $this->getCurrentState(),
                'ignore' => true
            )
        );

        $this->addElement(
            'radio',
            'state',
            array(
                'label'         => 'Status',
                'multiOptions'  => $this->getStateOptions()
            )
        );

        $this->addElement(
            'radio',
            'scheduled',
            array(
                'label'         => 'Schedule Status Change',
                'multiOptions'  => array(
                    'false'     => 'Now',
                    'true'      => 'Specify Server Date and Time'
                )
            )
        );

        $this->addElement(
            'note',
            'currentTime'
        );
        $this->getElement('currentTime')
             ->getDecorator('htmlTag')
             ->setOption('class', 'current-time');

        $sheduledElementName = $this->getElement('scheduled')->getFullyQualifiedName();
        $this->addElement(
            'dateTextBox',
            'scheduledDate',
            array(
                'datePattern'   => 'MMM d, yyyy',
                'constraints'   => array(
                    'min'       => date('Y-m-d')
                )
            )
        );

        // prepare time options
        $options = array();
        for ($h = 0; $h < 24; $h++) {
            $amPm          = $h < 12 ? ' AM' : ' PM';
            $key           = sprintf('%02d:00', $h);
            $options[$key] = ($h % 12 ?: 12) . $amPm;
        }

        $this->addElement(
            'select',
            'scheduledTime',
            array(
                'multiOptions'  => $options
            )
        );

        // put time/date elements together
        $this->addDisplayGroup(
            array('scheduled', 'scheduledDate', 'scheduledTime', 'currentTime'),
            'scheduleGroup',
            array(
                'class' => 'scheduleGroup'
            )
        );
    }

    /**
     * Set content entry and workflow options.
     *
     * @param   array                   $options    Zend provides no documentation for this.
     * @return  Workflow_Form_EditContent           provides fluent interface.
     */
    public function setOptions(array $options)
    {
        if (isset($options['workflow'])) {
            $this->_workflow = $options['workflow'];
            unset($options['workflow']);
        }

        if (isset($options['entry'])) {
            $this->_entry = $options['entry'];
            unset($options['entry']);
        }

        return parent::setOptions($options);
    }

    /**
     * Get the workflow model this form is for.
     *
     * @return  Workflow_Model_Workflow     the workflow model instance.
     * @throws  Workflow_Exception          if no workflow has been set
     */
    public function getWorkflow()
    {
        if (!$this->_workflow instanceof Workflow_Model_Workflow) {
            throw new Workflow_Exception(
                "Cannot get workflow for workflow form. No workflow has been set."
            );
        }

        return $this->_workflow;
    }

    /**
     * Get the content entry this form is for.
     *
     * @return  P4Cms_Content       the content entry associated with this workflow.
     * @throws  Workflow_Exception  if no content entry has been set
     */
    public function getEntry()
    {
        if (!$this->_entry instanceof P4Cms_Content) {
            throw new Workflow_Exception(
                "Cannot get content entry for workflow form. No content entry has been set."
            );
        }

        return $this->_entry;
    }

    /**
     * Return current state of the form entry.
     *
     * @return  string  id of the form's current state
     */
    public function getCurrentState()
    {
        return $this->getWorkflow()->getStateOf($this->getEntry())->getId();
    }

    /**
     * Get the valid state options for the associated entry.
     * If data is given, pass it along so that workflow conditions
     * can evaluate against the latest (pending) values.
     *
     * @param   array|null  $data   optional - pending values to pass to condition
     * @return  array       the valid state options for the current entry/data
     */
    public function getStateOptions($data = null)
    {
        // get the entry and workflow that this form is for.
        $entry    = $this->getEntry();
        $workflow = $this->getWorkflow();

        // get the current state of the content entry.
        $state = $workflow->getStateOf($entry);

        // always include the current state.
        $options = array($state->getId() => $state->getLabel() . " (Current)");

        // get options for status radio element, passing data
        // so conditions can evaluate against pending values.
        $transitions = $state->getValidTransitionsFor($entry, $data);
        if ($transitions->count()) {
            $options += array_combine(
                $transitions->invoke('getId'),
                $transitions->invoke('getLabel')
            );
        }

        // mark scheduled transition if there is one
        $scheduledState = $workflow->getScheduledStateOf($entry);
        if ($scheduledState && array_key_exists($scheduledState->getId(), $options)) {
            $options[$scheduledState->getId()] .= " (Scheduled)";
        }

        // put state options in the same order they are defined in the
        // workflow - if the workflow is roughly linear (e.g. draft, review,
        // published) and defined in that order, this will likely be a more
        // intuitive order than if we used the order transitions are defined.
        $stateOrder = array_flip(array_keys($workflow->getStates()));
        uksort(
            $options,
            function($a, $b) use ($stateOrder)
            {
                return $stateOrder[$a] - $stateOrder[$b];
            }
        );

        return $options;
    }

    /**
     * Extend parent to hide schedule display group if current state is selected initially.
     *
     * @param   array       $defaults   Zend provides no documentation for this.
     * @return  Zend_Form               provides fluent interface.
     */
    public function setDefaults(array $defaults)
    {
        // hide schedule group if state is current
        $currentState = $this->getCurrentState();
        if (array_key_exists('state', $defaults) && $defaults['state'] === $currentState) {
            $this->getDisplayGroup('scheduleGroup')->setAttrib('class', 'hidden');
        }

        return parent::setDefaults($defaults);
    }

    /**
     * Extend parent method to provide additional checks for date and time
     * elements as they are required only if transition is scheduled.
     * Also ensures that date and time values (if required) refer to the
     * time in the future.
     *
     * @param   array       $data  values to check against
     * @return  boolean     true if form is valid, false otherwise
     */
    public function isValid($data)
    {
        $valid = parent::isValid($data);

        // extract workflow field values from data
        // in some contexts, fields are under a 'workflow' array
        // in other contexts they are at the top level.
        $belongTo = $this->getElementsBelongTo();
        $workflow = $belongTo
            ? isset($data[$belongTo]) ? $data[$belongTo] : array()
            : $data;

        // ensure date and time fields are filled if transition is scheduled
        if (isset($workflow['scheduled']) && $workflow['scheduled'] === 'true') {
            $date = $workflow['scheduledDate'];
            $time = $workflow['scheduledTime'];

            // ensure time and date fields are not blank
            if (!$date) {
                $this->getElement('currentTime')->addError(self::E_INVALID_DATE);
                $valid = false;
            }
            if (!$time) {
                $this->getElement('currentTime')->addError(self::E_INVALID_TIME);
                $valid = false;
            }

            // ensure that timedate refers to the future
            if ($valid) {
                $timestamp = strtotime($date . ' ' . $time);
                if ($timestamp < time()) {
                    $this->getElement('currentTime')->addError(self::E_INVALID_TIMEDATE);
                    $valid = false;
                }
            }
        }

        return $valid;
    }

    /**
     * Extend parent to set event attributes on some elements. This cannot be done
     * during the init as elements' fully qualified names may not be known at that time.
     * In some cases (e.g. when this is a sub-form of a non-array sub-form),
     * getFullyQualifiedName() method of Zend_Form_Element doesn't work.
     *
     * @param   Zend_View_Interface $view   Zend provides no documentation for this
     * @return  string              rendered form markup
     */
    public function render(Zend_View_Interface $view = null)
    {
        $currentState = $this->getElement('currentState')->getName();
        $scheduled    = $this->getElement('scheduled')->getName();
        $belongsTo    = $this->getElementsBelongTo();
        if ($belongsTo) {
            $currentState = $belongsTo . '[' . $currentState . ']';
            $scheduled    = $belongsTo . '[' . $scheduled    . ']';
        }

        $this->getElement('state')->setAttrib(
            "onClick",
            "
                var currentState = this.form.elements['$currentState'];
                if (currentState && this.value === currentState.value) {
                    p4cms.ui.hide('fieldset-scheduleGroup');
                    dojo.forEach(this.form.elements['$scheduled'], function(node){
                        if (node.value === 'false') {
                            node.checked = true;
                        }
                    });
                } else {
                    p4cms.ui.show('fieldset-scheduleGroup');
                }
            "
        );

        $this->getElement('scheduledDate')->setAttrib(
            "onClick",
            "
                var inputNode = this.valueNode || this.focusNode;
                dojo.forEach(inputNode.form.elements['$scheduled'], function(node){
                    if (node.value === 'true') {
                        node.checked = true;
                    }
                });
            "
        );

        $this->getElement('scheduledTime')->setAttrib(
            "onClick",
            "
                dojo.forEach(this.form.elements['$scheduled'], function(node){
                    if (node.value === 'true') {
                        node.checked = true;
                    }
                });
            "
        );

        return parent::render($view);
    }
}
