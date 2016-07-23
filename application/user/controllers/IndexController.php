<?php
/**
 * Manages user operations (e.g. login/logout).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class User_IndexController extends Zend_Controller_Action
{
    const   LOGIN_FAILED_MIN_DELAY  = 1000000;
    const   LOGIN_FAILED_MAX_DELAY  = 2000000;

    public $contexts = array(
        'login'     => array('partial', 'json'),
        'add'       => array('partial', 'json'),
        'edit'      => array('partial', 'json'),
        'index'     => array('json'),
        'delete'    => array('json'),
    );

    /**
     * Use management layout for all actions.
     */
    public function init()
    {
        $this->_helper->layout->setLayout('manage-layout');
    }

    /**
     * List all users.
     *
     * @publishes   p4cms.user.grid.actions
     *              Modify the passed menu (add/modify/delete items) to influence the actions shown
     *              on entries in the Manage Users grid.
     *              P4Cms_Navigation            $actions    A menu to hold grid actions.
     *
     * @publishes   p4cms.user.grid.data.item
     *              Return the passed item after applying any modifications (add properties, change
     *              values, etc.) to influence the row values sent to the Manage Users grid.
     *              array                       $item       The item to potentially modify.
     *              mixed                       $model      The original object/array that was used
     *                                                      to make the item.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.user.grid.data
     *              Adjust the passed data (add properties, modify values, etc.) to influence the
     *              row values sent to the Manage Users grid.
     *              Zend_Dojo_Data              $data       The data to be filtered.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.user.grid.populate
     *              Adjust the passed iterator (possibly based on values in the passed form) to
     *              filter which users will be shown on the Manage Users grid.
     *              P4Cms_Model_Iterator    $users          An Iterator of User_Model_User objects.
     *              P4Cms_Form_PubSubForm   $form           A form containing filter options.
     *
     * @publishes   p4cms.user.grid.render
     *              Make adjustments to the datagrid helper's options pre-render (e.g. change
     *              options to add columns) for the Manage Users grid.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.user.grid.form
     *              Make arbitrary modifications to the Manage Users filters form.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.user.grid.form.subForms
     *              Return a Form (or array of Forms) to have them added to the Manage Users filters
     *              form. The returned form(s) should have a 'name' set on them to allow them to be
     *              uniquely identified.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.user.grid.form.preValidate
     *              Allows subscribers to adjust the Manage Users filters form prior to
     *              validation of the passed data. For example, modify element values based on
     *              related selections to permit proper validation.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.user.grid.form.validate
     *              Return false to indicate the Manage Users filters form is invalid. Return true
     *              to indicate your custom checks were satisfied, so form validity should be
     *              unchanged.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.user.grid.form.populate
     *              Allows subscribers to adjust the Manage Users filters form after it has been
     *              populated with the passed data.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     The values passed to the populate
     *                                                      method.
     */
    public function indexAction()
    {
        // enforce permissions
        $this->acl->check('users', 'manage');

        // setup list options form.
        $request        = $this->getRequest();
        $gridNamespace  = 'p4cms.user.grid';
        $form           = new Ui_Form_GridOptions(
            array(
                'namespace'   => $gridNamespace
            )
        );
        $form->populate($request->getParams());

        // setup view.
        $view               = $this->view;
        $view->form         = $form;
        $view->pageSize     = $request->getParam('count', 100);
        $view->rowOffset    = $request->getParam('start', 0);
        $view->pageOffset   = round($view->rowOffset / $view->pageSize, 0) + 1;
        $view->showAddLink  = $this->acl->isAllowed('users', 'add');
        $view->headTitle()->set('Manage Users');

        // set DataGrid view helper namespace
        $helper = $view->dataGrid();
        $helper->setNamespace($gridNamespace);

        // collect the actions from interested parties
        $actions = new P4Cms_Navigation;
        P4Cms_PubSub::publish($gridNamespace . '.actions', $actions);
        $view->actions = $actions;

        // early exit for standard requests (ie. not json)
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->getHelper('helpUrl')->setUrl('users.management.html');
            return;
        }

        // fetch users - allow third-parties to influence list
        $users = P4Cms_User::fetchAll();
        try {
            P4Cms_PubSub::publish($gridNamespace . '.populate', $users, $form);
        } catch (Exception $e) {
            P4Cms_Log::logException("Error building user list.", $e);
        }

        // prepare sorting options
        $sortKey    = $request->getParam('sort', 'id');
        $sortFlags  = array(
            P4Cms_Model_Iterator::SORT_NATURAL,
            P4Cms_Model_Iterator::SORT_NO_CASE
        );
        if (substr($sortKey, 0, 1) == '-') {
            $sortKey = substr($sortKey, 1);
            $sortFlags[] = P4Cms_Model_Iterator::SORT_DESCENDING;
        } else {
            $sortFlags[] = P4Cms_Model_Iterator::SORT_ASCENDING;
        }

        // apply sorting options.
        $users->sortBy($sortKey, $sortFlags);

        // add users to the view.
        $view->users = $users;
    }

    /**
     * Handle user logins.
     * If not posted, presents form; otherwise, authenticates.
     */
    public function loginAction()
    {
        $request            = $this->getRequest();
        $form               = $this->_getLoginForm();

        // set up view
        $view               = $this->view;
        $view->form         = $form;
        $view->headTitle()->set('User Login');

        // if posted, validate form and authenticate user.
        if ($request->isPost()) {
            // if form is invalid, set response code and exit
            if (!$form->isValid($request->getPost())) {
                $this->getResponse()->setHttpResponseCode(400);
                return;
            }

            $login = $form->getValue('user');

            // silently clear login if it is does not look like
            // a valid p4 username or email address.
            $userValidator  = new P4_Validate_UserName;
            $emailValidator = new Zend_Validate_EmailAddress;
            if (!$userValidator->isValid($login) && !$emailValidator->isValid($login)) {
                $login = null;
            }

            // if login is an email address, lookup corresponding usernames.
            if (strpos($login, '@')) {
                $found = array(); // list with users matching the email address
                foreach (P4Cms_User::fetchAll() as $user) {
                    if ($user->getEmail() !== $login) {
                        continue;
                    }

                    $found[] = $user->getId();
                }

                $login = $found ?: null;
            }

            if (!is_array($login)) {
                $login = array($login);
            }

            // loop through all login candidates
            $errorMessage = 'Login failed. Invalid user or password.';
            foreach ($login as $loginCandidate) {

                // try to authenticate with given password
                $result = $this->_authenticate($loginCandidate, $form->getValue('password'));
                if (!$result->isValid()) {
                    // don't allow successful auth without priveleges, and report correct error
                    if ($result->getCode() === Zend_Auth_Result::FAILURE_IDENTITY_AMBIGUOUS) {
                        $errorMessage = 'You do not have permission to access this site.';
                        break;
                    }
                    continue;
                }

                // don't allow login as system user
                if ($this->_isSystemUser($loginCandidate)) {
                    Zend_Auth::getInstance()->clearIdentity();
                    $errorMessage = 'Login failed. Cannot login as the system user.';
                    break;
                }

                // auth data in session shouldn't influence page caching; add ourselves to the ignore list
                if (P4Cms_Cache::canCache('page')) {
                    P4Cms_Cache::getCache('page')->addIgnoredSessionVariable(
                        Zend_Auth::getInstance()->getStorage()->getNamespace()
                    );
                }

                // protect against session fixation
                Zend_Session::regenerateId();

                // login successful
                P4Cms_Notifications::add(
                    'Login successful.',
                    P4Cms_Notifications::SEVERITY_SUCCESS
                );

                // redirect for traditional contexts
                if (!$this->contextSwitch->getCurrentContext()) {
                    $this->redirector->gotoUrl($request->getBaseUrl());
                }

                return;
            }

            // login failed, add random 1-2 second delay.
            $delay = mt_rand(self::LOGIN_FAILED_MIN_DELAY, self::LOGIN_FAILED_MAX_DELAY);
            usleep($delay);

            // authentication failed - add error to form.
            if (!$this->contextSwitch->getCurrentContext()) {
                P4Cms_Notifications::add($errorMessage, P4Cms_Notifications::SEVERITY_ERROR);
            }

            $form->addError($errorMessage);
            $this->getResponse()->setHttpResponseCode(400);
        }
    }

    /**
     * Log the user out (clear identity).
     */
    public function logoutAction()
    {
        Zend_Auth::getInstance()->clearIdentity();
        P4Cms_Notifications::add('Logout completed.', P4Cms_Notifications::SEVERITY_SUCCESS);
        $this->redirector->gotoUrl($this->getRequest()->getBaseUrl());
    }

    /**
     * Add a new user.
     */
    public function addAction()
    {
        // enforce permissions
        $this->acl->check('users', 'add');

        $request    = $this->getRequest();
        $activeUser = P4Cms_User::fetchActive();
        $form       = new User_Form_Add;

        // deny if adding user is disabled
        if (!$activeUser->isAdministrator() && !P4_User::isAutoUserCreationEnabled()) {
            throw new P4Cms_AccessDeniedException(
                "You don't have permission to add users."
            );
        }

        // if we are connected to a P4 server using external authentication,
        // disable the password setting because it cannot be done without
        // an old password.  adding a description to the top of the dialog.
        $externalAuth = $activeUser->getAdapter()->getConnection()->hasExternalAuth();
        if ($externalAuth) {
            $note = "Your Perforce Server is using external authentication. An entry for this <br/>"
                  . "user must be added to the external authentication system before the <br/>"
                  . "user can log into Chronicle.";
            $form->removeElement('password');
            $form->removeElement('passwordConfirm');

            // add a note field
            $form->addElement(
                'note',
                'note',
                array(
                    'value'     => $note
                )
            );
            $form->getElement('note')
                 ->removeDecorator('label')
                 ->getDecorator('htmlTag')
                 ->setOption('class', 'user-note');
        }

        // set up view
        $view               = $this->view;
        $view->form         = $form;
        $view->headTitle()->set('Add User');

        // prepare default roles
        $defaultRoles = P4Cms_Acl_Role::exists(P4Cms_Acl_Role::ROLE_MEMBER)
            ? array(P4Cms_Acl_Role::ROLE_MEMBER)
            : array();

        // set default roles if no post
        if (!$request->isPost()) {
            $request->setParam('roles', $defaultRoles);
        }

        // populate form from request
        $form->populate($request->getParams());

        // if posted, validate form and save user.
        if ($request->isPost()) {
            // if form is invalid, set response code and exit
            if (!$form->isValid($request->getParams())) {
                $this->getResponse()->setHttpResponseCode(400);
                $view->errors = $form->getMessages();
                return;
            }

            // create the user entry.
            $user = new P4Cms_User;
            $user->setValues($form->getValues())
                 ->save();

            // set roles
            //  - if active user is not administrator, use site storage adapter
            //  - if user has permission, take roles from request, otherwise use defaults
            $adapter = $activeUser->isAdministrator()
                ? $activeUser->getPersonalAdapter()
                : P4Cms_Site::fetchActive()->getStorageAdapter();
            $roles   = $form->getElement('roles') && $this->acl->isAllowed('users', 'manage-roles')
                ? $form->getValue('roles')
                : $defaultRoles;
            P4Cms_Acl_Role::setUserRoles($user, $roles, $adapter);

            // if active user is anonymous, log in as newly created user
            if (P4Cms_User::fetchActive()->isAnonymous()) {
                $result = $this->_authenticate($user->getId(), $form->getValue('password'));
                if ($result->isValid()) {
                    P4Cms_Notifications::add(
                        "You have been logged in as '{$user->getId()}'",
                        P4Cms_Notifications::SEVERITY_SUCCESS
                    );
                }
            }

            // set notification message
            $view->message = "User '{$user->getId()}' has been successfuly added.";

            // for traditional requests, add notification message and redirect
            if (!$this->contextSwitch->getCurrentContext()) {
                P4Cms_Notifications::add(
                    $view->message,
                    P4Cms_Notifications::SEVERITY_SUCCESS
                );
                $this->redirector->gotoUrl($request->getBaseUrl());
            }
        }
    }

    /**
     * Edit an existing user entry.
     */
    public function editAction()
    {
        $request    = $this->getRequest();
        $activeUser = P4Cms_User::fetchActive();
        $userId     = $request->getParam('id', $activeUser->getId());

        // ensure user id is set in the request
        $request->setParam('id', $userId);

        // enforce permissions
        if ($userId !== $activeUser->getId()) {
            $this->acl->check('users', 'manage');
        }

        // deny if user id is null or attempted to edit system user
        if ($userId === null || $this->_isSystemUser($userId)) {
            throw new P4Cms_AccessDeniedException(
                "You don't have permission to edit this user."
            );
        }

        // determine if we can change password.
        // when connected to a P4 server using external authentication,
        // passwords cannot be changed if an auth-set trigger has not
        // been configured or trying to change other user's password.
        $connection        = $activeUser->getAdapter()->getConnection();
        $canChangePassword = !$connection->hasExternalAuth()
                             || ($connection->hasAuthSetTrigger() && ($activeUser->getId() === $userId));

        // determine whether old password input is neccessary when setting up new password
        $formOptions = array(
            'needOldPassword'   => !$activeUser->isAdministrator()
                || ($activeUser->getId() === $userId),
            'canChangePassword' => $canChangePassword
        );

        // determine whether the user is the last administrator
        $admins = P4Cms_Acl_Role::fetch(P4Cms_Acl_Role::ROLE_ADMINISTRATOR)->getRealUsers();
        $formOptions['requireAdministrator'] = count($admins) == 1
            && in_array($userId, $admins);

        // redirect to page not found if user doesn't exist
        if (!P4Cms_User::exists($userId)) {
            return $this->_forward('page-not-found', 'index', 'error');
        }

        $user = P4Cms_User::fetch($userId);

        // set up view
        $form               = new User_Form_Edit($formOptions);
        $view               = $this->view;
        $view->form         = $form;
        $view->user         = $user;
        $view->headTitle()->set('Edit User');

        // set roles from storage if no post, or not permitted.
        if (!$request->isPost() || !$this->acl->isAllowed('users', 'manage-roles')) {
            $request->setParam('roles', $user->getRoles()->invoke('getId'));
        }

        // populate form from request if posted, otherwise from storage.
        $form->populate(
            $request->isPost()
            ? $request->getParams()
            : $request->getParams() + $user->getValues()
        );

        // if change password unchecked, disable 'password' group.
        if (!$form->getValue('changePassword')) {
            $group = $form->getDisplayGroup('passwords');
            $group->setAttrib('class', $group->getAttrib('class') . ' disabled');
        }

        // if posted, validate form and save user.
        if ($request->isPost()) {
            // if form is invalid, set response code and exit
            if (!$form->isValid($request->getParams())) {
                $this->getResponse()->setHttpResponseCode(400);
                $view->errors = $form->getMessages();
                return;
            }

            $user->setValues($form->getValues());

            // if current password given, must set password explicitly.
            if ($form->getValue('currentPassword')) {
                $user->setPassword(
                    $form->getValue('password'),
                    $form->getValue('currentPassword')
                );
            }

            // we now try to save the user
            // if we are using external auth, there are several cases to handle:
            //  - auth set trigger fails (extract message and set errror on form)
            //  - subsequent login fails - auth-set didn't work correctly (tell user to use ext. auth)
            $externalAuth = $connection->hasExternalAuth();
            try {
                $user->save();
            } catch (P4_Exception $e) {
                $error   = false;
                $message = $e->getMessage();
                if ($externalAuth && stristr($message, "Command failed: Password not changed.")) {
                    $error = preg_replace('/^.*validation failed:/s', '', $message);
                    if (trim($error) === "no error message") {
                        $error = true;
                    }
                } else if ($externalAuth && ($e instanceof P4_Connection_LoginException)) {
                    $error = true;
                }

                // if this is an expected case, report as validation error.
                if ($error) {
                    if (!is_string($error)) {
                        $error = "Your Perforce Server is using external authentication. "
                               . "Please change the user's password in the external authentication system.";
                    }
                    $form->getElement('password')->addError(trim($error));
                    $this->getResponse()->setHttpResponseCode(400);
                    $view->errors = $form->getMessages();
                    return;
                }

                // unexpected/unhandled case.
                throw $e;
            }

            // if user has permission to manage roles, set roles from request
            if ($form->getElement('roles') && $this->acl->isAllowed('users', 'manage-roles')) {
                P4Cms_Acl_Role::setUserRoles($user, $form->getValue('roles'));
            }

            // if current user changed the password, re-authenticate to get updated ticket
            if ($activeUser->getId() === $userId && $form->getValue('changePassword')) {
                $result = $this->_authenticate($userId, $form->getValue('password'));
            }

            // set notification message
            $view->message = "User '{$user->getId()}' has been successfuly updated.";

            // clear any cache entries related to this user
            P4Cms_Cache::clean('all', 'p4cms_user_' . md5($user->getId()));

            // for traditional requests, add notification message and redirect
            if (!$this->contextSwitch->getCurrentContext()) {
                P4Cms_Notifications::add(
                    $view->message,
                    P4Cms_Notifications::SEVERITY_SUCCESS
                );
                $this->redirector->gotoUrl($request->getBaseUrl());
            }
        }
    }

    /**
     *  Delete user entry.
     *	User account can be removed only via post.
     */
    public function deleteAction()
    {
        // deny if not accessed via post
        $request = $this->getRequest();
        if (!$request->isPost()) {
            throw new P4Cms_AccessDeniedException(
                "Deleting users is not permitted in this context."
            );
        }

        $activeUser       = P4Cms_User::fetchActive();
        $userId           = $request->getPost('id');
        $deleteActiveUser = $userId === $activeUser->getId();

        if (!$deleteActiveUser) {
            $this->acl->check('users', 'manage');
        }

        // deny if attempting to delete system user
        if ($this->_isSystemUser($userId)) {
            throw new P4Cms_AccessDeniedException(
                "You don't have permission to delete this user."
            );
        }

        // get user to delete
        $user = $deleteActiveUser
            ? $activeUser
            : P4Cms_User::fetch($userId);

        // deleted user should have the same adapter as active user personal adapter
        $user->setAdapter($activeUser->getPersonalAdapter());

        // deny if deleted user is the only administrator
        if ($user->isAdministrator()
             && count(P4Cms_Acl_Role::fetch(P4Cms_Acl_Role::ROLE_ADMINISTRATOR)->getUsers()) == 1
        ) {
            throw new P4Cms_AccessDeniedException(
                "The only administrator cannot be deleted."
            );
        }

        // remove user references from the roles that user is associated with
        // use personal adapter if user is admin, otherwise use site adapter.
        $adapter = $activeUser->isAdministrator()
                 ? $activeUser->getPersonalAdapter()
                 : P4Cms_Site::fetchActive()->getStorageAdapter();
        P4Cms_Acl_Role::setUserRoles($user, array(), $adapter);

        // do the actual delete
        $user->delete();

        // clear any cache entries related to this user
        P4Cms_Cache::clean('all', 'p4cms_user_' . md5($user->getId()));

        // add notification if active user was deleted or
        // if we are in traditional context
        $context = $this->contextSwitch->getCurrentContext();
        if (!$context || $deleteActiveUser) {
            P4Cms_Notifications::add(
                "User '$userId' has been deleted.",
                P4Cms_Notifications::SEVERITY_SUCCESS
            );
        }

        // redirect for traditional requests
        if (!$context) {
            if ($deleteActiveUser) {
                $this->redirector->gotoUrl($request->getBaseUrl());
            } else {
                $this->redirector->gotoSimple('index');
            }
        }

        $this->view->userId           = $userId;
        $this->view->deleteActiveUser = $deleteActiveUser;
    }

    /**
     * Get the login form.
     *
     * @return  User_Form_Login     the login form.
     */
    protected function _getLoginForm()
    {
        // grab current acl.
        $acl = $this->acl->getAcl();

        $form = new User_Form_Login(
            array(
                'action' => $this->view->url(
                    array(
                        'module'        => 'user',
                        'controller'    => 'index',
                        'action'        => 'login'
                    )
                ),
                'acl'   => $acl
            )
        );

        // for context switched requests, prefix form ids with context
        // to ensure ids are unique if they appear twice on the page.
        $context = $this->contextSwitch->getCurrentContext();
        if ($context) {
            $form->setIdPrefix($context . "-");
        }

        return $form;
    }

    /**
     * Authenticate the user. Return true if success, otherwise false.
     *
     * @param   string  $login      user's id
     * @param   string  $password   user's password
     * @return  Zend_Auth_Result    The result of the authentication attempt.
     */
    protected function _authenticate($login, $password)
    {
        // construct user instance to authenticate against.
        $user = new P4Cms_User;
        $user->setId($login)
             ->setPassword($password);

        // authenticate
        return Zend_Auth::getInstance()->authenticate($user);
    }

    /**
     * Check if the given user is the system user.
     *
     * @param   string      $userId     the user id to compare against the system user id.
     * @return  boolean     true if the given user is the system user; otherwise false.
     */
    protected function _isSystemUser($userId)
    {
        return $userId == $this->getInvokeArg('bootstrap')->getResource('perforce')->getUser();
    }
}
