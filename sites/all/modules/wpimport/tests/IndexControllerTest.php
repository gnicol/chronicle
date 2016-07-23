<?php
/**
 * Test the WordPress import index controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Wpimport_Test_IndexControllerTest extends ModuleControllerTest
{
    protected $_wpimportModule;

    /**
     * Perform setup
     */
    public function setUp()
    {
        parent::setUp();

        $this->_wpimportModule = P4Cms_Module::fetch('Wpimport');
        $this->_wpimportModule->enable();
        $this->_wpimportModule->load();
    }

    /**
     * Tests the index action of the manage controller.
     */
    public function testIndex()
    {
        $this->utility->impersonate('administrator');

        $this->dispatch('/wpimport/index/index');
        $body = $this->response->getBody();

        $this->assertModule('wpimport', __LINE__ .': Last module run should be generation module.' . $body);
        $this->assertController('index', __LINE__ .': Expected controller' . $body);
        $this->assertAction('index', __LINE__ .': Expected action' . $body);

        //verify form content
        $this->assertQuery("form",                      "Expected configuration form.");
        $this->assertQuery("input[name='importfile']",  "Expected file input.");
    }
}
