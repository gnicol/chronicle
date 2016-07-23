<?php
/**
 * Test the user index controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class User_Test_IndexControllerTest extends ModuleControllerTest
{
    /**
     * Test manage users grid
     */
    public function testIndexAction()
    {
        $this->utility->impersonate('administrator');

        // verify that user grid is accessible
        $this->dispatch('/user');

        $this->assertModule('user', 'Expected module for dispatching /user action.');
        $this->assertController('index', 'Expected controller for dispatching /user action.');
        $this->assertAction('index', 'Expected action for dispatching /user action.');

        // verify that table and dojo data elements exist
        $this->assertXpath('//div[@dojotype="dojox.data.QueryReadStore"]', 'Expected dojo.data div');
        $this->assertXpath(
            '//table[@dojotype="p4cms.ui.grid.DataGrid" and @jsid="p4cms.user.grid.instance"]',
            'Expected dojox.grid table'
        );

        // verify add button appears
        $this->assertXpath('//button[@class="add-button"]', 'Expected role add link.');

        // check initial JSON output
        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/user/format/json');
        $body = $this->response->getBody();
        $this->assertModule('user', 'Expected module, dispatch #2. '. $body);
        $this->assertController('index', 'Expected controller, dispatch #2 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #2 '. $body);

        // verify there are no items in the grid (system user is not shown in the grid)
        $data = Zend_Json::decode($body);
        $this->assertSame(
            array(
                0 => array(
                    'id'        => 'mweiss',
                    'fullName'  => 'Michael T. Weiss',
                    'email'     => 'mweiss@thepretender.tv',
                    'roles'     => '["administrator"]',
                    'editUri'   => '/user/edit/id/mweiss',
                    'deleteUri' => '/user/delete/id/mweiss'
                )
            ),
            $data['items'],
            'Expected no users'
        );

        // add few users and verify they appear
        P4Cms_User::create(
            array(
                'id'        => 'joe',
                'email'     => 'joe@test.com',
                'fullName'  => 'Joe Joy'
            )
        )->save();
        P4Cms_User::create(
            array(
                'id'        => 'bob',
                'email'     => 'bob@test.com',
                'fullName'  => 'Bob Bobson'
            )
        )->save();
        P4Cms_User::create(
            array(
                'id'        => 'bill',
                'email'     => 'bill@test.com',
                'fullName'  => 'Billy'
            )
        )->save();

        // add new role to test roles data grid column
        P4Cms_Acl_Role::create(
            array(
                'id'        => 'foo',
                'users'     => array('bob')
            )
        )->save();

        $this->resetRequest()
             ->resetResponse();
        // sort by name
        $this->dispatch('/user/format/json?sort=id');
        $body = $this->response->getBody();
        $this->assertModule('user', 'Expected module, dispatch #3. '. $body);
        $this->assertController('index', 'Expected controller, dispatch #3 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #3 '. $body);

        $data = Zend_Json::decode($body);
        $expected = array(
            0 => array(
                'id'        => 'bill',
                'fullName'  => 'Billy',
                'email'     => 'bill@test.com',
                'roles'     => '[]',
                'editUri'   => '/user/edit/id/bill',
                'deleteUri' => '/user/delete/id/bill'
            ),
            1 => array(
                'id'        => 'bob',
                'fullName'  => 'Bob Bobson',
                'email'     => 'bob@test.com',
                'roles'     => '["foo"]',
                'editUri'   => '/user/edit/id/bob',
                'deleteUri' => '/user/delete/id/bob'
            ),
            2 => array(
                'id'        => 'joe',
                'fullName'  => 'Joe Joy',
                'email'     => 'joe@test.com',
                'roles'     => '[]',
                'editUri'   => '/user/edit/id/joe',
                'deleteUri' => '/user/delete/id/joe'
            ),
            3 => array(
                'id'        => 'mweiss',
                'fullName'  => 'Michael T. Weiss',
                'email'     => 'mweiss@thepretender.tv',
                'roles'     => '["administrator"]',
                'editUri'   => '/user/edit/id/mweiss',
                'deleteUri' => '/user/delete/id/mweiss'
            )
        );

        $this->assertEquals(
            $expected,
            $data['items'],
            'Expected 3 users'
        );
    }

    /**
     * Ensure that User_Form_Edit form returns null as password value
     * when changePassword checkbox is not checked.
     *
     * Saving changes will fail if password is anything else (like '' etc.)
     * as P4_User will try to set the password with blank old password, which
     * is wrong for all users having non-empty password.
     */
    public function testNullPassword()
    {
        $this->_initPerforceUsers();

        $params = array(
            'id'                        => 'instant-user',
            'email'                     => 'bob@test.com',
            'fullName'                  => 'Bob Bobson',
            'changePassword'            => '',
            'currentPassword'           => '',
            'password'                  => '',
            'passwordConfirm'           => '',
        );

        $form = new User_Form_Edit;
        $form->setCsrfProtection(false);
        $this->assertTrue(
            $form->isValid($params),
            'Expected valid form with sample data.'. print_r($form->getErrorMessages(), true)
        );

        // if change password is not requested, ensure P4_User will not try
        // to set the password
        $user = P4Cms_User::fetch('instant-user');
        $user->setValues($form->getValues());
        $this->assertNull(
            $user->getValue('password'),
            "Expected null value for password field if password change is not requested."
        );

        // save and check new values
        $user->save();
        $this->assertSame(
            'bob@test.com',
            $user->getValue('email'),
            "Expected new email address."
        );
        $this->assertSame(
            'Bob Bobson',
            $user->getValue('fullName'),
            "Expected new full name."
        );
    }

    /**
     * Test add action with good post
     */
    public function testAddGoodPost()
    {
        $this->utility->impersonate('anonymous');

        $params = array(
            'id'        => 'bob',
            'fullName'  => 'Bob Bobson',
            'email'     => 'bob@test.com',
        );

        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/user/add');

        $this->assertModule('user',         'Expected module.');
        $this->assertController('index',    'Expected controller');
        $this->assertAction('add',          'Expected action');

        // verify that user has been added to Perforce users
        $this->assertTrue(P4Cms_User::exists('bob'));
    }

    /**
     * Test add action with good post with some constraints.
     */
    public function testAddGoodPostConstraints()
    {
        // set default adapter to use a non-super privileged connection
        P4Cms_User::create(
            array(
                'id'        => 'foo',
                'email'     => 'foo@foo.com',
                'fullName'  => 'Mr Foo',
                'password'  => 'asdf1234'
            )
        )->save();

        // add foo to the owners of the member group so he can add users
        P4Cms_Acl_Role::fetch('member')->addOwner('foo')->save();

        // modify default adapter to use foo's connection
        $connection = P4_Connection::factory(
            P4Cms_Record::getDefaultAdapter()->getConnection()->getPort(),
            'foo',
            P4Cms_Record::getDefaultAdapter()->getConnection()->getClient(),
            'asdf1234'
        );

        $adapter = P4Cms_Record::getDefaultAdapter();
        $adapter->setConnection($connection);
        P4Cms_Record::setDefaultAdapter($adapter);

        // impersonate with some existing role, otherwise (because now connection is not super)
        // adding users will be denied by the acl check
        $this->utility->impersonate('anonymous');

        // try to add user with different security levels
        for ($i = 0; $i <= 2; $i++) {

            $counter = new P4_Counter;
            $counter->setId('security')->setValue($i, true);

            $user   = 'bob' . $i;
            $email  = $user . '@email.com';
            $params = array(
                'id'                => $user,
                'fullName'          => 'Bob Bobson',
                'email'             => $email,
                'password'          => 'qwert123',
                'passwordConfirm'   => 'qwert123'
            );

            $this->resetRequest()
                 ->resetResponse();

            $this->request->setMethod('POST');
            $this->request->setPost($params);
            $this->dispatch('/user/add');

            $this->assertModule('user',         "Expected module (security $i).");
            $this->assertController('index',    "Expected controller (security $i).");
            $this->assertAction('add',          "Expected action (security $i).");

            // verify that user has been added to Perforce users
            $this->assertTrue(P4Cms_User::exists($user));

            // verify that email matches
            $user = P4Cms_User::fetch($user);
            $this->assertSame(
                $email,
                $user->getEmail(),
                "Expected user's email (security level $i)."
            );

            // ensure passwordConfirm field was not saved in user record (config)
            $this->assertSame(
                null,
                $user->getValue('passwordConfirm'),
                "Expected user record doesn't contain passwordConfirm value."
            );
        }
    }

    /**
     * Test add as admin assigning roles.
     */
    public function testAddWithRoles()
    {
        $this->utility->impersonate('administrator');

        // assign some roles as well
        P4Cms_Acl_Role::create(
            array(
                'id'        => 'manager',
                'users'     => array('bob')
            )
        )->save();
        P4Cms_Acl_Role::create(
            array(
                'id'        => 'director',
                'users'     => array('bob')
            )
        )->save();
        P4Cms_Acl_Role::create(
            array(
                'id'        => 'ceo',
                'users'     => array('bob')
            )
        )->save();

        $params = array(
            'id'        => 'joe',
            'fullName'  => 'Joey',
            'email'     => 'joe@test.com',
            'roles'     => array('manager', 'ceo')
        );

        $this->resetRequest()->resetResponse();
        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch('/user/add');

        $this->assertModule('user',         'Expected module.');
        $this->assertController('index',    'Expected controller');
        $this->assertAction('add',          'Expected action');

        // verify that user has been added to Perforce users
        $this->assertTrue(P4Cms_User::exists('joe'));

        //verify user has member role
        $userRoles = P4Cms_User::fetch('joe')->getRoles()->invoke('getId');
        $this->assertSame(
            2,
            count($userRoles),
            "Expected new user has 2 roles"
        );
        $this->assertTrue(
            in_array('manager', $userRoles),
            "Expected user has manager role"
        );
        $this->assertTrue(
            in_array('ceo', $userRoles),
            "Expected user has ceo role"
        );
    }

    /**
     * Test add action with bad post
     */
    public function testAddBadPost()
    {
        $this->utility->impersonate('anonymous');

        $counter = new P4_Counter;
        $counter->setId('security')->setValue(1, true);

        // add some users needed for testing username duplicate filter
        $this->_initPerforceUsers();

        $tests = array(
            array(
                'message'   => "Expected error due to passwords don't match.",
                'params'    =>
                    array (
                        'id'                => 'bob',
                        'password'          => 'aaa',
                        'passwordConfirm'   => 'aaax',
                        'fullName'          => 'Bob Bobson',
                        'email'             => 'bob@test.com',
                    ),
                'errorMessage'  => User_Form_Add::E_PASSWORDS_MISMATCH,
                'userExists'    => false
            ),

            array(
                'message'   => "Expected error due to too short password.",
                'params'    =>
                    array (
                        'id'                => 'steve',
                        'password'          => 'pswd',
                        'passwordConfirm'   => 'pswd',
                        'fullName'          => 'Blank',
                        'email'             => 'blank@domain.com',
                    ),
                'errorMessage'  => "Passwords must be at least 8 characters long",
                'userExists'    => false
            ),

            array(
                'message'   => "Expected error due to weak password.",
                'params'    =>
                    array (
                        'id'                => 'weak',
                        'password'          => 'aabbccddee',
                        'passwordConfirm'   => 'aabbccddee',
                        'fullName'          => 'John Weak',
                        'email'             => '2weak@domain.com',
                    ),
                'errorMessage'  => "Passwords must be at least 8 characters long",
                'userExists'    => false
            ),

            array(
                'message'   => "Expected error due to wrong email address.",
                'params'    =>
                    array (
                        'id'                => 'joe',
                        'password'          => 'blabla',
                        'passwordConfirm'   => 'blabla',
                        'fullName'          => 'Joe Joeson',
                        'email'             => 'joe.email',
                    ),
                'errorMessage'  => "'joe.email' is not a valid email address",
                'userExists'    => false
            ),

            array(
                'message'   => "Expected error due to already existing user.",
                'params'    =>
                    array (
                        'id'                => 'instant-user',
                        'password'          => 'qwe123AA',
                        'passwordConfirm'   => 'qwe123AA',
                        'fullName'          => 'Test Tester',
                        'email'             => 'instant@mail.com',
                    ),
                'errorMessage'  => str_replace("%s", "instant-user", User_Form_Add::E_USER_EXISTS),
                'userExists'    => true
            ),

            array(
                'message'   => "Expected error due to non-allowed username.",
                'params'    =>
                    array (
                        'id'                => '123',
                        'password'          => '777000',
                        'passwordConfirm'   => '777000',
                        'fullName'          => 'Pure Numeric',
                        'email'             => '123@mail.com',
                    ),
                'errorMessage'  => 'Purely numeric values are not allowed',
                'userExists'    => false
            )
        );

        foreach ($tests as $test) {
            $this->resetRequest()
                 ->resetResponse();

            $this->request->setMethod('POST');
            $this->request->setPost($test['params']);
            $this->dispatch('/user/add');
            $responseBody = $this->response->getBody();

            $this->assertModule('user',         'Expected module.');
            $this->assertController('index',    'Expected controller');
            $this->assertAction('add',          'Expected action');

            $this->assertTrue(
                strpos($responseBody, $test['errorMessage']) !== false,
                $test['message']
            );

            // verify that user has not been added to Perforce users
            if (!$test['userExists']) {
                $this->assertFalse(P4Cms_User::exists($test['params']['id']));
            }
        }
    }

    /**
     * Test roles element when adding new user as anonymous
     */
    public function testAddDefaultRoleAnonymous()
    {
        $this->utility->impersonate('anonymous');

        $this->dispatch('/user/add');

        // verify roles element is not rendered in the form as anonymous user doesn't
        // have permission to manage roles
        $this->assertNotQuery(
            'form.user-add-form #roles-element',
            "Unexpected presence of roles element in the user form."
        );
    }

    /**
     * Test default roles pre-selection when adding new user as administrator
     */
    public function testAddDefaultRoleAdministrator()
    {
        $this->utility->impersonate('administrator');

        // ensure member role is present
        if (!P4Cms_Acl_Role::exists(P4Cms_Acl_Role::ROLE_MEMBER)) {
            P4Cms_Acl_Role::create(
                array(
                    'id'        => P4Cms_Acl_Role::ROLE_MEMBER,
                    'users'     => array('tester')
                )
            )->save();
        }

        $this->dispatch('/user/add');

        // verify roles element is present in the user add form
        $this->assertQuery(
            'form.user-add-form #roles-element',
            "Expected presence of roles element in the user form."
        );

        // verify member role is preselected by default
        $this->assertXpath(
            '//input[@type="checkbox" and @value="'.P4Cms_Acl_Role::ROLE_MEMBER.'" and @checked="checked"]',
            "Expected member role is pre-selected when adding new user."
        );
    }

    /**
     * Test edit action with good post
     */
    public function testEditGoodPost()
    {
        $this->utility->impersonate('administrator');

        $this->_initPerforceUsers();

        $tests = array(
            array(
                'loggedUser'            => null,
                'loggedUserPassword'    => null,
                'message'               => __LINE__,
                'security'              => 0,
                'params'                =>
                    array (
                        'id'                => 'instant-user',
                        'email'             => 'bob@test.com',
                        'fullName'          => 'Bob Bobson',
                        'changePassword'    => 1,
                        'currentPassword'   => 'aaaAAA123',
                        'password'          => 'a',
                        'passwordConfirm'   => 'a',
                    ),
                'passwordAfterSave'     => 'a',
            ),
            array(
                'loggedUser'            => null,
                'loggedUserPassword'    => null,
                'message'               => __LINE__,
                'security'              => 0,
                'params'                =>
                    array (
                        'id'                => 'instant-user',
                        'email'             => 'bob1@test.com',
                        'fullName'          => 'Foo',
                        'changePassword'    => '',
                        'currentPassword'   => 'a',
                        'password'          => 'b',
                        'passwordConfirm'   => 'b',
                    ),
                'passwordAfterSave'     => 'a',
            ),
            array(
                'loggedUser'            => null,
                'loggedUserPassword'    => null,
                'message'               => __LINE__,
                'security'              => 0,
                'params'                =>
                    array (
                        'id'                => 'instant-user',
                        'email'             => 'bob2@test.com',
                        'fullName'          => 'Bob 2',
                        'changePassword'    => 1,
                        'currentPassword'   => 'a',
                        'password'          => 'b',
                        'passwordConfirm'   => 'b',
                    ),
                'passwordAfterSave'     => 'b',
            ),
            array(
                'loggedUser'            => null,
                'loggedUserPassword'    => null,
                'message'               => __LINE__,
                'security'              => 0,
                'params'                =>
                    array (
                        'id'                => 'instant-user',
                        'email'             => 'bob3@test.com',
                        'fullName'          => 'Bob 3',
                        'changePassword'    => 1,
                        'currentPassword'   => 'a',
                        'password'          => '',
                        'passwordConfirm'   => '',
                    ),
                'passwordAfterSave'     => '',
            ),
            array(
                'loggedUser'            => null,
                'loggedUserPassword'    => null,
                'message'               => __LINE__,
                'security'              => 1,
                'params'                =>
                    array (
                        'id'                => 'instant-user',
                        'email'             => 'bob4@test.com',
                        'fullName'          => 'Bob 4',
                        'changePassword'    => 1,
                        'currentPassword'   => '',
                        'password'          => 'abcdAAAA',
                        'passwordConfirm'   => 'abcdAAAA',
                    ),
                'passwordAfterSave'     => 'abcdAAAA',
            ),
        );

        // prepare expected set of fields for user model
        $expectedUserFields = array('id', 'fullName', 'email', 'password');

        foreach ($tests as $test) {
            // @todo incorporate ability to change active user,
            // currently all edits are done under tester account

            $params = $test['params'];
            $userId = $params['id'];

            // set security level
            $counter = new P4_Counter;
            $counter->setId('security')->setValue($test['security'], true);

            // test edit
            $this->resetRequest()
                 ->resetResponse();
            $this->request->setMethod('POST');
            $this->request->setPost($params);
            $this->dispatch("/user/edit/id/$userId");

            $this->assertModule('user',         'Expected module.');
            $this->assertController('index',    'Expected controller');
            $this->assertAction('edit',         'Expected action');

            // verify that user's details have been changed
            $user = P4Cms_User::fetch($userId);
            $this->assertSame(
                $params['email'],
                $user->getEmail(),
                'Expected new email: line ' . $test['message']
            );
            $this->assertSame(
                $params['fullName'],
                $user->getFullName(),
                'Expected new full name: line ' . $test['message']
            );
            $this->assertTrue(
                $user->isPassword($test['passwordAfterSave']),
                'Expected new password: line ' . $test['message']
            );

            // ensure user has only expected set of fields, i.e. no extra
            // unwanted data (like passwordConfirm) have been saved
            $fieldsCheckOk = count($user->getFields()) === count($expectedUserFields)
                && !count(array_diff($user->getFields(), $expectedUserFields));
            $this->assertTrue(
                $fieldsCheckOk,
                "Unexpected fields in user model: "
                . implode(', ', array_diff($user->getFields(), $expectedUserFields))
            );
        }
    }

    /**
     * Test edit action with bad post
     */
    public function testEditBadPost()
    {
        $this->utility->impersonate('administrator');

        $this->_initPerforceUsers();

        $tests = array(
            array(
                'loggedUser'            => null,
                'loggedUserPassword'    => null,
                'message'               => __LINE__,
                'security'              => 0,
                'params'                => array(
                    'id'                => 'instant-user',
                    'email'             => 'bob1@test.com',
                    'fullName'          => 'Bob Bobson',
                    'changePassword'    => 1,
                    'currentPassword'   => 'aaaAAA123',
                    'password'          => 'a',
                    'passwordConfirm'   => 'af',
                ),
                'errorMessage'          => User_Form_Add::E_PASSWORDS_MISMATCH,
            ),
            array(
                'loggedUser'            => null,
                'loggedUserPassword'    => null,
                'message'               => __LINE__,
                'security'              => 1,
                'params'                => array(
                    'id'                => 'instant-user',
                    'email'             => 'bob1@test.com',
                    'fullName'          => 'Bob Bobson',
                    'changePassword'    => 1,
                    'currentPassword'   => 'aaaAAA123',
                    'password'          => 'abc',
                    'passwordConfirm'   => 'abc',
                ),
                'errorMessage'          => 'Passwords must be at least 8 characters long',
            ),
        );

        foreach ($tests as $test) {
            // @todo incorporate ability to change active user,
            // currently all edits are done under tester account

            $params = $test['params'];
            $userId = $params['id'];

            // set security level
            $counter = new P4_Counter;
            $counter->setId('security')->setValue($test['security'], true);

            // test edit
            $this->resetRequest()
                 ->resetResponse();
            $this->request->setMethod('POST');
            $this->request->setPost($params);
            $this->dispatch("/user/edit/id/$userId");
            $responseBody = $this->response->getBody();

            $this->assertModule('user',         'Expected module.');
            $this->assertController('index',    'Expected controller');
            $this->assertAction('edit',         'Expected action');

            // verify there is an error message
            $this->assertTrue(
                strpos($responseBody, $test['errorMessage']) !== false,
                "Expected error message '{$test['errorMessage']}' not found, line: " . $test['message']
            );
        }
    }

    /**
     * Test constraints when editing a user
     */
    public function testEditConstraints()
    {
        $this->utility->impersonate('administrator');

        // impersonating administrator role implies there is one admin user, ensure its true
        $admins = P4Cms_Acl_Role::fetch(P4Cms_Acl_Role::ROLE_ADMINISTRATOR)->getRealUsers();
        $this->assertTrue(
            count($admins) == 1,
            "Assuming existence of exactly one administrator"
        );

        // try remove admin role for this user
        $user   = P4Cms_User::fetch($admins[0]);
        $params = array(
            'id'                => $user->getId(),
            'email'             => $user->getEmail(),
            'fullName'          => $user->getFullName(),
            'changePassword'    => 0
        );

        $this->request->setMethod('POST');
        $this->request->setPost($params);
        $this->dispatch("/user/edit/id/{$user->getId()}");
        $responseBody = $this->response->getBody();

        $this->assertModule('user',         'Expected module.');
        $this->assertController('index',    'Expected controller');
        $this->assertAction('edit',         'Expected action');

        $this->assertQueryContentContains('ul.errors li', "'administrator' role is required.");
    }

    /**
     * Test login action with bad data
     */
    public function testBadLogin()
    {
        $this->request->setMethod('POST');
        $this->request->setPost(array('user' => 'instant-user'));

        $start = microtime(true);
        $this->dispatch('/user/login');
        $lapse = microtime(true) - $start;

        $this->assertModule('user',         'Expected module.');
        $this->assertController('index',    'Expected controller');
        $this->assertAction('login',        'Expected action');

        $this->assertQueryContentContains(
            'ul.errors li',
            'Login failed. Invalid user or password.',
            'Expected login error, attempt 1'
        );

        // ensure there at least a one second delay.
        $this->assertTrue((round($lapse) >= 1), "Expected minimum 1 second delay.");

        // test that user must have member role for successful login
        $this->_initPerforceUsers(false);

        $this->resetRequest()
             ->resetResponse();
        $this->request->setMethod('POST');
        $this->request->setPost(array('user' => 'instant-user', 'password' => 'aaaAAA123'));

        $this->dispatch('/user/login');

        $this->assertModule('user',         'Expected module.');
        $this->assertController('index',    'Expected controller');
        $this->assertAction('login',        'Expected action');

        $this->assertQueryContentContains(
            'ul.errors li',
            'You do not have permission to access this site.',
            'Expected login error, attempt 2'
        );
    }

    /**
     * Test login action with bad email
     */
    public function testBadLoginWithEmail()
    {
        $this->request->setMethod('POST');
        $this->request->setPost(array('user' => 'instantuser@domain.com'));

        $start = microtime(true);
        $this->dispatch('/user/login');
        $lapse = microtime(true) - $start;

        $this->assertModule('user',         'Expected module.');
        $this->assertController('index',    'Expected controller');
        $this->assertAction('login',        'Expected action');

        $this->assertQueryContentContains(
            'ul.errors li',
            'You do not have permission to access this site.',
            'Expected login error'
        );

        // ensure there at least a one second delay.
        $this->assertTrue((round($lapse) >= 1), "Expected minimum 1 second delay.");
    }

    /**
     * Test login action with good data
     */
    public function testGoodLogin()
    {
        $this->_initPerforceUsers();

        $this->request->setMethod('POST');
        $this->request->setPost(array('user' => 'instant-user', 'password' => 'aaaAAA123'));

        $this->dispatch('/user/login');

        $this->assertModule('user',         'Expected module.');
        $this->assertController('index',    'Expected controller');
        $this->assertAction('login',        'Expected action');

        $this->assertRedirectTo('/',        'Expect redirect home. Got:'. print_r($this->response, true));
    }

    /**
     * Test login action with good email
     */
    public function testGoodEmailLogin()
    {
        $this->_initPerforceUsers();

        $this->request->setMethod('POST');
        $this->request->setPost(array('user' => 'instantuser@domain.com', 'password' => 'aaaAAA123'));

        $this->dispatch('/user/login');

        $this->assertModule('user',         'Expected module.');
        $this->assertController('index',    'Expected controller');
        $this->assertAction('login',        'Expected action');

        $this->assertRedirectTo('/',        'Expect redirect home. Got:'. print_r($this->response, true));
    }

    /**
     * Test delete action via get
     */
    public function testDeleteGet()
    {
        $this->utility->impersonate('administrator');

        // add user
        $user = new P4Cms_User;
        $user->setId('delete-test')
             ->setFullName('To Remove')
             ->setEmail('2remove@domain.com')
             ->save();

        // verify that user has been added to Perforce users
        $this->assertTrue(P4Cms_User::exists('delete-test'));

        // try to remove via GET - shouldn't work (throws P4Cms_AccessDeniedException exception)
        $this->resetRequest()
             ->resetResponse();

        $this->dispatch('/user/delete/id/delete-test');
        $responseBody = $this->response->getBody();

        $this->assertModule('error',            'Expected module.');
        $this->assertController('index',        'Expected controller');
        $this->assertAction('access-denied',    'Expected action');

        $this->assertRegexp(
            "/Deleting users is not permitted in this context/",
            $responseBody,
            "Expected Redirection to Access Denied page"
        );

        // verify that user still exists
        $this->assertTrue(P4Cms_User::exists('delete-test'));
    }

    /**
     * Test delete action with good post
     */
    public function testDeleteGoodPost()
    {
        $this->utility->impersonate('administrator');

        // add user
        $user = new P4Cms_User;
        $user->setId('delete-test')
             ->setFullName('To Remove')
             ->setEmail('2remove@domain.com')
             ->save();

        // verify that user has been added to Perforce users
        $this->assertTrue(P4Cms_User::exists('delete-test'));

        // try to remove via post - should work
        $this->resetRequest()
             ->resetResponse();
        $this->request->setMethod('POST');
        $this->request->setPost(array('id' => 'delete-test'));

        $this->dispatch('/user/delete');

        $this->assertModule('user',         'Expected module.');
        $this->assertController('index',    'Expected controller');
        $this->assertAction('delete',       'Expected action');
        $this->assertRedirectTo('/user',    'Expected redirect to manage users');

        // verify that user is gone
        $this->assertFalse(P4Cms_User::exists('delete-test'));
        //verify that user's config file is gone as well
        $this->assertFalse(P4Cms_Record::exists('users/delete-test'));
    }

    /**
     * Test constraints when deleting a user
     */
    public function testDeleteConstraints()
    {
        $this->utility->impersonate('administrator');

        $this->request->setMethod('POST');
        $this->request->setPost(array('id' => P4Cms_User::fetchActive()->getId()));

        // ensure last administrator cannot be removed
        $this->dispatch('/user/delete');
        $responseBody = $this->response->getBody();

        $this->assertModule('error',            'Expected module.');
        $this->assertController('index',        'Expected controller');
        $this->assertAction('access-denied',    'Expected action');

        $this->assertRegexp(
            "/The only administrator cannot be deleted/",
            $responseBody,
            "Expected Redirection to Access Denied page when try to delete last administrator."
        );
    }

    /**
     * Test response when attempted to edit user with no id.
     */
    public function testEditNoId()
    {
        $this->utility->impersonate('anonymous');

        // ensure user is redirected to access-denied if attempted to edit user with no id
        $this->dispatch('/user/edit');
        $responseBody = $this->response->getBody();

        $this->assertModule('error',            'Expected module.');
        $this->assertController('index',        'Expected controller');
        $this->assertAction('access-denied',    'Expected action');

        $this->assertRegexp(
            "/You don't have permission to edit this user/",
            $responseBody,
            "Expected Redirection to Access Denied page when try to edit user with no id."
        );
    }

    /**
     * Test response when attempted to edit user with non-existing id.
     */
    public function testEditWrongId()
    {
        $this->utility->impersonate('administrator');

        // ensure user is redirected to page-not-found if attempted to edit user with non-existing id
        $this->dispatch('/user/edit/id/does-not-exist');
        $responseBody = $this->response->getBody();

        $this->assertModule('error',            'Expected module.');
        $this->assertController('index',        'Expected controller');
        $this->assertAction('page-not-found',   'Expected action');

        // try with purely numeric id (its a slightly different case
        // as purely numeric ids are not allowed for usernames)
        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/user/edit/id/123');
        $responseBody = $this->response->getBody();

        $this->assertModule('error',            'Expected module #2.');
        $this->assertController('index',        'Expected controller #2.');
        $this->assertAction('page-not-found',   'Expected action #2.');
    }

    /**
     * Initialize Perforce users
     *
     * @param boolean $assignMemberRole     if true, then member role will be automatically
     *                                      assigned to the user
     */
    protected function _initPerforceUsers($assignMemberRole = true)
    {
        // Add 'instant-user' user
        $user = new P4Cms_User;
        $user->setId('instant-user')
             ->setFullName('Instant User')
             ->setEmail('instantuser@domain.com')
             ->setPassword('aaaAAA123')
             ->save();

        // assign user a member role if requested
        if ($assignMemberRole) {
            $role = P4Cms_Acl_Role::fetch(P4Cms_Acl_Role::ROLE_MEMBER);
            $role->addUser(P4Cms_User::fetch('instant-user'))
                 ->save();
        }
    }
}
