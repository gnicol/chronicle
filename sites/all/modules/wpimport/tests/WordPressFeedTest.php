<?php
/**
 * Test the WordPress feedreader feed extension.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Wpimport_Test_WordPressFeedTest extends ModuleControllerTest
{
    protected $_xmlFile = 'test.xml';
    protected $_feed    = null;

    /**
     * Perform setup
     */
    public function setUp()
    {
        parent::setUp();
        $path = dirname(__FILE__) . '/';

        Zend_Feed_Reader::addPrefixPath(
            'Wp_FeedReader_Extension', $path . '../'
        );
        Zend_Feed_Reader::registerExtension('WordPress');

        $this->_feed = Zend_Feed_Reader::importFile($path . $this->_xmlFile);
    }

    /**
     * Verify the feed was imported correctly.
     */
    public function testGet()
    {
        $expected = array(
            'title'            => 'WP Test',
            'link'             => 'http://wordpress.test.perforce.ca',
            'pubDate'          => 'Wed, 08 Aug 2012 17:41:22 +0000',
            'wp:wxr_version'   => '1.2',
            'wp:base_site_url' => 'http://wordpress.test.perforce.ca',
            'categories'       => array(
                array('id' => 'uncategorized', 'title' => 'Uncategorized'),
                array('id' => 'bar', 'title' => 'bar'),
                array('id' => 'baz', 'title' => 'baz'),
                array('id' => 'foo', 'title' => 'foo')
            )
        );

        foreach ($expected as $field => $value) {
            $result = $this->_feed->get($field);
            $this->assertEquals(
                $result,
                $value,
                'Field "' . $field . '" did not have expected value when parsing test xml file.'
            );
        }

        $expectedAuthors = array(
            array(
                'id'       => 'ed',
                'email'    => 'ed@test.perforce.ca',
                'fullName' => 'Ed Sa'
            ),
            array(
                'id'       => 'aaron',
                'email'    => 'aaron@test.perforce.ca',
                'fullName' => 'Aaron Fed'
            ),
            array(
                'id'       => 'maya',
                'email'    => 'maya@test.perforce.ca',
                'fullName' => 'Maya Ce'
            ),
            array(
                'id'       => 'patricia',
                'email'    => 'patricia@test.perforce.ca',
                'fullName' => 'Patricia Cm'
            )
        );

        $this->assertEquals($expectedAuthors, $this->_feed->getWpAuthors(), 'Parsed authors did not match expected.');
    }
}
