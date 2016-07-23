<?php
/**
 * Abstract class for testing image drivers.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
abstract class P4Cms_Image_Driver_TestAbstract extends TestCase
{
    // class of the image driver to test (defined by extending class)
    protected static    $_driverClass       = null;

    // driver instance to use by the tests in this class
    protected           $_driver            = null;

    // list of transformations to test
    protected           $_testedTransforms  = array(
        'scale',
        'sharpen'
    );

    /**
     * Create image driver and store reference in $_driver property, so tests can access it.
     */
    public function setUp()
    {
        // try to create an image driver instance from the driver class
        if (static::$_driverClass) {
            try{
                $this->_driver = P4Cms_Image_Driver_Factory::create(static::$_driverClass);
            } catch(P4Cms_Image_Exception $e) {
                // image driver failed to create
            }
        }

        // skip tests entirely if we have no driver
        if (!$this->_driver) {
            $this->markTestSkipped("No image driver set to test with.");
        }

        parent::setUp();
    }

    /**
     * Verify functionality of getData() and setData() methods.
     */
    public function testSetGetData()
    {
        // ensure getData() returns null if no or an empty image input image was set
        $this->assertSame(
            null,
            $this->_driver->getData(),
            "Expected getData() returns null if no image data were set."
        );

        // ensure we can set empty input data
        $this->_driver->setData(null);
        $this->assertSame(
            null,
            $this->_driver->getData(),
            "Expected getData() returns null if empty image data were set."
        );

        // following tests will use jpeg image; mark test incomplete if driver
        // doesn't support jpeg images
        if (!$this->_driver->isSupportedType('jpeg')) {
            $this->markTestIncomplete("Driver doesn't support jpeg.");
        }

        // set real image data and check that output of getData() represents image
        // with the correct mime type
        $data = file_get_contents(TEST_ASSETS_PATH . '/images/perforce-logo.jpg');
        $this->_driver->setData($data);
        $mime = $this->_getMimeType($this->_driver->getData());
        if (!$mime) {
            // cannot verify output data mime type
            $this->assertTrue(is_string($driver->getData()));
        } else {
            $this->assertSame(
                'image/jpeg',
                $mime,
                'Expected mime for output image.'
            );
        }
    }

    /**
     * Test getImageSize() method.
     */
    public function testGetImageSize()
    {
        // ensure that exception is thrown if called when no image data were previously set
        try {
            $this->_driver->getImageSize();
            $this->fail("Unexpected continuation after calling getImageSize() with no image data.");
        } catch (P4Cms_Image_Exception $e) {
            $this->assertTrue(true);
        }

        // following tests will use jpeg image; mark test incomplete if driver
        // doesn't support jpeg images
        if (!$this->_driver->isSupportedType('jpeg')) {
            $this->markTestIncomplete("Driver doesn't support jpeg.");
        }

        // set data representing image 200x46 pixels
        $data = file_get_contents(TEST_ASSETS_PATH . '/images/perforce-logo.jpg');
        $this->_driver->setData($data);

        // ensure size is array with defined 'width' and 'height' keys
        $size = $this->_driver->getImageSize();
        $this->assertTrue(
            is_array($size),
            "Expected getImageSize() return an array."
        );
        $this->assertTrue(
            array_key_exists('width', $size),
            "Expected 'width' key exists in the array returned by getImageSize()."
        );
        $this->assertTrue(
            array_key_exists('height', $size),
            "Expected 'height' key exists in the array returned by getImageSize()."
        );

        // ensure dimensions are correct
        $this->assertTrue(
            $size['width'] === 200 && $size['height'] === 46,
            "Expected dimension of returned image."
        );
    }

    /**
     * Test transform methods - ensure that each driver class defines methods for all supported
     * transforms according to translation in transform() method.
     */
    public function testTransformMethods()
    {
        // create driver reflection class
        $reflector = new ReflectionClass($this->_driver);

        // ensure that there exists '_<TRANSFORM>()' method, where <TRANSFORM> iterates
        // all transforms supported by the class
        $driverClass         = get_class($this->_driver);
        $supportedTransforms = $driverClass::getSupportedTransforms();
        foreach ($supportedTransforms as $transform) {
            // ensure that there exists '_<TRANSFORM>' method defined for each TRANSFORM
            $method = '_' . $transform;
            $this->assertTrue(
                $reflector->hasMethod($method),
                "Missing '$method' for '$transform' transform."
            );
        }
    }

    /**
     * Test image transformations defined in $_testedTransforms property.
     */
    public function testTransforms()
    {
        foreach ($this->_testedTransforms as $transform) {
            // reset driver's data
            $this->_driver->setData(null);
            
            $method = '_test' . ucwords($transform) . 'Transform';
            call_user_func(array('static', $method));
        }
    }

    /**
     * Test scale() transform.
     */
    protected function _testScaleTransform()
    {
        // ensure that exception is thrown if called when no image data were previously set
        try {
            $this->_driver->transform('scale', array(11, 17));
            $this->fail("Unexpected continuation after calling transform() with no image data.");
        } catch (P4Cms_Image_Exception $e) {
            $this->assertTrue(true);
        }

        // following tests will use jpeg image; mark test incomplete if driver
        // doesn't support jpeg images
        if (!$this->_driver->isSupportedType('jpeg')) {
            $this->markTestIncomplete("Driver doesn't support jpeg.");
        }

        // read 200x46 pixels image data
        $imageData = file_get_contents(TEST_ASSETS_PATH . '/images/perforce-logo.jpg');

        // test 'scale' transformation with various options:
        //  - set both width and height
        //  - set only target width (height should be computed such that image proportions
        //    remain the same)
        //  - set only target height (width should be computed such that image proportions
        //    remain the same)
        //  - set neither width nor height (exception should be thrown)
        $this->_driver->setData($imageData);
        $this->_driver->transform('scale', array(101, 79));

        $mime = $this->_getMimeType($this->_driver->getData());
        if (!$mime) {
            // cannot verify output data mime type
            $this->assertTrue(is_string($this->_driver->getData()));
        } else {
            $this->assertSame(
                'image/jpeg',
                $mime,
                'Expected mime for output image.'
            );
        }
        $size = $this->_driver->getImageSize();
        $this->assertSame(
            101,
            $size['width'],
            "Expected image width #1."
        );
        $this->assertSame(
            79,
            $size['height'],
            "Expected image height #1."
        );

        // test 'scale' transform with only target width set
        $this->_driver->setData($imageData);
        $this->_driver->transform('scale', array(100));

        $mime = $this->_getMimeType($this->_driver->getData());
        if (!$mime) {
            // cannot verify output data mime type
            $this->assertTrue(is_string($this->_driver->getData()));
        } else {
            $this->assertSame(
                'image/jpeg',
                $mime,
                'Expected mime for output image.'
            );
        }
        $size = $this->_driver->getImageSize();
        $this->assertSame(
            100,
            $size['width'],
            "Expected image width #2."
        );
        $this->assertSame(
            23,
            $size['height'],
            "Expected image height #2."
        );

        // test 'scale' transform with only target height set
        $this->_driver->setData($imageData);
        $this->_driver->transform('scale', array(null, 138));

        $mime = $this->_getMimeType($this->_driver->getData());
        if (!$mime) {
            // cannot verify output data mime type
            $this->assertTrue(is_string($this->_driver->getData()));
        } else {
            $this->assertSame(
                'image/jpeg',
                $mime,
                'Expected mime for output image.'
            );
        }
        $size = $this->_driver->getImageSize();
        $this->assertSame(
            600,
            $size['width'],
            "Expected image width #3."
        );
        $this->assertSame(
            138,
            $size['height'],
            "Expected image height #3."
        );

        // test 'scale' transform with no arguments set
        $this->_driver->setData($imageData);
        try {
            $this->_driver->transform('scale', array());
            $this->fail("Unexpected continuation after invalid transformation.");
        } catch (P4Cms_Image_Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * Test sharpen() transform.
     */
    protected function _testSharpenTransform()
    {
        // ensure that exception is thrown if called when no image data were previously set
        try {
            $this->_driver->transform('sharpen');
            $this->fail("Unexpected continuation after calling transform() with no image data.");
        } catch (P4Cms_Image_Exception $e) {
            $this->assertTrue(true);
        }

        // following tests will use jpeg image; mark test incomplete if driver
        // doesn't support jpeg images
        if (!$this->_driver->isSupportedType('jpeg')) {
            $this->markTestIncomplete("Driver doesn't support jpeg.");
        }

        // read 200x46 pixels image data
        $imageData = file_get_contents(TEST_ASSETS_PATH . '/images/perforce-logo.jpg');

        $this->_driver->setData($imageData);
        $this->_driver->transform('sharpen');

        //  we cannot really test if the resulting image is sharper, so we
        //  verify parameters and ensure that image dimensions remain unchanged
        $mime = $this->_getMimeType($this->_driver->getData());
        if (!$mime) {
            // cannot verify output data mime type
            $this->assertTrue(is_string($this->_driver->getData()));
        } else {
            $this->assertSame(
                'image/jpeg',
                $mime,
                'Expected mime for output image.'
            );
        }

        $size = $this->_driver->getImageSize();
        $this->assertSame(
            200,
            $size['width'],
            "Expected image width."
        );
        $this->assertSame(
            46,
            $size['height'],
            "Expected image height."
        );
    }

    /**
     * Helper function to detect mime type of $data by using fileinfo. This extension
     * is enabled by default (as of PHP 5.3.0). If its turned off from whatever reason,
     * return null.
     *
     * @param   string  $data   data to detect mime type on
     */
    protected function _getMimeType($data)
    {
        if (!class_exists('finfo')) {
            return null;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->buffer($data);
    }
}