<?php
/**
 * Test methods for the P4Cms FileSize View Helper.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_FileSizeTest extends TestCase
{
    /**
     * Test attributes escaping.
     */
    public function testFileSize()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': no size',
                'size'      => null,
                'expected'  => '0 B'
            ),
            array(
                'label'     => __LINE__ .': 0 bytes',
                'size'      => 0,
                'expected'  => '0 B'
            ),
            array(
                'label'     => __LINE__ .': 10 bytes',
                'size'      => 10,
                'expected'  => '10 B'
            ),
            array(
                'label'     => __LINE__ .': 1023 bytes',
                'size'      => 1023,
                'expected'  => '1023 B'
            ),
            array(
                'label'     => __LINE__ .': 1024 bytes',
                'size'      => 1024,
                'expected'  => '1024 B'
            ),
            array(
                'label'     => __LINE__ .': 1025 bytes',
                'size'      => 1025,
                'expected'  => '1.00 KB'
            ),
            array(
                'label'     => __LINE__ .': 32768 bytes',
                'size'      => 32768,
                'expected'  => '32.00 KB'
            ),
            array(
                'label'     => __LINE__ .': 1024 kbytes',
                'size'      => 1024 * 1024,
                'expected'  => '1024.00 KB'
            ),
            array(
                'label'     => __LINE__ .': 1024 kbytes + 1 byte',
                'size'      => 1024 * 1024 + 1,
                'expected'  => '1.00 MB'
            ),
        );

        $view   = new Zend_View;
        $view->setEncoding('UTF-8');
        $helper = new P4Cms_View_Helper_FileSize;
        $helper->setView($view);

        foreach ($tests as $test) {
            $label = $test['label'];
            $this->assertSame($test['expected'], $helper->fileSize($test['size']), "$label - Expected size escaping");
        }
    }
}
