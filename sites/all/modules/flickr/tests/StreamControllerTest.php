<?php
/**
 * Test the Flickr stream controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Flickr_Test_StreamControllerTest extends ModuleControllerTest
{
    /**
     *  Message passed to markTestSkipped() in tests that are skipped
     *  due to undefined parameters needed for full module functionality.
     */
    const       TEST_SKIP_MESSAGE = "
        The variable TEST_FLICKR_KEY is not defined.  Any tests against a Flickr
        widget will therefore be skipped.";

    /**
     *
     * @var     P4Cms_Module        Reference to the flickr module object.
     */
    private     $_flickrModule;

    /**
     * Clear caches prior to start of each test.
     */
    public function setUp()
    {
        parent::setUp();

        // enable youtube module
        $this->_flickrModule = P4Cms_Module::fetch('Flickr');
        $this->_flickrModule->enable();
        $this->_flickrModule->load();

        // add in available test widgets
        P4Cms_Widget::installDefaults();

        $this->utility->impersonate('editor');
    }

    /**
     * Utility function to add the widget to the test region.
     */
    public function addWidget()
    {
        $widget = P4Cms_Widget::factory('flickr/stream');
        $widget->setValue('region', 'test')->save();

        return $widget;
    }

    /**
     * Test the results of the index action when no api key is configured.
     */
    public function testNoApiKey()
    {
        $widget = $this->addWidget();

        $size           = Flickr_Form_StreamWidget::DIMENSION_THUMBNAIL;
        $imageInterval  =  5;
        $searchKey      = 'perforce';
        $values         = array('config'=>
            array(
                'sourceType'        => Flickr_Form_StreamWidget::SOURCE_TAG,
                'sourceTag'         => $searchKey,
                'imageSize'         => $size,
                'showImageTitle'    => false,
                'imageDelay'        => $imageInterval
            )
        );

        $widget->setValues($values)->save();

        $this->dispatch('/flickr/stream/index/region/test/widget/' . $widget->getId());
        $responseBody = $this->response->getBody();

        $this->assertQueryContentContains(
            'div#content div.container',
            'The Flickr module is not configured.',
            'Did not receive the expected error message. ' . $responseBody
        );
    }

    /**
     * Test the results of the index action when the widget is not configured.
     */
    public function testNoWidgetConfiguration()
    {
        $this->_flickrModule->saveConfig(
            new Zend_Config(
                array(
                    'key'       => 'invalid key'
                )
            )
        );

        $widget = $this->addWidget();

        $this->dispatch('/flickr/stream/index/region/test/widget/' . $widget->getId());
        $responseBody = $this->response->getBody();

        $this->assertQueryContentContains(
            'div#content div.container',
            'No image slideshow data to fetch.',
            'Did not receive the expected error message.' . $responseBody
        );
    }

    /**
     * Test display configuration.  Note that we don't actually talk to Flickr, we just
     * verify that we've set up the dojo slideshow object.
     */
    public function testFlickrWidgetConfiguration()
    {
        $this->_flickrModule->saveConfig(
            new Zend_Config(
                array(
                    'key'       => 'invalid key'
                )
            )
        );

        $widget = $this->addWidget();

        $size           = Flickr_Form_StreamWidget::DIMENSION_THUMBNAIL;
        $imageInterval  =  5;
        $searchKey      = 'perforce';
        $values         = array('config'=>
            array(
                'sourceType'        => Flickr_Form_StreamWidget::SOURCE_TAG,
                'sourceTag'         => $searchKey,
                'imageSize'         => $size,
                'showImageTitle'    => false,
                'imageDelay'        => $imageInterval
            )
        );

        $widget->setValues($values)->save();

        $this->dispatch('/flickr/stream/index/region/test/widget/' . $widget->getId());
        $responseBody = $this->response->getBody();

        $this->assertRegExp(
            '/.*?(dojoType).*?(p4cms\\.flickr\\.SlideShow)/',
            $responseBody,
            'Slideshow div was not created using tag "' . $searchKey . '".' . $responseBody
        );

        $dimension = Flickr_Form_StreamWidget::$sizeDimensions[$size];
        $this->assertQuery(
            'div[imageWidth="100"][imageHeight="100"]',
            'Expected image width and height to be ' . $dimension .' in response.' . $responseBody
        );

        $this->assertQuery(
            'div[slideshowInterval="' . $imageInterval . '"]',
            'Expected delay between images to be set to ' . $imageInterval . ' seconds.' . $responseBody
        );

        $searchKey  = 'p4cms';
        $values     = array('config'=>
            array(
                'sourceType'        => Flickr_Form_StreamWidget::SOURCE_USER,
                'sourceUser'        => $searchKey,
                'imageSize'         => $size,
                'showImageTitle'    => true,
                'imageDelay'        => $imageInterval
            )
        );

        $widget->setValues($values)->save();

        $this->dispatch('/flickr/stream/index/region/test/widget/' . $widget->getId());
        $responseBody = $this->response->getBody();

        $this->assertRegExp(
            '/.*?(dojoType).*?(p4cms\\.flickr\\.SlideShow)/',
            $responseBody,
            'Slideshow div was not created using user "' . $searchKey . '".' . $responseBody
        );

        $searchKey  = '52241285452@N01';   // victoria, bc group
        $values     = array('config'=>
            array(
                'sourceType'        => Flickr_Form_StreamWidget::SOURCE_GROUP,
                'sourceGroup'       => $searchKey,
                'imageSize'         => $size,
                'showImageTitle'    => true,
                'imageDelay'        => $imageInterval
            )
        );

        $widget->setValues($values)->save();

        $this->dispatch('/flickr/stream/index/region/test/widget/' . $widget->getId());
        $responseBody = $this->response->getBody();

        $this->assertRegExp(
            '/.*?(dojoType).*?(p4cms\\.flickr\\.SlideShow)/',
            $responseBody,
            'Slideshow div was not created using group id "' . $searchKey . '".' . $responseBody
        );
    }
}