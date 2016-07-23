<?php
/**
 * Test methods for the P4_Connection class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Connection_CommandExceptionTest extends TestCase
{
    /**
     * Test connection handling.
     */
    public function testConnections()
    {
        $e = new P4_Connection_CommandException('test');

        // set retrieving an unset connection.
        $connection = $e->getConnection();
        $this->assertSame(null, $connection, 'Expected null connection');

        // test setting a bogus connection
        try {
            $e->setConnection('bogus');
            $this->fail("Expected exception setting bogus connection");
        } catch (Exception $exception) {
            $this->assertTrue(true);
        }

        // test setting a real connection
        $connection = P4_Connection::factory(
            $this->utility->getP4Params('port'),
            $this->utility->getP4Params('user'),
            $this->utility->getP4Params('client'),
            $this->utility->getP4Params('password'),
            null,
            'P4_Connection_CommandLine'
        );
        $e->setConnection($connection);
        $this->assertSame($connection, $e->getConnection(), 'Expected real connection');
    }

    /**
     * Test result handling.
     */
    public function testResult()
    {
        $e = new P4_Connection_CommandException('another_test');

        // set retrieving an unset result.
        $result = $e->getResult();
        $this->assertSame(null, $result, 'Expected null result');

        // test setting a bogus result
        $e->setResult('bogus');
        $this->assertSame(null, $e->getResult(), 'Expected bogus result');

        // test setting a real result
        $result = $this->p4->run('info');
        $e->setResult($result);
        $this->assertSame($result, $e->getResult(), 'Expected real result');
    }

}
