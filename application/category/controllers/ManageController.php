<?php
/**
 * Manages category operations (e.g. add).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Category_ManageController extends Zend_Controller_Action
{
    public $contexts = array(
        'add'       => array('partial' => 'get', 'json' => 'post'),
        'edit'      => array('partial' => 'get', 'json' => 'post'),
        'delete'    => array('partial' => 'get', 'json' => 'post'),
        'index'     => array('json'),
        'move'      => array('json' => 'post')
    );

    /**
     * Use management layout for all actions.
     */
    public function init()
    {
        $this->getHelper('layout')->setLayout('manage-layout');
    }

    /**
     * List categories.
     *
     * @publishes   p4cms.category.grid.actions
     *              Modify the passed menu (add/modify/delete items) to influence the actions shown
     *              on entries in the Manage Categories grid.
     *              P4Cms_Navigation            $actions    A menu to hold grid actions.
     *
     * @publishes   p4cms.category.grid.data.item
     *              Return the passed item after applying any modifications (add properties, change
     *              values, etc.) to influence the row values sent to the Manage Categories grid.
     *              array                       $item       The item to potentially modify.
     *              mixed                       $model      The original object/array that was used
     *                                                      to make the item.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.category.grid.data
     *              Adjust the passed data (add properties, modify values, etc.) to influence
     *              the row values sent to the Manage Categories grid.
     *              Zend_Dojo_Data              $data       The data to be filtered.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.category.grid.populate
     *              Adjust the passed iterator (possibly based on values in the passed form) to
     *              filter which categories will be shown on the Manage Categories grid.
     *              P4Cms_Model_Iterator        $items      An iterator of Category_Model_Category
     *                                                      objects.
     *              P4Cms_Form_PubSubForm       $form       A form containing filter options.
     *
     * @publishes   p4cms.category.grid.render
     *              Make adjustments to the datagrid helper's options pre-render (e.g. change
     *              options to add columns) for the Manage Categories grid.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.category.grid.form
     *              Make arbitrary modifications to the Manage Categories filters form.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.category.grid.form.subForms
     *              Return a Form (or array of Forms) to have them added to the Manage Categories
     *              filters form. The returned form(s) should have a 'name' set on them to allow
     *              them to be uniquely identified.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *
     * @publishes   p4cms.category.grid.form.preValidate
     *              Allows subscribers to adjust the Manage Categories filters form prior to
     *              validation of the passed data. For example, modify element values based on
     *              related selections to permit proper validation.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.category.grid.form.validate
     *              Return false to indicate the Manage Categories filters form is invalid. Return
     *              true to indicate your custom checks were satisfied, so form validity should be
     *              unchanged.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     An associative array of form values.
     *
     * @publishes   p4cms.category.grid.form.populate
     *              Allows subscribers to adjust the Manage Categories filters form after it has
     *              been populated with the passed data.
     *              P4Cms_Form_PubSubForm       $form       The form that published this event.
     *              array                       $values     The values passed to the populate
     *                                                      method.
     */
    public function indexAction()
    {
        // enforce permissions.
        $this->acl->check('categories', 'manage');

        // setup list options form
        $request        = $this->getRequest();
        $gridNamespace  = 'p4cms.category.grid';
        $form           = new Ui_Form_GridOptions(
            array(
                'namespace'   => $gridNamespace
            )
        );
        $form->populate($request->getParams());

        // set up view
        $view               = $this->view;
        $view->form         = $form;
        $view->pageSize     = $request->getParam('count', 100);
        $view->rowOffset    = $request->getParam('start', 0);
        $view->pageOffset   = round($view->rowOffset / $view->pageSize, 0) + 1;
        $view->showAddLink  = $this->acl->isAllowed('categories', 'add');
        $view->headTitle()->set('Manage Categories');

        // set DataGrid view helper namespace
        $helper = $view->dataGrid();
        $helper->setNamespace($gridNamespace);

        // collect the actions from interested parties
        $actions = new P4Cms_Navigation;
        P4Cms_PubSub::publish($gridNamespace . '.actions', $actions);
        $view->actions = $actions;

        // early exit for standard requests (ie. not json)
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->getHelper('helpUrl')->setUrl('navigation.category.overview.html');
            return;
        }

        // fetch categories and make a copy for later use
        $categories = Category_Model_Category::fetchAll();
        $copy       = new P4Cms_Model_Iterator($categories->getArrayCopy());

        // allow third-parties to influence list
        try {
            P4Cms_PubSub::publish($gridNamespace . '.populate', $categories, $form);
        } catch (Exception $e) {
            P4Cms_Log::logException("Error building categories list.", $e);
        }

        // restore categories hierarchy by appending ancestors to the
        // filtered categories
        $this->_restoreCategoriesHierarchy($categories, $copy);

        // prepare sorting options
        $sortKey    = $request->getParam('sort', 'title');
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

        // compose list of sorted categories
        $view->categories = $this->_sortCategoriesRecursively($categories, $sortKey, $sortFlags);
    }

    /**
     * Add a category.
     */
    public function addAction()
    {
        // enforce permissions.
        $this->acl->check('categories', 'add');

        // set up view
        $view            = $this->view;
        $request         = $this->getRequest();
        $view->shortForm = $request->getParam('short', false);
        $view->headTitle()->set('Add Category');

        // set up form
        $form       = new Category_Form_Manage;
        $view->form = $form;
        $form->setIdPrefix($request->getParam('formIdPrefix'));

        if ($request->isPost()) {
            // if a valid data were posted, try to add the category
            if ($form->isValid($request->getPost())) {
                // populate the form with posted data
                $form->populate($request->getPost());

                // create new category with collected values and save it.
                // try to add a category with posted data; it may fail when
                // ancestors don't exist
                $category = new Category_Model_Category;
                try {
                    $category->setValues($form->getValues())
                             ->setId($form->composeCategoryId())
                             ->save();
                } catch (InvalidArgumentException $e) {
                    // handle the category ancestry does not exist exception
                    if ($e->getMessage() == 'Cannot create new category; category ancestry does not exist.') {
                        $form->getElement('parent')->addError(
                            "Cannot create new category; category ancestor '"
                            . $category->getParentId() . "' does not exist."
                        );
                    } else {
                        // re-thrown other exceptions
                        throw $e;
                    }
                }
            }

            // re-check whether form is valid (i.e. doesn't contain any error messages)
            if ($form->getMessages()) {
                // form is invalid; set bad request response code
                // and assign error messages to the view
                $this->getResponse()->setHttpResponseCode(400);
                $view->errors = $form->getMessages();
                return;
            }

            // if we reached this point, form is valid;
            // set notification message
            $view->message = "Category '{$category->getTitle()}' has been successfully added.";

            // for traditional requests, add notification message and redirect
            if (!$this->contextSwitch->getCurrentContext()) {
                // notify user and return to category list.
                P4Cms_Notifications::add(
                    $view->message,
                    P4Cms_Notifications::SEVERITY_SUCCESS
                );
                $this->redirector->gotoUrl($request->getBaseUrl());
            }

            // assign details about new category to the view
            $view->category = $category;
        }
    }

    /**
     * Edit a content category.
     *
     * @param   array    $params      The values to apply
     */
    public function editAction(array $params = null)
    {
        // enforce permissions.
        $this->acl->check('categories', 'manage');

        // set up view
        $view       = $this->view;
        $view->headTitle()->set('Edit Category');

        // fetch the content category to be edited.
        $request    = $this->getRequest();
        $id         = $request->getParam('category');
        $category   = Category_Model_Category::fetch($id);

        // setup form
        $form       = new Category_Form_Manage;
        $view->form = $form;
        $values     = array_merge(
            $category->getValues(),
            array(
                'parent' => $category->getParentId()
            )
        );
        $form->populate($values);

        // disable unique title check
        $form->setUniqueTitleRequired(false);

        // remove the current category and it's children from parent options.
        $newOptions = array();
        foreach ($form->getElement('parent')->getMultiOptions() as $key => $label) {
            if ($key === $id or strpos($key, "$id/") === 0) {
                continue;
            }
            $newOptions[$key] = $label;
        }
        $form->getElement('parent')->setMultiOptions($newOptions);

        if ($request->isPost()) {
            // if params is specified, we use them to override the existing values
            // else item values come from request
            if (isset($params)) {
                $values = array_merge($category->getValues(), $params);
            } else {
                $values = $request->getPost();
            }

            // if a valid data were posted, try to update the category
            if ($form->isValid($values)) {
                // populate the form with posted data
                $form->populate($values);

                // start a batch in case we're moving
                $adapter = $category->getAdapter();
                $adapter->beginBatch("Edit category $id");

                // if the id has changed, move the category
                $newId = $form->composeCategoryId();
                if ($id !== $newId) {
                    try {
                        Category_Model_Category::move($id, $newId);
                    } catch (InvalidArgumentException $e) {
                        $form->getElement('parent')->addError($e->getMessage());
                    }
                }

                // set form values on category, and save it
                try {
                    $category->setValues($form->getValues())
                             ->setId($newId)
                             ->save();
                } catch (InvalidArgumentException $e) {
                    // handle the category ancestry does not exist exception
                    if ($e->getMessage() == 'Cannot create new category; category ancestry does not exist.') {
                        $form->getElement('parent')->addError(
                            "Cannot create new category; category ancestor '"
                            . $category->getParentId() . "' does not exist."
                        );
                    }

                    // re-throw other exceptions
                    throw $e;
                }
            }

            // re-check whether form is valid (i.e. doesn't contain any error messages)
            if ($form->getMessages()) {
                // form is invalid; revert batch, set bad request response code
                // and assign error messages to the view
                $this->getResponse()->setHttpResponseCode(400);
                $view->errors = $form->getMessages();
                if (isset($adapter)) {
                    $adapter->revertBatch();
                }
                return;
            }

            // at this point, form is valid;
            // attempt to commit the batch - cleanup on failure.
            try {
                $adapter->commitBatch();
            } catch (Exception $e) {
                $adapter->revertBatch();
                throw $e;
            }

            // set notification message
            $view->message = "Category '{$category->getTitle()}' has been updated.";

            // for traditional requests, add notification message and redirect
            if (!$this->contextSwitch->getCurrentContext()) {
                P4Cms_Notifications::add(
                    $view->message,
                    P4Cms_Notifications::SEVERITY_SUCCESS
                );

                $this->redirector->gotoSimple('index');
            }
        }
    }

    /**
     * Move a category
     */
    public function moveAction()
    {
        // enforce permissions.
        $this->acl->check('categories', 'manage');

        $request    = $this->getRequest();
        $values     = array("parent" => $request->getParam('parent'));

        $this->editAction($values);
    }

    /**
     * Remove a content category.
     */
    public function deleteAction()
    {
        // enforce permissions.
        $this->acl->check('categories', 'manage');

        // require post request method.
        $request = $this->getRequest();
        if (!$request->isPost()) {
            throw new P4Cms_AccessDeniedException(
                "Cannot delete category. Request method must be http post."
            );
        }

        $id       = $request->getParam('id', $request->getParam('category'));
        $category = Category_Model_Category::fetch($id);
        $category->delete();

        $this->view->category = $category;
    }

    /**
     * Sort the given list of categories recursively to preserve parent-child
     * relationships.
     *
     * Example:
     *
     * Assume there are 2 categories, A, B where A has 2 childs A/B, A/C.
     * The output is an array iterator with categories in the following order:
     * - A, A/B, A/C, B  if sorted A to Z
     * - B, A, A/C, A/B  if sorted Z to A
     *
     * @param   P4Cms_Model_Iterator    $categories     list of all categories
     *                                                  used for getting the children
     * @param   string|array            $sortKey        key to sort categories by
     * @param   array                   $sortFlags      sorting options
     * @param   string|null             $parentId       optional - parent category of
     *                                                  the generated list
     *                                                  (null by default)
     * @return  P4Cms_Model_Iterator                    list of sorted categories
     */
    protected function _sortCategoriesRecursively($categories, $sortKey, $sortFlags, $parentId = null)
    {
        // get categories with given parent and sort them
        $childs = $categories
            ->filterByCallback(
                function($category, $parentId)
                {
                    return $category->getParentId() === $parentId;
                },
                $parentId,
                array(P4_Model_Iterator::FILTER_COPY)
            )
            ->sortBy($sortKey, $sortFlags);

        // assemble categories list and append sorted sub-categories below each category
        $sortedCategories = new P4Cms_Model_Iterator;
        foreach ($childs as $category) {
            $sortedCategories[] = $category;
            $sortedChilds       = $this->_sortCategoriesRecursively(
                $categories, $sortKey, $sortFlags, $category->getId()
            );
            foreach ($sortedChilds as $childCategory) {
                $sortedCategories[] = $childCategory;
            }
        }

        return $sortedCategories;
    }

    /**
     * Restore categories hierarchy by appending ancestors to the items contained
     * in match so as for any sub-category, all its ancestors are also present.
     * Parent categories that do not match, but must be included, are refered as
     * obligatory.
     * In addition, these obligatory items will be added an extra 'obligatory'
     * field so they can be recognized.
     *
     * @param P4Cms_Model_Iterator  $match              list with categories to keep
     * @param P4Cms_Model_Iterator  $categories         list with categories where
     *                                                  ancestors will be taken from
     */
    protected function _restoreCategoriesHierarchy(P4Cms_Model_Iterator $match,
        P4Cms_Model_Iterator $categories
    )
    {
        // compose list with categories to keep
        $keepIds = array();
        foreach ($match as $category) {
            $keepIds = array_merge(
                $keepIds,
                array($category->getId()),
                $category->getAncestorIds()
            );
        }

        // make keep list unique as there may be duplicates
        $keepIds = array_unique($keepIds);

        // filter categories to keep only selected items
        $categories->filter('id', $keepIds);

        // append and mark obligatory categories
        $obligatory = array_diff($keepIds, $match->invoke('getId'));
        foreach ($categories as $category) {
            if (in_array($category->getId(), $obligatory)) {
                $category->setValue('obligatory', true);
                $match->append($category);
            }
        }
    }
}
