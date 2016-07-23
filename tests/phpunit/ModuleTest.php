<?php
/**
 * Parent class for all core module (application) TestCases.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class ModuleTest extends TestCase
{
    /**
     * Extend the base test case to create test sites
     * and bootstrap the application.
     *
     * @param   string|null              $environment   optional - The application environment to use
     * @param   string|array|Zend_Config $options       String path to bootstrap configuration file,
     *                                                  or array/Zend_Config of configuration options
     */
    public function setUp($environment = null, $options = null)
    {
        parent::setUp();
        $this->utility->setUpModuleTest($this, $environment, $options);
    }

    /**
     * Clean up after ourselves.
     */
    public function tearDown()
    {
        if ($this->utility) {
            $this->utility->tearDownModuleTest($this);
        }

        parent::tearDown();
    }
}
