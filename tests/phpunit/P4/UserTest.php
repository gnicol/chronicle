<?php
/**
 * Test methods for the P4 User class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_UserTest extends TestCase
{
    /**
     * Test fetch().
     */
    public function testFetch()
    {
        // ensure fetch fails for a non-existant user.
        try {
            P4_User::fetch('alskdfj2134');
            $this->fail("Fetch should fail for a non-existant user.");
        } catch (P4_Spec_NotFoundException $e) {
            $this->assertTrue(true);
        }

        // ensure fetch succeeds for a user that exists.
        try {
            P4_User::fetch($this->p4->getUser());
            $this->assertTrue(true);
        } catch (P4_Spec_NotFoundException $e) {
            $this->fail("Fetch should succeed for a user that exists.");
        }

        // ensure that fetch works for a user that is not the current user.
        $user = new P4_User;
        $user->setId('jdoe')
             ->setEmail('jdoe@host.com')
             ->setFullName('Jane Doe')
             ->save();
        try {
            $user = P4_User::fetch('jdoe');
            $this->assertTrue(true);
        } catch (P4_Spec_NotFoundException $e) {
            $this->fail("Fetch should succeed for a user that exists.");
        }
        $this->assertTrue($user->getId() == 'jdoe', "User id should be 'jdoe'.");
    }

    /**
     * Test that position specifiers can be used in a username.
     */
    public function testPositionSpecifiersInUserName()
    {
        $user = new P4_User;
        $user->setId('jdoe%%')
             ->setEmail('jdoe@host.host')
             ->setFullName('Jimmy Doe')
             ->save();
        $user = P4_User::fetch('jdoe%%');
        $this->assertTrue($user->getId() == 'jdoe%%', "User id should be 'jdoe%%'.");
    }

    /**
     * Test that Windows environmental variables are not interpolated.
     */
    public function testWindowsEscapeArgs()
    {
        $user = new P4_User;
        $user->setId('%PATH%')
             ->setEmail('patherson@host.host')
             ->setFullName('Patricia Atherson')
             ->save();
        $user = P4_User::fetch('%PATH%');
        $this->assertTrue($user->getId() == '%PATH%', "User id should be '%PATH%'.");
    }

    /**
     * Test fetchAll().
     */
    public function testFetchAll()
    {
        $users = P4_User::fetchAll();
        $this->assertTrue($users->count()           == 1);
        $this->assertTrue($users[0]->getId()        == 'tester');
        $this->assertTrue($users[0]->getFullName()  == 'Test User');

        // add a user and test again.
        $user = new P4_User;
        $user->setId('jdoe')
             ->setEmail('jdoe@host.com')
             ->setFullName('Jane Doe')
             ->save();

        $users = P4_User::fetchAll();
        $this->assertTrue($users->count() == 2);

        // test w. max results option.
        $users = P4_User::fetchAll(array(P4_User::FETCH_MAXIMUM => 1));
        $this->assertTrue($users->count() == 1);

        // test w. filters.
        $users = P4_User::fetchAll(array(P4_User::FETCH_BY_NAME => "laksdjf"));
        $this->assertTrue($users->count() == 0);
        $users = P4_User::fetchAll(array(P4_User::FETCH_BY_NAME => "jdo*"));
        $this->assertTrue($users->count() == 1);
        $users = P4_User::fetchAll(array(P4_User::FETCH_BY_NAME => "*"));
        $this->assertTrue($users->count() == 2);

        // test w. both options.
        $users = P4_User::fetchAll(
            array(
                P4_User::FETCH_BY_NAME => "*",
                P4_User::FETCH_MAXIMUM => 1
            )
        );
        $this->assertTrue($users->count() == 1);

        // add another user
        $user = new P4_User;
        $user->setId('joe')
             ->setEmail('joe@host.com')
             ->setFullName('Mr Joe')
             ->save();

        // fetch by name where list of users is specified
        $users = P4_User::fetchAll(array(P4_User::FETCH_BY_NAME => array('jdoe', 'joe')));
        $this->assertTrue($users->count() == 2);
        $users = P4_User::fetchAll(array(P4_User::FETCH_BY_NAME => array('jdoe', 'joe', 'no')));
        $this->assertTrue($users->count() == 2);
        $users = P4_User::fetchAll(array(P4_User::FETCH_BY_NAME => array('no')));
        $this->assertTrue($users->count() == 0);

        // test fetching with list of users and fetch max
        // (fetch max is applied to each user name pattern)
        $users = P4_User::fetchAll(
            array(
                P4_User::FETCH_BY_NAME => array('tester', 'joe', 'jdoe'),
                P4_User::FETCH_MAXIMUM => 1
            )
        );

        $this->assertTrue($users->count() == 1);

        // ensure users are sorted before cutting-off
        $this->assertSame(
            'jdoe',
            $users->current()->getId(),
            "Expected user returned by server when fetching by name and max is set."
        );

        $users = P4_User::fetchAll(
            array(
                P4_User::FETCH_BY_NAME => array('t*','j*'),
                P4_User::FETCH_MAXIMUM => 1
            )
        );

        $this->assertTrue($users->count() == 1);

        // ensure users are sorted before cutting-off
        $this->assertSame(
            'jdoe',
            $users->current()->getId(),
            "Expected user returned by server when fetching by name and max is set."
        );
    }

    /**
     * Test idExists().
     */
    public function testIdExists()
    {
        // ensure id-exists returns false for non-existant user
        $this->assertFalse(P4_User::exists("alsdjf"), "Given user id should not exist.");

        // create user and ensure it exists.
        $user = new P4_User;
        $user->setId("jdoe")
             ->setEmail('jdoe@host.com')
             ->setFullName('Jane Doe')
             ->save();

        $this->assertTrue(P4_User::exists("jdoe"), "Given user id should exist.");

        // test with invalid user id.
        $this->assertFalse(P4_User::exists("jdo*"), "Invalid user id should return false.");
    }

    /**
     * Test save().
     */
    public function testSave()
    {
        $user = new P4_User;
        $user->setId('jdoe');
        $user->setEmail('jdoe@host.com');
        $user->setFullName("Jane Doe");
        $user->save();

        // test reading out of the same instance.
        $this->assertSame("jdoe",     $user->getId());
        $this->assertSame("Jane Doe", $user->getFullName());

        // test reading out of fetched instance.
        $user = P4_User::fetch("jdoe");
        $this->assertSame("jdoe",     $user->getId());
        $this->assertSame("Jane Doe", $user->getFullName());

        // test updating existing user using all fields (except password).
        $user = P4_User::fetch("jdoe");
        $user->setEmail("user@host.com");
        $user->setFullName("John Doe");
        $user->setJobView("status=open bug");
        $reviews = array("//depot/path/...", "//depot/other/foo");
        $user->setReviews($reviews);
        $user->save();

        // ensure values properly updated.
        $user = P4_User::fetch("jdoe");
        $this->assertSame($user->getEmail(),    "user@host.com");
        $this->assertSame($user->getFullName(), "John Doe");
        $this->assertSame($user->getJobView(),  "status=open bug");
        $this->assertSame($user->getReviews(),  $reviews);

        // ensure save fails with no id.
        $user = new P4_User;
        try {
            $user->save();
            $this->fail("Should not be able to save user without an id.");
        } catch (P4_Spec_Exception $e) {
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail("Save with no id should not throw generic exception.");
        }

        // test save without super permissions.
        // (test save while connected as the user).
        $p4 = P4_Connection::factory($this->p4->getPort(), 'jdoe');
        $p4->connect();
        $user = new P4_User;
        $user->setConnection($p4);
        $this->assertSame(
            $user->getConnection(),
            $p4,
            "User object should have connection we set."
        );
        $this->assertFalse(
            $user->getConnection()->isSuperUser(),
            "User object should have connection without super user privileges."
        );
        $user->setId('jdoe');
        $user->setFullName("Jane Doe");
        $user->setEmail("jdoe@host.com");
        try {
            $user->save();
            $this->assertTrue(true);
        } catch (P4_Exception $e) {
            $this->fail("Should be able to save user.");
        }
    }

    /**
     * Test deleting a user without an id.
     */
    public function testDeleteUserWithoutId()
    {
        $user = new P4_User;
        try {
            $user->delete();
            $this->fail('Unexpected success deleting a user without an id.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4_Spec_Exception $e) {
            $this->assertEquals(
                'Cannot delete. No id has been set.',
                $e->getMessage(),
                'Expected exception message.'
            );
        } catch (Exception $e) {
            $this->fail(
                "$label: Unexpected Exception (" . get_class($e) . '): ' . $e->getMessage()
            );
        }
    }

    /**
     * Test delete().
     */
    public function testDelete()
    {
        // create a user we can delete.
        $user = new P4_User;
        $user->setId('test-user')
             ->setEmail('jdoe@host.com')
             ->setFullName('Jane Doe')
             ->save();

        $user = P4_User::fetch('test-user');
        try {
            $user->delete();
            $this->assertTrue(true);
        } catch (P4_Exception $e) {
            $this->fail("Should be able to delete user.");
        }

        // ensure user is gone.
        try {
            P4_User::fetch('test-user');
            $this->fail("User should not exist - fetch should fail.");
        } catch (P4_Spec_NotFoundException $e) {
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail("Fetch should fail with not-found exception.");
        }
    }

    /**
     * Test delete without super permission (delete while connected as the user).
     */
    public function testDeleteUnprivilegedSelf()
    {
        // test delete without super permissions.
        // (test delete while connected as the user).
        $user = new P4_User;
        $user->setId('test-user')
             ->setEmail('tester@host.com')
             ->setFullName('Test User')
             ->save();
        $p4 = P4_Connection::factory($this->p4->getPort(), 'test-user');
        $p4->connect();
        $user = new P4_User;
        $user->setConnection($p4);
        $this->assertSame(
            $user->getConnection(),
            $p4,
            "User object should have connection we set."
        );
        $this->assertFalse(
            $user->getConnection()->isSuperUser(),
            "User object should have connection without super user privileges."
        );
        $user->setId('test-user');
        try {
            $user->delete();
            $this->assertTrue(true);
        } catch (P4_Exception $e) {
            $this->fail("Should be able to delete user.");
        }

        // ensure user is gone.
        try {
            P4_User::fetch('test-user');
            $this->fail("User should not exist - fetch should fail.");
        } catch (P4_Spec_NotFoundException $e) {
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail("Fetch should fail with not-found exception.");
        }
    }

    /**
     * Test delete without super permission (delete while connected as other user).
     */
    public function testDeleteUnprivilegedOther()
    {
        // test delete without super permissions.
        // (test delete while connected as the user).
        $user = new P4_User;
        $user->setId('test-user')
             ->setEmail('tester@host.com')
             ->setFullName('Test User')
             ->save();
        $p4 = P4_Connection::factory($this->p4->getPort(), 'test-user');
        $p4->connect();
        $user = new P4_User;
        $user->setConnection($p4);
        $this->assertSame(
            $user->getConnection(),
            $p4,
            "User object should have connection we set."
        );
        $this->assertFalse(
            $user->getConnection()->isSuperUser(),
            "User object should have connection without super user privileges."
        );

        // update the other user
        $user->setId('test-user-2')
             ->setEmail('tester2@host.com')
             ->setFullName('Tester2')
             ->save();
        try {
            $user->delete();
            $this->assertTrue(true);
        } catch (P4_Exception $e) {
            $this->fail("Should be able to delete user.");
        }

        // ensure user is gone.
        try {
            P4_User::fetch('test-user-2');
            $this->fail("User should not exist - fetch should fail.");
        } catch (P4_Spec_NotFoundException $e) {
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail("Fetch should fail with not-found exception.");
        }
    }

    /**
     * Test user object mutators.
     */
    public function testMutators()
    {
        // ensure mutators reject invalid types.
        $tests = array(
            array(
                "method" => "setId",
                "value"  => null,
                "throws" => false,
            ),
            array(
                "method" => "setId",
                "value"  => "",
                "throws" => true,
            ),
            array(
                "method" => "setId",
                "value"  => "jdoe",
                "throws" => false,
            ),
            array(
                "method" => "setId",
                "value"  => "jdoe%%",
                "throws" => false,
            ),
            array(
                "method" => "setId",
                "value"  => "john doe",
                "throws" => true,
            ),
            array(
                "method" => "setEmail",
                "value"  => null,
                "throws" => false,
            ),
            array(
                "method" => "setEmail",
                "value"  => "joe@host.com",
                "throws" => false,
            ),
            array(
                "method" => "setEmail",
                "value"  => array(),
                "throws" => true,
            ),
            array(
                "method" => "setFullName",
                "value"  => null,
                "throws" => false,
            ),
            array(
                "method" => "setFullName",
                "value"  => "Jane Doe",
                "throws" => false,
            ),
            array(
                "method" => "setFullName",
                "value"  => array(),
                "throws" => true,
            ),
            array(
                "method" => "setJobView",
                "value"  => null,
                "throws" => false,
            ),
            array(
                "method" => "setJobView",
                "value"  => "status=open blah blah",
                "throws" => false,
            ),
            array(
                "method" => "setJobView",
                "value"  => array(),
                "throws" => true,
            ),
            array(
                "method" => "setReviews",
                "value"  => null,
                "throws" => true,
            ),
            array(
                "method" => "setReviews",
                "value"  => array("//depot/some/path", "//depot/some-other/path..."),
                "throws" => false,
            ),
            array(
                "method" => "setReviews",
                "value"  => "alksdfj",
                "throws" => true,
            ),
        );

        foreach ($tests as $test) {
            $user   = new P4_User;
            $method = $test['method'];
            $value  = $test['value'];
            $throws = $test['throws'];
            try {
                $user->$method($value);
                if ($throws) {
                    $this->fail("$method with value '$value' should throw exception.");
                } else {
                    $this->assertTrue(true);
                }
            } catch (InvalidArgumentException $e) {
                if (!$throws) {
                    $this->fail("$method with value '$value' should not throw exception.");
                } else {
                    $this->assertTrue(true);
                }
            }
        }

        // ensure update/access read-only and throw on set.
        $readOnlyFields = array("Update", "Access");
        foreach ($readOnlyFields as $field) {
            $user = new P4_User;
            try {
                $user->setValue($field, time());
                $this->fail("Should not be able to set read-only $field field.");
            } catch (P4_Spec_Exception $e) {
                $this->assertTrue(true);
            } catch (Exception $e) {
                $this->fail("Set on read-only field should not throw generic exception.");
            }
        }
    }

    /**
     * Test getGroups().
     */
    public function testGetGroups()
    {
        // create a group.
        $group = new P4_Group;
        $group->setId("test-group")
              ->addUser("tester")
              ->save();

        // ensure user is in group.
        $user   = P4_User::fetch($this->p4->getUser());

        $this->assertSame(
            1,
            count($user->getGroups()),
            'Expected one result'
        );

        $this->assertSame(
            'test-group',
            $user->getGroups()->offsetGet(0)->getId(),
            'Expected matching group entry'
        );
    }

    /**
     * Test getGroups() with bad user id.
     */
    public function testGetGroupsWithBadId()
    {
        $user = new P4_User;
        try {
            $user->getGroups();
            $this->fail('Unexpected success fetting clients for user without id.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4_Spec_Exception $e) {
            $this->assertEquals(
                'Cannot get groups. No user id has been set.',
                $e->getMessage(),
                'Expected exception message.'
            );
        } catch (Exception $e) {
            $this->fail(
                "$label: Unexpected Exception (" . get_class($e) . '): ' . $e->getMessage()
            );
        }
    }

    /**
     * Test getUpdateDateTime().
     */
    public function testGetUpdateDateTime()
    {
        $user = new P4_User;
        $dateTime = $user->getUpdateDatetime();
        $this->assertEquals(null, $dateTime, 'Expected null datetime for unsaved user.');

        $user->setId('test-user')
             ->setEmail('tester@host.com')
             ->setFullName('Test User')
             ->save();
        $dateTime = $user->getUpdateDatetime();
        $this->assertRegExp(
            '/^\d{4}\/\d\d\/\d\d \d\d:\d\d:\d\d$/',
            $dateTime,
            'Expected datetime for just-saved user.'
        );
    }

    /**
     * Test getAccessDateTime().
     */
    public function testGetAccessDateTime()
    {
        $user = new P4_User;
        $dateTime = $user->getAccessDatetime();
        $this->assertEquals(null, $dateTime, 'Expected null datetime for unsaved user.');

        $user->setId('test-user')
             ->setEmail('tester@host.com')
             ->setFullName('Test User')
             ->save();
        $dateTime = $user->getAccessDatetime();
        $this->assertRegExp(
            '/^\d{4}\/\d\d\/\d\d \d\d:\d\d:\d\d$/',
            $dateTime,
            'Expected datetime for just-saved user.'
        );
    }

    /**
     * Test addToGroup().
     */
    public function testAddToGroup()
    {
        // create a group.
        $group = new P4_Group;
        $group->setId("test-group")
              ->addUser("tester")
              ->save();

        $user = new P4_User;
        $user->setId('jdoe')
             ->setEmail('jdoe@host.com')
             ->setFullName("John Doe")
             ->save();
        $user->addToGroup('test-group');

        $this->assertSame('test-group', $user->getGroups()->offsetGet(0)->getId());
    }

    /**
     * Test isPassword().
     */
    public function testIsPassword()
    {
        $user = P4_User::fetch($this->p4->getUser());
        $this->assertTrue($user->isPassword($this->p4->getPassword()));
        $this->assertFalse($user->isPassword('klasdjfkls'));
    }

    /**
     * Test setPassword().
     */
    public function testSetPassword()
    {
        $newPassword = "a-new-test-password";
        $user = P4_User::fetch($this->p4->getUser());
        $user->setPassword($newPassword, $this->p4->getPassword())->save();
        $this->assertTrue($user->isPassword($newPassword));
    }

    /**
     * Set the behaviour of the password field.
     */
    public function testPasswordField()
    {
        $user = new P4_User;
        $user->getValue('Password');
        $this->assertSame(null, $user->getValue('Password'));

        // create user.
        $user->setId('bob')
             ->setEmail('bob@bob')
             ->setFullName('BOB')
             ->setPassword('bob-pass')
             ->save();

        // can't get password back out after save.
        $this->assertSame(null, $user->getValue('Password'));

        // ensure password was set.
        $this->assertTrue($user->isPassword('bob-pass'), 'Expected bob-pass password');

        // ensure can't read password from fetched user.
        $this->assertSame(
            null,
            P4_User::fetch('bob')->getPassword()
        );

        // ensure we can read in-memory password.
        $user = new P4_User;
        $user->setPassword('test');
        $this->assertSame('test', $user->getPassword());

        // ensure password unaffected after same of other field.
        $user = P4_User::fetch('bob');
        $user->setFullName('Bob Bobson')->save();
        $this->assertTrue($user->isPassword('bob-pass'));
    }

    /**
     * Test getClients().
     */
    public function testGetClients()
    {
        // Add junk client to verify we are filtering
        $client = new P4_Client;
        $client->setId('test2-client')
               ->setOwner('user1')
               ->setRoot(DATA_PATH . '/clients/test2-client')
               ->save();

        $user = P4_User::fetch('tester');

        $this->assertSame(
            1,
            count($user->getClients()),
            'Expected one result'
        );

        $this->assertSame(
            'test-client',
            $user->getClients()->offsetGet(0)->getId(),
            'Expected matching client entry'
        );
    }

    /**
     * Test getClients() with bad id
     */
    public function testGetClientsWithBadId()
    {
        $user = new P4_User;
        try {
            $user->getClients();
            $this->fail('Unexpected success fetting clients for user without id.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4_Spec_Exception $e) {
            $this->assertEquals(
                'Cannot get clients. No user id has been set.',
                $e->getMessage(),
                'Expected exception message.'
            );
        } catch (Exception $e) {
            $this->fail(
                "$label: Unexpected Exception (" . get_class($e) . '): ' . $e->getMessage()
            );
        }
    }

    /**
     * Exercise auto user creation detection
     */
    public function testIsAutoUserCreationEnabled()
    {
        // should be enabled by default.
        $this->assertTrue(P4_User::isAutoUserCreationEnabled(), "Expected auto user creation on");

        // turn off auto-user creation.
        $protections = new P4_Protections;
        $protections->setProtections(array("super user " . $this->p4->getUser() . " * //..."))
                    ->save();

        // should be off now.
        $this->assertFalse(P4_User::isAutoUserCreationEnabled(), "Expected auto user creation off");
    }
}
