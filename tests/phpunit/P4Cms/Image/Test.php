<?php
/**
 * Test the P4Cms_Image class.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Image_Test extends TestCase
{
    /**
     * Test behaviour of setDriver, hasDriver and getDriver.
     */
    public function testDriver()
    {
        $img = new P4Cms_Image();
        $this->assertFalse(
            $img->hasDriver(),
            'Expected no driver to be set on new P4Cms_Image.'
        );

        if (extension_loaded('gd')) {
            $gd = new P4Cms_Image_Driver_Gd();
            $img->setDriver($gd);
            $this->assertTrue(
                $img->hasDriver(),
                'Expected gd driver to be set.'
            );
        } else {
            $this->markTestIncomplete("Cannot verify default driver, 'gd' extension not found.");
        }

        $driver = $img->getDriver();
        $this->assertSame(
            $gd,
            $driver,
            'Expected gd driver to be returned by getDriver.'
        );
    }

    /**
     * Test behaviour of setData and getData.
     */
    public function testData()
    {
        $img = new P4Cms_Image();
        $data = file_get_contents(TEST_ASSETS_PATH . '/images/luigi.png');
        $img->setData($data);

        if (extension_loaded('imagick')) {
            // no driver was specified so the driver should be an instance of
            // P4Cms_Image_Driver_Imagick
            $this->assertTrue(
                $img->getDriver() instanceof P4Cms_Image_Driver_Imagick,
                'Expected driver to be instance of P4Cms_Image_Driver_Imagick.'
            );
        } else {
            $this->markTestIncomplete("Cannot verify default driver, 'imagick' extension not found.");
        }

        $this->assertSame(
            $img->getImageSize(),
            array('width' => 516, 'height' => 710),
            'Expected file at: ..assets/images/luigi.png to be 516x710 pixels.'
        );

        $newImg = new P4Cms_Image();
        $newImg->setData($data);

        // the data returned from getData() should be the same as what was set
        $this->assertSame(
            $newImg->getImageSize(),
            array('width' => 516, 'height' => 710),
            'Expected image built from file: ..assets/images/luigi.png to be 516x710 pixels.'
        );
    }

    /**
     * Test behaviour of transform.
     */
    public function testTransform()
    {
        $img = new P4Cms_Image();
        $data = file_get_contents(TEST_ASSETS_PATH . '/images/luigi.png');
        $img->setData($data);

        // test the behavior of scale with width and height
        $img->transform('scale', array(258, 355));
        $this->assertSame(
            $img->getImageSize(),
            array('width' => 258, 'height' => 355),
            'Expected image to be scaled to 258x355 pixels.'
        );

        // test the behavior of scale with width only
        // 516/710 = 400/h => h = 550
        $img->transform('scale', array(400));
        $this->assertSame(
            $img->getImageSize(),
            array('width' => 400, 'height' => 550),
            'Expected image to be scaled to 300x300 pixels.'
        );

        // test the behavior of crop
        $img->transform('crop', array(100, 100, 50, 50));
        $this->assertSame(
            $img->getImageSize(),
            array('width' => 100, 'height' => 100),
            'Expected image to be cropped to 100x100 pixels.'
        );

        // test the behavior of unsupported transform
        try {
            $img->transform('foo');
            $this->fail('Expected failure with an unsupported transform.');
        } catch (P4Cms_Image_Exception $e) {
            $this->assertSame($e->getMessage(), "Transform \"foo\" is not supported.");
        } catch (Exception $e) {
            $this->fail('Unexpected exception: '. $e->getMessage());
        }
    }

     /**
     * Test behaviour of setData and getData.
     */
    public function testGetImageSize()
    {
        $img = new P4Cms_Image();
        $data = file_get_contents(TEST_ASSETS_PATH . '/images/luigi.png');
        $img->setData($data);
        $this->assertSame(
            $img->getImageSize(),
            array('width' => 516, 'height' => 710),
            'Expected image size to be 516x710 pixels.'
        );

        if (extension_loaded('imagick')) {
            $img = new P4Cms_Image();
            try {
                $img->getImageSize();
                $this->fail('Expected failure with empty Imagick object.');
            } catch (P4Cms_Image_Exception $e) {
                $this->assertSame(
                    $e->getMessage(),
                    "Can not get image size: no image data were set."
                );
            } catch (Exception $e) {
                $this->fail('Unexpected exception: '. $e->getMessage());
            }
        } else {
            $this->markTestIncomplete("Cannot verify default driver, 'imagick' extension not found.");
        }
    }

    /**
     * Test behaviour of missing data.
     */
    public function testMissingData()
    {
        $img = new P4Cms_Image();
        try {
            $img->transform('sharpen');
            $img->getData();
            $this->fail('Expected failure with no data available.');
        } catch (P4Cms_Image_Exception $e) {
            $this->assertSame(
                $e->getMessage(),
                "Cannot do 'sharpen': image contains no data."
            );
        } catch (Exception $e) {
            $this->fail('Unexpected exception: '. $e->getMessage());
        }
    }
}