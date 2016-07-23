<?php
/**
 * Test the list-widget index controller.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Test_ListWidgetControllerTest extends ModuleControllerTest
{
    /**
     * Perform setup
     */
    public function setUp()
    {
        parent::setUp();

        // install default content types
        P4Cms_Content_Type::installDefaultTypes();
    }

    /**
     * Test the functionality of generating an RSS feed from list of content entries
     * provided by the content list widget.
     */
    public function testRssAction()
    {
        $this->utility->impersonate('editor');

        // create several content entries to test with
        $fooEntry = P4Cms_Content::create(
            array(
                'contentType'   => 'basic-page',
                'title'         => 'foo',
                'contentOwner'  => 'mweiss',
                'body'          => 'foo test entry'
            )
        )->save();

        $barEntry = P4Cms_Content::create(
            array(
                'contentType'   => 'basic-page',
                'title'         => 'bar',
                'contentOwner'  => 'tester'
            )
        )->save();

        // set up a content-list widget
        $widget = P4Cms_Widget::create(
            array(
                'id'        => 'test',
                'type'      => 'content/list',
                'title'     => 'Content Test',
                'config'    => array(
                    'contentType'   => array(
                        'Pages/basic-page'
                    )
                )
            )
        )->save();

        // test generated rss feed output in 2 loops with slightly modified input data
        for ($loop = 1; $loop <= 2; $loop++) {
            // prepare expected values for specific loop
            if ($loop == 2) {
                // ensure feed title and description will be taken from widget config
                // if these values are provided
                $config = $widget->getConfigAsArray();
                $config['feedTitle']       = 'RSS test';
                $config['feedDescription'] = 'RSS feed description';
                $widget->setConfig($config)->save();

                // change content owner to a non-existent user and ensure that it
                // won't break the feed generation
                $fooEntry->setOwner('none')->save();

                $expectedTitle       = 'RSS test';
                $expectedDescription = 'RSS feed description';
                $expectedFooAuthor   = 'none';
            } else {
                $expectedTitle       = 'Content Test';
                $expectedDescription = 'Content Test';
                $expectedFooAuthor   = 'Michael T. Weiss';
            }

            // dispatch to rss action and check the xml output
            $this->resetRequest()->resetResponse();
            $this->dispatch('/content/rss/test');

            // ensure that the request went through the expected route
            $this->assertRoute('rss',               'Expected route.');
            $this->assertModule('content',          'Expected module.');
            $this->assertController('list-widget',  'Expected controller.');
            $this->assertAction('rss',              'Expected action.');

            // check the feed markup
            $this->assertXpathContentContains('/rss/channel/title',         $expectedTitle);
            $this->assertXpathContentContains('/rss/channel/description',   $expectedDescription);
            $this->assertXpathCount('/rss/channel/item', 2);

            // check the 'foo' item markup
            $fooItem = "/rss/channel/item[title='foo']";
            $this->assertXpathContentContains($fooItem . '/description', 'foo test entry');
            $this->assertXpath($fooItem . '/pubDate');
            $this->assertXpath($fooItem . '/link');
            $this->assertXpathContentContains($fooItem . '/author', $expectedFooAuthor);

            // check the 'bar' item markup
            $barItem = "/rss/channel/item[title='bar']";
            $this->assertNotXpath($barItem . '/description');
            $this->assertXpath($barItem . '/pubDate');
            $this->assertXpath($barItem . '/link');
            $this->assertXpathContentContains($barItem . '/author', 'Test User');
        }
    }
}