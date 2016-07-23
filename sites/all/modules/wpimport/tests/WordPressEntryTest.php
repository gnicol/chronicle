<?php
/**
 * Test the WordPress feedreader entry extension.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Wpimport_Test_WordPressEntryTest extends ModuleControllerTest
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
            'title' => 'Hello world!',
            'link' => 'http://wordpress.test.perforce.ca/?p=1',
            'pubDate' => 'Tue, 03 Jul 2012 21:25:59 +0000',
            'postDateGmt' => '2012-7-3',
            'postMeta'  => array(),
            'categories'=> array('uncategorized')
        );

        foreach ($expected as $field => $value) {
            $result = $this->_feed->current()->get($field);
            $this->assertEquals(
                $result,
                $value,
                'Field "' . $field . '" did not have expected value when parsing test xml file.'
            );
        }
    }
}
