<?php
/**
 * Test the cron index controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 *
 */
class Cron_Test_IndexControllerTest extends ModuleControllerTest
{
    /**
     * Test index action.
     */
    public function testIndexAction()
    {
        $this->utility->impersonate('anonymous');

        // remember the current hour, we will use it later to check which cron tasks
        // should be triggered by the following cron runs
        $currentHour = date('H');

        // verify that cron is accessible
        $this->dispatch('/cron');

        $this->assertModule('cron', 'Expected module for dispatching /cron action.');
        $this->assertController('index', 'Expected controller for dispatching /cron action.');
        $this->assertAction('index', 'Expected action for dispatching /cron action.');

        // verify json output
        $body = $this->response->getBody();
        $data = Zend_Json::decode($body);
        $this->assertSame(
            array(
                'hourly'   => 'executed',
                'daily'    => 'executed',
                'weekly'   => 'executed',
                'monthly'  => 'executed'
            ),
            $data,
            'Expected all cron frequencies have been executed.'
        );

        // dispatch again and verify that no cron tasks were executed
        $this->resetRequest()
             ->resetResponse();

        $this->dispatch('/cron');

        $this->assertModule('cron', 'Expected module for dispatching /cron action.');
        $this->assertController('index', 'Expected controller for dispatching /cron action.');
        $this->assertAction('index', 'Expected action for dispatching /cron action.');

        // verify json output; in most cases no cron tasks should be executed,
        // however we have to be careful as we might be in an unlikely situation
        // when the hour/day/week or month crossed between this and the previous
        // cron run
        if (date('H') !== $currentHour) {
            // this means that there is a possibility that the hour crossed between the
            // last two cron executions, however we are not certain as the first cron
            // run might have been executed in this hour also - skip the test
            $this->markTestSkipped("Skipped cron test due to possible hour cross.");
        }

        $body = $this->response->getBody();
        $data = Zend_Json::decode($body);
        $this->assertSame(
            array(
                'hourly'   => 'skipped',
                'daily'    => 'skipped',
                'weekly'   => 'skipped',
                'monthly'  => 'skipped'
            ),
            $data,
            'Expected no cron frequencies have been executed.'
        );
    }
}