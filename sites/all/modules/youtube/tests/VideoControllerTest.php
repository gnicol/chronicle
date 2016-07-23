<?php
/**
 * Test the video widget controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Youtube_Test_VideoControllerTest extends ModuleControllerTest
{
    /**
     * Clear caches prior to start of each test.
     */
    public function setUp()
    {
        parent::setUp();

        // enable youtube module
        $module = P4Cms_Module::fetch('Youtube');
        $module->enable();
        $module->load();

        // load test theme and widgets
        P4Cms_Widget::installDefaults();

        $this->utility->impersonate('editor');
    }

    /**
     * Utility function to add the widget to the test region.
     */
    public function addWidget()
    {
        $widget = P4Cms_Widget::factory('youtube/youtubevideo')->setId(1);
        $widget->setValue('region', 'test')->save();

        return $widget;
    }

    /**
     * Ensure add widget pane has not been broken with this module enabled.
     */
    public function testAddWidgetPrompt()
    {
        $this->dispatch('/widget/index/add');
        $responseBody = $this->response->getBody();

        // ensure response looks correct.
        $this->assertQuery(
            'ul.widget-types',
            'Expected widget types markup to contain ul.widget-types: '. $responseBody
        );
        $this->assertQueryContentContains(
            'span.widget-type-label',
            'YouTube Video Widget',
            'Expected widget type markup: '. $responseBody
        );
    }

    /**
     * Ensure this widget can be added via post request.
     */
    public function testAddWidgetPost()
    {
        $this->utility->impersonate('editor');

        // determine how many widgets currently exist in the test region.
        $widgets   = P4Cms_Widget::fetchByRegion('test');
        $count     = count($widgets);

        $this->request->setMethod('POST')
            ->setPost(
                array(
                    'region' => 'test',
                    'type'   => 'youtube/youtubevideo'
                )
            );
        $this->dispatch('/widget/index/add');

        $responseBody = $this->response->getBody();

        $this->assertModule('widget', 'Last module run should be widget module; '. $responseBody);
        $this->assertController('index', 'Expected controller; '. $responseBody);
        $this->assertAction('add', 'Expected action; '. $responseBody);

        // ensure response contains uuid of new widget (should be '1')
        $uuid      = Zend_Json::decode($responseBody);
        $validator = new P4Cms_Uuid;
        $this->assertTrue($validator->isValid($uuid));

        // ensure region contains correct set of widgets.
        $widgets = P4Cms_Widget::fetchByRegion('test');
        $this->assertEquals(count($widgets), $count + 1, 'Expecting two widgets');
        $this->assertTrue(P4Cms_Widget::fetch($uuid) instanceof P4Cms_Widget, 'Expecting P4Cms_Widget');
    }

    /**
     * Ensure the widget has not broken the config form, and that all options are displayed
     * as expected.
     */
    public function testGoodConfigurePromptRequest()
    {
        $this->addWidget();

        $this->dispatch('/widget/index/configure/region/test/widget/1');
        $responseBody = $this->response->getBody();

        $this->assertModule('widget', 'Last module run should be widget module; '. $responseBody);
        $this->assertController('index', 'Expected controller; '. $responseBody);
        $this->assertAction('configure', 'Expected action; '. $responseBody);

        // verify form looks correct.
        $this->assertQuery("form#widget-1-config-form", $responseBody);
        $this->assertQuery("input#widget-1-config-region", $responseBody);
        $this->assertQuery("input#widget-1-config-widget", $responseBody);
        $this->assertQuery("fieldset#widget-1-config-general", $responseBody);
        $this->assertQuery("fieldset#widget-1-config-config", $responseBody);

        $this->assertQuery("input#widget-1-config-videoUrl", $responseBody);
        $this->assertQuery("select#widget-1-config-videoSize", $responseBody);
        $this->assertQuery("fieldset#widget-1-config-videoCustomSize", $responseBody);
        $this->assertQuery("select#widget-1-config-controls", $responseBody);
        $this->assertQuery("input#widget-1-config-autoplay", $responseBody);
        $this->assertQuery("input#widget-1-config-loop", $responseBody);
        $this->assertQuery("input#widget-1-config-allowFullscreen", $responseBody);
        $this->assertQuery("input#widget-1-config-playHd", $responseBody);
        $this->assertQuery("input#widget-1-config-showAnnotations", $responseBody);
        $this->assertQuery("input#widget-1-config-showRelated", $responseBody);
    }

    /**
     * Ensure good widget configuration can be saved.
     */
    public function testGoodConfigureSaveRequest()
    {
        $this->addWidget();

        $this->request->setMethod('POST')
            ->setPost(
                array(
                    'region'            => 'test',
                    'widget'            => '1',
                    'title'             => 'YouTube test',
                    'order'             => '10',
                    'config'            => array(
                        'videoUrl'  => 'http://www.youtube.com/watch?v=CDunnQz81FY'
                    )
                )
            );

        $this->dispatch('/widget/index/configure');
        $responseBody = $this->response->getBody();

        $this->assertModule('widget', 'Last module run should be widget module; '. $responseBody);
        $this->assertController('index', 'Expected controller; '. $responseBody);
        $this->assertAction('configure', 'Expected action; '. $responseBody);

        // verify widget config has changed.
        $widget  = P4Cms_Widget::fetch('1');

        $this->assertSame('test',           $widget->region,    'Expected region');
        $this->assertSame('1',              $widget->id,        'Expected id');
        $this->assertSame('YouTube test',   $widget->title,     'Expected title');
        $this->assertSame('10',             $widget->order,     'Expected order');
        $this->assertSame(
            'http://www.youtube.com/watch?v=CDunnQz81FY',
            $widget->getConfig('videoUrl'),
            'Expected text'
        );
    }

    /**
     * Test the results of the index action.
     */
    public function testIndexAction()
    {
        $videoId = 'CDunnQz81FY';
        $widget = $this->addWidget();
        $values = array('config'=>
            array(
                'videoUrl'          => 'http://www.youtube.com/watch?v=' . $videoId,
                'videoSize'         => Youtube_Form_VideoWidget::DIMENSION_MEDIUM,
                'videoHeight'       => 0,
                'videoWidth'        => 0,
                'controls'          => Youtube_Form_VideoWidget::CONTROLS_ALWAYS_SHOW,
                'autoplay'          => true,
                'loop'              => 0,
                'allowFullscreen'   => true,
                'playHd'            => true,
                'showAnnotations'   => 0,
                'showRelated'       => 0
            )
        );

        $widget->setValues($values)->save();

        $this->dispatch('/youtube/video/index/region/test/widget/1');
        $responseBody = $this->response->getBody();

        $expectedUrl = 'http://www.youtube.com/v/' . $videoId . '?version=3&feature=player_embedded&'
            . 'autoplay=1&controls=1&iv_load_policy=3&hd=1&fs=1';

        $this->assertQuery("object", 'Expected "<object>" in response.' . $responseBody);
        $this->assertXpath(
            '//param[@name="movie"][@value="' . $expectedUrl . '"]',
            'Movie param is missing or does not have the expected value.' . $responseBody
        );
        $this->assertXpath(
            '//param[@name="allowFullScreen"][@value="true"]',
            'Expected allowFullScreen param with value "true" in response.' . $responseBody
        );
        $this->assertXpath(
            '//param[@name="allowScriptAccess"][@value="always"]',
            'Expected allowScriptAccess param with value "true" in response.' . $responseBody
        );
        $this->assertXpath(
            '//embed[@src="' . $expectedUrl . '"][@type="application/x-shockwave-flash"][@wmode="opaque"]',
            'Embed src attribute does not have the expected value.' . $responseBody
        );
        $this->assertQuery(
            'embed[allowfullscreen="true"]',
            'Embed "allowfullscreen" attribute does not have the expected value.' . $responseBody
        );
        $this->assertQuery(
            'embed[allowScriptAccess="always"]',
            'Embed "allowScriptAccess" attribute does not have the expected value.' . $responseBody
        );

        $values = array('config'=>
            array(
                'videoUrl'          => 'http://www.youtube.com/watch?v=' . $videoId,
                'videoSize'         => Youtube_Form_VideoWidget::DIMENSION_CUSTOM,
                'videoHeight'       => 500,
                'videoWidth'        => 500,
                'controls'          => Youtube_Form_VideoWidget::CONTROLS_NEVER_SHOW,
                'autoplay'          => false,
                'loop'              => true,
                'allowFullscreen'   => false,
                'playHd'            => false,
                'showAnnotations'   => true,
                'showRelated'       => true
            )
        );

        $widget->setValues($values)->save();

        $this->dispatch('/youtube/video/index/region/test/widget/1');
        $responseBody = $this->response->getBody();

        $expectedUrl = 'http://www.youtube.com/v/' . $videoId . '?version=3&feature=player_embedded&'
            . 'controls=0&rel=1&iv_load_policy=1&loop=1&playlist=' . $videoId;

        $this->assertXpath(
            '//object[@style="height: 500px; width: 500px"]',
            'Expected "<object>" in response.' . $responseBody
        );
        $this->assertXpath(
            '//param[@name="movie"][@value="' . $expectedUrl . '"]',
            'Movie param is missing or does not have the expected value.' . $responseBody
        );

        $this->assertXpath(
            '//embed[@src="' . $expectedUrl . '"][@type="application/x-shockwave-flash"][@wmode="opaque"]'
            .'[@width="500"][@height="500"]',
            'Embed src attribute does not have the expected value.' . $responseBody
        );
    }
}