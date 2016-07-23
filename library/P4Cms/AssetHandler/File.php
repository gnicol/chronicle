<?php
/**
 * Provide a local file backend for Asset Handling. This is the
 * default backend and is intended for single web server deployments.
 * If you wish to use this backend with horizontally scaled web servers
 * the output path should point to a shared storage location (e.g. nfs).
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_AssetHandler_File implements P4Cms_AssetHandlerInterface
{
    protected   $_outputPath;
    protected   $_basePath;

    /**
     * Constructor allows passing options for outputPath or basePath.
     * 
     * @param   array   $options    Options to use on this instance
     */
    public function __construct(array $options = null)
    {
        if (isset($options['outputPath'])) {
            $this->setOutputPath($options['outputPath']);
        }
        if (isset($options['basePath'])) {
            $this->setBasePath($options['basePath']);
        }
    }
    
    /**
     * Sets the base path to use. Set to null to enable 
     * default base path.
     * 
     * @param   string|null     $path   The base path to use or null for default
     * @return  P4Cms_AssetHandler_File To maintain a fluent interface
     */
    public function setBasePath($path)
    {
        if (!is_string($path) && !is_null($path)) {
            throw new InvalidArgumentException('Path must be a string or null');
        }

        $this->_basePath = $path ? rtrim($path, '/\\') : $path;

        return $this;
    }
    
    /**
     * Returns the base path presently in use. Defaults to the
     * value of BASE_PATH define if not set.
     * 
     * @return  string  The current base path
     */
    public function getBasePath()
    {
        return $this->_basePath ?: BASE_PATH;
    }

    /**
     * Sets the output path to use.
     * 
     * @param   string|null     $path   The output path
     * @return  P4Cms_AssetHandler_File To maintain a fluent interface
     */
    public function setOutputPath($path)
    {
        if (!is_string($path) && !is_null($path)) {
            throw new InvalidArgumentException('Path must be a string or null');
        }

        // if we have a path strip trailing slashes and try to ensure it exists
        if ($path) {
            $path = rtrim($path, '/\\');
            @mkdir($path, 0755, true);
        }

        $this->_outputPath = $path;

        return $this;
    }
    
    /**
     * Returns the output path presently in use or null.
     * 
     * @return  string|null     The current output path
     */
    public function getOutputPath()
    {
        return $this->_outputPath;
    }

    /**
     * Check if the given ID exists in storage.
     * 
     * @param   string  $id     The ID to test
     * @return  bool    true if exists, false otherwise
     */
    public function exists($id)
    {
        if (!$this->getOutputPath()) {
            return false;
        }
        
        return file_exists($this->getOutputPath() . '/' . $id);
    }
    
    /**
     * Store the passed data using indicated ID.
     * Will clobber any existing entry with the same ID.
     * 
     * @param   string  $id     The ID to store under
     * @param   string  $data   The data to store
     * @return  bool    True if put was successful, false otherwise
     */
    public function put($id, $data)
    {
        if (!$this->getOutputPath()) {
            return false;
        }

        $result = @file_put_contents($this->getOutputPath() . '/' . $id, $data);
        
        // if we failed to write, log as a warning and return false.
        if ($result === false) {
            $writable = is_writable($this->getOutputPath());
            $message  = "Failed to put asset '" . $id . "'. Output path is "
                      . ($writable ? '' : 'not ') . 'writable';
            P4Cms_Log::log($message, P4Cms_Log::WARN);
            
            return false;
        } else {
            return true;
        }
    }

    /**
     * Provide a URI for the indicated asset ID.
     * 
     * @param   string  $id     The ID to get a URI for
     * @return  string|bool     The uri used for the specified asset or false
     */
    public function uri($id)
    {
        if (!$this->getOutputPath()) {
            return false;
        }

        $request = Zend_Controller_Front::getInstance()->getRequest();
        $baseUrl = isset($request)
                 ? $request->getBaseUrl()
                 : '';

        return $baseUrl . str_replace($this->getBasePath(), '', $this->getOutputPath()) . "/" . $id;
    }
    
    /**
     * Used to determine if the asset handler will be storing
     * assets offsite or not. Assets such as CSS need to know 
     * this so they can decide if they need to include the 
     * site's url when referencing images.
     * 
     * @return  bool    true if assets stored offsite, false otherwise
     */
    public function isOffsite()
    {
        return false;
    }
}
