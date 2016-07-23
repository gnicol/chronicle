<?php
/**
 * Test the diff index controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Diff_Test_IndexControllerTest extends ModuleControllerTest
{
    /**
     * Perform setup
     */
    public function setUp()
    {
        parent::setUp();
        $this->utility->impersonate('administrator');
    }
    
    /**
     * Create a type and a entry for testing.
     *
     */
    protected function _createTestTypeAndEntry()
    {
        $elements = array(
            'title' => array(
                'type'      => 'text',
                'options'   => array('label' => 'Title', 'required' => true),
            ),
            'body'  => array(
                'type'      => 'textarea',
                'options'   => array('label' => 'Body'),
            ),
            'abstract'  => array(
                'type'      => 'textarea',
                'options'   => array('label' => 'Abstract'),
            ),
            'id'        => array(
                'type'      => 'text',
                'options'   => array('label' => 'ID', 'required' => true)
            )  
        );

        $type = new P4Cms_Content_Type;
        $type->setId("test-type")
             ->setLabel("Test Type")
             ->setElements($elements)
             ->setValue('icon', file_get_contents(TEST_ASSETS_PATH . '/images/content-type-icon.png'))
             ->setFieldMetadata('icon', array("mimeType" => "image/png"))
             ->save();

        $entry = new P4Cms_Content;
        $entry->setContentType($type)
             ->setValue('title', 'Test Title')
             ->setValue('body', 'The body of the test')
             ->setValue('abstract', 'abstract this')
             ->setId('theId')
             ->save('a test entry');

        return array($type, $entry);
    }
    
    /**
     * create a registered content type.
     */
    protected function _createRegisteredType()
    {
        return P4Cms_Record_RegisteredType::create()
                    ->setId('content')
                    ->setRecordClass('P4Cms_Content')
                    ->setUriCallback(
                        function($id, $action, $params)
                        {
                            return call_user_func(
                                P4Cms_Content::getUriCallback(),
                                P4Cms_Content::fetch($id, array('includeDeleted' => true)),
                                $action,
                                $params
                            );
                        }
                    );
        
    }
    
    /**
     * Test an empty request, expect error.
     */
    public function testBadIndexRequest()
    {
        $this->dispatch('/diff/index/index/');
        $body = $this->response->getBody();
        $this->assertModule('error', 'Expected error module.'. $body);
        $this->assertController('index', 'Expected controller'. $body);
        $this->assertAction('error', 'Expected action'. $body);
    }
        
    /**
     * Test a valid diff reqeust.
     */
    public function testModifiedEntry()
    {
        list($type, $entry) = $this->_createTestTypeAndEntry();
        
        $registeredType = $this->_createRegisteredType();
        
        $entry->setValue('body', 'The revised body of the test')->save();
        
        $this->dispatch(
            '/diff/index/index/format/partial'
            . '/type/'  . $registeredType->getId()
            . '/left/'  . $entry->getId() . '#1'
            . '/right/' . $entry->getId() . '#2'
        );
        
        $body = $this->response->getBody();
        $this->assertModule('diff', 'Last module run should be diff module.'. $body);
        $this->assertController('index', 'Expected controller'. $body);
        $this->assertAction('index', 'Expected action'. $body);
        
        // verify chunk count
        $this->assertQuery('div.count', 'Expected chunk count div.' . $body);
        $this->assertQuery('span.current-diff-chunk', 'Expected current chunk span.' . $body);
        $this->assertQuery('span.total-diff-chunks', 'Expected total chunk span.' . $body);
        $this->assertQueryContentContains('span.total-diff-chunks', '1', 'Expected one chunk.' . $body);
        
        // verify diff & highlighting
        $this->assertQueryContentContains(
            'span.insert', 
            ' revised', 
            'Expected highlighted diff.' . $body
        );
    }
    
    /**
     * creates content, verifies diff to itself (no change)
     */
    public function testNoDifference()
    {
        list($type, $entry) = $this->_createTestTypeAndEntry();
        $registeredType = $this->_createRegisteredType();
        
        $this->dispatch(
            '/diff/index/index/format/partial'
            . '/type/'  . $registeredType->getId()
            . '/left/'  . $entry->getId()
            . '/right/' . $entry->getId()
        );
        
        $body = $this->response->getBody();
        $this->assertModule('diff', 'Last module run should be diff module.'. $body);
        $this->assertController('index', 'Expected controller'. $body);
        $this->assertAction('index', 'Expected action'. $body);
        
        // verify no difference
        $this->assertQuery('div.no-difference', 'Expected no difference.' . $body);
    }
    
    /**
     * diffs two content entries with different types.
     */
    public function testDifferentTypes()
    {
        list($type, $testEntry) = $this->_createTestTypeAndEntry();
        $elements = array(
            'title' => array(
                'type'      => 'text',
                'options'   => array('label' => 'Title', 'required' => true),
            ),
            'excerpt'  => array(
                'type'      => 'textarea',
                'options'   => array('label' => 'Excerpt'),
            ),
            'body'  => array(
                'type'      => 'textarea',
                'options'   => array('label' => 'Body'),
            ),
            'id'        => array(
                'type'      => 'text',
                'options'   => array('label' => 'ID', 'required' => true)
            )  
        );

        $type = new P4Cms_Content_Type;
        $type->setId("test-type-blog")
             ->setLabel("Test Type Blog")
             ->setElements($elements)
             ->setValue('icon', file_get_contents(TEST_ASSETS_PATH . '/images/content-type-icon.png'))
             ->setFieldMetadata('icon', array("mimeType" => "image/png"))
             ->save();

        $blogEntry = new P4Cms_Content;
        $blogEntry->setContentType($type)
             ->setValue('title', 'Test Title')
             ->setValue('excerpt', 'Blog excerpt')
             ->setValue('body', 'The body of the other test')
             ->setId('theOtherId')
             ->save('a test entry');

        
        $registeredType = $this->_createRegisteredType();
        
        $this->dispatch(
            '/diff/index/index/format/partial'
            . '/type/'  . $registeredType->getId()
            . '/left/'  . $testEntry->getId()
            . '/right/' . $blogEntry->getId()
        );
        
        $body = $this->response->getBody();
        $this->assertModule('diff', 'Last module run should be diff module.'. $body);
        $this->assertController('index', 'Expected controller'. $body);
        $this->assertAction('index', 'Expected action'. $body);
        
        // verify chunk count
        $this->assertQuery('div.count', 'Expected chunk count div.' . $body);
        
        $this->assertQueryContentContains(
            'span.current-diff-chunk', 
            '1', 
            'Expected chunk one.' . $body
        );
        
        $this->assertQueryContentContains(
            'span.total-diff-chunks', 
            '4', 
            'Expected extra chunky (4 chunks).' . $body
        );
        
        // verify diff & highlighting
        $this->assertQueryContentContains(
            'span.insert',
            'other ',
            'Expected body difference to be highlighted.' . $body
        );
        
        $this->assertQueryContentContains(
            'span.delete',
            $testEntry->getValue('abstract'),
            'Expected abstract field to be highlighted.' . $body
        );
        
        $this->assertQueryContentContains(
            'span.insert',
            $blogEntry->getValue('excerpt'),
            'Expected excerpt field to be highlighted.' . $body
        );
    }
}