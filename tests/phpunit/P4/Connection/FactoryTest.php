<?php
/**
 * Test methods for the P4_Connection class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Connection_FactoryTest extends TestCase
{
    protected $_clients;

    /**
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();

        // create extension client implementation if perforce
        // extension is available.
        if (extension_loaded('perforce')) {
            $this->_clients[] = P4_Connection::factory(
                $this->utility->getP4Params('port'),
                $this->utility->getP4Params('user'),
                $this->utility->getP4Params('client'),
                $this->utility->getP4Params('password'),
                null,
                'P4_Connection_Extension'
            );
        }

        // create commandline client implementation
        $this->_clients[] = P4_Connection::factory(
            $this->utility->getP4Params('port'),
            $this->utility->getP4Params('user'),
            $this->utility->getP4Params('client'),
            $this->utility->getP4Params('password'),
            null,
            'P4_Connection_CommandLine'
        );
    }

    /**
     * Clear app name static.
     */
    public function tearDown()
    {
        P4_Connection::setAppName(null);

        parent::tearDown();
    }

    /**
     * Test that the factory method functions properly.
     */
    public function testValidTypeCreation()
    {
        // verify that each client created is of the correct type.
        foreach ($this->_clients as $client) {
            $this->assertTrue(
                $client instanceof P4_Connection_Interface,
                'Expected client object type'
            );
        }
    }

    /**
     * Attempting to create a P4 connection with a non-existing type should
     * result in an exception being thrown.
     *
     * @expectedException P4_Exception
     */
    public function testBadTypeCreation()
    {
        $type = 'Bogus_Type';
        $this->assertFalse(class_exists($type), 'Expect bogus class to not exist');
        $connection = P4_Connection::factory(
            null, null, null, null, null, $type
        );
    }

    /**
     * Test app name
     */
    public function testAppName()
    {
        P4_Connection::setAppName('test-name');

        $p4 = P4_Connection::factory(
            $this->utility->getP4Params('port'),
            $this->utility->getP4Params('user'),
            $this->utility->getP4Params('client'),
            $this->utility->getP4Params('password')
        );

        $this->assertSame('test-name', $p4->getAppName());
    }

    /**
     * Test the Connection identity method.
     */
    public function testConnectionIdentity()
    {
        $identity = P4_Connection::getConnectionIdentity();
        $this->assertTrue(is_array($identity), 'Expect identity array');
        $this->assertSame(sizeof($identity), 8, 'Expect 8 identities');
        $this->assertArrayHasKey('name',       $identity, 'Expect name identity');
        $this->assertArrayHasKey('platform',   $identity, 'Expect platform identity');
        $this->assertArrayHasKey('version',    $identity, 'Expect version identity');
        $this->assertArrayHasKey('build',      $identity, 'Expect build identity');
        $this->assertArrayHasKey('apiversion', $identity, 'Expect apiversion identity');
        $this->assertArrayHasKey('apibuild',   $identity, 'Expect apibuild identity');
        $this->assertArrayHasKey('date',       $identity, 'Expect date identity');
        $this->assertArrayHasKey('original',   $identity, 'Expect original identity');
    }
}
