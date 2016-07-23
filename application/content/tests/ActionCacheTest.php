<?php
/**
 * Test caching of content requests.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Test_ActionCacheTest extends ModuleControllerTest
{
    /**
     * Extend parent to pass in bootstrap options enabling the
     * page cache for this test.
     */
    public function setUp()
    {
        parent::setUp('production');
    }

    /**
     * Verify a page request is cached on initial request.
     */
    public function testAnonymousCacheMiss()
    {
        $cacheKey = 'action_6666cd76f96956469e7be39d750cc7d9';

        $this->assertSame(
            0,
            count(P4Cms_Cache::getCache('page')->getIds()),
            'Expected no IDs in page cache intially'
        );

        $this->dispatch('/');
        ob_end_flush();

        $this->assertModule('content', 'Last module run should be content module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('index', 'Expected action');

        // check that output looks correct.
        $this->assertQueryContentContains(
            '#content p',
            'This site does not contain any content.',
            'Expect the correct title.'
        );

        // verify we cached the output
        $this->assertTrue(
            P4Cms_Cache::load($cacheKey, 'page') !== false,
            'Expected page cache to exist post dispatch'
        );

        $this->assertSame(
            2,
            count(P4Cms_Cache::getCache('page')->getIds()),
            'Expected 2 entries in page cache post request'
        );
    }

    /**
     * Verify a page request is not cached for authenticated user.
     */
    public function testAuthenticatedCacheMiss()
    {
        $this->utility->impersonate('administrator');
        P4Cms_Cache::getCache('page')->setUsername('administrator');

        // a cookie must be present for caching to assume we have a
        // session active; add one.
        $_COOKIE['random'] = 'value';

        // verify we have the right key by checking pre/post flush
        $this->assertSame(
            0,
            count(P4Cms_Cache::getCache('page')->getIds()),
            'Expected no IDs in page cache at start'
        );

        $this->dispatch('/');
        ob_end_flush();

        $this->assertModule('content', 'Last module run should be content module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('index', 'Expected action');

        // check that output looks correct.
        $this->assertQueryContentContains(
            '#content p',
            'This site does not contain any content.',
            'Expect the correct title.'
        );

        // verify no caching occured
        $this->assertSame(
            0,
            count(P4Cms_Cache::getCache('page')->getIds()),
            'Expected page cache to be empty post-authenticated dispatch'
        );
    }

    /**
     * Verify an image request is cached for anonymous.
     */
    public function testAuthenticatedCacheHit()
    {
        $this->utility->impersonate('administrator');
        P4Cms_Cache::getCache('page')->setUsername('administrator');

        // a cookie must be present for caching to assume we have a
        // session active; add one.
        $_COOKIE['random'] = 'value';

        // end the current caching as it would die if it hits
        ob_end_flush();

        $this->assertSame(
            0,
            count(P4Cms_Cache::getCache('page')->getIds()),
            'Expected page cache to be empty'
        );


        // get an entry with an image setup
        $type = new P4Cms_Content_Type;
        $type->setId('test-type-w-file')
             ->setLabel('Test Type')
             ->setElements(
                array(
                    "title" => array(
                        "type"      => "text",
                        "options"   => array("label" => "Title", "required" => true)
                    ),
                    "name"  => array(
                        "type"      => "file",
                        "options"   => array("label" => "File")
                    )
                )
             )
             ->setValue('icon', file_get_contents(TEST_ASSETS_PATH . "/images/content-type-icon.png"))
             ->setFieldMetadata('icon', array("mimeType" => "image/png"))
             ->setValue('group', 'test2')
             ->save();
        $entry = new P4Cms_Content;
        $entry->setId('test867-5309')
              ->setContentType('test-type-w-file')
              ->setValue('title', 'Test Image')
              ->setValue('file',  'test image content')
              ->setFieldMetadata(
                'file',
                array('filename' => 'image.jpg', 'mimeType' => 'image/jpg')
              )
              ->save();


        // restart caching, pass doNotDie to allow us to actually test
        $_SERVER['REQUEST_URI'] = '/content/image/id/test867-5309';
        $pageCache = P4Cms_Cache::getCache('page');
        $pageCache->addIgnoredSessionVariable('Zend_Auth')
                  ->addIgnoredSessionVariable('Forms[csrfToken]')
                  ->start(null, true);

        $this->request->setParam('id', 'test867-5309');
        $this->dispatch('/content/image/');
        $this->assertModule('content',   'Expected module.');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('image',  'Expected action');

        // ensure content delivered.
        $this->assertSame(
            $this->response->getBody(),
            "test image content"
        );

        ob_end_clean();

        // verify caching occured
        $this->assertSame(
            2,
            count(P4Cms_Cache::getCache('page')->getIds()),
            'Expected page cache to be populated post-authenticated dispatch'
        );
    }
}
