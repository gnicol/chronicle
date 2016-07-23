<?php
/**
 * Provides storage of workflow definitions.
 *
 * The important part of the workflow is defined in the states field
 * in INI format. Each state may define a label and zero or more transitions.
 *
 * Transitions permit movement from one state to another. Each transition
 * may define a label, conditions and actions.
 *
 * Conditions are special classes that, when evaluated, control whether or
 * not a transition should be allowed for the given record at that time.
 *
 * Actions are special classes that, when invoked, perform automated tasks
 * in response to a transition occurring on a record under workflow.
 *
 * For example:
 *
 *   [draft]
 *   label                                      = Draft
 *   transitions.review.label                   = Promote to Review
 *   transitions.review.actions.email.action    = SendEmail
 *   transitions.review.actions.email.toRole    = reviewers
 *   transitions.published.label                = Publish
 *
 *   [review]
 *   label                                      = Review
 *   transitions.draft.label                    = Demote to Draft
 *   transitions.published.label                = Publish
 *   transitions.published.conditions[]         = IsCategorized
 *
 *   [published]
 *   label                                      = Published
 *   transitions.review.label                   = Demote to Review
 *   transitions.draft.label                    = Demote to Draft
 *
 * Conditions may be specified a couple of different ways. Each condition
 * can be specified as a simple string (as above), in which case the string
 * will be taken to be the short-form name of the condition class (resolved
 * via the condition loader).
 *
 * Conditions can also be specified in a longer form that permits the
 * inclusion of options. For example:
 *
 *   transitions.published.conditions.IsCategorized.maxDepth = 5
 *
 * In the above example, the key of the condition is taken to be the
 * short-form name of the condition class. If you need to use the same
 * condition class more than once, you can specify a 'condition' key
 * and that will be used instead. For example:
 *
 *   transitions.published.conditions.categorized.condition = IsCategorized
 *   transitions.published.conditions.categorized.maxDepth  = 5
 *
 * Just like conditions, actions can be specified as a string (with no
 * options) or as an array that permits options (see the SendEmail action
 * in the example workflow definition above).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Model_Workflow extends P4Cms_Record
{
    protected           $_states            = null;

    protected static    $_fields            = array(
        'label'         => array(
            'accessor'  => 'getLabel',
            'mutator'   => 'setLabel'
        ),
        'description'   => array(
            'accessor'  => 'getDescription',
            'mutator'   => 'setDescription'
        ),
        'states'        => array(
            'accessor'  => 'getStates',
            'mutator'   => 'setStates'
        )
    );
    protected static    $_fileContentField  = 'states';
    protected static    $_storageSubPath    = 'workflows';

    /**
     * Get the current label.
     *
     * @return  string|null     the current label or null.
     */
    public function getLabel()
    {
        return $this->_getValue('label');
    }

    /**
     * Set a new label.
     *
     * @param   string|null     $label      the new label to use.
     * @return  Workflow_Model_Workflow     provides fluent interface.
     */
    public function setLabel($label)
    {
        if (!is_string($label) && !is_null($label)) {
            throw new InvalidArgumentException("Label must be a string or null.");
        }

        return $this->_setValue('label', $label);
    }

    /**
     * Get the current description.
     *
     * @return  string|null     the current description or null.
     */
    public function getDescription()
    {
        return $this->_getValue('description');
    }

    /**
     * Set a new description.
     *
     * @param   string|null     $description    the new description to use.
     * @return  Workflow_Model_Workflow         provides fluent interface.
     */
    public function setDescription($description)
    {
        if (!is_string($description) && !is_null($description)) {
            throw new InvalidArgumentException("Description must be a string or null.");
        }

        return $this->_setValue('description', $description);
    }

    /**
     * Get the states making up this workflow.
     *
     * @return  array   the list of states (state definitions).
     */
    public function getStates()
    {
        if ($this->_states === null) {
            // convert states INI string to an array using Zend_Config_Ini
            // write states to a temp file to facilitate Zend_Config_Ini parsing
            $tempFile = tempnam(sys_get_temp_dir(), 'workflow');
            file_put_contents($tempFile, $this->_getValue('states'));
            $config   = new Zend_Config_Ini($tempFile);
            $states   = $config->toArray();
            unlink($tempFile);

            $this->_states = is_array($states) ? $states : array();
        }

        return $this->_states;
    }

    /**
     * Get the states in this workflow as models.
     *
     * @return  P4Cms_Model_Iterator    states making up this workflow.
     */
    public function getStateModels()
    {
        $states = new P4Cms_Model_Iterator();
        foreach ($this->getStates() as $name => $values) {
            $states[] = $this->getStateModel($name);
        }

        return $states;
    }

    /**
     * Get the states in INI format.
     *
     * @return  string  List of workflow states in INI format.
     */
    public function getStatesAsIni()
    {
        return $this->_getValue('states');
    }

    /**
     * Set the states making up this workflow.
     *
     * @param   array|string|null   $states     the list of states (assumed to be in INI format.
     *                                          if given as a string) making up this workflow.
     * @return  Workflow_Model_Workflow         provides fluent interface.
     */
    public function setStates($states)
    {
        if (!is_null($states) && !is_array($states) && !is_string($states)) {
            throw new InvalidArgumentException(
                "Cannot set states. States must be given as an array, string or null."
            );
        }

        // if states given as string, assumed to be in INI format
        if (is_string($states)) {
            return $this->setStatesFromIni($states);
        }

        // if states given as non-null, convert to INI
        if (!is_null($states)) {
            // convert elements array to INI format
            $config = new Zend_Config($states);
            $writer = new Zend_Config_Writer_Ini();
            $states = $writer->setConfig($config)->render();
        }

        // reset states
        $this->_states = null;

        return $this->_setValue('states', $states);
    }

    /**
     * Set the states in the INI format.
     *
     * @param   string  $states   the list of states in the INI format.
     */
    public function setStatesFromIni($states)
    {
        if (!is_string($states)) {
            throw new InvalidArgumentException(
                "Cannot set states. States must be a string."
            );
        }

        // reset states
        $this->_states = null;

        return $this->_setValue('states', $states);
    }

    /**
     * The first state defined is (by convention) the default state.
     *
     * @return  Workflow_Model_State    the default state for content using this workflow
     */
    public function getDefaultState()
    {
        return $this->getStateModels()->first();
    }

    /**
     * Get specified state from the states making up this workflow.
     *
     * @param   string  $name       state name.
     * @return  array               field details for the named state.
     * @throws  Workflow_Exception  if state is not found between
     *                              states making up this workflow.
     */
    public function getState($name)
    {
        $states = $this->getStates();

        // return an empty array if invalid state specified
        if (!array_key_exists($name, $states)) {
            throw new Workflow_Exception(
                "State '$name' not found between the states making up this workflow."
            );
        }

        return $states[$name];
    }

    /**
     * Get specified state from this workflow as model.
     *
     * @param   string  $name           state name.
     * @return  Workflow_Model_State    workflow state model.
     */
    public function getStateModel($name)
    {
        $state = new Workflow_Model_State($this->getState($name));
        $state->setId($name)
              ->setWorkflow($this);

        return $state;
    }

    /** Determine if this workflow has the given state.
     *
     * @param   string  $name   the name of the state to check for.
     * @return  bool            true if the given state is one of those
     *                          making up this workflow, false otherwise.
     */
    public function hasState($name)
    {
        return array_key_exists($name, $this->getStates());
    }

    /**
     * Return workflow state of the given record.
     *
     * Returns state model (defined by this workflow) whose key matches
     * the record's workflowState field value. If record doesn't have
     * workflowState field or workflowState value doesn't match any of
     * state keys defined by this workflow, default state of this workflow
     * is returned.
     *
     * Its user's responsibility to call this method on a correct workflow object
     * in relation to the provided record.
     *
     * @param   P4Cms_Record        $record     record to determine state for.
     * @return  Workflow_Model_State            workflow state of given record.
     * @todo    should this method provide any extra effort to determine
     *          if given record is under this workflow (when possible)?
     */
    public function getStateOf(P4Cms_Record $record)
    {
        $recordState = $record->getValue(Workflow_Model_State::RECORD_FIELD);
        return ($recordState && $this->hasState($recordState))
            ? $this->getStateModel($recordState)
            : $this->getDefaultState();
    }

    /**
     * Return scheduled workflow state of the given record or null if record doesn't have any
     * scheduled transition.
     *
     * @param   P4Cms_Record                $record     record to determine scheduled state for.
     * @return  Workflow_Model_State|null               workflow target state of the scheduled
     *                                                  transition for the given record or null
     *                                                  if no scheduled transition.
     */
    public function getScheduledStateOf(P4Cms_Record $record)
    {
        $recordState = $record->getValue(Workflow_Model_State::RECORD_SCHEDULED_FIELD);
        return ($recordState && $this->hasState($recordState))
            ? $this->getStateModel($recordState)
            : null;
    }

    /**
     * Return timestamp for the scheduled transition or null if record doesn't have
     * any scheduled transition.
     *
     * @param   P4Cms_Record    $record     record to determine time of the scheduled transition.
     * @return  int|null        timestamp of the scheduled transition or null if record doesn't
     *                          have any scheduled transition.
     */
    public function getScheduledTimeOf(P4Cms_Record $record)
    {
        $time = $record->getValue(Workflow_Model_State::RECORD_TIME_FIELD);
        return $time ? (int) $time : null;
    }

    /**
     * Sets the state of the given record.
     *
     * @param   P4Cms_Record                    $record     record to set state to.
     * @param   Workflow_Model_State|string     $state      state to set for the given
     *                                                      record.
     * @return  Workflow_Model_Workflow         provides fluent interface.
     */
    public function setStateOf(P4Cms_Record $record, $state)
    {
        $state = $this->_getStateId($state);

        // ensure transition is valid, but only if a transition is happening.
        $fromState = $this->getStateOf($record);
        if ($state !== $fromState->getId()) {
            $transitions   = $fromState->getValidTransitionsFor($record);
            $validToStates = new P4Cms_Model_Iterator($transitions->invoke('getToState'));
            if (!in_array($state, $validToStates->invoke('getId'))) {
                throw new Workflow_Exception(
                    "Cannot set state on the given record. Not a valid transition."
                );
            }
        }

        // set record field
        $record->setValue(Workflow_Model_State::RECORD_FIELD, $state);

        // clear schedule values
        $record->setValue(Workflow_Model_State::RECORD_SCHEDULED_FIELD, null);
        $record->setValue(Workflow_Model_State::RECORD_TIME_FIELD, null);

        return $this;
    }

    /**
     * Set the target state and the time of the scheduled transition for the given record.
     *
     * @param   P4Cms_Record                    $record     record to set scheduled state and time for.
     * @param   Workflow_Model_State|string     $state      target state of the scheduled transition.
     * @param   int                             $time       timestamp of the scheduled transition.
     * @return  Workflow_Model_Workflow         provides fluent interface
     */
    public function setScheduledStateOf(P4Cms_Record $record, $state, $time)
    {
        $state = $this->_getStateId($state);

        // ensure date is in the future
        if (!is_int($time) || $time < time()) {
            throw new InvalidArgumentException(
                "Cannot schedule transition. Time must be an integer timestamp in the future."
            );
        }

        // ensure we have a valid current state
        $record->setValue(Workflow_Model_State::RECORD_FIELD, $this->getStateOf($record)->getId());

        // set schedule values
        $record->setValue(Workflow_Model_State::RECORD_SCHEDULED_FIELD, $state);
        $record->setValue(Workflow_Model_State::RECORD_TIME_FIELD, $time);

        return $this;
    }

    /**
     * Check for a transition on the given record. Record must be in an
     * opened/pending state to detect the transition. If the record is
     * in transition, returns the appropriate transition model.
     *
     * @param   P4Cms_Record                    $record     record to detect transition on.
     * @return  null|Workflow_Model_Transition  the appropriate transition model or null if
     *                                          the given record is not in transition.
     */
    public function detectTransitionOn(P4Cms_Record $record)
    {
        // try to get the associated p4 file - we need the file to
        // know if a transition is underway vs. already committed.
        try {
            $file = $record->toP4File();
        } catch (Exception $e) {
            throw new Workflow_Exception(
                "Cannot detect transition on record. Unable to get required file object."
            );
        }

        $field     = Workflow_Model_State::RECORD_FIELD;
        $fromState = $file->hasAttribute($field) ? $file->getAttribute($field) : null;
        $toState   = $file->hasOpenAttribute($field) ? $file->getOpenAttribute($field) : null;

        // if no valid from state, assume previous state is default state.
        if (!$this->hasState($fromState)) {
            $fromState = $this->getDefaultState()->getId();
        }

        // if no valid to state, assume to state is default state.
        if (!$this->hasState($toState)) {
            $toState = $this->getDefaultState()->getId();
        }

        // no transition if from-state and to-state are the same.
        if ($fromState == $toState) {
            return;
        }

        // no transition if from-state has no transition for to-state.
        // could arguably throw an exception here (how did this happen?)
        // but we don't throw because the caller didn't ask us to validate
        // the state of the record, just to see if we could detect a
        // transition and return the appropriate transition model.
        $fromStateModel = $this->getStateModel($fromState);
        if (!$fromStateModel->hasTransition($toState)) {
            return;
        }

        return $fromStateModel->getTransitionModel($toState);
    }

    /**
     * Return array iterator with content types mapped to their associated workflows.
     * Content types that have no worfklow associated are not present in the map.
     *
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  P4Cms_Model_Iterator    list with content type ids (keys) mapped to
     *                                  associated workflow models (values).
     */
    public static function fetchTypeMap(P4Cms_Record_Adapter $adapter = null)
    {
        // get array with workflow_id => workflow_model mappings
        $workflows = array();
        foreach (static::fetchAll(null, $adapter) as $workflow) {
            $workflows[$workflow->getId()] = $workflow;
        }

        // assemble array iterator with content_type_id => workflow_model mappings
        $map = new P4Cms_Model_Iterator;
        foreach (P4Cms_Content_Type::fetchAll(null, $adapter) as $type) {
            if (array_key_exists($type->workflow, $workflows)) {
                $map[$type->getId()] = $workflows[$type->workflow];
            }
        }

        return $map;
    }

    /**
     * Return workflow model for the given content entry or throw an exception
     * if entry has no workflow.
     *
     * @param   P4Cms_Content               $entry  content entry to get workflow for.
     * @return  Workflow_Model_Workflow     workflow model for the given entry.
     * @throws  Workflow_Exception          if entry has no workflow.
     */
    public static function fetchByContent(P4Cms_Content $entry)
    {
        // get content type
        try {
            $type = $entry->getContentType();
        } catch (P4Cms_Content_Exception $e) {
            $type = null;
        }

        if (!$type || !$type->workflow || !static::exists($type->workflow, null, $entry->getAdapter())) {
            throw new Workflow_Exception(
                "Cannot fetch workflow for the content entry '" + $entry->getId() . "'."
            );
        }

        return static::fetch($type->workflow, null, $entry->getAdapter());
    }

    /**
     * Collect all of the default workflows and install them.
     *
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     */
    public static function installDefaultWorkflows(P4Cms_Record_Adapter $adapter = null)
    {
        // clear the module/theme cache
        P4Cms_Module::clearCache();
        P4Cms_Theme::clearCache();

        // get all enabled modules.
        $packages = P4Cms_Module::fetchAllEnabled();

        // add current theme to packages since it may provide workflows.
        if (P4Cms_Theme::hasActive()) {
            $packages[] = P4Cms_Theme::fetchActive();
        }

        // install default workflows for each package.
        foreach ($packages as $package) {
            static::installPackageDefaults($package, $adapter);
        }
    }

    /**
     * Install the default workflows contributed by a package.
     *
     * @param   P4Cms_PackageAbstract   $package    the package whose workflows will be installed
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     */
    public static function installPackageDefaults(
        P4Cms_PackageAbstract $package,
        P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        $info = $package->getPackageInfo();

        // get the default workflows provided by the package
        $workflows = isset($info['workflows']) && is_array($info['workflows'])
               ? $info['workflows']
               : array();

        foreach ($workflows as $id => $workflow) {
            // skip existing workflows
            if (static::exists($id, null, $adapter)) {
                continue;
            }

            // associate with content types (only if the content
            // type doesn't already have a workflow specified)
            $types = isset($workflow['types'])
                ? (array) $workflow['types']
                : array();
            unset($workflow['types']);
            foreach ($types as $type) {
                if (P4Cms_Content_Type::exists($type, null, $adapter)) {
                    $type = P4Cms_Content_Type::fetch($type, null, $adapter);
                    if (!$type->getValue('workflow')) {
                        $type->setValue('workflow', $id)
                             ->save();
                    }
                }
            }

            // make and save the workflow
            $workflow['id'] = $id;
            $workflow       = new static($workflow, $adapter);
            $workflow->save();
        }
    }

    /**
     * Remove workflows contributed by a package.
     * The workflows are only removed if they have not been edited.
     *
     * @param   P4Cms_PackageAbstract   $package    the package whose workflows are to be removed
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @todo    Don't delete workflows if content entries are currently making use of them.
     */
    public static function removePackageDefaults(
        P4Cms_PackageAbstract $package,
        P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        $info = $package->getPackageInfo();

        // get the default workflows provided by the package
        $workflows = isset($info['workflows']) && is_array($info['workflows'])
               ? $info['workflows']
               : array();

        foreach ($workflows as $id => $workflow) {
            // skip workflows that don't exist
            if (!static::exists($id, null, $adapter)) {
                continue;
            }

            // skip workflows that have been edited.
            $storedWorkflow = static::fetch($id, null, $adapter);
            $workflow['id'] = $id;
            unset($workflow['types']);
            $workflow       = new static($workflow, $adapter);
            if ($workflow->getValues() != $storedWorkflow->getValues()) {
                continue;
            }

            // skip workflows that are in use.
            // @todo    get associated content types and get list of state
            //          ids in this workflow - then, count content where
            //          content-type is in types and workflow-state is in
            //          defined states - if count > 0, continue.

            $storedWorkflow->delete("Package '" . $package->getName() . "' disabled.");
        }
    }

    /**
     * Creates record filter to allow only entries in a published state.
     *
     * If filters are applied to the current status then entry is considered
     * as published if and only if:
     * 1. entry is not under any workflow OR
     * 2. entry is under workflow (lets denote it W) AND
     *    has workflowState attribute equals to 'published' which is a valid
     *    state of the workflow W.
     *
     * If filters are applied to the scheduled status, then entry is considered
     * as published if and only if:
     * 1. entry is under workflow AND
     * 2. workflowScheduledState attribute is equal to 'published'.
     *
     * @param   boolean                 $scheduled  (optional) if true, then filter
     *                                              will be  applied to the scheduled
     *                                              state, otherwise to the current
     *                                              state; false by default
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  P4Cms_Record_Filter     record filter that only content entries
     *                                  in published state can pass.
     */
    public static function makePublishedContentFilter($scheduled = false, P4Cms_Record_Adapter $adapter = null)
    {
        // collect all content types which are either under no workflow
        // (implicitly published) or have a valid workflow which has a
        // published state
        $types       = P4Cms_Content_Type::fetchAll(null, $adapter);
        $workflows   = static::fetchAll(null, $adapter);
        $noWorkflow  = array();
        $publishable = array();
        foreach ($types as $type) {
            // if this type isn't under workflow; collect the id and continue
            if (!$type->workflow) {
                $noWorkflow[] = $type->getId();
                continue;
            }

            // if this type has an invalid workflow or lacks a published state skip it
            $workflow = isset($workflows[$type->workflow]) ? $workflows[$type->workflow] : null;
            if (!$workflow || !$workflow->hasState(Workflow_Model_State::PUBLISHED)) {
                continue;
            }

            $publishable[] = $type->getId();
        }

        // create a filter which whitelists in the known publishable and
        // implicitly published content types. we do it this way to ensure
        // any unknown content types or workflows don't show as published.
        $filter = new P4Cms_Record_Filter;

        // get name of the record field where the filter will be applied to
        $field = $scheduled
            ? Workflow_Model_State::RECORD_SCHEDULED_FIELD
            : Workflow_Model_State::RECORD_FIELD;

        // deal with filtering for types that have a published state
        if ($publishable) {
            $filter->addSubFilter(
                P4Cms_Record_Filter::create()
                ->add($field, array(Workflow_Model_State::PUBLISHED))
                ->add(P4Cms_Content::TYPE_FIELD, $publishable)
            );
        }

        // capture types that have no workflow
        // so long as we aren't looking for a scheduled transition
        if (!$scheduled && $noWorkflow) {
            $filter->addSubFilter(
                P4Cms_Record_Filter::create()->add(P4Cms_Content::TYPE_FIELD, $noWorkflow),
                $filter::CONNECTIVE_OR
            );
        }

        // if there are no candidates return a fixed false expression
        if ($filter->getExpression() === '') {
            return new P4Cms_Record_Filter(P4Cms_Record_Filter::FALSE_EXPRESSION);
        }

        return $filter;
    }

    /**
     * Creates record filter to allow only entries in an unpublished state.
     *
     * If applied to current state, entry is unpublished if and only if
     * its not published.
     *
     * If applied to the scheduled state, entry must be also under a workflow.
     *
     * @param   boolean                 $scheduled  (optional) if true, then filter
     *                                              will be  applied to the scheduled
     *                                              state, otherwise to the current
     *                                              state; false by default
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  P4Cms_Record_Filter     record filter to keep only content
     *                                  entries not being published.
     */
    public static function makeUnpublishedContentFilter($scheduled = false, P4Cms_Record_Adapter $adapter = null)
    {
        $publishedFilter = static::makePublishedContentFilter($scheduled, $adapter);

        // if filter for published entries is empty, i.e. all entries
        // are published, return filter that no entry can pass
        if ($publishedFilter->getExpression() === '') {
            return new P4Cms_Record_Filter(P4Cms_Record_Filter::FALSE_EXPRESSION);
        }

        // get filter inverted to publishedFilter
        $filter = P4Cms_Record_Filter::create()
            ->addSubfilter(
                $publishedFilter,
                P4Cms_Record_Filter::CONNECTIVE_AND_NOT
            );

        // if filter is applied to a scheduled state, exclude entries with unset
        // scheduled state field and also exclude entries not under workflow
        if ($scheduled) {
            $workflowsByType = static::fetchTypeMap($adapter);
            $filter
                ->add(
                    Workflow_Model_State::RECORD_SCHEDULED_FIELD,
                    '.+',
                    P4Cms_Record_Filter::COMPARE_REGEX
                )
                ->add(
                    'contentType',
                    $workflowsByType->keys(),
                    P4Cms_Record_Filter::COMPARE_EQUAL
                );
        }

        return $filter;
    }

    /**
     * Creates record filter to keep only entries having states specified
     * in $states parameter. States array assumes to have workflow ids in
     * keys and selected state ids in values.
     *
     * Entry is considered having given state (W denotes the workflow that
     * given state is defined under):
     *
     *   entry is under workflow W
     *     AND
     *     status attribute matches the given state
     *       OR
     *     state is default state for the workflow W AND status attribute is
     *     unset or not equal to any of the states defined by the workflow W.
     *
     * if applied to the current state, and
     *
     *   entry is under workflow W
     *     AND
     *   S matches the given state
     *
     *  if applied to the scheduled state.
     *
     * @param   array                   $workflowFilterStates   array with states organized by
     *                                                          workflows
     * @param   boolean                 $scheduled              (optional) if true, then filter
     *                                                          will be  applied to the scheduled
     *                                                          state, otherwise to the current
     *                                                          state; false by default
     * @param   P4Cms_Record_Adapter    $adapter                optional - storage adapter to use.
     * @return  P4Cms_Record_Filter     record filter to keep only content
     *                                  entries that are under one of the
     *                                  specified states.
     */
    public static function makeStatesContentFilter(
        array $workflowFilterStates,
        $scheduled = false,
        P4Cms_Record_Adapter $adapter = null
    )
    {
        // early exit if no states provided
        if (!count($workflowFilterStates)) {
            return new P4Cms_Record_Filter;
        }

        // get name of the record field where the filter will be applied to
        $field = $scheduled
            ? Workflow_Model_State::RECORD_SCHEDULED_FIELD
            : Workflow_Model_State::RECORD_FIELD;

        // get arrays with default workflow states and all states keyed by governing workflow
        $defaultStates = array();
        $allStates     = array();
        foreach (static::fetchAll(null, $adapter) as $workflow) {
            $workflowId                 = $workflow->getId();
            $defaultStates[$workflowId] = $workflow->getDefaultState()->getId();
            $allStates[$workflowId]     = $workflow->getStateModels()->invoke('getId');
        }

        // get the workflows keyed by content type
        $workflowsByType = static::fetchTypeMap($adapter);

        // get content types keyed by associated workflows
        $typesByWorkflow = array();
        foreach ($workflowsByType as $type => $workflow) {
            if (!isset($typesByWorkflow[$workflow->getId()])) {
                $typesByWorkflow[$workflow->getId()] = array();
            }
            $typesByWorkflow[$workflow->getId()][] = $type;
        }

        // construct record filter for given workflow states
        $filter = new P4Cms_Record_Filter;
        foreach ($workflowFilterStates as $workflow => $states) {

            // skip if workflow is not associated with any content type
            // or unknown workflow
            if (!array_key_exists($workflow, $typesByWorkflow)
                || !array_key_exists($workflow, $defaultStates)
            ) {
                continue;
            }

            // create filter to keep entries having given states
            $stateFilter = P4Cms_Record_Filter::create()
                ->add(
                    $field,
                    $states,
                    P4Cms_Record_Filter::COMPARE_EQUAL
                );

            // if applied to a current state then for default states include
            // entries having invalid state as they automatically become default
            // state
            if (!$scheduled && in_array($defaultStates[$workflow], $states)) {
                $stateFilter->add(
                    $field,
                    $allStates[$workflow],
                    P4Cms_Record_Filter::COMPARE_NOT_EQUAL,
                    P4Cms_Record_Filter::CONNECTIVE_OR
                );
            }

            // add a subfilter limiting the content types governed by
            // this workflow to the state filter created above
            $filter->addSubFilter(
                P4Cms_Record_Filter::create()
                ->add(
                    'contentType',
                    $typesByWorkflow[$workflow],
                    P4Cms_Record_Filter::COMPARE_EQUAL
                )
                ->addSubFilter(
                    $stateFilter,
                    P4Cms_Record_Filter::CONNECTIVE_AND
                ),
                P4Cms_Record_Filter::CONNECTIVE_OR
            );
        }

        // don't let pass any entry if filter is empty (e.g. if selection
        // contains states that are not used by any content types)
        if ($filter->getExpression() == '') {
            $filter = new P4Cms_Record_Filter(P4Cms_Record_Filter::FALSE_EXPRESSION);
        }

        return $filter;
    }

    /**
     * Creates record filter to allow only entries with scheduled transitions,
     * where the scheduled time is older than the given timestamp.
     *
     * @param   int|string              $timestamp  optional - timestamp to determine if
     *                                              scheduled transition is in the past;
     *                                              current time will be used if not set
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  P4Cms_Record_Filter     record filter to keep only content entries with
     *                                  scheduled transitions to happen before the time
     *                                  specified by the timestamp
     */
    public static function makeScheduledContentFilter($timestamp = null, P4Cms_Record_Adapter $adapter = null)
    {
        $timestamp       = $timestamp ?: time();
        $workflowsByType = static::fetchTypeMap($adapter);

        // if no types are under workflow; nothing matches
        if (!$workflowsByType->count()) {
            return new P4Cms_Record_Filter(P4Cms_Record_Filter::FALSE_EXPRESSION);
        }

        $filter = P4Cms_Record_Filter::create()
            ->add(
                Workflow_Model_State::RECORD_TIME_FIELD,
                (string) $timestamp,
                P4Cms_Record_Filter::COMPARE_LTE
            )
            ->add(
                Workflow_Model_State::RECORD_SCHEDULED_FIELD,
                '.+',
                P4Cms_Record_Filter::COMPARE_REGEX
            )
            ->add(
                'contentType',
                $workflowsByType->keys(),
                P4Cms_Record_Filter::COMPARE_EQUAL
            );

        return $filter;
    }

    /**
     * Helper method to get state id from any valid state representation (string or object).
     * It also checks if state is valid for this workflow.
     *
     * @param   string|Workflow_Model_State $state          representation of a workflow state.
     * @throws  InvalidArgumentException    if $state is neither string nor instance of
     *                                      Workflow_Model_State.
     * @throws  Workflow_Exception          if state is not governed by this workflow.
     * @return  string                      state id        id of the given state.
     */
    protected function _getStateId($state)
    {
        if ($state instanceof Workflow_Model_State) {
            $state = $state->getId();
        } else if (!is_string($state)) {
            throw new InvalidArgumentException(
                "State must be a string or instance of Workflow_Model_State class."
            );
        }

        // check if given state is governed by this workflow
        if (!$this->hasState($state)) {
            throw new Workflow_Exception(
                "Cannot set state on the given record. State is undefined or governed by other workflow."
            );
        }

        return $state;
    }
}
