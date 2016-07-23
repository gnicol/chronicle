<?php
/**
 * Controller for dealing with site branches.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        enforce permissions - what p4 rights are req'd to make streams?
 */
class Site_BranchController extends Zend_Controller_Action
{
    public $contexts = array(
        'manage'        => array('json'),
        'add'           => array('json', 'partial'),
        'edit'          => array('json', 'partial'),
        'delete'        => array('json'),
        'pull'          => array('json', 'partial'),
        'pull-details'  => array('json', 'partial'),
        'switch'        => array('json')
    );

    /**
     * Set the management layout for all actions.
     */
    public function init()
    {
        $this->getHelper('layout')->setLayout('manage-layout');
    }

    /**
     * Transfer user's credentials to this branch if they are not
     * already logged in. Supports jsonp if 'callback' is passed.
     */
    public function switchAction()
    {
        // force json context.
        $this->contextSwitch->initContext('json');

        $user      = P4Cms_User::fetchActive();
        $view      = $this->view;
        $request   = $this->getRequest();
        $sessionId = $request->getParam('sessionId');
        $csrf      = $request->getParam(P4Cms_Form::CSRF_TOKEN_NAME);
        $callback  = $request->getParam('callback');

        // whitelist the allowed characters for callbacks
        $callback  = preg_replace('/[^a-zA-Z0-9_\.]/', '', $callback);

        // if the user isn't already logged into this branch, and a
        // session id is present for another branch, try and clone
        // the session data from the branch the user is switching
        // from so that they will remain logged in on the new branch.
        if ($user->isAnonymous() && $sessionId) {
            // pull out any current session data for this branch
            // we will merge it with the auth and cache details
            // present on the branch they are switching from
            $original = $_SESSION;

            // kill our current session so we can read in the session
            // data of the branch they are switching from
            session_destroy();

            // force our session id to the passed value
            session_id($sessionId);

            // try to restart, we silence warnings that occur for invalid IDs
            @session_start();

            // regenerate a fresh id but don't destroy the passed session
            session_regenerate_id(false);

            // only copy over the authentication and cache details
            // if the csrf token matches, otherwise we leave the
            // current session data alone
            $data = array();
            if ($csrf === P4Cms_Form::getCsrfToken()) {
                $authKey   = Zend_Auth::getInstance()->getStorage()->getNamespace();
                $cacheKey  = P4Cms_Cache_Frontend_Action::SESSION_NAMESPACE;
                $_SESSION += array($authKey => null, $cacheKey => null);
                $data      = array(
                    $authKey  => $_SESSION[$authKey],
                    $cacheKey => $_SESSION[$cacheKey]
                );
            }

            // blend any source branch data (auth/cache) with the
            // existing destinations branch's session data
            $_SESSION = $data + $original;
        }

        $view->callback = $callback;
        $view->site     = P4Cms_Site::fetchActive();
    }

    /**
     * Loads manage grid pre-filtered for the active site.
     */
    public function manageActiveAction()
    {
        $site = P4Cms_Site::fetchActive();
        $this->getRequest()->setParam('site', array('sites' => array($site->getSiteId())));

        $this->_forward('manage');
    }

