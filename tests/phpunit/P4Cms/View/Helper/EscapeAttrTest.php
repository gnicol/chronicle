<?php
/**
 * Test methods for the P4Cms Editor View Helper.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_EscapeAttrTest extends TestCase
{
    /**
     * Test attributes escaping.
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
                'original'      => ",.-_", //safe chars
                'escaped'       => ",.-_"
            ),
            array(
                'original'      => "Text <tag> 123",
                'escaped'       => "Text&#x20;&#x3c;tag&#x3e;&#x20;123"
            ),
            array(
                'original'      => "_a,b",
                'escaped'       => "_a,b"
            ),
            array(
                'original'      => "a-b;x",
                'escaped'       => "a-b&#x3b;x"
            ),
            array(
                'original'      => "1..n +- %x",
                'escaped'       => "1..n&#x20;&#x2b;-&#x20;&#x25;x"
            ),
            array(
                'original'      => "abcd<XYZ> (1,2_3)",
                'escaped'       => "abcd&#x3c;XYZ&#x3e;&#x20;&#x28;1,2_3&#x29;"
            ),
            array(
                'original'      => '\\n',
                'escaped'       => "&#x5c;n"
            ),
            array(
                'original'      => "\n",
                'escaped'       => "&#xa;"
            ),
            array(
                'original'      => chr(13), // \r
                'escaped'       => "&#xd;"
            ),
            array(
                'original'      => chr(11),
                'escaped'       => " "
            ),
            array(
                'original'      => "\">ij",
                'escaped'       => "&#x22;&#x3e;ij"
            ),
            array(
                'original'      => '_' . chr(47) . '09' . chr(58) . '-' . chr(64) . 'AZ' . chr(91)
                    . '-' . chr(96) . 'az' . chr(123),
                'escaped'       => "_&#x2f;09&#x3a;-&#x40;AZ&#x5b;-&#x60;az&#x7b;"
            ),
        );

        $view   = new Zend_View;
        $view->setEncoding('UTF-8');
        $helper = new P4Cms_View_Helper_EscapeAttr;
        $helper->setView($view);

        foreach ($tests as $test) {
            $this->assertSame(
                $test['escaped'],
                $helper->escapeAttr($test['original']),
                "Unexpected escaping of '{$test['original']}'."
            );
        }
        
    }
    
}
