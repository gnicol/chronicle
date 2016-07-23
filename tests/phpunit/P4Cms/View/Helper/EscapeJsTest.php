<?php
/**
 * Test methods for the P4Cms Editor View Helper.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_EscapeJsTest extends TestCase
{
    /**
     * Test javascript escaping.
     */
    public function testEscape()
    {
        $tests = array(
            array(
                'original'      => "abc",
                'escaped'       => "abc"
            ),
            array(
                'original'      => "0123456789",
                'escaped'       => "0123456789"
            ),
            array(
                'original'      => ",._", //safe chars
                'escaped'       => ",._"
            ),
            array(
                'original'      => "Text <tag> 123",
                'escaped'       => "Text\\x20\\x3Ctag\\x3E\\x20123"
            ),
            array(
                'original'      => "_a,b",
                'escaped'       => "_a,b"
            ),
            array(
                'original'      => "a-b;x",
                'escaped'       => "a\\x2Db\\x3Bx"
            ),
            array(
                'original'      => "1..n +- %x",
                'escaped'       => "1..n\\x20\\x2B\\x2D\\x20\\x25x"
            ),
            array(
                'original'      => "abcd<XYZ> (1,2_3)",
                'escaped'       => "abcd\\x3CXYZ\\x3E\\x20\\x281,2_3\\x29"
            ),
            array(
                'original'      => '\\n',
                'escaped'       => "\\x5Cn"
            ),
            array(
                'original'      => "\n",
                'escaped'       => "\\x0A"
            ),
            array(
                'original'      => chr(13), // \r
                'escaped'       => "\\x0D"
            ),
            array(
                'original'      => chr(11),
                'escaped'       => "\\x0B"
            ),
            array(
                'original'      => "\">ij",
                'escaped'       => "\\x22\\x3Eij"
            ),
            array(
                'original'      => '_' . chr(47) . '09' . chr(58) . '-' . chr(64) . 'AZ' . chr(91)
                    . '-' . chr(96) . 'az' . chr(123),
                'escaped'       => "_\\x2F09\\x3A\\x2D\\x40AZ\\x5B\\x2D\\x60az\\x7B"
            ),
            array(
                'original'      => 'ě', // ord 283
                'escaped'       => "\\u011B"
            )
        );

        $view   = new Zend_View;
        $view->setEncoding('UTF-8');
        $helper = new P4Cms_View_Helper_EscapeJs;
        $helper->setView($view);

        foreach ($tests as $test) {
            $this->assertSame(
                $test['escaped'],
                $helper->escapeJs($test['original']),
                "Unexpected escaping of '{$test['original']}'."
            );
        }

    }

}
