<?php
/**
 * Test the category manage controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Category_Test_ManageControllerTest extends ModuleControllerTest
{
    /**
     * Test the index action.
     */
    public function testIndex()
    {
        $this->utility->impersonate('editor');

        $this->dispatch('/category/manage/index');
        $this->assertModule('category', 'Expected module.');
        $this->assertController('manage', 'Expected controller');
        $this->assertAction('index', 'Expected action');

        // verify that table and dojo data elements exist
        $this->assertXpath('//div[@dojotype="dojox.data.QueryReadStore"]', 'Expected dojo.data div');
        $this->assertXpath(
            '//table[@dojotype="p4cms.ui.grid.DataGrid" and @jsid="p4cms.category.grid.instance"]',
            'Expected dojox.grid table'
        );

        // verify add button appears
        $this->assertXpath('//button[@class="add-button"]', 'Expected category add link.');

        // create several categories.
        $items = array();
        for ($i = 1; $i <= 10; $i++) {
            $values = array(
                'id'            => "test-cat-$i",
                'title'         => "Test Category $i",
                'description'   => "a category for testing #$i",
            );
            Category_Model_Category::store($values);
            $items[] = $values;
        }

        // check JSON output
        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/category/manage/format/json');
        $body = $this->response->getBody();
        $this->assertModule('category', 'Expected module, dispatch #2. '. $body);
        $this->assertController('manage', 'Expected controller, dispatch #2 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #2 '. $body);

        $data = Zend_Json::decode($body);

        // verify number of items
        $this->assertSame(
            count($items),
            $data['numRows'],
            'Expected number of items'
        );

        // verify item values
        reset($items);
        foreach ($data['items'] as $item) {
            $this->assertSame(
                current($items),
                array_intersect($item, current($items)),
                'Expected item values'
            );
            next($items);
        }
    }

    /**
     * Test add- with a conflicting title.
     */
    public function testAddConflictingTitle()
    {
        $this->utility->impersonate('author');

        // create a category to show up in the parent select
        Category_Model_Category::store(
            array(
                'id'            => 'foo',
                'title'         => 'Foobulous',
                'description'   => 'Foobulous'
            )
        );

        // form request with appropriate fields.
        $this->request->setMethod('POST');
        $this->request->setPost('title',       'Foo');
        $this->request->setPost('parent',      '/');
        $this->request->setPost(P4Cms_Form::CSRF_TOKEN_NAME, P4Cms_Form::getCsrfToken());
        $this->dispatch('/category/manage/add');
        $this->assertModule('category', 'Expected module.');
        $this->assertController('manage', 'Expected controller');
        $this->assertAction('add', 'Expected action');

        // check for form w. errors.
        $this->assertQuery('form.category-form', 'Expected add form.');
        $this->assertQueryCount('ul.errors', 1, 'Expected one error.');
    }

    /**
     * Test add with a good post w. JSON
     */
    public function testAddGoodPostJson()
    {
        $this->utility->impersonate('author');

        // form request without required fields.
        $title = 'Good Category';
        $this->request->setMethod('POST');
        $this->request->setPost('title',  $title);
        $this->request->setPost('parent', '');
        $this->request->setPost('format', 'json');
        $this->request->setPost(P4Cms_Form::CSRF_TOKEN_NAME, P4Cms_Form::getCsrfToken());
        
        $this->dispatch('/category/manage/add');
        $this->assertModule('category', 'Expected module.');
        $this->assertController('manage', 'Expected controller');
        $this->assertAction('add', 'Expected action');

        // check for saved category entry.
        $id = 'good-category';
        $this->assertTrue(Category_Model_Category::exists($id), 'Expected category to be saved.');
        $category = Category_Model_Category::fetch($id);
        $this->assertSame(
            $title,
            $category->getValue('title'),
            'Expected same title as was posted.'
        );

        $body = $this->response->getBody();
        $this->assertResponseCode(200, 'Expected success response code for post');
        $data = json_decode($body, true);

        // verify that arrays of category in json and saved category are same (items order can differ)
        $this->assertSame(
            count($data['category']),
            count($category->toArray()),
            'Expected number of items in category in json and in saved category are the same.'
        );
        $this->assertSame(
            array(),
            array_diff($category->toArray(), $data['category']),
            'Expected items in category in json response and in saved category are the same.'
        );
    }

    /**
     * Test the add action without a post.
     */
    public function testAddNoPost()
    {
        $this->utility->impersonate('author');

        // create a category to show up in the parent select
        Category_Model_Category::store(
            array(
                'id'            => 'foo',
                'title'         => 'Foo',
                'description'   => 'Foobulous'
            )
        );

        $this->dispatch('/category/manage/add');
        $this->assertModule('category', 'Expected module.');
        $this->assertController('manage', 'Expected controller');
        $this->assertAction('add', 'Expected action');

        // ensure that form inputs are presented correctly.
        $this->assertQuery('form.category-form', 'Expected add form.');
        $this->assertQuery('select[name="parent"]', 'Expected handler select input.');
        $this->assertQueryCount('select[name="parent"] option', 2, 'Expected two parents.');
        $this->assertQuery('input[name="title"]', 'Expected label input.');
        $this->assertQuery('textarea[name="description"]', 'Expected description input.');
        $this->assertQuery('input[type="submit"]', 'Expected submit button.');

        // ensure labels are present.
        $labels = array(
            'title'       => 'Title',
            'parent'      => 'Parent',
            'description' => 'Description',
        );
        foreach ($labels as $field => $label) {
            $this->assertQueryContentContains("label[for='$field']", $label, 'Expected $field label.');
        }
    }

    /**
     * Test add with a post missing several required fields.
     */
    public function testAddBadPost()
    {
        $this->utility->impersonate('author');

        // form request without required fields.
        $this->request->setMethod('POST');
        $this->request->setPost('description', 'test description');
        $this->request->setPost(P4Cms_Form::CSRF_TOKEN_NAME, P4Cms_Form::getCsrfToken());
        $this->dispatch('/category/manage/add');
        $this->assertModule('category', 'Expected module.');
        $this->assertController('manage', 'Expected controller');
        $this->assertAction('add', 'Expected action');

        // check for form w. errors.
        $this->assertQuery('form.category-form', 'Expected add form.');
        $this->assertQueryCount('ul.errors', 1, 'Expected matching number of errors.');

        // ensure description value was preserved.
        $this->assertQueryContentContains('textarea', 'test description');
    }

    /**
     * Test add with a bad parent.
     */
    public function testAddBadParentPost()
    {
        $this->utility->impersonate('author');

        // form request without required fields.
        $this->request->setMethod('POST');
        $this->request->setPost('title',       'Test Category');
        $this->request->setPost('parent',      'lasdjkfasdklf');
        $this->request->setPost('description', 'a description');
        $this->request->setPost(P4Cms_Form::CSRF_TOKEN_NAME, P4Cms_Form::getCsrfToken());
        $this->dispatch('/category/manage/add');

        $this->assertModule('category', 'Expected module.');
        $this->assertController('manage', 'Expected controller');
        $this->assertAction('add', 'Expected action');

        // check for form w. errors.
        $this->assertQuery('form.category-form', 'Expected add form.');
        $this->assertQueryCount('ul.errors', 1, 'Expected one error.');

        $this->assertQueryContentContains('li', "'lasdjkfasdklf' is not a valid parent category.");

        // ensure elements value was preserved.
        $this->assertQueryContentContains('textarea', 'a description');
    }

    /**
     * Test add with a good post.
     */
    public function testGoodAddPost()
    {
        $this->utility->impersonate('author');

        // form request without required fields.
        $title       = 'Good Category';
        $id          = 'good-category';
        $description = 'a description';
        $this->request->setMethod('POST');
        $this->request->setPost('title',       $title);
        $this->request->setPost('parent',      '');
        $this->request->setPost('description', $description);
        $this->request->setPost(P4Cms_Form::CSRF_TOKEN_NAME, P4Cms_Form::getCsrfToken());

        $this->dispatch('/category/manage/add');
        $this->assertModule('category', 'Expected module.');
        $this->assertController('manage', 'Expected controller');
        $this->assertAction('add', 'Expected action');

        // check for saved category entry.
        $this->assertTrue(Category_Model_Category::exists($id), 'Expected category to be saved.');
        $category = Category_Model_Category::fetch($id);
        $this->assertSame(
            $title,
            $category->getValue('title'),
            'Expected same title as was posted.'
        );
        $this->assertSame(
            $description,
            $category->getValue('description'),
            'Expected same description as was posted.'
        );

        $this->resetRequest()
             ->resetResponse();

         // test that id must be unique (can't add same category twice).
        $this->request->setMethod('POST');
        $this->request->setPost('title',       $title);
        $this->request->setPost('parent',      '');
        $this->request->setPost('description', $description);
        $this->request->setPost(P4Cms_Form::CSRF_TOKEN_NAME, P4Cms_Form::getCsrfToken());
        $this->dispatch('/category/manage/add');
        $this->assertQueryCount('ul.errors', 1, 'Expected id error.');

        $this->resetRequest()
             ->resetResponse();

        // test that the added category appears as a parent
        $this->dispatch('/category/manage/add');
        $this->assertModule('category', 'Expected module.');
        $this->assertController('manage', 'Expected controller');
        $this->assertAction('add', 'Expected action');

        $this->assertQueryContentContains('option', $title);
    }

    /**
     * Test edit with no post.
     */
    public function testEditNoPost()
    {
        $this->utility->impersonate('editor');

        // test editing an id that does not exist
        $id = 'editme/subcat';
        $this->request->setParam('category', $id);
        $this->dispatch('/category/manage/edit');
        $this->assertModule('error', 'Expected module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('error', 'Expected action');

        $this->resetRequest()->resetResponse();

        // test editing an id that does exist.
        Category_Model_Category::store(array('id' => 'editme', 'title' => 'Edit Me'));
        Category_Model_Category::store(array('id' => 'editme/subcat', 'title' => 'subCat', 'description' => 'docs'));
        $this->request->setParam('category', $id);
        $this->dispatch('/category/manage/edit');
        $this->assertModule('category', 'Expected module.');
        $this->assertController('manage', 'Expected controller');
        $this->assertAction('edit', 'Expected action');

        $this->assertQueryContentContains('select[name="parent"] option', 'Edit Me', 'Expected parent input.');
        $this->assertXpath('//input[@name="title"][@value="subCat"]', 'Expected title input.');
        $this->assertQueryContentContains('textarea[name="description"]', 'docs', 'Expected description input.');
    }

    /**
     * Test edit with bad post.
     */
    public function testEditBadPost()
    {
        $this->utility->impersonate('editor');

        // create category to be edited.
        Category_Model_Category::store(array('id' => 'edit-me', 'title' => 'Edit Me'));
        Category_Model_Category::store(array('id' => 'edit-me/subcat', 'title' => 'subCat', 'description' => 'docs'));

        // form request without required field (elements).
        $this->request->setParam('category', 'edit-me/subcat');
        $this->request->setMethod('POST');
        $this->request->setPost('title',       '');
        $this->request->setPost('description', 'changed');
        $this->request->setPost(P4Cms_Form::CSRF_TOKEN_NAME, P4Cms_Form::getCsrfToken());

        $this->dispatch('/category/manage/edit');
        $this->assertModule('category', 'Expected module.');
        $this->assertController('manage', 'Expected controller');
        $this->assertAction('edit', 'Expected action');

        // check for form w. errors.
        $this->assertQuery('form', 'Expected edit form.');
        $this->assertQueryCount('ul.errors', 1, 'Expected one error.');

        // ensure label value was preserved.
        $this->assertQuery('//input[@name="title"][@value=""]', 'Expect title to be changed.');
    }


    /**
     * Test good post to edit.
     */
    public function testGoodEditPost()
    {
        $this->utility->impersonate('editor');

        // create category to be edited.
        Category_Model_Category::store(array('id' => 'edit-me', 'title' => 'Edit Me'));
        Category_Model_Category::store(array('id' => 'edit-me/subcat', 'title' => 'subCat', 'description' => 'docs'));

        // form request without required fields.
        $this->request->setParam('category', 'edit-me/subcat');
        $this->request->setMethod('POST');
        $this->request->setPost('title',       'Test Category');
        $this->request->setPost('parent',      'edit-me');
        $this->request->setPost('description', 'new docs');
        $this->request->setPost(P4Cms_Form::CSRF_TOKEN_NAME, P4Cms_Form::getCsrfToken());

        $this->dispatch('/category/manage/edit');
        $this->assertModule('category', 'Expected module.');
        $this->assertController('manage', 'Expected controller');
        $this->assertAction('edit', 'Expected action');

        // check for saved category entry.
        $this->assertTrue(
            Category_Model_Category::exists('edit-me/test-category'),
            'Expected edited category to be saved.'
        );
        $this->assertFalse(
            Category_Model_Category::exists('edit-me/subcat'),
            'Expected original category to be gone.'
        );
        $category = Category_Model_Category::fetch('edit-me/test-category');
        $this->assertSame(
            'Test Category',
            $category->getValue('title'),
            "Expected same title as was posted."
        );
        $this->assertSame(
            'new docs',
            $category->getValue('description'),
            "Expected same description as was posted."
        );
    }

    /**
     * Test deleting.
     */
    public function testDelete()
    {
        $this->utility->impersonate('editor');

        // create category to be edited.
        Category_Model_Category::store(array('id' => 'editme', 'title' => 'Edit Me'));
        Category_Model_Category::store(array('id' => 'editme/subcat', 'title' => 'subCat', 'description' => 'docs'));

        $this->request->setMethod('POST');
        $this->request->setParam('category', 'editme');
        $this->request->setParam('format',   'json');
        $this->request->setPost(P4Cms_Form::CSRF_TOKEN_NAME, P4Cms_Form::getCsrfToken());
        $this->dispatch('/category/manage/delete');
        $this->assertModule('category', 'Expected module.');
        $this->assertController('manage', 'Expected controller');
        $this->assertAction('delete', 'Expected action');

        // ensure categories gone.
        $this->assertFalse(
            Category_Model_Category::exists('editme/subcat'),
            "Expected category editme/subcat not to exist post delete."
        );
        $this->assertFalse(
            Category_Model_Category::exists('editme'),
            "Expected category editme not to exist post delete."
        );
    }

    /**
     * Test filtering grid by search query.
     */
    public function testFilterBySearch()
    {
        $this->utility->impersonate('editor');

        // setup environment for testing
        $this->_setupFiltering();

        $this->dispatch('/category/manage/format/json?search[query]=last');
        $body = $this->response->getBody();
        $this->assertModule('category', 'Expected module, dispatch #1. '. $body);
        $this->assertController('manage', 'Expected controller, dispatch #1 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #1 '. $body);

        // decode json output
        $data = Zend_Json::decode($body);

        // expected items (* denotes obligatory): B(*), B/subB, C(*), C/subC(*), C/subC/subsubC-last
        $obligatoryLogicMap = array(
            'B'                     => 1,
            'B/subB-last'           => 0,
            'C'                     => 1,
            'C/subC'                => 1,
            'C/subC/subsubC-last'   => 0
        );

        // verify number of items after filtering
        $this->assertSame(
            count($obligatoryLogicMap),
            count($data['items']),
            'Expected number of items after filtering by search #1.'
        );

        // verify obligatory items are marked
        foreach ($obligatoryLogicMap as $id => $isObligatory) {
            $assertFunction = $isObligatory ? 'assertTrue' : 'assertFalse';
            $this->$assertFunction(
                $this->_getItemById($id, $data, 'obligatory'),
                "Expected category $id " . ($isObligatory ? 'is' : 'is not') 
                    . " marked as obligatory after filtering by search #1"
            );
        }

        // test with another search query
        $this->resetRequest()
             ->resetResponse();

        $this->dispatch('/category/manage/format/json?search[query]=subc');
        $body = $this->response->getBody();
        $this->assertModule('category', 'Expected module, dispatch #2. '. $body);
        $this->assertController('manage', 'Expected controller, dispatch #2 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #2 '. $body);

        // decode json output
        $data = Zend_Json::decode($body);

        // expected items (* denotes obligatory): C(*), C/subC, C/subC/subsubC-last
        $obligatoryLogicMap = array(
            'C'                     => 1,
            'C/subC'                => 0,
            'C/subC/subsubC-last'   => 0
        );

        // verify number of items after filtering
        $this->assertSame(
            count($obligatoryLogicMap),
            count($data['items']),
            'Expected number of items after filtering by search #2.'
        );

        // verify obligatory items are marked
        foreach ($obligatoryLogicMap as $id => $isObligatory) {
            $assertFunction = $isObligatory ? 'assertTrue' : 'assertFalse';
            $this->$assertFunction(
                $this->_getItemById($id, $data, 'obligatory'),
                "Expected category $id " . ($isObligatory ? 'is' : 'is not')
                    . " marked as obligatory after filtering by search #2"
            );
        }
    }

    /**
     * Test filtering grid by number of category entries.
     */
    public function testFilterByCategoryEntries()
    {
        $this->utility->impersonate('editor');

        // setup environment for testing
        $this->_setupFiltering();

        // show only non-empty categories
        $this->dispatch('/category/manage/format/json?entriesCount[display]=more');
        $body = $this->response->getBody();
        $this->assertModule('category', 'Expected module, dispatch #1. '. $body);
        $this->assertController('manage', 'Expected controller, dispatch #1 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #1 '. $body);

        // decode json output
        $data = Zend_Json::decode($body);

        // expected items (* denotes obligatory): B, C(*), C/subC
        $obligatoryLogicMap = array(
            'B'                     => 0,
            'C'                     => 1,
            'C/subC'                => 0,
        );

        // verify number of items after filtering
        $this->assertSame(
            count($obligatoryLogicMap),
            count($data['items']),
            'Expected number of items after filtering by category entries #1.'
        );

        // verify obligatory items are marked
        foreach ($obligatoryLogicMap as $id => $isObligatory) {
            $assertFunction = $isObligatory ? 'assertTrue' : 'assertFalse';
            $this->$assertFunction(
                $this->_getItemById($id, $data, 'obligatory'),
                "Expected category $id " . ($isObligatory ? 'is' : 'is not')
                    . " marked as obligatory after filtering by category entries #1"
            );
        }

        // test with another filter
        $this->resetRequest()
             ->resetResponse();

        // show only empty categories
        $this->dispatch('/category/manage/format/json?entriesCount[display]=none');
        $body = $this->response->getBody();
        $this->assertModule('category', 'Expected module, dispatch #2. '. $body);
        $this->assertController('manage', 'Expected controller, dispatch #2 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #2 '. $body);

        // decode json output
        $data = Zend_Json::decode($body);

        // expected items (* denotes obligatory): A, B(*), B/subB-last, C, C/subC(*), C/subC/subsubC-last
        $obligatoryLogicMap = array(
            'A'                     => 0,
            'B'                     => 1,
            'B/subB-last'           => 0,
            'C'                     => 0,
            'C/subC'                => 1,
            'C/subC/subsubC-last'   => 0
        );

        // verify number of items after filtering
        $this->assertSame(
            count($obligatoryLogicMap),
            count($data['items']),
            'Expected number of items after filtering by category entries #2.'
        );

        // verify obligatory items are marked
        foreach ($obligatoryLogicMap as $id => $isObligatory) {
            $assertFunction = $isObligatory ? 'assertTrue' : 'assertFalse';
            $this->$assertFunction(
                $this->_getItemById($id, $data, 'obligatory'),
                "Expected category $id " . ($isObligatory ? 'is' : 'is not')
                    . " marked as obligatory after filtering by category entries #2"
            );
        }
    }

    /**
     * Test multi-filtering.
     */
    public function testComposedFilter()
    {
        $this->utility->impersonate('editor');

        // setup environment for testing
        $this->_setupFiltering();

        $this->dispatch('/category/manage/format/json?search[query]=sub&entriesCount[display]=more');
        $body = $this->response->getBody();
        $this->assertModule('category', 'Expected module, dispatch #1. '. $body);
        $this->assertController('manage', 'Expected controller, dispatch #1 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #1 '. $body);

        // decode json output
        $data = Zend_Json::decode($body);

        // expected items (* denotes obligatory): C(*), C/subC
        $obligatoryLogicMap = array(
            'C'                     => 1,
            'C/subC'                => 0,
        );

        // verify number of items after filtering
        $this->assertSame(
            count($obligatoryLogicMap),
            count($data['items']),
            'Expected number of items after multi-filtering #1.'
        );

        // verify obligatory items are marked
        foreach ($obligatoryLogicMap as $id => $isObligatory) {
            $assertFunction = $isObligatory ? 'assertTrue' : 'assertFalse';
            $this->$assertFunction(
                $this->_getItemById($id, $data, 'obligatory'),
                "Expected category $id " . ($isObligatory ? 'is' : 'is not')
                    . " marked as obligatory after multi-filtering #1"
            );
        }

        // test with another search query
        $this->resetRequest()
             ->resetResponse();

        $this->dispatch('/category/manage/format/json?search[query]=last&entriesCount[display]=more');
        $body = $this->response->getBody();
        $this->assertModule('category', 'Expected module, dispatch #2. '. $body);
        $this->assertController('manage', 'Expected controller, dispatch #2 '. $body);
        $this->assertAction('index', 'Expected action, dispatch #2 '. $body);

        // decode json output
        $data = Zend_Json::decode($body);

        // expected items (* denotes obligatory): empty
        $obligatoryLogicMap = array();

        // verify number of items after filtering
        $this->assertSame(
            count($obligatoryLogicMap),
            count($data['items']),
            'Expected number of items after multi-filtering #2.'
        );

        // verify obligatory items are marked
        foreach ($obligatoryLogicMap as $id => $isObligatory) {
            $assertFunction = $isObligatory ? 'assertTrue' : 'assertFalse';
            $this->$assertFunction(
                $this->_getItemById($id, $data, 'obligatory'),
                "Expected category $id " . ($isObligatory ? 'is' : 'is not')
                    . " marked as obligatory after multi-filtering #2"
            );
        }
    }

    /**
     * Helper function for getting item with given id from json decoded data.
     *
     * @param   string          $id     id to search for between items in the data
     * @param   array           $data   array to search item with given id for
     * @param   string          $key    optional - if set then item[key] will be
     *                                  returned, where item is one of the items in
     *                                  data with item['id'] matches the given id,
     *                                  otherwise the whole item will be returned
     * @return  array|string            data item with id matching the passed value
     *                                  or value of item[key]
     */
    protected function _getItemById($id, array $data, $key = null)
    {
        $callback = function($item) use ($id)
        {
            return isset($item['id']) && $item['id'] === $id;
        };
        $found = current(array_filter($data['items'], $callback));
        return $key ? $found[$key] : $found;
    }

    /**
     * Creates categories and assigned content for testing filtering options.
     */
    protected function _setupFiltering()
    {
        // create folowing categories structure:
        // A
        // B
        //   subB-last
        // C
        //   subC
        //     subsubC-last
        Category_Model_Category::store(
            array(
                'title' => 'A',
                'id'    => 'A'
            )
        );
        Category_Model_Category::store(
            array(
                'title' => 'B',
                'id'    => 'B'
            )
        );
        Category_Model_Category::store(
            array(
                'title' => 'subB-last',
                'id'    => 'B/subB-last'
            )
        );
        Category_Model_Category::store(
            array(
                'title' => 'C',
                'id'    => 'C'
            )
        );
        Category_Model_Category::store(
            array(
                'title' => 'subC',
                'id'    => 'C/subC'
            )
        );
        Category_Model_Category::store(
            array(
                'title' => 'subsubC-last',
                'id'    => 'C/subC/subsubC-last'
            )
        );

        // create content entry and assign the B and subC category to it
        P4Cms_Content::store(
            array(
                'id'    => 'test'
            )
        );
        Category_Model_Category::setEntryCategories('test', array('B', 'C/subC'));
    }

    /**
     * Test helper method to create specified content records.
     *
     * @param   array  $entries  An array of id => title for the content records to create.
     * @return  array  An array of the created content entries.
     */
    protected function _makeContent(array $entries)
    {
        $created = array();
        foreach ($entries as $id => $title) {
            $entry = new P4Cms_Content;
            $entry->setId($id)
                  ->setValue('contentType', 'basic-page')
                  ->setValue('title', $title)
                  ->save();
            $created[] = $entry;
        }

        return $created;
    }
}
