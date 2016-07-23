<?php
/**
 * Test methods for the P4 Depot class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_DepotTest extends TestCase
{
    /**
     * Test initial conditions.
     */
    public function testInitialConditions()
    {
        // assume there is one local depot
        $depots = P4_Depot::fetchAll();
        $this->assertSame(1, count($depots), 'Expected depots at start.');

        $depot = $depots->first();

        $this->assertSame('depot',      $depot->getId(),    "Expected depot 'depot' at start.");
        $this->assertSame('local',      $depot->getType(),  "Expected local depot at start.");
        $this->assertSame('depot/...',  $depot->getMap(),   "Expected mapping of depot at start.");
    }

    /**
     * Test fetch() method.
     */
    public function testFetch()
    {
        // create new depot
        $depot = new P4_Depot;
        $depot
            ->setId('foo-depot')
            ->setValues(
                array(
                    'Type'  => 'local',
                    'Map'   => 'foo/...'
                )
            )
            ->save();

        $depot = P4_Depot::fetch('foo-depot');
        $this->assertTrue(
            $depot instanceof P4_Depot,
            "Expected fetch returns instance of P4_Depot."
        );
        $this->assertSame(
            'local',
            $depot->getType(),
            "Expected type of fetched depot."
        );
        $this->assertSame(
            'foo/...',
            $depot->getmap(),
            "Expected type of fetched depot."
        );

        // verify fetching a non-existant depot throws an exception
        $depot->delete();
        try {
            P4_Depot::fetch('foo-depot');
        } catch (P4_Spec_NotFoundException $e) {
            // expected exception
            $this->assertTrue(true);
        }
    }

    /**
     * Test exist() method.
     */
    public function testExist()
    {
        // verify required fields (depot, type, map) must be set before save
        $depot = new P4_Depot;
        try {
            $depot->save();
            $this->fail("Unexpected possibility of saving empty depot.");
        } catch (P4_Spec_Exception $e) {
            // expected exception
            $this->assertTrue(true);
        }

        $depot->setValues(
            array(
                'Depot' => 'test',
                'Type'  => 'local',
                'Map'   => 'test/...'
            )
        );

        $depot->save();
        $this->assertTrue(P4_Depot::exists('test'), "Expected existence of 'test' depot.");

        // query non-existant depot
        $this->assertFalse(P4_Depot::exists('non-exist'), "Expected exist() returns false for non-existant depot.");
    }

    /**
     * Test accessors/mutators.
     */
    public function testAccessorsMutators()
    {
        $depot = new P4_Depot;
        $tests = array(
            'Depot'         => 'tdepot',
            'Owner'         => 'town',
            'Description'   => 'tdesc',
            'Type'          => 'local',
            'Address'       => 'taddr',
            'Suffix'        => '.tsuf',
            'Map'           => 'tmap/...'
        );

        foreach ($tests as $key => $value) {
            $depot->setValue($key, $value);
            $this->assertSame($value, $depot->getValue($key), "Expected value for $key");
        }

        // verify again on fetched depot
        $expected = array(
            'Depot'         => 'tdepot',
            'Owner'         => 'town',
            'Description'   => "tdesc\n",
            'Type'          => 'local',
            'Map'           => 'tmap/...'
        );

        $depot->save();
        $depot = P4_Depot::fetch('tdepot');
        
        foreach ($expected as $key => $value) {
            $this->assertSame($value, $depot->getValue($key), "Expected value for $key after fetch");
        }
    }

    /**
     * Verify that its possible to save a client with mapping the new depot into the view.
     */
    public function testCreateClient()
    {
        // create new deopt
        $depot = new P4_Depot;
        $depot->setValues(
            array(
                'Depot'         => 'tdep',
                'Type'          => 'local',
                'Map'           => 'tdep/...'
            )
        );
        $depot->save();
        $this->assertTrue(P4_Depot::exists('tdep'));

        // at this point we have to disconnect as Perforce doesn't let
        // creating new client with mapping a depot created by the same
        // connection
        // @todo remove when bug is fixed
        $this->p4->disconnect();

        // create client mapping the new depot
        $client = new P4_Client;
        $client->setValues(
            array(
                'Client'        => 'foo',
                'Root'          => '/tmp/tcli',
                'View'          => array(
                    array(
                        'depot'     => '//tdep/...',
                        'client'    => '//foo/a/...'
                    )
                )
            )
        );
        $client->save();
        $this->assertTrue(P4_Client::exists('foo'));
        $this->assertSame(
            array(
                0 => array(
                    'depot'     => '//tdep/...',
                    'client'    => '//foo/a/...'
                )
            ),
            P4_Client::fetch('foo')->getView(),
            "Expected view of fetched client matches saved values."
        );
    }
}
