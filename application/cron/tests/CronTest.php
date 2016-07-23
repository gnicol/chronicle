<?php
/**
 * Test the cron model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Cron_Test_CronTest extends ModuleTest
{
    protected static $_counters = array();

    /**
     * Test run() method where each cron run should trigger all cycles.
     */
    public function testRunIndependent()
    {
        $tests = array(
            array(
                'line'      => __LINE__,
                'date'      => '2011-01-01 15:45:27'
            ),
            array(
                'line'      => __LINE__,
                'date'      => '2001-11-17 00:00:01'
            ),
            array(
                'line'      => __LINE__,
                'date'      => '2007-02-25 07:00:01'
            ),
            array(
                'line'      => __LINE__,
                'date'      => '2020-05-31 23:59:59'
            ),
            array(
                'line'      => __LINE__,
                'date'      => '2020-06-01 00:00:00'
            )
        );

        // run tests
        $this->_initSubscribe();
        $revisionsCount = 2;
        $cronFiles      = array('hourly', 'daily', 'weekly', 'monthly');

        foreach ($tests as $test) {
            static::_resetCounters();
            $timestamp = strtotime($test['date']);
            $result    = Cron_Model_Cron::run(null, $timestamp);

            $this->_verifyResult($result);
            $this->_verifyCounters(
                array('hourly' => 1, 'daily' => 1, 'weekly' => 1, 'monthly' => 1),
                "Line {$test['line']}: Expected each cron cycle was run once."
            );

            // verify cron file revisions and attributes
            foreach ($cronFiles as $record) {
                $cronFile = Cron_Model_Cron::fetch($record);

                // ensure 2 new revisions of each cron file have been created
                $this->assertSame(
                    $revisionsCount,
                    count($cronFile->toP4File()->getChanges()),
                    "Line {$test['line']}: Expected 2 new revisions created."
                );

                // verify last run attribute
                $this->assertSame(
                    $timestamp,
                    $cronFile->getRunTime(),
                    "Line {$test['line']}: Expected value for lastRun attribute."
                );

                // verify completed attribute
                $this->assertSame(
                    true,
                    $cronFile->getCompleted(),
                    "Line {$test['line']}: Expected value for completed attribute."
                );

                // ensure that returned messages are contained in the data attribute
                // of the cron file
                $messages = array(
                    "foo",
                    "p4cms.cron.$record published"
                );

                $this->assertTrue(
                    in_array($messages, $cronFile->getData()),
                    "Line {$test['line']}: Expected messages are captured in the cron data attribute."
                );

                // verify filetype of cron log file
                $this->assertSame(
                    'ctext',
                    $cronFile->toP4File()->getStatus('headType'),
                    "LINE {$test['line']}: Expected ctext filetype"
                );
            }

            $revisionsCount += 2;
        }
    }

    /**
     * Test run() method where each run may not trigger all cycles.
     */
    public function testRunDependent()
    {
        $this->_initSubscribe();

        static::_resetCounters();
        $timestamp = strtotime("2012-09-02 13:13:13");
        $result    = Cron_Model_Cron::run(null, $timestamp);

        $this->_verifyResult($result);
        $this->_verifyCounters(
            array('hourly' => 1, 'daily' => 1, 'weekly' => 1, 'monthly' => 1),
            "Expected each cron cycle was run once."
        );

        // run the same cron and ensure no cycles were run
        static::_resetCounters();
        $result = Cron_Model_Cron::run(null, $timestamp);

        $this->_verifyResult($result, 'ssss');
        $this->_verifyCounters(
            array(),
            "Expected no cron cycle was run."
        );

        // run with time shifted towards by half an hour
        $timestamp = strtotime("2012-09-02 13:43:13");
        static::_resetCounters();
        $result = Cron_Model_Cron::run(null, $timestamp);
        $this->_verifyResult($result, 'ssss');
        $this->_verifyCounters(
            array(),
            "Unexpected cron run cycle."
        );

        // run with time in next hour, should update only cron.hourly
        $timestamp = strtotime("2012-09-02 14:02:01");
        static::_resetCounters();
        $result = Cron_Model_Cron::run(null, $timestamp);

        $this->_verifyResult($result, 'esss');
        $this->_verifyCounters(
            array('hourly' => 1),
            "Unexpected cron run cycle."
        );

        // run next day
        $timestamp = strtotime("2012-09-03 01:00:05");
        static::_resetCounters();
        $result = Cron_Model_Cron::run(null, $timestamp);

        $this->_verifyResult($result, 'eees');
        $this->_verifyCounters(
            array('hourly' => 1, 'daily' => 1, 'weekly' => 1),
            "Unexpected cron run cycle."
        );
        
        // run next day
        $timestamp = strtotime("2012-09-04 01:00:05");
        static::_resetCounters();
        $result = Cron_Model_Cron::run(null, $timestamp);

        $this->_verifyResult($result, 'eess');
        $this->_verifyCounters(
            array('hourly' => 1, 'daily' => 1),
            "Unexpected cron run cycle."
        );
    }

    /**
     * Perform series of cron runs in constant intervals.
     */
    public function testRunPeriodic()
    {
        $this->_initSubscribe();

        $timestamp = strtotime("2012-10-31 00:00:01");
        $result    = Cron_Model_Cron::run(null, $timestamp);
        $this->_verifyResult($result);

        // simulate running cron for a day in 30-min intervals
        for ($i = 1; $i < 24; $i++) {
            // add 30 minutes
            $timestamp += 1800;

            static::_resetCounters();
            $result = Cron_Model_Cron::run(null, $timestamp);

            $expected = $i % 2 === 0 ? array('hourly' => 1) : array();
            $this->_verifyResult($result, count($expected) ? 'esss' : 'ssss');
            $this->_verifyCounters(
                $expected,
                "Unexpected cron run cycle."
            );
        }

        // simulate running cron for a month in 1-day intervals
        $timeStart = strtotime("2010-01-16 00:00:01");
        $timeStop  = strtotime("2010-02-16 00:00:00");
        static::_resetCounters();

        $timestamp = $timeStart;
        while ($timestamp < $timeStop) {
            $result = Cron_Model_Cron::run(null, $timestamp);
            $timestamp += 86400;
        }

        $this->_verifyCounters(
            array('hourly' => 31, 'daily' => 31, 'weekly' => 6, 'monthly' => 2),
            "Unexpected cron run cycle."
        );

        // ensure cron files have ctext filetype
        $records = Cron_Model_Cron::fetchAll();
        $this->assertSame(
            4,
            $records->count(),
            "Expected 4 cron log files."
        );
        foreach ($records as $record) {
            $this->assertSame(
                'ctext',
                $record->toP4File()->getStatus('headType'),
                "Expected ctext filetype of {$record->getId()} file."
            );
        }
    }

    /**
     * Helper function to subscribe to all cron topics.
     */
    protected function _initSubscribe()
    {
        // prepare callback for the pubsub
        $callback = function ($frequency)
        {
            return function() use ($frequency)
            {
                Cron_Test_CronTest::updateCounter($frequency);
                return array(
                    "foo",
                    "p4cms.cron.$frequency published"
                );
            };
        };

        P4Cms_PubSub::subscribe('p4cms.cron.hourly',  $callback('hourly'));
        P4Cms_PubSub::subscribe('p4cms.cron.daily',   $callback('daily'));
        P4Cms_PubSub::subscribe('p4cms.cron.weekly',  $callback('weekly'));
        P4Cms_PubSub::subscribe('p4cms.cron.monthly', $callback('monthly'));
    }

    /**
     * Helper function to compare counters with expected values.
     * 
     * @param   array   $expected   list of expected counters and their values
     * @param   type    $message    (optional) message for assert
     */
    protected function _verifyCounters(array $expected, $message = '')
    {
        $counters = static::$_counters;
        ksort($counters);
        ksort($expected);
        $this->assertSame($expected, $counters, $message);
    }

    /**
     * Helper function to compare result of Cron_Model_Cron::run() method with
     * provided $expected values where $expected is string of length 4 where first
     * letter represents expected result for hourly frequency, second for daily,
     * third for weekly and fourth for monthly. Letters are mapped as follows:
     *  e ... executed
     *  s ... skipped
     *  f ... failed
     *
     * @param   array       $result     result array returned by the Cron_Model_Cron::run()
     *                                  method
     * @param   string|null $expected   (optional) encoded expected result, defaults to 'eeee'
     */
    protected function _verifyResult(array $result, $expected = null)
    {
        if ($expected === null) {
            $expected = 'eeee';
        }

        $cron           = array('hourly', 'daily', 'weekly', 'monthly');
        $expectedResult = array();
        $statusMap      = array(
            'e' => 'executed',
            's' => 'skipped',
            'f' => 'failed'
        );

        foreach ($cron as $index => $frequence) {
            $statusShort = substr($expected, $index, 1);
            $expectedResult[$frequence] = $statusMap[$statusShort];
        }

        $this->assertSame(
            $expectedResult,
            $result,
            "Expected cron run result."
        );
    }

    /**
     * Resets counters.
     */
    protected static function _resetCounters()
    {
        static::$_counters = array();
    }

    /**
     * Updates specified counter by one.
     * 
     * @param   string  $counter    counter to update
     */
    public static function updateCounter($counter)
    {
        if (!isset(static::$_counters[$counter])) {
            static::$_counters[$counter] = 1;
        } else {
            static::$_counters[$counter]++;
        }
    }
}