    /**
     * List sites/branches for management.
     *
     * @publishes   p4cms.site.branch.grid.actions
     *              Modify the passed menu (add/modify/delete items) to influence the actions shown
     *              on entries in the Manage Sites and Branches grid.
     *              P4Cms_Navigation            $actions    A menu to hold grid actions.
     *
     * @publishes   p4cms.site.branch.grid.data.item
     *              Return the passed item after applying any modifications (add properties, change
     *              values, etc.) to influence the row values sent to the Manage Sites and Branches
     *              grid.
     *              array                       $item       The item to potentially modify.
     *              mixed                       $model      The original object/array that was used
     *                                                      to make the item.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.site.branch.grid.data
     *              Adjust the passed data (add properties, modify values, etc.) to influence the
     *              row values sent to the Manage Sites and Branches grid.
     *              Zend_Dojo_Data              $data       The data to be filtered.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.site.branch.grid.populate
     *              Adjust the passed iterator (possibly based on values in the passed form) to
     *              filter which branches will be shown on the Manage Sites and Branches grid.
     *              P4Cms_Model_Iterator        $branches   An iterator of P4Cms_Site objects.
     *              P4Cms_Form_PubSubForm       $form       A form containing filter options.
     *
     * @publishes   p4cms.site.branch.grid.render
     *              Make adjustments to the datagrid helper's options pre-render (e.g. change
     *              options to add columns) for the Manage Sites and Branches grid.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.site.branch.grid.form
     *              Make arbitrary modifications to the Manage Sites and Branches filters form.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.site.branch.grid.form.subForms
     *              Return a Form (or array of Forms) to have them added to the Manage Sites and
     *              Branches filters form. The returned form(s) should have a 'name' set on them to
     *              allow them to be uniquely identified.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.site.branch.grid.form.preValidate
     *              Allows subscribers to adjust the Manage Sites and Branches filters form prior to
     *              validation of the passed data. For example, modify element values based on
     *              related selections to permit proper validation.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.site.branch.grid.form.validate
     *              Return false to indicate the Manage Sites and Branches filters form is invalid.
     *              Return true to indicate your custom checks were satisfied, so form validity
     *              should be unchanged.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.site.branch.grid.form.populate
     *              Allows subscribers to adjust the Manage Sites and Branches filters form after it
     *              has been populated with the passed data.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     The values passed to the populate
     *                                                      method.
     */
    public function manageAction()
    {
        // enforce permissions.
        $this->acl->check('site', 'manage-branches');

        // setup list options form
        $request        = $this->getRequest();
        $gridNamespace  = 'p4cms.site.branch.grid';
        $view           = $this->view;

        // collect the actions from interested parties
        $actions = new P4Cms_Navigation;
        P4Cms_PubSub::publish($gridNamespace . '.actions', $actions);
        $view->actions = $actions;

        // determine whether to show add site/branch footer buttons.
        $view->showAddSiteButton   = $this->acl->isAllowed('site', 'add');
        $view->showAddBranchButton = P4Cms_Site::fetchAll(
            array(P4Cms_Site::FETCH_BY_ACL => array('branch', 'pull-from'))
        )->count();

        // set DataGrid view helper namespace
        $helper = $view->dataGrid();
        $helper->setNamespace($gridNamespace);

        // create a list of sites and their branches.
        // the 'site' entries really represent the depot the site branches
        // live in. we don't actually have an object for this so we simply
        // use a P4Cms_Model with a few basic details.
        $user     = P4Cms_User::fetchActive();
        $active   = P4Cms_Site::fetchActive();
        $branches = P4Cms_Site::fetchAll();
        $items    = new P4Cms_Model_Iterator;
        $lastSite = null;
        foreach ($branches as $branch) {
            // if this branch is on a new site (depot really) add a
            // site entry to our list of items
            $siteId = $branch->getSiteId();
            if ($lastSite != $siteId) {
                $item = new P4Cms_Model;
                $item->setId($branch->getSiteId());
                $item->setValues(
                    array(
                        'siteId'    => $item->getId(),
                        'type'      => 'site',
                        'owner'     => P4_Depot::fetch($item->getId())->getOwner(),
                        'name'      => $branch->getConfig()->getTitle()
                    )
                );
                $items[]  = $item;
                $lastSite = $siteId;
            }

            // pull the useful details off the branch and its stream/config
            // and place them onto a new generic model
            $stream = $branch->getStream();
            $config = $branch->getConfig();
            $parent = $stream->getParent();
            $item   = new P4Cms_Model;
            $item->setId($branch->getId());
            $item->setValues(
                array(
                    'siteId'        => $branch->getSiteId(),
                    'type'          => $stream->getType(),
                    'owner'         => $stream->getOwner(),
                    'name'          => $stream->getName(),
                    'basename'      => $branch->getBranchBasename(),
                    'parent'        => $parent,
                    'parentName'    => $parent && isset($items[$parent]) ? $items[$parent]->name : null,
                    'description'   => $stream->getDescription(),
                    'siteTitle'     => $config->getTitle(),
                    'url'           => $config->getUrl(),
                    'depth'         => $stream->getDepth(),
                    'branch'        => $branch,
                    'isParent'      => false,
                    'isActive'      => $branch->getId() == $active->getId(),
                    'canPull'       => $user->isAllowed('branch', 'pull-from', $branch->getAcl()),
                    'canDelete'     => $branch->getId() != $active->getId() && $stream->getType() !== 'mainline'
                )
            );
            $items[$branch->getId()] = $item;

            // update isParent flag on parent's item
            if ($parent && isset($items[$parent])) {
                $items[$parent]->setValue('isParent', true)
                               ->setValue('canDelete', false);
            }
        }

        // create the site form now that we have the list of items to hand it
        $form = new Site_Form_BranchGridOptions(
            array(
                'namespace'   => $gridNamespace,
                'items'       => $items
            )
        );
        $form->populate($request->getParams());

        // complete setting up view
        $view->form         = $form;
        $view->pageSize     = $request->getParam('count', 100);
        $view->rowOffset    = $request->getParam('start', 0);
        $view->pageOffset   = round($view->rowOffset / $view->pageSize, 0) + 1;
        $view->headTitle()->set('Sites and Branches');

        // early exit for standard requests (ie. not json)
        if (!$this->contextSwitch->getCurrentContext()) {
            // ensure we are using the management layout
            $this->getHelper('layout')->setLayout('manage-layout');
            $this->getHelper('helpUrl')->setUrl('branches.html');

            return;
        }

        // create a copy so we can later restore the 'obligitory' items
        $copy = new P4Cms_Model_Iterator($items->getArrayCopy());

        // allow third-parties to influence list
        try {
            P4Cms_PubSub::publish($gridNamespace . '.populate', $items, $form);
        } catch (Exception $e) {
            P4Cms_Log::logException("Error building branches list.", $e);
        }

        // put back any missing parents so the tree can display properly
        $items = $this->_restoreObligatory($items, $copy);

        // compose list of sorted items
        $view->items = $items;
    }

