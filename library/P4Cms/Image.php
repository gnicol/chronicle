<?php
/**
 * Perforce image manipulation API. The actual work is handled by implementations
 * of P4Cms_Image_Driver_Interface.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Image
{
    protected   $_driver        = null;
    protected   $_transforms    = null;

    /**
     * Push the given transformation on to the queue.
     *
     * @param   string      $transform      name of the transformation
     * @param   array       $args           optional - transformation arguments
     * @throws  P4Cms_Image_Exception       if given transformation is not supported
     * @return  P4Cms_Image                 provides fluent interface
     */
    public function transform($transform, $args = array())
    {
        // build a queue if neccessary
        if (!$this->_transforms) {
            $this->_transforms = new SplQueue();
        }

        // get the driver and make sure the transform is supported
        $driver = $this->getDriver();
        if (!$driver->isSupportedTransform($transform)) {
            throw new P4Cms_Image_Exception(
                'Transform "' . $transform . '" is not supported.'
            );
        }

        // queue the transform
        $this->_transforms->enqueue(array($transform, $args));
        return $this;
    }

    /**
     * Set the graphics library to use.
     *
     * @param   P4Cms_Image_Driver_Interface|null
     *                                  $driver optional - the image library driver
     *                                  to use
     * @return  P4Cms_Image             provides fluent interface
     * @throws  P4Cms_Image_Exception   if the driver is not valid
     */
    public function setDriver(P4Cms_Image_Driver_Interface $driver = null)
    {
        // if we are setting a non-null driver, process pending transformations
        // and set the data on the new driver with the result
        if ($driver) {
            $data = $this->hasDriver() ? $this->getData() : null;
            $driver->setData($data);
        }

        $this->_driver = $driver;
    }

    /**
     * Get the graphics library driver instance.
     * If there is no existing driver, function will try to create and set a default one.
     */
    public function getDriver()
    {
        // if no driver has been set, try to create default one
        if (!$this->_driver) {
            $this->_driver = P4Cms_Image_Driver_Factory::create();
        }

        return $this->_driver;
    }

    /**
     * Determine if an image driver has been set.
     *
     * @return  boolean  true if we have a driver, false otherwise.
     */
    public function hasDriver()
    {
        return $this->_driver instanceof P4Cms_Image_Driver_Interface;
    }

    /**
     * Set the data of the image.
     *
     * @param   string|null     $data  optional - the image data to use
     * @return  P4Cms_Image     provides fluent interface
     */
    public function setData($data = null)
    {
        if (isset($data) && !is_string($data)) {
            throw new InvalidArgumentException(
                'Data has to be a string or null.'
            );
        }
        $this->getDriver()->setData($data);

        return $this;
    }

    /**
     * Get the current data for the image including any pending transformations.
     *
     * @param   string  $type   optional - the requested image format
     * @return  string  the binary image data
     */
    public function getData($type = null)
    {
        // apply pending transformations
        $this->_processQueue();

        return $this->getDriver()->getData($type);
    }

    /**
     * Return the resulting image dimensions after pending transforms have been
     * applied.
     *
     * @return  array   first element is width in pixels, second element is
     *                  height in pixels
     */
    public function getImageSize()
    {
        // apply pending transformations
        $this->_processQueue();

        return $this->getDriver()->getImageSize();
    }

    /**
     * Check if the given image type is supported. Result is dependent on the driver.
     * Return false if unable to get a driver.
     *
     * @param   string                      $type   image type to check for
     * @return  bool                        true if given image type is supported by the driver,
     *                                      false otherwise; also return false if unable to get
     *                                      a driver
     * @throws  InvalidArgumentException    if input $type is not a string
     */
    public function isSupportedType($type)
    {
        if (!is_string($type)) {
            throw new InvalidArgumentException("Type must be a string.");
        }

        // check if driver supports the type;
        // return false if we are unable to get a driver
        try {
            return $this->getDriver()->isSupportedType($type);
        } catch (P4Cms_Image_Exception $e) {
            return false;
        }
    }

    /**
     * Process any pending transformations.
     */
    protected function _processQueue()
    {
        $queue = $this->_transforms;

        // if there is a queue, apply all transformations in it.
        if ($queue) {
            $driver = $this->getDriver();
            while (!$queue->isEmpty()) {
                $transform = $queue->dequeue();
                call_user_func_array(array($driver, "transform"), $transform);
            }
        }

        $this->_transforms = null;
    }
}