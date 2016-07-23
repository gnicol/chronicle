<?php
/**
 * Parent class for all P4Cms Zend Controller TestCases.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class ModuleControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    public      $utility;
    public      $p4;
    protected   $_addCsrfToken   = true;

    /**
     * Setup our test environment, including a functioning perforce server.
     *
     * @param   string|null              $environment   optional - The application environment to use
     * @param   string|array|Zend_Config $options       String path to bootstrap configuration file,
     *                                                  or array/Zend_Config of configuration options
     * @todo review cache strategy for widget types,
     */
    public function setUp($environment = null, $options = null)
    {
        $this->utility = new TestUtility(get_class($this), $this->getName());
        $this->utility->setUp($this);
        $this->utility->setUpModuleTest($this, $environment, $options);

        parent::setUp();
    }

    /**
     * Clean up after ourselves.
     */
    public function tearDown()
    {
        if ($this->utility) {
            $this->utility->tearDownModuleTest($this);
            $this->utility->tearDown($this);
            unset($this->utility);
        }

        // get rid of any left over output buffering (phpunit expects one level)
        while (ob_get_level() > 1) {
            ob_end_clean();
        }

        parent::tearDown();
    }

    /**
     * Dispatches the MVC, adding a csrf token if required.
     *
     * @param  string|null $url  The url to dispatch to.
     * @return void
     */
    public function dispatch($url = null)
    {
        if ($this->_addCsrfToken) {
            $this->request->setParam(
                P4Cms_Form::CSRF_TOKEN_NAME,
                P4Cms_Form::getCsrfToken()
            );
        }
        parent::dispatch($url);
    }
}