    /**
     * Edit site branch.
     */
    public function editAction()
    {
        // enforce permissions.
        $this->acl->check('site', 'manage-branches');

        // set up view
        $request    = $this->getRequest();
        $form       = new Site_Form_EditBranch;
        $view       = $this->view;
        $view->form = $form;
        $view->headTitle()->set('Edit Branch');

        // fetch the branch to edit
        $id         = $request->getParam('id', P4Cms_Site::fetchActive()->getId());
        $branch     = P4Cms_Site::fetch($id);
        $stream     = $branch->getStream();

        // populate form from the request if posted, otherwise from the storage
        $data = $request->isPost()
            ? $request->getParams()
            : array(
                'id'          => $id,
                'name'        => $stream->getName(),
                'parent'      => $stream->getParent(),
                'description' => $stream->getDescription(),
                'urls'        => implode(', ', $branch->getConfig()->getUrls())
            );
        $form->populate($data);

        // if posted, validate the form and update the branch
        if ($request->isPost() && $form->isValid($request->getPost())) {

            // update stream related to the branch
            $stream->setName($form->getValue('name'))
                   ->setParent($form->getValue('parent'))
                   ->setDescription($form->getValue('description'))
                   ->save();

            // update branch url
            $branch->getConfig()
                   ->setUrls($form->getValue('urls'))
                   ->save();

            // clear the global 'sites' cache.
            P4Cms_Cache::remove(P4Cms_Site::CACHE_KEY, 'global');

            // set notification message
            $view->message = "Branch '" . $form->getValue('name') . "' has been successfully updated.";

            // for traditional requests, add notification message and redirect
            if (!$this->contextSwitch->getCurrentContext()) {
                P4Cms_Notifications::add(
                    $view->message,
                    P4Cms_Notifications::SEVERITY_SUCCESS
                );
                $this->redirector->gotoSimple('manage');
            }
        }

        // if form contains errors, set response code and exit
        if ($form->getMessages()) {
            $this->getResponse()->setHttpResponseCode(400);
            $view->errors = $form->getMessages();
            return;
        }
    }

