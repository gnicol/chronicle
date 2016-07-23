<?php
/**
 * Test the site model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_SiteTest extends TestCase
{
    /**
     * Set core modules path so that site load can find modules.
     * Load test sites and set the sites path to the test sites.
     */
    public function setUp()
    {
        parent::setUp();
        P4Cms_Site::setSitesDataPath(TEST_DATA_PATH . '/sites');
        P4Cms_Module::reset();
        P4Cms_Module::setCoreModulesPath(TEST_ASSETS_PATH . '/core-modules');
        P4Cms_Theme::addPackagesPath(TEST_ASSETS_PATH . '/sites/all/themes');
        P4Cms_PackageAbstract::setDocumentRoot(dirname(TEST_DATA_PATH));
        $this->utility->createTestSites();
    }

    /**
     * Cleanup.
     */
    public function tearDown()
    {
        P4Cms_Module::reset();
        P4Cms_PackageAbstract::setDocumentRoot(null);
        parent::tearDown();
    }

    /**
     * Ensure that sites list can be accessed and is cached.
     */
    public function testSites()
    {
        $sites = P4Cms_Site::fetchAll();
        $this->assertTrue($sites instanceof P4Cms_Model_Iterator, 'Expect sites object site');
        $this->_loadSiteData();
        $site = P4Cms_Site::fetch($this->_qualifySiteId('basic'));
        $this->assertTrue($site instanceof P4Cms_Site, 'Expect site object type');
    }

    /**
     * Ensure that site to url matching logic works as expected.
     */
    public function testSiteUrls()
    {
        $this->_loadSiteData();

        $tests = array(
            'http://basic.com'              => 'basic',
            'http://www.basic.com/'         => 'basic',
            'http://www.basic.com/foo'      => 'basic',
            'http://basic.com:123/'         => 'basic',
            'http://nomatch.com/'           => 'basic',
            'http://othersite.com/'         => 'other',
            'http://subdir.com/foo'         => 'subdir',
            'https://secure.com/'           => 'secure',
        );

        foreach ($tests as $urlString => $expectedId) {
            $url     = Zend_Uri_Http::fromString($urlString);
            $request = new P4Cms_Controller_Request_HttpTestCase($url);
            $request->setServer('HTTPS',     $url->getScheme() == 'https' ? 'on' : 'off');
            $request->setServer('HTTP_HOST', $url->getHost());

            $site    = P4Cms_Site::fetchByRequest($request);
            $id      = $site ? $site->getId() : false;
            $this->assertSame($this->_qualifySiteId($expectedId), $id, 'Expected site id');
        }
    }

    /**
     * Test the fetch method.
     */
    public function testFetchSite()
    {
        $this->_loadSiteData();
        $this->assertTrue(P4Cms_Site::fetch($this->_qualifySiteId('basic')) instanceof P4Cms_Site);

        try {
            P4Cms_Site::fetch($this->_qualifySiteId('alsdkjfksdf'));
            $this->fail('Fetch with bogus id should have failed');
        } catch (P4Cms_Model_NotFoundException $e) {
            $this->assertTrue(true, 'Expect fetch with bogus id to fail');
            $this->assertEquals(
                "Cannot find the specified site.",
                $e->getMessage(),
                'Expected error message'
            );
        } catch (Exception $e) {
            $this->fail(__LINE__ .' - Unexpected exception: '. $e->getMessage());
        }

        try {
            P4Cms_Site::fetch(0);
            $this->fail('Fetch with numeric id should have failed');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true, 'Expect fetch with numeric id to fail');
            $this->assertEquals(
                "No site id given.",
                $e->getMessage(),
                'Expected error message'
            );
        } catch (Exception $e) {
            $this->fail(__LINE__ .' - Unexpected exception: '. $e->getMessage());
        }

        try {
            P4Cms_Site::fetch(false);
            $this->fail('Fetch with false id should have failed');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true, 'Expect fetch with false id to fail');
            $this->assertEquals(
                "No site id given.",
                $e->getMessage(),
                'Expected error message'
            );
        } catch (Exception $e) {
            $this->fail(__LINE__ .' - Unexpected exception: '. $e->getMessage());
        }
    }

    /**
     * Test the key exists method.
     */
    public function testIdExists()
    {
        $this->_loadSiteData();
        $this->assertTrue(P4Cms_Site::exists($this->_qualifySiteId('basic')));
        $this->assertFalse(P4Cms_Site::exists($this->_qualifySiteId('laskdfjsdkfj')));
    }

    /**
     * Test config.
     */
    public function testConfig()
    {
        $site   = $this->_getFirstTestSite();
        $config = $site->getConfig();
        $this->assertTrue($config instanceof P4Cms_Site_Config, 'expected matching type');

        // adjust a config value directly
        $counter = intval($config->counter);
        $config->setValue('counter', ++$counter);

        // adjust values stored in config indirectly
        $title = 'the site title';
        $site->getConfig()->setTitle($title);
        $description = 'description of the site';
        $site->getConfig()->setDescription($description);

        $site->getConfig()->save();
        $site   = $this->_getFirstTestSite();
        $config = $site->getConfig();
        $this->assertSame((string)$counter, $config->counter, 'expected matching count');

        $this->assertEquals($title, $site->getConfig()->getTitle(), 'Expected title.');
        $this->assertEquals($description, $site->getConfig()->getDescription(), 'Expected description.');

        // test invalid arguments
        try {
            $site->getConfig()->setTitle(new stdClass);
            $this->fail('Unexpected success setting a non-string title.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(
                "The provided title is not a string.",
                $e->getMessage(),
                'Expected error message.'
            );
        } catch (Exception $e) {
            $this->fail(
                "Unexpected exception setting a non-string title (" . get_class($e) . '): ' . $e->getMessage()
            );
        }

        try {
            $site->getConfig()->setDescription(new stdClass);
            $this->fail('Unexpected success setting a non-string description.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(
                "The provided description is not a string.",
                $e->getMessage(),
                'Expected error message.'
            );
        } catch (Exception $e) {
            $this->fail(
                "Unexpected exception setting a non-string description (" . get_class($e) . '): ' . $e->getMessage()
            );
        }
    }

    /**
     * Test p4.
     */
    public function testP4()
    {
        $site = $this->_getFirstTestSite();
        $p4   = $site->getConnection();
        $info = $p4->getInfo();

        $this->assertTrue($p4 instanceof P4_Connection_Interface, 'Expected object class');
        $this->assertTrue(is_array($info), 'Info should be an array');

        // ensure site's p4 connection uses a temp client.
        $this->assertRegExp(
            "/^" . P4_Client::TEMP_ID_PREFIX . "/",
            $p4->getClient()
        );
    }

    /**
     * Test getUrl method.
     */
    public function testGetUrl()
    {
        $site = $this->_getFirstTestSite();
        try {
            $url = Zend_Uri_Http::fromString($site->getConfig()->getUrl());
            $this->assertTrue(true, "string url from getUrl should not fail");
        } catch (Zend_Uri_Exception $e) {
            $this->fail($e->getMessage());
        } catch (Exception $e) {
            $this->fail(__LINE__ .' - Unexpected exception: '. $e->getMessage());
        }

        $request = new P4Cms_Controller_Request_HttpTestCase($url);
        $request->setServer('HTTP_HOST', $url->getHost());

        $fetchedSite = P4Cms_Site::fetchByRequest($request);
        $this->assertEquals($site->getId(), $fetchedSite->getId(), 'Expected fetched site');
    }

    /**
     * Try to get a site's current theme.
     */
    public function testThemeAccessors()
    {
        $site   = $this->_getFirstTestSite();
        $config = $site->getConfig();
        unset($config->theme);
        $theme = $config->getTheme();
        $this->assertSame($theme, 'default', 'Expect default theme');

        $config->theme = 'alternative';
        $theme = $config->getTheme();
        $this->assertSame($theme, 'alternative', 'Expect alternative theme');
        $config->setTheme('default');
        $theme = $config->getTheme();
        $this->assertSame($theme, 'default', 'Expect default theme again');
    }

    /**
     * Test site load.
     */
    public function testLoad()
    {
        $site = $this->_getFirstTestSite();

        // clear the active site.
        P4Cms_Site::clearActive();

        try {
            $active = P4Cms_Site::fetchActive();
            $this->fail('Expect fetchActive() to fail');
        } catch(P4Cms_Site_Exception $e) {
            $this->assertSame(
                'There is no active (currently loaded) site.',
                $e->getMessage(),
                'Expect fetchActive() to fail when no site is active'
            );
        } catch(Exception $e) {
            $this->fail(__LINE__ .' - Unexpected error: '. $e->getMessage());
        }

        // run load and verify site is active.
        $site->load();
        $this->assertTrue(P4Cms_Site::hasActive(), 'Expect an active site post load');
        $this->assertTrue(
            P4Cms_Site::fetchActive() instanceof P4Cms_Site,
            'Expect correct object type for active site'
        );
    }

    /**
     * Test getting the acl object.
     */
    public function testGetAcl()
    {
        $site = $this->_getFirstTestSite();

        // should create an acl instance automatically.
        $acl = $site->getAcl();
        $this->assertTrue($acl instanceof P4Cms_Acl);

        // should contain default roles.
        $roles = P4Cms_Acl_Role::fetchAll(null, $site->getStorageAdapter());
        $this->assertSame(count($roles), count($acl->getRoles()));

        // should be able to fetch a second time (this time from memory).
        $acl2 = $site->getAcl();
        $this->assertSame($acl, $acl2);

        // should be able to save acl and have a new site fetch it from storage.
        $acl->add(new P4Cms_Acl_Resource('test-resource'));
        $acl->save();

        $site2 = new P4Cms_Site;
        $site2->setValues($site->getValues());

        $acl3 = $site2->getAcl();
        $this->assertTrue($acl3->has('test-resource'));
    }

    /**
     * Takes a site id (e.g. 'foo') and turns it into a qualified
     * stream id (e.g. '//chronicle-foo/live') using the SITE_PREFIX
     * and DEFAULT_BRANCH constants off the P4Cms_Site class.
     *
     * @param   string|bool     $id     the id to qualify or false
     * @return  string|bool     the qualified id or a flow through false
     */
    protected function _qualifySiteId($id)
    {
        // if false is passed in just return it.
        if ($id === false) {
            return false;
        }

        $prefix = '//' . P4Cms_Site::SITE_PREFIX;
        $suffix = '/'  . P4Cms_Site::DEFAULT_BRANCH;

        return $prefix . $id . $suffix;
    }

    /**
     * Get the first test site model.
     *
     * @return  P4Cms_Site  the first test site.
     */
    protected function _getFirstTestSite()
    {
        reset($this->utility->getTestSites());
        return P4Cms_Site::fetch(key($this->utility->getTestSites()));
    }

    /**
     * Load specific site test data.
     */
    protected function _loadSiteData()
    {
        $this->utility->saveSites(
            array(
                $this->_qualifySiteId('basic') => array(
                    'urls' => array(
                        'basic.com',
                        'www.basic.com'
                    )
                ),
                $this->_qualifySiteId('other') => array(
                    'urls' => array(
                        'othersite.com'
                    )
                ),
                $this->_qualifySiteId('subdir') => array(
                    'urls' => array(
                        'subdir.com/foo'
                    )
                ),
                $this->_qualifySiteId('secure') => array(
                    'urls' => array(
                        'https://secure.com'
                    )
                )
            )
        );
    }
}
