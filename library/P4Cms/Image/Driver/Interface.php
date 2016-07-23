<?php
/**
 * Defines the interface that Perforce image manipulation drivers must implement.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
interface P4Cms_Image_Driver_Interface
{
    /**
     * Set the image data.
     *
     * @param   string|null                     $data   optional - image data
     * @return  P4Cms_Image_Driver_Interface    provides fluent interface
     */
    public function setData($data = null);

    /**
     * Return binary image data.
     *
     * @param   string  $type   optional - the image format (will return image data in the
     *                          same format as input if not provided)
     * @return  string  binary image data
     */
    public function getData($type = null);

    /**
     * Check if there are image data to operate with.
     *
     * @return  bool    true if there has been image data set, false otherwise.
     */
    public function hasData();

    /**
     * Apply the given transformation.
     *
     * @param   string  $transform  name of the transformation
     * @param   array   $args       optional - transformation arguments
     */
    public function transform($transform, $args = array());

    /**
     * Determine if the passed transformation is supported by this driver.
     *
     * @param   string      $transform  the transformation to query.
     * @return  boolean     true if the transformation supported by the current driver,
     *                      false otherwise.
     */
    public static function isSupportedTransform($transform);

    /**
     * Check if given image type is supported.
     *
     * @param   string  $type   image type to check for
     * @return  bool    true if given image type is supported, false otherwise
     */
    public static function isSupportedType($type);

    /**
     * List of supported transformations.
     *
     * @return  array  a list of the supported transform operations
     */
    public static function getSupportedTransforms();

    /**
     * Return the image dimensions.
     *
     * @return  array   associated array with image dimensions
     */
    public function getImageSize();
}