    /**
     * Add a new site branch.
     *
     * @publishes   p4cms.site.branch.add.preSubmit
     *              Provides an opportunity for modules to modify a new branch just prior to its
     *              files being committed to Perforce.
     *              P4Cms_Site              $branch     The branch being added.
     *              P4Cms_Site              $parent     The new branch's parent branch.
     *              P4Cms_Record_Adapter    $adapter    The current storage connection adapter.
     *
     * @publishes   p4cms.site.branch.add.postSubmit
     *              Provides an opportunity for modules to modify a new branch just after its
     *              files have been committed to Perforce.
     *              P4Cms_Site              $branch     The branch just added.
     *              P4Cms_Site              $parent     The new branch's parent branch.
     *              P4Cms_Record_Adapter    $adapter    The current storage connection adapter.
     */
    public function addAction()
    {
        // set up view
        $request    = $this->getRequest();
        $form       = new Site_Form_Branch;
        $view       = $this->view;
        $view->form = $form;
        $view->headTitle()->set('Add Branch');

        // populate form from request to fill in default values
        $form->populate($request->getParams());

        if ($request->isPost() && $form->isValid($request->getPost())) {

            // verify we are allowed to pull from parent.
            $parent = P4Cms_Site::fetch($form->getValue('parent'));
            $this->acl->check('branch', 'pull-from', null, null, $parent->getAcl());

            // compose new branch id.
            $filter = new P4Cms_Filter_TitleToId;
            $id     = '//' . $form->getValue('site') . '/' . $filter->filter($form->getValue('name'));

            // resolve conflicting ids by appending an incrementing number
            for ($raw = $id, $i = 2; P4Cms_Site::exists($id); $i++) {
                $id = $raw . '-' . $i;
            }

            // create the site stream (each site branch relates 1:1 with a stream)
            $stream = new P4_Stream();
            $stream->setId($id)
                   ->setName($form->getValue('name'))
                   ->setDescription($form->getValue('description'))
                   ->setParent($form->getValue('parent'))
                   ->setType('development')
                   ->setOwner(P4Cms_User::fetchActive()->getId())
                   ->setPaths('share ...')
                   ->save();

            // fetch our new site/branch object (must clear site cache first)
            P4Cms_Cache::remove(P4Cms_Site::CACHE_KEY, 'global');
            $branch = P4Cms_Site::fetch($stream->getId());

            // setup a new batch operation to contain the branch copy and configure.
            $p4      = $branch->getConnection();
            $adapter = $branch->getStorageAdapter();
            $change  = $adapter->beginBatch(
                'Creating ' . $stream->getId() . ' from ' . $stream->getParent()
            );

            // copy data from parent to the new branch.
            //  -S  indicates the new stream
            //  -r  so it goes from parent to stream
            //  -c  to put it in the batch
            //  -F  to force the copy
            //  -v  don't copy files to workspace
            $p4->run('copy', array('-c', $change, '-vrFS', $stream->getId()));

            // configure new branch according to parent branch and new branch form.
            $branch->getConfig()
                   ->setValues($parent->getConfig()->getValues())
                   ->setUrls($form->getValue('urls'))
                   ->save();

            // by default new branches should not be accessible by anonymous users.
            // we assume that new branches are for staging, testing, etc.
            $acl = clone $parent->getAcl();
            $acl->setRecord($branch->getAcl()->getRecord());
            $acl->setRule(
                P4Cms_Acl::OP_REMOVE,
                P4Cms_Acl::TYPE_ALLOW,
                P4Cms_Acl_Role::ROLE_ANONYMOUS,
                'branch',
                'access'
            )->save();

            // give third-parties a chance to modify new branch.
            P4Cms_PubSub::publish(
                'p4cms.site.branch.add.preSubmit',
                $branch,
                $parent,
                $adapter
            );

            // commit the copy and configure.
            $adapter->commitBatch();

            // give third-parties the chance to react to a new branch
            P4Cms_PubSub::publish(
                'p4cms.site.branch.add.postSubmit',
                $branch,
                $parent,
                $adapter
            );

            // clear the global 'sites' cache (again).
            P4Cms_Cache::remove(P4Cms_Site::CACHE_KEY, 'global');

            // set notification message
            $view->message = "Branch '" . $form->getValue('name') . "' has been successfully added.";

            // add a notification if requested or in a traditional context.
            if ($request->getParam('notify') || !$this->contextSwitch->getCurrentContext()) {
                P4Cms_Notifications::add(
                    $view->message,
                    P4Cms_Notifications::SEVERITY_SUCCESS
                );
            }

            // for traditional requests, redirect
            if (!$this->contextSwitch->getCurrentContext()) {
                $this->redirector->gotoUrl($request->getBaseUrl());
            }
        }

        // if form contains errors, set response code and exit
        if ($form->getMessages()) {
            $this->getResponse()->setHttpResponseCode(400);
            $view->errors = $form->getMessages();
            return;
        }
    }

    /**
     * Delete a site branch.
     */
    public function deleteAction()
    {
        // enforce permissions
        $this->acl->check('site', 'manage-branches');

        // deny if not accessed via post
        $request = $this->getRequest();
        if (!$request->isPost()) {
            throw new P4Cms_AccessDeniedException(
                "Deleting branches is not permitted in this context."
            );
        }

        // delete stream associated with the branch
        $id     = $request->getParam('id');
        $stream = P4_Stream::fetch($id);
        $stream->delete(true);

        // clear all caches - this clears the global site
        // cache and everything related to this site
        P4Cms_Cache::clean();

        // set notification and redirect for traditional requests
        if (!$this->contextSwitch->getCurrentContext()) {
            P4Cms_Notifications::add(
                'Branch "'. $stream->getName() .'" has been deleted.',
                P4Cms_Notifications::SEVERITY_SUCCESS
            );
            $this->redirector->gotoSimple('manage');
        }

        $this->view->branchId = $id;
    }

