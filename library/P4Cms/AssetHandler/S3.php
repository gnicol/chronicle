<?php
/**
 * Provide an Amazon S3 backend for Asset Handling. Storing generated 
 * assets in S3 moves them to a fast cookie free host which should give
 * a speed boost. Further, if you are horizontally scaling your web 
 * servers this will provide a shared data store.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_AssetHandler_S3 implements P4Cms_AssetHandlerInterface
{
    protected   $_bucket;
    protected   $_accessKey;
    protected   $_secretKey;
   
    protected   $_s3Service;

    /**
     * Constructor allows passing options for accessKey, secretKey 
     * or bucket.
     * 
     * @param   array   $options    Options to use on this instance
     */
    public function __construct(array $options = null)
    {
        foreach ($options ?: array() as $option => $value) {
            $method = 'set' . ucfirst($option);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    /**
     * Used to set the bucket for storage.
     * 
     * @param   string|null     $bucket     The bucket to store assets under
     * @return  P4Cms_AssetHandler_S3       To maintain a fluent interface
     */
    public function setBucket($bucket)
    {
        if (!is_string($bucket) && !is_null($bucket)) {
            throw new InvalidArgumentException("Bucket must be a string or null");
        }

        $this->_bucket = $bucket;
        
        return $this;
    }
    
    /**
     * Returns the bucket to store objects under. The 
     * DEFAULT_BUCKET constant will be used if no value
     * has been provided.
     * 
     * @return  string  The bucket assets will be stored in
     */
    public function getBucket()
    {
        return $this->_bucket;
    }

    /**
     * Used to set the s3 access key.
     * 
     * @param   string|null     $key    The access key to use
     * @return  P4Cms_AssetHandler_S3   To maintain a fluent interface
     */
    public function setAccessKey($key)
    {
        if (!is_string($key) && !is_null($key)) {
            throw new InvalidArgumentException("Access Key must be a string or null");
        }

        $this->_s3Service = null;
        $this->_accessKey = $key;
        
        return $this;
    }
    
    /**
     * Returns the s3 access key.
     * 
     * @return  string  The access key in use
     */
    public function getAccessKey()
    {
        return $this->_accessKey;
    }

    /**
     * Used to set the s3 secret key.
     * 
     * @param   string|null     $key    The secret key to use
     * @return  P4Cms_AssetHandler_S3   To maintain a fluent interface
     */
    public function setSecretKey($key)
    {
        if (!is_string($key) && !is_null($key)) {
            throw new InvalidArgumentException("Secret Key must be a string or null");
        }

        $this->_s3Service = null;
        $this->_secretKey = $key;
        
        return $this;
    }
    
    /**
     * Returns the s3 access key.
     * 
     * @return  string  The secret key in use
     */
    public function getSecretKey()
    {
        return $this->_secretKey;
    }
    
    /**
     * Check if the given ID exists in storage.
     * 
     * @param   string  $id     The ID to test
     * @return  bool    true if exists, false otherwise
     */
    public function exists($id)
    {
        $cacheId = 's3_asset_' . md5($id);
        if (P4Cms_Cache::load($cacheId)) {
            return true;
        }

        // if we can retreive meta-data cache result and return true
        $info = $this->_getS3Service()->getInfo($this->_toObjectId($id));
        if ($info) {
            P4Cms_Cache::save(true, $cacheId);
            return true;
        }

        // we don't cache failures as we will, most likely,
        // upload a copy shortly; simply return false
        return false;
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
        return $this->_getS3Service()->putObject(
            $this->_toObjectId($id),
            $data,
            $this->_getHeaders($id)
        );
    }
    
    /**
     * Provide a URI for the indicated asset ID.
     * 
     * @param   string  $id     The ID to get a URI for
     * @return  string|bool     The uri used for the specified asset or false
     */
    public function uri($id)
    {
        return 'http://' . Zend_Service_Amazon_S3::S3_ENDPOINT . '/' . $this->getBucket() . '/' . $id;
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
        return true;
    }
    
    /**
     * Returns the s3 service instance in use.
     * 
     * @return  Zend_Service_Amazon_S3  the s3 service instance
     */
    protected function _getS3Service()
    {
        if (!$this->_s3Service) {
            $this->_s3Service = new Zend_Service_Amazon_S3(
                $this->getAccessKey(), 
                $this->getSecretKey()
            );
        }

        return $this->_s3Service;
    }
    
    /** 
     * Translates a Asset ID to S3 Object ID. This is a simple 
     * task of tacking the bucket name on the front.
     * 
     * @param   string  $id     The id to translate to an object ID
     * @return  string  The object ID to use for S3 purposes
     */
    protected function _toObjectId($id)
    {
        return $this->getBucket() . '/' . $id;
    }
    
    /**
     * This method will provide the appropriate headers for the passed id.
     * It ensures the asset is publicly available and properly detects the
     * cssgz and jsgz file extensions to set content encoding/type as needed.
     * 
     * @param   string  $id     The id to generate headers for
     */
    protected function _getHeaders($id)
    {
        $headers = array(
            Zend_Service_Amazon_S3::S3_ACL_HEADER => Zend_Service_Amazon_S3::S3_ACL_PUBLIC_READ
        );

        // the S3 class won't detect our compressed assets
        // correctly so we manually setup the headers here.
        $extension = substr(strrchr($id, '.'), 1);
        if ($extension == 'cssgz') {
            $headers['Content-Type']     = 'text/css';
            $headers['Content-Encoding'] = 'gzip';
        } else if ($extension == 'jsgz') {
            $headers['Content-Type']     = 'text/javascript';
            $headers['Content-Encoding'] = 'gzip';
        }
        
        return $headers;
    }
}
