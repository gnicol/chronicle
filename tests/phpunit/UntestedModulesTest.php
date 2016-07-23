<?php
/**
 * Attempt to require_once all files under P4 folder to verify they show in
 * code coverage reports.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class UntestedModulesTest extends TestCase
{
    /**
     * Requires all files to ensure they show up in coverage
     */
    public function testRequireAllModuleFiles()
    {
        P4Cms_Module::setCoreModulesPath(APPLICATION_PATH);
        P4Cms_Module::addPackagesPath(MODULE_PATH);

        $moduleFiles = array();
        // load all modules first to avoid errors due to inter-dependencies
        foreach (P4Cms_Module::fetchAll() as $module) {
            if (strtolower($module->getName()) === 'dojo') {
                continue;
            }
            P4Cms_Loader::addPackagePath($module->getName(), $module->getPath());
            $moduleFiles[] = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($module->getPath())
            );
        }

        foreach ($moduleFiles as $files) {
            foreach ($files as $fileName => $file) {
                if (!$files->isFile()
                    || pathinfo($fileName, PATHINFO_EXTENSION) !== 'php'
                    || preg_match('@/modules/ide/templates/@', $fileName)
                ) {
                    continue;
                }
                include_once($fileName);
            }
        }
    }
}
