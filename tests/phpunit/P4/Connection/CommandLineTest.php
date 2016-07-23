<?php
/**
 * Test methods for the client command line class.
 * Note: inherits tests from interface test.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Connection_CommandLineTest extends P4_Connection_InterfaceTest
{
    /**
     * Override setUp to ensure that the p4 client being used is of the correct
     * type for these tests.
     */
    public function setUp()
    {
        parent::setUp();
        $this->p4 = $this->utility->createP4Connection('P4_Connection_CommandLine');
        P4_Connection::setDefaultConnection($this->p4);
    }

    /**
     * Test connection identity.
     *
     * @todo: need an override path for each platform.
     */
    public function testGetConnectionIdentity()
    {
        $p4 = new P4_Connection_CommandLine;
        $identity = $p4->getConnectionIdentity();
        $this->assertSame(
            array('name', 'platform', 'version', 'build', 'apiversion', 'apibuild', 'date', 'original'),
            array_keys($identity),
            'Expected identity keys'
        );

        // override path with bogus path
        $p4->setP4Path('thiscommandshouldnotexist');
        try {
            $identity = $p4->getConnectionIdentity();
            $this->fail('Unexpected success calling p4 with bogus path');
        } catch (P4_Exception $e) {
            $exitCode = P4_Environment::isWindows() ? 1 : 127;
            $this->assertSame(
                "Unable to exec() the 'p4' command (return: $exitCode).",
                $e->getMessage(),
                'Expected error for bogus path'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected exception calling p4 with bogus path: '.  $e->getMessage());
        }

        // override path non-P4 path
        $script = TEST_SCRIPTS_PATH . '/noOutput.';
        $script .= P4_Environment::isWindows() ? 'bat' : 'sh';
        $p4->setP4Path($script);
        try {
            $identity = $p4->getConnectionIdentity();
            $this->fail('Unexpected success calling p4 with non-P4 path');
        } catch (P4_Exception $e) {
            $this->assertSame(
                "p4 returned an invalid version string",
                $e->getMessage(),
                'Expected error for non-P4 path'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected exception calling p4 with non-P4 path: '.  $e->getMessage());
        }

        // remove override
        $p4->setP4Path(null);
        $identity = $p4->getConnectionIdentity();
        $this->assertSame(
            array('name', 'platform', 'version', 'build', 'apiversion', 'apibuild', 'date', 'original'),
            array_keys($identity),
            'Expected identity keys, again'
        );
    }

    /**
     * Test connect and disconnect.
     */
    public function testConnectDisconnect()
    {
        $p4 = new P4_Connection_CommandLine;
        $this->assertFalse($p4->isConnected(), 'Expected connect status after init');

        try {
            $p4->connect();
            $this->fail("Expected exception when connecting without defined port.");
        } catch (P4_Connection_ConnectException $e) {
            $this->assertTrue(true);
        }

        $p4->setPort($this->p4->getPort());
        try {
            $p4->connect();
            $this->fail("Expected exception when connecting without defined user.");
        } catch (P4_Connection_ConnectException $e) {
            $this->assertTrue(true);
        }

        $p4->setUser($this->p4->getUser());
        $p4->connect();
        $this->assertTrue($p4->isConnected(), 'Expected connect status after connect');

        $p4->disconnect();
        $this->assertFalse($p4->isConnected(), 'Expected connect status after disconnect');
    }

    /**
     * Test run.
     */
    public function testRun()
    {
        $p4 = new P4_Connection_CommandLine(
            $this->p4->getPort(),
            $this->p4->getUser()
        );
        $result = $p4->run('info');
        $this->assertTrue($result instanceof P4_Result, 'Expect a valid result');

        // test invalid an invalid parameter
        try {
            $result = $p4->run('-q', 'info');
            $this->fail('Unexpected success with invalid param');
        } catch (P4_Exception $e) {
            $message = "Usage error: Perforce client error:" . PHP_EOL . "\tp4 -h for usage."
                     . PHP_EOL . "\tInvalid option: -q." . PHP_EOL;
            $this->assertSame(
                $message,
                $e->getMessage(),
                'Expected error for invalid param.'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected exception for invalid param: '.  $e->getMessage());
        }

        // test invalid output
        $script = TEST_SCRIPTS_PATH . '/noOutput.';
        $script .= P4_Environment::isWindows() ? 'bat' : 'sh';
        $p4->setP4Path($script);
        try {
            $result = $p4->run('info');
            $this->fail('Unexpected success with invalid output');
        } catch (P4_Connection_CommandException $e) {
            $this->assertSame(
                "Command failed. Output did not deserialize into an array.",
                $e->getMessage(),
                'Expected error for invalid output.'
            );
        } catch (Exception $e) {
            print "Exception class: ". get_class($e) ."\n";
            $this->fail('Unexpected exception for invalid output: '.  $e->getMessage());
        }

        // cleanup
        $p4->setP4Path(null);
        $result = $p4->run('info');
        $this->assertTrue($result instanceof P4_Result, 'Expect a valid result');
    }

    /**
     * Test login.
     */
    public function testLoginAgain()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': no user, no password',
                'username'  => null,
                'password'  => '',
                'create'    => null,
                'exception' => true,
                'error'     => "/Username is empty\./",
            ),
            array(
                'label'     => __LINE__ .': non-existant user, no password',
                'username'  => 'foozlebarb',
                'password'  => '',
                'create'    => null,
                'exception' => true,
                'error'     => "/Command failed: User foozlebarb doesn't exist\./",
            ),
            array(
                'label'     => __LINE__ .': existant user, no password',
                'username'  => $this->utility->getP4Params('user'),
                'password'  => '',
                'create'    => null,
                'exception' => true,
                'error'     => "/Command failed: Password invalid\./",
            ),
            array(
                'label'     => __LINE__ .': existant user, wrong password',
                'username'  => $this->utility->getP4Params('user'),
                'password'  => 'notmypassword',
                'create'    => null,
                'exception' => true,
                'error'     => "/Command failed: Password invalid\./",
            ),
            array(
                'label'     => __LINE__ .': existant user, correct password',
                'username'  => $this->utility->getP4Params('user'),
                'password'  => $this->utility->getP4Params('password'),
                'create'    => null,
                'exception' => false,
                'error'     => null,
            ),
            array(
                'label'     => __LINE__ .': created user w/np, no password',
                'username'  => 'bob',
                'password'  => '',
                'create'    => array(
                    'User'      => 'bob',
                    'Email'     => 'testBob@testhost',
                    'FullName'  => 'Bob the Tester',
                    'Password'  => '',
                ),
                'exception' => false,
                'error'     => null,
            ),
            array(
                'label'     => __LINE__ .': created user w/np, wrong password',
                'username'  => 'bob2',
                'password'  => 'notmypassword',
                'create'    => array(
                    'User'      => 'bob2',
                    'Email'     => 'testBob@testhost',
                    'FullName'  => 'Bob the Tester',
                ),
                'exception' => true,
                'error'     => "/'login' not necessary, no password set for this user\./",
            ),
            // Attempt to execute php as part of the p4 command to simulate
            // a valid response, but no ticket, without using p4.
            array(
                'label'     => __LINE__ .': valid user/pass, no ticket',
                'username'  => $this->utility->getP4Params('user'),
                'password'  => $this->utility->getP4Params('password'),
                'create'    => null,
                'exception' => true,
                'error'     => "/Unable to capture login ticket\./",
                'fakep4'    => 1,
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            if (is_array($test['create'])) {
                $this->p4->run('user', array('-i', '-f'), $test['create']);
            }

            if (array_key_exists('fakep4', $test)) {
                // Should output serialized array and ignore rest of
                // p4 parameters.
                $script = TEST_SCRIPTS_PATH . '/serializedArray.';
                $script .= P4_Environment::isWindows() ? 'bat' : 'sh';
                $this->p4->setP4Path($script);
            }

            // set the credentials for login
            $this->p4->setUser($test['username']);
            $this->p4->setPassword($test['password']);
            try {
                $result = $this->p4->login();
                if ($test['exception']) {
                    $this->fail("$label - Unexpected success");
                }
            } catch (P4_Exception $e) {
                if (!$test['exception']) {
                    $this->fail("$label - Unexpected failure: ". $e->getMessage());
                } else {
                    $this->assertRegExp(
                        $test['error'],
                        $e->getMessage(),
                        "$label - Expected exception"
                    );
                    continue;
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (Exception $e) {
                $this->fail("$label - Unexpected exception: ". $e->getMessage());
            }

            // restore the internal connection
            $this->p4 = $this->utility->createP4Connection('P4_Connection_CommandLine');
            if ($test['create']) {
                $this->p4->run('user', array('-d', '-f', $test['create']['User']));
            }
        }
    }

    /**
     * Test escapeArg escaping for Windows and other platforms
     * No P4 connection required
     */
    public function testEscapeArg()
    {
        if (!P4_Environment::isWindows()) {
            $this->markTestSkipped();
        }

        $tests = array(
            array(
                'label' => __LINE__ . ' single backslash, middle of string',
                'value' => 'te\\st',
                'expect'   => '"te\\st"'
            ),
            array(
                'label' => __LINE__ . ' escaped double quote, middle of string',
                'value' => 'te\\"st',
                'expect'   => '"te\\\\\\"st"'
            ),
            array(
                'label' => __LINE__ . ' double quote, middle of string; single backslash, end of string',
                'value' => 'te"st\\',
                'expect'   => '"te\\"st\\\\"'
            ),
            array(
                'label' => __LINE__ . ' double backslash before double-quoted string',
                'value' => 'te\\"st"',
                'expect'   => '"te\\\\\"st\""'
            ),
            array(
                'label' => __LINE__ . ' double backslash, middle of string',
                'value' => 'te\\\\st',
                'expect'   => '"te\\\\st"'
            ),
            array(
                'label' => __LINE__ . ' double backslash',
                'value' => '\\\\',
                'expect'   => '"\\\\\\\\"'
            ),
            array(
                'label' => __LINE__ . ' double quote',
                'value' => '"',
                'expect'   => '"\""'
            ),
            array(
                'label' => __LINE__ . ' single backslash',
                'value' => '\\',
                'expect'   => '"\\\\"'
            )
        );

        foreach ($tests as $test) {
            $this->assertSame(
                $test['expect'],
                P4_Connection_CommandLine::escapeArg($test['value']),
                $test['label'] . ": unexpected escaping result for\n".$test['value']
            );
        }
    }

    /**
     * Test batchArgs() method.
     */
    public function testBatchArgs()
    {
        $connection = P4_Connection::getDefaultConnection();

        // for the command line client, the argMax should not be zero
        $argMax = $connection->getArgMax();
        $this->assertNotEquals(
            0,
            $argMax,
            "Expected argMax is not zero."
        );

        // create list of arguments that will exceed argMax and verify that batchArgs()
        // splits them into several batches;
        // use same value for all arguments for simplicity
        $argVal     = 'abcd123X';
        $argLength  = strlen($connection->escapeArg($argVal)) + 1;

        // create arguments list that will be split into 3 batches with single argument
        // in the last batch
        $batchArgs = (int) floor($argMax / $argLength);
        $arguments = array_fill(0, 2 * $batchArgs + 1, $argVal);

        // verify number of batches returned by batchArgs() and number of arguments
        // in each batch
        $batches = $connection->batchArgs($arguments);
        $this->assertSame(
            3,
            count($batches),
            "Expected number of batches."
        );

        $this->assertSame(
            $batchArgs,
            count($batches[0]),
            "Expected number of arguments in the first batch."
        );
        $this->assertSame(
            $batchArgs,
            count($batches[1]),
            "Expected number of arguments in the second batch."
        );
        $this->assertSame(
            1,
            count($batches[2]),
            "Expected number of arguments in the third batch."
        );
    }

    /**
     * Ensure a too-big argument causes a batch args exception.
     *
     * @expectedException   P4_Exception
     */
    public function testTooBigBatch()
    {
        $connection = P4_Connection::getDefaultConnection();

        // for the command line client, the argMax should not be zero
        $argMax = $connection->getArgMax();
        $arg    = str_repeat("a", $argMax + 1);

        $connection->batchArgs(array($arg));
    }

    /**
     * Ensure a too-big argument group causes a batch args exception.
     *
     * @expectedException   P4_Exception
     */
    public function testTooBigBatchGroup()
    {
        $connection = P4_Connection::getDefaultConnection();

        // for the command line client, the argMax should not be zero
        $argMax = $connection->getArgMax();
        $arg    = str_repeat("a", $argMax/2 + 1);

        $connection->batchArgs(array($arg, $arg), null, null, 2);
    }

    /**
     * Test batchArgs() method when using a group size
     */
    public function testBatchArgsTwosome()
    {
        $connection = P4_Connection::getDefaultConnection();

        // for the command line client, the argMax should not be zero
        $argMax = $connection->getArgMax();

        // imagine we're clearing attributes (ie. -n/attr-name pairs)
        // with artificially long attr-names
        $name   = str_repeat("a", $argMax/3 + 1);
        $args   = array();
        $args[] = "-n";
        $args[] = $name;
        $args[] = "-n";
        $args[] = $name;
        $args[] = "-n";
        $args[] = $name;

        // should have two batches, five entries in first batch, one in the second.
        // this is an example of the pairs not being grouped together.
        $batches = $connection->batchArgs($args, null, null, 1);
        $this->assertSame(2, count($batches));
        $this->assertSame(5, count($batches[0]));
        $this->assertSame(1, count($batches[1]));

        // should have two batches, two-pairs in the first batch, one pair in the second.
        $batches = $connection->batchArgs($args, null, null, 2);
        $this->assertSame(2, count($batches));
        $this->assertSame(4, count($batches[0]));
        $this->assertSame(2, count($batches[1]));
    }

    /**
     * Test batchArgs() method when using a group size
     */
    public function testBatchArgsFoursome()
    {
        $connection = P4_Connection::getDefaultConnection();

        // for the command line client, the argMax should not be zero
        $argMax = $connection->getArgMax();

        // imagine we're setting attributes (ie. -n/attr-name, -v/attr-value set)
        $name   = "some-attribute-name";
        $value  = str_repeat("a", $argMax/3 + 1);
        $args   = array();
        for ($i = 0; $i < 3; $i++) {
            $args[] = "-n";
            $args[] = $name;
            $args[] = "-v";
            $args[] = $value;
        }

        // should have two batches, two-foursomes in the first batch, one in the second.
        $batches = $connection->batchArgs($args, null, null, 4);
        $this->assertSame(2, count($batches));
        $this->assertSame(8, count($batches[0]));
        $this->assertSame(4, count($batches[1]));
    }

    /**
     * Test track output - ensure it doesn't cause an exception.
     */
    public function testTrackOutputHandling()
    {
        $port = $this->p4->getPort();
        $this->p4->setPort(str_replace("-L /dev/null", "-vtrack=1", $port));
        $result = $this->p4->run('info');
        $this->assertFalse($result->hasErrors());
    }
}
