<?php
/**
 * Test the widget/image controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widget_Test_ImageWidgetControllerTest extends ModuleControllerTest
{
    protected $_widget = null;
    protected $_type   = null;
    protected $_entry  = null;

    /**
     * Clear caches prior to start of each test.
     */
    public function setUp()
    {
        parent::setUp();
        P4Cms_Widget_Type::clearCache();

        // install default widgets.
        P4Cms_Widget::installDefaults();

        // add an image widget
        $this->_widget = P4Cms_Widget::factory('widget/image');
        $this->_widget->setValue('region', 'test')
                      ->save();
    }

    /**
     * Test image widget partial output
     */
    public function testPartial()
    {
        $id = $this->_widget->getId();
        $this->dispatch('/widget/index/index/format/partial/region/test/widget/' . $id);
        $responseBody = $this->response->getBody();

        $this->assertModule('widget', 'Expected widget module.');
        $this->assertController('index', 'Expected index controller.');
        $this->assertAction('index', 'Expected index action.');

        // an empty rotator should have no output
        $this->assertRegExp("/id=\"widget-$id\"/", $responseBody, 'Expected widget id');
        $this->assertRegExp(
            '/widgetType="widget&#x2f;image"/',
            $responseBody,
            'Expected widget type'
        );
        $this->assertRegExp(
            "/<div id=\"widget-$id-content\" class=\"widget-content\">\s+No image to display\.\s+<\/div>/",
            $responseBody,
            'Expected widget content markup'
        );

        // configure image to use remote URL
        $this->resetRequest()->resetResponse();
        $widget = P4Cms_Widget::fetch($id);
        $config = $widget->getConfigAsArray();
        $config['imageSource']  = 'remote';
        $config['imageUrl']     = 'http://www.perforce.com/sites/default/files/perforce_logo.gif';
        $config['contentId']    = null;
        $widget->setConfig(
            array(
                'imageSource'   => 'remote',
                'imageUrl'      => 'http://www.perforce.com/sites/default/files/perforce_logo.gif',
                'contentId'     => null
            )
        )->save();

        $this->dispatch('/widget/index/index/format/partial/region/test/widget/' . $id);
        $responseBody = $this->response->getBody();

        $this->assertModule('widget', 'Expected widget module.');
        $this->assertController('index', 'Expected index controller.');
        $this->assertAction('index', 'Expected index action.');

        // an empty rotator should have no output
        $this->assertRegExp(
            "/id=\"widget-$id\"/",
            $responseBody,
            'Expected widget id for remote URL'
        );
        $this->assertRegExp(
            '/widgetType="widget&#x2f;image"/',
            $responseBody,
            'Expected widget type for remote URL'
        );
        $this->assertRegExp(
            "/<div id=\"widget-$id-content\" class=\"widget-content\">/",
            $responseBody,
            'Expected widget content start for remote URL'
        );
        $this->assertRegExp(
            '/<img.+src="http:\/\/www\.perforce\.com\/sites\/default\/files\/perforce_logo\.gif"'
            . '.+title="Image&#x20;Widget"/',
            $responseBody,
            'Expected widget content image for remote URL'
        );

        // configure image to use local content
        $this->resetRequest()->resetResponse();

        // ensure a content type with a file element exists
        $this->_type = new P4Cms_Content_Type;
        $this->_type->setId('test-type-w-file')
                    ->setLabel('Test Type')
                    ->setElements(
                        array(
                            "title" => array(
                                "type"      => "text",
                                "options"   => array("label" => "Title", "required" => true)
                            ),
                            "file"  => array(
                                "type"      => "file",
                                "options"   => array("label" => "File")
                            )
                        )
                    )
                    ->setValue(
                        'icon',
                        file_get_contents(TEST_ASSETS_PATH . "/images/content-type-icon.png")
                    )
                    ->setFieldMetadata('icon', array("mimeType" => "image/png"))
                    ->setValue('group', 'test2')
                    ->save();

        // create content entry using a real image (200x46 pixels)
        $imageData    = @file_get_contents(TEST_ASSETS_PATH . '/images/perforce-logo.jpg');
        $this->_entry = new P4Cms_Content;
        $this->_entry->setId('image-test')
                     ->setContentType('test-type-w-file')
                     ->setValue('title', 'Test Image')
                     ->setValue('file',  $imageData)
                     ->setFieldMetadata(
                        'file',
                        array('filename' => 'image.jpg', 'mimeType' => 'image/jpg')
                     )
                     ->save();
        $widget = P4Cms_Widget::fetch($id);
        $widget->setConfig(
            array(
                'imageSource'   => 'content',
                'imageUrl'      => null,
                'contentId'     => 'image-test'
            )
        )->save();

        $this->dispatch('/widget/index/index/format/partial/region/test/widget/' . $id);
        $responseBody = $this->response->getBody();

        $this->assertModule('widget', 'Expected widget module.');
        $this->assertController('index', 'Expected index controller.');
        $this->assertAction('index', 'Expected index action.');

        // an empty rotator should have no output
        $this->assertRegExp(
            "/id=\"widget-$id\"/",
            $responseBody,
            'Expected widget id for local content'
        );
        $this->assertRegExp(
            '/widgetType="widget&#x2f;image"/',
            $responseBody,
            'Expected widget type for local content'
        );
        $this->assertRegExp(
            "/<div id=\"widget-$id-content\" class=\"widget-content\">/",
            $responseBody,
            'Expected widget content start for local content'
        );
        $this->assertRegExp(
            '/<img.+src="\/image\/id\/image-test" .+title="Image&#x20;Widget"/',
            $responseBody,
            'Expected widget content image for local content'
        );

    }

    /**
     * Test the rotator's configuration structure.
     */
    public function testConfigureRequest()
    {
        $this->utility->impersonate('editor');

        $id = $this->_widget->getId();
        $this->dispatch('/widget/index/configure/region/test/widget/' . $id);
        $responseBody = $this->response->getBody();

        $this->assertModule('widget', 'Last module run should be widget module; '. $responseBody);
        $this->assertController('index', 'Expected controller; '. $responseBody);
        $this->assertAction('configure', 'Expected action; '. $responseBody);

        // verify form looks correct.
        $this->assertQuery(
            "input#widget-$id-config-imageSource-content",
            'Expected image source -content- element in form'
        );
        $this->assertQuery(
            "input#widget-$id-config-imageSource-remote",
            'Expected image source -remote- element in form'
        );
        $this->assertQuery(
            "input#widget-$id-config-contentTitle",
            'Expected content title element in form'
        );
        $this->assertQuery(
            "input#widget-$id-config-imageUrl",
            'Expected image URL element in form'
        );
        $this->assertQuery(
            "select#widget-$id-config-sizeType",
            'Expected size type element in form'
        );
        $this->assertQuery(
            "input#widget-$id-config-imageWidth",
            'Expected imageWidth element in form'
        );
        $this->assertQuery(
            "input#widget-$id-config-imageHeight",
            'Expected imageHeight element in form'
        );
        $this->assertQuery(
            "input#widget-$id-config-lockRatio",
            'Expected lockRatio element in form'
        );
        $this->assertQuery(
            "input#widget-$id-config-imageAlt",
            'Expected imageAlt element in form'
        );
        $this->assertQuery(
            "input#widget-$id-config-link",
            'Expected link element in form'
        );
        $this->assertQuery(
            "select#widget-$id-config-linkTarget",
            'Expected linkTarget element in form'
        );
        $this->assertQuery(
            "input#widget-$id-config-alignment-none",
            'Expected alignment-none element in form'
        );
        $this->assertQuery(
            "input#widget-$id-config-alignment-left",
            'Expected alignment-left element in form'
        );
        $this->assertQuery(
            "input#widget-$id-config-alignment-center",
            'Expected alignment-center element in form'
        );
        $this->assertQuery(
            "input#widget-$id-config-alignment-right",
            'Expected alignment-right element in form'
        );
    }
}