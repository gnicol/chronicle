<?php
/**
 * Test the menu model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Navigation_Test extends TestCase
{
    /**
     * Test that toArray returns items in the appropriate order
     */
    public function testToArray()
    {
        $container = new P4Cms_Navigation;
        $pages = array(
            array(
                'label' => 'natural 1',
                'uri'   => 'http://natural 1/',
            ),
            array(
                'label' => 'force last 1',
                'uri'   => 'http://force last 1/',
                'order' => 100,
            ),
            array(
                'label' => 'natural 2',
                'uri'   => 'http://natural 2/',
            ),
            array(
                'label' => 'natural 3',
                'uri'   => 'http://natural 3/',
            ),
            array(
                'label' => 'force first 2',
                'uri'   => 'http://force first 2/',
                'order' => -1,
            ),
            array(
                'label' => 'force first 1',
                'uri'   => 'http://force first 1/',
                'order' => -1,
            ),
        );

        $container->addPages($pages);
        $actualPages = $container->toArray();
        $actualLabels = array();
        foreach ($actualPages as $page) {
            $actualLabels[] = $page['label'];
        }
        $expectedLabels = array(
            'force first 2',
            'force first 1',
            'natural 1',
            'natural 2',
            'natural 3',
            'force last 1'
        );
        $this->assertEquals(
            $expectedLabels,
            $actualLabels,
            'labels should reflect desired order'
        );
    }

    /**
     * Test that getPages returns items in the appropriate order
     */
    public function testGetPages()
    {
        $container = new P4Cms_Navigation;
        $pages = array(
            array(
                'label' => 'natural 1',
                'uri'   => 'http://natural 1/',
            ),
            array(
                'label' => 'force last 1',
                'uri'   => 'http://force last 1/',
                'order' => 100,
            ),
            array(
                'label' => 'natural 2',
                'uri'   => 'http://natural 2/',
            ),
            array(
                'label' => 'natural 3',
                'uri'   => 'http://natural 3/',
            ),
            array(
                'label' => 'force first 2',
                'uri'   => 'http://force first 2/',
                'order' => -1,
            ),
            array(
                'label' => 'force first 1',
                'uri'   => 'http://force first 1/',
                'order' => -1,
            ),
        );

        $container->addPages($pages);
        $actualPages = $container->getPages();
        $actualLabels = array();
        foreach ($actualPages as $page) {
            $actualLabels[] = $page->label;
        }
        $expectedLabels = array(
            'force first 2',
            'force first 1',
            'natural 1',
            'natural 2',
            'natural 3',
            'force last 1'
        );
        $this->assertEquals(
            $expectedLabels,
            $actualLabels,
            'labels should reflect desired order'
        );
    }

}