    /**
     * Pull changes from another branch to the current branch.
     *
     * @publishes   p4cms.site.branch.pull.postSubmit
     *              Provides an opportunity for modules to react to pulling changes from the source
     *              branch into the target branch just after the files have been committed to
     *              Perforce.
     *              Site_Model_PullPathGroup    $paths      The paths affected by this pull, grouped
     *                                                      logically.
     *              P4Cms_Site                  $target     The target branch for the pull.
     *              P4Cms_Site                  $source     The source branch for the pull.
     *              P4Cms_Record_Adapter        $adapter    The current storage connection adapter.
     */
    public function pullAction()
    {
        // enforce permissions (we will check pull-from later)
        $this->acl->check('branch', 'pull-into');

        $request = $this->getRequest();
        $mode    = $request->getParam('mode', Site_Form_Pull::MODE_MERGE);
        $paths   = new Site_Model_PullPathGroup;
        $adapter = P4Cms_User::fetchActive()->getPersonalAdapter();
        $target  = P4Cms_Site::fetchActive();
        $source  = $request->getParam('source')
            ? P4Cms_Site::fetch($request->getParam('source'))
            : null;

        // verify that we can pull from the selected source.
        if ($source) {
            $this->acl->check('branch', 'pull-from', null, null, $source->getAcl());
        }

        // if the request is posted and a source head-change has been
        // specified, we will use this to "pin" the pull to that point
        // in time - if we don't do this, newly submitted files might
        // make their way into the pull operation.
        if ($request->getPost('headChange')) {
            $headChange = $request->getPost('headChange');
        } else {
            $headChange = P4_Change::fetchAll(
                array(
                    P4_Change::FETCH_BY_STATUS => P4_Change::SUBMITTED_CHANGE,
                    P4_Change::FETCH_MAXIMUM   => 1
                ),
                $adapter->getConnection()
            )->first()->getId();

            // set on request so it makes its way into the form.
            $request->setParam('headChange', $headChange);
        }

        // if we have a source selected, preview the pull so that
        // we can inform the user about what paths will be affected
        // this means that we actually perform the pull twice if
        // the user is posting (doing the pull) - we can't skip this
        // step because we need to know all of the path groups to
        // validate the user's input.
        // @todo    consider caching this result for performance.
        if ($source) {
            $paths = $this->_doPull($source, $target, $mode, $headChange, $adapter, null, true);
        }

        // set the pull mode and source on the path group so that
        // the form can investigate how the paths were generated
        $paths->setValues(array('mode' => $mode, 'source' => $source));

        // set up view
        $form       = new Site_Form_Pull(array('pathGroup' => $paths));
        $view       = $this->view;
        $view->form = $form;
        $view->headTitle()->set('Pull Changes');

        // allow form to be primed via get params. we exclude 'paths' when
        // doing this as we want to keep the defaults if this isn't a post.
        $values = $request->getParams();
        unset($values['paths']);
        $form->populate($values);

        if ($request->isPost() && $form->isValid($request->getParams())) {

            // collect selected paths to merge from the form.
            // the form only contains ids that relate back to path groups.
            // we need to find the groups associated with those ids to get the paths.
            $include = array();
            foreach ($form->getValue('paths') as $pathGroupId) {
                $group = $paths->findById($pathGroupId);
                if ($group) {
                    $include = array_merge($include, $group->getIncludePaths());
                }
            }

            // perform the pull
            $paths    = $this->_doPull($source, $target, $mode, $headChange, $adapter, $include);
            $affected = $paths->getCount($paths::RECURSIVE);

            // give third-parties the chance to react to a completed pull
            P4Cms_PubSub::publish(
                'p4cms.site.branch.pull.postSubmit',
                $paths,
                $target,
                $source,
                $adapter
            );

            // clear all caches because pull can have a very broad impact
            P4Cms_Cache::clean();

            // set notification message
            $view->severity = P4Cms_Notifications::SEVERITY_SUCCESS;
            $view->message  = "Pulled " . $affected . " item" . ($affected != 1 ? "s" : "")
                            . " from '" . $source->getStream()->getName() . "'.";

            // we add the notification even for context specific requests
            // because the JS that drives the pull dialog does a reload
            // and the user wouldn't otherwise see a notification.
            P4Cms_Notifications::add($view->message, $view->severity);

            // for traditional requests, add notification message and redirect
            if (!$this->contextSwitch->getCurrentContext()) {
                $this->redirector->gotoUrl($request->getBaseUrl());
            }
        }

        // if form contains errors, set response code and exit
        if ($form->getMessages()) {
            $this->getResponse()->setHttpResponseCode(400);
            $view->errors = $form->getMessages();
            return;
        }
    }

    /**
     * Provides the details for specified include path(s).
     */
    public function pullDetailsAction()
    {
        // enforce 'pull-into' permission (later we check pull-from).
        $this->acl->check('branch', 'pull-into');

        // default to partial context if none is specified
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->contextSwitch->initContext('partial');
        }

        $request    = $this->getRequest();
        $mode       = $request->getParam('mode', Site_Form_Pull::MODE_MERGE);
        $headChange = $request->getParam('headChange') ?: 'now';
        $groupId    = $request->getParam('groupId');
        $adapter    = P4Cms_User::fetchActive()->getPersonalAdapter();
        $target     = P4Cms_Site::fetchActive();
        $source     = P4Cms_Site::fetch($request->getParam('source'));

        // enforce pull-from permission
        $this->acl->check('branch', 'pull-from', null, null, $source->getAcl());

        $groups  = $this->_doPull($source, $target, $mode, $headChange, $adapter, null, true);
        $group   = $groups->findById($groupId);
        $details = $group->getDetails(Site_Model_PullPathGroup::RECURSIVE);
        $columns = $request->getParam('columns', $details->getProperty('columns'));

