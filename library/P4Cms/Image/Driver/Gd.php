<?php
/**
 * Implementation of P4Cms_Image_Driver_Interface using the 'gd' extension.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Image_Driver_Gd extends P4Cms_Image_Driver_Abstract
{
    protected static    $_requiredExtension     = 'gd';
    protected static    $_supportedTransforms   = array(
        'scale',
        'sharpen',
        'crop',
        'rotate'
    );
    protected           $_resource              = null;
    protected           $_type                  = null;

    /**
     * Set the image data.
     *
     * @param   string|null             $data optional - image data
     * @return  P4Cms_Image_Driver_Gd   provides fluent interface
     */
    public function setData($data = null)
    {
        if ($data) {
            $this->_resource = imagecreatefromstring($data);
            $this->_type     = $this->_getType($data);
        } else {
            $this->_resource = null;
            $this->_type     = null;
        }
        return $this;
    }

    /**
     * Return binary image data.
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

        // if no image type was provided, use the type of input image
        $type = $type ?: $this->_type;

        // check if given type is supported
        if (!static::isSupportedType($type)) {
            throw new P4Cms_Image_Exception("Image type '$type' is not supported.");
        }

        // assemble callback to get the image data in a given type
        // @todo should we normalize jpg/jpeg as there is imagejpeg() function but no imagejpg()
        $callback = 'image' . strtolower($type);

        // get image data
        ob_start();
        call_user_func($callback, $this->_resource);
        $data = ob_get_contents();
        ob_end_clean();

        return $data;
    }

    /**
     * Check if there are image data to operate with.
     *
     * @return  bool    true if there has been image data set, false otherwise.
     */
    public function hasData()
    {
        return $this->_resource !== null;
    }

    /**
     * Check if given image type is supported.
     *
     * @param   string  $type   image type to check for
     * @return  bool    true if given image type is supported, false otherwise
     */
    public static function isSupportedType($type)
    {
        $constant = 'IMG_' . strtoupper($type);
        return defined($constant) && (imagetypes() & constant($constant));
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

        // calculate original image aspect ratio
        $sourceWidth  = $this->_getImageWidth();
        $sourceHeight = $this->_getImageHeight();
        $ratio        = $sourceWidth / $sourceHeight;

        // if only one dimension is given, calculate the another one such
        // that new image keeps same ratio as the original
        if (!$width) {
            $width  = round($height * $ratio);
        } else if (!$height) {
            $height = round($width / $ratio);
        }

        // resize the image
        $dst = imagecreatetruecolor($width, $height);
        imagecopyresized(
            $dst,
            $this->_resource,
            0,
            0,
            0,
            0,
            $width,
            $height,
            $sourceWidth,
            $sourceHeight
        );
        $this->_resource = $dst;
    }

    /**
     * Sharpen the image by applying a default 3x3 matrix passed to the
     * imageconvolution() function.
     */
    protected function _sharpen()
    {
        $matrix  = array(
            array( 0.0, -1.0,  0.0),
            array(-1.0,  5.0, -1.0),
            array( 0.0, -1.0,  0.0)
        );
        $divisor = array_sum(array_map('array_sum', $matrix));

        // sharpen image by using image convolution
        imageconvolution(
            $this->_resource,
            $matrix,
            $divisor,
            0
        );
    }

    /**
     * Crop the image to the given size and position.
     *
     * @param   int|null    $width      the width in pixels
     * @param   int|null    $height     the height in pixels
     * @param   int         $x          the x coordinate starting position
     * @param   int         $y          the y coordinate starting position
     */
    protected function _crop($width = null, $height = null, $x = 0, $y = 0)
    {
        if (!$width || !$height) {
            throw new P4Cms_Image_Exception(
                'Both width and height are required.'
            );
        }

        // crop the image
        $dst = imagecreatetruecolor($width, $height);
        imagecopy(
            $dst,
            $this->_resource,
            0,
            0,
            $x,
            $y,
            $width,
            $height
        );
        $this->_resource = $dst;
    }

    /**
     * Rotate the image.
     *
     * @param   float   $degrees    the rotation angle
     */
    protected function _rotate($degrees = 0)
    {
        $this->_resource = imagerotate($this->_resource, $degrees, 0);
    }

    /**
     * Return image width in pixels.
     *
     * @return  int     image width in pixels
     */
    protected function _getImageWidth()
    {
        return imagesx($this->_resource);
    }

    /**
     * Return image height in pixels.
     *
     * @return  int     image height in pixels
     */
    protected function _getImageHeight()
    {
        return imagesy($this->_resource);
    }

    /**
     * Detect image type from a given image data.
     *
     * @param   string  $imageData  data representing image to detect type on
     * @return  string  image type (jpeg, gif etc.)
     * @throws  P4Cms_Image_Exception   if image type cannot be determined
     */
    protected function _getType($imageData)
    {
        // try to get image type from 'finfo' if its available
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->buffer($imageData);

            return str_replace('image/', '', $mime);
        }

        throw new P4Cms_Image_Exception('Cannot determine image type.');
    }
}