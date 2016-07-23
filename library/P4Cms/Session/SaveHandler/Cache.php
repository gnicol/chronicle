<?php
/**
 * This save handler allows you to use any of the cache backends
 * for session storage. We intend it primarily to allow memcached
 * to be used for session storage via the MemcacheTagged backend.
 *
 * To enable this save handler from the application.ini use the format:
 *  resources.session.savehandler.class = P4Cms_Session_SaveHandler_Cache
 *  resources.session.savehandler.options.backend.name = P4Cms_Cache_Backend_MemcachedTagged
 *  resources.session.savehandler.options.backend.customBackendNaming = 1
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Session_SaveHandler_Cache implements Zend_Session_SaveHandler_Interface
{
    protected $_backend  = null;
    protected $_idPrefix = 'session-';

    /**
     * If the passed options array contains a 'backend' or 'idPrefix'
     * key the value will be passed to 'setBackend' or 'setIdPrefix'
     * respectively. No other options are presently supported.
     * 
     * @param   type    $options    allows passing in a cache backend
     */
    public function __construct($options = null)
    {
        if (isset($options['backend'])) {
            $this->setBackend($options['backend']);
        }
        
        if (isset($options['idPrefix'])) {
            $this->setIdPrefix($options['idPrefix']);
        }
    }

    /**
     * Returns the the cache backend presently in use or null if none.
     * 
     * @return  Zend_Cache_Backend_Interface|null   the backend or null
     */
    public function getBackend()
    {
        return $this->_backend;
    }
    
    /**
     * Used to set a cache backend to be used for session storage.
     * You can pass in a backend instance or an array of config 
     * details. The array format matches that of the 'backend' section
     * used by the Zend_Cache_Manager.
     * 
     * @param   Zend_Cache_Backend_Interface|array|null     $backend    A backend or null
     * @return  P4Cms_Session_SaveHandler_Cache     To maintain a fluent interface
     */
    public function setBackend($backend)
    {
        // deal with object or null input
        if ($backend instanceof Zend_Cache_Backend_Interface || $backend === null) {
            $this->_backend = $backend;
            return $this;
        }

        // if we don't have a valid array by here, we have to throw
        if (!is_array($backend) || !isset($backend['name'])) {
            throw new InvalidArgumentException('Can not set invalid backend');
        }

        // use the zend_cache factory to convert array input to a backend
        // this is mainly useful for dealing with shorthand backend names.
        $frontend = Zend_Cache::factory(
            new Zend_Cache_Core,
            $backend['name'],
            array(),
            isset($backend['options']) ? $backend['options'] : array(),
            false,
            isset($backend['customBackendNaming']) ? $backend['customBackendNaming'] : false
        );
        $this->_backend = $frontend->getBackend();

        return $this;
    }
    
    /**
     * Returns the current ID prefix. See mutator
     * for details.
     * 
     * @return  string|null     The id prefix in use
     */
    public function getIdPrefix()
    {
        return $this->_idPrefix;
    }
    
    /**
     * You can specify an id prefix to use when storing session
     * details in the cache backend. This prefix will only be used
     * under the hood, it won't have any impact on the session key
     * sent as a cookie to the end user.
     * 
     * This is intended to allow namespacing the session data when
     * sharing your cache backend with other data.
     * 
     * @param   string|null     $idPrefix   The id prefix to use or null
     * @return  P4Cms_Session_SaveHandler_Cache     To maintain a fluent interface
     */
    public function setIdPrefix($idPrefix)
    {
        if (!is_string($idPrefix) && !is_null($idPrefix)) {
            throw new InvalidArgumentException('ID prefix must be a string or null');
        }

        $this->_idPrefix = $idPrefix;

        return $this;
    }
 
    /**
     * Simply returns true. It doesn't appear we need this 
     * method but the interface requires an implementation.
     *
     * @param   string  $savePath   not used
     * @param   string  $name       not used
     * @return  bool    always true
     */
    public function open($savePath, $name)
    {
        return true;
    }

    /**
     * Simply returns true. It doesn't appear we need this 
     * method but the interface requires an implementation.
     *
     * @return  bool    always true
     */
    public function close()
    {
        return true;
    }

    /**
     * Read session data from our cache backend. 
     *
     * @param   string  $id     The session id to return details for.
     * @return  mixed   The data the was previously stored or false
     */
    public function read($id)
    {
        return $this->_getBackend()->load($this->getIdPrefix() . $id);
    }

    /**
     * Write Session - commit/update data to our cache backend
     * If you are using a shared cache backend these IDs could
     * potentially collide with other entries; consider setting
     * an id prefix to reduce collision likelyhood.
     *
     * @param   string  $id     The session id to store under
     * @param   mixed   $data   The data to store
     * @return  bool    The result of calling save on our cache backend
     */
    public function write($id, $data)
    {
        return $this->_getBackend()->save(
            $data, 
            $this->getIdPrefix() . $id, 
            array(), 
            Zend_Session::getOptions('gc_maxlifetime')
        );
    }

    /**
     * Destroy Session - remove data from cache backend
     * for the given session id.
     *
     * @param   string  $id     The session id we are removing
     * @return  bool    The result of calling remove on our cache backend
     */
    public function destroy($id)
    {
        return $this->_getBackend()->remove($this->getIdPrefix() . $id);
    }

    /**
     * This is intended to remove session data older
     * than $maxlifetime (in seconds).
     * 
     * Our cache backends doen't have this capability
     * so we have simply stubbed out the method.
     *
     * @param   int     $maxlifetime    not used
     * @return  bool    always true
     */
    public function gc($maxlifetime)
    {
        return true;
    }
    
    /**
     * Returns the cache backend or throws if none is set.
     * This differs from the public accessor which will return
     * null when no backend is set.
     * 
     * @throws  Zend_Session_SaveHandler_Exception  If no cache backend is set
     * @return  Zend_Cache_Backend_Interface    Current backend
     */
    protected function _getBackend()
    {
        $backend = $this->getBackend();
        
        if (!$backend) {
            throw new Zend_Session_SaveHandler_Exception('No cache has been set');
        }
        
        return $backend;
    }
}