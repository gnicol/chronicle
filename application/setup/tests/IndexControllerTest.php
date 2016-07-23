<?php
/**
 * 'Smoke' tests for the setup module's IndexController.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Setup_Test_IndexControllerTest extends ModuleControllerTest
{
    /**
     * Set hostname so that urls can be built correctly.
     */
    public function setUp()
    {
        if (!defined('HTTP_HOST')) {
            $this->markTestSkipped('The HTTP_HOST is not defined.');
        }

        // we want the bootstrap to run without a perforce connection
        // so that the application thinks setup is needed.
        parent::setUp(null, TEST_DATA_PATH . '/application.ini');

        // reset any global session variables to ensure a known starting state
        $_SESSION = array();
    }

    /**
     * Test setup splash page.
     */
    public function testIndex()
    {
        // prime session to indicate previous completion
        $_SESSION['setup']['setupComplete'] = true;

        $this->dispatch('/setup');
        $this->assertModule('setup');
        $this->assertController('index');
        $this->assertAction('index');
        $this->assertQuery("a[href='/setup/start/yes']");
    }

    /**
     * Test that index forwards to requirements action when start is set.
     */
    public function testIndexForward()
    {
        $this->request->setQuery(array('start' => 'yes'));
        $this->dispatch('/setup');
        $this->assertModule('setup');
        $this->assertController('index');
        $this->assertAction('requirements');
    }

    /**
     * Test requirements action.
     */
    public function testSystemRequirements()
    {
        $this->dispatch('/setup/requirements');
        $this->assertModule('setup');
        $this->assertController('index');
        $this->assertAction('requirements');

        $body = $this->response->getBody();
        $this->assertRegExp('/<div[^>]+?preferred[^>]+?storage[^>]+?>/', $body);

        // try again with partial format
        $this->resetRequest()->resetResponse();
        $this->dispatch('/setup/requirements/format/partial');
        $body = $this->response->getBody();

        $this->assertModule('setup');
        $this->assertController('index');
        $this->assertAction('requirements');
        $this->assertRegExp('/Your data directory/', $body);
    }

    /**
     * Test adminstrator action.
     */
    public function testAdministrator()
    {
        // prime session with perforce details for new server.
        $_SESSION['setup']['storage'] = array(
            'serverType'    => 'new',
            'port'          => ''
        );

        $this->dispatch('/setup/administrator');
        $body = $this->response->getBody();
        $this->assertResponseCode('200');
        $this->assertModule('setup');
        $this->assertController('index');
        $this->assertAction('administrator');
        $this->assertQuery("input[name='user']", 'Expected user');
        $this->assertQuery("input[name='email']", 'Expected user');
        $this->assertQuery("input[name='password']", 'Expected password');
        $this->assertQuery("input[name='passwordConfirm']", 'Expected password');
        $this->assertQuery("input[type='submit']", 'Expected submit');

        // prime session with perforce details for existing server.
        $this->resetRequest()->resetResponse();
        $_SESSION['setup']['storage'] = array(
            'serverType'    => 'existing',
            'port'          => $this->utility->getP4Params('port'),
        );

        $this->dispatch('/setup/administrator');
        $body = $this->response->getBody();
        $this->assertResponseCode('200');
        $this->assertModule('setup');
        $this->assertController('index');
        $this->assertAction('administrator');
        $this->assertQuery("input[name='user']", 'Expected user');
        $this->assertQuery("input[name='password']", 'Expected password');
        $this->assertNotQuery("input[name='email']", 'Did not expect email');
        $this->assertNotQuery("input[name='passwordConfirm']", 'Did not expect password confirm');
        $this->assertQuery("input[type='submit']", 'Expected submit');

        // use bad storage details in session
        $this->resetRequest()->resetResponse();
        $_SESSION['setup']['storage'] = array();

        $this->dispatch('/setup/administrator');
        $body = $this->response->getBody();
        $this->assertModule('setup');
        $this->assertController('index');
        $this->assertAction('administrator');
        $this->assertRedirectTo('/setup/storage', 'Expect redirect to storage.');

        // use bad administrator details
        $this->resetRequest()->resetResponse();
        $_SESSION['setup']['storage'] = array(
            'serverType'    => 'existing',
            'port'          => $this->utility->getP4Params('port'),
        );

        $adminData = array();
        $this->request->setPost($adminData);
        $this->request->setMethod('POST');
        $this->dispatch('/setup/administrator');
        $body = $this->response->getBody();
        $this->assertModule('setup');
        $this->assertController('index');
        $this->assertAction('administrator');
        $this->assertQuery("input[name='user']", 'Expected user');
        $this->assertQuery("input[name='password']", 'Expected password');
        $this->assertRegExp("/<li>Value is required and can't be empty<\/li>/", $body, 'Expected error');

        // use bad administrator details with Go Back clicked
        $this->resetRequest()->resetResponse();
        $_SESSION['setup']['storage'] = array(
            'serverType'    => 'existing',
            'port'          => $this->utility->getP4Params('port'),
        );

        $adminData = array(
            'goback'    => true
        );
        $this->request->setPost($adminData);
        $this->request->setMethod('POST');
        $this->dispatch('/setup/administrator');
        $body = $this->response->getBody();
        $this->assertModule('setup');
        $this->assertController('index');
        $this->assertAction('administrator');
        $this->assertRedirectTo('/setup/storage', 'Expect redirect to storage.');

        // use good administrator details for new server
        $this->resetRequest()->resetResponse();
        $_SESSION['setup']['storage'] = array(
            'serverType'    => 'new',
            'port'          => ''
        );

        $adminData = array(
            'user'              => 'admin-user',
            'email'             => 'admin@localhost',
            'password'          => 'admin-pass1',
            'passwordConfirm'   => 'admin-pass1'
        );
        $this->request->setPost($adminData);
        $this->request->setMethod('POST');
        $this->dispatch('/setup/administrator');
        $body = $this->response->getBody();
        $this->assertModule('setup');
        $this->assertController('index');
        $this->assertAction('administrator');
        $this->assertRedirectTo('/setup/site', 'Expect redirect to site.');

        // use good administrator details for new server with Go Back clicked
        $this->resetRequest()->resetResponse();
        $_SESSION['setup']['storage'] = array(
            'serverType'    => 'new',
            'port'          => ''
        );

        $adminData = array(
            'user'              => 'admin-user',
            'email'             => 'admin@localhost',
            'password'          => 'admin-pass1',
            'passwordConfirm'   => 'admin-pass1',
            'goback'            => true
        );
        $this->request->setPost($adminData);
        $this->request->setMethod('POST');
        $this->dispatch('/setup/administrator');
        $body = $this->response->getBody();
        $this->assertModule('setup');
        $this->assertController('index');
        $this->assertAction('administrator');
        $this->assertRedirectTo('/setup/storage', 'Expect redirect to storage.');

        // use good administrator details for existing server
        $this->resetRequest()->resetResponse();
        $_SESSION['setup']['storage'] = array(
            'serverType'    => 'existing',
            'port'          => $this->utility->getP4Params('port'),
        );

        $adminData = array(
            'user'      => $this->utility->getP4Params('user'),
            'password'  => $this->utility->getP4Params('password')
        );
        $this->request->setPost($adminData);
        $this->request->setMethod('POST');
        $this->dispatch('/setup/administrator');
        $body = $this->response->getBody();
        $this->assertModule('setup');
        $this->assertController('index');
        $this->assertAction('administrator');
        $this->assertRedirectTo('/setup/site', 'Expect redirect to site.');

        // use good administrator details for existing server with Go Back clicked
        $this->resetRequest()->resetResponse();
        $_SESSION['setup']['storage'] = array(
            'serverType'    => 'existing',
            'port'          => $this->utility->getP4Params('port'),
        );

        $adminData = array(
            'user'      => $this->utility->getP4Params('user'),
            'password'  => $this->utility->getP4Params('password'),
            'goback'    => true
        );
        $this->request->setPost($adminData);
        $this->request->setMethod('POST');
        $this->dispatch('/setup/administrator');
        $body = $this->response->getBody();
        $this->assertModule('setup');
        $this->assertController('index');
        $this->assertAction('administrator');
        $this->assertRedirectTo('/setup/storage', 'Expect redirect to storage.');
    }

    /**
     * Test site action.
     */
    public function testSite()
    {
        // prime session with perforce details.
        $_SESSION['setup']['storage'] = array(
            'serverType'    => 'existing',
            'port'          => $this->utility->getP4Params('port'),
        );
        $_SESSION['setup']['administrator'] = array(
            'user'          => $this->utility->getP4Params('user'),
            'password'      => $this->utility->getP4Params('password')
        );

        $this->dispatch('/setup/site');
        $this->assertResponseCode('200');
        $this->assertModule('setup');
        $this->assertController('index');
        $this->assertAction('site');
        $this->assertQuery("input[name='title']");
        $this->assertQuery("textarea[name='urls']");
        $this->assertQuery("input[type='submit']");
    }

    /**
     * Test site creation.
     */
    public function testSiteCreation()
    {
        // prime session with perforce details.
        $_SESSION['setup']['storage'] = array(
            'serverType'    => 'existing',
            'port'          => $this->utility->getP4Params('port'),
        );
        $_SESSION['setup']['administrator'] = array(
            'user'          => $this->utility->getP4Params('user'),
            'password'      => $this->utility->getP4Params('password')
        );

        // ensure 'new-site' does not exist.
        $this->utility->removeSites();

        // create new-site.
        $siteData = array(
            'title'     => 'new-site',
            'urls'      => 'new-site.com, www.newsite.com',
        );
        $this->request->setPost($siteData);
        $this->request->setMethod('POST');
        $this->dispatch('/setup/site');
        $session = new Zend_Session_Namespace('setup');
        $this->assertTrue($session->site instanceof P4Cms_Site, 'Expect a site object.');
        $this->assertRedirectTo('/setup/summary', 'Expect redirect to summary.');

        // ensure site can login.
        try {
            $ticket = $session->site->getConnection()->login();
            $this->assertTrue(strlen($ticket) > 0, "Expected login ticket");
        } catch (P4_Connection_LoginException $e) {
            $this->fail("Expected login to succeed");
        }

        // test the unique site check which should fail because
        // the site client still exists.
        $this->resetRequest()->resetResponse();
        P4_User::fetch('chronicle')->delete();
        $this->request->setPost($siteData);
        $this->request->setMethod('POST');
        $this->dispatch('/setup/site');
        $this->assertQueryContentContains('ul.errors li', 'The site title you provided appears to be taken.');
    }

    /**
     * Test default roles creation
     */
    public function testRolesCreation()
    {
        // prime session with perforce details.
        $_SESSION['setup']['storage'] = array(
            'serverType'    => 'existing',
            'port'          => $this->utility->getP4Params('port'),
        );
        $_SESSION['setup']['administrator'] = array(
            'user'          => $this->utility->getP4Params('user'),
            'password'      => $this->utility->getP4Params('password')
        );

        // delete all groups if there are any
        foreach (P4_Group::fetchAll() as $group) {
            $group->delete();
        }

        // ensure 'test-site' does not exist.
        $this->utility->removeSites();

        // create test-site.
        $siteData = array(
            'title'     => 'test-site',
            'urls'      => 'test-site.com, www.test-site.com',
        );

        $this->request->setPost($siteData);
        $this->request->setMethod('POST');
        $this->dispatch('/setup/site');

        $this->assertModule('setup', 'Expected setup module');
        $this->assertController('index', 'Expected index controller');
        $this->assertAction('site', 'Expected site action');

        // load test-site site
        $prefix = '//' . P4Cms_Site::SITE_PREFIX;
        $suffix = '/'  . P4Cms_Site::DEFAULT_BRANCH;
        $site   = P4Cms_Site::fetch($prefix . 'test-site' . $suffix)->load();

        // verify that site groups has been created
        $adapter   = $site->getStorageAdapter();
        $siteGroup = $adapter->getProperty(P4Cms_Acl_Role::PARENT_GROUP);
        $this->assertTrue(
            P4_Group::exists($siteGroup),
            "Expected creation of site group"
        );

        // verify that default roles were created and permissions set
        $roles = P4Cms_Acl_Role::fetchAll()->invoke('getId');

        // verify that member role exists
        $this->assertTrue(
            in_array(P4Cms_Acl_Role::ROLE_MEMBER, $roles),
            "Expected existence of member role."
        );

        // verify that administrator role exists
        $this->assertTrue(
            in_array(P4Cms_Acl_Role::ROLE_ADMINISTRATOR, $roles),
            "Expected existence of administrator role."
        );
        // verify that entered user has administrator role
        $user = $this->utility->getP4Params('user');
        $this->assertTrue(
            in_array($user, P4Cms_Acl_Role::fetch(P4Cms_Acl_Role::ROLE_ADMINISTRATOR)->getUsers()),
            "Expected entered user has an administrator role."
        );

        // verify that editor role exists
        $this->assertTrue(
            in_array('editor', $roles),
            "Expected existence of editor role."
        );

        // verify that author role exists
        $this->assertTrue(
            in_array('author', $roles),
            "Expected existence of author role."
        );

        // verify permissions
        $members  = $siteGroup . '--' . P4Cms_Acl_Role::ROLE_MEMBER;
        $admins   = $siteGroup . '--' . P4Cms_Acl_Role::ROLE_ADMINISTRATOR;
        $depotMap = $prefix . "test-site" . "/...";
        $aclFile  = $prefix . "test-site" . "/*/" . P4Cms_Site::ACL_RECORD_ID;

        // construct expected protections table.
        $table = new P4_Protections;
        $table->addProtection('write',  'user',  '*',        '*', '//...')
              ->addProtection('super',  'user',  'tester',   '*', '//...')
              ->addProtection('write',  'group', $siteGroup, '*', $depotMap)
              ->addProtection('review', 'group', $siteGroup, '*', $depotMap)
              ->addProtection('super',  'group', $admins,    '*', $depotMap);

        // get super user connection
        $connection = P4_Connection::factory(
            $this->utility->getP4Params('port'),
            $this->utility->getP4Params('user'),
            null,
            $this->utility->getP4Params('password')
        );

        // compare against actual table.
        $this->assertSame(
            $table->getProtections(),
            P4_Protections::fetch($connection)->getProtections(),
            "Expected matching protections table entries."
        );
    }

    /**
     * Test site & local p4d creation.
     */
    public function testSiteAndDepotCreation()
    {
        // prime session with perforce details.
        $_SESSION['setup']['storage'] = array(
            'serverType'    => 'new',
            'port'          => '',
        );
        $_SESSION['setup']['administrator'] = array(
            'user'              => 'admin',
            'email'             => 'admin@localhost',
            'password'          => 'passWORD1',
            'passwordConfirm'   => 'passWORD1',
        );

        // ensure 'new-site' does not exist.
        $this->utility->removeSites();

        // create new-site.
        $siteData = array(
            'title'         => 'new-site',
            'description'   => 'the description',
            'urls'          => 'new-site.com, www.newsite.com'
        );
        $this->request->setPost($siteData);
        $this->request->setMethod('POST');
        $this->dispatch('/setup/site');

        $body = $this->response->getBody();
        $this->assertModule('setup', 'Expected setup module');
        $this->assertController('index', 'Expected index controller');
        $this->assertAction('site', 'Expected site action');
        $this->assertRedirectTo('/setup/summary', 'Expect redirect to summary.');
        $session = new Zend_Session_Namespace('setup');
        $this->assertTrue($session->site instanceof P4Cms_Site, 'Expect a site object.');
        $this->assertEquals(
            $siteData['title'],
            $session->site->getConfig()->getTitle(),
            'Expected title.'
        );
        $this->assertEquals(
            $siteData['description'],
            $session->site->getConfig()->getDescription(),
            'Expected description.'
        );

        // ensure local p4d created.
        $perforce       = $_SESSION['setup']['storage'];
        $administrator  = $_SESSION['setup']['administrator'];
        $root           = TEST_DATA_PATH . '/perforce';
        $this->assertSame(
            $root,
            $perforce['root'],
            'New p4d P4ROOT should be in session.'
        );
        $this->assertSame(
            'admin',
            $administrator['user'],
            'User should be admin user.'
        );
        $this->assertTrue(strpos($perforce['port'], $root) !== false, "Port should include root");
        $this->assertTrue(strlen($administrator['password']) == 9, "Password should be 9 characters");

        // ensure new depot works.
        $p4 = P4_Connection::factory(
            $perforce['port'],
            $administrator['user'],
            null,
            $administrator['password']
        );
        $info = $p4->getInfo();
        $this->assertTrue(is_array($info), "P4 info output should be array.");
        $this->assertSame($root, $info['serverRoot'], "P4 server root should be expected local root");
    }

    /**
     * Test storage action.
     */
    public function testStorage()
    {
        $this->dispatch('/setup/storage');
        $body = $this->response->getBody();
        $this->assertModule('setup', 'Expected module');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('storage', 'Expected action');
        $this->assertQuery("input[name='serverType']", 'Expected serverType field.');
        $this->assertQuery("input[name='port']", 'Expected port field.');
        $this->assertQuery("input[type='submit']", 'expected submit button');

        // test perforce action with bogus port
        $this->resetRequest()->resetResponse();
        $this->request->setPost(
            array(
                'serverType'    => 'existing',
                'port'          => 123456,
            )
        );
        $this->request->setMethod('POST');
        $this->dispatch('/setup/storage');
        $body = $this->response->getBody();
        $this->assertModule('setup', 'Expected module');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('storage', 'Expected action');
        $this->assertQueryContentContains('ul.errors li', "Unable to connect to server on '123456'.", $body);

        // test perforce action with bogus port and Go Back clicked
        $this->resetRequest()->resetResponse();
        $this->request->setPost(
            array(
                'serverType'    => 'existing',
                'port'          => 123456,
                'goback'        => true,
            )
        );
        $this->request->setMethod('POST');
        $this->dispatch('/setup/storage');
        $body = $this->response->getBody();
        $this->assertModule('setup', 'Expected module');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('storage', 'Expected action');
        $this->assertRedirectTo('/setup/requirements', 'Expect redirect to requirements');

        // test perforce action with good credentials.
        $this->resetRequest()->resetResponse();
        $this->request->setPost(
            array(
                'serverType'    => 'existing',
                'port'          => $this->utility->getP4Params('port'),
            )
        );
        $this->request->setMethod('POST');
        $this->dispatch('/setup/storage');
        $body = $this->response->getBody();
        $this->assertModule('setup', 'Expected module');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('storage', 'Expected action');
        $this->assertRedirectTo('/setup/administrator', 'Expect redirect to administrator');

        // test perforce action with good credentials and Go Back clicked.
        $this->resetRequest()->resetResponse();
        $this->request->setPost(
            array(
                'serverType'    => 'existing',
                'port'          => $this->utility->getP4Params('port'),
                'goback'        => true,
            )
        );
        $this->request->setMethod('POST');
        $this->dispatch('/setup/storage');
        $body = $this->response->getBody();
        $this->assertModule('setup', 'Expected module');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('storage', 'Expected action');
        $this->assertRedirectTo('/setup/requirements', 'Expect redirect to requirements');
    }

    /**
     * Test the rewrite action.
     */
    public function testRewrite()
    {
        $this->dispatch('/setup/rewrite');
        $this->assertModule('setup');
        $this->assertController('index');
        $this->assertAction('rewrite');
        $this->assertSame(
            trim($this->response->getBody()),
            md5_file(APPLICATION_PATH . '/setup/controllers/IndexController.php')
        );
    }

    /**
     * Test the summary action.
     */
    public function testSummary()
    {
        // prime session with perforce details.
        $_SESSION['setup']['storage'] = array(
            'serverType'    => 'new',
            'port'          => '',
        );
        $_SESSION['setup']['administrator'] = array(
            'user'              => 'admin',
            'email'             => 'admin@localhost',
            'password'          => 'passWORD1',
            'passwordConfirm'   => 'passWORD1',
        );

        // ensure 'new-site' does not exist.
        $this->utility->removeSites();

        // create new-site.
        $siteData = array(
            'title'         => 'new-site',
            'description'   => 'the site description',
            'urls'          => 'new-site.com, www.newsite.com'
        );
        $this->request->setPost($siteData);
        $this->request->setMethod('POST');
        $this->dispatch('/setup/site');
        $body = $this->response->getBody();
        $this->assertModule('setup', 'Expected setup module');
        $this->assertController('index', 'Expected index controller');
        $this->assertAction('site', 'Expected site action');
        $this->assertRedirectTo('/setup/summary', 'Expect redirect to summary.');
        $session = new Zend_Session_Namespace('setup');
        $this->assertTrue($session->site instanceof P4Cms_Site, 'Expect a site object.');

        $this->resetRequest()->resetResponse();

        // run summary action.
        $this->dispatch('/setup/summary');

        $body = $this->response->getBody();
        $this->assertModule('setup', 'Expected module');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('summary', 'Expected action');
        $this->assertQueryContentContains('td.label', 'Title');
        $this->assertQueryContentContains('td.label', 'Addresses');
        $this->assertQueryContentContains('td.label', 'Administrator');
        $this->assertQueryContentContains('td.value', 'new-site');
        $this->assertQueryContentContains('td.value', 'the site description');
        $this->assertQueryContentContains('td.value', 'new-site.com');
        $this->assertQueryContentContains('td.value', 'admin');
    }
}
