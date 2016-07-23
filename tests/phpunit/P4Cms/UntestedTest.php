<?php
/**
 * Attempt to require_once all files under P4CMS folder to verify they show in
 * code coverage reports.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_UntestedTest extends TestCase
{
    /**
     * Requires all files to ensure they show up in coverage
     */
    public function testRequireAllFiles()
    {
        $files = new RecursiveDirectoryIterator(LIBRARY_PATH . '/P4Cms');
        foreach ($files as $fileName => $file) {
            if (!$files->isFile() || pathinfo($fileName, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }

            include_once($fileName);
        }
    }
}
