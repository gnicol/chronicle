<?php
/**
 * Test the workflow content controller.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Test_ContentControllerTest extends ModuleControllerTest
{
    /**
     * Test successful changing workflow state on multiple entries.
     */
    public function testChangeStateSuccess()
    {
        $this->utility->impersonate('administrator');

        // create workflows, content types and entries
        $this->_createWorkflowsAndTypes(3);

        // change workflow state to review on some entries
        $ids = array('simple1', 'simple3');

        // dispatch
        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'workflows' => array('simple'),
                'ids'       => $ids,
                'state'     => 'review',
                'scheduled' => 'false'
            )
        );
        $this->dispatch('/workflow/content/change-state/format/json');

        $this->assertModule('workflow', __LINE__ .': Last module run should be workflow module.');
        $this->assertController('content', __LINE__ .': Expected controller');
        $this->assertAction('change-state', __LINE__ .': Expected action');

        // verify json response
        $this->_verifyChangeStateJsonResponse($ids);

        // verify all entries have given workflow state and they have been changes in one chagelist
        $this->_verifyEntriesWorkflow($ids, 'review');

        // change workflow state to published for contents of simple and basic type
        $this->resetRequest()->resetResponse();
        $ids = $this->_getContentsByType(array('simple', 'basic'))->invoke('getId');

        // dispatch
        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'workflows' => array('simple', 'basic'),
                'ids'       => $ids,
                'state'     => 'published',
                'scheduled' => 'false'
            )
        );
        $this->dispatch('/workflow/content/change-state/format/json');

        $this->assertModule('workflow', __LINE__ .': Last module run should be workflow module.');
        $this->assertController('content', __LINE__ .': Expected controller');
        $this->assertAction('change-state', __LINE__ .': Expected action');

        // verify json response
        $this->_verifyChangeStateJsonResponse($ids);

        // verify all entries have given workflow state and they have been changes in one chagelist
        $this->_verifyEntriesWorkflow($ids, 'published');
    }

    /**
     * Test changing workflow state on multiple entries with some entries not under workflow.
     */
    public function testChangeStateNoWorkflow()
    {
        $this->utility->impersonate('administrator');

        // create workflows, content types and entries
        $this->_createWorkflowsAndTypes(1);

        // change workflow state to review for contents of simple and basic type
        $ids = array('simple1', 'no-workflow1');

        // dispatch
        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'workflows' => array('simple'),
                'ids'       => $ids,
                'state'     => 'review',
                'scheduled' => 'false'
            )
        );
        $this->dispatch('/workflow/content/change-state/format/json');

        $this->assertModule('workflow', __LINE__ .': Last module run should be workflow module.');
        $this->assertController('content', __LINE__ .': Expected controller');
        $this->assertAction('change-state', __LINE__ .': Expected action');

        $response = Zend_Json::decode($this->response->getBody());

        // verify json response
        $this->_verifyChangeStateJsonResponse(array(), array('no-workflow1'));

        // ensure simple1 has not been changed
        $entry          = P4Cms_Content::fetch('simple1');
        $simpleWorkflow = Workflow_Model_Workflow::fetchByContent($entry);
        $this->assertSame(
            'draft',
            $simpleWorkflow->getStateOf($entry)->getId(),
            "Expected state of the simple1 entry."
        );
    }

    /**
     * Test changing workflow state on multiple entries where not all entries can be changed.
     */
    public function testChangeStateFail()
    {
        $this->utility->impersonate('administrator');

        // create workflows, content types and entries
        $this->_createWorkflowsAndTypes(3);

        // change workflow state to published on entries of simple and private type
        // this should not work as draft->published transition in private workflow is
        // not allowed due to a 'False' condition
        $ids = $this->_getContentsByType(array('simple', 'private'))->invoke('getId');

        // dispatch
        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'workflows' => array('simple', 'private'),
                'ids'       => $ids,
                'state'     => 'published',
                'scheduled' => 'false'
            )
        );
        $this->dispatch('/workflow/content/change-state/format/json');

        $this->assertModule('workflow', __LINE__ .': Last module run should be workflow module.');
        $this->assertController('content', __LINE__ .': Expected controller');
        $this->assertAction('change-state', __LINE__ .': Expected action');

        $response = Zend_Json::decode($this->response->getBody());

        // verify json response
        $this->_verifyChangeStateJsonResponse(
            array(),
            $this->_getContentsByType('private')->invoke('getId')
        );

        // ensure simple entries were not changed
        $simpleWorkflow = Workflow_Model_Workflow::fetch('simple');
        foreach ($this->_getContentsByType('simple') as $entry) {
            $this->assertSame(
                'draft',
                $simpleWorkflow->getStateOf($entry)->getId(),
                "Expected simple entries were not changed."
            );
        }
    }

    /**
     * Test changing workflow state on multiple entries with using 'forced' parameter.
     */
    public function testChangeStateForce()
    {
        $this->utility->impersonate('administrator');

        // create workflows, content types and entries
        $this->_createWorkflowsAndTypes(3);

        // change workflow state to published on entries of simple and private type by force
        // it should change only entries of simple type and leave untouched entries of private
        // type
        $ids = $this->_getContentsByType(array('simple', 'private'))->invoke('getId');

        // dispatch
        $this->request->setMethod('POST');
        $this->request->setPost(
            array(
                'workflows' => array('simple', 'private'),
                'ids'       => $ids,
                'state'     => 'published',
                'scheduled' => 'false'
            )
        );
        $this->dispatch('/workflow/content/change-state/format/json/forced/true');

        $this->assertModule('workflow', __LINE__ .': Last module run should be workflow module.');
        $this->assertController('content', __LINE__ .': Expected controller');
        $this->assertAction('change-state', __LINE__ .': Expected action');

        $response       = Zend_Json::decode($this->response->getBody());
        $simpleEntries  = $this->_getContentsByType('simple');
        $privateEntries = $this->_getContentsByType('private');

        // verify json response
        $this->_verifyChangeStateJsonResponse(
            $simpleEntries->invoke('getId'),
            array()
        );

        // ensure simple entries were changed (in a single change)
        $this->_verifyEntriesWorkflow($simpleEntries->invoke('getId'), 'published');

        // ensure private entries were not changed
        $privateWorkflow = Workflow_Model_Workflow::fetch('private');
        foreach ($privateEntries as $entry) {
            $this->assertSame(
                'draft',
                $privateWorkflow->getStateOf($entry)->getId(),
                "Expected private entries were not changed."
            );
        }
    }

    /**
     * Helper function to return list of entries having given content type(s).
     *
     * @param   string|array    $types  list of content types
     * @return  P4Cms_Model_Iterator    list of entries having given content type(s)
     */
    public function _getContentsByType($types)
    {
        $types  = array ($types);

        // create record filter to keep only entries with given types
        $filter = new P4Cms_Record_Filter;
        foreach ($types as $type) {
            $filter->add(
                'contentType',
                $type,
                P4Cms_Record_Filter::COMPARE_EQUAL,
                P4Cms_Record_Filter::CONNECTIVE_OR
            );
        }

        $query = P4Cms_Record_Query::create()->addFilter($filter);
        return P4Cms_Content::fetchAll($query);
    }

    /**
     * Helper function to verify json response of changed-state action.
     *
     * @param   array       $changedEntries     list of expected entry ids that have been changed
     * @param   array       $failedEntries      list of expected entry ids that have failed
     * @param   string|null $errors             expected errors
     */
    public function _verifyChangeStateJsonResponse(
        array $changedEntries, array $failedEntries = array(), $errors = null
    )
    {
        $response = Zend_Json::decode($this->response->getBody());
        $this->assertSame(
            $changedEntries,
            $response['changedEntries'],
            "Expected list of changed entries."
        );
        $this->assertSame(
            $failedEntries,
            $response['failedEntries'],
            "Expected list of failed entries."
        );
        $this->assertSame(
            $errors,
            $response['errors'],
            "Expected errors message."
        );
    }

    /**
     * Helper function, verify that all given entries (specified by their ids)
     * have given workflow state. Also verifies, that they have been submitted
     * in one change list.
     *
     * @param   array       $entryIds           list with entry ids to verify
     * @param   string      $state              workflow state assumed to be the current
     *                                          entries state of workflow
     * @param   string|null $scheduledState     optional, workflow state assumed to be
     *                                          scheduled for the entries, if null then
     *                                          it won't be checked
     * @param   string|null $scheduledTime      optional, timestamp of scheduled transition
     *                                          for given entries
     */
    public function _verifyEntriesWorkflow(
        array $entryIds, $state, $scheduledState = null, $scheduledTime = null
    )
    {
        $workflowsByType = Workflow_Model_Workflow::fetchTypeMap();

        // ensure all entries have given state and they have been submitted in the same changelist
        $entryChanges = array();
        $entries      = P4Cms_Content::fetchAll(array('ids' => $entryIds));
        foreach ($entries as $entry) {
            $type = $entry->getContentTypeId();
            if (!isset($workflowsByType[$type])) {
                $this->fail("Unexpected entry with no workflow.");
            }

            $workflow = $workflowsByType[$type];
            $this->assertSame(
                $state,
                $workflow->getStateOf($entry)->getId(),
                "Expected entry has '$state' workflow state."
            );

            if ($scheduledState) {
                $this->assertSame(
                    $scheduledState,
                    $workflow->getScheduledStateOf($entry)->getId(),
                    "Expected entry has '$scheduledState' as scheduled workflow state."
                );
            }

            if ($scheduledTime) {
                $this->assertSame(
                    $scheduledTime,
                    $workflow->getScheduledTimeOf($entry),
                    "Expected scheduled time of the scheduled workflow transition."
                );
            }

            $entryChanges[] = $entry->toP4File()->getChange()->getId();
        }

        // ensure that entryChangelist contains just one unique value
        $this->assertSame(
            1,
            count(array_unique($entryChanges)),
            "Expected all entries have been submitted in the same changelist."
        );
    }

    /**
     * Helper function, creates workflows, content types (one for each workflow +
     * one with no workflow) and optionally specified number of entries for each
     * content type, ids are derived from content type ids with appended number 1
     * to <number of entries>.
     *
     * @param   int $entriesPerType     optional, number entries to create for each
     *                                  content type
     */
    protected function _createWorkflowsAndTypes($entriesPerType = 0)
    {
        // create workflows with following states (and labels):
        //  simple  : draft (Draft), review (Review), published (Published)
        //  basic   : draft (Preliminary), published (Live)
        //  private : draft (Private), published (Public), unable to transit from draft to published
        Workflow_Model_Workflow::store(
            array(
                'id'        => 'simple',
                'states'    => "
[draft]
label = 'Draft'
transitions.review.label = 'Promote to reviw'
transitions.published.label = 'Publish'

[review]
label = 'Review'
transitions.draft.label = 'Demote to Draft'
transitions.published.label = 'Publish'

[published]
label = 'Published'
transitions.review.label = 'Demote to Review'
transitions.draft.label = 'Demote to Draft'"
            )
        );

        Workflow_Model_Workflow::store(
            array(
                'id'        => 'basic',
                'states'    => "
[draft]
label = 'Preliminary'
transitions.published.label = 'Go Live'

[published]
label = 'Live'
transitions.draft.label = 'Demote to Preliminary'"
            )
        );

        Workflow_Model_Workflow::store(
            array(
                'id'        => 'private',
                'states'    => "
[draft]
label = 'Private'
transitions.published.label = 'Publish'
transitions.published.conditions[] = 'False'

[published]
label = 'Public'
transitions.draft.label = 'Demote to Private'"
            )
        );

        // create content types, one for each workflow + one with no workflow
        foreach (Workflow_Model_Workflow::fetchAll() as $workflow) {
            P4Cms_Content_Type::store(
                array(
                    'id'        => $workflow->getId(),
                    'elements'  => array(
                        'title' => array(
                            'type'  => 'text'
                        )
                    ),
                    'workflow'  => $workflow->getId()
                )
            );
        }
        P4Cms_Content_Type::store(
            array(
                'id'        => 'no-workflow',
                'elements'  => array(
                    'title' => array(
                        'type'  => 'text'
                    )
                )
            )
        );

        // create content
        if ($entriesPerType) {
            $entry = P4Cms_Content::create(
                array('title' => 'test')
            );

            foreach (P4Cms_Content_Type::fetchAll() as $type) {
                $typeId = $type->getId();
                $entry->setContentType($typeId);
                for ($i = 1; $i <= $entriesPerType; $i++) {
                    $entry->setId($typeId . $i);
                    $entry->save();
                }
            }
        }
    }
}