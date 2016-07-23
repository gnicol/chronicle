<?php
/**
 * Test the widget/iframe controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widget_Test_IframeWidgetControllerTest extends ModuleControllerTest
{
    protected $_widget = null;

    /**
     * Clear caches prior to start of each test.
     */
    public function setUp()
    {
        parent::setUp();
        P4Cms_Widget_Type::clearCache();

        // install default widgets.
        P4Cms_Widget::installDefaults();

        // add an iframe widget widget
        $this->_widget = P4Cms_Widget::factory('widget/iframe');
        $this->_widget->setValue('region', 'test')
                      ->save();

    }

    /**
     * Test iframe widget partial output
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
            '/widgetType="widget&#x2f;iframe"/',
            $responseBody,
            'Expected widget type'
        );
        $this->assertRegExp(
            "/<div id=\"widget-$id-content\" class=\"widget-content\">"
            . "\s+<p>IFrame source URL not set\.<\/p>\s+<\/div>/",
            $responseBody,
            'Expected widget content markup'
        );
    }

    /**
     * Test the iframe's configuration structure.
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
            "input#widget-$id-config-iframeSrc",
            'Expected iframe src element in form'
        );
        $this->assertQuery(
            "input#widget-$id-config-iframeWidth",
            'Expected iframeWidth element in form'
        );
        $this->assertQuery(
            "input#widget-$id-config-iframeHeight",
            'Expected iframeHeight element in form'
        );
        $this->assertQuery(
            "select#widget-$id-config-iframeScroll",
            'Expected iframeScroll element in form'
        );
    }
}