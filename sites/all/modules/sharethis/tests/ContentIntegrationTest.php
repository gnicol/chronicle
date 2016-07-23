<?php
/**
 * Test that sharethis is integrated with content as expected.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Sharethis_Test_ContentIntegrationTest extends ModuleControllerTest
{
    /**
     * Setup tests - install default content types as sharethis uses entries types
     * to determine whether to show buttons by default or not.
     */
    public function setUp()
    {
        parent::setUp();

        // install default content types and workflows
        P4Cms_Content_Type::installDefaultTypes();
        Workflow_Model_Workflow::installDefaultWorkflows();

        // enable ShareThis module
        $module = P4Cms_Module::fetch('Sharethis');
        $module->enable()->load();
    }

    /**
     * Ensure sharethis sub-form is present when adding content.
     */
    public function testContentSubForm()
    {
        $this->utility->impersonate('editor');

        // verify that if new entry is created, sharethis sub-form will contain 'showButtons'
        // checkbox checked by default for blog-post and press-release types
        $types = P4Cms_Content_Type::fetchAll();
        $query = '#sharethis-showButtons[checked="checked"]';
        foreach ($types->invoke('getId') as $typeId) {
            $this->resetRequest()->resetResponse();
            $this->dispatch('/content/add/type/' . $typeId);

            // ensure that sharethis sub-form is here
            $this->assertQuery('.sharethis-sub-form');

            // verify that showButtons checkbox has correct check state depending
            // on content type
            $assertMethod = in_array($typeId, array('blog-post', 'press-release'))
                ? 'assertQuery'
                : 'assertNotQuery';
            $this->$assertMethod($query);
        }
    }

    /**
     * Ensure saving the content with sharethis values works.
     */
    public function testContentSave()
    {
        $this->utility->impersonate('editor');

        $this->request->setMethod('post')
                      ->setPost('format',      'json')
                      ->setPost('contentType', 'basic-page')
                      ->setPost('title',       'test-title')
                      ->setPost('body',        'test-body')
                      ->setPost(
                        'sharethis',
                        array(
                            'showButtons'   => true
                        )
                      );

        $this->dispatch('/content/add/');

        $data    = Zend_Json::decode($this->getResponse()->getBody());
        $content = P4Cms_Content::fetch($data['contentId']);
        $values  = $content->getValues();
        $this->assertSame(
            true,
            (bool) $values['sharethis']['showButtons'],
            "Expected showButtons value for saved value."
        );
    }

    /**
     * Ensure editing shows saved sharethis values for the entry.
     */
    public function testContentEdit()
    {
        $this->utility->impersonate('editor');

        // create an entry and set showButtons to true
        $entry = $this->_createEntry(true);

        // ensure that sharethis sub-form is here
        $this->dispatch('/content/edit/id/' . $entry->getId());
        $this->assertQuery('.sharethis-sub-form');

        // ensure showButtons checkbox is checked when edit
        $this->assertQuery('#sharethis-showButtons[checked="checked"]');
    }

    /**
     * Ensure sharethis container is rendered with the entry if 'showButtons'
     * value was set to true.
     */
    public function testContentRender()
    {
        $this->utility->impersonate('anonymous');

        // create an entry and set showButtons to true
        $entry = $this->_createEntry(true);

        // ensure that sharethis buttons container is rendered with the entry
        $this->dispatch('/content/view/id/' . $entry->getId());
        $this->assertQuery('div.sharethis-container');

        // change showButtons value to false and verify that buttons container
        // will not be shown
        $entry = $this->_createEntry(false);
        $this->resetRequest()->resetResponse();
        $this->dispatch('/content/view/id/' . $entry->getId());
        $this->assertNotQuery('div.sharethis-container');
    }

    /**
     * Verify markup of the buttons in sharethis container attached to the content.
     */
    public function testButtonsMarkup()
    {
        // configure share this module as the buttons markup depends on it
        $services     = array('x', 'y', 'z', 'foo', 'bar');
        $configValues = array(
            'buttonStyle'   => 'small',
            'services'      => implode(',', $services),
            'contentTypes'  => array('basic-page'),
            'publisherKey'  => ''
        );

        $module = P4Cms_Module::fetch('Sharethis');
        $module->saveConfig($configValues);

        // create an entry and set showButtons to true
        $entry = $this->_createEntry(true);

        // dispatch to view the entry
        $this->utility->impersonate('anonymous');
        $this->dispatch('/content/view/id/' . $entry->getId());

        // ensure all buttons are present in the markup
        foreach ($services as $service) {
            $this->assertQuery('.sharethis-container .buttons .st_' . $service);
        }

        // ensure buttons are in given order
        // we map services to positions of their respective buttons in the output body markup
        // and verify that values this array are in increased order
        $body            = $this->getResponse()->getBody();
        $buttonPositions = array_map(
            function($service) use ($body)
            {
                return strpos($body, "<span class='st_" . $service . "'></span>");
            },
            $services
        );

        // ensure values in buttonPositions array are in increasing order by comparing
        // the array to the same array that is sorted, these two should be the same
        $sortedPositions = $buttonPositions;
        sort($sortedPositions);
        $this->assertSame(
            $sortedPositions,
            $buttonPositions,
            "Expected buttons are rendered in correct order."
        );
    }

    /**
     * Convenience function to create an entry with minimum attribs, but having
     * 'sharethis' options.
     *
     * @param   bool    $showButtons    value to set on sharethis['showButtons']
     * @return  P4Cms_Content           reference to the created content entry
     */
    protected function _createEntry($showButtons)
    {
        return P4Cms_Content::store(
            array(
                'contentType'               => 'basic-page',
                'title'                     => 'test-title',
                'body'                      => 'test-body',
                'workflowState'             => 'published',
                'sharethis'                 => array(
                    'showButtons' => (bool) $showButtons
                )
            )
        );
    }
}