<?php
/**
 * Test the menu index controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_Test_IndexControllerTest extends ModuleControllerTest
{
    /**
     * Impersonate the administrator for all manage tests.
     */
    public function setUp()
    {   
        parent::setUp();

        $this->utility->impersonate('administrator');
    }
    
    /**
     * Test the sitemap action.
     */
    public function testSitemap()
    {
        // ensure controller provides blank menu if sitemap menu doesn't exist
        if (P4Cms_Menu::exists('sitemap')) {
            P4Cms_Menu::fetch('sitemap')->delete();
        }

        // html
        $this->dispatch('/menu/sitemap');
        $this->assertQueryContentContains("div#content h1", "Sitemap");

        // xml
        $this->resetRequest()->resetResponse();
        $this->dispatch('/menu/sitemap/format/xml');

        $xml = $this->getResponse()->getBody();
        $this->assertSelectCount("urlset", 1, $xml);

        // create sitemap menu and verify generated structure
        $sitemap = P4Cms_Menu::create(
            array(
                'id'    => 'sitemap',
                'label' => 'site map'
            )
        );

        // add few links
        $sitemap->addPage(
            array(
                'id'    => 'link1',
                'label' => 'link 1',
                'type'  => 'Zend_Navigation_Page_Uri',
                'uri'   => '/link/1'
            )
        );
        $sitemap->addPage(
            array(
                'id'    => 'link2',
                'label' => 'link 2',
                'type'  => 'Zend_Navigation_Page_Uri',
                'uri'   => '/link/2'
            )
        );

        // add heading
        $sitemap->addPage(
            array(
                'id'    => 'heading1',
                'label' => 'Head 123',
                'type'  => 'P4Cms_Navigation_Page_Heading'
            )
        );

        // add content link
        P4Cms_Content::store(
            array(
                'contentType'   => 'basic-page',
                'title'         => 'Info',
                'id'            => 'inf'
            )
        );
        $sitemap->addPage(
            array(
                'id'        => 'content',
                'type'      => 'P4Cms_Navigation_Page_Content',
                'contentId' => 'inf'
            )
        );
        
        // save sitemap menu and dispatch to test
        $sitemap->save();

        // html
        $this->resetRequest()->resetResponse();
        $this->dispatch('/menu/sitemap');

        $this->assertQueryCount("ul.sitemap li", 4);
        $this->assertQueryContentContains("ul.sitemap li a[href='/link/1']", "link 1");
        $this->assertQueryContentContains("ul.sitemap li a[href='/link/2']", "link 2");
        $this->assertQueryContentContains("ul.sitemap li a[href='/view/id/inf']", "Info");
        $this->assertQueryContentContains("ul.sitemap li span", "Head 123");

        // xml
        $this->resetRequest()->resetResponse();
        $this->dispatch('/menu/sitemap/format/xml');

        // in xml output, heading is not rendered as its not a link
        $xml = $this->getResponse()->getBody();
        $this->assertSelectCount("urlset url loc", 3, $xml);
        $expectedUrls = array(
            'regexp:/link\/1\s*$/',
            'regexp:/link\/2\s*$/',
            'regexp:/view\/id\/inf\s*$/'
        );
        foreach ($expectedUrls as $url) {
            $this->assertTag(
                array(
                    'tag'       => 'loc',
                    'content'   => $url,
                ),
                $xml,
                '',
                false
            );
        }

        // ensure that /sitemap.xml is routed via /menu/sitemap
        $this->resetRequest()->resetResponse();
        $this->dispatch('/sitemap.xml');

        $this->assertModule('menu', "Expected module for /sitemap.xml request.");
        $this->assertController('index', "Expected controller for /sitemap.xml request.");
        $this->assertAction('sitemap', "Expected action for /sitemap.xml request.");

        $this->assertXmlStringEqualsXmlString($xml, $this->getResponse()->getBody());
    }
}
