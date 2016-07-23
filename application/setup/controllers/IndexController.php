<?php
/**
 * Setup/configure the application
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Setup_IndexController extends Zend_Controller_Action
{
    const       MIN_PHP_VERSION     = '5.3';
    const       MIN_P4_VERSION      = '2012.1';
    const       P4D_BINARY          = 'p4d';
    const       P4D_FLAGS           = '-ir';
    const       P4D_PORT            = 'rsh:';
    const       P4D_FOLDER          = 'perforce';
    const       P4D_USER            = 'chronicle';

    public      $contexts = array(
        'requirements'  => array('partial'),
        'create'        => array('json')
    );

    protected   $_adminP4           = null;
    protected   $_session           = null;

    /**
     * Use the setup layout and disable toolbar for all setup actions.
     */
    public function init()
    {
        $this->_helper->layout->setLayout('setup-layout');

        // never cache setup requests; they can be particularly problematic
        // on the first run of setup caching the root page.
        if (P4Cms_Cache::canCache('page')) {
            P4Cms_Cache::getCache('page')->cancel();
        }

        // list of actions that will be skipped from the permissions check
        $skipActions = array('rewrite', 'summary');

        // don't enforce permissions if setup is needed.
        // as there is only one privilege, we can do the permissions check for all
        // actions here - with the exception of actions listed in skipActions.
        if (in_array($this->getRequest()->getActionName(), $skipActions)
            || $this->getInvokeArg('bootstrap')->isSetupNeeded()
        ) {
            return;
        }

        // enforce permissions.
        $this->_helper->acl->check('site', 'add');
    }

    /**
     * Clear out completed setup data from session.
     */
    protected function _cleanupSession()
    {
        $session = $this->_getSession();
        if ($session->setupComplete) {
            $session->site          = null;
            $session->storage       = null;
            $session->administrator = null;
            $session->setupComplete = false;
        }
    }

    /**
     * Show setup splash page.
     */
    public function indexAction()
    {
        $this->_cleanupSession();

        // display splash page unless start is set.
        $request = $this->getRequest();
        if ($request->getParam('start')) {
            $this->_forward('requirements');
        } else {
            // build start url.
            $startUrl = $request->getBaseUrl();
            if ($this->_isRewriteWorking()) {
                $startUrl .= '/setup/start/yes';
            } else {
                $startUrl .= '?start=yes';
            }
            $this->view->startUrl = $startUrl;
        }
        $this->view->headTitle()->set('Setup');
    }

    /**
     * Start setup process by checking requirements.
     */
    public function requirementsAction()
    {
        $this->_cleanupSession();

        $this->view->headTitle()->set('Setup: Requirements');

        // check overall sanity.
        $this->view->isValidEnvironment     = $this->_isValidEnvironment();

        // check php requirement.
        $this->view->isPhpValid             = $this->_isPhpValid();
        $this->view->isPhpVersionValid      = $this->_isPhpVersionValid();
        $this->view->phpVersion             = PHP_VERSION;
        $this->view->minPhpVersion          = self::MIN_PHP_VERSION;
        $this->view->isMagicQuotesOn        = $this->_isMagicQuotesOn();

        // check mod-rewrite requirement.
        $this->view->isRewriteWorking       = $this->_isRewriteWorking();

        // check p4 requirement.
        $this->view->isP4Valid              = $this->_isP4Valid();
        $this->view->p4Version              = $this->_getP4Version();
        $this->view->minP4Version           = self::MIN_P4_VERSION;
        $this->view->isP4Installed          = $this->_isP4Installed();
        $this->view->p4ClientType           = $this->_p4ClientType();

        // check data directory.
        $this->view->isDataPathValid        = $this->_isDataPathValid();
        $this->view->isDataPathPresent      = $this->_isDataPathPresent();
        $this->view->isDataPathWritable     = $this->_isDataPathWritable();
        $this->view->dataPath               = DATA_PATH;

        // check the Perforce extension
        $this->view->isP4PHPInstalled       = extension_loaded('perforce');

        // check the Opcode Cache.
        $this->view->isWinCacheInstalled    = extension_loaded('wincache');
        $this->view->isApcInstalled         = extension_loaded('apc');
        if (P4_Environment::isWindows() && isset($_SERVER['SERVER_SOFTWARE'])) {
            $this->view->isWebServerIis     = stripos($_SERVER['SERVER_SOFTWARE'], "Microsoft-IIS") !== false;
        }

        // check for image manipulation availability
        $this->view->imageExtensions        = array();
        $this->view->imageExtensionsEnabled = array();
        foreach (P4Cms_Image_Driver_Factory::getDriverClasses() as $driverClass) {
            $extension = $driverClass::getRequiredExtension();
            if (!$extension) {
                continue;
            }
            $this->view->imageExtensions[] = $extension;
            if (extension_loaded($extension)) {
                $this->view->imageExtensionsEnabled[] = $extension;
            }
        }

        // check for common image types support for the default driver
        try {
            $defaultDriver = P4Cms_Image_Driver_Factory::create();
        } catch (P4Cms_Image_Exception $e) {
            // no driver available
            $defaultDriver = null;
        }
        $commonTypes                         = array('jpeg', 'png', 'gif');
        $this->view->defaultImageDriver      = $defaultDriver;
        $this->view->missingCommonImageTypes = $defaultDriver
            ? array_diff($commonTypes, array_filter($commonTypes, array($defaultDriver, 'isSupportedType')))
            : array();

        // save the username/group for the web server if available
        $webServerDetails = '';
        if (function_exists("posix_geteuid")
            && function_exists("posix_getpwuid")
            && function_exists("posix_getgrgid")
        ) {
            $userInfo  = posix_getpwuid(posix_geteuid());
            $userName  = $userInfo['name'];
            $groupInfo = posix_getgrgid($userInfo['gid']);
            $groupName = $groupInfo['name'];
            $webServerDetails = " (username \"$userName\", group \"$groupName\")";
        }
        $this->view->webServerDetails = $webServerDetails;
    }

    /**
     * Simple action exists only to be requested to test if rewrite is working.
     * Responds with a checksum of this file.
     */
    public function rewriteAction()
    {
        print(md5_file(__FILE__));
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
    }

    /**
     * Obtain Perforce server information.
     */
    public function storageAction()
    {
        // if requirements not met, return to requirements step.
        if (!$this->_isValidEnvironment()) {
            $this->redirector->gotoSimple('requirements');
            return;
        }

        // setup view.
        $form                       = new Setup_Form_Storage;
        $this->view->form           = $form;
        $this->view->isP4dInstalled = $form->isP4dInstalled();
        $this->view->isP4dValid     = $form->isP4dValid();
        $this->view->minP4Version   = self::MIN_P4_VERSION;
        $this->view->headTitle()->set('Setup: Site Storage');

        // if we have a previously configured perforce connection, setup
        // the form to always use it (regardless of request paramaters)
        $request  = $this->getRequest();
        $perforce = $this->getInvokeArg('bootstrap')->getResource('perforce');
        if ($perforce) {
            $form->getElement('serverType')
                 ->setAttrib('disabled', true)
                 ->setValue($form::SERVER_TYPE_EXISTING);

            $form->getElement('port')
                 ->setAttrib('disabled', true)
                 ->setValue($perforce->getPort())
                 ->setDescription('You have already configured a Perforce Server.');

            $request->setPost('serverType', $form::SERVER_TYPE_EXISTING)
                    ->setPost('port',       $perforce->getPort());
        }

        // if form has been posted and is valid, save values
        // to session and proceed to administrator form.
        if ($request->isPost() && $form->isValid($request->getPost())) {
            $session          = $this->_getSession();
            $session->storage = $form->getValues();
            if ($request->getParam('goback')) {
                $this->redirector->gotoSimple('requirements');
                return;
            }
            $this->redirector->gotoSimple('administrator');
            return;
        } elseif ($request->isPost()) {
            if ($request->getParam('goback')) {
                $this->redirector->gotoSimple('requirements');
                return;
            }
            $count = count($form->getMessages());
            $s = ($count == 1) ? '' : 's';
            P4Cms_Notifications::add("$count field$s failed validation.", P4Cms_Notifications::SEVERITY_ERROR);
        }

        // if serverType=new, disable port/address field
        if ($form->getValue('serverType') == $form::SERVER_TYPE_NEW) {
            $group = $form->getDisplayGroup('existingServer');
            $group->setAttrib('class', $group->getAttrib('class') . ' disabled');
        }

        // if we have a previously configured 'rsh' perforce server,
        // pretty-up the port value to hide 'rsh' details.
        if ($perforce && $this->_isRshServer($perforce)) {
            $form->getElement('port')
                 ->setValue($this->_getFriendlyPort($perforce))
                 ->setLabel('Local Server');
        }
    }

    /**
     * Obtain server administrator information.
     */
    public function administratorAction()
    {
        // if requirements not met, return to requirements step.
        if (!$this->_isValidEnvironment()) {
            $this->redirector->gotoSimple('requirements');
            return;
        }

        // If perforce server is invalid - return to storage action.
        $storageForm = new Setup_Form_Storage;
        $storageForm->setCsrfProtection(false);         // trusted source, disable CRSF so isValid works
        $session = $this->_getSession();
        if (!is_array($session->storage) ||
            !$storageForm->isValid($session->storage)) {
            $this->redirector->gotoSimple('storage');
            return;
        }

        $this->_cleanupSession();

        // setup view.
        $perforce    = $this->getInvokeArg('bootstrap')->getResource('perforce');
        $view        = $this->view;
        $view->port  = $perforce ? $this->_getFriendlyPort($perforce) : $session->storage['port'];
        $view->isRsh = $perforce ? $this->_isRshServer($perforce) : false;

        if (!$perforce && $storageForm->getValue('serverType') !== $storageForm::SERVER_TYPE_NEW) {
            // note: auto user creation does not appear to be triggered for the info command (which
            // P4_Connection::hasExternalAuth() uses.) but should that change, we create a
            // highly-unlikely username for the connection test.
            $username = md5(mt_rand());
            $p4       = P4_Connection::factory($session->storage['port'], $username);
            $session->storage['hasExternalAuth'] = $p4->hasExternalAuth();
        }

        // setup form.
        $form = new Setup_Form_Administrator(
            array(
                'serverType'      => $storageForm->getValue('serverType'),
                'p4Port'          => $session->storage['port'],
                'hasExternalAuth' => isset($session->storage['hasExternalAuth'])
                                   ? $session->storage['hasExternalAuth'] : false
            )
        );

        $view->form  = $form;
        $view->headTitle()->set('Setup: Administrator');

        // if form has been posted and is valid, save values
        // to session and proceed to site form.
        $request = $this->getRequest();
        if ($request->isPost() && $form->isValid($request->getPost())) {
            $session                = $this->_getSession();
            $session->administrator = $form->getValues();
            if ($request->getParam('goback')) {
                $this->redirector->gotoSimple('storage');
                return;
            }
            $this->redirector->gotoSimple('site');
            return;
        } elseif ($request->isPost()) {
            if ($request->getParam('goback')) {
                $this->redirector->gotoSimple('storage');
                return;
            }
            $count = count($form->getMessages());
            $s = ($count == 1) ? '' : 's';
            P4Cms_Notifications::add("$count field$s failed validation.", P4Cms_Notifications::SEVERITY_ERROR);
        }

    }

    /**
     * Setup a site definition.
     *
     * Both of the optional paramaters are intended for use by the 'create' action.
     *
     * @param   bool    $skipRedirect   optional - if true skips trying to redirect backward
     *                                  for failed requirements and simply returns.
     * @param   bool    $optionalUrls   optional - if true the 'urls' field of the site form
     *                                  isn't required.
     */
    public function siteAction($skipRedirect = false, $optionalUrls = false)
    {
        // if requirements not met, return to requirements step.
        if (!$this->_isValidEnvironment()) {
            $this->view->step    = 'environment';
            $this->view->isValid = false;
            $this->view->errors  = array('form' => array('One or more requirements are not met.'));

            $skipRedirect ?: $this->redirector->gotoSimple('requirements');
            return;
        }

        // if perforce server is invalid - return to storage action.
        $storageForm  = new Setup_Form_Storage;
        $storageForm->setCsrfProtection(false);         // trusted source, disable CRSF so isValid works
        $session = $this->_getSession();
        if (!$storageForm->isValid((array) $session->storage)) {
            $this->view->step    = 'storage';
            $this->view->isValid = false;
            $this->view->form    = $storageForm;

            $skipRedirect ?: $this->redirector->gotoSimple('storage');
            return;
        }

        // if administrator credentials are invalid, return to administrator action
        $options   = array(
            'p4Port'          => $session->storage['port'],
            'serverType'      => $session->storage['serverType'],
            'hasExternalAuth' => isset($session->storage['hasExternalAuth'])
                               ? $session->storage['hasExternalAuth'] : false
        );
        $adminForm = new Setup_Form_Administrator($options);
        $adminForm->setCsrfProtection(false);   // trusted source, disable CRSF so isValid works
        if (!$adminForm->isValid((array) $session->administrator)) {
            $this->view->step    = 'administrator';
            $this->view->isValid = false;
            $this->view->form    = $adminForm;

            $skipRedirect ?: $this->redirector->gotoSimple('administrator');
            return;
        }

        // set the page title
        $view = $this->view;
        $view->headTitle()->set('Setup: Site');

        // prepare the site form - if we are adding a site to an existing
        // perforce server, we need a connection to the server so that the
        // form can check if the site title is taken.
        $form       = new Setup_Form_Site;
        $view->form = $form;
        $bootstrap  = $this->getInvokeArg('bootstrap');
        if ($bootstrap->hasResource('perforce')
            || $storageForm->getValue('serverType') !== $storageForm::SERVER_TYPE_NEW
        ) {
            $form->setConnection(
                $this->_getAdminConnection($storageForm, $adminForm)
            );
        }

        // make urls optional if requested by caller
        // this is intended for API driven create
        if ($optionalUrls) {
            $form->getElement('urls')->setRequired(false);
        }

        // if form has been posted and is valid, create site.
        $request = $this->getRequest();
        if ($request->isPost() && $form->isValid($request->getPost())) {
            if ($request->getParam('goback')) {
                $this->redirector->gotoSimple('administrator');
                return;
            }

            $site = $this->_createSite($form, $storageForm, $adminForm);

            // as we have created a new site, we need to clear the site cache.
            P4Cms_Cache::remove(P4Cms_Site::CACHE_KEY, 'global');

            $session->site          = $site;
            $session->setupComplete = true;

            $this->view->step       = 'completed';
            $this->view->isValid    = true;

            $skipRedirect ?: $this->redirector->gotoSimple('summary');
        } elseif ($request->isPost()) {
            if ($request->getParam('goback')) {
                $this->redirector->gotoSimple('administrator');
                return;
            }

            $this->view->step    = 'site';
            $this->view->isValid = false;
            $this->view->form    = $form;

            $count = count($form->getMessages());
            $s = ($count == 1) ? '' : 's';
            P4Cms_Notifications::add("$count field$s failed validation.", P4Cms_Notifications::SEVERITY_ERROR);
        }
    }

    /**
     * This action allows api's to make a single post to create a new site.
     *
     * You should be able to succesfully add a site with the following post:
     *  storage[serverType]=new
     *  administrator[user]=<valid-user>
     *  administrator[email]=<valid-email>
     *  administrator[password]=<valid-password>
     *  administrator[passwordConfirm]=<valid-password>
     *  site[title]=<valid-title>
     *
     * To use an existing perforce server replace the storage line with:
     *  storage[serverType]=existing
     *  storage[port]=perforce:1666
     *
     * A json response will be returned with the following data:
     *  step    = environment, storage, administrator, site or completed
     *  isValid = true or false
     *  errors  = may contain a form key with an array of strings and/or
     *            an elements key which contains error arrays indexed by
     *            element id. value(s) can be ignored if isValid is true.
     *
     * Note: the site[urls] field is only optional for the first site
     *       if any subsequent sites are added this field must be included.
     */
    public function createAction()
    {
        // force a json context
        $this->contextSwitch->initContext('json');

        // administrator (username, email, password, confirm)
        // site-storage  (new or old radio and address)
        // site          (title/address/description)

        $request = $this->getRequest();
        $session = $this->_getSession();

        $session->administrator = $request->getPost('administrator');
        $session->storage       = $request->getPost('storage');

        // ensure the request only contains the values of site
        $site = (array) $request->getPost('site');
        $request->setPost($site + array('administrator' => '', 'storage' => '', 'site' => ''));

        // call through to the site action passing true to
        // ensure it won't redirect and our initial setup
        // status to determine if urls are optional or not.
        $this->siteAction(true, $this->_isInitalSetup());
    }

    /**
     * Summarize site setup - clear site from session.
     */
    public function summaryAction()
    {
        // if requirements not met, return to requirements step.
        if (!$this->_isValidEnvironment()) {
            $this->redirector->gotoSimple('requirements');
            return;
        }

        // if no site data in session, redirect to site creation.
        $session = $this->_getSession();
        if (!isset($session->site)) {
            $this->redirector->gotoSimple('site');
            return;
        }

        // setup view data.
        $view           = $this->view;
        $perforce       = $this->getInvokeArg('bootstrap')->getResource('perforce');
        $view->port     = $perforce ? $this->_getFriendlyPort($perforce) : $session->storage['port'];
        $view->isRsh    = $perforce ? $this->_isRshServer($perforce) : false;
        $view->site     = $session->site;
        $view->storage  = $session->storage;
        $view->admin    = $session->administrator;
        $view->headTitle()->set('Setup: Summary');
    }

    /**
     * Get a connection to the target Perforce Server as an administrator.
     * This method will not return a connection to a new local server; that
     * is the responsibility of _createLocalServer().
     *
     * @param   Zend_Form   $storageForm    the form with the target server port.
     * @param   Zend_Form   $adminForm      the form containing admin credentials.
     * @return  P4_Connection_Interface     an admin connection to the target server
     */
    protected function _getAdminConnection($storageForm, $adminForm)
    {
        // if we already have prepared an admin connection, re-use it.
        if ($this->_adminP4) {
            return $this->_adminP4;
        }

        // if we have a known perforce server use it,
        // otherwise use the port passed in via storageForm
        $bootstrap = $this->getInvokeArg('bootstrap');
        $port      = $bootstrap->hasResource('perforce')
            ? $bootstrap->getResource('perforce')->getPort()
            : $storageForm->getValue('port');

        $adminP4 = P4_Connection::factory(
            $port,
            $adminForm->getValue('user'),
            null,
            $adminForm->getValue('password')
        );
        $adminP4->login();

        return $adminP4;
    }

    /**
     * Checks if the current environment meets our requirements.
     *
     * @return  boolean     true if the environment meets requirements, false otherwise.
     */
    private function _isValidEnvironment()
    {
        if (!$this->_isPhpValid()) {
            return false;
        }
        if (!$this->_isRewriteWorking()) {
            return false;
        }
        if (!$this->_isP4Valid()) {
            return false;
        }
        if (!$this->_isDataPathValid()) {
            return false;
        }
        return true;
    }

    /**
     * Checks if the current version of PHP meets the minimum requirement and
     * magic quotes are disabled.
     *
     * @return  boolean     true if PHP meets the requirements, false otherwise.
     */
    private function _isPhpValid()
    {
        // check the version of php.
        if (!$this->_isPHPVersionValid()) {
            return false;
        }

        // check magic quotes.
        if ($this->_isMagicQuotesOn()) {
            return false;
        }

        return true;
    }


    /**
     * Checks if the current version of PHP meets the minimum requirement.
     *
     * @return  boolean     true if PHP meets the requirement, false otherwise.
     */
    private function _isPhpVersionValid()
    {
        if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION) >= 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if magic quotes gpc or runtime are enabled.
     *
     * @return  boolean     true if magic_quotes_gpc or magic_quotes_runtime are on.
     */
    private function _isMagicQuotesOn()
    {
        if (get_magic_quotes_gpc() || get_magic_quotes_runtime()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if mod rewrite is enabled and configured correctly.
     *
     * @return  boolean     true if mod-rewrite is enabled and configured.
     */
    private function _isRewriteWorking()
    {
        // make http request that requires rewrite.
        $request = $this->getRequest();
        $address = $request->getScheme() . "://" . $request->getHttpHost() .
            $request->getBaseUrl() . "/setup/index/rewrite";
        $result  = @file_get_contents($address);

        // verify that response matches md5 of this file.
        if (trim($result) == md5_file(__FILE__)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if the minimum version of p4 is installed in the web server's path.
     *
     * @return  boolean     true if a 'p4' is installed, false otherwise.
     */
    private function _isP4Valid()
    {
        $p4Version  = strtolower($this->_getP4Version());
        $minVersion = strtolower(self::MIN_P4_VERSION);
        if (version_compare($p4Version, $minVersion) >= 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the version of the Perforce client library that is installed.
     *
     * @return  string  the version component of the client identity.
     */
    private function _getP4Version()
    {
        try {
            $identity = P4_Connection::getConnectionIdentity();
            return $identity['version'];
        } catch (P4_Exception $e) {
            return false;
        }
    }

    /**
     * Check if the Perforce client library is installed.
     *
     * @return  boolean     true if p4 is installed.
     */
    private function _isP4Installed()
    {
        try {
            $identity = P4_Connection::getConnectionIdentity();
            return true;
        } catch (P4_Exception $e) {
            return false;
        }
    }

    /**
     * Determine what Perforce client type is in use.
     *
     * @return  string  description of the client type
     */
    public function _p4ClientType()
    {
        switch (get_class(P4_Connection::getDefaultConnection())) {
            case "P4_Connection_CommandLine":
                $type = 'Perforce command-line client, P4';
                break;
            case "P4_Connection_Extension":
                $type = 'Perforce PHP extension, P4PHP';
                break;
            default:
                $type = '(unknown client)';
                break;
        }

        return $type;
    }

    /**
     * Checks if the data path exists and is writable.
     *
     * @return  boolean     true if the data path exists and writable, false otherwise.
     */
    private function _isDataPathValid()
    {
        if ($this->_isDataPathPresent() && $this->_isDataPathWritable()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if the data directory exists.
     *
     * @return  boolean     true if data directory exists, false otherwise.
     */
    private function _isDataPathPresent()
    {
        if (is_dir(DATA_PATH)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if the data directory is writable.
     *
     * @return  boolean     true if data directory is writable, false otherwise.
     */
    private function _isDataPathWritable()
    {
        if (!is_writable(DATA_PATH)) {
            if (!@chmod(DATA_PATH, 0755)) {
                return false;
            }
        }

        // if we need to configure perforce, we must also
        // verify that the application config file is writable
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasResource('perforce')) {
            $file = $bootstrap->getApplication()->getConfigFile();
            if (file_exists($file) && !is_writable($file) && !@chmod($file, 0755)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a site in Perforce and save it to the local sites list.
     *
     * Site creation entails several changes in Perforce. A new depot, user, client,
     * several groups and the configuration of protections to grant read/write access
     * to the depot for the site groups and their members.
     *
     * @param   Setup_Form_Site             $siteForm       site title/urls collected from user.
     * @param   Setup_Form_Storage          $storageForm    target perforce server information.
     * @param   Setup_Form_Administrator    $adminForm      target administrator information.
     *
     * @publishes   p4cms.site.created
     *              Perform operations when a site is created by the Site Module.
     *              P4Cms_Site  $site   The site that has been created.
     *              P4Cms_User  $admin  The administrator account used for creation.
     */
    private function _createSite($siteForm, $storageForm, $adminForm)
    {
        // if the application has not yet been configured to use a specific
        // perforce server, we will need to write to the application config file
        // if there is already an application config file (unlikely) we want
        // to make sure it is valid before we get any further, so we set it up now.
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasResource('perforce')) {
            $configFile = $bootstrap->getApplication()->getConfigFile();
            $config     = file_exists($configFile)
                ? new Zend_Config_Ini($configFile, null, array('allowModifications' => true))
                : new Zend_Config(array(), true);
        }

        // create a writable sites folder if we don't already have one.
        P4Cms_FileUtility::createWritablePath(DATA_PATH . '/sites');

        // connect to the target perforce server.
        // a new (local) server will be created if we don't already have one
        if ($bootstrap->hasResource('perforce')
            || $storageForm->getValue('serverType') === $storageForm::SERVER_TYPE_EXISTING
        ) {
            $adminP4 = $this->_getAdminConnection($storageForm, $adminForm);
        } else {
            $root    = DATA_PATH . '/' . self::P4D_FOLDER;
            $adminP4 = $this->_createLocalServer($root);

            // add new server info to session.
            $session            = $this->_getSession();
            $session->storage   = array_merge(
                $session->storage,
                array(
                    'root'  => $root,
                    'port'  => $adminP4->getPort(),
                )
            );
            $session->administrator = array_merge(
                $session->administrator,
                array(
                    'user'      => $adminP4->getUser(),
                    'password'  => $adminP4->getPassword()
                )
            );
        }

        // if the application has not yet been configured to use a specific
        // perforce server, we need to create a dedicated 'chronicle' user
        // and save the connection information to the application config file.
        $session = $this->_getSession();
        if (!$bootstrap->hasResource('perforce')) {
            $user     = new P4_User($adminP4);
            $user->setId(static::P4D_USER)
                ->setFullName(static::P4D_USER)
                ->setEmail(static::P4D_USER);

            // do not set the password if we are connected to a P4 server
            // using external authentication
            if (!isset($session->storage['hasExternalAuth']) || !$session->storage['hasExternalAuth']) {
                $password = P4Cms_User::generatePassword(10, 3);
                $user->setPassword($password);
            } else if (isset($session->administrator['systemPassword'])) {
                $session  = $this->_getSession();
                $password = $session->administrator['systemPassword'];
            }
            $user->save();

            // perforce config should be shared by all environments by default.
            // prime config object to contain the default sections and inheritance.
            $config->all            = $config->get('all',         array());
            $config->all->resources = $config->all->resources ?:  array();
            $config->production     = $config->get('production',  array());
            $config->development    = $config->get('development', array());
            $config->setExtend('production',  'all');
            $config->setExtend('development', 'all');

            // create a system connection for later use
            $systemP4 = P4_Connection::factory($adminP4->getPort(), $user->getId(), null, $password);
            $systemP4->login();

            // add perforce connection information to the config file.
            $config->all->resources->perforce = array(
                'port'      => $adminP4->getPort(),
                'user'      => $user->getId(),
                'password'  => $password
            );

            // write it out.
            $writer = new Zend_Config_Writer_Ini;
            $writer->write($configFile, $config);
        } else {
            // get a copy of the system's connection for later use
            $systemP4 = $bootstrap->getResource('perforce');
        }

        // create site-specific depot (each site relates 1:1 with a depot)
        $depot = new P4_Depot($adminP4);
        $depot->setId($siteForm->getValue('id'))
              ->setOwner($systemP4->getUser())
              ->setType('stream')
              ->setMap($siteForm->getValue('id') . '/...')
              ->setDescription('Chronicle depot for ' . $siteForm->getValue('title') . ' site.')
              ->save();

        // disconnect and reconnect to avoid a p4 bug where
        // its not possible to make a new depot and map it
        // into a client spec on the same connection.
        $adminP4->disconnect()->connect();
        $systemP4->disconnect()->connect();

        // create a new site branch object setting just the id.
        // we don't set anything that is stored on the config record
        // as it is too early to read/write the config at this time.
        $site = new P4Cms_Site;
        $site->setId('//' . $depot->getId() . '/' . P4Cms_Site::DEFAULT_BRANCH);

        // create local writable folders for site data.
        P4Cms_FileUtility::createWritablePath($site->getDataPath());
        P4Cms_FileUtility::createWritablePath($site->getWorkspacesPath());

        // create the site stream (each site branch relates 1:1 with a stream)
        $stream = new P4_Stream($adminP4);
        $stream->setId($site->getId())
               ->setName(ucfirst($site->getBranchBasename()))
               ->setParent('none')
               ->setType('mainline')
               ->setOwner($systemP4->getUser())
               ->setPaths('share ...')
               ->save();

        // ensure the site is using the system connection; this has
        // to happen afer we create the site's stream above because
        // set connection will use the stream to create a temp client
        $site->setConnection($systemP4);

        // fetch system administrator user and load personal adapter
        // we will use the administrator's adapter to setup the roles.
        $admin = P4Cms_User::fetch($adminP4->getUser(), null, $site->getStorageAdapter());
        $admin->setPersonalAdapter(
            $admin->createPersonalAdapter($adminP4->getTicket(), $site)
        );

        // create default site roles
        $this->_createSiteRoles($site, $admin);

        // now that the perforce connection and permissions are established
        // for the site branch, we can access records and configure it.
        $site->getConfig()
             ->setTitle($siteForm->getValue('title'))
             ->setDescription($siteForm->getValue('description'))
             ->setUrls($siteForm->getValue('urls'))
             ->setTheme(P4Cms_Theme::DEFAULT_THEME)
             ->save();

        // temporarily swap out the active site (if there is one) and load
        // the new site. loading the site configures the package system to
        // look in the correct file-system paths for modules/themes and sets
        // the default adapter/connection so that naive reads/writes hit
        // the correct storage location.
        $activeSite = P4Cms_Site::hasActive() ? P4Cms_Site::fetchActive() : null;
        $site->load();

        // find optional modules that should be enabled by default.
        $modules = P4Cms_Module::fetchAllDisabled();
        foreach ($modules as $module) {
            if ($module->getPackageInfo('enableByDefault')) {
                $module->enable();
            }
        }

        // notify subscribers of site creation event.
        P4Cms_PubSub::publish('p4cms.site.created', $site, $admin);

        // restore the active site.
        if ($activeSite) {
            $activeSite->load();
        }

        return $site;
    }

    /**
     * Create user roles for the given site and alter protections table.
     *
     * By default, following roles are created:
     *
     *   member             gather all members of this site - having this role
     *                      is required for ability to log into the cms
     *   administrator      gather all site administrators (having this role
     *                      automatically implies super user privileges in Perforce)
     *
     * Additionally, a site group (not a role) is created to act as a parent
     * for all site roles so that they inherit its permissions. The site group
     * is given read/write access to all of the files in the site depot (with
     * the exception of the acl file which is read-only). The system user is
     * configured as the sole user in the site group so that it can have these
     * permissions, but not actually appear in any roles. The system user is
     * made the owner of the 'member' group so that the system can add new
     * users as members.
     *
     * @param P4Cms_Site    $site       site object
     * @param P4Cms_User    $admin      site administrator user
     */
    private function _createSiteRoles($site, $admin)
    {
        $adapter    = $admin->getPersonalAdapter();
        $systemUser = P4Cms_User::fetch($site->getConnection()->getUser(), null, $adapter);

        // create the base site group and add system user to it.
        $siteGroup = new P4_Group($adapter->getConnection());
        $siteGroup->setId($adapter->getProperty(P4Cms_Acl_Role::PARENT_GROUP))
                  ->setUsers(array($systemUser->getId()))
                  ->save();

        // create the administrator role
        $admins = new P4Cms_Acl_Role;
        $admins->setAdapter($adapter)
               ->setId(P4Cms_Acl_Role::ROLE_ADMINISTRATOR)
               ->setUsers(array($admin))
               ->save();

        // create the member role
        $members = new P4Cms_Acl_Role;
        $members->setAdapter($adapter)
                ->setId(P4Cms_Acl_Role::ROLE_MEMBER)
                ->addOwner($systemUser)
                ->save();

        // determine the group id prefix used for this site.
        $prefix = $siteGroup->getId() . P4Cms_Acl_Role::PREFIX_DELIMITER;

        // alter protections to grant write access for members
        // and super access for administrators (for site depot)
        $depotMap = dirname($site->getId()) . '/...';
        P4_Protections::fetch($adapter->getConnection())
            ->addProtection('write',  'group', $siteGroup->getId(),        '*', $depotMap)
            ->addProtection('review', 'group', $siteGroup->getId(),        '*', $depotMap)
            ->addProtection('super',  'group', $prefix . $admins->getId(), '*', $depotMap)
            ->save();
    }

    /**
     * Get the session namespace object for this setup session.
     *
     * @return  Zend_Session_Namespace  persisted data for this setup session.
     */
    private function _getSession()
    {
        if (!isset($this->_session)) {
            $this->_session = new Zend_Session_Namespace('setup');

            // our setup session data shouldn't influence page caching
            if (P4Cms_Cache::canCache('page')) {
                P4Cms_Cache::getCache('page')->addIgnoredSessionVariable('setup');
            }
        }

        return $this->_session;
    }

    /**
     * Setup a local perforce depot in the given path.
     * Create an administrator user with supplied password.
     *
     * @param   string  $path               the p4 root folder.
     * @return  P4_Connection_Interface     a connection to the new depot.
     */
    private function _createLocalServer($path)
    {
        // make target p4 root folder.
        P4Cms_FileUtility::createWritablePath($path);

        // generate p4 port for inetd/rsh mode (escape spaces in path).
        $p4Port = self::P4D_PORT . self::P4D_BINARY . ' '
                . self::P4D_FLAGS . ' ' . str_replace(' ', '\ ', $path);

        // connect to p4d as 'admin' user.
        $session  = $this->_getSession();
        $username = $session->administrator['user'];
        $email    = $session->administrator['email'];
        $p4 = P4_Connection::factory($p4Port, $username);

        // generate password for admin user. This is only used prior to setting the
        // security level; afterwards we'll use the user-supplied password.
        $password = P4Cms_User::generatePassword(10, 3);

        // create admin user.
        $user = new P4_User($p4);
        $user->setId($username)
             ->setFullName($username)
             ->setEmail($email)
             ->setPassword($password)
             ->save();

        // set server security level to 2
        $counter = new P4_Counter($p4);
        $counter->setId('security');
        $counter->setValue(2, true);

        // update the password for this connection.
        $p4->run(
            'password',
            null,
            array(
                $password,
                $session->administrator['password'],
                $session->administrator['password']
            )
        );

        // authenticate
        $p4->setPassword($session->administrator['password'])
           ->login();

        // disable server-locks - server locks are not needed for our
        // workflow and the locks directory will grow out of control
        $p4->run('configure', array('set', 'server.locks.dir=disabled'));

        return $p4;
    }

    /**
     * Check if setup is running for the first time.
     * We consider it the 'maiden voyage' if perforce is not yet configured.
     *
     * @return  bool    true if it is the first setup
     */
    protected function _isInitalSetup()
    {
        return !$this->getInvokeArg('bootstrap')->hasResource('perforce');
    }

    /**
     * Determines if given connection is to a local 'rsh' server.
     *
     * @param   P4_Connection_Interface     $connection     connection to examine.
     * @return  bool                        true if connection uses rsh; false otherwise.
     */
    protected function _isRshServer(P4_Connection_Interface $connection)
    {
        return strpos($connection->getPort(), 'rsh:') === 0;
    }

    /**
     * Present rsh ports as 'Local Server: /path/to/server/root'.
     * Remote server ports are returned as-is.
     *
     * @param   P4_Connection_Interface     $connection     connection to pretty-up port of.
     * @return  string                      the friendly port
     */
    protected function _getFriendlyPort(P4_Connection_Interface $connection)
    {
        if ($this->_isRshServer($connection)) {
            $info = $connection->getInfo();
            return $info['serverRoot'];
        }

        return $connection->getPort();
    }
}
