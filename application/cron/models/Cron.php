<?php
/**
 * Provides infrastructure for executing periodic time-based tasks in the application.
 *
 * Tasks are executed by third-parties via subscribing to the 'p4cms.cron.<FREQUENCY>'
 * topics, where <FREQUENCY> determines time-periods of publishing those topics.
 *
 * Currently, model handles the following frequencies:
 *
 *   hourly - 'p4cms.cron.hourly' topic is published (at the most) once per hour
 *    daily - 'p4cms.cron.daily' topic is published (at the most) once per day
 *   weekly - 'p4cms.cron.weekly' topic is published (at the most) once per week
 *  monthly - 'p4cms.cron.monthly' topic is published (at the most) once per month
 *
 * Pub-sub topics are published by calling Cron_Model_Cron::run() method, that
 * publishes only topics there were previously published sufficiently long ago
 * according to the frequency. This method also returns a report, to check
 * which topics were actually published, in the form of associative array with
 * frequencies as keys and statuses as values, where status is one of the following:
 *
 *  executed - if topic was successfully published,
 *   skipped - if topic was not published (ie. previously executed)
 *    failed - if there were errors during publishing the topic (however it doesn't
 *              necessarily mean that topic was published)
 *
 * Every time the topic is published, 2 new revisions of the cron file (for each
 * frequency) are created. One just before and one just after publishing the topic.
 * At the first update, execution timestamp is saved and 'completed' attribute is
 * set to false. At the second update, 'completed' attribute is set to true and data
 * collected from subscribers are stored in the 'data' attribute.
 *
 * Whereas this module guarantees that particular topics are not published too often
 * (i.e. not more than once per hour for p4cms.cron.hourly topic, not more than once
 * per day for p4cms.cron.daily topic etc.), it does not guarantee that
 * 'p4cms.cron.hourly' topic will be published every hour and similarly for other
 * frequencies, as it depends on calling Cron_Model_Cron::run() method sufficiently
 * often.
 *
 * Typically, this can be ensured by setting up a cron job on a server with a frequency
 * of at least once per hour. For example:
 *
 *  0 * * * * wget -O - -q -t 1 http://example.com/cron
 *
 * The above line executes wget once every hour (at the 'top' of the hour). The '-O -'
 * arguments tell wget to write output to standard out, '-q' runs quietly and '-t 1'
 * limits retries to one. If you do not have wget, try curl with comparable options.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Cron_Model_Cron extends P4Cms_Record
{
    const               CRON_HOURLY         = 'hourly';
    const               CRON_DAILY          = 'daily';
    const               CRON_WEEKLY         = 'weekly';
    const               CRON_MONTHLY        = 'monthly';

    const               RESULT_EXECUTED     = 'executed';
    const               RESULT_SKIPPED      = 'skipped';
    const               RESULT_FAILED       = 'failed';

    protected static    $_storageSubPath    = 'cron';
    protected static    $_fields            = array(
        'runTime'       => array(
            'accessor'  => 'getRunTime',
            'mutator'   => 'setRunTime'
        ),
        'completed'     => array(
            'accessor'  => 'getCompleted',
            'mutator'   => 'setCompleted'
        ),
        'data'          => array(
            'accessor'  => 'setData',
            'mutator'   => 'getData'
        )
    );

    /**
     * Get cron runTime (timestamp of the most recent cron execution).
     *
     * @return  int     runTime timestamp
     */
    public function getRunTime()
    {
        return (int) $this->_getValue('runTime');
    }

    /**
     * Set runTime timestamp.
     *
     * @param   int     $timestamp  unix timestamp
     * @return  Cron_Model_Cron     provides fluent interface
     */
    public function setRunTime($timestamp)
    {
        if (!is_int($timestamp) || $timestamp < 0) {
            throw new InvalidArgumentException("Timestamp must be a non-negative integer");
        }

        $this->_setValue('runTime', $timestamp);
        return $this;
    }

    /**
     * Get info whether last cron execution was completed.
     *
     * @return  boolean     true if last cron execution was completed, false otherwise
     */
    public function getCompleted()
    {
        return (boolean) $this->_getValue('completed');
    }

    /**
     * Set whether last cron execution was completed or not.
     *
     * @param   boolean     $completed  set true if last cron execution was completed,
     *                                  false otherwise
     * @return  Cron_Model_Cron         provides fluent interface
     */
    public function setCompleted($completed)
    {
        $this->_setValue('completed', $completed ? '1' : '0');
        return $this;
    }

    /**
     * Set additional data about the cron execution.
     *
     * @param   array   $data       data to record with cron execution
     * @return  Cron_Model_Cron     provides fluent interface
     */
    public function setData(array $data)
    {
        $this->_setValue('data', $data);
        return $this;
    }

    /**
     * Get cron data.
     *
     * @return  array   list of data about cron execution.
     */
    public function getData()
    {
        return $this->_getValue('data');
    }

    /**
     * Run cron (if needed) for given frequencies specified in the optional parameter
     * (all by default). Every cron run (for a given frequency) updates cron/<FREQUENCY>
     * record, where <FREQUENCY> is one of hourly|daily|weekly|monthly.
     *
     * @param   array                   $frequencies    (optional) list of frequencies to run cron for,
     *                                                  defaults to all
     * @param   int                     $timestamp      (optional) UNIX timestamp to use for
     *                                                  determining if cron needs to be run
     *                                                  (useful mostly for testing);
     *                                                  if not provided, then current timestamp
     *                                                  will be used
     * @param   P4Cms_Record_Adapter    $adapter        optional - storage adapter to use.
     * @return  array                   list with run report for each frequency, report is one of
     *                                  executed|skipped|failed.
     */
    public static function run(
        array $frequencies = null,
        $timestamp = null,
        P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        $all = array(
            static::CRON_HOURLY,
            static::CRON_DAILY,
            static::CRON_WEEKLY,
            static::CRON_MONTHLY
        );

        // filter for only valid frequencies, fall back to all frequency types if none passed
        $frequencies = array_intersect($frequencies ?: $all, $all);

        // if no timestamp is provided, set to current
        if (!$timestamp) {
            $timestamp = time();
        }

        // get list of frequencies to run cron for
        $needRun = static::_needRun($frequencies, $timestamp, $adapter);

        // iterate over all frequencies and run cron if needed
        $report = array();
        foreach ($frequencies as $frequency) {
            if (!in_array($frequency, $needRun)) {
                $report[$frequency] = static::RESULT_SKIPPED;
                continue;
            }

            // cron jobs should run as a special type of anonymous
            // user that can be granted additional privileges.
            $activeUser = P4Cms_User::hasActive()
                ? P4Cms_User::fetchActive()
                : null;
            P4Cms_User::setActive(new Cron_Model_User);

            try {
                static::_run($frequency, $timestamp, $adapter);
                $result = static::RESULT_EXECUTED;
            } catch (Exception $e) {
                P4Cms_Log::logException("Cron run failed [frequency: $frequency].", $e);
                $result = static::RESULT_FAILED;
            }

            // after each cron job, restore original active user.
            if ($activeUser) {
                P4Cms_User::setActive($activeUser);
            } else {
                P4Cms_User::clearActive();
            }

            $report[$frequency] = $result;
        }

        return $report;
    }

    /**
     * Execute cron for a given frequency. Publishes 'p4cms.cron.<frequency>' topic
     * to let subscribers executing their cron tasks. Subscribers can optionally
     * return list of messages that will be saved in the cron log file.
     *
     * @param   string                  $frequency  frequency to run cron for (it should be one
     *                                              of hourly|daily|weekly|monthly)
     * @param   string                  $timestamp  unix timestamp to run cron at (useful for
     *                                              testing, in real case this should refer to
     *                                              the current time)
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     *
     * @publishes   p4cms.cron.hourly
     *              Perform periodic operations intended to execute once per hour. Any returned text
     *              will be saved in the cron log.
     *              (requires additional settings on a server, or activation of the Easy_Cron
     *              module. Please see <xref linkend="site.time-based-tasks"/>)
     *
     * @publishes   p4cms.cron.daily
     *              Perform periodic operations intended to execute once per day. Any returned text
     *              will be saved in the cron log.
     *              (requires additional settings on a server, or activation of the Easy_Cron
     *              module. Please see <xref linkend="site.time-based-tasks"/>)
     *
     * @publishes   p4cms.cron.weekly
     *              Perform periodic operations intended to execute once per week. Any returned text
     *              will be saved in the cron log.
     *              (requires additional settings on a server, or activation of the Easy_Cron
     *              module. Please see <xref linkend="site.time-based-tasks"/>)
     *
     * @publishes   p4cms.cron.monthly
     *              Perform periodic operations intended to execute once per month. Any returned
     *              text will be saved in the cron log.
     *              (requires additional settings on a server, or activation of the Easy_Cron
     *              module. Please see <xref linkend="site.time-based-tasks"/>)
     */
    protected static function _run(
        $frequency,
        $timestamp,
        P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        // prepare messages to use later
        $cronStarted   = "Cron $frequency started.";
        $cronCompleted = "Cron $frequency completed.";

        // update cron file immediately, so if another cron check is started
        // before this run is done, it won't be executed again
        $cron = new static;
        $cron->setAdapter($adapter)
             ->setId($frequency)
             ->setRunTime($timestamp)
             ->setCompleted(false);

        // set filetype to ctext to improve performance as there may be a huge
        // number of revisions in the future
        $file = $cron->_getP4File();
        $file->touchLocalFile()
             ->open(null, 'text+C');

        $cron->save($cronStarted);

        // publish cron topic to allow subscribers to execute their cron tasks
        // collect messages that subscribers can optionally return
        P4Cms_Log::log($cronStarted, P4Cms_Log::NOTICE);
        $topic    = 'p4cms.cron.' . $frequency;
        $messages = P4Cms_PubSub::publish($topic);

        // add collected messages and save cron as completed
        $cron->setData($messages)
             ->setCompleted(true)
             ->save($cronCompleted);
        P4Cms_Log::log($cronCompleted, P4Cms_Log::NOTICE);
    }

    /**
     * Check if cron needs to be run for given frequencies. Returns list of
     * only those frequencies the cron needs to be run for.
     *
     * @param   array                   $frequencies    list of frequencies to check for
     * @param   string                  $timestamp      unix timestamp to run cron at
     * @param   P4Cms_Record_Adapter    $adapter        optional - storage adapter to use.
     * @return  array                   list of frequencies from $frequencies the cron needs
     *                                  to be run for.
     */
    protected static function _needRun(
        array $frequencies,
        $timestamp,
        P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        // fetch existing cron files for given frequencies
        $records = static::fetchAll(array('ids' => $frequencies));

        // assume cron needs to be run for all frequencies that don't have cron
        // record yet
        $needRun = array_diff($frequencies, $records->invoke('getId'));

        // iterate over existing cron files for given frequencies and determine
        // which of them need to be updated by comparing truncated timestamp
        // of the last cron run with truncated $timestamp provided
        foreach ($records as $cron) {
            $runTime            = $cron->getRunTime();
            $frequency          = $cron->getId();
            $truncatedRunTime   = static::_truncateTimestamp($runTime,   $frequency);
            $truncatedTimestamp = static::_truncateTimestamp($timestamp, $frequency);

            // cron needs to be updated if truncated timestamps are different
            if ($truncatedRunTime !== $truncatedTimestamp) {
                $needRun[] = $frequency;
            }
        }

        return $needRun;
    }

    /**
     * Return timestamp truncated with respect to the given frequency.
     *
     * Following parts from the timestamp are truncated for the given frequencies:
     *
     *   hourly: minutes + seconds
     *    daily: hours + minutes + seconds
     *   weekly: days in the week (since Monday) + hours + minutes + seconds
     *  monthly: days in the month + hours + minutes + seconds
     *
     * Example:
     *  January 2nd 2011 15:45:27 will be truncated to:
     *
     *  January   2nd 2011 15:00:00 for the hourly frequency,
     *  January   2nd 2011 00:00:00 for the daily frequency,
     *  December 26th 2010 00:00:00 for the weekly frequency and
     *  January   1st 2011 00:00:00 for the monthly frequency
     *
     * @param   int         $timestamp  unix timestamp to truncate
     * @param   string      $frequency  frequency to truncate $timestamp according to
     * @return  int|null    unix timestamp of truncated $timestamp with respect to
     *                      $frequency or null if not able to truncate
     */
    protected static function _truncateTimestamp($timestamp, $frequency)
    {
        // get formatted string representing datetime of the truncated timestamp
        switch ($frequency) {
            case static::CRON_HOURLY:
                $datetime = date('Y-m-d H:00', $timestamp);
                break;
            case static::CRON_DAILY:
                $datetime = date('Y-m-d', $timestamp);
                break;
            case static::CRON_WEEKLY:
                $datetime = date('Y-\WW', $timestamp);
                break;
            case static::CRON_MONTHLY:
                $datetime = date('Y-m', $timestamp);
                break;
        }

        // convert datetime to the timestamp and return
        return isset($datetime) ? strtotime($datetime) : null;
    }
}