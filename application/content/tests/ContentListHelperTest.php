<?php
/**
 * Test methods for the content list view helper.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Test_ContentListHelperTest extends ModuleTest
{
    protected $_templateFile = 'contentListTemplate.phtml';

    /**
     * Create a type for testing.
     */
    public function _createTestType()
    {
        $elements = array(
            'title' => array(
                'type'      => 'text',
                'options'   => array('label' => 'Title', 'required' => true),
            ),
            'body'  => array(
                'type'      => 'textarea',
                'options'   => array('label' => 'Body'),
            ),
            'abstract'  => array(
                'type'      => 'textarea',
                'options'   => array('label' => 'Abstract'),
            ),
            'id'        => array(
                'type'      => 'text',
                'options'   => array('label' => 'ID', 'required' => true)
            )
        );

        $type = new P4Cms_Content_Type;
        $type->setId("test-type")
             ->setLabel("Test Type")
             ->setElements($elements)
             ->setValue('icon', file_get_contents(TEST_ASSETS_PATH . '/images/content-type-icon.png'))
             ->setFieldMetadata('icon', array("mimeType" => "image/png"))
             ->save();

        return $type;
    }

    /**
     * Creates a test entry of the provided type
     *
     * @param   P4Cms_Content_Type  $type   the content type of entry to create
     * @param   string              $id     the id to use for the content entry
     */
    protected function _createTestEntry($type, $id = 'test-content')
    {
        $entry = new P4Cms_Content;
        $entry->setContentType($type)
             ->setValue('title', 'Test Title')
             ->setValue('body', 'The body of the test')
             ->setValue('abstract', 'abstract this')
             ->setId($id)
             ->save('a test entry');
        return $entry;
    }

    /**
     * Test setup.
     */
    public function setup()
    {
        parent::setup();

        $this->_view = Zend_Layout::getMvcInstance()->getView();
        $this->_view->addFilterPath(dirname(APPLICATION_PATH) . '/P4Cms/Filter');
    }

    /**
     * Test instantiation.
     */
    public function testInstantiation()
    {
        $helper = new Content_View_Helper_ContentList;
        $helper->setView($this->_view);
        $this->assertTrue($helper instanceof Content_View_Helper_ContentList, 'Expected class');
    }

    /**
     * Test render without content
     */
    public function testEmptyRender()
    {
        $type = $this->_createTestType();

        $helper = new Content_View_Helper_ContentList;
        $helper->setView($this->_view);
        $helper->contentList(new P4Cms_Record_Query(array('type' => $type->getId())));

        $this->assertEquals(
            $helper->render(),
            'No content entries found.',
            'Expected empty list. ' . __LINE__
        );
    }

    /**
     * Test render with content, but no query; ensure that all items are rendered.
     */
    public function testContentRender()
    {
        $type = $this->_createTestType();
        for ($x = 0; $x < 5; ++$x) {
            $this->_createTestEntry($type, 'test-content-' . $x);
        }

        $helper = new Content_View_Helper_ContentList;
        $helper->setView($this->_view);
        $helper->contentList(new P4Cms_Record_Query());

        $expected = '<ul class="content-list">' . PHP_EOL;
        for ($i = 0; $i < 5; $i++) {
            $expected .= '<li class="content-list-entry-' . ($i + 1)
                      .  ' content-list-entry-' . (($i + 1) % 2 ? 'odd' : 'even')
                      .  ' content-list-type-test-type"><a href="/view/id/test-content-' . $i . '">'
                      .  '<span class=\'value-node\'>Test Title</span></a></li>' . PHP_EOL;
        }
        $expected .= '</ul>' . PHP_EOL;

        $this->assertSame(
            $expected,
            $helper->render(),
            'Did not receive expected output.  Line #' . __LINE__
        );
    }

    /**
     * Test render with content, no query, and a list of fields; ensure that all items are rendered
     * but only requested fields are returned.
     */
    public function testFieldsRender()
    {
        $type = $this->_createTestType();
        for ($x = 0; $x < 5; ++$x) {
            $this->_createTestEntry($type, 'test-content-' . $x);
        }

        $helper = new Content_View_Helper_ContentList;
        $helper->setView($this->_view);
        $helper->contentList(new P4Cms_Record_Query(), array('fields' => array('title', 'abstract')));

        $expected = '<ul class="content-list">' . PHP_EOL;
        for ($i = 0; $i < 5; $i++) {
            $expected .= '<li class="content-list-entry-' . ($i + 1)
                      .  ' content-list-entry-' . (($i + 1) % 2 ? 'odd' : 'even')
                      .  ' content-list-type-test-type">'
                      .  '<span class=\'value-node\'>Test Title</span>'
                      .  '<span class=\'value-node\'>abstract this</span></li>' . PHP_EOL;
        }
        $expected .= '</ul>' . PHP_EOL;

        $this->assertSame(
            $expected,
            $helper->render(),
            'Expected only title and abstract fields.  Line #' . __LINE__
        );
    }

    /**
     * Test render with content, no query, and a list of fields; ensure that all items are rendered
     * but only requested fields are returned; fields should be altered by the provided filter.
     */
    public function testFilteredFieldsRender()
    {
        $type = $this->_createTestType();
        $this->_createTestEntry($type, 'test-content-0');
        $this->_createTestEntry($type, 'test-content-1');

        $options = array(
            'fields' => array(
                'title' => array('filters'=> array('StringToUpper'))
            )
        );

        $helper = new Content_View_Helper_ContentList;
        $helper->setView($this->_view);
        $helper->contentList(new P4Cms_Record_Query(), $options);

        $expected = '<ul class="content-list">' . PHP_EOL
                  . '<li class="content-list-entry-1 content-list-entry-odd'
                  . ' content-list-type-test-type"><span class=\'value-node\'>'
                  . 'TEST TITLE</span></li>' . PHP_EOL
                  . '<li class="content-list-entry-2 content-list-entry-even'
                  . ' content-list-type-test-type"><span class=\'value-node\'>'
                  . 'TEST TITLE</span></li>' . PHP_EOL
                  . '</ul>' . PHP_EOL;

        $this->assertSame(
            $expected,
            $helper->render(),
            'Expected only title and abstract fields.  Line #' . __LINE__
        );
    }


    /**
     * Test render with content, no query, and a list of fields; ensure that all items are rendered
     * but only requested fields are returned; fields should be altered by the provided decorator.
     */
    public function testDecoratedFieldsRender()
    {
        $type = $this->_createTestType();
        $this->_createTestEntry($type, 'test-content-0');
        $this->_createTestEntry($type, 'test-content-1');

        $options = array(
            'fields' => array(
                'title' => array(
                    'decorators'=> array(
                        'Value',
                        array(
                            'decorator' => 'HtmlTag',
                            'options'   => array('tag' => 'div')
                        )
                    )
                )
            )
        );

        $helper = new Content_View_Helper_ContentList;
        $helper->setView($this->_view);
        $helper->contentList(new P4Cms_Record_Query(), $options);

        $expected = '<ul class="content-list">' . PHP_EOL
                  . '<li class="content-list-entry-1 content-list-entry-odd'
                  . ' content-list-type-test-type"><div><span class=\'value-node\'>'
                  . 'Test Title</span></div></li>' . PHP_EOL
                  . '<li class="content-list-entry-2 content-list-entry-even'
                  . ' content-list-type-test-type"><div><span class=\'value-node\'>'
                  . 'Test Title</span></div></li>' . PHP_EOL
                  . '</ul>' . PHP_EOL;

        $this->assertSame(
            $expected,
            $helper->render(),
            'Expected decorated title field.  Line #' . __LINE__
        );
    }

    /**
     * Test render with content, no query, and a list of fields that have default decorators;
     * ensure that all items are rendered but only requested fields are returned;
     * fields should be altered by the default decorators.
     */
    public function testDefaultDecoratedRender()
    {
        $elements = array(
            'title' => array(
                'type'      => 'text',
                'options'   => array('label' => 'Title', 'required' => true),
            ),
            'image'  => array(
                'type'      => 'imageFile',
                'options'   => array('label' => 'Pic'),
            ),
            'id'        => array(
                'type'      => 'text',
                'options'   => array('label' => 'ID', 'required' => true)
            )
        );

        $type = new P4Cms_Content_Type;
        $type->setId("test-type")
             ->setLabel("Test Type")
             ->setElements($elements)
             ->setValue('icon', file_get_contents(TEST_ASSETS_PATH . '/images/content-type-icon.png'))
             ->setFieldMetadata('icon', array("mimeType" => "image/png"))
             ->save();

        $entry = new P4Cms_Content;
        $entry->setContentType($type)
             ->setValue('title', 'Test Title')
             ->setValue('image', file_get_contents(TEST_ASSETS_PATH . '/images/content-type-icon.png'))
             ->setFieldMetadata(
                'image',
                array(
                    "mimeType" => "image/png",
                    "filename" => 'content-type-icon.png'
                )
             )
             ->setId('file-content')
             ->save('a test entry');

        $options = array('fields' => array('image'));

        $helper = new Content_View_Helper_ContentList;
        $helper->setView($this->_view);
        $helper->contentList(new P4Cms_Record_Query(), $options);

        $expected = '<ul class="content-list">' . PHP_EOL
                  . '<li class="content-list-entry-1 content-list-entry-odd content-list-type-test-type">'
                  . '<img orientation="" alt="content-type-icon.png" src="/image/id/file-content/field/image/v/1">'
                  . '</li>' . PHP_EOL . '</ul>' . PHP_EOL;

        $this->assertSame(
            $expected,
            $helper->render(),
            'Expected decorated title field.  Line #' . __LINE__
        );
    }

    /**
     * Test render with content and a query; ensure that expected items are rendered.
     */
    public function testContentQuery()
    {
        $type = $this->_createTestType();
        for ($x = 0; $x < 5; $x++) {
            $this->_createTestEntry($type, 'test-content-' . $x);
        }

        $entry = new P4Cms_Content;
        $entry->setContentType($type)
             ->setValue('title', 'More Content')
             ->setValue('body', 'The body of the test')
             ->setValue('abstract', 'abstract this')
             ->setId('more-content')
             ->save('a test entry');

        $query = new P4Cms_Record_Query;
        $query->addFilter(P4Cms_Record_Filter::create()->add('contentType', 'test-type'));

        $helper = new Content_View_Helper_ContentList;
        $helper->setView($this->_view);
        $helper->contentList($query);

        $expected = '<ul class="content-list">' . PHP_EOL;
        for ($i = -1; $i < 5; $i++) {
            $id        = $i >= 0 ? "test-content-$i" : "more-content";
            $title     = $i >= 0 ? "Test Title" : "More Content";
            $expected .= '<li class="content-list-entry-' . ($i + 2)
                      .  ' content-list-entry-' . (($i + 2) % 2 ? 'odd' : 'even')
                      .  ' content-list-type-test-type"><a href="/view/id/' . $id . '">'
                      .  '<span class=\'value-node\'>' . $title . '</span></a></li>' . PHP_EOL;
        }
        $expected .= '</ul>' . PHP_EOL;

        $this->assertSame(
            $expected,
            $helper->render(),
            'Expected 6 content entries returned by query with content type filter.  Line #' . __LINE__
        );

        $query = new P4Cms_Record_Query;
        $query->addFilter(P4Cms_Record_Filter::create()->add('title', 'More Content'));

        $helper->contentList($query);

        $expected = '<ul class="content-list">' . PHP_EOL
                  . '<li class="content-list-entry-1 content-list-entry-odd'
                  . ' content-list-type-test-type"><a href="/view/id/more-content">'
                  . '<span class=\'value-node\'>More Content</span></a></li>' . PHP_EOL
                  . '</ul>' . PHP_EOL;

        $this->assertSame(
            $expected,
            $helper->render(),
            'Expected 1 content entry returned by query with filter.  Line #' . __LINE__
        );
    }

    /**
     * Test rendering the list with a template.
     */
    public function testTemplateRender()
    {
        $type = $this->_createTestType();
        for ($x = 0; $x < 5; ++$x) {
            $this->_createTestEntry($type, 'test-content-' . $x);
        }

        $this->_view->setScriptPath(__DIR__ . '/');

        $helper = new Content_View_Helper_ContentList;
        $helper->setView($this->_view);
        $helper->contentList(
            new P4Cms_Record_Query(),
            array('template' => $this->_templateFile)
        );

        $this->assertSelectCount(
            'ul li',
            $x,
            $helper->render(),
            'Expected ' . $x . ' content entries.  Line #' . __LINE__
        );
    }
}