        // sort details by conflict, then by label (so conflicts are first)
        $details->sortBy(
            array(
                'conflict'  => array($details::SORT_DESCENDING),
                'label'     => array($details::SORT_NATURAL, $details::SORT_NO_CASE)
            )
        );

        $this->view->groups  = $groups;
        $this->view->details = $details;
        $this->view->columns = $columns;
    }

    /**
     * Pull from source branch to target.
     *
     * @param   P4Cms_Site                  $source         the site/branch to copy from.
     * @param   P4Cms_Site                  $target         the site/branch to copy into.
     * @param   string                      $mode           merge or copy
     * @param   int                         $headChange     limit source files to this change
     *                                                      (ignore newer revisions)
     * @param   P4Cms_Record_Adapter        $adapter        the storage adapter to use.
     * @param   array|null                  $include        the list of paths to pull (null for all)
     * @param   bool|null                   $preview        set to true to revert instead of commit
     *                                                      needed to detect conflicts.
     * @return  Site_Model_PullPathGroup    the paths affected by this pull grouped logically.
     *
     * @publishes   p4cms.site.branch.pull.preSubmit
     *              Provides an opportunity for modules to react to pulling changes from the source
     *              branch into the target branch just prior to the paths being grouped. This event
     *              will occur for both actual pulls (where a postSubmit event will follow) and for
     *              previews (used to inform the user of which files are available for pull) where
     *              the data is never actually submitted.
     *              P4Cms_Site              $target     The target branch for the pull.
     *              P4Cms_Site              $source     The source branch for the pull.
     *              int                     $headChange A numerical revision representing the head
     *                                                  change on the source branch to pull.
     *              P4Cms_Record_Adapter    $adapter    The current storage connection adapter.
     */
    protected function _doPull(
        P4Cms_Site $source,
        P4Cms_Site $target,
        $mode,
        $headChange,
        P4Cms_Record_Adapter $adapter,
        array $include = null,
        $preview = false)
    {
        // include defaults to all files.
        $include = $include ?: array($target->getId() . '/...');

        // append head change to all of the include paths so that
        // we don't pull changes newer than those shown to the user.
        foreach ($include as &$path) {
            $path .= "@" . $headChange;
        }

        // begin a new batch to contain the pull operation
        $adapter->beginBatch(
            ($mode == Site_Form_Pull::MODE_COPY ? 'Copying' : 'Merging')
            . ' from ' . $source->getStream()->getName()
            . ' to '   . $target->getStream()->getName()
        );

        // two modes of operation:
        //   copy - clones source branch into target
        //  merge - propagates new changes since last pull
        if ($mode == Site_Form_Pull::MODE_COPY) {
            $conflicts = $this->_doCopy($source, $target, $include, $headChange, $preview, $adapter);
        } else {
            $conflicts = $this->_doMerge($source, $target, $include, $headChange, $preview, $adapter);
        }

        // give third-parties one last shot at modifying pull
        P4Cms_PubSub::publish(
            'p4cms.site.branch.pull.preSubmit',
            $target,
            $source,
            $headChange,
            $adapter
        );

        // determine which files have been affected by this pull operation
        // and organize the affected paths into human-friendly groups.
        $result = $adapter->getConnection()->run(
            'fstat',
            array(
                '-e',
                $adapter->getBatchId(),
                '-Ro',
                '-T',
                'depotFile,action',
                $target->getId() . '/...'
            )
        );
        $paths = $this->_groupPullPaths($result, $conflicts, $source, $target);

        // if we're previewing, revert and return early.
        if ($preview) {
            $adapter->revertBatch();
            return $paths;
        }

        // submit our pull operation.
        $adapter->commitBatch();

        // pulling changes can affect everything, clear all caches.
        P4Cms_Cache::clean();

        return $paths;
    }

    /**
     * Copy (clobber) from source branch to target.
     *
     * Returns a list of files with changes in the target
     * (not present in the source) that will be overwritten.
     *
     * @param   P4Cms_Site              $source         the site/branch to copy from.
     * @param   P4Cms_Site              $target         the site/branch to copy into.
     * @param   array                   $include        the list of paths to copy.
     * @param   int                     $headChange     limit source files to this change
     *                                                  (ignore newer revisions)
     * @param   bool|null               $preview        set to true if this is only a preview
     * @param   P4Cms_Record_Adapter    $adapter        the storage adapter to use.
     * @return  array                   files with conflicting changes (in depot syntax)
     */
    protected function _doCopy(
        P4Cms_Site $source,
        P4Cms_Site $target,
        array $include,
        $headChange,
        $preview,
        P4Cms_Record_Adapter $adapter)
    {
        $p4 = $adapter->getConnection();

        // copy data from source branch to the active branch.
        //  -S  indicates the source stream
        //  -P  indicates the target stream
        //  -c  to put it in the batch
        //  -F  to force the copy
        //  -v  don't copy files to workspace
        //  leading filespec arguments to limit scope (batched)
        $change   = $adapter->getBatchId();
        $batches  = $p4->batchArgs(
            $include,
            array('-vF', '-c', $change, '-S', $source->getId(), '-P', $target->getId())
        );
        foreach ($batches as $batch) {
            $p4->run('copy', $batch);
        }

        // to detect conflicts (files in the target with changes
        // that will be overwritten), we preview a merge in the
        // opposite direction (target -> source)

        // we need to do this using the source's connection
        $p4 = $source->getStorageAdapter()->getConnection();

        // we need to reverse the include paths to reference the
        // source branch instead of the target branch - we also
        // strip the 'headChange' revspec so that we don't miss
        // new conflicts on the target branch.
        $targetBase = $target->getId() . "/";
        $sourceBase = $source->getId() . "/";
        $reverse    = array();
        foreach ($include as $path) {
            if (strpos($path, $targetBase) === 0) {
                $reverse[] = P4_File::stripRevspec(
                    $sourceBase . substr($path, strlen($targetBase))
                );
            }
        }

        // preview the reverse merge
        //  -S  indicates the source stream
        //  -P  indicates the target stream
        //  -F  to force the merge
        //  -n  to preview the merge
        //  plus filespec arguments to limit scope (batched)
        $batches = $p4->batchArgs(
            $reverse,
            array('-F', '-n', '-S', $target->getId(), '-P', $source->getId())
        );

        // collect conflicts from merge result - note we need to
        // modify the depot files to reference the target branch.
        $conflicts = array();
        foreach ($batches as $batch) {
            $result = $p4->run('merge', $batch);
            foreach ($result->getData() as $conflict) {
                if (isset($conflict['depotFile'])
                    && strpos($conflict['depotFile'], $sourceBase) === 0
                ) {
                    $conflicts[] = $targetBase
                                 . substr($conflict['depotFile'], strlen($sourceBase));
                }
            }
        }

        return $conflicts;
    }

    /**
     * Merge changes from source branch to target.
     *
     * Returns a list of files with changes in the target
     * (not present in the source) that will be overwritten.
     *
     * @param   P4Cms_Site              $source         the site/branch to merge from.
     * @param   P4Cms_Site              $target         the site/branch to merge into.
     * @param   array                   $include        the list of paths to merge.
     * @param   int                     $headChange     limit source files to this change
     * @param   bool|null               $preview        set to true if this is only a preview
     * @param   P4Cms_Record_Adapter    $adapter        the storage adapter to use.
     *                                                  (ignore newer revisions)
     * @return  array                   files with conflicting changes (in depot syntax)
     *
     * @publishes   p4cms.site.branch.pull.conflicts
     *              Intended to provide modules an opportunity to programmatically resolve conflicts
     *              where possible. A resolve '-as' is run prior to this event so only files that
     *              were not safely auto resolved will be included. Any files which remain
     *              unresolved will be shown with a conflict warning to the end user.
     *              P4_Result               $conflicts  A list of the conflicts encountered during
     *                                                  a pull operation.
     *              P4Cms_Site              $target     The target branch for the pull.
     *              P4Cms_Site              $source     The source branch for the pull.
     *              int                     $headChange The head change number this pull was pinned
     *                                                  to.
     *              bool                    $preview    Set to true if the pull operation is just a
     *                                                  preview, false if the pull operation is to
     *                                                  be completed.
     *              P4Cms_Record_Adapter    $adapter    The current storage connection adapter.
     */
    protected function _doMerge(
        P4Cms_Site $source,
        P4Cms_Site $target,
        array $include,
        $headChange,
        $preview,
        P4Cms_Record_Adapter $adapter)
    {
        $p4 = $adapter->getConnection();

        // merge data from source branch to the active branch.
        //  -S  indicates the source stream
        //  -P  indicates the target stream
        //  -c  to put it in the batch
        //  -F  to force the merge
        //  plus filespec arguments to limit scope (batched)
        $change   = $adapter->getBatchId();
        $batches  = $p4->batchArgs(
            $include,
            array('-F', '-c', $change, '-S', $source->getId(), '-P', $target->getId())
        );
        foreach ($batches as $batch) {
            $p4->run('merge', $batch);
        }

        // perform initial safe-resolve to deal with files that can be
        // merged cleanly (only if source has changed and target has not)
        $p4->run('resolve', array('-as'));

        // allow interested parties to handle outstanding (unsafe) conflicts.
        // the last entry is the change description, so we remove it here.
        $conflicts = $p4->run('fstat', array('-e', $change, '-Ru', $target->getId() . '/...'));
        $conflicts->setData(array_slice($conflicts->getData(), 0, -1));

        P4Cms_PubSub::publish(
            'p4cms.site.branch.pull.conflicts',
            $conflicts,
            $target,
            $source,
            $headChange,
            $preview,
            $adapter
        );

        // make a final determination as to which files have
        // conflicts that cannot be safely resolved
        $result = $p4->run(
            'fstat',
            array(
                '-e',
                $change,
                '-Ru',
                '-T',
                'depotFile',
                $target->getId() . '/...'
            )
        );

        // extract depot-files as a flat list.
        $conflicts = array();
        foreach ($result->getData() as $conflict) {
            if (isset($conflict['depotFile'])) {
                $conflicts[] = $conflict['depotFile'];
            }
        }

        // resolve remaining conflicts with the source branch as the authority.
        $p4->run('resolve', array('-at'));

        return $conflicts;
    }

    /**
     * Group the paths affected by a pull operation in a human-friendly way.
     *
     * @param   P4_Result   $result         the output from a merge or copy command
     * @param   array       $conflicts      a flat list of depot-files in conflict.
     * @param   P4Cms_Site  $source         the source site/branch of the pull operation
     * @param   P4Cms_Site  $target         the target site/branch of the pull operation
     * @return  Site_Model_PullPathGroup    the paths affected by this pull grouped logically.
     *
     * @publishes   p4cms.site.branch.pull.groupPaths
     *              The passed paths object starts with all paths being pulled directly associated
     *              with it. Modules should add sub-groups and move logically grouped paths into
     *              them. They can also set callbacks on the sub-groups to provide human friendly
     *              entry titles and counts. Any paths left at the top level will be automatically
     *              moved into an 'Other' group after this event completes.
     *              Site_Model_PullPathGroup    $paths      The path structure to be organized.
     *              P4Cms_Site                  $source     The source branch.
     *              P4Cms_Site                  $target     The target branch.
     *              P4_Result                   $result     the output from a merge or copy command.
     */
    protected function _groupPullPaths($result, array $conflicts, $source, $target)
    {
        $paths = new Site_Model_PullPathGroup;

        // put all paths in the root initially.
        // here we also check if this file is conflicting.
        foreach ($result->getData() as $path) {
            if (isset($path['depotFile'])) {
                $path['conflict'] = in_array($path['depotFile'], $conflicts);
                $paths->addPath($path);
            }
        }

        // let third-parties organize paths.
        // the objective here is to move paths from the
        // root down into sub-groups.
        P4Cms_PubSub::publish(
            'p4cms.site.branch.pull.groupPaths',
            $paths,
            $source,
            $target,
            $result
        );

        // put all remaining root paths into a 'other' sub-group.
        if ($paths->getPaths()->count()) {
           $other = new Site_Model_PullPathGroup(
               array(
                   'label' => 'Other',
                   'order' => 100,
                   'paths' => $paths->getPaths()
               )
           );
           $paths->setPaths(null);
           $paths->addSubGroup($other);
        }

        return $paths;
    }

    /**
     * Scans over the filtered list of branch/site items and re-adds any missing
     * ancestors to ensure we can show a full heirachy to our matches.
     *
     * @param   P4Cms_Model_Iterator    $items      The filtered list of items
     * @param   P4Cms_Model_Iterator    $originals  A full list of all items
     * @return  P4Cms_Model_Iterator    A new iterator with the obligatory items restored
     */
    protected function _restoreObligatory(P4Cms_Model_Iterator $items, P4Cms_Model_Iterator $originals)
    {
        // produce an original list indexed by id for later lookups
        $originalsById = new P4Cms_Model_Iterator;
        foreach ($originals as $original) {
            $originalsById[$original->getId()] = $original;
        }

        // produce an array of obligatory items to later tack back on
        $obligatory = array();
        $itemKeys   = $items->invoke('getId');
        foreach ($items as $item) {
            // skip over site entries; they won't have any parents
            if ($item->getValue('type') == 'site') {
                continue;
            }

            // if the item's site isn't listed; add it to obligatory
            if (!in_array($item->getValue('siteId'), $itemKeys)) {
                $obligatory[] = $item->getValue('siteId');
            }

            // switch to the 'stream' layer and go through parents
            $parent = $item->getValue('branch')->getStream();
            while ($parent = $parent->getParentObject()) {
                if (!in_array($parent->getId(), $itemKeys)) {
                    $obligatory[] = $parent->getId();
                }
            }
        }

        // append and mark obligatory items but maintain original item ordering
        $obligatory = array_unique($obligatory);
        $result     = new P4Cms_Model_Iterator;
        foreach ($originalsById as $id => $item) {
           if (in_array($id, $obligatory)) {
               $item->setValue('obligatory', true);
               $result->append($item);
           } else if (in_array($id, $itemKeys)) {
               $result->append($item);
           }
        }

        return $result;
    }
}
