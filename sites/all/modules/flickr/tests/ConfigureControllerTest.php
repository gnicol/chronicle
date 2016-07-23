<?php
/**
 * Test the Flickr widget/index controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Flickr_Test_ConfigureControllerTest extends ModuleControllerTest
{
    /**
     *  Message passed to markTestSkipped() in tests that are skipped
     *  due to undefined parameters needed for full module functionality.
     */
    const TEST_SKIP_MESSAGE = "
        The variable TEST_FLICKR_KEY is not defined.
        Any tests against a Flickr widget will therefore be skipped.";

    protected $_flickrModule;

    /**
     * Perform setup
     */
    public function setUp()
    {
        parent::setUp();

        $this->_flickrModule = P4Cms_Module::fetch('Flickr');
        $this->_flickrModule->enable();
        $this->_flickrModule->load();

        // If TEST_FLICKR_KEY not explicitly defined, defer to environment.
        if (!defined('TEST_FLICKR_KEY') && getenv('P4CMS_TEST_FLICKR_KEY')) {
            define('TEST_FLICKR_KEY', getenv('P4CMS_TEST_FLICKR_KEY'));
        }
    }

     /**
     * Test that the configuration form works properly.
     */
    public function testConfigure()
    {
        $this->utility->impersonate('administrator');

        // test that this module has not broken the list
        $this->dispatch('/site/module');

        $this->assertModule('site');
        $this->assertController('module');
        $this->assertAction('index');

        $this->assertQuery("div.module-grid");
        $this->assertQuery("div.module-grid table");
        $this->assertQuery("div.module-grid thead");

        // dispatch again to get the module inventory
        $this->resetRequest()->resetResponse();
        $this->dispatch('/site/module/format/json');
        $this->assertModule('site');
        $this->assertController('module');
        $this->assertAction('index');

        // ensure that the module can be configured.
        $body               = $this->response->getBody();
        $values             = Zend_Json::decode($body);
        $configRouteParams  = $this->_flickrModule->getConfigRouteParams();

        $this->assertTrue(array_key_exists('items', $values), 'Expect an items entry in JSON output.');

        foreach ($values['items'] as $item) {
            if ($item['name'] !== $this->_flickrModule->name) continue;

            $this->assertEquals(
                $configRouteParams,
                $item['configRouteParams'],
                'Expected Flickr module configure uri params.'
            );
        }

        // test that module controller correctly forwards to module's configure action.
        $configUri = $this->_flickrModule->getConfigUri();
        $this->resetRequest()->resetResponse();
        $this->dispatch($configUri);

        $this->assertModule('flickr');
        $this->assertController('configure');
        $this->assertAction('index');
        $this->assertQueryContentContains("h1",     "Configure Flickr");
        $this->assertQueryContentContains("label",  "Flickr API Key");

        //verify form content
        $this->assertQuery("form.category-form",    "Expected configuration form.");
        $this->assertQuery("input[name='key']",     "Expected key input.");
        $this->assertQuery("input[type='submit']",  "Expected submit button.");

        // verify labels are present
        $labels = array(
            'key'            => 'Flickr API Key',
        );
        foreach ($labels as $field => $label) {
            $this->assertQueryContentContains("label[for='$field']", $label, "Expected $field label.");
        }
    }

    /**
     * Test of loading the module
     */
    public function testLoadModule()
    {
        if (!defined('TEST_FLICKR_KEY')) {
            $this->markTestSkipped(self::TEST_SKIP_MESSAGE);
            return;
        }

        $this->utility->impersonate('administrator');

        // form request with required fields.
        $this->request->setMethod('POST');

        //tested using P4cms test Flickr API key set via environment variable
        $this->request->setPost('key',          TEST_FLICKR_KEY);
        $this->request->setPost('save',         'save');

        $this->dispatch('/flickr/configure/index');

        $this->assertModule('flickr',           'Expected module.');
        $this->assertController('configure',    'Expected controller');
        $this->assertAction('index',            'Expected action');

        //confirm save
        $this->assertRedirectTo('/site/module', 'Expect redirect to site module index.');

        $this->resetRequest();
        $this->resetResponse();

        // save paths, then reset the module, so we can call load again
        // as load handles adding the new content to the page.
        $packagePaths = $this->_flickrModule->getPackagesPaths();
        $coreModulePaths = $this->_flickrModule->getCoreModulesPath();

        $this->_flickrModule->reset();

        foreach ($packagePaths as $path) {
            $this->_flickrModule->addPackagesPath($path);
        }
        $this->_flickrModule->setCoreModulesPath($coreModulePaths);

        $this->_flickrModule->init();
        $this->_flickrModule->load();

        $this->dispatch('/site/module');
    }

    /**
     * Test good post to save valid data.
     */
    public function testGoodAddPost()
    {
        $this->utility->impersonate('administrator');

        $this->request->setMethod('POST');
        $this->request->setPost('key',          'test-key');
        $this->request->setPost('save',         'save');

        $this->dispatch('/flickr/configure/index');

        $this->assertModule('flickr',           'Expected module.');
        $this->assertController('configure',    'Expected controller');
        $this->assertAction('index',            'Expected action');

        // expect redirect to index.
        $this->assertRedirectTo('/site/module', 'Expect redirect to site module index.');

        $this->resetRequest();
        $this->resetResponse();

        $this->dispatch('/site/module');

        // check for saved google key entry.
        $module = P4Cms_Module::fetch('Flickr');
        $config = $module->getConfig();
        $values = $config->toArray();

        $key = '';
        if (isset($values['key'])) {
            $key = $values['key'];
        }

        $this->assertSame(
            'test-key',
            $key,
            "Expected same flickr key as was posted."
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
        $this->request->setPost('key',          '');
        $this->request->setPost('save',         'save');

        $this->dispatch('/flickr/configure/index');
        $responseBody = $this->response->getBody();

        $this->assertModule('flickr',           'Expected module.');
        $this->assertController('configure',    'Expected controller');
        $this->assertAction('index',            'Expected action');

        $this->assertQueryContentContains(
            'ul.errors',
            "Value is required and can't be empty"
        );
    }
}
