<?php
/**
 * Test filter functionality of P4Cms model iterator.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Acl_RoleTest extends TestCase
{
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
     *  Test fetch all roles
     */
    public function testFetchAll()
    {
        $user = new P4Cms_User(
            array(
                'id'        => 'roletester',
                'fullName'  => 'Test Roles',
                'email'     => 'rt@test.com'
            )
        );
        $user->save();

        // add few roles first
        $newRoles = array('Editor', 'Admin', 'Moderator', 'Publisher', 'Writer');
        foreach ($newRoles as $roleId) {
            $role = new P4Cms_Acl_Role;
            $role->setId($roleId)
                 ->addUser($user)
                 ->save();
        }

        $roles      = P4Cms_Acl_Role::fetchAll();
        $rolesIds   = $roles->invoke('getId');

        $this->assertTrue(
            $roles instanceof P4Cms_Model_Iterator,
            "Expected instance of P4Cms_Model_Iterator."
        );

        // verify that getRoleId() returns same value as getId()
        foreach ($roles as $role) {
            $this->assertSame(
                $role->getId(),
                $role->getRoleId(),
                "Expected getRoleId() is same as getId()."
            );
        }

        // verify that all new roles are present in result
        foreach ($newRoles as $roleId) {
            $this->assertTrue(
                in_array($roleId, $rolesIds),
                "Expected added role $roleId is present"
                . " in fetchAll() result."
            );
        }

        // verify that anonymous virtual role is present in result
        $this->assertTrue(
            in_array(P4Cms_Acl_Role::ROLE_ANONYMOUS, $rolesIds),
            "Expected anonymous virtual role is present"
            . " in fetchAll() result."
        );

        // do the same with FETCH_HIDE_VIRTUAL option
        $roles      = P4Cms_Acl_Role::fetchAll(
            array(P4Cms_Acl_Role::FETCH_HIDE_VIRTUAL => true)
        );
        $rolesIds   = $roles->invoke('getId');

        // verify that all new roles are present in result
        foreach ($newRoles as $roleId) {
            $this->assertTrue(
                in_array($roleId, $rolesIds),
                "Expected added role $roleId is present"
                . " in fetchAll() result."
            );
        }

        // verify that anonymous virtual role is not present
        $this->assertFalse(
            in_array(P4Cms_Acl_Role::ROLE_ANONYMOUS, $rolesIds),
            "Expected anonymous virtual role is not present"
            . " in fetchAll() result when FETCH_HIDE_VIRTUAL option was used."
        );
    }

    /**
     * Test that roles can be added to the system
     */
    public function testSave()
    {
        // add few users to Perforce
        $user1 = P4Cms_User::create(
            array(
                'id'        => 'bob',
                'email'     => 'bob@bob-domain.com',
                'fullName'  => 'Bob Bobson'
            )
        )->save();
        $user2 = P4Cms_User::create(
            array(
                'id'        => 'pat',
                'email'     => 'pat@bob-domain.com',
                'fullName'  => 'Pat Patty',
            )
        )->save();
        $user3 = P4Cms_User::create(
            array(
                'id'        => 'bill',
                'email'     => 'bill@bob-domain.com',
                'fullName'  => 'Bill Billy'
            )
        )->save();

        // test create role without assigned users
        $role = new P4Cms_Acl_Role;
        $role->setId('New_Role');

        try {
            $role->save();
            $this->fail('Expected exception - associated group is empty.');
        } catch (P4_Spec_Exception $e) {
            $this->assertTrue(true);
        }

        $role->addUser($user1)
             ->save();

        $this->assertTrue(
            P4Cms_Acl_Role::exists('New_Role'),
            'Expected existence of newly added role'
        );

        // fetch new role
        $newrole = P4Cms_Acl_Role::fetch('New_Role');
        $this->assertTrue(
            $newrole instanceof P4Cms_Acl_Role,
            "Expected class name of fetched role"
        );
        $this->assertSame(
            'New_Role',
            $newrole->getId(),
            "Expected id of fetched role"
        );

        // test create role with assigned users
        $role = new P4Cms_Acl_Role;
        $role->setId('anotherRole')
             ->setUsers(array($user1, $user2))
             ->save();

        $this->assertTrue(
            P4Cms_Acl_Role::exists('anotherRole'),
            'Expected existence of newly added role'
        );

        // fetch new role
        $newrole = P4Cms_Acl_Role::fetch('anotherRole');
        $this->assertTrue(
            $newrole instanceof P4Cms_Acl_Role,
            "Expected class name of fetched role"
        );
        $this->assertSame(
            'anotherRole',
            $newrole->getId(),
            "Expected id of fetched role"
        );

        $this->assertSame(
            2,
            count($newrole->getUsers()),
            "Expected number of users assigned to the role"
        );

        // cannot save new role with same id as any of virtual roles
        try {
            $role = new P4Cms_Acl_Role;
            $role->setId(P4Cms_Acl_Role::ROLE_ANONYMOUS)
                 ->save();
            $this->fail("Attempt to create new role with same id as protected role shouldn't be successful.");
        } catch (P4Cms_Acl_Exception $exception) {
            // OK, expected this exception type
        } catch (Exception $e) {
            $this->fail(
                "Attempt to create new role with same id as protected role "
                . "should trigger exception of P4Cms_Acl_Exception class."
            );
        }

        // test save role with no users but having an owner
        $role = new P4Cms_Acl_Role;
        $role->setId('empty_role')
             ->addOwner($user1);

        try {
            $role->save();
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail("Unexpected exception thrown when saving the role.");
        }

        // check that role has not been assigned to any user
        $this->assertSame(
            0,
            count($role->getUsers()),
            'Expected no users having role'
        );
    }

    /**
     * Test add assign role to the user
     */
    public function testaddUser()
    {
        // add few users to Perforce
        $user1 = P4Cms_User::create(
            array(
                'id'        => 'bob',
                'email'     => 'bob@bob-domain.com',
                'fullName'  => 'Bob Bobson',
                'config'    => array('foo'=>'bar')
            )
        )->save();

        // create new role and assign it to the user
        $role = new P4Cms_Acl_Role;
        $role->setId('New_Role')
             ->addUser($user1)
             ->save();

        $role = P4Cms_Acl_Role::fetch('New_Role');
        $this->assertTrue(
            $role->hasUser($user1),
            "Expected that user has New_Role"
        );

        $users = $role->getUsers();
        $this->assertSame(
            1,
            count($users),
            "Expected number of users having new role"
        );

        $this->assertSame(
            1,
            count(
                array_intersect(
                    array($user1->getId()),
                    $users
                )
            ),
            "Expected the only users is returned in getUsers()."
        );

        // cannot assign anonymous role
        $role = P4Cms_Acl_Role::fetch(P4Cms_Acl_Role::ROLE_ANONYMOUS);
        try {
            $role->addUser($user1);
            $this->fail("Unexpected success when assigned anonymous role.");
        } catch (P4Cms_Acl_Exception $exception) {
            // OK, expected this exception type
        } catch (Exception $e) {
            $this->fail(
                "Unexpected type of exception {" . get_class($e) . "} when attempt to assign anonymous role."
            );
        }
    }

    /**
     * Test set/get users
     */
    public function testGetUsers()
    {
        // add few users to Perforce
        $user1 = P4Cms_User::create(
            array(
                'id'        => 'bob',
                'email'     => 'bob@bob-domain.com',
                'fullName'  => 'Bob Bobson',
                'config'    => array('foo'=>'bar')
            )
        )->save();
        $user2 = P4Cms_User::create(
            array(
                'id'        => 'pat',
                'email'     => 'pat@bob-domain.com',
                'fullName'  => 'Pat Patty',
            )
        )->save();
        $user3 = P4Cms_User::create(
            array(
                'id'        => 'bill',
                'email'     => 'bill@bob-domain.com',
                'fullName'  => 'Bill Billy',
                'config'    => array('foo1'=>'bar1')
            )
        )->save();

        // add bunch of users
        $role = new P4Cms_Acl_Role;
        $role->setId('Role2')
             ->setUsers(array($user1, $user2))
             ->save();

        $role = P4Cms_Acl_Role::fetch('Role2');
        $users = $role->getUsers();

        $this->assertSame(
            2,
            count($users),
            "Expected number of users having new role"
        );

        $this->assertSame(
            2,
            count(
                array_intersect(
                    array($user1->getId(), $user2->getId()),
                    $users
                )
            ),
            "Expected all added users were returned in getUsers()."
        );
    }

    /**
     * Test removing role
     */
    public function testDelete()
    {
        $user1 = P4Cms_User::create(
            array(
                'id'        => 'bob',
                'email'     => 'bob@bob-domain.com',
                'fullName'  => 'Bob Bobson'
            )
        )->save();

        // add new role
        $role = new P4Cms_Acl_Role;
        $role->setId('Test-role')
             ->addUser($user1)
             ->save();

        // ensure role was saved
        $this->assertTrue(
            P4Cms_Acl_Role::exists('Test-role'),
            'Expected existence of newly added role'
        );

        // delete
        P4Cms_Acl_Role::fetch('Test-role')->delete();

        // ensure role was removed
        $this->assertFalse(
            P4Cms_Acl_Role::exists('Test-role'),
            'Expected non-existence of newly added role'
        );

        // ensure user doesn't have this role any more
        $this->assertFalse(
            in_array('Test-role', $user1->getRoles()->invoke('getId'))
        );

        // verify that anonymous virtual roles cannot be deleted
        try {
            $role = P4Cms_Acl_Role::fetch(P4Cms_Acl_Role::ROLE_ANONYMOUS);
            $role->delete();
            $this->fail("Unexpected success when deleting anonymous role");
        } catch (P4Cms_Acl_Exception $exception) {
            // OK, expected this exception type
        } catch (Exception $e) {
            $this->fail("Unexpected type of exception {" . get_class($e) . "} when attempt to delete anonymous role.");
        }
    }

    /**
     * Test role for given user
     */
    public function testRemoveUser()
    {
        // add few users to Perforce
        $user0 = P4Cms_User::create(
            array(
                'id'        => 'own',
                'email'     => 'own@owner.com',
                'fullName'  => 'O OWn',
                'config'    => array('own'=>'me')
            )
        )->save();
        $user1 = P4Cms_User::create(
            array(
                'id'        => 'bob',
                'email'     => 'bob@bob-domain.com',
                'fullName'  => 'Bob Bobson',
                'config'    => array('foo'=>'bar')
            )
        )->save();
        $user2 = P4Cms_User::create(
            array(
                'id'        => 'pat',
                'email'     => 'pat@bob-domain.com',
                'fullName'  => 'Pat Patty',
            )
        )->save();
        $user3 = P4Cms_User::create(
            array(
                'id'        => 'bill',
                'email'     => 'bill@bob-domain.com',
                'fullName'  => 'Bill Billy',
                'config'    => array('foo1'=>'bar1')
            )
        )->save();

        // add role and assign it to users
        $role = new P4Cms_Acl_Role;
        $role->setId('Role2')
             ->setUsers(array($user1, $user2, $user3))
             ->addOwner($user0)
             ->save();

        $this->assertSame(
            3,
            count(P4Cms_Acl_Role::fetch('Role2')->getUsers()),
            "Expected number of users having new role"
        );

        // remove user and verify
        $role->removeUser($user2)
             ->save();

        $this->assertSame(
            2,
            count(P4Cms_Acl_Role::fetch('Role2')->getUsers()),
            "Expected number of users having new role after removing user"
        );
        $this->assertSame(
            2,
            count(
                array_intersect(
                    array($user1->getId(), $user3->getId()),
                    P4Cms_Acl_Role::fetch('Role2')->getUsers()
                )
            ),
            "Expected users having the role after update."
        );

        // remove another user and verify
        $role->removeUser($user1)
             ->save();

        $this->assertSame(
            1,
            count(P4Cms_Acl_Role::fetch('Role2')->getUsers()),
            "Expected number of users having new role after removing user"
        );
        $this->assertSame(
            1,
            count(
                array_intersect(
                    array($user3->getId()),
                    P4Cms_Acl_Role::fetch('Role2')->getUsers()
                )
            ),
            "Expected users having the role after update."
        );

        // remove last user and verify (make some user active to prevent failure due to saving empty associated group)
        P4Cms_User::setActive($user1);
        $role->removeUser($user3)
             ->save();

        $this->assertSame(
            0,
            count(P4Cms_Acl_Role::fetch('Role2')->getUsers()),
            "Expected no users having new role after removing last user"
        );
    }
}
