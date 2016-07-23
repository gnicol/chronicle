<?php
/**
 * Test methods for the P4 Log class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_LogTest extends TestCase
{
    /**
     * Test logger manipulation.
     */
    public function testBasicOperation()
    {
        P4_Log::setLogger(null);
        $this->assertFalse(P4_Log::hasLogger());

        // expect success writing with no logger set.
        P4_Log::log('test', P4_Log::INFO);

        // set a bad logger.
        try {
            P4_Log::setLogger('bob');
            $this->fail("Expected exception setting bad logger.");
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        // try setting a legit logger.
        $stream = fopen("php://temp", "a");
        $writer = new Zend_Log_Writer_Stream($stream);
        $logger = new Zend_Log($writer);
        P4_Log::setLogger($logger);
        $this->assertTrue(P4_Log::hasLogger());
        $this->assertSame(
            $logger,
            P4_Log::getLogger(),
            'Expect the set logger'
        );

        // try logging.
        $logData = stream_get_contents($stream, -1, 0);
        $this->assertTrue(strlen($logData) == 0, 'Expect log to not contain data');
        P4_Log::log('test', P4_Log::INFO);
        $logData = stream_get_contents($stream, -1, 0);
        $this->assertTrue(strlen($logData) > 0, 'Expect log to contain data');
        $this->assertRegexp('/test/', $logData, 'Expect log message in log');

        P4_Log::log('something else');
        $logData = stream_get_contents($stream, -1, 0);
        $this->assertRegexp('/something else/', $logData, 'Expect second log message in log');

        // try logging an exception
        $e = new InvalidArgumentException('poof');
        P4_Log::logException('my log message', $e);
        $logData = stream_get_contents($stream, -1, 0);
        $this->assertRegexp('/my log message/', $logData, 'Expect exception message in log');

        // try logging a bogus exception
        P4_Log::logException('bad', 'badexception');
        $logData = stream_get_contents($stream, -1, 0);
        $this->assertRegexp('/bad/', $logData, 'Expect second exception message in log');
        $this->assertNotRegexp(
            '/badexception/',
            $logData,
            'Expect second exception bad exception message to not be in log'
        );
    }
}
