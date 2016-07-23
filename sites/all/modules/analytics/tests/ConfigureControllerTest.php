<?php
/**
 * Test the Analytics configure controller.
 *
 * Because the analytics service is outside of our control, all we can test is that the
 * analytics code was injected accurately - we cannot test that it is working.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Analytics_Test_ConfigureControllerTest extends ModuleControllerTest
{
    protected $_analyticsModule;

    /**
     * Perform setup
     */
    public function setUp()
    {
        parent::setUp();

        if (!defined('TEST_ACCOUNT_NUMBER')) {
            define('TEST_ACCOUNT_NUMBER', 'UA-XXXXX-1');
        }
        $this->_analyticsModule = P4Cms_Module::fetch('Analytics');
        $this->_analyticsModule->enable();
        $this->_analyticsModule->load();
    }

     /**
     * Test that the configuration form works properly.
     */
    public function testConfigure()
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

        // ensure that the module can be configured.
        $body               = $this->response->getBody();
        $values             = Zend_Json::decode($body);
        $configRouteParams  = $this->_analyticsModule->getConfigRouteParams();

        $this->assertTrue(array_key_exists('items', $values), 'Expect an items entry in JSON output.');

        foreach ($values['items'] as $item) {
            if ($item['name'] !== $this->_analyticsModule->name) continue;

            $this->assertEquals(
                $configRouteParams,
                $item['configRouteParams'],
                'Expected Analytics module configure uri params.'
            );
        }

        // test that module controller correctly forwards to module's configure action.
        $configUri = $this->_analyticsModule->getConfigUri();
        $this->resetRequest()->resetResponse();
        $this->dispatch($configUri);

        $this->assertModule('analytics', 'expected "analytics" module');
        $this->assertController('configure', 'expected "configure" controller');
        $this->assertAction('index', 'expected "index" action for analytics');
        $this->assertQueryContentContains("h1",     "Configure Analytics");
        $this->assertQueryContentContains("label",  "Site Profile Id");

        //verify form content
        $this->assertQuery("form",                          "Expected configuration form.");
        $this->assertQuery("input[name='accountNumber']",   "Expected accountNumber input.");
        $this->assertQuery("input[name='customVars[]']",    "Expected customVars input.");
        $this->assertQuery("input[type='submit']",          "Expected submit button.");

        // verify labels are present
        $labels = array(
            'accountNumber' => 'Site Profile Id',
            'customVars'    => 'Tracking Variables'
        );
        foreach ($labels as $field => $label) {
            $this->assertQueryContentContains("label[for='$field']", $label, "Expected $field label.");
        }
    }

    /**
     * Test good post to save valid data.
     */
    public function testGoodAddPost()
    {
        $this->utility->impersonate('administrator');

        $this->request->setMethod('POST');
        $this->request->setPost('accountNumber', TEST_ACCOUNT_NUMBER);
        $this->request->setPost('save',          'save');

        $this->dispatch('/analytics/configure/index');

        $this->assertModule('analytics',        'Expected module.');
        $this->assertController('configure',    'Expected controller');
        $this->assertAction('index',            'Expected action');

        // check for saved tracking code
        $module =        P4Cms_Module::fetch('Analytics');
        $config =        $module->getConfig();
        $values =        $config->toArray();
        $accountNumber = $values['accountNumber'];

        $this->assertSame(
            'UA-XXXXX-1',
            $accountNumber,
            "Expected the same account number as was posted."
        );
    }

    /**
     * Test bad post data.
     */
    public function testBadAddPost()
    {
        $this->utility->impersonate('administrator');

        // form request without required fields.
        $this->request->setMethod('POST');
        $this->request->setPost('accountNumber', '');
        $this->request->setPost('save',          'save');

        $this->dispatch('/analytics/configure/index');
        $responseBody = $this->response->getBody();

        $this->assertModule('analytics',        'Expected module.');
        $this->assertController('configure',    'Expected controller');
        $this->assertAction('index',            'Expected action');

        $this->assertQueryContentContains(
            'ul.errors',
            "Value is required and can't be empty",
            $responseBody
        );
    }
}
