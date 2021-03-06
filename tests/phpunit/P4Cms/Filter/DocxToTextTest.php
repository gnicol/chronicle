<?php
/**
 * Test methods for the pdf to text filter.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_DocxToTextTest extends TestCase
{
    /**
     * Test PDF to text conversion.
     */
    public function testFilter()
    {
        if (!class_exists('ZipArchive', false)) {
            $this->markTestSkipped(
                'MS Office documents processing functionality requires' .
                ' Zip extension to be loaded'
            );
        }

        $filter = new P4Cms_Filter_DocxToText;

        $path = TEST_ASSETS_PATH . '/files/';
        $files = scandir($path);

        $testFiles = array();
        foreach ($files as $key => $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) == 'docx') {
                $testFiles[] = array(
                    'name'   => $file,
                    'text'   => $file . '.txt',
                    'label'  => 'Test ' . $key
                );
            }
        }

        foreach ($testFiles as $testFile) {
            // replace new lines, CR's, Null's, tabs, and vertical tabs
            // with spaces
            $expected = preg_replace(
                '/[\r\n\0\x0B\t]/',
                ' ',
                file_get_contents($path . $testFile['text'])
            );
            $contents = preg_replace(
                '/[\r\n\0\x0B\t]/',
                ' ',
                $filter->filter(file_get_contents($path . $testFile['name']))
            );

            // remove redundant spaces
            $expected = trim(preg_replace('/ +/', ' ', $expected));
            $contents = trim(preg_replace('/ +/', ' ', $contents));

            $this->assertSame(
                $expected,
                $contents,
                "Expected the text contents to be the same"
            );
        }
    }
}
