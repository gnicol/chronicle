<?php
/**
 * Test the user index controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class User_Test_RoleControllerTest extends ModuleControllerTest
{
    /**
     * Set the active user prior to each test.
     */
    public function setUp()
    {
        parent::setUp();

        $user = new P4Cms_User;
        $user->setId('tester')
             ->setPersonalAdapter($user->createPersonalAdapter());
        P4Cms_User::setActive($user);
    }

    /**
     * Test roles view
     */
    public function testIndexAction()
    {
        $this->utility->impersonate('administrator');

        // verify that roles grid is accessible
        $this->dispatch('/user/role');

        $this->assertModule('user', 'Expected module for dispatching /user/role action.');
        $this->assertController('role', 'Expected controller for dispatching /user/role action.');
        $this->assertAction('index', 'Expected action for dispatching /user/role action.');

        // verify that table and dojo data elements exist
        $this->assertXpath('//div[@dojotype="dojox.data.QueryReadStore"]', 'Expected dojo.data div');
        $this->assertXpath(
            '//table[@dojotype="p4cms.ui.grid.DataGrid" and @jsid="p4cms.user.role.grid.instance"]',
            'Expected dojox.grid table'
        );

        // verify add button appears
        $this->assertXpath('//button[@class="add-button"]', 'Expected role add link.');

        // check initial JSON output
        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/user/role/format/json');
        $body = $this->response->getBody();
        $this->assertModule('user', 'Expected module, dispatch #2. '. $body);
        $this->assertController('role', 'Expected controller, dispatch #2 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #2 '. $body);
        
        // verify default system roles are present
        $data = Zend_Json::decode($body);
        $this->_checkSystemRoles($data);

        // add new user to test roles assignement
        P4Cms_User::create(
            array(
                'id'        => 'roler',
                'email'     => 'roler@test.com',
                'fullName'  => 'Roler Tester'
            )
        )->save();

        // create some roles and verify that they appear
        P4Cms_Acl_Role::create(
            array(
                'id'        => 'foo',
                'users'     => array('tester')
            )
        )->save();
        P4Cms_Acl_Role::create(
            array(
                'id'        => 'bar',
                'users'     => array('tester', 'roler')
            )
        )->save();

        // check initial JSON output
        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/user/role/format/json');
        $body = $this->response->getBody();
        $this->assertModule('user', 'Expected module, dispatch #3. '. $body);
        $this->assertController('role', 'Expected controller, dispatch #3 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #3 '. $body);

        // verify default system roles
        $data = Zend_Json::decode($body);
        $this->_checkSystemRoles($data);

        // verify new roles are present as well
        $fooItem = $this->_searchItem($data['items'], 'id', 'foo');
        $this->assertTrue(
            $fooItem !== null,
            "Expected 'foo' role is present."
        );
        $this->assertSame(
            false,
            $fooItem['isSystem'],
            "Expected 'foo' role is not system"
        );
        $this->assertSame(
            false,
            $fooItem['isVirtual'],
            "Expected 'foo' role is not virtual"
        );        
        $this->assertSame(
            1,
            $fooItem['usersCount'],
            "Expected 'foo' role has one user"
        );
        
        $barItem = $this->_searchItem($data['items'], 'id', 'bar');
        $this->assertTrue(
            $barItem !== null,
            "Expected 'bar' role is present."
        );
        $this->assertSame(
            false,
            $barItem['isSystem'],
            "Expected 'bar' role is not system"
        );
        $this->assertSame(
            false,
            $barItem['isVirtual'],
            "Expected 'bar' role is not virtual"
        );
        $this->assertSame(
            2,
            $barItem['usersCount'],
            "Expected 'bar' role has two users"
        );
    }    

    /**
     * Test add action with a good post
     */
    public function testAddGoodPost()
    {
        $this->utility->impersonate('administrator');

        $this->_createTestRole();

        // define test batch
        $tests = array(
            array(
                'params'    => array(
                    'id'    => 'roler',
                    'users' => array('joe')
                ),
                'message'   => 'Unexpected failure when adding new role: ' . __LINE__
            ),
            array(
                'params'    => array(
                    'id'    => 'writer12',
                    'users' => array('bob', 'joe')
                ),
                'message'   => 'Unexpected failure when adding new role: ' . __LINE__
            ),
        );

        foreach ($tests as $test) {
            $this->resetRequest()
                 ->resetResponse();
            $this->request->setMethod('POST');
            $this->request->setPost($test['params']);
            $this->dispatch('/user/role/add');

            $this->assertModule('user', "Expected user module when adding role '{$test['params']['id']}'.");
            $this->assertController('role', "Expected role controller when adding role '{$test['params']['id']}'.");
            $this->assertAction('add', "Expected add action when adding role '{$test['params']['id']}'.");

            // verify role exists
            $this->assertTrue(
                P4Cms_Acl_Role::exists($test['params']['id']),
                $test['message']
            );
        }

        // verify joe has assigned roles
        $joeRoles = P4Cms_User::fetch('joe')->getRoles()->invoke('getId');
        $this->assertSame(
            2,
            count($joeRoles),
            'Expected joe has 2 roles after save'
        );
        $this->assertTrue(
            in_array('roler', $joeRoles),
            "Expected joe has roler role"
        );
        $this->assertTrue(
            in_array('writer12', $joeRoles),
            "Expected joe has writer12 role"
        );

        // verify bob has assigned roles
        $bobRoles = P4Cms_User::fetch('bob')->getRoles()->invoke('getId');

        $this->assertSame(
            2,
            count($bobRoles),
            'Expected bob has 2 roles after save'
        );
        $this->assertTrue(
            in_array('writer12', $bobRoles),
            "Expected bob has writer12 role"
        );
        $this->assertTrue(
            in_array('myrole', $bobRoles),
            "Expected bob has myrole role"
        );
    }

    /**
     * Test add action with a bad post
     */
    public function testAddBadPost()
    {
        $this->utility->impersonate('administrator');

        $this->_createTestRole();
        
        // verify role with id matching another already existing role cannot be added
        $params = array(
            'id'    => 'myrole',
            'users' => array('joe')
        );
        $this->resetRequest()
             ->resetResponse();
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/user/role/add');

        $this->assertModule('user', "Expected user module when adding role '{$params['id']}'.");
        $this->assertController('role', "Expected role controller when adding role '{$params['id']}'.");
        $this->assertAction('add', "Expected add action when adding role '{$params['id']}'.");
        
        $this->assertQueryContentContains(
            "form ul.errors li",
            "Role '{$params['id']}' already exists",
            "Expected error message when adding a role with existing id."
        );
        $this->assertFalse(
            in_array('joe', P4Cms_Acl_Role::fetch('myrole')->getUsers()),
            "Expected 'myrole' role was not altered"
        );

        // cannot create another system role
        $params = array(
            'id'    => P4Cms_Acl_Role::ROLE_ADMINISTRATOR,
            'users' => array('joe')
        );
        $this->resetRequest()
             ->resetResponse();
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/user/role/add');

        $this->assertModule('user', "Expected user module when adding role '{$params['id']}'.");
        $this->assertController('role', "Expected role controller when adding role '{$params['id']}'.");
        $this->assertAction('add', "Expected add action when adding role '{$params['id']}'.");

        $this->assertQueryContentContains(
            "form ul.errors li",
            "Role '".P4Cms_Acl_Role::ROLE_ADMINISTRATOR."' already exists",
            "Expected error message when adding a role with existing id."
        );
        $this->assertFalse(
            in_array('joe', P4Cms_Acl_Role::fetch(P4Cms_Acl_Role::ROLE_ADMINISTRATOR)->getUsers()),
            "Expected 'myrole' role was not altered"
        );

        // verify role id must be set
        $params = array(
            'id'    => '',
            'users' => array('joe')
        );
        $this->resetRequest()
             ->resetResponse();
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/user/role/add');

        $this->assertModule('user', "Expected user module when adding role '{$params['id']}'.");
        $this->assertController('role', "Expected role controller when adding role '{$params['id']}'.");
        $this->assertAction('add', "Expected add action when adding role '{$params['id']}'.");

        $this->assertQueryContentContains(
            "form ul.errors li",
            "Value is required and can't be empty",
            "Expected error message when adding a role without id."
        );

        // role id cannot be purely numeric
        $params = array(
            'id'    => '123',
            'users' => array('joe')
        );
        $this->resetRequest()
             ->resetResponse();
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/user/role/add');

        $this->assertModule('user', "Expected user module when adding role '{$params['id']}'.");
        $this->assertController('role', "Expected role controller when adding role '{$params['id']}'.");
        $this->assertAction('add', "Expected add action when adding role '{$params['id']}'.");

        $this->assertQueryContentContains(
            "form ul.errors li",
            "Purely numeric values are not allowed",
            "Expected error message when adding a role without id."
        );
        $this->assertFalse(
            P4Cms_Acl_Role::exists('123'),
            "Unexpected existence of 123 role."
        );
    }

    /**
     * Test edit action with a good post
     */
    public function testEditGoodPost()
    {
        $this->utility->impersonate('administrator');

        $this->_createTestRole();

        // alter roles users
        $params = array(
            'users' => array('joe', 'bob')
        );
        $this->request->setParam('id', 'myrole');
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/user/role/edit');

        $this->assertModule('user', "Expected user module when editing a role 'myrole'.");
        $this->assertController('role', "Expected role module when editing a role 'myrole'.");
        $this->assertAction('edit', "Expected edit action when editing a role 'myrole'.");

        // verify that users have been assigned
        $roleUsers = P4Cms_Acl_Role::fetch('myrole')->getUsers();
        $this->assertSame(
            2,
            count($roleUsers),
            'Expected role has 2 users after edit'
        );
        $this->assertTrue(
            in_array('joe', $roleUsers),
            "Expected joe has the role"
        );
        $this->assertTrue(
            in_array('bob', $roleUsers),
            "Expected bob has the role"
        );
    }
    
    /**
     * Test edit action with a bad post
     */
    public function testEditBadPost()
    {
        $this->utility->impersonate('administrator');

        $this->_createTestRole();

        // test to edit nonexisting role
        $params = array(
            'id'    => 'fail',
            'users' => array('joe')
        );
        $this->resetRequest()
             ->resetResponse();
        $this->request->setParam('id', 'role_noexist');
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/user/role/edit');

        $this->assertModule('error', __LINE__ .': Last module run should be error module.');
        $this->assertController('index', __LINE__ .': Expected controller');
        $this->assertAction('error', __LINE__ .': Expected action');
    }

    /**
     * Test altering role name
     */
    public function testAlterRoleIdPost()
    {
        $this->utility->impersonate('administrator');

        $this->_createTestRole();
        
        $params = array(
            'id'    => 'myrole_altered',
            'users' => array('joe', 'bob')
        );
        $this->resetRequest()
             ->resetResponse();
        $this->request->setParam('id', 'myrole');
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/user/role/edit');

        $this->assertModule('user', "Expected user module when editing a role 'myrole'.");
        $this->assertController('role', "Expected role module when editing a role 'myrole'.");
        $this->assertAction('edit', "Expected edit action when editing a role 'myrole'.");

        // ensure that id has not been changed
        $this->assertTrue(
            P4Cms_Acl_Role::exists('myrole'),
            "Expected myrole has not been deleted."
        );
        $this->assertFalse(
            P4Cms_Acl_Role::exists($params['id']),
            "Expected new role has not been created."
        );

        // verify that users have been altered
        $roleUsers = P4Cms_Acl_Role::fetch('myrole')->getUsers();
        $this->assertSame(
            2,
            count($roleUsers),
            'Expected role has 2 users after edit'
        );
        $this->assertTrue(
            in_array('joe', $roleUsers),
            "Expected joe has the role"
        );
        $this->assertTrue(
            in_array('bob', $roleUsers),
            "Expected bob has the role"
        );
    }

    /**
     * Test edit a system role
     */
    public function testEditSystemRole()
    {
        $this->utility->impersonate('administrator');

        $this->_createTestRole();

        // verify system role's id cannot be changed
        $params = array(
            'id'    => 'member_copy',
            'users' => array('joe', 'bob')
        );
        $this->resetRequest()
             ->resetResponse();
        $this->request->setParam('id', 'member');
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/user/role/edit');

        $this->assertModule('user', "Expected user module when editing a role 'member'.");
        $this->assertController('role', "Expected role module when editing a role 'member'.");
        $this->assertAction('edit', "Expected edit action when editing a role 'member'.");

        // verify id hasn't changed
        $this->assertTrue(
            P4Cms_Acl_Role::exists('member'),
            "Expected member role exists"
        );

        // verify member_copy doesn't exist
        $this->assertFalse(
            P4Cms_Acl_Role::exists('member_copy'),
            "Expected member_copy role doesn't exist"
        );

        // verify users have been updated
        $roleUsers = P4Cms_Acl_Role::fetch('member')->getUsers();
        $this->assertSame(
            2,
            count($roleUsers),
            'Expected role has 2 users after edit'
        );
        $this->assertTrue(
            in_array('joe', $roleUsers),
            "Expected joe has the role"
        );
        $this->assertTrue(
            in_array('bob', $roleUsers),
            "Expected bob has the role"
        );
    }

    /**
     * Test edit an anonymous role
     */
    public function testEditAnonymousRole()
    {
        $this->utility->impersonate('administrator');

        $params = array(
            'id'    => 'anonymous',
            'users' => array('joe')
        );
        $this->resetRequest()
             ->resetResponse();
        $this->request->setParam('id', 'anonymous');
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/user/role/edit/');
        $responseBody = $this->response->getBody();

        $this->assertModule('error',            'Expected module.');
        $this->assertController('index',        'Expected controller');
        $this->assertAction('access-denied',    'Expected action');

        $this->assertRegexp(
            "/Cannot modify 'anonymous' role/",
            $responseBody,
            "Expected Redirection to Access Denied page when try to edit anonymous role."
        );
    }

    /**
     *  Test delete action with a good post
     */
    public function testGoodDeletePost()
    {
        $this->utility->impersonate('administrator');

        $this->_createTestRole();

        // verify bob has the myrole role
        $bobRoles = P4Cms_User::fetch('bob')->getRoles()->invoke('getId');
        $this->assertTrue(
            in_array('myrole', $bobRoles),
            "Expected bob has myrole"
        );

        // delete myrole
        $this->resetRequest()
             ->resetResponse();
        $this->request->setMethod('POST');
        $this->request->setPost(array('id' => 'myrole'));

        $this->dispatch('/user/role/delete');

        $this->assertModule('user',         'Expected module.');
        $this->assertController('role',     'Expected controller');
        $this->assertAction('delete',       'Expected action');

        // verify role is not present
        $this->assertFalse(
            P4Cms_Acl_Role::exists('myrole'),
            "Unexpected existence of deleted role"
        );

        // verify role is not assigned to users
        $bobRoles = P4Cms_User::fetch('bob')->getRoles()->invoke('getId');
        $this->assertFalse(
            in_array('myrole', $bobRoles),
            "Unexpected myrole is still assigned to the bob"
        );
    }

    /**
     *  Test delete action with a bad post
     */
    public function testBadDeletePost()
    {
        $this->utility->impersonate('administrator');

        // verify that system roles cannot be removed
        $this->resetRequest()
             ->resetResponse();
        $this->request->setMethod('POST');
        $this->request->setPost(array('id' => 'member'));

        $this->dispatch("/user/role/delete");
        $responseBody = $this->response->getBody();

        $this->assertModule('error',            'Expected module.');
        $this->assertController('index',        'Expected controller');
        $this->assertAction('access-denied',    'Expected action');
        $this->assertRegexp(
            "/System roles cannot be deleted/",
            $responseBody,
            "Expected redirection to access-denied when try to delete a system role"
        );

        // verify all system roles are present
        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/user/role/format/json');
        $body = $this->response->getBody();
        $data = Zend_Json::decode($body);
        $this->_checkSystemRoles($data);
    }

    /**
     *  Test delete action by using get
     */
    public function testBadDeleteGet()
    {
        $this->utility->impersonate('administrator');

        $this->_createTestRole();
        
        // verify that role cannot be removed via get
        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/user/role/delete/id/myrole');
        $responseBody = $this->response->getBody();

        $this->assertModule('error',            'Expected module.');
        $this->assertController('index',        'Expected controller');
        $this->assertAction('access-denied',    'Expected action');

        $this->assertRegexp(
            "/Deleting roles is not permitted in this context/",
            $responseBody,
            "Expected redirection to access-denied when deleting role via get"
        );

        // verify role is still present
        $this->assertTrue(
            P4Cms_Acl_Role::exists('myrole'),
            "Expected existence of myrole role"
        );        
    }

    /**
     *  Create 'myrole' role and 'bob' and 'joe' users for testing.
     */
    protected function _createTestRole()
    {
        P4Cms_User::create(
            array(
                'id'        => 'bob',
                'email'     => 'bob@test.com',
                'fullName'  => 'Bobs'
            )
        )->save();

        P4Cms_User::create(
            array(
                'id'        => 'joe',
                'email'     => 'joe@test.com',
                'fullName'  => 'Joey'
            )
        )->save();

        P4Cms_Acl_Role::create(
            array(
                'id'        => 'myrole',
                'users'     => array('bob')
            )
        )->save();
        
        // ensure users and role exist
        $this->assertTrue(
            P4Cms_User::exists('bob'),
            "Expected 'bob' user for testing was added."
        );
        
        $this->assertTrue(
            P4Cms_User::exists('joe'),
            "Expected 'bob' user for testing was added."
        );
        
        $this->assertTrue(
            P4Cms_Acl_Role::exists('myrole'),
            "Expected 'myrole' role for testing was added."
        );        
    }

    /**
     * Search for key in items, so that $items[key] (which is array) contains $key=>$val item.
     * Returns $items[key].
     * 
     * Sample structure of $items:
     *
     * [items] => Array
     *   (
     *       [0] => Array
     *           (
     *               [id] => administrator
     *               [type] => System
     *               [isSystem] => 1
     *               [isVirtual] =>
     *               [usersCount] => 1
     *               [editUri] => /user/role/edit/id/administrator
     *               [deleteUri] => /user/role/delete/id/administrator
     *           )
     *
     *       [1] => Array
     *           (
     *               [id] => anonymous
     *               [type] => System
     *               [isSystem] => 1
     *               [isVirtual] => 1
     *               [usersCount] => 0
     *               [editUri] => /user/role/edit/id/anonymous
     *               [deleteUri] => /user/role/delete/id/anonymous
     *           )
     *
     *       [2] => Array
     *           (
     *               [id] => member
     *               [type] => System
     *               [isSystem] => 1
     *               [isVirtual] =>
     *               [usersCount] => 1
     *               [editUri] => /user/role/edit/id/member
     *               [deleteUri] => /user/role/delete/id/member
     *           )
     *
     *   )
     *
     * @param   array       $items  input array to search in
     * @param   string      $key    key to search for
     * @param   string      $value  value to search for
     * @return  array|null          item of $items containing ($key => $value) item
     *                              or null if not found
     */
    protected function _searchItem(array $items, $key, $value)
    {
        foreach ($items as $item) {
            if (isset($item[$key]) && $item[$key] === $value) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Helper function to verify system roles are present in the given data.
     *
     * @param array $data  json decoded body of role index page
     */
    protected function _checkSystemRoles(array $data)
    {
        $adminItem = $this->_searchItem($data['items'], 'id', P4Cms_Acl_Role::ROLE_ADMINISTRATOR);
        $this->assertTrue(
            $adminItem !== null,
            "Expected administrator role is present by default."
        );
        $this->assertSame(
            true,
            $adminItem['isSystem'],
            "Expected administrator role is system"
        );
        $this->assertSame(
            false,
            $adminItem['isVirtual'],
            "Expected administrator role is not virtual"
        );

        $memberItem = $this->_searchItem($data['items'], 'id', P4Cms_Acl_Role::ROLE_MEMBER);
        $this->assertTrue(
            $memberItem !== null,
            "Expected member role is present by default."
        );
        $this->assertSame(
            true,
            $memberItem['isSystem'],
            "Expected member role is system"
        );
        $this->assertSame(
            false,
            $memberItem['isVirtual'],
            "Expected member role is not virtual"
        );

        $anonymousItem = $this->_searchItem($data['items'], 'id', P4Cms_Acl_Role::ROLE_ANONYMOUS);
        $this->assertTrue(
            $memberItem !== null,
            "Expected anonymous role is present by default."
        );
        $this->assertSame(
            true,
            $anonymousItem['isSystem'],
            "Expected anonymous role is system"
        );
        $this->assertSame(
            true,
            $anonymousItem['isVirtual'],
            "Expected anonymous role is virtual"
        );
    }
}
