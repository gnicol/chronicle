<?php
/**
 * Test the Feed module widget controller.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Feed_Test_WidgetControllerTest extends ModuleControllerTest
{
    /**
     * Perform setup
     */
    public function setUp()
    {
        parent::setUp();

        // load the Feed module, and install all default widgets
        P4Cms_Module::fetch('Feed')->enable()->load();
        P4Cms_Widget::installDefaults();

        $this->utility->impersonate('editor');
    }

    /**
     * Test the output of the widget with no configuration. 
     */
    public function testControllerNoConfig()
    {
        $widget = $this->_addWidget();
        $this->dispatch('/feed/widget/index/region/test/widget/' . $widget->getId());
        $body = $this->response->getBody();

        $this->assertModule('feed', 'Expected "site" module: '. $body);
        $this->assertController('widget', 'Expected "module" controller: '. $body);
        $this->assertAction('index', 'Expected "index" action: '. $body);

        $this->assertQueryContentContains(
            'div#content div.container',
            'No feed to display.',
            'Expected unconfigured output.' . $body
        );
    }

    /**
     * The the output of the configured widget
     */
    public function testControllerWithConfig()
    {
        $widget = $this->_addWidget();
        $feedUrl = 'http://www.perforce.com/rss/p4releases.rss';
        $values = array(
            'config'=> array(
                'feedUrl'           => $feedUrl,
                'showFeedUrl'       => '1',
                'showDate'          => '1',
                'showDescription'   => '1',
                'maxItems'          => 2
            )
        );
        $widget->setValues($values)->save();

        $this->dispatch('/feed/widget/index/region/test/widget/' . $widget->getId());
        $body = $this->response->getBody();

        $this->assertModule('feed', 'Expected "site" module: '. $body);
        $this->assertController('widget', 'Expected "module" controller: '. $body);
        $this->assertAction('index', 'Expected "index" action: '. $body);

        $this->assertQueryContentContains(
            'div#content div.container div.feed-url a',
            $feedUrl,
            'Expected feedUrl in output.' . $body
        );
    }

    /**
     * Ensure configuration form look correct
     */
    public function testConfigureForm()
    {
        $widget = $this->_addWidget();
        $id = $widget->getId();

        $this->dispatch('/widget/index/configure/region/test/widget/' . $id);
        $body = $this->response->getBody();
        $this->assertModule('widget', 'Last module run should be widget module; '. $body);
        $this->assertController('index', 'Expected controller; '. $body);
        $this->assertAction('configure', 'Expected action; '. $body);

        // verify form looks correct.
        $this->assertQuery("form#widget-$id-config-form");
        $this->assertQuery("input#widget-$id-config-feedUrl");
        $this->assertQuery("input#widget-$id-config-showFeedUrl");
        $this->assertQuery("input#widget-$id-config-showDate");
        $this->assertQuery("input#widget-$id-config-showDescription");
        $this->assertQuery("select#widget-$id-config-maxItems");
    }

    /**
     * Utility function to add the widget to the test region.
     */
    public function _addWidget()
    {
        $widget = P4Cms_Widget::factory('feed/feed');
        $widget->setValue('region', 'test')->save();

        return $widget;
    }
}