<?php
/**
 * Displays and manages content.
 *
 * There are a number of 'tags' that will be used to clear cache entries
 * when content is modified. You can tag your cache entries with:
 *  p4cms_content                   - present on many entries; should only be cleared rarely
 *  p4cms_content_type              - also present on many entries; cleared on a reset of types
 *  p4cms_content_<binhex(id)>      - cleared when the given entry is edited/deleted
 *  p4cms_content_type_<binhex(id)> - cleared when the given type is edited/deleted
 *  p4cms_content_list              - cleared when any content is added/edited/deleted intended for
 *                                    updating aggregate lists of content
 * The <> brackets are just to show position, only the binhex'd id will be present.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_IndexController extends Zend_Controller_Action
{
    // arbitrary limit for image scaling operations
    // 4k allows scaling upto 10 megapixel output and uses about 1/4 GB of ram.
    const   MAX_SCALE   = 4000;

    public  $contexts   = array(
        'add'               => array('dojoio' => 'post', 'json' => 'post'),
        'browse'            => array('json', 'partial'),
        'edit'              => array('dojoio' => 'post', 'json' => 'post'),
        'choose-type'       => array('partial'),
        'delete'            => array('json', 'partial'),
        'form'              => array('partial', 'dojoio' => 'post'),
        'index'             => array('json'),
        'sub-form'          => array('partial'),
        'toolbar'           => array('partial'),
        'validate-field'    => array('json'),
        'view'              => array('json', 'preview', 'partial'),
        'opened'            => array('json')
    );

    /**
     * Initialize object
     *
     * Extends parent to add content and content type tags to page cache
     * if it is present.
     */
    public function init()
    {
        parent::init();

        if (P4Cms_Cache::canCache('page')) {
            P4Cms_Cache::getCache('page')
                ->addTag('p4cms_content')
                ->addTag('p4cms_content_type');
        }
    }

    /**
     * Display a list of recent content.
     */
    public function indexAction()
    {
        $this->view->canAdd = $this->acl->isAllowed('content', 'add');
        if (!$this->acl->isAllowed('content', 'access')) {
            return;
        }

        $this->view->recent = P4Cms_Content::fetchAll(
            P4Cms_Record_Query::create()
            ->setMaxRows(5)
            ->setSortBy(P4Cms_Record_Query::SORT_DATE)
            ->setReverseOrder(true)
        );

        // tag the page cache so it can be appropriately cleared later
        if (P4Cms_Cache::canCache('page')) {
            P4Cms_Cache::getCache('page')->addTag('p4cms_content_list');
        }
    }

    /**
     * List content for management.
     *
     * To provide an action the grid participants subscribe to the topic:
     *  p4cms.content.grid.actions
     *
     * One argument will be passed to subscribers:
     *  P4Cms_Navigation    $actions    a navigation container to hold all actions
     *
     * They are expected to add/modify/etc. pages to the navigation container.
     * The pages will be rendered to a Menu Dijit so utilizing the onClick
     * and, optionally, onShow events is advised to control menu item behaviour.
     *
     * The default actions are added during module init. To modify/remove a default
     * action, subscribe during module load, or later, to ensure the default nav
     * entries are already present.
     */
    public function manageAction()
    {
        // enforce permissions.
        $this->acl->check('content', 'manage');

        // generate page with data grid and form
        $this->browseAction();

        // the request can specify which columns appear - only permit column names.
        $request = $this->getRequest();
        $columns = $request->getParam('columns', array('type', 'title', 'modified', 'actions'));
        $columns = array_filter($columns, 'is_string');

        // enable manage-specific settings
        $view                       = $this->view;
        $view->showAddLink          = $this->acl->isAllowed('content', 'add');
        $view->showDeleteButton     = $this->acl->isAllowed('content', 'delete');
        $view->selectionMode        = 'extended';
        $view->columns              = $columns;

        $view->headTitle()->set('Manage Content');
        $this->getHelper('helpUrl')->setUrl('content.html');
    }

    /**
     * Handle requests to display a list of content
     * Prepares list options form for tradtional requests.
     * Prepares content query for context specific requests.
     *
     * @publishes   p4cms.content.grid.actions
     *              Modify the passed menu (add/modify/delete items) to influence the actions shown
     *              on entries in the Manage Content grid.
     *              P4Cms_Navigation            $actions    A menu to hold grid actions.
     *
     * @publishes   p4cms.content.grid.data.item
     *              Return the passed item after applying any modifications (add properties, change
     *              values, etc.) to influence the row values sent to the Manage Content grid.
     *              array                       $item       The item to potentially modify.
     *              mixed                       $model      The original object/array that was used
     *                                                      to make the item.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.content.grid.data
     *              Adjust the passed data (add properties, modify values, etc.) to influence the
     *              row values sent to the Manage Content grid.
     *              Zend_Dojo_Data              $data       The data to be filtered.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.content.grid.populate
     *              Adjust the passed query (possibly based on values in the passed form) to filter
     *              which content entries will be shown on the Manage Content grid.
     *              P4Cms_Record_Query          $query      The query to filter content entries.
     *              P4Cms_Form_PubSubForm       $form       A form containing filter options.
     *
     * @publishes   p4cms.content.grid.render
     *              Make adjustments to the datagrid helper's options pre-render (e.g. change
     *              options to add columns) for the Manage Content grid.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.content.grid.form
     *              Make arbitrary modifications to the Manage Content filters form.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.content.grid.form.subForms
     *              Return a Form (or array of Forms) to have them added to the Manage Content
     *              filters form. The returned form(s) should have a 'name' set on them to allow
     *              them to be uniquely identified.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.content.grid.form.preValidate
     *              Allows subscribers to adjust the Manage Content filters form prior to
     *              validation of the passed data. For example, modify element values based on
     *              related selections to permit proper validation.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.content.grid.form.validate
     *              Return false to indicate the Manage Content filters form is invalid. Return true
     *              to indicate your custom checks were satisfied, so form validity should be
     *              unchanged.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.content.grid.form.populate
     *              Allows subscribers to adjust the Manage Content filters form after it has been
     *              populated with the passed data.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     The values passed to the populate
     *                                                      method.
     */
    public function browseAction()
    {
        // ensure users are allowed to access content before the list is displayed
        $this->acl->check('content', 'access');

        // get list option sub-forms.
        $request        = $this->getRequest();
        $gridNamespace  = 'p4cms.content.grid';
        $form           = new Ui_Form_GridOptions(
            array(
                'namespace'   => $gridNamespace
            )
        );
        $form->populate($request->getParams());

        // the request can specify which columns appear - only permit column names.
        $columns = $request->getParam('columns', array('type', 'title', 'modified'));
        $columns = array_filter($columns, 'is_string');

        // if the title column is requested, ensure that it isn't "linkified"
        $columns = array_flip($columns);
        if (isset($columns['title'])) {
            $columns['title'] = array(
                'formatter' => 'p4cms.content.grid.Formatters.titleNoLink'
            );
        }

        // setup access-restricted view, manageAction will expand these settings as needed
        $view                       = $this->view;
        $view->form                 = $form;
        $view->pageSize             = $request->getParam('count', 100);
        $view->rowOffset            = $request->getParam('start', 0);
        $view->pageOffset           = round($view->rowOffset / $view->pageSize, 0) + 1;
        $view->columns              = $columns;
        $view->showAddLink          = false;
        $view->showDeleteButton     = false;
        $view->selectionMode        = $request->getParam('selectionMode', 'single');

        // set DataGrid view helper namespace
        $helper = $view->dataGrid();
        $helper->setNamespace($gridNamespace);

        // early exit for standard requests (ie. not json or partial)
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->_helper->layout->setLayout('manage-layout');
            return;
        }

        // construct list query - allow third-parties to influence query.
        $query = new P4Cms_Record_Query;
        $query->setRecordClass('P4Cms_Content');
        try {
            $result = P4Cms_PubSub::publish($gridNamespace . '.populate', $query, $form);
        } catch (Exception $e) {
            P4Cms_Log::logException("Error building content list query.", $e);
        }

        // prepare sorting options
        $sortKey = $sortKeyOriginal = $request->getParam('sort', '-#REdate');
        $sortFlags = array();
        // handle sort order; descending sort identified with '-' prefix.
        if (substr($sortKey, 0, 1) == '-') {
            $sortKey = substr($sortKey, 1);
            $sortFlags[] = P4Cms_Record_Query::SORT_DESCENDING;
        }

        // if we're sorting via an internal attribute, use the traditional
        // syntax by knocking out the query options and reversing the
        // results if necessary.
        if (strpos($sortKey, '#') === 0) {
            $sortFlags = null;
            $query->setReverseOrder($sortKey === $sortKeyOriginal ? false : true);
        }

        // some column names differ from the model, so we need a map.
        $sortKeyMap = array(
            'type' => 'contentType'
        );

        // look up requested sort column in our map.
        $sortKey = isset($sortKeyMap[$sortKey])
            ? $sortKeyMap[$sortKey]
            : $sortKey;

        $query->setSortBy($sortKey, $sortFlags);

        // add query to the view.
        $view->query = $query;
    }

    /**
     * Choose a content type to add.
     * Prompts the user to select from the available content types.
     */
    public function chooseTypeAction()
    {
        // load types into view.
        $this->view->typeGroups = P4Cms_Content_Type::fetchGroups();
        $this->view->headTitle()->set('Choose Content Type');
    }

    /**
     * Renders the content form for the requested entry or content type.
     *
     * @param   boolean     $getForm    if true, return the content form.
     *
     * The p4cms.content.form events documented on the manage action will
     * also be broadcast when this action is accessed.
     */
    public function formAction($getForm = false)
    {
        $request = $this->getRequest();
        $type    = $request->getParam('contentType');
        $id      = $request->getParam('id');

        // if a version is present, generate rev-spec.
        $revspec = $request->getParam('version');
        if ($revspec) {
            // enforce permissions - viewing historic versions requires 'access-history' privilege.
            $this->acl->check('content', 'access-history');

            $revspec = '#' . $revspec;
        }

        $entry = $id
            ? P4Cms_Content::fetch($id . $revspec, $revspec ? array('includeDeleted' => true) : null)
            : new P4Cms_Content(array(P4Cms_Content::TYPE_FIELD => $type));

        if (!$getForm && $request->isPost()) {
            return $this->editAction($entry, 'edit');
        }

        // construct and populate the content form.
        $form = $this->_getContentForm($entry);
        $form->populate($request->getPost());

        // populate the view.
        $this->view->entry = $entry;
        $this->view->type  = $type;
        $this->view->form  = $form;

        // explicitly set partial context for other requests.
        $this->contextSwitch->initContext('partial');

        return $form;
    }

    /**
     * Renders a specific sub-form of the content form.
     * Forces the 'partial' request context.
     */
    public function subFormAction()
    {
        $form = $this->formAction(true);

        // ensure requested sub-form exists.
        $subForm = $form->getSubForm($this->getRequest()->getParam('form'));
        if (!$subForm instanceof Zend_Form) {
            throw new Content_Exception(
                "Cannot get sub-form. Requested sub-form does not exist."
            );
        }

        // populate the view.
        $this->view->subForm = $subForm;
    }

    /**
     * Add content.
     *
     * If not type has been selected, forwards to choose-type action.
     * If a type is indicated, create new entry and forward to edit action.
     */
    public function addAction()
    {
        // enforce permissions.
        $this->acl->check('content', 'add');

        $request = $this->getRequest();

        // if get request and no valid type specified, prompt user for type.
        // check 'contentType' param first, and 'type' param second.
        $type = $request->getParam('contentType', $request->getParam('type'));
        if ($request->isGet() && (!$type || !P4Cms_Content_Type::exists($type))) {
            return $this->_forward('choose-type');
        }

        // get the content type definition.
        $type = P4Cms_Content_Type::fetch($type);

        // create new content model and set type.
        $entry = new P4Cms_Content;
        $entry->setContentType($type);

        // set the page title.
        $this->view->headTitle()->set("Add '" . $type->getLabel() . "'");

        return $this->editAction($entry, 'edit');
    }

    /**
     * Display content.
     *
     * @param   P4Cms_Content       $entry          optional - content entry to display.
     * @param   null|string|array   $skipAclCheck   optional - pass string or array of strings
     *                                              enumerating the privilegs that can be skipped
     */
    public function viewAction(P4Cms_Content $entry = null, $skipAclCheck = null)
    {
        // enforce permissions.
        if (!in_array('access', (array)$skipAclCheck)) {
            $this->acl->check('content', 'access');
        }

        // if not called with an entry, we must fetch one.
        $request = $this->getRequest();

        // if a version is present, generate rev-spec.
        $revspec = $request->getParam('version');
        if ($revspec) {
            // enforce permissions - viewing historic versions requires 'access-history' privilege.
            if (!in_array('access-history', (array)$skipAclCheck)) {
                $this->acl->check('content', 'access-history');
            }

            // inject javascript to enable the history toolbar button
            // if the user came directly to the view action.
            // the edit action forwards here and doesn't want this.
            if ($request->getActionName() == 'view') {
                $this->view->dojo()->addOnLoad(
                    "function(){ p4cms.content.startHistory(); }"
                );
            }

            $revspec = '#' . $revspec;
        }

        // attempt to fetch content entry.
        try {
            $entry = $entry ?: P4Cms_Content::fetch(
                $request->getParam('id') . $revspec,
                $revspec ? array('includeDeleted' => true) : null
            );
        } catch (Exception $e) {
            // we only have special handling for specific types; rethrow anything else
            if (!$e instanceof P4Cms_Record_NotFoundException
                && !$e instanceof InvalidArgumentException
            ) {
                throw $e;
            }

            return $this->_forward('page-not-found', 'index', 'error');
        }

        // validate the content type - if the type has no id, we assume it
        // is missing and was dynamically generated by the content class.
        $type   = $entry->getContentType();
        $typeId = $entry->getContentTypeId();
        if (!$type->getId()) {
            $message = $typeId
                ? "This content entry requires a missing content type ('$typeId')."
                : "This content entry has no content type.";
            throw new Content_Exception($message);
        }

        // populate the view.
        $view           = $this->view;
        $view->entry    = $entry;
        $view->type     = $type;


        // request can specify an array of fields, true or false for json context.
        $view->fields   = $request->getParam('fields', true);

        // request can request the change be included for json context.
        $view->includeChange = (bool) $request->getParam('includeChange', false);

        // request can request the status be included for json context.
        $view->includeStatus = (bool) $request->getParam('includeStatus', false);

        // request can request the opened status be included for json context.
        $view->includeOpened = (bool) $request->getParam('includeOpened', false);

        // tag the page cache so it can be appropriately cleared later
        if (P4Cms_Cache::canCache('page')) {
            P4Cms_Cache::getCache('page')
                ->addTag('p4cms_content_'      . bin2hex($entry->getId()))
                ->addTag('p4cms_content_type_' . bin2hex($entry->getContentTypeId()));
        }

        // set the page title if entry has one.
        if (strlen($entry->getTitle())) {
            $this->view->headTitle()->set($entry->getTitle());
        }

        // set the meta description from the entry's excerpt
        $excerpt = $entry->getExcerpt(150, array('fullExcerpt' => true));
        if (strlen($excerpt)) {
            $this->view->headMeta()->setName('description', $excerpt);
        }

        // set the contentEntry view helper defautls
        $view->contentEntry()->setDefaults(
            $entry,
            array(Content_View_Helper_ContentEntry::OPT_PRELOAD_FORM => true)
        );

        // record the content id in the widget context to assist any plugins
        // that need to know what content is currently being displayed.
        $this->widgetContext->setValue('contentId', $entry->getId());

        // select the layout and view scripts to use.
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->getHelper('layout')->setLayout($this->_getEntryLayoutScript($entry));
        }
        $this->getHelper('viewRenderer')->setRender($this->_getEntryViewScript($entry));
    }

    /**
     * This action exposes the list of users currently editing a given
     * content entry (along with the entry's status and change details).
     * If posted to with an event parameter set to start/ping/stop the
     * active user will be updated in opened list.
     *
     * If the event param is start, the active user will be added to the
     * opened list with a pingTime of now and editTime of null.
     *
     * If the event param is ping, the active user will have their ping
     * time updated to now. The edit time will be unchanged (the edit
     * time is updated whenever 'validateFieldAction' is called).
     *
     * For the stop event the active user will be removed from
     * the opened list.
     *
     * Lastly, if the event param is missing or unrecognized the status
     * will be returned but no changes will be made to the users opened
     * details.
     *
     * Even if the caller lacks 'access-history' permissions conflicts
     * involving a deleted revision are reported.
     */
    public function openedAction()
    {
        // require edit access as that is the only time this action makes sense
        $this->acl->check('content', 'edit');

        // force json context; without fields the html version won't work well.
        $this->contextSwitch->initContext('json');

        // force the settings we care about to known values, we don't
        // want to risk leaking field values via this action.
        $request = $this->getRequest();
        $request->setParam('fields',        false)
                ->setParam('includeChange', true)
                ->setParam('includeStatus', true)
                ->setParam('includeOpened', true)
                ->setParam('version',       'head');

        // let view take care of fetching the entry and providing output
        $this->viewAction(null, array('access', 'access-history'));

        // exit if no content entry could be retrieved
        $entry = $this->view->entry;
        if (!$entry instanceof P4Cms_Content) {
            return;
        }

        // if its a post deal with the start/ping/stop events
        if ($request->isPost()) {
            $user   = P4Cms_User::fetchActive();
            $record = new P4Cms_Content_Opened;
            $record->setAdapter(P4Cms_Site::fetchActive()->getStorageAdapter())
                   ->setId($entry->getId());

            switch ($request->getParam('event')) {
                case 'start':
                    $record->setUserStartTime($user)
                           ->setUserPingTime($user)
                           ->setUserEditTime($user, null)
                           ->save();
                    break;
                case 'ping':
                    $record->setUserPingTime($user)
                           ->save();
                    break;
                case 'stop':
                    $record->setUserPingTime($user, null)
                           ->setUserEditTime($user, null)
                           ->save();
                    break;
            }
        }
    }

    /**
     * Rollback content.
     *
     * If the record id and change are valid; rolls back the specified
     * entry and redirects to view so the result will be shown.
     */
    public function rollbackAction()
    {
        $request = $this->getRequest();
        $id      = $request->getParam('id');

        // enforce permissions - requires both access-history and edit privileges.
        $message = 'You do not have permission to rollback this content entry.';
        $this->acl->check('content',        'access-history', null, $message);
        $this->acl->check('content/' . $id, 'edit',           null, $message);

        if ($request->getParam('change')) {
            $revSpec = '@' . $request->getParam('change');
        } else if ($request->getParam('version')) {
            $revSpec = '#' . $request->getParam('version');
        } else {
            throw new InvalidArgumentException(
                'A version or change number must be specified'
            );
        }

        // fetch the entry at the requested revision
        $entry = P4Cms_Content::fetch(
            $id . $revSpec,
            array('includeDeleted' => true)
        );

        $version = $entry->toP4File()->getStatus('headRev');

        $entry->save('Rollback to version '  . $version);

        // clear any cached entries related to this page
        P4Cms_Cache::clean(
            Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
            array('p4cms_content_' . bin2hex($entry->getId()), 'p4cms_content_list')
        );

        // redirect to view the specified entry
        $this->getHelper('redirector')->gotoUrl($entry->getUri());
    }

    /**
     * Edit content.
     *
     * HTTP get requests are forwarded to the view action for in-place
     * editing. Post requests are validated and saved.
     *
     * The p4cms.content.form events documented on the manage action will
     * also be broadcast when this action is accessed.
     *
     * @param   P4Cms_Content       $entry          optional - content entry to display.
     * @param   null|string|array   $skipAclCheck   optional - pass string or array of strings
     *                                              enumerating the privilegs that can be skipped
     */
    public function editAction(P4Cms_Content $entry = null, $skipAclCheck = null)
    {
        $request     = $this->getRequest();
        $headVersion = $request->getParam('headVersion');
        $entryId     = $request->getParam('id');

        // If we have a head version and ID ensure we fetch that
        // revision of the entry. This will cause a conflict
        // exception on save should we be out of date.
        if ($entryId && $headVersion) {
            $entryId = $entryId . "#" . $headVersion;
        }

        // enforce permissions.
        if (!in_array('edit', (array)$skipAclCheck)) {
            $this->acl->check('content/' . $entryId, 'edit');
        }

        // forward get requests to view action
        if ($request->isGet()) {

            // inject javascript to enable the appropariate add/edit toolbar button
            $action = $entryId ? 'startEdit' : 'startAdd';
            $this->view->dojo()->addOnLoad(
                "function(){ p4cms.content.$action(); }"
            );

            return $this->viewAction($entry, $skipAclCheck);
        }

        // if not called with an entry, we must fetch one.
        try {
            $entry = $entry ?: P4Cms_Content::fetch($entryId, array('includeDeleted' => true));
        } catch (P4Cms_Record_NotFoundException $e) {
            return $this->_forward('page-not-found', 'index', 'error');
        }

        // construct and populate the content form.
        $form = $this->_getContentForm($entry);
        $form->populate($request->getPost());

        // populate the view.
        $this->view->entry   = $entry;
        $this->view->type    = $entry->getContentType();
        $this->view->form    = $form;
        $this->view->isValid = true;

        // if form was posted and is valid, save it.
        if ($request->isPost() && $form->isValid($request->getPost())) {
            try {
                // if this content entry doesn't have an owner,
                // set the content owner to the current user.
                $entry->setOwner($entry->getOwner() ?: P4Cms_User::fetchActive());

                // if a comment was provided by the user, set it as change
                // description, otherwise provide default value
                $saveForm    = $form->getSubForm('save');
                $description = $saveForm && $saveForm->getValue('comment')
                    ? $saveForm->getValue('comment')
                    : 'Saved content change.';

                // copy form values to the content entry and save it
                // entry is a pub/sub record so third-parties can participate
                // ensure we throw on conflict so we can alert user
                $entry->setValues($form)
                      ->save($description, P4Cms_Content::SAVE_THROW_CONFLICT);

                // clear any cached entries related to this page
                // @todo connect to p4cms.content.record.postSave
                P4Cms_Cache::clean(
                    Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
                    array('p4cms_content_' . bin2hex($entry->getId()), 'p4cms_content_list')
                );


                // remove ourselves from the 'opened' list if we had an identified record
                if ($entryId) {
                    $user   = P4Cms_User::fetchActive();
                    $record = new P4Cms_Content_Opened;
                    $record->setAdapter(P4Cms_Site::fetchActive()->getStorageAdapter())
                           ->setId($entry->getId())
                           ->setUserStartTime($user, null)
                           ->setUserPingTime($user,  null)
                           ->setUserEditTime($user,  null)
                           ->save();
                }

                // notify success and redirect for traditional requests.
                if (!$this->contextSwitch->getCurrentContext()) {
                    P4Cms_Notifications::add(
                        'Content saved.',
                        P4Cms_Notifications::SEVERITY_SUCCESS
                    );

                    return $this->_redirect($request->getBaseUrl());
                }
            } catch (P4_Connection_ConflictException $e) {
                // if we received a conflict exception we need to mark
                // the form as being in error and inform the view.
                $form->markAsError();
                $this->view->isConflict = true;
            }
        }

        // save failed validation - include errors in response
        if ($form->isErrors()) {
            $this->view->isValid     = false;
            $this->view->form        = $form;
            $this->view->errors      = array(
                'form'      => $form->getErrorMessages(),
                'elements'  => $form->getMessages()
            );
        }
    }

    /**
     * Delete content. Supports deleting of multiple content entries that will be deleted
     * in a batch - i.e. either all of them or none.
     *
     * List of entry ids to delete are passed in the 'ids' parameter, however the method
     * also accepts passing entry id(s) via 'id' parameter (this will have precedence if
     * both 'id and 'ids' parameters are present).
     *
     * Requires HTTP post request to perform delete. Traditional requests are redirected
     * to the index and a notification is set. Context specific requests are rendered using
     * the appropriate context view script.
     */
    public function deleteAction()
    {
        $request = $this->getRequest();

        // set up the view
        $form       = new Content_Form_Delete;
        $view       = $this->view;
        $view->form = $form;

        // populate the form from the request
        // support passing entry ids in both 'id' and/or 'ids' params,
        // ensure 'ids' param will get the value from 'id' if it was set
        $params        = $request->getParams();
        $params['ids'] = $request->getParam('id', $request->getParam('ids'));
        $form->populate($params);

        // if there are posted data, validate the form and delete selected entries
        if ($request->isPost()) {
            // if form is invalid, set response code and exit
            if (!$form->isValid($params)) {
                $this->getResponse()->setHttpResponseCode(400);
                $view->errors = $form->getMessages();
                return;
            }

            // get adapter for batch and fetch all entries to delete
            $adapter = P4Cms_Content::getDefaultAdapter();
            $entries = P4Cms_Content::fetchAll(array('ids' => (array) $form->getValue('ids')));

            // attempt to delete all specified entries in a batch
            $adapter->beginBatch($form->getValue('comment') ?: 'No description provided.');
            foreach ($entries as $entry) {
                try {
                    // enforce permissions
                    $this->acl->check('content/' . $entry->getId(), 'delete');

                    // delete content entry
                    $entry->delete();
                } catch (Exception $e) {
                    // cannot delete the entry; revert the batch, set the response code and exit
                    $adapter->revertBatch();
                    $this->getResponse()->setHttpResponseCode(400);
                    $view->message = $e->getMessage();
                    return;
                }
            }

            // commit batch
            $adapter->commitBatch();

            // clear any affected cached entries
            $tags       = array('p4cms_content_list');
            $deletedIds = $entries->invoke('getId');
            foreach ($deletedIds as $entryId) {
                $tags[] = 'p4cms_content_' . bin2hex($entryId);
            }
            P4Cms_Cache::clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $tags);

            // add notification and redirect to index for traditional requests
            if (!$this->contextSwitch->getCurrentContext()) {
                $message = 'Deleted ' . count($deletedIds)
                    . (count($deletedIds) === 1 ? ' content entry.' : ' content entries.');
                P4Cms_Notifications::add($message, P4Cms_Notifications::SEVERITY_SUCCESS);
                return $this->redirector->gotoSimple('index');
            }

            // pass list of deleted entries to the view
            $view->ids = $deletedIds;
        }
    }

    /**
     * Download the first image from a piece of content
     *
     * Looks at the type definition for the first 'imagefile' field of the
     * requested content entry. If it has a valid content serves it.
     *
     * If content type has no imagefile field, serves the content of the
     * first available 'file' field if it contains a valid image.
     *
     * Otherwise, forward to page-not-found.
     */
    public function imageAction()
    {
        // enforce permissions - deny if user doesn't have access permission.
        $this->acl->check('content', 'access');

        // get the content entry to download.
        $request = $this->getRequest();

        // if a version is present, generate rev-spec.
        $revspec = $request->getParam('version');
        $options = null;
        if ($revspec) {
            // enforce permissions - viewing historic versions requires 'access-history' privilege.
            $this->acl->check('content', 'access-history');
            $revspec = '#' . $revspec;
            $options = array('includeDeleted' => true);
        }

        // try to retreive the requested record and type; error out if not found
        try {
            $entry = P4Cms_Content::fetch($request->getParam('id') . $revspec, $options);
        } catch (Exception $e) {
            // we only have special handling for specific types; rethrow anything else
            if (!$e instanceof P4Cms_Record_NotFoundException
                && !$e instanceof InvalidArgumentException
            ) {
                throw $e;
            }

            return $this->_forward('page-not-found', 'index', 'error');
        }

        // check to see if there is an image element on the content type
        $image    = null;
        $elements = $entry->getContentType()->getFormElements();
        foreach ($elements as $element) {
            if ($element instanceof P4Cms_Form_Element_ImageFile) {
                $image = $element->getName();
                break;
            }
        }

        // if there was no image, check the file, which may be an image
        if (!$image && $entry->hasFileContentField()) {
            $image = $entry->getFileContentField();
        }

        // if request specifies a field, use that, otherwise
        // use the field that we detected above
        $image = $request->getParam('field', $image);

        // if we've found something, ensure it's an image and has content,
        // assuming that an empty element won't have mime data.
        // we also allow pdf files to be 'viewed as images' - support for
        // rendering pdf documents directly in the browser is pretty common
        if ($image) {
            $metadata = $entry->getFieldMetadata($image);
            $mimeType = isset($metadata['mimeType'])
                ? $metadata['mimeType']
                : null;

            if (strpos($mimeType, 'image/') !== 0 && $mimeType !== 'application/pdf') {
                $image = null;
            }
        }

        // tag the page cache so it can be appropriately cleared later
        if (P4Cms_Cache::canCache('page')) {
            P4Cms_Cache::getCache('page')
                ->addTag('p4cms_content_'      . bin2hex($entry->getId()))
                ->addTag('p4cms_content_type_' . bin2hex($entry->getContentTypeId()));
        }

        // if we didn't find a valid image, send a 404
        if (!$image) {
            $this->_forward('page-not-found', 'index', 'error');
            return;
        }

        // if we did find a valid image, let the download action handle it
        $request->setParam('field', $image);
        $this->downloadAction($entry, false);
    }

    /**
     * Download content.
     *
     * Serves the contents of the requested field or if no field is
     * specified, uses the file field. If the requested content entry
     * does not exist or has no suitable field, forwards to page-not-found.
     *
     * @param  P4Cms_Content  $entry     optional - entry instance to download
     * @param  boolean        $download  optional - indicate whether content should be downloaded
     *                                   defaults to true. influences content-disposition header.
     */
    public function downloadAction(P4Cms_Content $entry = null, $download = true)
    {
        // enforce permissions - deny if user doesn't have access permission.
        $this->acl->check('content', 'access');

        // get the content entry to download.
        $request = $this->getRequest();
        $id      = $request->getParam('id');

        // if a version is present, generate rev-spec.
        $revspec = $request->getParam('version');
        $options = null;
        if ($revspec) {
            // enforce permissions - viewing historic versions requires 'access-history' privilege.
            $this->acl->check('content', 'access-history');
            $revspec = '#' . $revspec;
            $options = array('includeDeleted' => true);
        }

        try {
            $entry = $entry ?: P4Cms_Content::fetch($id . $revspec, $options);
        } catch (Exception $e) {
            // we only have special handling for specific types; rethrow anything else
            if (!$e instanceof P4Cms_Record_NotFoundException
                && !$e instanceof InvalidArgumentException
            ) {
                throw $e;
            }

            return $this->_forward('page-not-found', 'index', 'error');
        }

        // tag the page cache so it can be appropriately cleared later
        if (P4Cms_Cache::canCache('page')) {
            P4Cms_Cache::getCache('page')
                ->addTag('p4cms_content_'      . bin2hex($entry->getId()))
                ->addTag('p4cms_content_type_' . bin2hex($entry->getContentTypeId()));
        }

        // determine what content field to deliver.
        // if the request specifies a field, serve it.
        // fallback to the entry's file content field.
        $field = $request->getParam('field', $entry->getFileContentField());

        // if there is still no field to serve, indicate 404.
        if (!$field) {
            $this->_forward('page-not-found', 'index', 'error');
            return;
        }

        // get entry data to download
        $data = $entry->getValue($field);

        // obtain file metadata.
        $metadata = $entry->getFieldMetadata($field);
        $filename = isset($metadata['filename']) ? $metadata['filename'] : null;
        $mimeType = isset($metadata['mimeType']) ? $metadata['mimeType'] : 'application/octet-stream';

        // if data represents an image, adjust it before sending to output
        if (strpos($mimeType, 'image/') === 0) {
            try {
                $data = $this->_adjustImage($data);
            } catch (Exception $e) {
                P4Cms_Log::log("Image adjust failed: " . $e->getMessage(), P4Cms_Log::WARN);
            }
        }

        $this->getResponse()->setHeader('Content-Type', $mimeType);
        if ($download) {
            $this->getResponse()->setHeader(
                'Content-Disposition',
                'attachment;' . ($filename ? ' filename="' . $filename . '"' : '')
            );
        }

        // if entry's field value is empty, render the page
        if (!$data) {
            return $this->viewAction($entry);
        }

        // disable autorendering for the download
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        print $data;
    }

    /**
     * Validate the passed field
     */
    public function validateFieldAction()
    {
        // force json context.
        $this->contextSwitch->initContext('json');

        // extract field/value from request.
        $request   = $this->getRequest();
        $field     = $request->getParam('field');
        $value     = $request->getParam('value');
        $contentId = $request->getParam('contentId', null);

        // enforce permissions.
        if ($contentId) {
            $this->acl->check('content' . '/' . $contentId, 'edit');
        } else {
            $this->acl->check('content', 'add');
        }

        // verify that field is specified
        if ($field == '') {
            throw new P4Cms_Content_Exception(
                "Cannot validate field - no field given."
            );
        }

        // fetch the type to ensure it is valid
        $type = P4Cms_Content_Type::fetch($request->getParam('contentType'));

        // setup an entry to get the display value from
        $entry = new P4Cms_Content;
        $entry->setContentType($type)
              ->setValue($field, $value);

        // get the content type and element definition.
        $type    = $entry->getContentType();
        $element = $type->getFormElement($field);

        // validate the field.
        $isValid = $element->isValid($value);

        // get the entry at the head revision
        $options    = array('includeDeleted' => true);
        $headEntry  = null;
        if ($contentId && P4Cms_Content::exists($contentId, $options)) {
            $headEntry = P4Cms_Content::fetch($contentId, $options);

            // update the opened info to reflect our edit/ping
            $user   = P4Cms_User::fetchActive();
            $record = new P4Cms_Content_Opened;
            $record->setAdapter(P4Cms_Site::fetchActive()->getStorageAdapter())
                   ->setId($contentId)
                   ->setUserPingTime($user)
                   ->setUserEditTime($user)
                   ->save();
        }

        // populate the view.
        $this->view->type           = $type;
        $this->view->entry          = $headEntry;
        $this->view->element        = $element;
        $this->view->fieldName      = $field;
        $this->view->fieldValue     = $value;
        $this->view->isValid        = $isValid;
        $this->view->errors         = $element->getMessages();
        $this->view->displayValue   = $entry->getDisplayValue($field);
    }

    /**
     * Get the view script to use for the given content entry.
     *
     * Searches view script paths for most specific template.
     *  1. index/view-entry-<id>.phtml
     *  2. index/view-type-<id>.phtml
     *  3. index/view.phtml
     *
     * @param   P4Cms_Content   $entry  the entry to be rendered.
     * @return  string          the name of the view script to use.
     *
     * @publishes   p4cms.content.view.scripts
     *              Return the passed scripts array, making any modifications (add, remove or change
     *              values), to influence the view script that ends up being selected for rendering.
     *              The first view script filename in the list that exists in the view's search
     *              paths gets used. Note that the filename should not include the suffix
     *              (typically ".phtml").
     *              array           $scripts    The list of view script filenames.
     *              P4Cms_Content   $entry      The content entry to render.
     */
    protected function _getEntryViewScript(P4Cms_Content $entry)
    {
        $scripts = array();

        // convention for entry ids.
        if ($entry->getId()) {
            $scripts[] = 'index/view-entry-'. $entry->getId();
        }

        // convention for content types.
        $scripts[] = 'index/view-type-'. $entry->getContentType()->getId();

        // let third-parties add to or alter the view script conventions
        $scripts = P4Cms_PubSub::filter(
            'p4cms.content.view.scripts',
            $scripts,
            $entry
        );

        // find the first template that exists among the possible scripts.
        $suffix = '.' . $this->_helper->viewRenderer->getViewSuffix();
        foreach ($scripts as $script) {
            if ($this->view->getScriptPath($script . $suffix)) {
                return basename($script);
            }
        }

        // no match, fallback to default view.
        return 'view';
    }

    /**
     * Get the layout script to use for the given content entry.
     * If the content type specifies a valid layout, returns it.
     * Otherwise, returns the current default layout.
     *
     * @param   P4Cms_Content   $entry  the entry to be rendered.
     * @return  string          the name of the layout script to use.
     */
    protected function _getEntryLayoutScript(P4Cms_Content $entry)
    {
        $layout = $entry->getContentType()->getLayout();
        $suffix = '.' . $this->_helper->viewRenderer->getViewSuffix();
        if ($layout && $this->view->getScriptPath($layout . $suffix)) {
            return $layout;
        }

        return $this->_helper->layout->getLayout();
    }

    /**
     * Creates the content form, passing in the formIdPrefix if present
     *
     * @param   P4Cms_Content   $entry  The content entry to make a form for.
     * @return  Content_Form_Content    The completed form.
     */
    protected function _getContentForm(P4Cms_Content $entry)
    {
        $options = array('entry' => $entry);

        // if the entry doesn't have a content type id, we can't build a proper form.
        // this is likely the case of a missing content type or missing attributes.
        if (!$entry->getContentTypeId()) {
            throw new Content_Exception("Cannot get content form. Content type is invalid or missing.");
        }

        // if request specifies an id prefix, add it to options
        $request = $this->getRequest();
        $options['idPrefix'] = $request->getParam('formIdPrefix');

        return new Content_Form_Content($options);
    }

    /**
     * Adjusts given image (represented by $imageData) according to the request params.
     *
     * Following request parameters are recognized:
     *  'width'       - target width in pixels; if not set, but 'height' is set,
     *                  then target width will be computed from 'height' such
     *                  that image keeps same aspect ratio as original
     *  'height'      - target height in pixels; if not set, but 'width' is set,
     *                  then target height will be computed from 'width' such
     *                  that image keeps same aspect ratio as original
     *  'maxWidth'    - maximum target width, image will be proportionally shrunk
     *                  if computed width is greater than this value
     *  'maxHeight'   - maximum target height, image will be proportionally shrunk
     *                  if computed height is greater than this value
     *  'sharpen'     - if set, image will be sharpened (applied after resizing)
     *
     * @param   string  $imageData  input image data
     * @return  string  adjusted image data
     */
    protected function _adjustImage($imageData)
    {
        $request    = $this->getRequest();
        $width      = (int) $request->getParam('width');
        $height     = (int) $request->getParam('height');
        $maxWidth   = (int) $request->getParam('maxWidth');
        $maxHeight  = (int) $request->getParam('maxHeight');
        $sharpen    = $request->getParam('sharpen');

        // early exit if nothing to apply
        if (!$width && !$height && !$maxWidth && !$maxHeight && !$sharpen) {
            return $imageData;
        }

        $image = new P4Cms_Image;
        $image->setData($imageData);

        $dimensions = $image->getDriver()->getImageSize();
        $ratio      = $dimensions['width'] / $dimensions['height'];

        // set target width and height:
        //  - set to original size if neither dimension was specified
        //  - if only one dimension was specified, compute the other one
        //    to keep the aspect ration of the original image
        if (!$width && !$height) {
            $width  = $dimensions['width'];
            $height = $dimensions['height'];
        } else if (!$width) {
            $width  = round($height * $ratio);
        } else if (!$height) {
            $height = round($width / $ratio);
        }

        // lower image dimensions if they exceed maximum dimensions (if provided)
        if ($maxHeight && $maxHeight < $height) {
            $width  = round($width * $maxHeight / $height);
            $height = $maxHeight;
        }
        if ($maxWidth && $maxWidth < $width) {
            $height = round($height * $maxWidth / $width);
            $width  = $maxWidth;
        }

        // resize image according to computed width and height (if they differ from original size)
        if ($width !== $dimensions['width'] || $height !== $dimensions['height']) {
            if ($width > static::MAX_SCALE || $height > static::MAX_SCALE) {
                throw new Content_Exception(
                    "Width or height exceeds maximum scale of " . static::MAX_SCALE . "px."
                );
            }

            $image->transform('scale', array($width, $height));
        }

        // sharpen output image if requested
        if ($sharpen) {
            $image->transform('sharpen');
        }

        return $image->getData();
    }
}