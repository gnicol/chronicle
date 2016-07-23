<?php
/**
 * Integrate the content module with the rest of the application.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Module extends P4Cms_Module_Integration
{
    /**
     * Perform early integration work (before load).
     */
    public static function init()
    {
        // install content types when a site is created or a module/theme is enabled.
        $installTypes = function(P4Cms_Site $site, $package = null)
        {
            $adapter = $site->getStorageAdapter();
            if ($package instanceof P4Cms_PackageAbstract) {
                P4Cms_Content_Type::installPackageDefaults($package, $adapter, true);
            } else {
                P4Cms_Content_Type::installDefaultTypes($adapter);
            }
        };

        // install content entries when a site is created or a module/theme is enabled.
        $installContent = function(P4Cms_Site $site, $package = null) 
        {
            $adapter = $site->getStorageAdapter();

            // if no specific package given, get all packages
            $packages = $package instanceof P4Cms_PackageAbstract
                ? new P4Cms_Model_Iterator(array($package))
                : P4Cms_Module::fetchAllEnabled();

            // add current theme to packages if no specific package given.
            if (!$package instanceof P4Cms_PackageAbstract && P4Cms_Theme::hasActive()) {
                $packages[] = P4Cms_Theme::fetchActive();
            }

            // install default content entries from each package.
            $adapter->beginBatch('Installed default content entries.');
            foreach ($packages as $package) {
                $info    = $package->getPackageInfo();
                $entries = isset($info['content']) && is_array($info['content'])
                    ? $info['content']
                    : array();

                foreach ($entries as $id => $entry) {
                    // skip existing content entries
                    if (P4Cms_Content::exists($id, null, $adapter)) {
                        continue;
                    }

                    $content = new P4Cms_Content($entry, $adapter);
                    $content->setId($id);

                    // if a file was specified, we assume it refers to a file
                    // under the package path and attempt to read it in.
                    $file = isset($entry['file'])
                          ? $package->getPath() . '/' . $entry['file']
                          : null;
                    if ($file && is_file($file)) {
                        $content->setValueFromFile('file', $file);
                    } else {
                        $content->unsetValue('file');
                    }

                    $content->save();
                }
            }
            $adapter->commitBatch();
        };

        // wire up type and content install events - note we install types
        // before content so that all types are available for use.
        P4Cms_PubSub::subscribe('p4cms.site.created',        $installTypes);
        P4Cms_PubSub::subscribe('p4cms.site.created',        $installContent);
        P4Cms_PubSub::subscribe('p4cms.site.theme.enabled',  $installTypes);
        P4Cms_PubSub::subscribe('p4cms.site.theme.enabled',  $installContent);
        P4Cms_PubSub::subscribe('p4cms.site.module.enabled', $installTypes);
        P4Cms_PubSub::subscribe('p4cms.site.module.enabled', $installContent);

        // update content types when a module/theme is disabled
        $removeTypes = function(P4Cms_Site $site, P4Cms_PackageAbstract $package)
        {
            $adapter = $site->getStorageAdapter();
            P4Cms_Content_Type::removePackageDefaults($package, $adapter);
        };

        P4Cms_PubSub::subscribe('p4cms.site.module.disabled', $removeTypes);
        P4Cms_PubSub::subscribe('p4cms.site.theme.disabled',  $removeTypes);

        // register content record class to participate in history,
        // diff and anything that needs to fetch records given type & id.
        P4Cms_PubSub::subscribe('p4cms.record.registeredTypes',
            function()
            {
                return P4Cms_Record_RegisteredType::create()
                    ->setId('content')
                    ->setRecordClass('P4Cms_Content')
                    ->setUriCallback(
                        function($id, $action, $params)
                        {
                            return call_user_func(
                                P4Cms_Content::getUriCallback(),
                                P4Cms_Content::fetch($id, array('includeDeleted' => true)),
                                $action,
                                $params
                            );
                        }
                    );
            }
        );

        // provide history grid actions when dealing with content records
        P4Cms_PubSub::subscribe('p4cms.history.grid.actions',
            function($type, $record, $actions)
            {
                if ($type->getId() != 'content') {
                    return;
                }

                $actions->addPages(
                    array(
                        array(
                            'label'     => 'View',
                            'onClick'   => 'p4cms.content.history.grid.Actions.onClickView();',
                            'order'     => '10',
                            'resource'  => 'content',
                            'privilege' => 'access'
                        ),
                        array(
                            'label'     => 'Diff Against Latest Version',
                            'onClick'   => 'p4cms.content.history.grid.Actions.onClickDiffLatest();',
                            'onShow'    => 'p4cms.content.history.grid.Actions.onShowDiffLatest(this);',
                            'order'     => '20',
                            'resource'  => 'content',
                            'privilege' => 'access'
                        ),
                        array(
                            'label'     => 'Diff Against Previous Version',
                            'onClick'   => 'p4cms.content.history.grid.Actions.onClickDiffPrevious();',
                            'onShow'    => 'p4cms.content.history.grid.Actions.onShowDiffPrevious(this);',
                            'order'     => '30',
                            'resource'  => 'content',
                            'privilege' => 'access'
                        ),
                        array(
                            'label'     => 'Diff Against Selected Version',
                            'onClick'   => 'p4cms.content.history.grid.Actions.onClickDiffSelected();',
                            'onShow'    => 'p4cms.content.history.grid.Actions.onShowDiffSelected(this);',
                            'order'     => '40',
                            'resource'  => 'content',
                            'privilege' => 'access'
                        ),
                        array(
                            'label'     => 'Rollback',
                            'onClick'   => 'p4cms.content.history.grid.Actions.onClickRollback();',
                            'onShow'    => 'p4cms.content.history.grid.Actions.onShowRollback(this);',
                            'order'     => '50',
                            'resource'  => 'content/' . $record->getId(),
                            'privilege' => 'edit'

                        )
                    )
                );
            }
        );

        // provide history toolbar actions when dealing with content records
        P4Cms_PubSub::subscribe('p4cms.history.toolbar.actions',
            function($type, $record, $actions)
            {
                if ($type->getId() != 'content') {
                    return;
                }

                $actions->addPages(
                    array(
                        array(
                            'label'     => 'Diff Against Latest Version',
                            'onClick'   => 'p4cms.content.history.toolbar.Actions.onClickDiffLatest();',
                            'onShow'    => 'p4cms.content.history.toolbar.Actions.onShowDiffLatest(this);',
                            'order'     => '20',
                            'resource'  => 'content',
                            'privilege' => 'access'
                        ),
                        array(
                            'label'     => 'Diff Against Previous Version',
                            'onClick'   => 'p4cms.content.history.toolbar.Actions.onClickDiffPrevious();',
                            'onShow'    => 'p4cms.content.history.toolbar.Actions.onShowDiffPrevious(this);',
                            'order'     => '30',
                            'resource'  => 'content',
                            'privilege' => 'access'
                        ),
                        array(
                            'label'     => 'Rollback',
                            'onClick'   => 'p4cms.content.history.toolbar.Actions.onClickRollback();',
                            'onShow'    => 'p4cms.content.history.toolbar.Actions.onShowRollback(this);',
                            'order'     => '50',
                            'resource'  => 'content/' . $record->getId(),
                            'privilege' => 'edit'

                        )
                    )
                );
            }
        );

        // provide manage content actions
        P4Cms_PubSub::subscribe('p4cms.content.grid.actions',
            function($actions)
            {
                $actions->addPages(
                    array(
                        array(
                            'label'     => 'View',
                            'onClick'   => 'p4cms.content.grid.Actions.onClickView();',
                            'onShow'    => 'p4cms.content.grid.Actions.onShowView(this);',
                            'order'     => '10',
                            'resource'  => 'content',
                            'privilege' => 'access'
                        ),
                        array(
                            'label'     => 'View in a New Window',
                            'onClick'   => 'p4cms.content.grid.Actions.onClickView(true);',
                            'onShow'    => 'p4cms.content.grid.Actions.onShowView(this);',
                            'order'     => '20',
                            'resource'  => 'content',
                            'privilege' => 'access'
                        ),
                        array(
                            'label'     => 'Edit',
                            'onClick'   => 'p4cms.content.grid.Actions.onClickEdit();',
                            'onShow'    => 'p4cms.content.grid.Actions.onShowEdit(this);',
                            'order'     => '30',
                            'resource'  => 'content',
                            'privilege' => 'edit'
                        ),
                        array(
                            'label'     => 'History',
                            'onClick'   => 'p4cms.content.grid.Actions.onClickHistory();',
                            'onShow'    => 'p4cms.content.grid.Actions.onShowHistory(this);',
                            'order'     => '40',
                            'resource'  => 'content',
                            'privilege' => 'access-history'
                        ),
                        array(
                            'label'     => 'Delete',
                            'onClick'   => 'p4cms.content.grid.Actions.onClickDelete();',
                            'onShow'    => 'p4cms.content.grid.Actions.onShowDelete(this);',
                            'order'     => '50',
                            'resource'  => 'content',
                            'privilege' => 'delete'
                        )
                    )
                );
            }
        );

        // contribute the list of content as a dynamic menu item.
        P4Cms_PubSub::subscribe('p4cms.navigation.dynamicHandlers',
            function()
            {
                $handler = new P4Cms_Navigation_DynamicHandler;
                $handler->setId('content')
                        ->setLabel('Content Listing')
                        ->setExpansionCallback(
                            function($item, $options)
                            {
                                // if current user is not allowed to access
                                // content, return empty array.
                                if (P4Cms_User::hasActive()) {
                                    $user = P4Cms_User::fetchActive();
                                    if (!$user->isAllowed('content', 'access')) {
                                        return array();
                                    }
                                }

                                // get content.
                                $menu    = array();
                                $entries = P4Cms_Content::fetchAll(
                                    P4Cms_Record_Query::create()
                                    ->setMaxRows($options[P4Cms_Menu::MENU_MAX_ITEMS])
                                );
                                foreach ($entries as $entry) {
                                    $menu[] = array(
                                        'label'         => $entry->getTitle(),
                                        'uri'           => $entry->getUri(),
                                        'expansionId'   => $entry->getId()
                                    );
                                }

                                return $menu;
                            }
                        )->setFormCallback(
                            function(Zend_Form $form)
                            {
                                // we are a flat list so remove the depth and root options
                                $removals = array(
                                    P4Cms_Menu::MENU_MAX_DEPTH,
                                    P4Cms_Menu::MENU_ROOT
                                );

                                array_map(array($form, 'removeElement'), $removals);

                                return $form;
                            }
                        );

                return array($handler);
            }
        );

        // Set the function to use when generating URIs to access content.
        P4Cms_Content::setUriCallback(
            function($content, $action, $params)
            {
                // all actions share some params.
                $routeParams = array(
                    'module'        => 'content',
                    'controller'    => 'index',
                    'action'        => $action
                );

                // if given valid content, add in the type id or content id as appropriate
                if ($content instanceof P4Cms_Content) {
                    if ($action == 'add') {
                        $routeParams['type'] = $content->getValue(P4Cms_Content::TYPE_FIELD);
                    } else {
                        $routeParams['id']   = $content->getId();
                    }
                }

                // merge in caller-provided params.
                $routeParams = array_merge($routeParams, $params);

                $router = Zend_Controller_Front::getInstance()->getRouter();
                $uri    = $router->assemble($routeParams, 'default');

                // append human-friendly title if the id is numeric
                // and the title differs from the id.
                $id    = $content->getId();
                $title = $content->getTitle();
                if (is_numeric($id) && $title != $id) {
                    $title = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
                    $title = trim($title, '-');
                    $uri  .= $title ? '/' . $title : null;
                }

                return $uri;
            }
        );

        // filter content list query.
        P4Cms_PubSub::subscribe('p4cms.content.grid.populate',
            function(P4Cms_Record_Query $query, Zend_Form $form)
            {
                // get type sub-form.
                $typeForm = $form->getSubForm('type');
                if (!$typeForm instanceof Content_Form_GridTypeFilter) {
                    return;
                }

                // filter for the selected types.
                $types = $typeForm->getElement('types')->getNormalizedTypes();
                if (count($types)) {
                    $filter = new P4Cms_Record_Filter;
                    $filter->add(
                        'contentType',
                        $types,
                        P4Cms_Record_Filter::COMPARE_EQUAL
                    );
                    $query->addFilter($filter);
                }
            }
        );

        // provide form to filter content list by type.
        P4Cms_PubSub::subscribe('p4cms.content.grid.form.subForms',
            function(Zend_Form $form)
            {
                return new Content_Form_GridTypeFilter;
            }
        );

        // when the content list form populates, make sure content type
        // checkbox selections respect the type-group/type hierarchy.
        P4Cms_PubSub::subscribe('p4cms.content.grid.form.populate',
            function(Zend_Form $form, $values)
            {
                // extract selected types.
                $selected = isset($values['type']['types'])
                    ? $values['type']['types']
                    : array();

                // early exit if no types element.
                if (!($subForm = $form->getSubForm('type'))
                    || !($element = $subForm->getElement('types'))
                ) {
                    return;
                }

                // ensure selected options respect the hierarchy.
                $options = $element->getMultiOptions();
                foreach ($options as $key => $option) {

                    // if a type group is selected, select all of it's sub-types.
                    if (!is_array($option) && in_array($key, $selected)) {
                        $selected = array_merge($selected, array_keys($options[substr($key, 0, -1)]));
                    }

                    // if all of the types in a group are selected, select the group.
                    if (is_array($option)) {
                        $allSelected = true;
                        foreach ($option as $subKey => $label) {
                            if (!in_array($subKey, $selected)) {
                                $allSelected = false;
                            }
                        }
                        if ($allSelected) {
                            $selected[] = $key . "*";
                        }
                    }
                }

                // update element value.
                $element->setValue($selected);
            }
        );

        // provide form to show/hide/only deleted content
        P4Cms_PubSub::subscribe('p4cms.content.grid.form.subForms',
            function(Zend_Form $form)
            {
                $options = array(
                    ''     => 'Hide Deleted',
                    'show' => 'Show Deleted',
                    'only' => 'Only Show Deleted'
                );

                $form = new P4Cms_Form_SubForm;
                $form->setName('deleted')
                     ->setAttrib('class', 'types-form')
                     ->setOrder(20)
                     ->addElement(
                        'Radio', 'display',
                        array(
                            'label'         => 'Deleted',
                            'multiOptions'  => $options,
                            'autoApply'     => true,
                            'order'         => 20,
                            'value'         => ''
                        )
                     );

                return $form;
            }
        );

        // touch up the query to reflect our display of deleted preference
        P4Cms_PubSub::subscribe('p4cms.content.grid.populate',
            function(P4Cms_Record_Query $query, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract selected types.
                $display = isset($values['deleted']['display'])
                    ? $values['deleted']['display']
                    : '';

                switch ($display) {
                    case 'only':
                        $query->addFilter(
                            P4Cms_Record_Filter::create()->addFstat(
                                'headAction',
                                '.*delete',
                                P4Cms_Record_Filter::COMPARE_REGEX
                            )
                        );
                    // in either case, include deleted
                    case 'show':
                        $query->setIncludeDeleted(true);
                }
            }
        );

        // provide form to filter content type list by search term.
        P4Cms_PubSub::subscribe('p4cms.content.grid.form.subForms',
            function(Zend_Form $form)
            {
                return new Ui_Form_GridSearch;
            }
        );

        // filter content list by search term
        P4Cms_PubSub::subscribe('p4cms.content.grid.populate',
            function(P4Cms_Record_Query $query, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract search query.
                $keywords = isset($values['search']['query'])
                    ? $values['search']['query']
                    : null;

                // early exit if no search text.
                if (!$keywords) {
                    return;
                }

                // add a text search filter to the content type query.
                $filter = new P4Cms_Record_Filter;
                $filter->addSearch('title', $keywords);
                $query->addFilter($filter);
            }
        );

        // provide a paginator of existing content entries for search index rebuild.
        P4Cms_PubSub::subscribe('p4cms.search.index.rebuild',
            function()
            {
                $query      = new P4Cms_Record_Query(array('recordClass' => 'P4Cms_Content'));
                $adapter    = new P4Cms_Record_PaginatorAdapter($query);
                $paginator  = new Zend_Paginator($adapter);

                $paginator->setCurrentPageNumber(0);
                $paginator->setItemCountPerPage(100);

                return $paginator;
            }
        );

        // provide form to filter content type list by search term.
        P4Cms_PubSub::subscribe('p4cms.content.type.grid.form.subForms',
            function(Zend_Form $form)
            {
                return new Ui_Form_GridSearch;
            }
        );

        // filter content type list by search term
        P4Cms_PubSub::subscribe('p4cms.content.type.grid.populate',
            function(P4Cms_Record_Query $query, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract search query.
                $keywords = isset($values['search']['query'])
                    ? $values['search']['query']
                    : null;

                // early exit if no search text.
                if (!$keywords) {
                    return;
                }

                // add a text search filter to the content type query.
                $filter = new P4Cms_Record_Filter;
                $fields = array('label', 'group', 'description');
                $filter->addSearch($fields, $keywords);
                $query->addFilter($filter);
            }
        );

        /**
         * provide form to filter by content type groups
         */
        P4Cms_PubSub::subscribe('p4cms.content.type.grid.form.subForms',
            function(Zend_Form $form)
            {
                // list all of the content type groups.
                // returns an array with group name as key and an array of
                // content types belonging to that group as values
                $groups = P4Cms_Content_Type::fetchGroups();

                // early exit if no groups defined.
                if (!count($groups)) {
                    return;
                }

                $options = array_combine(array_keys($groups), array_keys($groups));

                $form = new P4Cms_Form_SubForm;
                $form->setName('group')
                     ->setAttrib('class', 'types-form')
                     ->setOrder(20)
                     ->addElement(
                        'MultiCheckbox', 'groups',
                        array(
                            'label'         => 'Group',
                            'multiOptions'  => $options,
                            'autoApply'     => true,
                            'order'         => 10
                        )
                     );

                return $form;
            }
        );

        // filter/sort content type list query.
        P4Cms_PubSub::subscribe('p4cms.content.type.grid.populate',
            function(P4Cms_Record_Query $query, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract selected groups.
                $groups = isset($values['group']['groups'])
                    ? $values['group']['groups']
                    : array();

                // add record filter to limit by type.
                if (is_array($groups) && count($groups)) {
                    $filter = new P4Cms_Record_Filter;
                    $filter->add('group', $groups, P4Cms_Record_Filter::COMPARE_EQUAL);
                    $query->addFilter($filter);
                }
            }
        );

        // sort content type list query.
        P4Cms_PubSub::subscribe('p4cms.content.type.grid.populate',
            function(P4Cms_Record_Query $query, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract the sort field and order.
                if (!empty($values['sort'])) {
                    // if the datagrid prepends a dash to a field name,
                    // that indicates that the sort should be in descending order.
                    $sortBy = preg_replace('/^-/', '', $values['sort']);
                    $options = ($sortBy === $values['sort'])
                        ? array(P4Cms_Record_Query::SORT_ASCENDING)
                        : array(P4Cms_Record_Query::SORT_DESCENDING);

                    // if we're sorting via an internal option, use the traditional
                    // syntax by knocking out the query options and reversing the
                    // results if necessary.
                    if (strpos($sortBy, '#') === 0) {
                        $options = null;
                        $query->setReverseOrder($sortBy === $values['sort'] ? false : true);
                    }
                    $query->setSortBy($sortBy, $options);
                } else {
                    $query->setSortBy('label');
                }
            }
        );

        // provide history list actions when dealing with content records
        P4Cms_PubSub::subscribe('p4cms.content.type.grid.actions',
            function($actions)
            {
                $actions->addPages(
                    array(
                        array(
                            'label'     => 'Edit',
                            'onClick'   => 'p4cms.content.type.grid.Actions.onClickEdit();',
                            'order'     => '10'
                        ),
                        array(
                            'label'     => 'Delete',
                            'onClick'   => 'p4cms.content.type.grid.Actions.onClickDelete();',
                            'order'     => '20'
                        ),
                        array(
                            'label'     => '-',
                            'onShow'    => 'p4cms.content.type.grid.Actions.onShowAddContent(this);',
                            'order'     => '30'
                        ),
                        array(
                            'label'     => 'Add Content',
                            'onClick'   => 'p4cms.content.type.grid.Actions.onClickAddContent();',
                            'onShow'    => 'p4cms.content.type.grid.Actions.onShowAddContent(this);',
                            'order'     => '40'
                        )
                    )
                );
            }
        );

        // influence how content entries are diffed.
        P4Cms_PubSub::subscribe('p4cms.diff.options',
            function($options, $type, $left, $right)
            {
                // we're only concerned with content entries.
                if (!$left instanceof P4Cms_Content || !$right instanceof P4Cms_Content) {
                    return;
                }

                // iterate over elements defined in the content type
                // and extract information to augment comparison and
                // presentation of differences.
                $fields = array_merge(
                    $left->getContentType()->getElements(),
                    $right->getContentType()->getElements()
                );
                foreach ($fields as $name => $element) {

                    // prime options for this field.
                    if (!isset($options[$name]) || !$options[$name] instanceof P4Cms_Diff_Options) {
                        $options[$name] = new P4Cms_Diff_Options;
                    }

                    // set the label from the content type definition
                    // this won't influence the compare, but it will
                    // be picked up by the view script.
                    if (isset($element['options']['label'])) {
                        $options[$name]->setOption('label', $element['options']['label']);
                    }

                    // diff viewer relies on mime-type to know which comparison
                    // modes to use - look at left and right to detect mime-type
                    // and set the mime-type as a diff option for diff viewer.
                    foreach (array($left, $right) as $entry) {
                        if ($entry->hasField($name)) {
                            $metadata   = $entry->getFieldMetadata($name);
                            $definition = $entry->getContentType()->getElement($name);
                        } else {
                            $metadata   = array();
                            $definition = array();
                        }

                        // if field has a mime-type, set on diff options and check for binary diff.
                        // else if field type is editor, assume text/html
                        if (isset($metadata['mimeType'])) {
                            $options[$name]->setOption('mimeType', $metadata['mimeType']);
                            if (strpos($metadata['mimeType'], 'text/') !== 0) {
                                $options[$name]->setBinaryDiff(true);
                            }
                        } elseif (isset($definition['type']) && $definition['type'] == 'editor') {
                            $options[$name]->setOption('mimeType', 'text/html');
                        }
                    }
                }

                // mark non-content-type fields as to-be-skipped from diff
                foreach (array_merge($left->getFields(), $right->getFields()) as $field) {
                    if (isset($options[$field])) {
                        continue;
                    }
                    $options[$field] = new P4Cms_Diff_Options;
                    $options[$field]->setSkipped(true);
                }
            }
        );

        // help organize content-related records when pulling changes.
        P4Cms_PubSub::subscribe(
            'p4cms.site.branch.pull.groupPaths',
            function($paths, $source, $target, $result)
            {
                // make a umbrella group for all content related entries.
                $content = new Site_Model_PullPathGroup(
                    array(
                        'label'         => 'Content',
                        'order'         => -100,
                        'pullByDefault' => true
                    )
                );
                $paths->addSubGroup($content);

                // make sub-groups to hold content entries and types.
                // we expect the workflow module will later replace this
                // with seperate groups for published/unpublished entries
                $content->addSubGroup(
                    array(
                        'label'         => 'Entries',
                        'basePaths'     => $target->getId() . '/content/...',
                        'inheritPaths'  => $target->getId() . '/content/...',
                        'pullByDefault' => true,
                        'details'       =>
                            function($paths) use ($source, $target)
                            {
                                $pathsById = array();
                                foreach ($paths as $path) {
                                    if (strpos($path->depotFile, $target->getId() . '/content/') === 0) {
                                        $pathsById[P4Cms_Content::depotFileToId($path->depotFile)] = $path;
                                    }
                                }

                                $details = new P4Cms_Model_Iterator;
                                $entries = Site_Model_PullPathGroup::fetchRecords(
                                    array_keys($pathsById), 'P4Cms_Content', $source, $target
                                );
                                foreach ($entries as $entry) {
                                    $path      = $pathsById[$entry->getId()];
                                    $details[$entry->getId()] = new P4Cms_Model(
                                        array(
                                            'conflict' => $path->conflict,
                                            'action'   => $path->action,
                                            'label'    => $entry->getTitle()
                                        )
                                    );
                                }

                                $details->setProperty(
                                    'columns',
                                    array('label' => 'Title', 'action' => 'Action')
                                );

                                return $details;
                            }
                    )
                );
                $content->addSubGroup(
                    array(
                        'label'         => 'Types',
                        'basePaths'     => $target->getId() . '/content-types/...',
                        'inheritPaths'  => $target->getId() . '/content-types/...',
                        'pullByDefault' => true,
                        'details'       =>
                            function($paths) use ($source, $target)
                            {
                                $pathsById = array();
                                foreach ($paths as $path) {
                                    if (strpos($path->depotFile, $target->getId() . '/content-types/') === 0) {
                                        $pathsById[P4Cms_Content_Type::depotFileToId($path->depotFile)] = $path;
                                    }
                                }

                                $details = new P4Cms_Model_Iterator;
                                $entries = Site_Model_PullPathGroup::fetchRecords(
                                    array_keys($pathsById), 'P4Cms_Content_Type', $source, $target
                                );
                                foreach ($entries as $entry) {
                                    $path      = $pathsById[$entry->getId()];
                                    $details[] = new P4Cms_Model(
                                        array(
                                            'conflict' => $path->conflict,
                                            'action'   => $path->action,
                                            'label'    => $entry->getLabel()
                                        )
                                    );
                                }

                                $details->setProperty(
                                    'columns',
                                    array('label' => 'Type', 'action' => 'Action')
                                );

                                return $details;
                            }
                    )
                );
            }
        );

        /**
         * ensure search has a chance at updating post pull
         *
         * The p4cms.search.delete and update events published by this callback
         * are documented in P4Cms_Content.
         */
        P4Cms_PubSub::subscribe(
            'p4cms.site.branch.pull.postSubmit',
            function($paths, $target, $source, $adapter)
            {
                $paths = $paths->getPaths($paths::RECURSIVE);
                $paths = $paths->filter(
                    'depotFile',
                    $target->getId() . '/content/',
                    array($paths::FILTER_STARTS_WITH, $paths::FILTER_COPY)
                );

                // nothing to do if no content
                if (!$paths->count()) {
                    return;
                }

                // determine ids and fetch the affected content records
                $ids = array();
                foreach ($paths as $path) {
                    $ids[] = P4Cms_Content::depotFileToId($path->depotFile, $adapter);
                }
                $records = P4Cms_Content::fetchAll(
                    array(
                        'ids'            => $ids,
                        'includeDeleted' => true
                    ),
                    $adapter
                );

                // publish a delete or update event for any interested search indexers
                foreach ($records as $record) {
                    $topic = "p4cms.search." . ($record->isDeleted() ? 'delete' : 'update');
                    P4Cms_PubSub::publish($topic, $record);
                }
            }
        );
    }

    /**
     * Perform integration operations when the site is loaded.
     */
    public static function load()
    {
        // if the user has add permission, make the page body an upload 'drop-zone'.
        if (P4Cms_User::hasActive() && P4Cms_User::fetchActive()->isAllowed('content', 'add')) {
            $view = Zend_Layout::getMvcInstance()->getView();
            $view->dojo()->addOnLoad(
                "function(){
                    var dropZone = new p4cms.content.dnd.DropZone({node: dojo.body()});
                }"
            );
        }
    }
}
