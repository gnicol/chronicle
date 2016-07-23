<?php
/**
 * Test the category index controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Category_Test_IndexControllerTest extends ModuleControllerTest
{
    /**
     * Test the index action with no categories.
     */
    public function testIndexNoCategories()
    {
        $this->utility->impersonate('anonymous');

        $this->dispatch('/category/');
        $body = $this->response->getBody();
        $this->assertModule('category', 'Expected module.'. $body);
        $this->assertController('index', 'Expected controller'. $body);
        $this->assertAction('index', 'Expected action'. $body);

        $this->assertQueryContentContains('p', 'No items to display.');
    }

    /**
     * Test the index action with bogus category.
     */
    public function testIndexBogusCategory()
    {
        $this->utility->impersonate('anonymous');
        
        $this->dispatch('/category/view/bogus');

        $this->assertModule('error', 'Expected module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('page-not-found', 'Expected action');

        $this->assertQueryContentContains(
            'p', "Sorry, the page you requested ('/category/view/bogus') does not exist."
        );
    }

    /**
     * Test the behavior when a category has defaultContent specified.
     */
    public function testIndexDefaultContent()
    {
        // add a type to base our test entry on.
        $type = new P4Cms_Content_Type;
        $type->setId('test-type')
             ->setLabel('Test Type')
             ->setElements(
                array(
                    "title" => array(
                        "type"      => "text",
                        "options"   => array("label" => "Title", "required" => true)
                    ),
                    "body"  => array(
                        "type"      => "textarea",
                        "options"   => array("label" => "Body")
                    )
                )
             )
             ->setValue('icon', file_get_contents(TEST_ASSETS_PATH . "/images/content-type-icon.png"))
             ->setFieldMetadata('icon', array("mimeType" => "image/png"))
             ->save();

        // create content for testing
        $entry = new P4Cms_Content;
        $entry->setId('test')
              ->setContentType('test-type')
              ->setValue('title', 'title test')
              ->setValue('body',  'my testable body')
              ->save();

        $orange = new P4Cms_Content;
        $orange->setId('orange')
               ->setContentType('test-type')
               ->setValue('title', 'an orange')
               ->setValue('body',  'orange body')
               ->save();

        // create a category with defaultContent, but the content does not exist.
        $category1 = Category_Model_Category::store(
            array(
                'id'           => 'one',
                'title'        => 'First',
                'indexContent' => 'bogus',
            )
        );
        $category1->addEntry('apple');

        // create a category with defaultContent that exists.
        $category2 = Category_Model_Category::store(
            array(
                'id'           => 'two',
                'title'        => 'Second',
                'indexContent' => 'test',
            )
        );
        $category2->addEntry('orange');

        $this->utility->impersonate('anonymous');

        // initial dispatch
        $this->dispatch('/category');
        $body = $this->response->getBody();
        $this->assertModule('category', 'Expected module.'. $body);
        $this->assertController('index', 'Expected controller'. $body);
        $this->assertAction('index', 'Expected action'. $body);

        // ensure no categories are listed yet.
        $this->assertQueryCount('#content .category-member', 2, 'Expected 2 categories.');
        $this->assertQueryContentContains('a', 'First');
        $this->assertQueryContentContains('a', 'Second');

        // check again for category with bogus content
        $this->resetRequest()->resetResponse();

        $this->dispatch('/category/view/one');
        $this->assertModule('category', 'Expected module category one');
        $this->assertController('index', 'Expected controller category one');
        $this->assertAction('index', 'Expected action category one');

        $this->assertQueryContentContains('h1', 'First');
        $this->assertQueryContentContains('div[@class="category-breadcrumbs"] a', 'Categories');
        $this->assertQueryContentRegex('div[@class="category-breadcrumbs"]', '/span>First/');
        $this->assertQueryCount('#content .category-member', 0, 'Expected 0 entries.');
        $this->assertNotQueryContentContains('a', 'apple');

        // check again for category with existing content
        $this->resetRequest()->resetResponse();

        $this->dispatch('/category/view/two');
        $responseBody = $this->getResponse()->getBody();
        $this->assertModule('content', 'Expected module category two.', $responseBody);
        $this->assertController('index', 'Expected controller category two.'. $responseBody);
        $this->assertAction('view', 'Expected action category two.'. $responseBody);

        $this->assertQueryContentContains('div[@elementname="title"]', $entry->getValue('title'), $responseBody);
        $this->assertQueryContentContains('div[@elementname="body"]', $entry->getValue('body'), $responseBody);
    }

    /**
     * Test the index action with some categories.
     */
    public function testIndex()
    {
        // create several categories.
        for ($i = 1; $i <= 10; $i++) {
            Category_Model_Category::store(
                array(
                    'id'            => "test-cat-$i",
                    'title'         => "Test Category $i",
                    'description'   => "a category for testing #$i",
                )
            );
        }

        $this->utility->impersonate('anonymous');

        $this->dispatch('/category');
        $this->assertModule('category', 'Expected module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('index', 'Expected action');

        // ensure no categories are listed yet.
        $this->assertQueryCount('#content .category-member', 10, 'Expected 10 categories.');
        for ($i = 1; $i <= 10; $i++) {
            $this->assertQueryContentContains('a', "Test Category $i");
            $this->assertQueryContentContains('p.description', "a category for testing #$i");
        }
    }

    /**
     * Test the index action with nested categories/entries.
     */
    public function testIndexNestedCategories()
    {
        // add a type to base our test entry on.
        $type = new P4Cms_Content_Type;
        $type->setId('test-type')
             ->setLabel('Test Type')
             ->setElements(
                array(
                    "title" => array(
                        "type"      => "text",
                        "options"   => array("label" => "Title", "required" => true)
                    ),
                    "body"  => array(
                        "type"      => "textarea",
                        "options"   => array("label" => "Body")
                    )
                )
             )
             ->setValue('icon', file_get_contents(TEST_ASSETS_PATH . "/images/content-type-icon.png"))
             ->setFieldMetadata('icon', array("mimeType" => "image/png"))
             ->save();

        // create content for testing
        $entryA = new P4Cms_Content;
        $entryA->setId('testA')
                ->setContentType('test-type')
                ->setValue('title', 'title A')
                ->setValue('body',  'body A')
                ->save();

        $entryB = new P4Cms_Content;
        $entryB->setId('testB')
               ->setContentType('test-type')
               ->setValue('title', 'title B')
               ->setValue('body',  'body B')
               ->save();

        $entryC = new P4Cms_Content;
        $entryC->setId('testC')
               ->setContentType('test-type')
               ->setValue('title', 'title C')
               ->setValue('body',  'body C')
               ->save();

        $entryD = new P4Cms_Content;
        $entryD->setId('testD')
               ->setContentType('test-type')
               ->setValue('title', 'D title')
               ->setValue('body',  'D body')
               ->save();

        // create some nested categories with entries
        $category = Category_Model_Category::store(
            array(
                'id'            => 'one',
                'title'         => 'One',
                'description'   => 'First.',
            )
        );
        $category->addEntry('testA');

        $category = Category_Model_Category::store(
            array(
                'id'            => 'one/two',
                'title'         => 'Two',
                'description'   => 'Second.',
            )
        );
        $category->addEntries(array('testB', 'testC'));

        $category = Category_Model_Category::store(
            array(
                'id'            => 'one/two/three',
                'title'         => 'Three',
                'description'   => 'Third.',
            )
        );
        $category->addEntry('testD');

        $this->utility->impersonate('anonymous');

        // first request at root level
        $this->dispatch('/category');
        $this->assertModule('category', 'Expected module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('index', 'Expected action');

        // ensure expected categories are listed.
        $this->assertQueryCount('#content .category-member', 1, 'Expected 1 category.');
        $this->assertQueryContentContains('h1', 'Categories');
        $this->assertQueryContentContains('a', 'One');
        $this->assertQueryContentContains('p.description', 'First.');

        // reset for next request
        $this->resetRequest()->resetResponse();

        // second request at first level
        $this->dispatch('/category/view/one');
        $this->assertModule('category', 'Expected module 1st request');
        $this->assertController('index', 'Expected controller 1st request');
        $this->assertAction('index', 'Expected action 1st request');

        $this->assertQueryContentContains('h1', 'One');
        $this->assertQueryContentContains('p', 'First.');
        $this->assertQueryContentContains('div[@class="category-breadcrumbs"] a', 'Categories');
        $this->assertQueryContentRegex('div[@class="category-breadcrumbs"]', '/span>One/');
        $this->assertQueryCount('#content .category-member', 2, 'Expected 2 members.');
        $this->assertQueryContentContains('a', 'Two');
        $this->assertQueryContentContains('p.description', 'Second.');
        $this->assertQueryContentContains('a', 'title A');

        // reset for next request
        $this->resetRequest()->resetResponse();

        // second request at second level
        $this->dispatch('/category/view/one/two');
        $this->assertModule('category', 'Expected module 2nd request');
        $this->assertController('index', 'Expected controller 2nd request');
        $this->assertAction('index', 'Expected action 2nd request');

        $this->assertQueryContentContains('h1', 'Two');
        $this->assertQueryContentContains('p', 'Second.');
        $this->assertQueryContentContains('div[@class="category-breadcrumbs"] a', 'Categories');
        $this->assertQueryContentContains('div[@class="category-breadcrumbs"] a', 'One');
        $this->assertQueryContentRegex('div[@class="category-breadcrumbs"]', '/span>Two/');
        $this->assertQueryCount('#content .category-member', 3, 'Expected 3 members.');
        $this->assertQueryContentContains('a', 'Three');
        $this->assertQueryContentContains('p.description', 'Third.');
        $this->assertQueryContentContains('a', 'title B');
        $this->assertQueryContentContains('a', 'title C');

        // reset for next request
        $this->resetRequest()->resetResponse();

        // second request at third level
        $this->dispatch('/category/view/one/two/three');
        $this->assertModule('category', 'Expected module 3rd request.');
        $this->assertController('index', 'Expected controller 3rd request');
        $this->assertAction('index', 'Expected action 3rd request');

        $this->assertQueryContentContains('h1', 'Three');
        $this->assertQueryContentContains('p', 'Third.');
        $this->assertQueryContentContains('div[@class="category-breadcrumbs"] a', 'Categories');
        $this->assertQueryContentContains('div[@class="category-breadcrumbs"] a', 'One');
        $this->assertQueryContentContains('div[@class="category-breadcrumbs"] a', 'Two');
        $this->assertQueryContentRegex('div[@class="category-breadcrumbs"]', '/span>Three/');
        $this->assertQueryCount('#content .category-member', 1, 'Expected 1 members.');
        $this->assertQueryContentContains('a', 'D title');
    }

    /**
     * Test escaping data in view
     */
    public function testSecurity()
    {
        $this->utility->impersonate('administrator');
        
        // create new category
        Category_Model_Category::store(
            array(
                'id'            => "test-cat",
                'title'         => "test <script>alert('fail')</script> & ok",
                'description'   => "test >> 1 & 2",
            )
        );

        // ensure category has been saved
        $this->assertTrue(
            Category_Model_Category::exists('test-cat'),
            "Expected existence of created category."
        );

        // verify view is escaped
        $this->dispatch('/category/view/test-cat');
        $responseBody = $this->response->getBody();
        $this->assertModule('category',  'Expected module; '. $responseBody);
        $this->assertController('index', 'Expected controller; '. $responseBody);
        $this->assertAction('index',     'Expected action; '. $responseBody);

        $this->assertQueryContentRegex(
            'div[@class="category-breadcrumbs"]',
            "/test &lt;script&gt;alert\('fail'\)&lt;\/script&gt; &amp; ok/"
        );
        $this->assertQueryContentRegex(
            'h1[@class="category-title"]',
            "/test &lt;script&gt;alert\('fail'\)&lt;\/script&gt; &amp; ok/"
        );
        $this->assertQueryContentRegex(
            'p[@class="category-description"]',
            "/test &gt;&gt; 1 &amp; 2/"
        );
    }
}
