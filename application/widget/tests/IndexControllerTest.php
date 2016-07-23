<?php
/**
 * Test the widget/index controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widget_Test_IndexControllerTest extends ModuleControllerTest
{
    /**
     * Clear caches prior to start of each test.
     */
    public function setUp()
    {
        parent::setUp();
        P4Cms_Widget_Type::clearCache();

        // install default widgets.
        P4Cms_Widget::installDefaults();
    }

    /**
     * Test that a bad widget request is caught.
     */
    public function testBadIndexRequest()
    {
        // test that action requires widget param.
        $this->dispatch('/widget/index');
        $this->assertModule('error');
        $this->assertAction('error');
    }

    /**
     * Test that widgets can be run properly via the controller.
     */
    public function testGoodIndexRequest()
    {
        $id = P4Cms_Uuid::fromMd5(md5('default-test-1'))->get();
        $this->dispatch('/widget/index/index/region/test/widget/' . $id);
        $responseBody = $this->response->getBody();

        $this->assertModule('widget', 'Expected widget module.');
        $this->assertController('index', 'Expected index controller.');
        $this->assertAction('index', 'Expected index action.');

        // check that output looks sane.
        $this->assertQuery('div#widget-' . $id, 'expected widget container div.'. $responseBody);
        $this->assertQuery('div[regionName="test"]', $responseBody);

        // ensure generated dialog title is as expected
        $widgetDialogTitleAttrPattern = 'widgetDialogTitle="Text&#x20;Widget"';
        $this->assertTrue(
            preg_match("/$widgetDialogTitleAttrPattern/",   $responseBody) === 1,
            $responseBody
        );
    }

    /**
     * Test that delete action rejects bad requests.
     */
    public function testBadDeleteRequest()
    {
        $this->utility->impersonate('editor');

        $this->dispatch('/widget/index/delete/region/test/widget/1');
        $this->assertModule('error');
        $this->assertAction('error');
    }

    /**
     * Test that controller can delete widgets.
     */
    public function testGoodDeleteRequest()
    {
        $this->utility->impersonate('editor');
        $id = P4Cms_Uuid::fromMd5(md5('default-test-1'))->get();

        // ensure that widget exists.
        try {
            P4Cms_Widget::fetch($id);
            $this->assertTrue(true, 'Expected fetch to succeed');
        } catch (P4Cms_Model_NotFoundException $e) {
            $this->fail('Expected fetch to succeed');
        } catch (Exception $e) {
            $this->fail(__LINE__ .' - Unpected exception: '. $e->getMessage());
        }

        // delete it.
        $this->request->setMethod('POST')
                      ->setPost(array('widget' => $id));
        $this->dispatch('/widget/index/delete');
        $this->assertModule('widget', 'Last module run should be widget module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('delete', 'Expected action');

        // ensure that widget has been deleted.
        try {
            P4Cms_Widget::fetch($id);
            $this->fail('Expected fetch after deletion to fail');
        } catch (P4Cms_Model_NotFoundException $e) {
            $this->assertSame(
                "Cannot fetch record '$id'. Record does not exist.",
                $e->getMessage(),
                'Expected error message'
            );
        } catch (Exception $e) {
            $this->fail(__LINE__ .' - Unpected exception: '. $e->getMessage());
        }
    }

    /**
     * Ensure add widget prompt comes up clean.
     */
    public function testAddWidgetPrompt()
    {
        $this->utility->impersonate('editor');

        $this->dispatch('/widget/index/add');
        $responseBody = $this->response->getBody();
        $this->assertModule('widget', 'Last module run should be widget module; '. $responseBody);
        $this->assertController('index', 'Expected controller; '. $responseBody);
        $this->assertAction('add', 'Expected action; '. $responseBody);

        // ensure response looks correct.
        $this->assertQuery('ul.widget-types', 'Expected widget types markup; '. $responseBody);
        $this->assertQuery('li a.add-widget[widgetType="widget/text"]', 'Expected widget type markup; '. $responseBody);

        // quick verification of widget order
        $labels = array();
        foreach (P4Cms_Widget_Type::fetchAll() as $type) {
            $labels[] = $type->label;
        }
        $this->assertSame(
            array(
                'Associated Categories',
                'Content List',
                'IFrame Widget',
                'Image Rotator Widget',
                'Image Widget',
                'Menu Widget',
                'Text Widget'
            ),
            $labels,
            'Expected order of widget labels'
        );
    }

    /**
     * Ensure widgets can be added via post request.
     */
    public function testAddWidgetPost()
    {
        $this->utility->impersonate('editor');

        // determine how many widgets currently exist in the test region.
        $widgets   = P4Cms_Widget::fetchByRegion('test');
        $widgetIds = $widgets->invoke('getId');
        $count     = count($widgetIds);

        $this->request->setMethod('POST')
            ->setPost(
                array(
                    'region' => 'test',
                    'type'   => 'widget/text'
                )
            );
        $this->dispatch('/widget/index/add');
        $responseBody = $this->response->getBody();
        $this->assertModule('widget', 'Last module run should be widget module; '. $responseBody);
        $this->assertController('index', 'Expected controller; '. $responseBody);
        $this->assertAction('add', 'Expected action; '. $responseBody);

        // ensure response contains uuid of new widget
        $uuid      = Zend_Json::decode($responseBody);
        $validator = new P4Cms_Uuid;
        $this->assertTrue($validator->isValid($uuid));

        // ensure region contains correct set of widgets.
        $widgets = P4Cms_Widget::fetchByRegion('test');
        $this->assertEquals(count($widgets), $count + 1, 'Expecting two widgets');
        $this->assertTrue(P4Cms_Widget::fetch($uuid) instanceof P4Cms_Widget, 'Expecting P4Cms_Widget');
    }

    /**
     * Ensure attempts to configure bogus widget fail.
     */
    public function testBadConfigurePromptRequest()
    {
        $this->utility->impersonate('editor');

        $this->dispatch('/widget/index/configure/region/test/widget/2');
        $this->assertModule('error', 'Expected module');
        $this->assertAction('error');
    }

    /**
     * Ensure attempts to configure good widget succeed.
     */
    public function testGoodConfigurePromptRequest()
    {
        $this->utility->impersonate('editor');
        $id = P4Cms_Uuid::fromMd5(md5('default-test-1'))->get();

        $this->dispatch('/widget/index/configure/region/test/widget/' . $id);
        $responseBody = $this->response->getBody();
        $this->assertModule('widget', 'Last module run should be widget module; '. $responseBody);
        $this->assertController('index', 'Expected controller; '. $responseBody);
        $this->assertAction('configure', 'Expected action; '. $responseBody);

        // verify form looks correct.
        $this->assertQuery("form#widget-$id-config-form");
        $this->assertQuery("input#widget-$id-config-region");
        $this->assertQuery("input#widget-$id-config-widget");
        $this->assertQuery("fieldset#widget-$id-config-general");
        $this->assertQuery("fieldset#widget-$id-config-config");
        $this->assertQuery("input#widget-$id-config-title");
        $this->assertQuery("input#widget-$id-config-showTitle");
        $this->assertQuery("input#widget-$id-config-text");
        $this->assertQuery("div#widget-$id-config-text-Editor");
        $this->assertQuery("button#widget-$id-config-cancel");
        $this->assertQuery("input#widget-$id-config-save");
    }

    /**
     * Ensure widget configuration is validated.
     */
    public function testBadConfigureSaveRequest()
    {
        $this->utility->impersonate('editor');
        $id = P4Cms_Uuid::fromMd5(md5('default-test-1'))->get();

        // this request should fail because it is missing
        // the required title field.
        $this->request->setMethod('POST')
            ->setPost(
                array(
                    'region' => 'test',
                    'widget' => $id
                )
            );
        $this->dispatch('/widget/index/configure');
        $this->assertModule('widget', 'Expected module');
        $this->assertAction('configure');
        $this->assertQuery('ul.errors');
    }

    /**
     * Ensure widget configuration can be saved.
     */
    public function testGoodConfigureSaveRequest()
    {
        $this->utility->impersonate('editor');
        $id = P4Cms_Uuid::fromMd5(md5('default-test-1'))->get();

        $text = 'Hello Test!'
              . ' - baseUrl: {{baseUrl}}'
              . ' - user id: {{user:id}}'
              . ' - user fullname: {{user:fullName}}'
              . ' - user email: {{user:email}}'
              . ' - site title: {{site:title}}'
              . ' - site description: {{site:description}}'
              . ' - site theme: {{site:theme}}'
              . ' - theme baseUrl: {{theme:baseUrl}}'
              . "\n";
        $expandedText = 'Hello Test!'
              . ' - baseUrl: '
              . ' - user id: mweiss'
              . ' - user fullname: Michael T. Weiss'
              . ' - user email: mweiss@thepretender.tv'
              . ' - site title: testsite'
              . ' - site description: description of the test site'
              . ' - site theme: default'
              . ' - theme baseUrl: /tests/phpunit/assets/sites/all/themes/default'
              . "\n";

        $this->request->setMethod('POST')
            ->setPost(
                array(
                    'region' => 'test',
                    'widget' => $id,
                    'title'  => 'A New Title',
                    'order'  => '10',
                    'text'   => $text
                )
            );
        $this->dispatch('/widget/index/configure');
        $responseBody = $this->response->getBody();
        $this->assertModule('widget',    'Expected widget module; '. $responseBody);
        $this->assertController('index', 'Expected index controller; '. $responseBody);
        $this->assertAction('configure', 'Expected configure action; '. $responseBody);

        // verify widget config has changed.
        $widget = P4Cms_Widget::fetch($id);
        $this->assertSame('test',           $widget->region,              'Expected region');
        $this->assertSame($id,              $widget->id,                  'Expected id');
        $this->assertSame('A New Title',    $widget->title,               'Expected title');
        $this->assertSame('10',             $widget->order,               'Expected order');
        $this->assertEquals($text,          $widget->getConfig('text'),   'Expected text');

        // verify the widget renders as expected
        $this->dispatch('/widget/index/index/region/test/widget/' . $id);
        $responseBody = $this->response->getBody();

        $this->assertModule('widget', 'Expected widget module.');
        $this->assertController('index', 'Expected index controller.');
        $this->assertAction('index', 'Expected index action.');

        // check that output looks sane.
        $this->assertQuery('div#widget-' . $id, 'expected widget container div.'. $responseBody);
        $this->assertQuery('div[regionName="test"]', $responseBody);

        // ensure generated dialog title is as expected
        $widgetDialogTitleAttrPattern = 'widgetDialogTitle="Text&#x20;Widget"';
        $this->assertTrue(
            preg_match("/$widgetDialogTitleAttrPattern/",   $responseBody) === 1,
            $responseBody
        );

        // verify that the macros got expanded
        $this->assertQueryContentContains('div.widget-content', $expandedText, 'Expected content in '. $responseBody);
    }

    /**
     * Test escaping of various values
     */
    public function testSecurity()
    {
        $this->utility->impersonate('editor');
        $id = P4Cms_Uuid::fromMd5(md5('default-test-1'))->get();

        $this->request->setMethod('POST')
            ->setPost(
                array(
                    'region'    => 'test',
                    'widget'    => $id,
                    'title'     => '<script>alert("test")</script> & ok',
                    'showTitle' => '1',
                    'order'     => '10',
                    'text'      => 'test <a>test</a> 1 & 2'
                )
            );
        $this->dispatch('/widget/index/configure');
        $responseBody = $this->response->getBody();
        $this->assertModule('widget', 'Last module run should be widget module; '. $responseBody);
        $this->assertController('index', 'Expected controller; '. $responseBody);
        $this->assertAction('configure', 'Expected action; '. $responseBody);

        // ensure title and text are escaped
        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/widget/index/index/region/test/widget/' . $id);
        $responseBody = $this->response->getBody();

        $this->assertModule('widget', 'Last module run should be widget module.');
        $this->assertController('index');
        $this->assertAction('index');

        // widget title in the html should be escaped
        $widgetTitleHtmlPattern   = '<div id="widget\-' . str_replace('-', '\\-', $id)
            . '-title" class="widget\-title">\s+'
            . '&lt;script&gt;alert\(&quot;test&quot;\)&lt;\/script&gt; &amp; ok';

        // widget content should not be escaped as dojo editor produces html entities conversion
        $widgetContentHtmlPattern = '<div id="widget\-' . str_replace('-', '\\-', $id)
            . '\-content" class="widget\-content">\s+test <a>test<\/a> 1 & 2';

        // check that output looks escaped
        $this->assertTrue(
            preg_match("/$widgetTitleHtmlPattern/",   $responseBody) === 1
        );
        $this->assertTrue(
            preg_match("/$widgetContentHtmlPattern/", $responseBody) === 1
        );
    }

    /**
     * Add test for form action.
     */
    public function testFormAction()
    {
        $this->utility->impersonate('administrator');
        $id = P4Cms_Uuid::fromMd5(md5('default-test-1'))->get();

        $this->dispatch('/widget/index/form/region/test/widget/' . $id);
        $responseBody = $this->response->getBody();

        $this->assertModule('widget', 'Last module run should be widget module; '. $responseBody);
        $this->assertController('index', 'Expected controller; '. $responseBody);
        $this->assertAction('form', 'Expected action; '. $responseBody);

        // verify form looks correct.
        $this->assertQuery("form#widget-$id-config-form");
        $this->assertQuery("input#widget-$id-config-region");
        $this->assertQuery("input#widget-$id-config-widget");
        $this->assertQuery("fieldset#widget-$id-config-general");
        $this->assertQuery("fieldset#widget-$id-config-config");
        $this->assertQuery("input#widget-$id-config-title");
        $this->assertQuery("input#widget-$id-config-showTitle");
        $this->assertQuery("input#widget-$id-config-text");
        $this->assertQuery("div#widget-$id-config-text-Editor");
        $this->assertQuery("button#widget-$id-config-cancel");
        $this->assertQuery("input#widget-$id-config-save");
    }
}