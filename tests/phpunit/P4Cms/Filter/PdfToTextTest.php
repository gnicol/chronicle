<?php
/**
 * Test methods for the pdf to text filter.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_PdfToTextTest extends TestCase
{
    /**
     *  Message passed to markTestSkipped() in tests that are skipped
     *  due to undefined parameters needed for full functionality.
     */
    const TEST_SKIP_MESSAGE = "
        No PDF text extractor found.  Any tests against a PdfToText filter
        method will therefore be skipped.  The PdfToText filter depends on
        XPDF's pdftotext utility.  Make sure the XPDF package is installed
        on the machine.";

    /**
     * Test PDF to text conversion.
     */
    public function testFilter()
    {
        $filter = new P4Cms_Filter_PdfToText;

        // mark test as skipped if the pdftotext executable isn't found
        if (!$filter->checkExecutable()) {
            $this->markTestSkipped(self::TEST_SKIP_MESSAGE);
        }

        $testFiles = array(
            array(
                'pdf'   => 'test1.pdf',
                'text'  => 'test1.txt',
                'label' => 'Test 1'
            ),
            array(
                'pdf'   => 'test2.pdf',
                'text'  => 'test2.txt',
                'label' => 'Test 2'
            ),
            array(
                'pdf'   => 'test3.pdf',
                'text'  => 'test3.txt',
                'label' => 'Test 3'
            ),
        );

        $path = TEST_ASSETS_PATH . '/files/';
        foreach ($testFiles as $testFile) {
            $pdf    = Zend_Pdf::load($path . $testFile['pdf']);

            // remove new lines, vertical tabs, nulls since we don't care
            $contents = preg_replace('/[\r\n\0\x0B]/', '', $filter->filter($pdf));

            $expected = preg_replace(
                '/[\r\n\0\x0B]/',
                '', 
                file_get_contents($path . $testFile['text'])
            );

            // remove redundant spaces
            $contents = preg_replace('/ +/', ' ', $contents);
            $expected = preg_replace('/ +/', ' ', $contents);

            $this->assertSame($expected, $contents, $testFile['label'] . ': Expected');

            unset($pdf);
        }
    }

    /**
     * Test setting and getting executable
     */
    public function testSetGoodExecutable()
    {
        $filter = new P4Cms_Filter_PdfToText();

        // test using a different executable that should exist on all systems
        $myExecutable = 'echo';
        $filter->setExecutable($myExecutable);
        $this->assertSame($filter->getExecutable(), $myExecutable);
    }

    /**
     * Test setting pdftotext executable.
     */
    public function testSetBadExecutable()
    {
        $filter = new P4Cms_Filter_PdfToText();

        try {
            $filter->setExecutable('noSuch-pdftotext');
        } catch (Zend_Filter_Exception $e) {
            // it's expected
            return;
        }
        $this->fail("An expected exception has not been thrown.");
    }

    /**
     * Test setting pdftotext executable.
     */
    public function testSetNonStringExecutable()
    {
        $filter = new P4Cms_Filter_PdfToText();

        try {
            $filter->setExecutable(array());
        } catch (Zend_Filter_Exception $e) {
            // it's expected
            return;
        }
        $this->fail("An expected exception has not been thrown.");
    }

    /**
     * Test setting pdftotext executable.
     */
    public function testSetEmptyStringExecutable()
    {
        $filter = new P4Cms_Filter_PdfToText();

        try {
            $filter->setExecutable("");
        } catch (Zend_Filter_Exception $e) {
            // it's expected
            return;
        }
        $this->fail("An expected exception has not been thrown.");
    }

    /**
     * Test setting pdftotext executable.
     */
    public function testSetSpaceStringExecutable()
    {
        $filter = new P4Cms_Filter_PdfToText();

        try {
            $filter->setExecutable(' ');
        } catch (Zend_Filter_Exception $e) {
            // it's expected
            return;
        }
        $this->fail("An expected exception has not been thrown.");
    }

    /**
     * Test getting pdftotext executable.
     */
    public function testGetExecutable()
    {
        $filter = new P4Cms_Filter_PdfToText();

        // get the default pdftotext executable.
        $this->assertSame($filter->getExecutable(), 'pdftotext');
    }
}
