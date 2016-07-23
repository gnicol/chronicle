<?php
/**
 * Test the ShareThis configure controller.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Sharethis_Test_ConfigureControllerTest extends ModuleControllerTest
{
    protected $_shareThisModule;

    /**
     * Perform setup.
     */
    public function setUp()
    {
        parent::setUp();

        // install default content types
        P4Cms_Content_Type::installDefaultTypes();

        // enable ShareThis module
        $module = P4Cms_Module::fetch('Sharethis');
        $module->enable()->load();
        $this->_shareThisModule = $module;
    }

    /**
     * Ensure that this module has not broken the modules list when dispached to manage modules.
     */
    public function testManageModules()
    {
        $this->utility->impersonate('administrator');

        // test that this module has not broken the list
        $this->dispatch('/site/module');

        $this->assertModule('site', 'expected "site" module');
        $this->assertController('module', 'expected "module" controller');
        $this->assertAction('index', 'expected "index" action');

        $this->assertQuery("div.module-grid");
        $this->assertQuery("div.module-grid table");
        $this->assertQuery("div.module-grid thead");

        // dispatch again to get the module inventory
        $this->resetRequest()->resetResponse();
        $this->dispatch('/site/module/format/json');
        $this->assertModule('site', 'expected "site" module for JSON');
        $this->assertController('module', 'expected "module" controller for JSON');
        $this->assertAction('index', 'expected "index" action for JSON');

        // ensure that the module can be configured
        $body               = $this->response->getBody();
        $values             = Zend_Json::decode($body);
        $configRouteParams  = $this->_shareThisModule->getConfigRouteParams();
        $this->assertTrue(array_key_exists('items', $values), 'Expect an items entry in JSON output.');

        foreach ($values['items'] as $item) {
            if ($item['name'] !== $this->_shareThisModule->name) {
                continue;
            }

            $this->assertEquals(
                $configRouteParams,
                $item['configRouteParams'],
                'Expected ShareThis module config uri.'
            );
        }
    }

    /**
     * Test that the configuration form works properly.
     */
    public function testConfigure()
    {
        $this->utility->impersonate('editor');

        // test that module controller correctly forwards to module's configure action
        $configUri = $this->_shareThisModule->getConfigUri();
        $this->dispatch($configUri);

        $this->assertModule('sharethis',                    'Expected module.');
        $this->assertController('configure',                'Expected controller');
        $this->assertAction('index',                        'Expected action');

        $this->assertQueryContentContains("h1",             "Configure ShareThis");
        $this->assertQuery("body[class*='manage-layout']",  "Expected manage layout.");

        // verify form content
        $this->assertQuery("form",                          "Expected configuration form.");
        $this->assertQuery("input[name='buttonStyle']",     "Expected 'buttonStyle' element.");
        $this->assertQuery("input[name='services']",        "Expected 'services' element.");
        $this->assertQuery("input[name='contentTypes[]']",  "Expected 'contentTypes' element.");
        $this->assertQuery("input[name='publisherKey']",    "Expected 'publisherKey' element.");
    }

    /**
     * Test good post to save valid data.
     */
    public function testGoodAddPost()
    {
        $this->utility->impersonate('editor');

        $data = array(
            'buttonStyle'   => 'small',
            'services'      => 'a,b,c',
            'contentTypes'  => array('basic-page', 'image'),
            'publisherKey'  => 'xyz'
        );
        $this->request->setMethod('POST');
        $this->request->setPost($data);

        $this->dispatch('/sharethis/configure/index');

        $this->assertModule('sharethis',        'Expected module.');
        $this->assertController('configure',    'Expected controller');
        $this->assertAction('index',            'Expected action');

        // check for saved values
        $module = P4Cms_Module::fetch('Sharethis');
        $config = $module->getConfig();
        $values = $config->toArray();

        $this->assertSame(
            $data,
            $values,
            "Expected config values."
        );
    }

    /**
     * Test bad post data.
     */
    public function testBadAddPost()
    {
        $this->utility->impersonate('editor');

        // form request without required fields
        $this->request->setMethod('POST');
        $this->request->setPost(array('services' => 'a'));

        $this->dispatch('/sharethis/configure/index');
        $responseBody = $this->response->getBody();

        $this->assertModule('sharethis',        'Expected module.');
        $this->assertController('configure',    'Expected controller');
        $this->assertAction('index',            'Expected action');

        $this->assertQueryContentContains(
            'ul.errors',
            "Value is required and can't be empty",
            $responseBody
        );
    }
}
