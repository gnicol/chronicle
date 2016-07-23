<?php
/**
 * Test the site index controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Site_Test_IndexControllerTest extends ModuleControllerTest
{
    /**
     * Test general site settings display
     */
    public function testIndexGet()
    {
        $this->utility->impersonate('administrator');
        $site = P4Cms_Site::fetchActive();

        // test that basic list renders correctly.
        $this->dispatch('/site/config');
        $body = $this->response->getBody();
        $this->assertModule(
            'site',
            'Last module should be site, got "'. $this->request->getModuleName() .'", body: '. $body
        );
        $this->assertController(
            'index',
            'Expected index controller, got "'. $this->request->getControllerName() .'", body: '. $body
        );
        $this->assertAction(
            'config',
            'Expected index action, got "'. $this->request->getActionName() .'", body: '. $body
        );
    }

    /**
     * Test providing good POST parameters
     */
    public function testIndexGoodPost()
    {
        $this->utility->impersonate('administrator');

        // test that basic list renders correctly.
        $host  = $this->_getRequestHttpHost();
        $title = 'my site';
        $settings = array(
            'title' => $title
        );
        $this->request->setPost($settings);
        $this->request->setMethod('POST');
        $this->dispatch('/site/config');
        $body = $this->response->getBody();
        $this->assertModule(
            'site',
            'Last module should be site, got "'. $this->request->getModuleName() .'", body: '. $body
        );
        $this->assertController(
            'index',
            'Expected index controller, got "'. $this->request->getControllerName() .'", body: '. $body
        );
        $this->assertAction(
            'config',
            'Expected index action, got "'. $this->request->getActionName() .'", body: '. $body
        );
        $this->assertRedirectTo('/site/config', __LINE__ .': Expect redirect to site/index/config.'. $body);

        $config = P4Cms_Site::fetchActive()->getConfig();
        $this->assertEquals($title, $config->getTitle(), 'Expected value for title.');
    }

    /**
     * Test providing an empty site title
     */
    public function testIndexNoSiteTitle()
    {
        $this->utility->impersonate('administrator');

        // test that basic list renders correctly.
        $settings = array(
            'title' => ''
        );
        $this->request->setPost($settings);
        $this->request->setMethod('POST');
        $this->dispatch('/site/config');
        $body = $this->response->getBody();
        $this->assertModule(
            'site',
            'Last module should be site, got "'. $this->request->getModuleName() .'", body: '. $body
        );
        $this->assertController(
            'index',
            'Expected index controller, got "'. $this->request->getControllerName() .'", body: '. $body
        );
        $this->assertAction(
            'config',
            'Expected index action, got "'. $this->request->getActionName() .'", body: '. $body
        );

        $this->assertQueryContentContains(
            "dd[id='title-element'] ul.errors li",
            "Value is required and can't be empty",
            'Expected an error for the title field in: '. $body
        );
    }

    /**
     * Test the robots action.
     */
    public function testRobots()
    {
        $this->dispatch('/site/robots');
        $body = $this->response->getBody();
        $this->assertEquals("User-agent: *\nDisallow:\n", $body, 'Expected default robots.txt');

        // change robots.txt
        $this->resetRequest()->resetResponse();
        $this->utility->impersonate('administrator');

        $settings = array(
            'title'     => 'my site',
            'robots'    => "User-agent: google\nDisallow: yahoo"
        );
        $this->request->setPost($settings);
        $this->request->setMethod('POST');
        $this->dispatch('/site/config');
        $this->assertModule('site', 'Expected module');
        $this->assertController('index', 'Expected controller');
        $this->assertAction('config', 'Expected action');

        // verify the change
        $this->resetRequest()->resetResponse();
        $this->dispatch('/site/robots');
        $body = $this->response->getBody();
        $this->assertEquals("User-agent: google\nDisallow: yahoo", $body, 'Expected modified robots.txt');
    }

    /**
     * A helper method to determine the request's hostname
     *
     * @return  string  The request's current hostname
     */
    protected function _getRequestHttpHost()
    {
        $host = $this->request->getHttpHost();
        if (preg_match('#:\d+$#', $host, $result) === 1) {
            $host = substr($host, 0, -strlen($result[0]));
        }

        return $host;
    }
}
