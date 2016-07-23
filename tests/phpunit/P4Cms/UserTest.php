<?php
/**
 * Test the user model
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_UserTest extends TestCase
{
    /**
     * The username to use for 'known' users.
     *
     * @var string
     */
    public $username = 'bob';

    /**
     * The email address to use for 'known' users.
     *
     * @var string
     */
    public $email = 'bob@host.test';

    /**
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();

        // create parent group
        $group = new P4_Group($this->p4);
        $group->setId('test')
              ->setUsers(array('tester'))
              ->save();

        // set storage adapter
        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($this->p4)
                ->setBasePath("//depot/records")
                ->setProperty(P4Cms_Acl_Role::PARENT_GROUP, $group->getId());
        P4Cms_Record::setDefaultAdapter($adapter);
    }

    /**
     * Test fetching users.
     */
    public function testFetch()
    {
        try {
            $user = P4Cms_User::fetch('bob');
            $this->fail('Unexpected success fetching non-existant "bob"');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4Cms_Model_NotFoundException $e) {
            $this->assertSame(
                "Cannot fetch user. User 'bob' does not exist.",
                $e->getMessage(),
                'Unexpected error message fetching non-existant "bob"'
            );
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception fetching non-existant "bob" ('
                . get_class($e) .') '. $e->getMessage()
            );
        }

        // add a user and test again.
        $user = $this->_createUser();

        try {
            $fetched = P4Cms_User::fetch('bob');
            $this->assertSame(
                $user->getEmail(),
                $fetched->getEmail(),
                'Expected email address'
            );
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception fetching existant "bob" ('
                . get_class($e) .') '. $e->getMessage()
            );
        }
    }

    /**
     * Test fetchAll behaviour.
     */
    public function testFetchAll()
    {
        $users = P4Cms_User::fetchAll();
        $this->assertEquals(1, count($users), 'Expect 1 user initially');
        $this->assertSame($this->utility->getP4Params('user'), $users[0]->getId(), 'Expected userid of initial user');

        // add a user and test again.
        $username = 'bob';
        $user = $this->_createUser($username);

        $users = P4Cms_User::fetchAll();
        $this->assertEquals(2, count($users), 'Expect 2 users');
        $this->assertSame($username, $users[0]->getId(), 'Expected userid of user #1');
        $this->assertSame($this->utility->getP4Params('user'), $users[1]->getId(), 'Expected userid of user #2');

        // test fetch with filters.
        $users = P4Cms_User::fetchAll(array('name' => 'bob'));
        $this->assertEquals(1, count($users), 'Expect 1 user');
        $users = P4Cms_User::fetchAll(array('maximum' => 1));
        $this->assertEquals(1, count($users), 'Expect 1 user');
    }

    /**
     * Test test fetching users by role(s).
     */
    public function testFetchByRole()
    {
        // crete several users having different roles
        $users = array('joe', 'bob', 'abc', 'xyz', 'doer', 'foo', 'bar');
        foreach ($users as $username) {
            $this->_createUser($username);
        }

        $role1 = P4Cms_Acl_Role::create(
            array(
                'id'    => 'role1',
                'users' => array('joe', 'xyz', 'foo')
            )
        )->save();
        $role2 = P4Cms_Acl_Role::create(
            array(
                'id'    => 'role2',
                'users' => array('joe', 'bob', 'doer', 'bar')
            )
        )->save();
        $role3 = P4Cms_Acl_Role::create(
            array(
                'id'    => 'role3',
                'users' => array('xyz', 'bar')
            )
        )->save();

        // fetch by non-existing role
        $users = P4Cms_User::fetchByRole('role');
        $this->assertTrue(
            $users instanceof P4Cms_Model_Iterator,
            "Expected returning data type when fetching by role."
        );
        $this->assertSame(
            0,
            $users->count(),
            "Expected no users when fetching by non-existing role."
        );

        $users = P4Cms_User::fetchByRole(array('a', 'b', 'c'));
        $this->assertTrue(
            $users instanceof P4Cms_Model_Iterator,
            "Expected returning data type when fetching by role."
        );
        $this->assertSame(
            0,
            $users->count(),
            "Expected no users when fetching by non-existing roles."
        );

        // fetch users by role1
        $users    = P4Cms_User::fetchByRole('role1');
        $expected = array('joe', 'xyz', 'foo');
        $this->assertTrue(
            $users instanceof P4Cms_Model_Iterator,
            "Expected returning data type when fetching by role."
        );
        $this->assertSame(
            $users->count(),
            count($expected),
            "Expected number of expected users when fetch by role1."
        );
        $this->assertSame(
            array(),
            array_diff($expected, $users->invoke('getId')),
            "Expected users fetched by role1."
        );

        // fetch users by role1 give by objct
        $users    = P4Cms_User::fetchByRole($role1);
        $expected = array('joe', 'xyz', 'foo');
        $this->assertTrue(
            $users instanceof P4Cms_Model_Iterator,
            "Expected returning data type when fetching by role."
        );
        $this->assertSame(
            $users->count(),
            count($expected),
            "Expected number of expected users when fetch by role1."
        );
        $this->assertSame(
            array(),
            array_diff($expected, $users->invoke('getId')),
            "Expected users fetched by role1."
        );

        // fetch users by role1 & role3
        $users    = P4Cms_User::fetchByRole(array('role1', 'role3'));
        $expected = array('joe', 'xyz', 'foo', 'bar');
        $this->assertTrue(
            $users instanceof P4Cms_Model_Iterator,
            "Expected returning data type when fetching by role."
        );
        $this->assertSame(
            $users->count(),
            count($expected),
            "Expected number of expected users when fetch by role1 & role3."
        );
        $this->assertSame(
            array(),
            array_diff($expected, $users->invoke('getId')),
            "Expected users fetched by role1 & role3."
        );

        // test argument types
        try {
            $users = P4Cms_User::fetchByRole(new stdClass);
            $this->fail("Unexpected possibility to fetch users by wrong parameter.");
        } catch (InvalidArgumentException $e) {
            // expected exception
        }
    }

    /**
     * Test fetchActive behaviour along with isAnonymous().
     */
    public function testFetchActive()
    {
        P4Cms_User::clearActive();

        // should have no active user.
        $this->assertFalse(P4Cms_User::hasActive(), "Expected no active user");

        // should throw exception initially.
        try {
            P4Cms_User::fetchActive();
            $this->fail("Expected exception fetching active user");
        } catch (Exception $e) {
            $this->assertTrue(true);
        }

        // add a user and make active.
        $user = new P4Cms_User;
        $user->setId('bob');
        P4Cms_User::setActive($user);

        // should be able to fetch active.
        $this->assertTrue(P4Cms_User::hasActive(), "Expected active user");
        $this->assertSame(
            $user,
            P4Cms_User::fetchActive(),
            "Expected same user instance"
        );

        P4Cms_User::clearActive();
        $this->assertFalse(P4Cms_User::hasActive(), "Expected no active user");
    }

    /**
     * Test behaviour of getRoles.
     */
    public function testGetRoles()
    {
        // test the roles for an anonymous user
        $user  = new P4Cms_User;
        $roles = $user->getRoles();
        $this->assertEquals(1, count($roles), 'Expected 1 role for anonymous user');
        $this->assertSame(
            'anonymous',
            $roles[0]->getId(),
            'Expected role for anonymous user'
        );

        // test a known user
        $user = $this->_createUser('bob');

        // assign some role to the user
        $role = new P4Cms_Acl_Role;
        $role->setId('testrole')
             ->addUser($user)
             ->save();

        $roles = P4Cms_User::fetch('bob')->getRoles();
        $this->assertEquals(1, count($roles), 'Expected 1 role for known user');
        $this->assertSame(
            'testrole',
            $roles[0]->getId(),
            'Expected role for registered user'
        );
    }

    /**
     * Test isAllowed behaviour.
     */
    public function testIsAllowed()
    {
        $acl = $this->_createAcl();

        // test an anonymous user
        $user = new P4Cms_User;
        $this->assertFalse(
            $user->isAllowed('manage-toolbar', 'view', $acl),
            'Expected failure for anonymous user'
        );

        // test a member user
        $user = $this->_createUser('joe');

        // create member role
        $role = new P4Cms_Acl_Role;
        $role->setId('member')
             ->addUser($user)
             ->save();

        $user = P4Cms_User::fetch('joe');
        $this->assertTrue(
            $user->isAllowed('manage-toolbar', 'view', $acl),
            'Expected success for member user'
        );
    }

    /**
     * Test the user save method.
     */
    public function testSave()
    {
        // new user should not exist yet.
        $this->assertFalse(P4Cms_User::exists('new-user'));

        // create new user.
        $user = new P4Cms_User;
        $user->setId('new-user')
             ->setEmail('new-user@domain.com')
             ->setFullName('New User')
             ->save();

        // user should now exists.
        $this->assertTrue(P4Cms_User::exists('new-user'));

        // verify save worked as expected.
        // try once on in-memory object and again via fetch.
        for ($i = 0; $i < 2; $i++) {
            if ($i) {
                $user = P4Cms_User::fetch('new-user');
            }

            $this->assertSame(
                'new-user',
                $user->getId(),
                'Expected id'
            );
            $this->assertSame(
                'new-user@domain.com',
                $user->getEmail(),
                'Expected email'
            );
            $this->assertSame(
                'New User',
                $user->getFullName(),
                'Expected full name'
            );
        }

        // test updating an existing user record.
        $user = P4Cms_User::fetch('new-user');
        $user->setFullName('New Name')->save();
        $user = P4Cms_User::fetch('new-user');
        $this->assertSame(
            'New Name',
            $user->getFullName(),
            'Expected full name'
        );

        // test updating new-user's password connected as new-user
        $p4 = P4_Connection::factory(
            $this->p4->getPort(),
            'new-user',
            $this->p4->getClient()
        );
        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($p4)
                ->setBasePath(P4Cms_Record::getDefaultAdapter()->getBasePath());
        $user = P4Cms_User::fetch('new-user', null, $adapter);
        $user->setPassword('passwd')->save();
        $this->assertTrue(
            $user->isPassword('passwd'),
            'Expected new password'
        );
    }

    /**
     * Test count method.
     */
    public function testCount()
    {
        // expect one user.
        $this->assertEquals(1, P4Cms_User::count());

        // create a user.
        P4Cms_User::create(
            array(
                'id'        => 'bob',
                'email'     => 'bob@bob-domain.com',
                'fullName'  => 'Bob Bobson'
            )
        )->save();

        // expect two users.
        $this->assertEquals(2, P4Cms_User::count());
    }

    /**
     * Test the delete method.
     */
    public function testDelete()
    {
        $user = P4Cms_User::create(
            array(
                'id'        => 'bob',
                'email'     => 'bob@bob-domain.com',
                'fullName'  => 'Bob Bobson'
            )
        )->save();

        // check user exists.
        $this->assertTrue(P4Cms_User::exists('bob'));

        $user->delete();

        // ensure user is gone.
        $this->assertFalse(P4Cms_User::exists('bob'));
    }

    /**
     * Test login/authentication.
     */
    public function testAuthenticate()
    {
        $user = new P4Cms_User;
        $user->setId('joe')
             ->setPassword('asdf1234')
             ->setEmail('joe@mail.com')
             ->setFullName('Joe Joey')
             ->save();

        // create member role and assign it to the p4 user
        $role = new P4Cms_Acl_Role;
        $role->setId(P4Cms_Acl_Role::ROLE_MEMBER)
             ->addUser(P4Cms_User::fetch($this->p4->getUser()))
             ->save();

        $user   = new P4Cms_User;
        $result = $user->authenticate();
        $this->assertSame(
            $result->getCode(),
            Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS,
            'Expect login failure.'
        );

        $user->setId($this->p4->getUser())
             ->setPassword($this->p4->getPassword());
        $result = $user->authenticate();
        $this->assertSame(
            $result->getCode(),
            Zend_Auth_Result::SUCCESS,
            'Expect authenticate success.'
        );

        $user->setPassword('alskdfj23523');
        $result = $user->authenticate();
        $this->assertSame(
            $result->getCode(),
            Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,
            'Expect password failure.'
        );

        $user->setId('laksdjflkasdfj');
        $result = $user->authenticate();
        $this->assertSame(
            $result->getCode(),
            Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND,
            'Expect user failure.'
        );

        $user->setId('joe')
             ->setPassword('asdf1234');

        $result = $user->authenticate();
        $this->assertSame(
            $result->getCode(),
            Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS,
            'Expect login failure due to missing member role.'
        );

        // Assign member role to the user
        $role = P4Cms_Acl_Role::fetch(P4Cms_Acl_Role::ROLE_MEMBER);
        $role->addUser(P4Cms_User::fetch('joe'))
             ->save();

        $result = $user->authenticate();
        $this->assertSame(
            $result->getCode(),
            Zend_Auth_Result::SUCCESS,
            'Expect authenticate success.'
        );
    }

    /**
     * Test of is(Member|Administrator|Anonymous) methods
     */
    public function testIsMethods()
    {
        // create member and administrator roles
        $testUser = $this->_createUser();

        $role = new P4Cms_Acl_Role;
        $role->setId(P4Cms_Acl_Role::ROLE_MEMBER)
             ->addUser($testUser)
             ->save();

        $role = new P4Cms_Acl_Role;
        $role->setId(P4Cms_Acl_Role::ROLE_ADMINISTRATOR)
             ->addUser($testUser)
             ->save();

        // create user - by default no role is assigned at the creation point
        $user = new P4Cms_User;
        $user->setId('joe')
             ->setEmail('joe@mail.com')
             ->setFullName('joe joesson')
             ->save();

        $this->assertFalse(
            P4Cms_User::fetch('joe')->isMember(),
            "Expected user is not member by default."
        );
        $this->assertFalse(
            P4Cms_User::fetch('joe')->isAdministrator(),
            "Expected user is not administrator by default."
        );
        $this->assertFalse(
            P4Cms_User::fetch('joe')->isAnonymous(),
            "Expected user is not anonymous."
        );

        // assign the member role to the user
        $role = P4Cms_Acl_Role::fetch(P4Cms_Acl_Role::ROLE_MEMBER);
        $role->addUser($user)
             ->save();

        $this->assertTrue(
            P4Cms_User::fetch('joe')->isMember(),
            "Expected user has a member role."
        );
        $this->assertFalse(
            P4Cms_User::fetch('joe')->isAdministrator(),
            "Expected user has not administrator role."
        );
        $this->assertFalse(
            P4Cms_User::fetch('joe')->isAnonymous(),
            "Expected user is not anonymous."
        );

        // assign the administrator role to the user
        $role = P4Cms_Acl_Role::fetch(P4Cms_Acl_Role::ROLE_ADMINISTRATOR);
        $role->addUser($user)
             ->save();

        $this->assertTrue(
            P4Cms_User::fetch('joe')->isMember(),
            "Expected user has a member role."
        );
        $this->assertTrue(
            P4Cms_User::fetch('joe')->isAdministrator(),
            "Expected user has administrator role."
        );
        $this->assertFalse(
            P4Cms_User::fetch('joe')->isAnonymous(),
            "Expected user is not anonymous."
        );

        // remove member role from user roles
        $users      = array();
        $roleMember = P4Cms_Acl_Role::fetch(P4Cms_Acl_Role::ROLE_MEMBER);
        foreach ($roleMember->getUsers() as $user) {
            if ($user !== 'joe') {
                $users[] = $user;
            }
        }
        $roleMember->setUsers($users)
                   ->save();

        $this->assertFalse(
            P4Cms_User::fetch('joe')->isMember(),
            "Expected user has not a member role."
        );
        $this->assertTrue(
            P4Cms_User::fetch('joe')->isAdministrator(),
            "Expected user has administrator role."
        );
        $this->assertFalse(
            P4Cms_User::fetch('joe')->isAnonymous(),
            "Expected user is not anonymous."
        );
    }

    /**
     * Test combining user roles into one role.
     */
    public function testGetAggregateRole()
    {
        P4Cms_Acl_Role::create(
            array(
                'id'    => 'member',
                'users' => array($this->p4->getUser())
            )
        )->save();
        P4Cms_Acl_Role::create(
            array(
                'id'    => 'administrator',
                'users' => array($this->p4->getUser())
            )
        )->save();

        $acl  = $this->_createAcl();
        $user = $this->_createUser('joe', 'joe@domain.com');

        // test w. no roles.
        try {
            $user->getAggregateRole($acl);
            $this->fail("Unexpected success getting aggregate role for user with no roles.");
        } catch (P4Cms_User_Exception $e) {
            $this->assertTrue(true);
        }

        // test w. one role.
        P4Cms_Acl_Role::setUserRoles($user, array('member'));
        $this->assertSame(
            'member',
            $user->getAggregateRole($acl)
        );

        // test w. multiple roles.
        P4Cms_Acl_Role::setUserRoles($user, array('administrator', 'member'));
        $this->assertSame(
            'joe-administrator-member',
            $user->getAggregateRole($acl)
        );

        // test again to verify id is unique.
        $this->assertSame(
            'joe-administrator-member-1',
            $user->getAggregateRole($acl)
        );
    }

    /**
     * Test getAllowedPrivileges() method.
     */
    public function testGetAllowedPrivileges()
    {
        $joe = $this->_createUser('joe', 'joe@domain.com');
        $bob = $this->_createUser('bob', 'bob@domain.com');

        P4Cms_Acl_Role::create(
            array(
                'id'    => 'role1',
                'users' => array('joe')
            )
        )->save();
        P4Cms_Acl_Role::create(
            array(
                'id'    => 'role2',
                'users' => array('bob')
            )
        )->save();
        P4Cms_Acl_Role::create(
            array(
                'id'    => 'role3',
                'users' => array('joe', 'bob')
            )
        )->save();

        $acl = new P4Cms_Acl;
        $acl->add(new P4Cms_Acl_Resource('resource1', array('A', 'B', 'C', 'D')));
        $acl->add(new P4Cms_Acl_Resource('resource2', array('K', 'L', 'M')));
        $acl->add(new P4Cms_Acl_Resource('resource3', array('X', 'Y', 'Z', 'A', 'M', 'K')));
        $acl->addRole('role1');
        $acl->addRole('role2');
        $acl->addRole('role3');
        $acl->allow('role1', 'resource1', array('A', 'B'));
        $acl->allow('role1', 'resource2', array('K', 'L'));
        $acl->allow('role2', 'resource3', array('Z', 'A', 'K'));
        $acl->allow('role3', 'resource3', array('X', 'Y', 'M'));

        // joe is is allowed for (resource/privileges): resource1/A,B, resource2/K,L, resource3/X,Y,M
        $privileges = $joe->getAllowedPrivileges('resource1', $acl);
        $this->assertSame(
            2,
            count($privileges),
            "Expected number of allowed privileges for joe/resource1."
        );
        $this->assertSame(
            array(),
            array_diff($privileges, array('A', 'B')),
            "Expected list of allowed privileges for joe/resource1."
        );

        $privileges = $joe->getAllowedPrivileges('resource2', $acl);
        $this->assertSame(
            2,
            count($privileges),
            "Expected number of allowed privileges for joe/resource2."
        );
        $this->assertSame(
            array(),
            array_diff($privileges, array('K', 'L')),
            "Expected list of allowed privileges for joe/resource2."
        );

        $privileges = $joe->getAllowedPrivileges('resource3', $acl);
        $this->assertSame(
            3,
            count($privileges),
            "Expected number of allowed privileges for joe/resource3."
        );
        $this->assertSame(
            array(),
            array_diff($privileges, array('X', 'Y', 'M')),
            "Expected list of allowed privileges for joe/resource3."
        );

        // bob is is allowed for (resource/privileges): resource1/- resource2/- resource3/A,K,M,X,Y,Z
        $privileges = $bob->getAllowedPrivileges('resource1', $acl);
        $this->assertSame(
            0,
            count($privileges),
            "Expected number of allowed privileges for bob/resource1."
        );

        $privileges = $bob->getAllowedPrivileges('resource2', $acl);
        $this->assertSame(
            0,
            count($privileges),
            "Expected number of allowed privileges for bob/resource2."
        );

        $privileges = $bob->getAllowedPrivileges('resource3', $acl);
        $this->assertSame(
            6,
            count($privileges),
            "Expected number of allowed privileges for bob/resource3."
        );
        $this->assertSame(
            array(),
            array_diff($privileges, array('A', 'K', 'M', 'X', 'Y', 'Z')),
            "Expected list of allowed privileges for bob/resource3."
        );

        // try composed resource
        $privileges = $joe->getAllowedPrivileges('resource1/id', $acl);
        $this->assertSame(
            2,
            count($privileges),
            "Expected number of allowed privileges for joe/resource1/id."
        );
        $this->assertSame(
            array(),
            array_diff($privileges, array('A', 'B')),
            "Expected list of allowed privileges for joe/resource1."
        );

        $privileges = $bob->getAllowedPrivileges('resource2/id', $acl);
        $this->assertSame(
            0,
            count($privileges),
            "Expected number of allowed privileges for bob/resource2/id."
        );
    }

    /**
     * Test password generation.
     */
    public function testGeneratePassword()
    {
        $this->assertSame(strlen(P4Cms_User::generatePassword(0)),   0);
        $this->assertSame(strlen(P4Cms_User::generatePassword(10)),  10);
        $this->assertSame(strlen(P4Cms_User::generatePassword(13)),  13);
        $this->assertSame(strlen(P4Cms_User::generatePassword(100)), 100);

        $this->assertTrue(
            preg_match(
                '/[^a-z]/',
                P4Cms_User::generatePassword(1000)
            ) == 0
        );
        $this->assertTrue(
            preg_match(
                '/[BCDFGHJKMNPQRSTVWXYZ]/',
                P4Cms_User::generatePassword(1000, 1)
            ) > 0
        );
        $this->assertTrue(
            preg_match(
                '/[AEUY]/',
                P4Cms_User::generatePassword(1000, 2)
            ) > 0
        );
        $this->assertTrue(
            preg_match(
                '/[2-9]/',
                P4Cms_User::generatePassword(1000, 3)
            ) > 0
        );
        $this->assertTrue(
            preg_match(
                "/[@\$%\^';]/",
                P4Cms_User::generatePassword(1000, 4)
            ) > 0
        );
        $this->assertTrue(
            preg_match(
                '/[01il]/',
                P4Cms_User::generatePassword(1000, 4)
            ) == 0
        );
    }

    /**
     * Test caching user roles.
     */
    public function testRolesCache()
    {
        // create several roles and users for testing
        $role = new P4Cms_Acl_Role;
        $role->addOwner('foo')
            ->setId('a')
            ->save()
            ->setId('b')
            ->save();

        $user = new P4Cms_User;
        $user->setFullName('Foo')
            ->setEmail('foo@test.com')
            ->setId('u1')
            ->save()
            ->setId('u2')
            ->save();

        // assign roles to users
        $u1 = P4Cms_User::fetch('u1');
        $u2 = P4Cms_User::fetch('u2');
        P4Cms_Acl_Role::setUserRoles($u1, array('a', 'b'));
        P4Cms_Acl_Role::setUserRoles($u2, array('b'));

        // verify that if user id changes, user will get fresh list of roles
        $user = new P4Cms_User;
        $this->assertSame(
            array(P4Cms_Acl_Role::ROLE_ANONYMOUS),
            $user->getRoles()->invoke('getId'),
            "Expected roles for anonymous user."
        );
        $this->assertSame(
            array('a', 'b'),
            $user->setId('u1')->getRoles()->invoke('getId'),
            "Expected roles for user u1"
        );
        $this->assertSame(
            array('b'),
            $user->setId('u2')->getRoles()->invoke('getId'),
            "Expected roles for user u2"
        );

        // verify that roles cache is updated when user is removed from the role
        $u21 = P4Cms_User::fetch('u2');
        $u22 = P4Cms_User::fetch('u2');
        $this->assertSame(
            array('b'),
            $u21->getRoles()->invoke('getId'),
            "Expected roles for instance #1 of user u2."
        );
        $this->assertSame(
            array('b'),
            $u22->getRoles()->invoke('getId'),
            "Expected roles for instance #2 of user u2."
        );

        // remove user u2 from role b
        P4Cms_Acl_Role::fetch('b')->removeUser($u2)->save();

        // verify that instances of user u2 don't have role b
        $this->assertSame(
            array(),
            $u21->getRoles()->invoke('getId'),
            "Expected no roles for instance #1 of user u2."
        );
        $this->assertSame(
            array(),
            $u22->getRoles()->invoke('getId'),
            "Expected no roles for instance #2 of user u2."
        );

        // verify that user gets fresh list of roles if new role is added
        $u11 = P4Cms_User::fetch('u1');
        $u12 = P4Cms_User::fetch('u1');
        $this->assertSame(
            array('a', 'b'),
            $u11->getRoles()->invoke('getId'),
            "Expected roles for instance #1 of user u1."
        );
        $this->assertSame(
            array('a', 'b'),
            $u12->getRoles()->invoke('getId'),
            "Expected roles for instance #2 of user u1."
        );

        P4Cms_Acl_Role::create()
            ->setUsers(array('u1', 'u2'))
            ->setId('x')
            ->save();

        $this->assertSame(
            array('a', 'b', 'x'),
            $u11->getRoles()->invoke('getId'),
            "Expected roles for instance #1 of user u1."
        );
        $this->assertSame(
            array('a', 'b', 'x'),
            $u12->getRoles()->invoke('getId'),
            "Expected roles for instance #2 of user u1."
        );

        // verify that user roles cache gets updated when role is removed
        P4Cms_Acl_Role::fetch('b')->delete();

        $this->assertSame(
            array('a', 'x'),
            $u11->getRoles()->invoke('getId'),
            "Expected roles for instance #1 of user u1."
        );
        $this->assertSame(
            array('a', 'x'),
            $u12->getRoles()->invoke('getId'),
            "Expected roles for instance #2 of user u1."
        );
    }

    /**
     * Create a known user for testing.
     *
     * @param  string  $username  Optional username to apply to the created user.
     * @param  string  $email     Optional email address to apply to the created user.
     *
     * @return  P4_User  An instantiated P4_User object.
     */
    protected function _createUser($username = null, $email = null)
    {
        if (!$username) {
            $username = $this->username;
        }
        if (!$email) {
            $email = $this->email;
        }

        $user = new P4Cms_User;
        $user->setId($username)
             ->setEmail($email)
             ->setFullName(ucfirst($username) .' tester')
             ->save();

        return $user;
    }

    /**
     * Create a acl instance with some test resources, privs and roles.
     *
     * @return P4Cms_Acl    the test acl instance.
     */
    protected function _createAcl()
    {
        $acl = new P4Cms_Acl;
        $acl->add(new P4Cms_Acl_Resource('manage-toolbar', array('view')));
        $acl->addRole(new P4Cms_Acl_Role(array('id' => 'administrator')));
        $acl->addRole(new P4Cms_Acl_Role(array('id' => 'member')));
        $acl->allow('member', 'manage-toolbar', 'view');

        return $acl;
    }
}
