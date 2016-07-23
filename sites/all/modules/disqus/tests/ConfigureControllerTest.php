<?php
/**
 * Test the Disqus module config controller.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Disqus_Test_ConfigureControllerTest extends ModuleControllerTest
{
    /**
     *  Message passed to markTestSkipped() in tests that are skipped
     *  due to undefined parameters needed for full module functionality.
     */
    const TEST_SKIP_MESSAGE = 'The variable DISQUS_SHORT_NAME is not defined.
        Any tests against a Disqus widget will therefore be skipped.';

    protected $_disqusModule;

    /**
     * Perform setup
     */
    public function setUp()
    {
        parent::setUp();

        // install default content types
        P4Cms_Content_Type::installDefaultTypes();

        $this->_disqusModule = P4Cms_Module::fetch('Disqus');
        $this->_disqusModule->enable()->load();

        // If DISQUS_SHORT_NAME not explicitly defined, defer to environment.
        if (!defined('DISQUS_SHORT_NAME') && getenv('DISQUS_SHORT_NAME')) {
            define('DISQUS_SHORT_NAME', getenv('DISQUS_SHORT_NAME'));
        }
    }

    /**
     * Ensure that this module has not broken the modules list when dispached to manage modules.
     */
    public function testManageModules()
    {
        $this->utility->impersonate('administrator');

        // test that this module has not broken the list
        $this->dispatch('/site/module');

        $this->assertModule('site',         'Expected "site" module');
        $this->assertController('module',   'Expected "module" controller');
        $this->assertAction('index',        'Expected "index" action');

        $this->assertQuery("div.module-grid");
        $this->assertQuery("div.module-grid table");
        $this->assertQuery("div.module-grid thead");

        // dispatch again to get the module inventory
        $this->resetRequest()->resetResponse();
        $this->dispatch('/site/module/format/json');
        $this->assertModule('site',         'Expected "site" module');
        $this->assertController('module',   'Expected "module" controller');
        $this->assertAction('index',        'Expected "index" action');

        // ensure that the module can be configured
        $body               = $this->response->getBody();
        $values             = Zend_Json::decode($body);
        $configRouteParams  = $this->_disqusModule->getConfigRouteParams();
        $this->assertTrue(
            array_key_exists('items', $values),
            'Expect an items entry in JSON output.'
        );

        foreach ($values['items'] as $item) {
            if ($item['name'] === $this->_disqusModule->name) {
                $this->assertEquals(
                    $configRouteParams,
                    $item['configRouteParams'],
                    'Expected Disqus module config route params.'
                );
            }
        }
    }

    /**
     * Test that the configuration form works properly.
     */
    public function testConfigure()
    {
        $this->utility->impersonate('editor');

        // test that module controller correctly forwards to module's configure action
        $configUri = $this->_disqusModule->getConfigUri();
        $this->dispatch($configUri);

        $this->assertModule('disqus', 'Expected module.');
        $this->assertController('configure', 'Expected controller');
        $this->assertAction('index', 'Expected action');

        $this->assertQueryContentContains("h1", "Configure Disqus");
        $this->assertQuery("body[class*='manage-layout']", "Expected manage layout.");

        // verify form content
        $this->assertQuery("form", "Expected configuration form.");
        $this->assertQuery(
            "input[name='". Disqus_Form_Configure::SHORT_NAME ."']",
            "Expected 'shortName' element."
        );
        $this->assertQuery("input[name='contentTypes[]']", "Expected 'contentTypes' element.");
    }

    /**
     * Test good post to save valid data.
     */
    public function testGoodConfigure()
    {
        $this->utility->impersonate('editor');

        $data = array(
            Disqus_Form_Configure::SHORT_NAME   => 'test-short-name',
            'contentTypes'                      => array('basic-page', 'blog-post')
        );
        $this->request->setMethod('POST');
        $this->request->setPost($data);

        $this->dispatch('/disqus/configure/index');

        $this->assertModule('disqus', 'Expected module.');
        $this->assertController('configure', 'Expected controller');
        $this->assertAction('index', 'Expected action');

        // check for saved values
        $module = P4Cms_Module::fetch('Disqus');
        $values = $module->getConfig()->toArray();

        $this->assertSame(
            $data,
            $values,
            "Expected config values."
        );
    }

    /**
     * Test bad post data.
     */
    public function testBadConfigure()
    {
        $this->utility->impersonate('editor');

        // form request without required fields
        $this->request->setMethod('POST');
        $this->request->setPost(array(Disqus_Form_Configure::SHORT_NAME => ''));

        $this->dispatch('/disqus/configure/index');
        $responseBody = $this->response->getBody();

        $this->assertModule('disqus', 'Expected module.');
        $this->assertController('configure', 'Expected controller');
        $this->assertAction('index', 'Expected action');

        $this->assertQueryContentContains(
            'ul.errors',
            "Value is required and can't be empty",
            $responseBody
        );
    }
}