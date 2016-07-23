<?php
/**
 * Test methods for the title to id filter.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_TitleToIdTest extends TestCase
{
    /**
     * Test filter
     */
    public function testFilter()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'value'     => null,
                'expected'  => '',
            ),
            array(
                'label'     => __LINE__ .': empty',
                'value'     => '',
                'expected'  => '',
            ),
            array(
                'label'     => __LINE__ .': numeric',
                'value'     => 1,
                'expected'  => '1',
            ),
            array(
                'label'     => __LINE__ .': array',
                'value'     => array('one', 'two', 'three'),
                'expected'  => 'array',
            ),
            array(
                'label'     => __LINE__ .': boolean true',
                'value'     => true,
                'expected'  => '1',
            ),
            array(
                'label'     => __LINE__ .': boolean false',
                'value'     => false,
                'expected'  => '',
            ),
            array(
                'label'     => __LINE__ .': a common title',
                'value'     => 'A Common Title',
                'expected'  => 'a-common-title',
            ),
            array(
                'label'     => __LINE__ .': string mash',
                'value'     => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 '
                               . '.!@#$%^&*()-_=+[{]};:,<.>/?"\\`~'. "'",
                'expected'  => 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz0123456789',
            ),
            array(
                'label'     => __LINE__ .': string mash',
                'value'     => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 '
                               . '.!@#$%^&*()-_=+[{]};:,<.>/?"\\`~'. "'b",
                'expected'  => 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz0123456789'
                               . '---------------------------------b',
            ),
        );

        $filter = new P4Cms_Filter_TitleToId;
        foreach ($tests as $test) {
            $label = $test['label'];

            $filtered = $filter->filter($test['value']);
            $this->assertSame($test['expected'], $filtered, "$label - Expected value");
        }
    }
}
