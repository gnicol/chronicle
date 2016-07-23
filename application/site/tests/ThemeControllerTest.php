<?php
/**
 * Test the theme controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Site_Test_ThemeControllerTest extends ModuleControllerTest
{
    /**
     * Test theme list page.
     */
    public function testIndex()
    {
        $this->utility->impersonate('administrator');

        $this->dispatch('/site/theme');
        $this->assertModule('site', 'Expected module');
        $this->assertController('theme', 'Expected controller');
        $this->assertAction('index', 'Expected action');
        $this->assertQuery("div.current-theme");
        $this->assertQuery("div.current-theme div.maintainer");
        $this->assertQuery("div.theme-grid");
        $this->assertQuery("div.theme-grid table");
        $this->assertQuery("div.theme-grid thead");

        $view  = Zend_Layout::getMvcInstance()->getView();
        $theme = P4Cms_Theme::fetchActive();

        // ensure that view renderer has added the correct
        // script path for the current theme's view scripts.
        $this->assertContains(
            $theme->getPath() .'/views/site/',
            $view->getScriptPaths(),
            'Expect site path in script paths'
        );

        // ensure that view renderer has added the correct
        // script path for the current theme's layout scripts.
        $this->assertContains(
            $theme->getPath() . '/layouts/',
            $view->getScriptPaths(),
            'Expect layouts path in script paths'
        );
    }

    /**
     * Test theme apply action for rejection of get requests.
     */
    public function testGetApply()
    {
        $this->utility->impersonate('administrator');

        $this->request->setQuery(array('theme' => 'default'));
        $this->request->setMethod('GET');
        $this->dispatch('/site/theme/apply');
        $this->assertModule('error', 'Expected error module.');
    }

    /**
     * Test theme apply action for acceptance of valid post requests.
     */
    public function testPostApply()
    {
        $this->utility->impersonate('administrator');

        $this->request->setPost(array('theme' => 'alternative'));
        $this->request->setMethod('POST');
        $this->dispatch('/site/theme/apply');
        $this->assertModule('site', 'Expected module');
        $this->assertController('theme', 'Expected controller');
        $this->assertAction('apply', 'Expected action');

        $config = P4Cms_Site::fetchActive()->getConfig();
        $this->assertSame($config->getTheme(), 'alternative', 'Expected theme');
        $this->assertRedirect();
    }
}
