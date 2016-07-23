<?php
/**
 * Integrate the category module with the rest of the application.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Category_Module extends P4Cms_Module_Integration
{
    /**
     * Subscribe to the relevant topics.
     */
    public static function init()
    {
        // contribute the list of categories as a dynamic menu item.
        P4Cms_PubSub::subscribe('p4cms.navigation.dynamicHandlers',
            function()
            {
                $handler = new P4Cms_Navigation_DynamicHandler;
                $handler->setId('categories')
                        ->setLabel('Category Listing')
                        ->setExpansionCallback('Category_Module::expandDynamicMenu')
                        ->setFormCallback(
                            function(Zend_Form $form)
                            {
                                // add a field to control if content entries in categories
                                // are included when dynamic category menu items are expanded.
                                $form->addElement(
                                    'checkbox',
                                    'includeEntries',
                                    array(
                                        'label'         => 'Include Content',
                                        'description'   => "Include content entries in categories.",
                                        'checked'       => false
                                    )
                                );

                                return $form;
                            }
                        );

                return array($handler);
            }
        );

        // participate in content editing by providing a subform.
        P4Cms_PubSub::subscribe('p4cms.content.form.subForms',
            function(Content_Form_Content $form)
            {
                // enforce permissions.
                $user = P4Cms_User::fetchActive();
                if (!$user->isAllowed('categories', 'associate')) {
                    return;
                }

                return new Category_Form_Content(
                    array(
                        'name'      => 'category',
                        'idPrefix'  => $form->getIdPrefix(),
                        'order'     => -30
                    )
                );
            }
        );

        // participate in content editing by populating categories sub-form.
        P4Cms_PubSub::subscribe('p4cms.content.form.populate',
            function(Content_Form_Content $form, array $values)
            {
                // nothing to do if no category sub-form.
                if (!$form->getSubForm('category')) {
                    return;
                }

                // pull categories from entry if not in values array.
                $categories = array();
                if (isset($values['category']['categories'])) {
                    $categories = $values['category']['categories'];
                } else {
                    $id = $form->getEntry()->getId();
                    if ($id !== null) {
                        $categories = Category_Model_Category::fetchIdsByEntry($id);
                    }
                }
                $form->getSubForm('category')
                     ->getElement('categories')
                     ->setValue($categories);
            }
        );

        // participate in content editing - don't store categories on content
        // directly, store category associations in category records instead.
        P4Cms_PubSub::subscribe('p4cms.content.record.preSave',
            function(P4Cms_Content $entry)
            {
                // move category out of fields list and into a public
                // property so it doesn't get saved with the record
                if ($entry->hasField('category')) {
                    $category = $entry->getValue('category');
                    $entry->unsetValue('category');
                    $entry->category = $category;
                }
            }
        );
        P4Cms_PubSub::subscribe('p4cms.content.record.postSave',
            function(P4Cms_Content $entry)
            {
                // if no category data present, no category changes required.
                if (!isset($entry->category)) {
                    return;
                }

                // pull off the latest category associations.
                $categories = isset($entry->category['categories'])
                    ? $entry->category['categories']
                    : array();

                Category_Model_Category::setEntryCategories($entry->getId(), $categories);
            }
        );

        // provide form to filter content by category.
        P4Cms_PubSub::subscribe('p4cms.content.grid.form.subForms',
            function(Zend_Form $form)
            {
                // enforce permissions.
                $user = P4Cms_User::fetchActive();
                if (!$user->isAllowed('categories', 'access')) {
                    return;
                }

                $form = new Category_Form_Content;
                $form->setName('category')
                     ->setOrder(15)
                     ->setLegend(null)
                     ->removeElement('addCategory');

                $categories = $form->getElement('categories');
                $categories->setLabel('Category')
                           ->setAttrib('autoApply', true);

                // if there are no categories, don't return form.
                if (!count($categories->getMultiOptions())) {
                    return null;
                }

                return $form;
            }
        );

        // filter content list by category.
        P4Cms_PubSub::subscribe('p4cms.content.grid.populate',
            function(P4Cms_Record_Query $query, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract selected categories.
                $categories = isset($values['category']['categories'])
                    ? $values['category']['categories']
                    : null;

                // early exit if no categories selected.
                if (!is_array($categories) || !count($categories)) {
                    return;
                }

                // check for existing 'categories' options and intersect if found
                $filter = $query->getFilter() ?: new P4Cms_Record_Filter;
                if (is_array($filter->getOption('categories'))) {
                    $categories = array_intersect($categories, $filter->getOption('categories'));
                }

                // The category module subscribes to the p4cms.content.record.query topic
                // so we can just add our categories to the query here and they will be
                // filtered when the query is executed.
                $filter->setOption('categories', $categories);
                $query->setFilter($filter);
            }
        );

        // provide category tree actions
        P4Cms_PubSub::subscribe('p4cms.category.grid.actions',
            function($actions)
            {
                $actions->addPages(
                    array(
                        array(
                            'label'     => 'View',
                            'onClick'   => 'p4cms.category.grid.Actions.onClickView();',
                            'order'     => '10'
                        ),
                        array(
                            'label'     => 'Edit',
                            'onClick'   => 'p4cms.category.grid.Actions.onClickEdit();',
                            'order'     => '20'
                        ),
                        array(
                            'label'     => 'Delete',
                            'onClick'   => 'p4cms.category.grid.Actions.onClickDelete();',
                            'order'     => '30'
                        )
                    )
                );
            }
        );

        // provide form to search categories
        P4Cms_PubSub::subscribe('p4cms.category.grid.form.subForms',
            function(Zend_Form $form)
            {
                return new Ui_Form_GridSearch;
            }
        );

        // filter categories by keyword search
        P4Cms_PubSub::subscribe('p4cms.category.grid.populate',
            function(P4Cms_Model_Iterator $categories, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract search query.
                $query = isset($values['search']['query'])
                    ? $values['search']['query']
                    : null;

                // early exit if no query.
                if (!$query) {
                    return null;
                }

                // filter categories by the search query
                $categories->search('title', $query);
            }
        );

        // provide form to filter categories by number of entries
        P4Cms_PubSub::subscribe('p4cms.category.grid.form.subForms',
            function(Zend_Form $form)
            {
                $options = array(
                    ''     => 'Any Number',
                    'more' => 'One or More',
                    'none' => 'None'
                );

                $form = new P4Cms_Form_SubForm;
                $form->setName('entriesCount')
                     ->setAttrib('class', 'types-form')
                     ->setOrder(20)
                     ->addElement(
                        'Radio', 'display',
                        array(
                            'label'         => 'Number of Entries',
                            'multiOptions'  => $options,
                            'autoApply'     => true,
                            'order'         => 20,
                            'value'         => ''
                        )
                     );

                return $form;
            }
        );

        // touch up the query to reflect our display of display empty categories
        P4Cms_PubSub::subscribe('p4cms.category.grid.populate',
            function(P4Cms_Model_Iterator $categories, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract selected types.
                $display = isset($values['entriesCount']['display'])
                    ? $values['entriesCount']['display']
                    : '';

                // early exit if no display restrictions
                if (!$display) {
                    return null;
                }

                // helper function to determine whether the category passes the display filter
                // @return boolean true if category passes the filter, false otherwise
                $passesFilter = function (Category_Model_Category $category, $display)
                {
                    return ($display === 'more' && $category->getEntries())
                        || ($display === 'none' && !$category->getEntries());
                };

                // get list with categories matching the search query
                $categories->filterByCallback($passesFilter, $display);
            }
        );

        // subscribe to the record query topic
        P4Cms_PubSub::subscribe('p4cms.content.record.query',
            function(P4Cms_Record_Query $query, P4Cms_Record_Adapter $adapter)
            {
                // are we filtering by categories?
                $filter = $query->getFilter();
                if (!$filter) {
                    return;
                }

                $categoryIds = $filter->getOption('categories');
                if (!$categoryIds || (!is_string($categoryIds) && !is_array($categoryIds))) {
                    return;
                }
                if (is_string($categoryIds)) {
                    $categoryIds = (array) $categoryIds;
                }

                // get entries for selected category ids.
                $ids = array();
                foreach ($categoryIds as $category) {
                    try {
                        $category = Category_Model_Category::fetch($category);
                        $ids = array_merge($ids, $category->getEntries());
                    } catch (P4Cms_Model_NotFoundException $e) {
                        continue;
                    }
                }

                // filter query to matching entries.
                $query->addPaths($ids, true);
            }
        );

        // organize category records when pulling changes.
        P4Cms_PubSub::subscribe(
            'p4cms.site.branch.pull.groupPaths',
            function($paths, $source, $target, $result)
            {
                $paths->addSubGroup(
                    array(
                        'label'         => 'Categories',
                        'basePaths'     => $target->getId() . '/categories/...',
                        'inheritPaths'  => $target->getId() . '/categories/...',
                        'pullByDefault' => true,
                        'count'         =>
                            function($group, $count, $options) use ($target)
                            {
                                $categories = array_map(
                                    'dirname',
                                    $group->getPaths($options)->invoke('getValue', array('depotFile'))
                                );

                                return count(array_unique($categories));
                            },
                        'details'       =>
                            function($paths) use ($source, $target)
                            {
                                $ids     = array();
                                $details = new P4Cms_Model_Iterator;
                                foreach ($paths as $path) {
                                    $entry    = basename($path->depotFile);
                                    $category = dirname($path->depotFile)
                                                . '/' . Category_Model_Category::CATEGORY_FILENAME;
                                    $id       = Category_Model_Category::depotFileToId($category);

                                    // add some defaults for this category if this is the
                                    // first time we have seen it
                                    if (!isset($details[$id])) {
                                        $ids[]        = $id;
                                        $details[$id] = new P4Cms_Model(
                                            array(
                                                'conflict' => false,
                                                'action'   => 'edit'
                                            )
                                        );
                                    }

                                    // if we have the actual category index file we should
                                    // use its action for our details entry
                                    if ($entry == Category_Model_Category::CATEGORY_FILENAME) {
                                        $details[$id]->setValue('action', $path->action);
                                    }

                                    // if any of the paths in this entry are in conflict the
                                    // whole category needs to be flagged as a conflict
                                    if ($path->conflict) {
                                        $details[$id]->setValue('conflict', $path->conflict);
                                    }
                                }

                                // fetch all of the categories in one go and set their
                                // titles onto the associated details entries
                                $entries = Site_Model_PullPathGroup::fetchRecords(
                                    $ids, 'Category_Model_Category', $source, $target
                                );
                                foreach ($entries as $entry) {
                                    $details[$entry->getId()]->setValue('label', $entry->getTitle());
                                }

                                $details->setProperty(
                                    'columns',
                                    array('label' => 'Category', 'action' => 'Action')
                                );

                                return $details;
                            }
                    )
                );
            }
        );
    }

    /**
     * Expand a dynamic category menu item.
     *
     * @param   P4Cms_Navigation_Page_Dynamic       $item           the dynamic item to be expanded.
     * @param   array                               $options        options (hints) to influence expansion.
     * @return  array                               the replacement menu items.
     */
    public static function expandDynamicMenu($item, $options)
    {
        // if current user is not allowed to access categories, return empty array.
        if (P4Cms_User::hasActive()) {
            $user = P4Cms_User::fetchActive();
            if (!$user->isAllowed('categories', 'access')) {
                return array();
            }
        }

        // if options specify max-items, only fetch the specified number of records.
        $query = new P4Cms_Record_Query;
        $query->setSortBy(array('title'));

        // if options specify max-depth, only fetch up to max depth.
        if ($options[P4Cms_Menu::MENU_MAX_DEPTH] !== null) {
            $query->setMaxDepth($options[P4Cms_Menu::MENU_MAX_DEPTH]);
        }

        // if options specify menu root, only search under the given path.
        $root = $options[P4Cms_Menu::MENU_ROOT];
        if ($root) {
            // increment max-depth to account for added depth of root.
            if ($query->getMaxDepth() !== null) {
                $query->setMaxDepth($query->getMaxDepth() + substr_count($root, '/') + 1);
            }

            // make the root inclusive, outside filtering needs the specified root to
            // be present to make it happy. The root itself will be removed by the caller.
            $query->addPaths(array($root, $root .'/...'));
        }

        // take the flat list of all categories and transform it into
        // a multi-dimensional array of navigation page entries.
        $categories = Category_Model_Category::fetchAll($query);
        $container  = array();
        foreach ($categories as $category) {
            // scan down the list of ancestors and create empty entries for any missing ones
            $parentContainer =& $container;
            foreach ($category->getAncestorIds() as $ancestorId) {
                // skip any ancestor IDs which are not under our filtered root
                if ($root && strpos($ancestorId, $root) !== 0) {
                    continue;
                }

                // make a stub entry if we haven't found the ancestor yet
                // this entry will be replaced when we later encounter it
                if (!array_key_exists($ancestorId, $parentContainer)) {
                    $parentContainer[$ancestorId] = array('pages' => array());
                }

                // keep drilling down on the parentContainer pointer
                $parentContainer =& $parentContainer[$ancestorId]['pages'];
            }

            // if we have a 'stub' entry, cache the pages and remove it;
            // removing it ensures the correct order is maintained.
            $id    = $category->getId();
            $pages = array();
            if (isset($parentContainer[$id]['pages'])) {
                $pages = $parentContainer[$id]['pages'];
                unset($parentContainer[$id]);
            }

            // add the new entry; maintaining child pages had they been present
            $parentContainer[$id] = array(
                'label'         => $category->getTitle(),
                'expansionId'   => $category->getId(),
                'pages'         => $pages,
                'module'        => 'category',
                'controller'    => 'index',
                'action'        => 'index',
                'route'         => 'category',
                'encode'        => false,
                'params'        => array(
                    'category'  => $category->getId()
                )
            );

            // update pointer for any entries we may add
            $parentContainer =& $parentContainer[$id]['pages'];

            // include content entries if so configured.
            $options += array('includeEntries' => false);
            if ($options['includeEntries'] || $item->get('includeEntries')) {
                $entriesOptions = array(Category_Model_Category::OPTION_DEREFERENCE => true);
                foreach ($category->getEntries($entriesOptions) as $entry) {
                    $entryId = $category->getId() . '/' . $entry->getId();
                    $parentContainer[$entryId] = array(
                        'label'         => $entry->getTitle(),
                        'uri'           => $entry->getUri(),
                        'expansionId'   => $entryId
                    );
                }
            }
        }

        return $container;
    }
}
