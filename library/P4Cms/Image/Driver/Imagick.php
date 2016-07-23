<?php
/**
 * Implementation of P4Cms_Image_Driver_Interface using the 'imagick' extension.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Image_Driver_Imagick extends P4Cms_Image_Driver_Abstract
{
    protected static    $_requiredExtension     = 'imagick';
    protected static    $_supportedTransforms   = array(
        'scale',
        'sharpen',
        'crop',
        'rotate'
    );
    protected           $_imagick               = null;

    /**
     * Set the image data.
     *
     * @param   string|null                 $data optional - image data
     * @return  P4Cms_Image_Driver_Imagick  provides fluent interface
     */
    public function setData($data = null)
    {
        if ($data) {
            $this->_getImagick()->readImageBlob($data);
        } else {
            $this->_imagick = null;
        }
        return $this;
    }

    /**
     * Return binary image data in the given format.
     *
     * @param   string          $type   optional - the image format (will return image data
     *                                  in the same format as input if not provided)
     * @return  string|null     binary image data or null if no image data were set
     */
    public function getData($type = null)
    {
        // early exit if there are no image data
        if (!$this->hasData()) {
            return null;
        }

        // set output image type if specified
        if ($type) {
            if (!static::isSupportedType($type)) {
                throw new P4Cms_Image_Exception("Image type '$type' is not supported.");
            }
            $this->_getImagick()->setImageFormat($type);
        }

        return $this->_getImagick()->getImageBlob();
    }

    /**
     * Check if there are image data to operate with.
     *
     * @return  bool    true if there has been image data set, false otherwise.
     */
    public function hasData()
    {
        return $this->_imagick !== null;
    }

    /**
     * Check if given image type is supported.
     *
     * @param   string  $type   image type to check for
     * @return  bool    true if given image type is supported, false otherwise
     */
    public static function isSupportedType($type)
    {
        // get list of supported image types, normalized to lowercase
        $types = array_map('strtolower', static::_getSupportedTypes());

        return in_array(strtolower($type), $types);
    }

    /**
     * Return list of supported image types.
     *
     * @return  array   list of supported image types
     */
    protected static function _getSupportedTypes()
    {
        $imagick = new Imagick;
        return $imagick->queryFormats();
    }

    /**
     * Scale the image to the given size.
     *
     * @param   int     $width      the width in pixels
     * @param   int     $height     the height in pixels
     */
    protected function _scale($width = null, $height = null)
    {
        if (!$width && !$height) {
            throw new P4Cms_Image_Exception(
                'At least one of width or height is required.'
            );
        }

        $this->_getImagick()->resizeImage(
            $width,
            $height,
            Imagick::FILTER_LANCZOS,
            1
        );
    }

    /**
     * Sharpens an image by blurring with the given radius and subtracting from
     * the original with the deviation. The radius should be larger than the
     * deviation. A radius of zero will let the driver pick a suitable value.
     * see: http://en.wikipedia.org/wiki/Unsharp_masking
     */
    protected function _sharpen()
    {
        $this->_getImagick()->sharpenImage(0, 1);
    }

    /**
     * Crop the image to the given size and position.
     *
     * @param   int|null    $width      the width in pixels
     * @param   int|null    $height     the height in pixels
     * @param   int         $x          the x coordinate starting position from the left
     * @param   int         $y          the y coordinate starting position from the top
     */
    protected function _crop($width = null, $height = null, $x = 0, $y = 0)
    {
        if (!$width || !$height) {
            throw new P4Cms_Image_Exception(
                'Both image width and height are required.'
            );
        }

        $this->_getImagick()->cropImage(
            $width,
            $height,
            $x,
            $y
        );
    }

    /**
     * Rotate the image.
     *
     * @param   float   $degrees    the rotation angle
     */
    protected function _rotate($degrees)
    {
        $this->_getImagick()->rotateImage(new ImagickPixel('none'), $degrees);
    }

    /**
     * Return image width in pixels.
     *
     * @return  int     image width in pixels
     */
    protected function _getImageWidth()
    {
        $geometry = $this->_getImagick()->getImageGeometry();
        return $geometry['width'];
    }

    /**
     * Return image height in pixels.
     *
     * @return  int     image height in pixels
     */
    protected function _getImageHeight()
    {
        $geometry = $this->_getImagick()->getImageGeometry();
        return $geometry['height'];
    }

    /**
     * Return reference to the Imagick object hold by this class. If there is no
     * reference to an existing Imagick object, it will create one and set the
     * reference, so the next time reference to the same object will be returned.
     *
     * @return  Imagick     Imagick object referenced by this class
     */
    protected function _getImagick()
    {
        if (!$this->_imagick) {
            $this->_imagick = new Imagick;
        }
        return $this->_imagick;
    }
}