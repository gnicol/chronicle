<?php
/**
 * Test that workflow is integrated with cron module as expected.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Test_CronIntegrationTest extends ModuleControllerTest
{
    /**
     * Install default content types and workflows as tests below need them.
     */
    public function setUp()
    {
        parent::setUp();

        // install default content types and workflows.
        P4Cms_Content_Type::installDefaultTypes();
        Workflow_Model_Workflow::installDefaultWorkflows();
    }

    /**
     * Test processing scheduled transitions when dispatched to /cron.
     */
    public function testProcessingScheduledTransitions()
    {
        $this->utility->impersonate('editor');

        // create content to test processing scheduled transitions on
        $currentTime = time();
        P4Cms_Content::store(
            array(
                'contentType'            => 'basic-page',
                'id'                     => 'a',
                'workflowState'          => 'draft',
                'workflowScheduledState' => 'review',
                'workflowScheduledTime'  => $currentTime + 100
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'basic-page',
                'id'                     => 'b',
                'workflowState'          => 'draft',
                'workflowScheduledState' => 'review',
                'workflowScheduledTime'  => $currentTime - 100
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'basic-page',
                'id'                     => 'c',
                'workflowState'          => 'draft',
                'workflowScheduledState' => 'published',
                'workflowScheduledTime'  => $currentTime + 100
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'basic-page',
                'id'                     => 'd',
                'workflowState'          => 'draft',
                'workflowScheduledState' => '',
                'workflowScheduledTime'  => $currentTime -100
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'basic-page',
                'id'                     => 'e',
                'workflowState'          => 'draft',
                'workflowScheduledState' => 'review'
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'basic-page',
                'id'                     => 'f',
                'workflowState'          => 'review',
                'workflowScheduledState' => 'published',
                'workflowScheduledTime'  => $currentTime - 100
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'basic-page',
                'id'                     => 'g',
                'workflowState'          => '',
                'workflowScheduledState' => 'review',
                'workflowScheduledTime'  => $currentTime - 100
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'unknown',
                'id'                     => 'h',
                'workflowState'          => 'draft',
                'workflowScheduledState' => 'review',
                'workflowScheduledTime'  => $currentTime - 100
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'basic-page',
                'id'                     => 'i',
                'workflowState'          => 'review',
                'workflowScheduledState' => 'unknown',
                'workflowScheduledTime'  => $currentTime - 100
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'basic-page',
                'id'                     => 'j',
                'workflowState'          => 'unknown',
                'workflowScheduledState' => 'review',
                'workflowScheduledTime'  => $currentTime - 100
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'basic-page',
                'id'                     => 'k',
                'workflowState'          => 'foo',
                'workflowScheduledState' => 'unknown',
                'workflowScheduledTime'  => $currentTime - 100
            )
        );
        P4Cms_Content::store(
            array(
                'contentType'            => 'basic-page',
                'id'                     => 'l',
                'workflowState'          => 'published',
                'workflowScheduledState' => 'draft',
                'workflowScheduledTime'  => $currentTime - 100
            )
        );

        $expectedStatesAfter = array(
            'a' => 'draft',
            'b' => 'review',
            'c' => 'draft',
            'd' => 'draft',
            'e' => 'draft',
            'f' => 'published',
            'g' => 'review',
            'h' => 'draft',
            'i' => 'review',
            'j' => 'review',
            'k' => 'foo',
            'l' => 'draft'
        );

        // set cron user as the active user as its assumed by the 'p4cms.cron.hourly'
        // topic callback
        P4Cms_User::setActive(new Cron_Model_User);

        // ensure it works when topic is published
        P4Cms_PubSub::publish('p4cms.cron.hourly');

        // dispatch to /cron to trigger processing of scheduled transtitions
        $this->dispatch('/cron');

        // fetch entries and compare their current states to the expected list
        $query   = P4Cms_Record_Query::create(array('ids' => array_keys($expectedStatesAfter)));
        $entries = P4Cms_Content::fetchAll($query);

        $this->assertSame(
            count($expectedStatesAfter),
            $entries->count(),
            "Expected number of entries."
        );

        foreach ($entries as $entry) {
            $this->assertSame(
                $expectedStatesAfter[$entry->getId()],
                $entry->getValue('workflowState'),
                "Expected current state of entry '{$entry->getId()}' after processing scheduled transition."
            );
        }
    }
}