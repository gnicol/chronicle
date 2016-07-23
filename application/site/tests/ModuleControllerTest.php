<?php
/**
 * Test the module controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Site_Test_ModuleControllerTest extends ModuleControllerTest
{

    /**
     * Test module list page.
     */
    public function testIndex()
    {
        $this->utility->impersonate('administrator');

        // test that basic list renders correctly.
        $this->dispatch('/site/module');
        $this->assertModule(
            'site',
            'Last module should be site, got "'. $this->request->getModuleName() .'"'
        );
        $this->assertController('module', 'Expected controller');
        $this->assertAction('index', 'Expected action');
        $this->assertQuery("div.module-grid");
        $this->assertQuery("div.module-grid table");
        $this->assertQuery("div.module-grid thead");

        // test the json output
        $this->resetRequest()->resetResponse();
        $this->dispatch('/site/module/format/json');
        $body = $this->response->getBody();
        $this->assertModule(
            'site',
            'Last module should be site, got "'. $this->request->getModuleName() .'"'
        );
        $this->assertController('module', 'Expected controller');
        $this->assertAction('index', 'Expected action');

        $body = $this->response->getBody();
        $values = Zend_Json::decode($body);
        $this->assertTrue(array_key_exists('items', $values), 'Expect an items entry in JSON output.');

        $independent = $dependent = array();
        foreach ($values['items'] as $item) {
            // skip core modules
            if ($item['core']) continue;

            $this->assertTrue($item['enabled'] == 0, 'Expect all items to be disabled, got: '. print_r($item, true));
            if ($item['name'] == 'Dependent') $dependent = $item;
            if ($item['name'] == 'Independent') $independent = $item;
        }
    }

    /**
     * Test enabling a module through the controller.
     */
    public function testEnable()
    {
        $this->utility->impersonate('administrator');

        // fabricate request.
        $this->request->setPost(array('moduleName' => 'Independent'));
        $this->request->setMethod('POST');
        $this->dispatch('/site/module/enable');

        // ensure proper dispatch.
        $this->assertModule(
            'site',
            'Last module should be site, got "'. $this->request->getModuleName() .'"'
        );
        $this->assertController('module', 'final controller');
        $this->assertAction('enable', 'final action');

        // should redirect.
        $this->assertRedirect();

        // reset request/response.
        $this->resetRequest()->resetResponse();

        // module should now appear enabled.
        $this->dispatch('/site/module/format/json');
        $this->assertModule('site', 'enabled module');
        $this->assertController('module', 'enabled controller');
        $this->assertAction('index', 'enabled action');

        $body = $this->response->getBody();
        $values = Zend_Json::decode($body);
        $this->assertTrue(array_key_exists('items', $values), 'Expect an items entry in JSON output.');

        foreach ($values['items'] as $item) {
            if ($item['name'] != 'Independent') continue;

            $this->assertTrue($item['enabled'] == 1, 'Expected the Independent module to be enabled.');
        }
    }

    /**
     * Test disabling a module through the controller.
     */
    public function testDisable()
    {
        $this->utility->impersonate('administrator');

        // must enable a module first.
        $this->testEnable();

        // now we can disable - fabricate request.
        $this->request->setPost(array('moduleName'=>'Independent'));
        $this->request->setMethod('POST');
        $this->dispatch('/site/module/disable');

        // ensure proper dispatch.
        $this->assertModule('site');
        $this->assertController('module');
        $this->assertAction('disable');

        // should redirect.
        $this->assertRedirect();

        // reset request/response.
        $this->resetRequest()->resetResponse();

        // module should now appear disabled.
        $this->dispatch('/site/module/format/json');
        $this->assertModule('site');
        $this->assertController('module');
        $this->assertAction('index');

        $body = $this->response->getBody();
        $values = Zend_Json::decode($body);
        $this->assertTrue(array_key_exists('items', $values), 'Expect an items entry in JSON output.');

        foreach ($values['items'] as $item) {
            if ($item['name'] != 'Independent') continue;

            $this->assertTrue($item['enabled'] == 0, 'Expected the Independent module to be disabled.');
        }
    }

    /**
     * Test that a configurable module can be configured.
     */
    public function testConfigure()
    {
        $this->utility->impersonate('administrator');

        // must enable a module first.
        $this->testEnable();

        // test that the configure URL is set
        $this->resetRequest()->resetResponse();
        $this->dispatch('/site/module/format/json');
        $this->assertModule('site');
        $this->assertController('module');
        $this->assertAction('index');

        // build url to configure the module.
        $module = P4Cms_Module::fetch('Independent');
        $uri    = $module->getConfigUri();

        $body = $this->response->getBody();
        $values = Zend_Json::decode($body);
        $this->assertTrue(array_key_exists('items', $values), 'Expect an items entry in JSON output.');

        foreach ($values['items'] as $item) {
            if ($item['name'] != 'Independent') continue;

            $this->assertEquals(
                $module->getConfigRouteParams(),
                $item['configRouteParams'],
                'Expected config URI for Independent.'
            );
        }

        // test that module controller correctly forwards to module's configure action.
        $this->resetRequest()->resetResponse();
        $this->dispatch($uri);
        $this->assertModule('independent');
        $this->assertController('configure');
        $this->assertAction('index');
        $this->assertQueryContentContains("h1", "Configure Independent Module");
    }

    /**
     * Test that package namespace conflicts are avoided.
     */
    public function testPackageConflict()
    {
        $this->utility->impersonate('administrator');

        // test that only one 'independent' module appears.
        $this->dispatch('/site/module/format/json');
        $body = $this->response->getBody();
        $values = Zend_Json::decode($body);
        $this->assertTrue(array_key_exists('items', $values), 'Expect an items entry in JSON output.');

        foreach ($values['items'] as $item) {
            if ($item['name'] != 'Independent') continue;

            $this->assertEquals(0, $item['enabled'], 'Expected Independent to be disabled.');
        }

        // enable the module and verify that there is still only one listed.
        $this->testEnable();
        $this->resetRequest()->resetResponse();
        $this->dispatch('/site/module/format/json');
        $body = $this->response->getBody();
        $values = Zend_Json::decode($body);
        $this->assertTrue(array_key_exists('items', $values), 'Expect an items entry in JSON output.');

        foreach ($values['items'] as $item) {
            if ($item['name'] != 'Independent') continue;

            $this->assertEquals(1, $item['enabled'], 'Expected Independent to be enabled.');
        }
    }
}
