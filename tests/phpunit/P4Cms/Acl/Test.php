<?php
/**
 * Test extended functionality of p4cms acl.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Acl_Test extends TestCase
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
     * Tear down the test
     */
    public function tearDown()
    {
        P4Cms_Acl::setActive(null);

        parent::tearDown();
    }

    /**
     *  Test statically accessible active acl feature.
     */
    public function testActiveAcl()
    {
        $this->assertFalse(P4Cms_Acl::hasActive());

        try {
            P4Cms_Acl::fetchActive();
            $this->fail("Expected exception fetching active acl - no acl active!");
        } catch (P4Cms_Acl_Exception $e) {
            $this->assertTrue(true);
        }

        $acl = new P4Cms_Acl;
        $acl->makeActive();

        $this->assertTrue(P4Cms_Acl::hasActive());
        $this->assertSame(P4Cms_Acl::fetchActive(), $acl);

        P4Cms_Acl::setActive(null);
        $this->assertFalse(P4Cms_Acl::hasActive());

        P4Cms_Acl::setActive($acl);
        $this->assertTrue(P4Cms_Acl::hasActive());
    }

    /**
     * Test that roles are not serialized with rules and resources.
     */
    public function testSerialization()
    {
        $acl = new P4Cms_Acl;

        $acl->add(new P4Cms_Acl_Resource('thing', array('action', 'other')));
        $acl->addRole('doer');
        $acl->allow('doer', 'thing', 'action');

        // put the acl to bed, then wake it up again.
        $sleep = serialize($acl);
        $awake = unserialize($sleep);

        $this->assertTrue($awake->hasRole('doer'));
        $this->assertTrue($awake->has('thing'));
        $this->assertTrue($awake->isAllowed('doer', 'thing', 'action'));
    }

    /**
     * Test direct access to acl resource instances.
     */
    public function testGetResourceObjects()
    {
        $acl      = new P4Cms_Acl;
        $resource = new P4Cms_Acl_Resource('thing', array('action', 'other'));
        $acl->add($resource);

        $resources = $acl->getResourceObjects();

        $this->assertSame(
            array('thing' => $resource),
            $resources
        );
    }

    /**
     * Test ability to set roles en-mass
     */
    public function testSetRoles()
    {
        $acl = new P4Cms_Acl;

        // should start with no roles.
        $this->assertTrue(count($acl->getRoles()) === 0, "Expected no roles by default.");

        // test setting roles via role registry.
        $registry = new Zend_Acl_Role_Registry;
        $registry->add(new Zend_Acl_Role('foo'));
        $registry->add(new Zend_Acl_Role('bar'));
        $acl->setRoles($registry);

        // should have 2 roles now.
        $this->assertTrue(count($acl->getRoles()) === 2, "Expected two roles after setting them.");

        // test clearing roles.
        $acl->setRoles(null);
        $this->assertTrue(count($acl->getRoles()) === 0, "Expected no roles after clearing.");

        // test setting roles via iterator.
        $iterator = new P4Cms_Model_Iterator;
        $iterator->append(new P4Cms_Acl_Role(array('id' => 'foo')));
        $iterator->append(new P4Cms_Acl_Role(array('id' => 'bar')));
        $iterator->append(new P4Cms_Acl_Role(array('id' => 'baz')));
        $acl->setRoles($iterator);

        // should have 2 roles now.
        $this->assertTrue(count($acl->getRoles()) === 3, "Expected three roles after setting them.");

        // test w. bogus inputs.
        try {
            $acl->setRoles('alksdfj');
            $this->fail("Expected exception setting roles to string.");
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
        try {
            $iterator = new P4Cms_Model_Iterator;
            $iterator->append(new P4Cms_Model);
            $acl->setRoles($iterator);
            $this->fail("Expected exception setting roles to iterator with bogus entries.");
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test persistence of acl.
     */
    public function testSave()
    {
        // create storage adapter for testing.
        $adapter = new P4Cms_Record_Adapter;
        $adapter->setBasePath('//depot/')
                ->setConnection($this->p4);

        // create record for testing.
        $record = new P4Cms_Record;
        $record->setAdapter($adapter)
               ->setId(1);

        // create acl w. associated record.
        $acl = new P4Cms_Acl;
        $acl->setRecord($record);

        // modify acl and save
        // (add a role so we can make sure it's not saved)
        $acl->add(new P4Cms_Acl_Resource('test'))
            ->addRole('test-role')
            ->save();

        // now fetch acl from storage.
        $acl = P4Cms_Acl::fetch('1', $adapter);
        $this->assertTrue($acl instanceof P4Cms_Acl, "Expected ACL instance from fetch.");
        $this->assertTrue($acl->has('test'), "Expected ACL to have 'test' resource.");

        // ensure we don't have roles in storage.
        $this->assertFalse($acl->hasRole('test-role'));

        // ensure we can still save.
        $acl->add(new P4Cms_Acl_Resource('test2'))
            ->save();

        // fetch again from storage.
        $acl = P4Cms_Acl::fetch('1', $adapter);
        $this->assertTrue(count($acl->getResources()) === 2, "Expected two resources.");
    }

    /**
     * Test installation of default acl resources and rules.
     */
    public function testInstallDefaults()
    {
        // create storage adapter for testing.
        $adapter = new P4Cms_Record_Adapter;
        $adapter->setBasePath('//depot/')
                ->setConnection($this->p4);
        P4Cms_Record::setDefaultAdapter($adapter);

        // setup and enable some modules for testing.
        P4Cms_Module::setCoreModulesPath(TEST_ASSETS_PATH . '/core-modules');
        P4Cms_Module::addPackagesPath(TEST_ASSETS_PATH . '/more-optional-modules');
        P4Cms_Module::fetchAllDisabled()->invoke('enable');

        // create test acl.
        $acl = new P4Cms_Acl;
        $acl->setRecord(new P4Cms_Record(array('id' => 'acl')));
        $acl->addRole('role1');
        $acl->addRole('role2');

        // install default resources/privileges/rules.
        $acl->installDefaults();

        // verify pre-save.
        $this->_verifyAclDefaults($acl);

        // save it.
        $acl->save();

        // verify post-save.
        $this->_verifyAclDefaults($acl);

        // resurrect acl from storage.
        $acl = P4Cms_Acl::fetch('acl');

        // verify post-fetch.
        $this->_verifyAclDefaults($acl);
    }

    /**
     * Ensure getAllPrivileges() works as expected.
     */
    public function testGetAllPrivileges()
    {
        $acl = new P4Cms_Acl;
        $acl->add(new P4Cms_Acl_Resource('test1', array('foo', 'bar')));
        $acl->add(new P4Cms_Acl_Resource('test2', array('baz', 'bof')));

        $privileges = $acl->getAllPrivileges();
        $this->assertSame(4, count($privileges));

        foreach ($privileges as $privilege) {
            $this->assertTrue($privilege instanceof P4Cms_Acl_Privilege);
            $this->assertTrue($privilege->getResource() instanceof P4Cms_Acl_Resource);
        }
    }

    /**
     * Test auto-insertion of composed privileges.
     */
    public function testAutoInsertPrivileges()
    {
        $acl = new P4Cms_Acl;
        $acl->add(new P4Cms_Acl_Resource('resource1', array('action1', 'other1')));
        $acl->add(new P4Cms_Acl_Resource('resource2', array('action2', 'other2')));
        $acl->addRole('doer');
        $acl->allow('doer', 'resource1', 'action1');
        $acl->allow('doer', 'resource2', array('action2', 'other2'));

        $this->assertTrue(
            $acl->isAllowed('doer', 'resource1/testA', 'action1'),
            "Expected composed resource inherits rules from parent."
        );

        $this->assertTrue(
            $acl->has('resource1/testA'),
            "Expected composed resource has been added."
        );

        // ensure composed resource is not serialized
        $sleep = serialize($acl);
        $awake = unserialize($sleep);

        $this->assertFalse(
            $awake->has('resource1/testA'),
            "Expected composed resource is not serialized."
        );
        $this->assertTrue(
            $awake->has('resource1'),
            "Expected resource1 is serialized."
        );

        try {
            $acl->isAllowed('doer', 'resourceUnknown/1', 'action1');
            $this->fail('Unexpected insert of composed resource, as parent is not a valid resource.');
        } catch (Zend_Acl_Exception $e) {
            $this->assertTrue(true);
        }

        $this->assertFalse(
            $acl->has('resourceUnknown/1'),
            "Expected composed resource with unknown parent was not added."
        );
    }

    /**
     * Test getAllowedPrivileges() method.
     */
    public function testGetAllowedPrivileges()
    {
        $acl = new P4Cms_Acl;
        $acl->add(new P4Cms_Acl_Resource('resource1', array('actionA', 'actionB', 'actionC')));
        $acl->add(new P4Cms_Acl_Resource('resource2', array('actionK', 'actionL', 'actionM', 'actionA')));
        $acl->add(new P4Cms_Acl_Resource('resource3', array('actionX', 'actionY', 'actionA', 'actionK')));
        $acl->addRole('foo');
        $acl->addRole('bar');
        $acl->addRole('joe');
        $acl->addRole('bob');
        $acl->allow('foo', 'resource1', array('actionA', 'actionC'));
        $acl->allow('foo', 'resource2', array('actionK'));
        $acl->allow('bar', 'resource3', array('actionA', 'actionK'));
        $acl->allow('joe', 'resource3', array('actionX', 'actionY'));

        $privileges = $acl->getAllowedPrivileges('foo', 'resource1');
        $this->assertSame(
            2,
            count($privileges),
            "Expected number of allowed privileges for 'foo' role."
        );
        $this->assertSame(
            array(),
            array_diff($privileges, array('actionA', 'actionC')),
            "Expected list of allowed privileges for 'foo' role."
        );

        $privileges = $acl->getAllowedPrivileges('joe', 'resource3');
        $this->assertSame(
            2,
            count($privileges),
            "Expected number of allowed privileges for 'joe' role."
        );
        $this->assertSame(
            array(),
            array_diff($privileges, array('actionX', 'actionY')),
            "Expected list of allowed privileges for 'joe' role."
        );

        $privileges = $acl->getAllowedPrivileges('bob', 'resource1');
        $this->assertSame(
            0,
            count($privileges),
            "Expected number of allowed privileges for 'bob' role."
        );

        $privileges = $acl->getAllowedPrivileges('bar', 'resource1');
        $this->assertSame(
            0,
            count($privileges),
            "Expected number of allowed privileges for 'bar' role."
        );

        // composed resource should inherit from parent
        $privileges = $acl->getAllowedPrivileges('bar', 'resource3/whatever');
        $this->assertSame(
            2,
            count($privileges),
            "Expected number of allowed privileges for 'bar' role."
        );
        $this->assertSame(
            array(),
            array_diff($privileges, array('actionA', 'actionK')),
            "Expected list of allowed privileges for composed resource for 'bar' role."
        );
    }

    /**
     * Verify given acl has expected default resources/privileges/rules.
     *
     * @param   P4Cms_Acl   $acl    the acl to verify.
     */
    protected function _verifyAclDefaults($acl)
    {
        // acl should now have resources from test modules.
        $this->assertSame(3, count($acl->getResources()), "Expected acl to have three resources");
        $this->assertTrue($acl->has('resource-a'), "Expected acl to have resource-a");
        $this->assertTrue($acl->has('resource-b'), "Expected acl to have resource-b");
        $this->assertTrue($acl->has('resource-c'), "Expected acl to have resource-c");

        // verify resource-a was installed correctly.
        $resource   = $acl->get('resource-a');
        $privileges = $resource->getPrivileges();
        $this->assertSame('My Resource A Changed', $resource->getLabel());
        $this->assertSame(3, count($privileges), "Expected three privileges.");

        // verify get privilege works.
        $this->assertTrue($resource->getPrivilege('foo') instanceof P4Cms_Acl_Privilege);
        try {
            $resource->getPrivilege('bogus');
            $this->fail();
        } catch (P4Cms_Acl_Exception $e) {
            $this->assertTrue(true);
        }

        // verify foo priv.
        $this->assertSame(
            array(
                'id'        => 'foo',
                'label'     => 'Foo Privilege',
                'allow'     => array('role1', 'role2'),
                'resource'  => 'resource-a',
                'options'   => array()
            ),
            $privileges['foo']->toArray()
        );

        // verify bar priv.
        $this->assertSame(
            array(
                'id'        => 'bar',
                'label'     => 'Bar Privilege',
                'allow'     => array('role1', 'role2'),
                'resource'  => 'resource-a',
                'options'   => array()
            ),
            $privileges['bar']->toArray()
        );

        // verify baz priv.
        $this->assertSame(
            array(
                'id'        => 'baz',
                'label'     => 'Baz Privilege',
                'allow'     => array(),
                'resource'  => 'resource-a',
                'options'   => array('blah' => 'blah', 'bork' => 'bork', 'a' => 'a')
            ),
            $privileges['baz']->toArray()
        );

        // verify resource-b
        $resource   = $acl->get('resource-b');
        $privileges = $resource->getPrivileges();
        $this->assertSame('My Resource B', $resource->getLabel());
        $this->assertSame(1, count($privileges), "Expected one privilege.");

        // verify test priv.
        $this->assertSame(
            array(
                'id'    => 'test',
                'label' => 'Test Privilege',
                'allow' => array('role1'),
                'resource'  => 'resource-b',
                'options'   => array()
            ),
            $privileges['test']->toArray()
        );
    }
}
