<?php
/**
 * Test the generation module index controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Generation_Test_IndexControllerTest extends ModuleControllerTest
{
    protected $_generationModule;

    /**
     * Perform setup
     */
    public function setUp()
    {
        parent::setUp();

        $this->_generationModule = P4Cms_Module::fetch('Generation');
        $this->_generationModule->enable();
        $this->_generationModule->load();
    }

    /**
     * Tests the index action of the manage controller.
     */
    public function testIndex()
    {
        $this->utility->impersonate('administrator');

        $this->dispatch('/generation/index/index');
        $body = $this->response->getBody();

        $this->assertModule('generation', __LINE__ .': Last module run should be generation module.' . $body);
        $this->assertController('index', __LINE__ .': Expected controller' . $body);
        $this->assertAction('index', __LINE__ .': Expected action' . $body);

        //verify form content
        $this->assertQuery("form",                   "Expected configuration form.");
        $this->assertQuery("input[name='count']",    "Expected count input.");
        $this->assertQuery("input[name='generate']", "Expected generate button." . $body);
    }
}
