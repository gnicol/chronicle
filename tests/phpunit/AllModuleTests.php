<?php
/**
 * Container for all core and optional module tests.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class AllModuleTests
{
    /**
     * Build up a test suite containing all core and optional module tests.
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('Module Tests');

        P4Cms_Module::setCoreModulesPath(APPLICATION_PATH);
        P4Cms_Module::addPackagesPath(MODULE_PATH);

        // save working directory.
        $cwd = getcwd();

        foreach (P4Cms_Module::fetchAll() as $module) {
            // detect and load phpunit.xml file.
            $configFile = $module->getPath() . '/tests/phpunit.xml';
            if (file_exists($configFile)) {
                chdir(dirname($configFile));
                $config = PHPUnit_Util_Configuration::getInstance($configFile);
                $config->handlePHPConfiguration();
                $suite->addTest($config->getTestSuiteConfiguration());
                continue;
            }
            
            // fallback - detect and load AllTests.php file.
            $suiteFile = $module->getPath() . '/tests/AllTests.php';
            if (file_exists($suiteFile)) {
                P4Cms_Loader::addPackagePath($module->getName(), $module->getPath());
                $testClassName = $module->getName().'_Test_AllTests';
                $suite->addTest($testClassName::suite());
            }

        }

        // restore working directory.
        chdir($cwd);

        return $suite;
    }
}
