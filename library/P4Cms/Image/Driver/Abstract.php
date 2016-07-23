<?php
/**
 * Abstract class for image drivers.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
abstract class P4Cms_Image_Driver_Abstract implements P4Cms_Image_Driver_Interface
{
    protected static    $_supportedTransforms   = array();
    protected static    $_requiredExtension     = null;

    /**
     * Deny creating an instance if required extension (if set) is not loaded.
     *
     * @throws  P4Cms_Image_Exception   if the required extension is not loaded
     */
    public function __construct()
    {
        $extension = static::$_requiredExtension;
        if ($extension && !extension_loaded($extension)) {
            throw new P4Cms_Image_Exception(
                "Cannot create image driver: required extension '"
                . $extension . "' is not loaded."
            );
        }
    }

    /**
     * Return php extension that must be installed to make the driver work or null
     * if no extension is needed.
     *
     * @return  string|null     php extension required by the driver or null if no
     *                          extension required
     */
    public static function getRequiredExtension()
    {
        return static::$_requiredExtension;
    }

    /**
     * Determine if the given transformation is supported by this driver.
     *
     * @param   string      $transform  the transformation to check for
     * @return  boolean     true if the transformation supported by the current
     *                      driver, false otherwise
     */
    public static function isSupportedTransform($transform)
    {
        return in_array($transform, static::getSupportedTransforms());
    }

    /**
     * Return list of supported transformations.
     *
     * @return  array   a list of the supported transform operations
     */
    public static function getSupportedTransforms()
    {
        return static::$_supportedTransforms;
    }

    /**
     * Return the image dimensions.
     *
     * @return  array   associative array with image width and height in pixels
     */
    public function getImageSize()
    {
        // cannot get image size if there are no image data
        if (!$this->hasData()) {
            throw new P4Cms_Image_Exception(
                "Can not get image size: no image data were set."
            );
        }

        return array(
            'width'  => $this->_getImageWidth(),
            'height' => $this->_getImageheight()
        );
    }

    /**
     * Apply the given transformation.
     *
     * @param   string      $transform          name of the transformation
     * @param   array       $args               optional - transformation arguments
     * @throws  P4Cms_Image_Exception           if given transformation is not supported
     */
    public function transform($transform, $args = array())
    {
        // throw an exception if transformation is not supported
        if (!static::isSupportedTransform($transform)) {
            throw new P4Cms_Image_Exception("Transformation '$transform' is not supported.");
        }

        // cannot transform if there are no image data
        if (!$this->hasData()) {
            throw new P4Cms_Image_Exception(
                "Cannot do '" . $transform . "': image contains no data."
            );
        }

        // assemble callback function to apply given transform on the image data
        $callback = array('static', '_' . strtolower($transform));
        if (!is_callable($callback)) {
            throw new P4Cms_Image_Exception("Transform '$transform' is not implemented.");
        }
        call_user_func_array($callback, $args);
    }

    /**
     * Return image width in pixels.
     *
     * @return  int     image width in pixels
     */
    abstract protected function _getImageWidth();

    /**
     * Return image height in pixels.
     *
     * @return  int     image height in pixels
     */
    abstract protected function _getImageHeight();
}