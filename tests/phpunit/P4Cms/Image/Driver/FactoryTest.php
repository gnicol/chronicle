<?php
/**
 * Test P4Cms_Image_Driver_Factory class.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Image_Driver_FactoryTest extends TestCase
{
    /**
     * Skip tests if no image drivers can be instantiated (i.e. no image extensions are available).
     */
    public function setUp()
    {
        // skip tests if neither of 'gd' or 'imagick' extensions are present
        $drivers = array();

        try {
            $drivers[] = new P4Cms_Image_Driver_Gd;
        } catch (P4Cms_Image_Exception $e) {
        }

        try {
            $drivers[] = new P4Cms_Image_Driver_Imagick;
        } catch (P4Cms_Image_Exception $e) {
        }

        if (!count($drivers)) {
            $this->markTestSkipped("No image drivers available to test with.");
        }
    }

    /**
     * Ensure that user cannot create an instance of P4Cms_Image_Driver_Factory class.
     */
    public function testConstructor()
    {
        $reflector   = new ReflectionClass('P4Cms_Image_Driver_Factory');
        $constructor = $reflector->getConstructor();
        $this->assertTrue(
            $constructor && !$constructor->isPublic(),
            "Expected constructor is not public."
        );
    }

    /**
     * Test the create() method.
     */
    public function testCreate()
    {
        // test exception is thrown when passing invalid driver type
        try {
            $driver = P4Cms_Image_Driver_Factory::create('P4Cms_Image_Driver_Foo');
            $this->fail("Unexpected success of creating invalid driver.");
        } catch (P4Cms_Image_Exception $e) {
            $this->assertTrue(true);
        }

        try {
            $driver = P4Cms_Image_Driver_Factory::create('P4Cms_Image_Driver');
            $this->fail("Unexpected success of creating invalid driver.");
        } catch (P4Cms_Image_Exception $e) {
            $this->assertTrue(true);
        }

        // ensure that imagic driver can be created if imagick extension is present
        if (extension_loaded('imagick')) {
            $this->assertTrue(
                P4Cms_Image_Driver_Factory::create('P4Cms_Image_Driver_Imagick') instanceof P4Cms_Image_Driver_Imagick,
                "Expected class of imagick driver"
            );
        } else {
            try {
                $driver = P4Cms_Image_Driver_Factory::create('P4Cms_Image_Driver_Imagick');
                $this->fail("Unexpected success of creating driver without extension loaded.");
            } catch (P4Cms_Image_Exception $e) {
                $this->assertTrue(true);
            }
        }

        // ensure that gd driver can be created if gd extension is present
        if (extension_loaded('gd')) {
            $this->assertTrue(
                P4Cms_Image_Driver_Factory::create('P4Cms_Image_Driver_Gd') instanceof P4Cms_Image_Driver_Gd,
                "Expected class of gd driver"
            );
        } else {
            try {
                $driver = P4Cms_Image_Driver_Factory::create('P4Cms_Image_Driver_Gd');
                $this->fail("Unexpected success of creating driver without extension loaded.");
            } catch (P4Cms_Image_Exception $e) {
                $this->assertTrue(true);
            }
        }

        // ensure that factory class will create default driver in the given order: imagick, gd
        $defaultDriverClass = null;
        if (extension_loaded('imagick')) {
            $defaultDriverClass = 'P4Cms_Image_Driver_Imagick';
        } else if (extension_loaded('gd')) {
            $defaultDriverClass = 'P4Cms_Image_Driver_Gd';
        }

        if ($defaultDriverClass) {
            $this->assertTrue(
                P4Cms_Image_Driver_Factory::create() instanceof $defaultDriverClass,
                "Expected class of default image driver."
            );
        }
    }
}