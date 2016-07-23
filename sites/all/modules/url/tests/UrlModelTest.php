<?php
/**
 * Test the url model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Url_Test_UrlModelTest extends ModuleTest
{
    /**
     * Activate url module.
     */
    public function setUp()
    {
        parent::setUp();
        P4Cms_Module::fetch('Url')->enable()->load();
    }

    /**
     * Exercise saving urls.
     */
    public function testSave()
    {
        $params = array(
            'action'        => 'view',
            'controller'    => 'index',
            'id'            => '1',
            'module'        => 'content'
        );

        $url = new Url_Model_Url;
        $url->setPath('my-custom-url-path')
            ->setParams($params)
            ->save();
        
        // ensure we now have one url record.
        $this->assertSame(1, Url_Model_Url::count());
        
        // ensure we can get the url back out.
        $fetched = Url_Model_Url::fetch('my-custom-url-path');
        $this->assertSame($fetched->getPath(), 'my-custom-url-path');
        $this->assertSame($params, $fetched->getParams());
        
        // check for presence of a lookup record.
        $lookups = P4Cms_Record::fetchAll(
            array('paths' => 'urls/by-params/...')
        );
        $this->assertSame(1, $lookups->count());
    }
    
    /**
     * Test deleting urls
     */
    public function testDelete()
    {
        $url = new Url_Model_Url;
        $url->setPath('my-custom-url-path');
        $url->setParams(
            array(
                'module'        => 'content',
                'controller'    => 'index',
                'action'        => 'view',
                'id'            => '1'
            )
        );
        $url->save();
        
        // ensure we now have one url record and one lookup
        $this->assertSame(1, Url_Model_Url::count());
        $lookups = P4Cms_Record::fetchAll(
            array('paths' => 'urls/by-params/...')
        );
        $this->assertSame(1, $lookups->count());
        
        // remove the url.
        $url->delete();
        
        // ensure we now have no url records or lookups
        $this->assertSame(0, Url_Model_Url::count());
        $lookups = P4Cms_Record::fetchAll(
            array('paths' => 'urls/by-params/...')
        );
        $this->assertSame(0, $lookups->count());
    }
    
    /**
     * Test looking up urls by params
     */
    public function testParamLookup()
    {
        $params = array(
            'module'        => 'content',
            'controller'    => 'index',
            'action'        => 'view',
            'id'            => '1'
        );

        $url = new Url_Model_Url;
        $url->setPath('my-custom-url-path')
            ->setParams($params)
            ->save();

        $url = new Url_Model_Url;
        $url->setPath('my-other-url-path')
            ->setParams(array('id' => 2) + $params)
            ->save();
        
        $url = Url_Model_Url::fetchByParams($params);
        $this->assertSame('my-custom-url-path', $url->getPath());
        
        $url = Url_Model_Url::fetchByParams(array('id' => 2) + $params);
        $this->assertSame('my-other-url-path', $url->getPath());        
    }
    
    /**
     * Test looking up urls by content
     */
    public function testContentLookup()
    {
        $url = new Url_Model_Url;
        $url->setPath('my-custom-url-path')
            ->setParams(Url_Model_Url::getContentRouteParams('1'))
            ->save();
        
        $url = Url_Model_Url::fetchByContent('1');
        $this->assertSame('my-custom-url-path', $url->getPath());
    }
    
    /**
     * Test generation of content view route params.
     */
    public function testContentRouteParams()
    {
        $this->assertSame(
            array(
                'module'        => 'content',
                'controller'    => 'index',
                'action'        => 'view',
                'id'            => '1'
            ),
            Url_Model_Url::getContentRouteParams('1')
        );
        
        $entry = new P4Cms_Content;
        $entry->setId('foobar');
        
        $this->assertSame(
            array(
                'module'        => 'content',
                'controller'    => 'index',
                'action'        => 'view',
                'id'            => 'foobar'
            ),
            Url_Model_Url::getContentRouteParams($entry)
        );
    }
}
