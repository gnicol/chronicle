<?php
/**
 * Test methods for the Array Utility class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_ArrayUtilityTest extends TestCase
{
    /**
     * Test computeDiff
     */
    public function testComputeDiff()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': empty, empty - expect no changes',
                'old'       => array(),
                'new'       => array(),
                'additions' => array(),
                'removals'  => array(),
            ),
            array(
                'label'     => __LINE__ .': some, empty - expect removals',
                'old'       => array(1, 2, 3),
                'new'       => array(),
                'additions' => array(),
                'removals'  => array(1, 2, 3),
            ),
            array(
                'label'     => __LINE__ .': empty, some - expect additions',
                'old'       => array(),
                'new'       => array(1, 2, 3),
                'additions' => array(1, 2, 3),
                'removals'  => array(),
            ),
            array(
                'label'     => __LINE__ .': same, same - expect no changes',
                'old'       => array(1, 2, 3),
                'new'       => array(1, 2, 3),
                'additions' => array(),
                'removals'  => array(),
            ),
            array(
                'label'     => __LINE__ .': some, some - expect mixed',
                'old'       => array(1, 2, 3),
                'new'       => array(3, 4, 5),
                'additions' => array(4, 5),
                'removals'  => array(1, 2),
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];

            list ($additions, $removals) = P4Cms_ArrayUtility::computeDiff($test['old'], $test['new']);
            $this->assertSame($test['additions'], $additions, "$label - Expected additions");
            $this->assertSame($test['removals'], $removals, "$label - Expected removals");
        }
    }
}
