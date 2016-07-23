<?php
/**
 * Integrate the workflow module with the rest of the application.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Module extends P4Cms_Module_Integration
{
    const TRANSITION_ARROW              = "\xe2\x9e\x9c";

    /**
     * Static storage for the workflow plugin loaders.
     * This is so that we only have to configure the loaders
     * once. Can be cleared via clearPluginLoaders().
     *
     * @var array   list of plugin loaders.
     */
    protected static    $_pluginLoaders = array();

    /**
     * Perform early integration work (before load).
     *
     * @todo    hook into content save to fire off transition actions
     */
    public static function init()
    {
        // participate in content editing by providing a subform.
        // we place the workflow sub-form under the save sub-form
        // so that the user is prompted for workflow on save.
        P4Cms_PubSub::subscribe('p4cms.content.form',
            function(Content_Form_Content $form)
            {
                // if save subform doesn't exist, nothing to do.
                $saveSubForm = $form->getSubForm('save');
                if (!$saveSubForm) {
                    return;
                }

                // if the content entry has no workflow, nothing to do.
                $entry = $form->getEntry();
                try {
                    $workflow = Workflow_Model_Workflow::fetchByContent($entry);
                } catch (Workflow_Exception $e) {
                    return;
                }

                // content type has workflow, add workflow sub-form so
                // editor can change state of content.
                $workflowForm = new Workflow_Form_EditContent(
                    array(
                        'idPrefix'  => $form->getIdPrefix(),
                        'entry'     => $entry,
                        'workflow'  => $workflow,
                        'order'     => -10,
                        'dojoType'  => 'p4cms.workflow.ContentSubForm',
                        'formName'  => 'workflow',
                        'class'     => 'workflow-sub-form'
                    )
                );

                // normalize workflow sub-form and add it as a content-save sub-form
                Content_Form_Content::normalizeSubForm($workflowForm);
                $saveSubForm->addSubForm($workflowForm, 'workflow');
            }
        );

        // populate workflow sub-form when editing a content
        P4Cms_PubSub::subscribe('p4cms.content.form.populate',
            function(Content_Form_Content $form, array $values)
            {
                // if save subform doesn't exist, nothing to do.
                $saveSubForm = $form->getSubForm('save');
                if (!$saveSubForm) {
                    return;
                }

                // if workflow subform doesn't exist, nothing to do also.
                $workflowSubForm = $saveSubForm->getSubForm('workflow');
                if (!$workflowSubForm) {
                    return;
                }

                // there are 2 different data sources the content form is populated from:
                // request data and content entry values
                // below we check which case occurs and populate workflow sub-form either
                // from passed $values (this happens when form data are contained in the
                // request, typically when form was previously submitted) or from content
                // entry values (if form data are not present in the request, typically
                // when form initializes)
                $state = $workflowSubForm->getElement('state');

                if (isset($values['workflow']['state'])
                    && array_key_exists($values['workflow']['state'], $state->getMultiOptions())
                ) {
                    $data = $values['workflow'] + array(
                        'scheduled'     => null,
                        'scheduledDate' => null,
                        'scheduledTime' => null
                    );

                    // set scheduled to 'false' if it contains whatever else then 'true'
                    if ($data['scheduled'] !== 'true') {
                        $data['scheduled'] = 'false';
                    }
                } else {
                    // get values from entry
                    $entry          = $form->getEntry();
                    $workflow       = Workflow_Model_Workflow::fetchByContent($entry);
                    $scheduledState = $workflow->getScheduledStateOf($entry);
                    $scheduledTime  = $workflow->getScheduledTimeOf($entry);
                    $isScheduled    = $scheduledState !== null;
                    $selectedState  = $isScheduled
                        ? $scheduledState->getId()
                        : $workflow->getStateOf($entry)->getId();

                    $data = array(
                        'state'         => $selectedState,
                        'scheduled'     => $isScheduled ? 'true' : 'false',
                        'scheduledDate' => $isScheduled ? date('Y-m-d', $scheduledTime) : null,
                        'scheduledTime' => $isScheduled ? date('H:i',   $scheduledTime) : null
                    );
                }

                // populate the workflow sub-form with prepared data
                $workflowSubForm->populate($data);
            }
        );

        // re-evaluate 'valid' transitions in light of pending data.
        P4Cms_PubSub::subscribe('p4cms.content.form.preValidate',
            function(Content_Form_Content $form, array $values)
            {
                // if save subform doesn't exist, nothing to do.
                $saveSubForm = $form->getSubForm('save');
                if (!$saveSubForm) {
                    return;
                }

                // if workflow subform doesn't exist, nothing to do also.
                $workflowSubForm = $saveSubForm->getSubForm('workflow');
                if (!$workflowSubForm) {
                    return;
                }

                $state = $workflowSubForm->getElement('state');
                $state->setMultiOptions($workflowSubForm->getStateOptions($values));
            }
        );

        // connect to content pre-save event to use the workflow model's method
        // of storing the workflow state (validates state and stores it as a
        // first-class attribute - otherwise state would be an array and hard
        // to query).
        P4Cms_PubSub::subscribe('p4cms.content.record.preSave',
            function(P4Cms_Record $entry)
            {
                $workflow = $entry->getValue('workflow');
                $entry->unsetValue('workflow');

                // if workflow is not an array, nothing to work with.
                if (!is_array($workflow)) {
                    return;
                }

                // grab the workflow model for this content entry
                // if no workflow, nothing to do.
                try {
                    $workflowModel = Workflow_Model_Workflow::fetchByContent($entry);
                } catch (Workflow_Exception $e) {
                    return;
                }

                // set current state or scheduled state and time if transition is scheduled
                if (isset($workflow['state'])) {
                    // set scheduled state/time if scheduled option was selected and there
                    // is a transition (i.e. other than current state was selected),
                    // otherwise set current state
                    $currentState = $workflowModel->getStateOf($entry)->getId();
                    if ($workflow['state'] !== $currentState && isset($workflow['scheduled'])
                        && $workflow['scheduled'] === 'true'
                    ) {
                        $time = strtotime(
                            $workflow['scheduledDate'] . ' ' . $workflow['scheduledTime']
                        );
                        $workflowModel->setScheduledStateOf($entry, $workflow['state'], $time);
                    } else {
                        $workflowModel->setStateOf($entry, $workflow['state']);
                    }
                }
            }
        );

        // connect to content post-save event to detect workflow
        // transitions and invoke any transition actions.
        P4Cms_PubSub::subscribe('p4cms.content.record.postSave',
            function(P4Cms_Record $entry)
            {
                // grab the workflow model for this content entry
                // if no workflow, nothing to do.
                try {
                    $workflowModel = Workflow_Model_Workflow::fetchByContent($entry);
                } catch (Workflow_Exception $e) {
                    return;
                }

                // detect workflow transition and invoke actions.
                $transition = $workflowModel->detectTransitionOn($entry);
                if ($transition) {
                    $transition->invokeActionsOn($entry);
                }
            }
        );

        // connect to content query generation to filter unpublished
        // content from users that don't have permission to see it.
        P4Cms_PubSub::subscribe('p4cms.content.record.query',
            function(P4Cms_Record_Query $query, P4Cms_Record_Adapter $adapter)
            {
                $user = P4Cms_User::fetchActive();
                if (!$user->isAllowed('content', 'access-unpublished')) {
                    $filter = Workflow_Model_Workflow::makePublishedContentFilter();

                    // add filter to allow accessing own content (as long as user is not anonymous)
                    if (!$user->isAnonymous()) {
                        $filter->add(
                            P4Cms_Content::OWNER_FIELD,
                            $user->getId(),
                            P4Cms_Record_Filter::COMPARE_EQUAL,
                            P4Cms_Record_Filter::CONNECTIVE_OR
                        );
                    }

                    $query->addFilter($filter);
                }
            }
        );

        // provide form to filter content by workflow state.
        P4Cms_PubSub::subscribe('p4cms.content.grid.form.subForms',
            function(Zend_Form $form)
            {
                // provide the form only if user can access unpublished content
                $user = P4Cms_User::fetchActive();
                if (!$user->isAllowed('content', 'access-unpublished')) {
                    return;
                }

                return new Workflow_Form_GridStateFilter;
            }
        );

        // filter content query by selected states.
        P4Cms_PubSub::subscribe('p4cms.content.grid.populate',
            function(P4Cms_Record_Query $query, Zend_Form $form)
            {
                // get workflow sub-form
                $workflowForm = $form->getSubForm('workflow');
                if (!$workflowForm instanceof Workflow_Form_GridStateFilter) {
                    return;
                }

                // early exit if no workflow filters selected
                $workflow = $workflowForm->getValue('workflow');
                if (!$workflow) {
                    return;
                }

                // get list of target states where filters should be applied to: current, scheduled or either
                $target = $workflowForm->getValue('targetState');
                if ($target === 'current') {
                    $targets = array(false);
                } else if ($target === 'scheduled') {
                    $targets = array(true);
                } else if ($target === 'either') {
                    $targets = array(false, true);
                } else {
                    $targets = array();
                }

                $filter = new P4Cms_Record_Filter;
                foreach ($targets as $scheduled) {
                    // create subfilter depending on selected workflow options and target states
                    switch ($workflow) {
                        case Workflow_Form_GridStateFilter::OPTION_ONLY_PUBLISHED:
                            $subFilter = Workflow_Model_Workflow::makePublishedContentFilter($scheduled);
                            break;
                        case Workflow_Form_GridStateFilter::OPTION_ONLY_UNPUBLISHED:
                            $subFilter = Workflow_Model_Workflow::makeUnpublishedContentFilter($scheduled);
                            break;
                        case Workflow_Form_GridStateFilter::OPTION_USER_SELECTED:
                            $subFilter = Workflow_Model_Workflow::makeStatesContentFilter(
                                $workflowForm->getSelectedStates(), $scheduled
                            );
                            break;
                        default:
                            return;
                    }

                    // append subfilter to the record filter
                    $filter->addSubFilter($subFilter, P4Cms_Record_Filter::CONNECTIVE_OR);
                }

                $query->addFilter($filter);
            }
        );

        // provide form to filter content history list by workflow state.
        P4Cms_PubSub::subscribe('p4cms.history.grid.form.subForms',
            function(Zend_Form $form)
            {
                // get record the history grid was constructed for from the form
                // if it is not a content record, we have no interest in it
                $record = $form->getRecord();
                if (!$record instanceof P4Cms_Content) {
                    return;
                }

                $workflow = $record->getContentType()->workflow;
                if (!Workflow_Model_Workflow::exists($workflow)) {
                    return;
                }

                $workflow     = Workflow_Model_Workflow::fetch($workflow);
                $states       = $workflow->getStateModels();
                $stateOptions = array_combine($states->invoke('getId'), $states->invoke('getLabel'));

                // add all states that are not governed by the current workflow but appear in the grid
                $extraStates = array();
                $filename    = $record->toP4File()->getDepotFilename();
                foreach ($form->getChanges() as $change) {
                    $file           = $change->getFileObject($filename);
                    $entry          = P4Cms_Content::fromP4File($file);
                    $extraStates[]  = $entry->getValue(Workflow_Model_State::RECORD_FIELD);
                    $extraStates[]  = $entry->getValue(Workflow_Model_State::RECORD_SCHEDULED_FIELD);
                }
                $extraStates = array_diff(
                    array_unique(array_filter($extraStates)),
                    array_keys($stateOptions)
                );

                // don't show sub-form if there is less than 2 states
                if (count($stateOptions) + count($extraStates) < 2) {
                    return;
                }

                // create the form to filter grid by workflow states
                $form = new P4Cms_Form_SubForm;
                $form->setName('workflow')
                     ->setAttrib('class', 'states-form')
                     ->setOrder(40);

                // add select box with options the filters will be applied to
                $form->addElement(
                    'Select',
                    'targetState',
                    array(
                        'label'         => 'Workflow',
                        'multiOptions'  => array(
                            'current'   => 'Current Status',
                            'scheduled' => 'Scheduled Status',
                            'either'    => 'Current or Scheduled Status'
                        ),
                        'autoApply'     => true,
                        'order'         => 40
                    )
                );

                // add checkboxes with existing states
                if (count($stateOptions)) {
                    $form->addElement(
                        'MultiCheckbox', 'validStates',
                        array(
                            'multiOptions'  => $stateOptions,
                            'autoApply'     => true,
                            'order'         => 41
                        )
                    );
                }

                // add checkboxes with extra states sorted alphabetically
                if (count($extraStates)) {
                    natcasesort($extraStates);
                    $form->addElement(
                        'MultiCheckbox', 'extraStates',
                        array(
                            'multiOptions'  => array_combine($extraStates, $extraStates),
                            'autoApply'     => true
                        )
                    );

                    // put extra states into a display group so it can be styled separately
                    $form->addDisplayGroup(
                        array('extraStates'),
                        'extraStatesGroup',
                        array(
                            'order' => 42
                        )
                    );
                }

                return $form;
            }
        );

        // filter history grid by selected states.
        P4Cms_PubSub::subscribe('p4cms.history.grid.populate',
            function(P4_Model_Iterator $changes, Zend_Form $form)
            {
                $values   = $form->getValues();
                $workflow = isset($values['workflow']) ? $values['workflow'] : array();

                // extract states from workflow options
                $states = array_merge(
                    isset($workflow['validStates']) ? $workflow['validStates'] : array(),
                    isset($workflow['extraStates']) ? $workflow['extraStates'] : array()
                );

                // get entry field the filters will be applied to
                $applyTo = isset($values['workflow']['targetState'])
                    ? $values['workflow']['targetState']
                    : null;

                // early exit if no states selected or not specified where to apply the filters
                if (!count($states) || !$applyTo) {
                    return;
                }

                // get record the history grid was constructed for from the form
                $record = $form->getRecord();
                if (!$record instanceof P4Cms_Content) {
                    return;
                }

                // filter entries to keep only revisions with one of the selected workflow states
                $filename = $record->toP4File()->getDepotFilename();
                $changes->filterByCallback(
                    function($change) use ($states, $filename, $applyTo)
                    {
                        $file        = $change->getFileObject($filename);
                        $entry       = P4Cms_Content::fromP4File($file);
                        $current     = $entry->getValue(Workflow_Model_State::RECORD_FIELD);
                        $scheduled   = $entry->getValue(Workflow_Model_State::RECORD_SCHEDULED_FIELD);
                        $inCurrent   = in_array($current, $states);
                        $inScheduled = in_array($scheduled, $states);

                        return ($applyTo === 'current'   && $inCurrent)
                            || ($applyTo === 'scheduled' && $inScheduled)
                            || ($applyTo === 'either'    && ($inCurrent || $inScheduled));
                    }
                );
            }
        );

        // add state field into the dojo data passed to the content data grid
        $workflows    = null;
        $contentTypes = null;
        P4Cms_PubSub::subscribe('p4cms.content.grid.data.item',
            function(array $data, P4Cms_Content $content, $helper) use (&$workflows, &$contentTypes)
            {
                // get the workflow used by this content entry's type - we use
                // references for the workflows and content types for performance.
                $workflows  = $workflows    ?: Workflow_Model_Workflow::fetchAll();
                $types      = $contentTypes ?: P4Cms_Content_Type::fetchAll();
                $stateField = Workflow_Model_State::RECORD_FIELD;

                $type       = $content->getContentTypeId();
                $type       = $type && isset($types[$type]) ? $types[$type] : null;
                $workflow   = $type ? $type->workflow : null;
                $workflow   = $workflow && isset($workflows[$workflow]) ? $workflows[$workflow] : null;

                // deal with the three types of output
                // a) The type specifies a valid workflow, output the current state
                // b) The type specifies no workflow, implicitly published
                // c) The type or workflow are invalid empty output
                if ($type && $type->workflow && $workflow) {
                    $state              = $workflow->getStateOf($content);
                    $data[$stateField]  = $state->getLabel();
                    $data['workflow']   = $workflow->getLabel() . ': ' . $state->getLabel();
                    $data['workflowId'] = $workflow->getId();

                    // if there is scheduled transition, append info about scheduled state and time
                    $scheduledState = $workflow->getScheduledStateOf($content);
                    if ($scheduledState !== null) {
                        $timestamp          = $workflow->getScheduledTimeOf($content);
                        $data[$stateField] .= ' ' . Workflow_Module::TRANSITION_ARROW
                            . ' ' . $scheduledState->getLabel();
                        $data['workflow']  .= '<br>'
                            . $state->getTransitionModel($scheduledState->getId())->getLabel()
                            . ' on ' . date('M j, Y', $timestamp)
                            . ' at ' . date('g:i A T', $timestamp);
                    }
                } else if ($type && !$type->workflow) {
                    $data[$stateField]  = ucfirst(Workflow_Model_State::PUBLISHED);
                    $data['workflow']   = 'No workflow: content automatically published';
                    $data['workflowId'] = '';
                } else {
                    $data[$stateField]  = '';
                    $data['workflow']   = 'Unknown workflow state. Content type and/or workflow are missing.';
                    $data['workflowId'] = '';
                }

                return $data;
            }
        );

        // add state column into the content data grid
        P4Cms_PubSub::subscribe('p4cms.content.grid.render',
            function($helper)
            {
                $attributes = array(
                    'order'     => 35,
                    'width'     => '20%',
                    'label'     => 'Workflow',
                    'formatter' => 'p4cms.workflow.contentGridFormatters.state'
                );
                $helper->addColumn(Workflow_Model_State::RECORD_FIELD, $attributes, false);

                // attach tooltip dialog to this columns to show workflow details
                $tooltips   = $helper->getAttrib('fieldTooltips') ?: array();
                $tooltips[] = array(
                    'sourceField'   => 'workflow',
                    'attachField'   => Workflow_Model_State::RECORD_FIELD
                );
                $helper->setAttrib('fieldTooltips', $tooltips);
            }
        );

        // add button to the footer for changing workflow state on selected entries
        P4Cms_PubSub::subscribe('p4cms.content.grid.render',
            function($helper)
            {
                // only add button if user can edit content and the delete button is showing.
                // if the delete button is showing, that is indicative of an editing context.
                $user = P4Cms_User::fetchActive();
                if (!$helper->view->showDeleteButton || !$user->isAllowed('content', 'edit')) {
                    return;
                }

                $helper->addButton(
                    'Workflow',
                    array(
                        'attribs'       => array(
                            'onclick'   => 'p4cms.workflow.content.grid.Utility.openWorkflowDialog();',
                            'class'     => 'workflow-button'
                        ),
                        'order'         => 20
                    )
                );
            }
        );

        // add state field into the dojo data passed to the history data grid
        P4Cms_PubSub::subscribe('p4cms.history.grid.data.item',
            function(array $data, P4_Change $change, $helper)
            {
                // we are only interested in the content history grid
                if (!$helper->view->record instanceof P4Cms_Content) {
                    return;
                }

                $revspec  = isset($data['version'])  ? $data['version']  : null;
                $recordId = isset($data['recordId']) ? $data['recordId'] : null;

                // get workflow state of given content entry at #revspec
                if ($revspec && $recordId) {
                    $entry = P4Cms_Content::fetch(
                        $recordId . '#' . $revspec,
                        array('includeDeleted' => true)
                    );

                    // if entry has no workflow, nothing to do
                    try {
                        $workflow = Workflow_Model_Workflow::fetchByContent($entry);
                    } catch (Workflow_Exception $e) {
                        return $data;
                    }

                    // add state to data as array with state label/id and flag whether it exists or not
                    $stateId          = $entry->getValue(Workflow_Model_State::RECORD_FIELD);
                    $scheduledStateId = $entry->getValue(Workflow_Model_State::RECORD_SCHEDULED_FIELD);
                    if ($workflow->hasState($stateId)) {
                        $state = array(
                            'state'  => $workflow->getStateModel($stateId)->getLabel(),
                            'exists' => true
                        );

                        // append scheduled state if entry has one
                        if ($scheduledStateId && $workflow->hasState($scheduledStateId)) {
                            $state['state'] .= ' ' . Workflow_Module::TRANSITION_ARROW . ' '
                                . $workflow->getStateModel($scheduledStateId)->getLabel();
                        }
                    } else {
                        $state = array(
                            'state'  => $stateId,
                            'exists' => false
                        );

                        // append scheduled state if entry has one
                        if ($scheduledStateId) {
                            $state['state'] .= ' ' . Workflow_Module::TRANSITION_ARROW . ' '
                                . $scheduledStateId;
                        }
                    }

                    $data['state'] = $state;
                }

                return $data;
            }
        );

        // add workflow column into the history data grid
        P4Cms_PubSub::subscribe('p4cms.history.grid.render',
            function($helper)
            {
                // do not show column if content is not under workflow
                if (!P4cms_Content::exists($helper->view->id)
                    || !P4cms_Content::fetch($helper->view->id)->getContentType()->workflow
                ) {
                    return;
                }

                $attributes = array(
                    'order'     => 35,
                    'width'     => '20%',
                    'label'     => 'Workflow',
                    'formatter' => 'p4cms.workflow.contentHistoryGridFormatters.state'
                );
                $helper->addColumn('state', $attributes, false);
            }
        );

        // provide workflow grid actions
        P4Cms_PubSub::subscribe('p4cms.workflow.grid.actions',
            function($actions)
            {
                $actions->addPages(
                    array(
                        array(
                            'label'     => 'Edit',
                            'onClick'   => 'p4cms.workflow.grid.Actions.onClickEdit();',
                            'order'     => '10'
                        ),
                        array(
                            'label'     => 'Delete',
                            'onClick'   => 'p4cms.workflow.grid.Actions.onClickDelete();',
                            'order'     => '20'
                        )
                    )
                );
            }
        );

        // provide content grid actions
        P4Cms_PubSub::subscribe('p4cms.content.grid.actions',
            function($actions)
            {
                $actions->addPages(
                    array(
                        array(
                            'label'     => 'Change Status',
                            'onClick'   => 'p4cms.workflow.content.grid.Actions.onClickChangeStatus();',
                            'onShow'    => 'p4cms.workflow.content.grid.Actions.onShowChangeStatus(this);',
                            'order'     => '100',
                            'resource'  => 'content',
                            'privilege' => 'edit'
                        )
                    )
                );
            }
        );

        // provide form to search workflows
        P4Cms_PubSub::subscribe('p4cms.workflow.grid.form.subForms',
            function(Zend_Form $form)
            {
                return new Ui_Form_GridSearch;
            }
        );

        // filter workflows by keyword search
        P4Cms_PubSub::subscribe('p4cms.workflow.grid.populate',
            function(P4Cms_Model_Iterator $workflows, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract search query.
                $query = isset($values['search']['query'])
                    ? $values['search']['query']
                    : null;

                // early exit if no query.
                if (!$query) {
                    return null;
                }

                // remove workflows that don't match search query.
                return $workflows->search(
                    array('label'),
                    $query
                );
            }
        );

        // provide form to filter workflows by associated content types
        P4Cms_PubSub::subscribe('p4cms.workflow.grid.form.subForms',
            function(Zend_Form $form)
            {
                return new Content_Form_GridTypeFilter;
            }
        );

        // filter workflows by selected content types
        P4Cms_PubSub::subscribe('p4cms.workflow.grid.populate',
            function(P4Cms_Model_Iterator $workflows, Zend_Form $form)
            {
                // get type sub-form.
                $typeForm = $form->getSubForm('type');
                if (!$typeForm instanceof Content_Form_GridTypeFilter) {
                    return;
                }

                // filter for selected types.
                $types = $typeForm->getElement('types')->getNormalizedTypes();
                if (count($types)) {
                    // get list of workflows of all selected types
                    $typeWorkflows = P4Cms_Content_Type::fetchAll(array('ids' => $types))
                        ->invoke('getValue', array('workflow'));

                    // filter workflows to keep only those associated with selected content types
                    $workflows->filter('id', array_unique($typeWorkflows));
                }
            }
        );

        // update workflows when a site is created.
        P4Cms_PubSub::subscribe('p4cms.site.created',
            function(P4Cms_Site $site)
            {
                $adapter = $site->getStorageAdapter();
                Workflow_Model_Workflow::installDefaultWorkflows($adapter);
            }
        );

        // update workflows when a module/theme is enabled.
        $installDefaults = function(P4Cms_Site $site, P4Cms_PackageAbstract $package)
        {
            $adapter = $site->getStorageAdapter();
            Workflow_Model_Workflow::installPackageDefaults($package, $adapter);
        };

        P4Cms_PubSub::subscribe('p4cms.site.module.enabled', $installDefaults);
        P4Cms_PubSub::subscribe('p4cms.site.theme.enabled',  $installDefaults);

        // update workflows when a module/theme is disabled
        $removeDefaults = function(P4Cms_Site $site, P4Cms_PackageAbstract $package)
        {
            $adapter = $site->getStorageAdapter();
            Workflow_Model_Workflow::removePackageDefaults($package, $adapter);
        };

        P4Cms_PubSub::subscribe('p4cms.site.module.disabled', $removeDefaults);
        P4Cms_PubSub::subscribe('p4cms.site.theme.disabled',  $removeDefaults);

        // add workflow drop-down to content type form.
        P4Cms_PubSub::subscribe('p4cms.content.type.form',
            function(P4Cms_Form_PubSubForm $form)
            {
                // collect available workflows.
                $options   = array('' => 'No Workflow (Always Published)');
                $workflows = Workflow_Model_Workflow::fetchAll();
                foreach ($workflows as $workflow) {
                    $states = $workflow->getStateModels()->invoke('getLabel');
                    $states = implode(', ', $states);
                    $helper = $form->getView()->getHelper('truncate');
                    $states = $helper->truncate($states, 50, '...');
                    $label  = $workflow->getLabel() . " ($states)";

                    $options[$workflow->getId()] = $label;
                }

                $form->addElement(
                    'select',
                    'workflow',
                    array(
                        'label'         => 'Workflow',
                        'multiOptions'  => $options,
                        'description'   => 'Select a workflow to control the process of creating '
                                        .  'and publishing content of this type.',
                        'order'         => 6
                    )
                );
            }
        );

        // connect to search prepare document event to add the workflow state
        P4Cms_PubSub::subscribe('p4cms.search.prepareDocument',
            function($document, $original)
            {
                // we only care about lucene documents and content records.
                if (!$document instanceof Zend_Search_Lucene_Document
                    || !$original instanceof P4Cms_Content
                ) {
                    return $document;
                }

                // add the workflow state, but don't index it.
                $document->addField(
                    Zend_Search_Lucene_Field::unIndexed(
                        Workflow_Model_State::RECORD_FIELD,
                        $original->getValue(Workflow_Model_State::RECORD_FIELD)
                    )
                );

                return $document;
            }
        );

        // connect to search results event to filter unpublished content
        $workflows    = null;
        $contentTypes = null;
        P4Cms_PubSub::subscribe('p4cms.search.results',
            function($results) use (&$workflows, &$contentTypes)
            {
                // nothing to do if current user can access unpublished content.
                $user = P4Cms_User::fetchActive();
                if ($user->isAllowed('content', 'access-unpublished')) {
                    return $results;
                }

                // populate the workflows and content types if needed - we use
                // references for the workflows and content types for performance.
                $workflows  = $workflows    ?: Workflow_Model_Workflow::fetchAll();
                $types      = $contentTypes ?: P4Cms_Content_Type::fetchAll();

                // exclude hits that are not published
                foreach ($results as $key => $result) {
                    $document = $result->getDocument();
                    $fields   = $document->getFieldNames();

                    // only consider results that appear to reference content.
                    if (!in_array('contentType', $fields)) {
                        continue;
                    }

                    $type       = $document->contentType;
                    $type       = isset($types[$type]) ? $types[$type] : null;
                    $workflow   = $type ? $type->workflow : null;
                    $workflow   = $workflow && isset($workflows[$workflow]) ? $workflows[$workflow] : null;

                    // only check state on content types under workflow.
                    if ($type && !$type->workflow) {
                        continue;
                    }

                    // remove any entries with invalid type or workflow settings
                    if (!$type || !$workflow) {
                        unset($results[$key]);
                        continue;
                    }

                    // remove unpublished content entries
                    $state = in_array(Workflow_Model_State::RECORD_FIELD, $fields)
                        ? $document->getFieldValue(Workflow_Model_State::RECORD_FIELD)
                        : null;
                    if (!$workflow->hasState($state) || $state !== Workflow_Model_State::PUBLISHED) {
                        unset($results[$key]);
                    }
                }

                return $results;
            }
        );

        // process scheduled transitions
        // @todo should we clear scheduled data on entries that fail when changing the state?
        //       they will most likely fail on the next run as well
        P4Cms_PubSub::subscribe('p4cms.cron.hourly',
            function()
            {
                // elevate privileges of current (cron) user to grant all content privileges
                P4Cms_User::fetchActive()->allow('content');

                // get record filter to keep only entries with scheduled transitions
                // where scheduled time is in the past
                $filter = Workflow_Model_Workflow::makeScheduledContentFilter();

                // iterate over filtered entries and process scheduled transitions
                $query  = P4Cms_Record_Query::create()->addFilter($filter);
                $report = array();
                foreach (P4Cms_Content::fetchAll($query) as $entry) {
                    $id = $entry->getId();

                    try {
                        // get the governing workflow of the entry
                        $workflow = Workflow_Model_Workflow::fetchByContent($entry);

                        // update the state of workflow for the entry according to the
                        // scheduled transition
                        $fromState = $workflow->getStateOf($entry);
                        $toState   = $workflow->getScheduledStateOf($entry);
                        if (!$toState) {
                            throw new Exception("Scheduled state not found.");
                        }

                        $workflow->setStateOf($entry, $toState->getId());
                        $entry->save(
                            "Processed scheduled transition: "
                            . $fromState->getLabel()
                            . " " . Workflow_Module::TRANSITION_ARROW . " "
                            . $toState->getLabel() . "."
                        );
                    } catch (Exception $e) {
                        $message = "Cannot process scheduled transition for entry id '$id': "
                            . $e->getMessage();
                        P4Cms_Log::log($message, P4Cms_Log::ERR);
                        $report['error'][] = $message;
                        continue;
                    }

                    $message = "Processed scheduled transition for content entry id '$id'"
                        . " (from state: " . $fromState->getLabel()
                        . ", to state: " . $toState->getLabel() . ").";
                    P4Cms_Log::log($message, P4Cms_Log::NOTICE);
                    $report['notice'][] = $message;
                }

                return $report;
            }
        );

        // organize workflow under configuration group for pull operations.
        P4Cms_PubSub::subscribe(
            'p4cms.site.branch.pull.groupPaths',
            function($paths, $source, $target, $result)
            {
                $paths->addSubGroup(
                    array(
                        'label'         => 'Workflows',
                        'basePaths'     => $target->getId() . '/workflows/...',
                        'inheritPaths'  => $target->getId() . '/workflows/...',
                        'pullByDefault' => true,
                        'details'       =>
                                function($paths) use ($source, $target)
                                {
                                    $pathsById = array();
                                    foreach ($paths as $path) {
                                        if (strpos($path->depotFile, $target->getId() . '/workflows/') === 0) {
                                            $id = Workflow_Model_Workflow::depotFileToId($path->depotFile);
                                            $pathsById[$id] = $path;
                                        }
                                    }

                                    $details = new P4Cms_Model_Iterator;
                                    $entries = Site_Model_PullPathGroup::fetchRecords(
                                        array_keys($pathsById), 'Workflow_Model_Workflow', $source, $target
                                    );
                                    foreach ($entries as $entry) {
                                        $path      = $pathsById[$entry->getId()];
                                        $details[] = new P4Cms_Model(
                                            array(
                                                'conflict' => $path->conflict,
                                                'action'   => $path->action,
                                                'label'    => $entry->getLabel()
                                            )
                                        );
                                    }

                                    $details->setProperty(
                                        'columns',
                                        array('label' => 'Workflow', 'action' => 'Action')
                                    );

                                    return $details;
                                }
                    )
                );
            }
        );

        // help organize content-related records by workflow when pulling changes.
        P4Cms_PubSub::subscribe(
            'p4cms.site.branch.pull.groupPaths',
            function($paths, $source, $target, $result)
            {
                // try to find the content entries group
                $content = $paths->getSubGroup('Content');
                $entries = $content ? $content->getSubGroup('Entries') : null;
                if (!$entries) {
                    return;
                }

                // all paths will be in target syntax. we need to convert any paths
                // we are not deleting to source syntax to check their status.
                $paths = array();
                foreach ($entries->getPaths() as $path) {
                    if ($path->action != 'delete') {
                        $paths[] = $source->getId() . substr($path->depotFile, strlen($target->getId()));
                    } else {
                        $paths[] = $path->depotFile;
                    }
                }

                // determine which paths, if any, represent published content entries
                $filter = Workflow_Model_Workflow::makePublishedContentFilter(false, $source->getStorageAdapter());
                $query  = P4_File_Query::create()
                    ->addFilespecs($paths)
                    ->setLimitFields(array('depotFile'))
                    ->setFilter($filter);
                $published = $paths
                    ? P4_File::fetchAll($query, $source->getStorageAdapter()->getConnection())
                    : new P4_Model_Iterator;

                // translate any source syntax results back to target syntax.
                $paths = array();
                foreach ($published->invoke('getValue', array('depotFile')) as $path) {
                    if (strpos($path, $source->getId()) === 0) {
                        $path = $target->getId() . substr($path, strlen($source->getId()));
                    }
                    $paths[] = $path;
                }

                $entries->addSubGroup(
                    array(
                        'label'         => 'Published Entries',
                        'inheritPaths'  => $paths,
                        'pullByDefault' => true,
                        'order'         => -100,
                        'details'       => $entries->getDetailsCallback()
                    )
                );

                // move the remaining paths to an un-published content group
                $entries->addSubGroup(
                    array(
                        'label'         => 'Unpublished Entries',
                        'inheritPaths'  => $entries->getPaths(),
                        'pullByDefault' => true,
                        'order'         => -90,
                        'details'       => $entries->getDetailsCallback()
                    )
                );

                // move our published/unpublished group (and any others) up to
                // the content group instead of being under entries
                foreach ($entries->getSubGroups() as $group) {
                    $content->addSubGroup($group);
                }

                // remove the now empty entries group as we are done with it
                $content->getSubGroups()
                        ->filter('label', 'Entries', array(P4Cms_Model_Iterator::FILTER_INVERSE));
            }
        );
    }

    /**
     * Get a plugin loader for instantiating workflow conditions or actions.
     *
     * This loader is configured with appropriate prefixes and paths for
     * all enabled modules that include workflow plugins of the given type.
     * This allows plugins to be loaded via their short name and overridden
     * by later modules.
     *
     * @param   string  $type               the plugin loader to get ('condition' or 'action')
     * @return  Zend_Loader_PluginLoader    the loader to use with plugins of this type
     */
    public static function getPluginLoader($type)
    {
        $types = array(
            'action'    => array('/workflows/actions',    '_Workflow_Action'),
            'condition' => array('/workflows/conditions', '_Workflow_Condition')
        );

        if (!$type || !isset($types[$type])) {
            throw new InvalidArgumentException(
                "Cannot get plugin loader. Invalid plugin type specified."
            );
        }

        // return cached copy if present.
        if (isset(static::$_pluginLoaders[$type])) {
            return static::$_pluginLoaders[$type];
        }

        // make a new plugin loader and add paths for all
        // modules containing workflow plugins of given type.
        $loader = new Zend_Loader_PluginLoader;
        foreach (P4Cms_Module::fetchAllEnabled() as $module) {
            $path = $module->getPath() . $types[$type][0];
            if (is_dir($path)) {
                $loader->addPrefixPath(
                    $module->getName() . $types[$type][1],
                    $path
                );
            }
        }
        static::$_pluginLoaders[$type] = $loader;

        return $loader;
    }

    /**
     * Reset the workflow plugin loaders. Useful for testing.
     */
    public static function clearPluginLoaders()
    {
        static::$_pluginLoaders = array();
    }
}
