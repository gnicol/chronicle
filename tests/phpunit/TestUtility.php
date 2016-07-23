<?php
/**
 * Utility methods for running test suites.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class TestUtility
{
    private $_testClass;
    private $_testMethod;
    private $_logFile;
    private $_p4Params  = array();
    private $_testSites = array();

    const TEST_MAX_TRY_COUNT = 1000;

    /**
     * Constructor for this class
     *
     * @param  string   $testClass   The name of class of the test
     * @param  string   $testMethod  The name of method of the test
     */
    public function __construct($testClass, $testMethod)
    {
        $this->_testClass  = $testClass;
        $this->_testMethod = $testMethod;

        $this->_logFile = TEST_LOG_PATH .'/'. $testClass .'-'. $testMethod .'.log';

        // configure p4 connection params.
        $serverRoot      = TEST_DATA_PATH .'/server-'. $testClass .'-'. $testMethod;
        $clientRoot      = TEST_DATA_PATH .'/clients-'. $testClass .'-'. $testMethod;
        $this->_p4Params = array(
            'serverRoot' => $serverRoot,
            'clientRoot' => $clientRoot,
            'port'       => 'rsh:' . P4D_PATH . ' -iqr ' . $serverRoot . ' -J off '
                            . '-vtrack=0 -vserver.locks.dir=disabled',
            'user'       => 'tester',
            'client'     => 'test-client',
            'group'      => 'test-group',
            'password'   => 'testing123'
        );

        // define at least one site configuration for testing purposes.
        $prefix = '//' . P4Cms_Site::SITE_PREFIX;
        $suffix = '/'  . P4Cms_Site::DEFAULT_BRANCH;
        $this->_testSites = array(
            $prefix . 'test' . $suffix => array(
                'urls' => array(
                    defined('HTTP_HOST') ? preg_replace('#^http://#', '', HTTP_HOST) : 'test-host.com'
                )
            )
        );
    }

    /**
     * Get Perforce config parameters
     *
     * @param  string   $param   Optional - specific Perforce parameter to get
     *
     * @return mixed    A specific Perforce parameter, or all parameters
     */
    public function getP4Params($param = null)
    {
        $params = $this->_p4Params;
        if ($param) {
            return isset($params[$param]) ? $params[$param] : null;
        }
        return $params;
    }

    /**
     * Get test sites configuration
     *
     * @return array    A site configuration array
     */
    public function getTestSites()
    {
        return $this->_testSites;
    }

    /**
     * Create a Perforce connection for testing. The perforce connection will
     * connect using a p4d started with the -i (run for inetd) flag.
     *
     * @param  string   $type   Allow caller to force the API implementation.
     *
     * @return P4_Connection_Interface  A Perforce API implementation.
     */
    public function createP4Connection($type = null)
    {
        // make sure we're using the bundled p4/p4d.
        require_once(APPLICATION_PATH . '/Bootstrap.php');
        Bootstrap::initPath();

        extract($this->_p4Params);

        if (!is_dir($serverRoot)) {
            throw new P4_Exception('Unable to create new server.');
        }

        // create connection.
        $p4 = P4_Connection::factory(
            $port, $user, $client, $password, null, $type
        );

        // create user.
        $userForm = array(
            'User'     => $user,
            'Email'    => $user . '@testhost',
            'FullName' => 'Test User',
            'Password' => $password
        );
        $p4->run('user', '-i', $userForm);
        $p4->run('login', array(), $password);

        // establish protections.
        // This looks like a no-op, but remember that fresh P4 servers consider
        // every user to be a superuser. These operations make only the configured
        // user a superuser, and subsequent users will be 'normal' users.
        $result = $p4->run('protect', '-o');
        $protect = $result->getData(0);
        $p4->run('protect', '-i', $protect);

        // create client
        $clientForm = array(
            'Client'    => $client,
            'Owner'     => $user,
            'Root'      => $clientRoot .'/superuser',
            'View'      => array('//depot/... //'. $client .'/...')
        );
        $p4->run('client', '-i', $clientForm);

        $this->openPermissions($serverRoot, true);

        return $p4;
    }

    /**
     * Recursively remove a directory and all of it's file contents.
     *
     * @param  string   $directory   The directory to remove.
     * @param  boolean  $recursive   when true, recursively delete directories.
     * @param  boolean  $removeRoot  when true, remove the root (passed) directory too
     */
    public function removeDirectory($directory, $recursive = true, $removeRoot = true)
    {
        if (is_dir($directory)) {
            $files = new RecursiveDirectoryIterator($directory);
            foreach ($files as $file) {
                if ($files->isDot()) {
                    continue;
                }
                if ($file->isFile()) {
                    // on Windows, it may take some time for open file handles to
                    // be closed.  We try to unlink a file for TEST_MAX_TRY_COUNT
                    // times and then bail out.
                    $count = 0;
                    chmod($file->getPathname(), 0777);
                    while ($count <= self::TEST_MAX_TRY_COUNT) {
                        try {
                            unlink($file->getPathname());
                            break;
                        } catch (Exception $e) {
                            $count++;
                            if ($count == self::TEST_MAX_TRY_COUNT) {
                                throw new Exception(
                                    "Can't delete '" . $file->getPathname() . "' with message ".$e->getMessage()
                                );
                            }
                        }
                    }
                } elseif ($file->isDir() && $recursive) {
                    $this->removeDirectory($file->getPathname(), true, true);
                }
            }

            if ($removeRoot) {
                chmod($directory, 0777);
                $count = 0;
                while ($count <= self::TEST_MAX_TRY_COUNT) {
                    try {
                        rmdir($directory);
                        break;
                    } catch (Exception $e) {
                        $count++;
                        if ($count == self::TEST_MAX_TRY_COUNT) {
                            throw new Exception(
                                "Can't delete '" . $directory->getPathname() . "' with message ".$e->getMessage()
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Removes any existing sites (streams) and creates new depots
     * and 'live' streams based on the passed sites array.
     *
     * @param   array   $sites  sites to create.
     */
    public function saveSites($sites)
    {
        $p4     = $this->p4;
        $result = $p4->run('streams');
        foreach ($result->getData() as $stream) {
            // remove any clients of this stream so we can delete it
            $clients = $p4->run('clients', array('-S', $stream['Stream']));
            foreach ($clients->getData() as $client) {
                $p4->run('client', array('-d', '-f', $client['client']));
            }

            $p4->run('stream', array('-d', '-f', $stream['Stream']));
        }

        // we're done if no sites were passed; return early
        if (empty($sites)) {
            return;
        }

        // remember our original test client
        // (site->setConnection will change it below)
        $client = $p4->getClient();

        foreach ($sites as $id => $config) {
            preg_match('#^//([^/]+)/(.+)#', $id, $matches);
            $depot  = $matches[1];
            $stream = $matches[2];

            $input = array(
                'Depot'     => $depot,
                'Type'      => 'stream',
                'Map'       => $depot . '/...'
            );
            $result = $p4->run('depot', '-i', $input);

            $input = array(
                'Stream'    => $id,
                'Name'      => $stream,
                'Parent'    => 'none',
                'Type'      => 'mainline',
                'Owner'     => $p4->getUser(),
                'Paths'     => array('share ...')
            );
            $result = $p4->run('stream', '-i', $input);

            // write site branch config.
            $site = new P4Cms_Site;
            $site->setId($id)
                 ->setConnection($p4)
                 ->getConfig()
                 ->setValues($config)
                 ->save();
        }

        // force the disconnect callback(s) the site objects
        // added to run prior to setting back our client
        $p4->disconnect()->connect();

        // restore original client.
        $p4->setClient($client);
    }

    /**
     * Generate the test sites file.
     *
     * @todo    consider using setup controller's createSite() method
     *          to more faithfully mimic properly configured sites.
     */
    public function createTestSites()
    {
        P4Cms_Site::setSitesPackagesPath(TEST_SITES_PATH);
        P4Cms_Site::setSitesDataPath(TEST_DATA_PATH . '/sites');

        // save test sites.
        $this->saveSites($this->_testSites);

        // configure environment for first site.
        $firstSite              = reset($this->_testSites);
        $_SERVER['HTTP_HOST']   = $firstSite['urls'][0];
        $_SERVER['REQUEST_URI'] = '/';

        // create built-in system roles for first site.
        $site = P4Cms_Site::fetch(key($this->_testSites));
        $site->getConfig()
             ->setTitle('testsite')
             ->setDescription('description of the test site')
             ->save();
        $this->createSiteRoles($site);
    }

    /**
     * Create the built-in system roles (member and admin)
     *
     * @param P4Cms_Site    $site   site object
     */
    public function createSiteRoles($site)
    {
        $adapter = $site->getStorageAdapter();
        $user    = $this->getP4Params('user');

        // create the base site group and add system user to it.
        $siteGroup = new P4_Group($adapter->getConnection());
        $siteGroup->setId($adapter->getProperty(P4Cms_Acl_Role::PARENT_GROUP))
                  ->setUsers(array($user))
                  ->save();

        // create an administrator role
        $role = new P4Cms_Acl_Role;
        $role->setAdapter($adapter)
             ->setId(P4Cms_Acl_Role::ROLE_ADMINISTRATOR)
             ->setUsers(array($user))
             ->save();

        // create a member role
        $role = new P4Cms_Acl_Role;
        $role->setAdapter($adapter)
             ->setId(P4Cms_Acl_Role::ROLE_MEMBER)
             ->addOwner($user)
             ->setUsers(array($user))
             ->save();
    }

    /**
     * Remove all sites.
     */
    public function removeSites()
    {
        return $this->saveSites(array());
    }

    /**
     * Ensure that directories needed for testing exist.
     */
    public function createTestDirectories()
    {
        // remove existing directories to start fresh w. each test.
        $this->removeTestDirectories();

        extract($this->_p4Params);

        $directories = array(
            TEST_DATA_PATH,
            $serverRoot,
            $clientRoot,
            $clientRoot . '/superuser',
            $clientRoot . '/testuser',
            TEST_SESSION_SAVE_PATH,
            TEST_DATA_PATH . '/sites'
        );

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }
    }

    /**
     * Remove test directories (clean-up).
     */
    public function removeTestDirectories()
    {
        // remove the entire data directory since it is test-specific
        $this->removeDirectory(TEST_DATA_PATH);
    }

    /**
     * Open up permissions (possibly recursively) on a directory. All files
     * in the directory (including the directory itself) will be given a
     * permission mask of 0777. This method checks that the owner of the
     * running PHP process owns each file before it attempts to change
     * permissions on it.
     *
     * @param  string  $directory  the directory to change permissions on.
     * @param  bool    $recursive  optional - whether to do so recursively.
     */
    private function openPermissions($directory, $recursive = false)
    {
        $uid   = getmyuid();
        $files = new RecursiveDirectoryIterator($directory);

        foreach ($files as $file) {
            $stat = stat($file->getPathname());
            if ($stat['uid'] != $uid) continue; // skip files we don't own
            if (!chmod($file->getPathname(), 0777)) {
                throw new Exception(
                    "Can't set permissions on '" . $file->getPathname() . "'"
                );
            }
            if ($file->isDir() && $recursive) {
                if ($files->isDot()) {
                    continue;
                }
                $this->openPermissions($file->getPathname(), $recursive);
            }
        }

        chmod($directory, 0777);
    }

    /**
     * Setup the logger to direct to a file based on the class/method
     */
    public function setUpLogger()
    {
        if (!is_dir(dirname($this->_logFile))) {
            mkdir(dirname($this->_logFile), 0777, true);
        }
        if (is_file($this->_logFile)) {
            chmod($this->_logFile, 0777);
            unlink($this->_logFile);
        }

        $writer   = new Zend_Log_Writer_Stream($this->_logFile);
        $logger   = new Zend_Log($writer);
        P4_Log::setLogger($logger);
    }

    /**
     * If the test passed, remove the log
     *
     * @param  boolean  $failed  Indicates whether the recently completed test has failed.
     */
    public function tearDownLogger($failed)
    {
        P4_Log::getLogger()->__destruct();
        P4Cms_Log::getLogger()->__destruct();

        // Disable teardown for testing
        return;

        // if we didn't fail, remove the log file
        if (!$failed && is_file($this->_logFile)) {
            chmod($this->_logFile, 0777);
            unlink($this->_logFile);
        }
    }

    /**
     * Add core modules to the include path.
     * Some tests depend on availability of core module facilities.
     */
    public function initCoreModules()
    {
        P4Cms_Module::reset();

        // tell p4cms module where to find core modules.
        P4Cms_Module::setCoreModulesPath(APPLICATION_PATH);

        // init.
        $modules = P4Cms_Module::fetchAllCore();
        foreach ($modules as $module) {
            $module->init();
        }
    }

    /**
     * Dump variable content into a string within test context.
     *
     * @param  mixed  $var  variable to dump
     */
    public function dumper($var)
    {
        ob_start();
        var_dump($var);
        return ob_get_clean();
    }

    /**
     * Perform application bootstrap.
     *
     * @param   string|null              $environment   optional - The application environment to use
     * @param   string|array|Zend_Config $options       String path to bootstrap configuration file,
     *                                                  or array/Zend_Config of configuration options
     * @return  Zend_Application         the zend application instance we ran boostrap on
     */
    public function doBootstrap($environment = null, $options = null)
    {
        $application = new P4Cms_Application(
            $environment ?: APPLICATION_ENV,
            $options ?: array('resources' => array('perforce' => $this->_p4Params))
        );
        $application->bootstrap();

        // explicitly enable the stream wrapper so that when the tests
        // are run in a PHP with short tags disabled, the tests can
        // complete successfully.
        $view = Zend_Layout::getMvcInstance()->getView();
        $view->setUseStreamWrapper(true);

        // re-introduce sites/all/modules because $site->load()
        // of the test site will remove it in favor of mock modules
        P4Cms_Module::addPackagesPath(MODULE_PATH);

        return $application;
    }

    /**
     * Simulate post of a file input field where no file has been selected.
     *
     * @param  string  $field  The name for the file input form field.
     */
    public function simulateEmptyFileInput($field)
    {
        if (!is_array($_FILES)) {
            $_FILES = array();
        }

        $_FILES[$field] = array(
            'name'      => "",
            'type'      => null,
            'tmp_name'  => null,
            'error'     => UPLOAD_ERR_NO_FILE,
            'size'      => 0
        );
    }

    /**
     * Reset zend library components.
     */
    public function resetZend()
    {
        Zend_Registry::_unsetInstance();
        $front = Zend_Controller_Front::getInstance();
        $front->resetInstance();
        Zend_Layout::resetMvcInstance();
    }

    /**
     * Reset p4 library components.
     */
    public function resetP4()
    {
        // clear default connection
        if (class_exists('P4_Connection', false)) {
            P4_Connection::clearDefaultConnection();
        }

        // clear out shutdown callbacks
        if (class_exists('P4_Environment', false)) {
            P4_Environment::setShutdownCallbacks(null);
        }

        // clear logger.
        if (class_exists('P4_Log', false)) {
            P4_Log::setLogger(null);
        }
    }

    /**
     * Reset p4cms library components.
     */
    public function resetP4Cms()
    {
        // clear active user.
        if (class_exists('P4Cms_User', false)) {
            P4Cms_User::clearActive();
        }

        // reset theme.
        if (class_exists('P4Cms_Theme', false)) {
            P4Cms_Theme::reset();
        }

        // reset site component.
        if (class_exists('P4Cms_Site', false)) {
            P4Cms_Site::clearActive();
            P4Cms_Site::setSitesPackagesPath(null);
            P4Cms_Site::setSitesDataPath(null);
        }

        // clear pub/sub provider.
        if (class_exists('P4Cms_PubSub', false)) {
            P4Cms_PubSub::setInstance(new P4Cms_PubSub_Provider);
        }

        // reset modules.
        if (class_exists('P4Cms_Module', false)) {
            P4Cms_Module::reset();
        }

        // clear default adapter.
        if (class_exists('P4Cms_Record', false)) {
            P4Cms_Record::clearDefaultAdapter();
        }

        // clear logger.
        if (class_exists('P4Cms_Log', false)) {
            if (P4Cms_Log::hasLogger()) {
                $logger = P4Cms_Log::getLogger();
                $logger->__destruct();
            }

            P4Cms_Log::setLogger(null);
        }

        // clear any registered form plugin paths.
        if (class_exists('P4Cms_Form', false)) {
            P4Cms_Form::clearPrefixPathRegistry();
        }

        // clear cache of widget types
        if (class_exists('P4Cms_Widget_Type', false)) {
            P4Cms_Widget_Type::clearCache();
        }

        // clear active ACL.
        if (class_exists('P4Cms_Acl', false)) {
            P4Cms_Acl::setActive(null);
        }

        // clear loader packages.
        if (class_exists('P4Cms_Loader', false)) {
            P4Cms_Loader::setPackagePaths(array());
        }

        // clear static cache manager.
        if (class_exists('P4Cms_Loader', false)) {
            P4Cms_Cache::setManager(null);
        }
    }

    /**
     * Reset the application
     */
    public function resetApplication()
    {
        // purge the search instance from search module; if present
        if (class_exists('Search_Module', false)) {
            if (Search_Module::hasSearchInstance()) {
                $proxy           = Search_Module::factory();
                $proxyReflection = new ReflectionObject($proxy);
                $proxyIndex      = $proxyReflection->getProperty('_index');
                $proxyIndex->setAccessible(true);
                $index           = $proxyIndex->getValue($proxy);
                $index->__destruct();

                Search_Module::clearSearchInstances();
            }
        }

        // clear out auto-loader
        spl_autoload_unregister(array('P4Cms_Loader', 'autoload'));
    }

    /**
     * Perform setup of a library test.
     *
     * @param  PHPUnit_Test  $test  An instance of a not-yet-run test.
     */
    public function setUp($test)
    {
        $this->createTestDirectories();
        $this->setUpLogger();

        $test->p4 = $this->createP4Connection();
        $this->p4 = $test->p4;

        Zend_Session::$_unitTestEnabled = true;
    }

    /**
     * Perform setup of a module test
     *
     * @param   PHPUnit_Test             $test          An instance of a not-yet-run test.
     * @param   string|null              $environment   optional - The application environment to use
     * @param   string|array|Zend_Config $options       String path to bootstrap configuration file,
     *                                                  or array/Zend_Config of configuration options
     */
    public function setUpModuleTest($test, $environment = null, $options = null)
    {
        // add sites/all/modules to packages.
        P4Cms_Module::addPackagesPath(
            dirname(APPLICATION_PATH) . '/sites/all/modules'
        );

        $this->createTestSites();

        if ($test instanceof Zend_Test_PHPUnit_ControllerTestCase) {
            $utility = $this;
            $test->bootstrap = function() use ($utility, $environment, $options, $test)
            {
                $test->bootstrap = $utility->doBootstrap($environment, $options);
            };
        } else {
            $this->doBootstrap($environment, $options);
        }
    }

    /**
     * Perform tear down of a library test
     *
     * @param  PHPUnit_Test  $test  An instance of a just-completed test.
     */
    public function tearDown($test)
    {
        // call p4 library shutdown functions
        // (closes all p4 connections, cleans up temp specs)
        if (class_exists('P4_Environment', false)) {
            P4_Environment::runShutdownCallbacks();
        }

        // disconnect the P4 connection, if exists
        if (isset($test->p4)) $test->p4->disconnect();

        $this->tearDownLogger($test->hasFailed());
        $this->resetP4Cms();
        $this->resetP4();
        $this->resetZend();

        // forces collection of any existing garbage cycles
        // so no open file handles prevent files/directories
        // from being removed.
        gc_collect_cycles();

        $this->removeTestDirectories();
    }

    /**
     * Perform tear down of a module test
     *
     * @param  PHPUnit_Test  $test  An instance of a just-completed test.
     */
    public function tearDownModuleTest($test)
    {
        $this->resetApplication();
    }

    /**
     * Impersonate a logged-in user with the given role (e.g. member,
     * administrator, ...). Installs the default ACL and the named role.
     *
     * @param   string      $roleId     the id of the role to act as.
     * @param   P4Cms_Site  $site       the site to impersonate the role on
     *
     */
    public function impersonate($roleId, P4Cms_Site $site = null)
    {
        $site = $site ?: P4Cms_Site::fetchActive();
        $acl  = $site->getAcl();

        // if role is not anonymous (virtual) we need
        // to create it and authenticate a mock user
        if ($roleId != P4Cms_Acl_Role::ROLE_ANONYMOUS) {

            // create pretend user.
            $user = new P4Cms_User;
            $user->setId('mweiss')
                 ->setFullName('Michael T. Weiss')
                 ->setEmail('mweiss@thepretender.tv')
                 ->setPersonalAdapter(P4Cms_Record::getDefaultAdapter())
                 ->save();
            P4Cms_User::setActive($user);

            // assign user to named role.
            $role = new P4Cms_Acl_Role();
            $role->setId($roleId)
                 ->setUsers(array($user->getId()))
                 ->save();

            $auth = Zend_Auth::getInstance();
            $auth->authenticate($user);

            // update acl roles.
            $acl->setRoles(P4Cms_Acl_Role::fetchAll());

        }

        // update acl defaults now that named role exists.
        $acl->installDefaults();
    }
}
