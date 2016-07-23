<?php
/**
 * Test methods for the entity decode filter.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_HtmlEntityDecodeTest extends TestCase
{
    /**
     * Test filter
     */
    public function testFilter()
    {
        $tests = array(
            array(
                'label'     => __LINE__,
                'value'     => "test &nbsp; string",
                'expected'  => "test \xc2\xa0 string"
            ),
            array(
                'label'     => __LINE__,
                'value'     => "&nbsp;",
                'expected'  => "\xc2\xa0"
            ),
            array(
                'label'     => __LINE__,
                'value'     => "&NBSP;",
                'expected'  => "\xc2\xa0"
            ),
            array(
                'label'     => __LINE__,
                'value'     => "&aring;",
                'expected'  => "\xc3\xa5"
            ),
            array(
                'label'     => __LINE__,
                'value'     => "&Aring;",
                'expected'  => "\xc3\x85"
            ),
            array(
                'label'     => __LINE__,
                'value'     => "&uuml;",
                'expected'  => "\xc3\xbc",
            ),
            array(
                'label'     => __LINE__,
                'value'     => "&#252;",
                'expected'  => "\xc3\xbc",
            ),
            array(
                'label'     => __LINE__,
                'value'     => '&#x0a0;',
                'expected'  => '&#x0a0;'
            ),
            array(
                'label'     => __LINE__,
                'value'     => '&#0a0a;',
                'expected'  => '&#0a0a;'
            ),
            array(
                'label'     => __LINE__,
                'value'     => '&#xFFFFFF00;',
                'expected'  => '&#xFFFFFF00;'
            ),
            array(
                'label'     => __LINE__,
                'value'     => "&#x31;",
                'expected'  => "1",
            ),
            array(
                'label'     => __LINE__,
                'value'     => "&#x31",
                'expected'  => "&#x31",
            ),
            array(
                'label'     => __LINE__,
                'value'     => "&#xff00000031;",
                'expected'  => "&#xff00000031;",
            )
        );

        $filter = new P4Cms_Filter_HtmlEntityDecode;
        foreach ($tests as $test) {
            $filtered = $filter->filter($test['value']);
            $this->assertSame(
                $test['expected'],
                $filtered,
                $test['label'] . ' - ' . $test['value']
            );
        }
    }

    /**
     * Test instantiation options.
     */
    public function testInstantiation()
    {
        $filter = new P4Cms_Filter_HtmlEntityDecode;
        $this->assertSame('UTF-8', $filter->getCharset());

        $filter = new P4Cms_Filter_HtmlEntityDecode('ISO-8859-1');
        $this->assertSame('ISO-8859-1', $filter->getCharset());

        $filter = new P4Cms_Filter_HtmlEntityDecode(array('charset' => 'ISO-8859-1'));
        $this->assertSame('ISO-8859-1', $filter->getCharset());

        $filter = new P4Cms_Filter_HtmlEntityDecode(
            new Zend_Config(array('charset' => 'ISO-8859-1'))
        );
        $this->assertSame('ISO-8859-1', $filter->getCharset());

        $filter = new P4Cms_Filter_HtmlEntityDecode(12345);
        $this->assertSame('UTF-8', $filter->getCharset());
    }

    /**
     * Test setting of charset.
     */
    public function testSetCharset()
    {
        $filter = new P4Cms_Filter_HtmlEntityDecode;
        $filter->setCharset('ISO-8859-1');
        $this->assertSame('ISO-8859-1', $filter->getCharset());

        // test bad inputs.
        try {
            $filter->setCharset(array());
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
        try {
            $filter->setCharset(12345);
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
        try {
            $filter->setCharset(new Zend_Config(array()));
            $this->fail();
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
    }
}
