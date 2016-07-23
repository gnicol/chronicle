<?php
/**
 * Test methods for the P4 Model Iterator.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_ConnectionTest extends TestCase
{
    /**
     * Test setDefaultConnection.
     */
    public function testSetDefaultConnection()
    {
        // test an invalid connection
        try {
            P4_Connection::setDefaultConnection(null);
            $this->fail('Unexpected success setting empty default connection.');
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        // test a valid connection.
        $connection = new P4_Connection_CommandLine;
        P4_Connection::setDefaultConnection($connection);
        $this->assertSame(
            $connection,
            P4_Connection::getDefaultConnection(),
            'Expected connection'
        );
    }

    /**
     * Test isValidType.
     */
    public function testIsValidType()
    {
        $tests = array(
            ''                          => false,
            'bogus'                     => false,
            'P4_File'                   => false,
            'P4_Connection_CommandLine' => true,
            'P4_Connection_Extension'   => true,
        );

        foreach ($tests as $class => $expectation) {
            $this->assertSame(
                $expectation,
                P4_Connection::isValidType($class),
                "Expected result for '$class'"
            );
        }
    }

    /**
     * Test getClientRoot with invalid client.
     */
    public function testGetClientRoot()
    {
        // by default, the test suite can connect; test the normal case
        $connection = $this->utility->createP4Connection();
        $this->assertSame(
            realpath($this->utility->getP4Params('clientRoot') .'/superuser'),
            realpath($connection->getClientRoot()),
            'Expected client root'
        );

        // skip the following test if P4PHP is loaded; we cannot manipulate P4PHP
        // to make this test pass.
        if (!extension_loaded('perforce')) {

            // now override P4 to get unexpected behaviour
            $connection->clearInfo();
            $script = TEST_SCRIPTS_PATH . '/serializedArray.';
            $script .= P4_Environment::isWindows() ? 'bat' : 'sh';
            $connection->setP4Path($script);
            $this->assertSame(
                false,
                $connection->getClientRoot(),
                'Expect no root for bogus P4'
            );
        }
    }
}
