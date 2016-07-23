<?php
/**
 * Test the url -> content integration
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Url_Test_ContentIntegrationTest extends ModuleControllerTest
{
    /**
     * Activate url module.
     */
    public function setUp()
    {
        parent::setUp();
        P4Cms_Module::fetch('Url')->enable()->load();

        // turn off exiting in the redirector
        P4Cms_Controller_Action_Helper_Redirector::$unitTestEnabled = true;
    }

    /**
     * Exercise saving content.
     */
    public function testSave()
    {
        $content = $this->_createContent();

        // ensure we have a corresponding url record.
        $this->assertTrue(Url_Model_Url::exists('my-page'));

        // ensure we can fetch it by params.
        $this->assertTrue(Url_Model_Url::fetchByContent($content) instanceof Url_Model_Url);
    }

    /**
     * Test deleting urls
     */
    public function testDelete()
    {
        $content = $this->_createContent();

        // delete the content.
        $content->delete();

        // ensure the associated url record is gone too.
        $this->assertFalse(Url_Model_Url::exists('my-page'));

        // ensure we can't fetch it by params.
        try {
            Url_Model_Url::fetchByContent($content);
            $this->fail('Unexpected success fetching url for deleted content');
        } catch (P4Cms_Record_NotFoundException $e) {
            $this->assertTrue(true);
        }

        // ensure we can fetch if we include deleted.
        $this->assertTrue(Url_Model_Url::exists('my-page', array('includeDeleted' => true)));

        // ensure we can fetch by content if we include deleted.
        $url = Url_Model_Url::fetchByContent($content, array('includeDeleted' => true));
        $this->assertTrue($url instanceof Url_Model_Url);
    }

    /**
     * Test dispatching a custom url.
     */
    public function testDispatch()
    {
        $this->utility->impersonate('anonymous');
        $content = $this->_createContent();

        $this->dispatch('/my-page');

        $this->assertRoute(Url_Module::ROUTE);
        $this->assertModule('content');
        $this->assertController('index');
        $this->assertAction('view');
        $this->assertSame('1', $this->request->getParam('id'));

        $this->resetRequest()
             ->resetResponse();
        $this->dispatch('/my-page?action=download');
        $this->assertModule('content');
        $this->assertController('index');
        $this->assertAction('download');
        $this->assertSame('1', $this->request->getParam('id'));
    }

    /**
     * Test dispatching a custom url for a deleted content entry.
     */
    public function testDispatchDeleted()
    {
        $this->utility->impersonate('anonymous');
        $content = $this->_createContent();
        $content->delete();

        $this->dispatch('/my-page');

        // verify custom url mapping is gone.
        $this->assertFalse(Url_Model_Url::exists('my-page'));

        $this->assertRoute(Url_Module::ROUTE);
        $this->assertModule('error');
        $this->assertController('index');
        $this->assertAction('page-not-found');
        $this->assertResponseCode(404);
    }

    /**
     * Test dispatching an outdated custom url.
     */
    public function testDispatchOutdated()
    {
        $this->utility->impersonate('anonymous');
        $content = $this->_createContent();
        $content->setValue('url', array('path' => 'my-new-url'))->save();

        $this->dispatch('/my-page');

        // verify custom url mapping is gone.
        $this->assertFalse(Url_Model_Url::exists('my-page'));

        $this->assertRoute(Url_Module::ROUTE);
        $this->assertRedirectTo('/my-new-url');
        $this->assertResponseCode(301);
    }

    /**
     * Exercise helper function for making a url path unique in the system.
     * Also tests isPathRouted() - indirectly.
     */
    public function testMakeUnique()
    {
        // test resolving conflicts against custom urls.
        $params = array('a' => 1, 'b' => 2, 'c' => 3);
        $url    = new Url_Model_Url;
        $url->setPath('foo')
            ->setParams($params)
            ->save();

        // test simple cases.
        $this->assertSame('bar', Url_Module::makePathUnique('bar'));
        $this->assertSame('foo', Url_Module::makePathUnique('foo', $params));
        $this->assertSame('foo-2', Url_Module::makePathUnique('foo'));

        // make some more entries.
        $url->setPath('foo-1')->save();
        $url->setPath('foo-10')->save();
        $url->setPath('foo-2')->save();
        $url->setPath('foo-bar')->save();

        // test resolvable conflict against other custom urls.
        $this->assertSame('foo-11', Url_Module::makePathUnique('foo'));

        // test resolvable conflict against internal route (user module).
        $this->assertSame('user-2', Url_Module::makePathUnique('user'));

        // test resolvable conflict against both internal route and custom url
        $url->setPath('user-2')->save();
        $this->assertSame('user-3', Url_Module::makePathUnique('user'));

        // test un-resolvable conflict against internal route (user/login)
        $this->assertSame('user/login', Url_Module::makePathUnique('user/login'));

        // test resolvable conflict where we have already been assigned a number.
        $url->setParams(array());
        $url->setPath('bar')->save();
        $url->setPath('bar-5')->save();
        $url->setPath('bar-4')->setParams($params)->save();
        $this->assertSame('foo', Url_Module::makePathUnique('foo', $params));
    }

    /**
     * Make a content entry.
     *
     * @return  P4Cms_Content   the created content entry.
     */
    protected function _createContent()
    {
        // install default types and disable workflow on basic page.
        // this makes basic pages implicitly published allowing anonymous
        // users to get access to them.
        P4Cms_Content_Type::installDefaultTypes();
        $type = P4Cms_Content_Type::fetch('basic-page');
        $type->setValue('workflow', null)
             ->save();

        $content = new P4Cms_Content;
        $content->setValues(
            array(
                'id'          => 1,
                'contentType' => 'basic-page',
                'title'       => 'My Page',
                'body'        => 'My page body text.',
                'url'         => array(
                    'auto'    => true,
                    'path'    => '/my-page'
                )
            )
        );

        return $content->save();
    }
}
