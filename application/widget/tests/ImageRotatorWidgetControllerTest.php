<?php
/**
 * Test the widget/image-rotator controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widget_Test_ImageRotatorWidgetControllerTest extends ModuleControllerTest
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

        // add default content types
        P4Cms_Content_Type::installDefaultTypes();

        // add an image rotator widget
        $this->_widget = P4Cms_Widget::factory('widget/image-rotator');
        $this->_widget->setValue('region', 'test')
                      ->save();

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
    }

    /**
     * Test image rotator widget partial output
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
            '/widgetType="widget&#x2f;image-rotator"/',
            $responseBody,
            'Expected widget type'
        );
        $this->assertRegExp(
            "/<div id=\"widget-$id-content\" class=\"widget-content\">\s+No images to display\.\s+<\/div>/",
            $responseBody,
            'Expected widget content markup'
        );

        // configure rotator to use content entry
        $this->resetRequest()->resetResponse();
        $widget = P4Cms_Widget::fetch($id);
        $config = $widget->getConfigAsArray();
        $config['images'][] = array(
            'id'        => 'image-test',
            'caption'   => 'the test caption'
        );
        $widget->setConfig($config)
               ->save();

        $this->dispatch('/widget/index/index/format/partial/region/test/widget/' . $id);
        $responseBody = $this->response->getBody();

        $this->assertModule('widget', 'Expected widget module.');
        $this->assertController('index', 'Expected index controller.');
        $this->assertAction('index', 'Expected index action.');

        // an empty rotator should have no output
        $this->assertRegExp("/id=\"widget-$id\"/", $responseBody, 'Expected widget id');
        $this->assertRegExp(
            '/widgetType="widget&#x2f;image-rotator"/',
            $responseBody,
            'Expected widget type'
        );
        $this->assertRegExp(
            "/<div id=\"widget-$id-content\" class=\"widget-content\">/",
            $responseBody,
            'Expected widget content start'
        );
        $this->assertRegExp(
            "/<div class=\"image\" orientation=\"\" title=\"image\.jpg\""
            . " style=\"background-image: url\(\'\/image\/id\/image-test\/v\/1\'\); \"><\/div>/",
            $responseBody,
            'Expected widget content image'
        );
        $this->assertRegExp(
            "/<div class=\"image-caption\">the test caption<\/div>/",
            $responseBody,
            'Expected widget content caption'
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
        $this->assertQuery("div#widget-$id-config-images", 'Expected images element in form');
        $this->assertQuery(
            "input#widget-$id-config-imageWidth",
            'Expected imageWidth element in form'
        );
        $this->assertQuery(
            "input#widget-$id-config-imageHeight",
            'Expected imageHeight element in form'
        );
    }
}