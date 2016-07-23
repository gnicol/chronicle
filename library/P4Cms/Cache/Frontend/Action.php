<?php
/**
 * The Action Cache operates in a similar manner to Zend's Page Cache frontend
 * but defines caching rules based on the module/controller/action instead of URI.
 *
 * Zend's page cache utilizes uri regex matching to determine cachability; this
 * approach isn't compatible with custom URLs. They also don't support things like
 * username, rolename, filtering session variables, on the fly tagging or base url.
 *
 * Our Action cache allows you to set rules based on the module/controller/action
 * the request ends up being routed to. We store the options used for the cached
 * action under the request URI. These options are then used to make a seperate
 * data id that holds the actual cached page and headers. Using this approach
 * allows the final data url to include things like the active user's rolenames
 * thereby storing, and serving, multiple versions of a given page.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Cache_Frontend_Action extends Zend_Cache_Core
{
    const   SESSION_NAMESPACE   = 'p4cms.cache.action';


    /**
     * This frontend specific options
     *
     * ====> (boolean) content_type_memorization :
     * - pass true to memorize the value of the Content-Type header and replay it
     *   when cache is hit. Defaults to true.
     *
     * ====> (array) memorize_headers :
     * - an array of strings corresponding to some HTTP headers name. Listed headers
     *   will be stored with cache datas and "replayed" when the cache is hit
     *
     * ====> (array) default_options :
     * - an associative array of default options :
     *     - (boolean) cache : cache is on by default if true
     *     - (boolean) compress : if server and client support, conten will be gzip'ed.
     *                            if the served page utilizes the css or js aggregators
     *                            it is critical this be enabled.
     *     - (boolean) cache_with_XXX  (XXXX = 'get', 'post', 'session', etc) :
     *       if true,  cache is still on even if the item has value(s)
     *       if false, cache is off if the item has value(s)
     *     - (boolean) make_id_with_XXX (XXXX = 'get', 'post', 'session', etc) :
     *       if true, we have to use the value(s) of the specified item to make cache validator
     *       if false, the cache validator won't be dependent of the value(s) of the specified item
     *     - (int) specific_lifetime : cache specific lifetime
     *                                (false => global lifetime is used, null => infinite lifetime,
     *                                 integer => this lifetime is used), this "lifetime" is probably only
     *                                usefull when used with "actions" array
     *     - (array) tags : array of tags (strings)
     *     - (int) priority : integer between 0 (very low priority) and 10 (maximum priority) used by
     *                        some particular backends
     *
     * ====> (array) actions :
     * - an associative array to set options only for some actions
     * - keys are <module>/<controller>/<action> in route format (e.g. module/foo-bar/action)
     * - values are associative array with specific options to set if the action matches
     *   (see default_options for the list of available options)
     *
     * @var array $_specificOptions
     */
    protected $_specificOptions = array(
        'content_type_memorization' => true,
        'memorize_headers'          => array(),
        'actions'                   => array(),
        'default_options'           => array(
            'cache_with_get'        => false,
            'cache_with_post'       => false,
            'cache_with_session'    => false,
            'cache_with_files'      => false,
            'cache_with_cookies'    => true,
            'cache_with_username'   => false,
            'cache_with_rolename'   => true,
            'cache_with_locale'     => true,
            'make_id_with_get'      => true,
            'make_id_with_post'     => true,
            'make_id_with_session'  => true,
            'make_id_with_files'    => true,
            'make_id_with_cookies'  => false,
            'make_id_with_username' => false,
            'make_id_with_rolename' => true,
            'make_id_with_locale'   => true,
            'compress'              => true,
            'cache'                 => true,
            'specific_lifetime'     => false,
            'tags'                  => array(),
            'priority'              => null
        )
    );

    /**
     * When we push something into cache we will merge the default options,
     * action specific options and these active options together. Add items,
     * such as tags, to the activeOptions during execution so they can take
     * affect when storing the final result or testing for validity.
     *
     * @var array   $_activeOptions
     */
    protected           $_activeOptions             = array();

    protected           $_cancel                    = false;
    protected           $_ignoredSessionVariables   = null;
    protected           $_baseUrl                   = null;
    protected           $_username                  = null;
    protected           $_rolenames                 = null;
    protected           $_locale                    = null;

    protected static    $_session                   = null;

    /**
     * Constructor; allows the base options to be set.
     *
     * @param  array   $options     Associative array of options
     */
    public function __construct(array $options = array())
    {
        // merge in any passed options
        while (list($name, $value) = each($options)) {
            $name = strtolower($name);
            switch ($name) {
                case 'actions':
                case 'default_options':
                case 'content_type_memorization':
                    $this->_specificOptions[$name] = $this->_mergeOptions(
                        $this->_specificOptions[$name],
                        $value
                    );
                    break;
                default:
                    $this->setOption($name, $value);
            }
        }

        // this has to be on or action cache will break
        $this->setOption('automatic_serialization', true);
    }

    /**
     * Start the cache. If a cached entry is present for the current request
     * it will be served out and execution halted (unless do not die is passed).
     * If no suitable cached entry can be found the output buffer is setup so
     * we can attemp to capture a copy of the request at completion.
     *
     * @param   bool    $doNotLoad  Skip reading from cache, but still try to write.
     * @param   bool    $doNotDie   For unit testing only!
     * @return  bool    True if the cache is hit (false else)
     * @todo    consider having a flag to enable/disable cache hit/miss headers
     */
    public function start($doNotLoad = false, $doNotDie = false)
    {
        $this->_cancel = false;

        // attempt to read out the stored action options using the URI
        $options = $doNotLoad ? false : $this->load($this->_makeUriId());

        // if we could retreive the options; try and read the actual data out
        $dataId  = $options ? $this->_makeDataId($options) : false;
        $data    = ($options && $dataId) ? $this->load($dataId) : false;

        // if we can read the cached options and data out; serve it
        if ($data) {
            $content = $data['content'];
            $headers = $data['headers'];
            if (!headers_sent()) {
                // output that this was a cache hit
                header('X-Page-Cache: Hit');

                // if client included an etag and we have a match the client
                // already has a copy of the content so we exit early.
                // otherwise sends the etag to assist in future requests.
                if ($this->_handleEtag($data, $doNotDie)) {
                    return true;
                }

                // send any cached headers
                foreach ($headers as $key => $headerCouple) {
                    $name  = $headerCouple[0];
                    $value = $headerCouple[1];
                    header("$name: $value");
                }
            }

            echo $content;

            if ($doNotDie) {
                return true;
            }

            die();
        }

        // if we made it this far there was no cache hit.
        // connect the output buffer so we can attempt to store
        // the response at completion.
        ob_start(array($this, '_flush'));
        ob_implicit_flush(false);

        return false;
    }

    /**
     * Cancel the current caching process
     *
     * @todo stop output buffering when this is called
     */
    public function cancel()
    {
        $this->_cancel = true;
    }

    /**
     * Add a session variable key to the list we will ignore.
     * The key can be a simple top level key name such as 'foo' or you may
     * utilize unquoted array syntax to specify a child key such as:
     * 'foo[bar]' or 'foo[woozle][wobble]'.
     *
     * @param   string  $key    The key of the session variable to ignore
     * @return  P4Cms_Cache_Frontend_Page   To maintain a fluent interface
     */
    public function addIgnoredSessionVariable($key)
    {
        $curr = $this->getIgnoredSessionVariables();
        $new  = array($key);
        $new  = array_merge($new, $curr);
        $this->setIgnoredSessionVariables(
            array_merge($this->getIgnoredSessionVariables(), array($key))
        );

        return $this;
    }

    /**
     * Returns the list of session variable keys which will be ignored.
     *
     * The list is itself stored in the session under our SESSION_NAMESPACE
     * value. The SESSION_NAMESPACE is always ignored though it will not be
     * returned by this accessor unless manually added to the ignored keys.
     *
     * @return  array   The list of session variable keys we will ignore
     */
    public function getIgnoredSessionVariables()
    {
        if ($this->_ignoredSessionVariables === null) {
            $this->_ignoredSessionVariables = static::_getSession()->ignoredSessionVariables ?: array();
        }

        return $this->_ignoredSessionVariables;
    }

    /**
     * Cause the list of ignored session variable keys to contain only the passed keys.
     * See addIgnoredSessionVariable for details on the individual key format.
     *
     * @param   array   $keys   An array of strings representing session variable keys to ignore
     * @return  P4Cms_Cache_Frontend_Page   To maintain a fluent interface
     */
    public function setIgnoredSessionVariables(array $keys)
    {
        foreach ($keys as $key) {
            if (!$this->_isValidIgnoreKey($key)) {
                throw new InvalidArgumentException(
                    "Ignored session variable keys can only contain "
                    . "a-z, A-Z, 0-9, '_', '-', '.', '[', ']' and ' '."
                );
            }
        }

        // filter for unique values and re-index array.
        $this->_ignoredSessionVariables = array_values(array_unique($keys));

        static::_getSession()->ignoredSessionVariables = $this->_ignoredSessionVariables;

        return $this;
    }

    /**
     * Add the specified tag to the active options.
     *
     * @param   string  $tag    The tag to add
     */
    public function addTag($tag)
    {
        return $this->addTags(array($tag));
    }

    /**
     * Add the specified tags to the active options.
     *
     * @param   array   $tags    The tags to add
     */
    public function addTags(array $tags)
    {
        static::_validateTagsArray($tags);

        // ensure tags option is initialized
        if (!isset($this->_activeOptions['tags'])) {
            $this->_activeOptions['tags'] = array();
        }

        // mix in the new tags ensure we don't have duplicates
        $this->_activeOptions['tags'] = array_unique(
            array_merge($this->_activeOptions['tags'], $tags)
        );

        return $this;
    }

    /**
     * Get the current list of tags.
     *
     * @return  array   Array of tags
     */
    public function getTags()
    {
        return isset($this->_activeOptions['tags']) ? $this->_activeOptions['tags'] : array();
    }

    /**
     * Get the base url set on this instance.
     *
     * @return  string|null     The base URL
     */
    public function getBaseUrl()
    {
        return $this->_baseUrl;
    }

    /**
     * Set a base url on this instance.
     *
     * @param   string|null     $baseUrl    The base url to use
     * @return  P4Cms_Cache_Frontend_Page   To maintain a fluent interface
     */
    public function setBaseUrl($baseUrl)
    {
        if (!is_string($baseUrl) && !is_null($baseUrl)) {
            throw new InvalidArgumentException('Base URL must be a string or null');
        }

        $this->_baseUrl = $baseUrl;

        return $this;
    }

    /**
     * Get the username set on this instance.
     *
     * @return  string|null     The username
     */
    public function getUsername()
    {
        return $this->_username;
    }

    /**
     * Set a username on this instance.
     *
     * @param   string|null     $username   The username to use
     * @return  P4Cms_Cache_Frontend_Page   To maintain a fluent interface
     */
    public function setUsername($username)
    {
        if (!is_string($username) && !is_null($username)) {
            throw new InvalidArgumentException('Username must be a string or null.');
        }

        $this->_username = $username;

        return $this;
    }

    /**
     * Get the rolenames set on this instance.
     *
     * @return  array|null     The role names
     */
    public function getRolenames()
    {
        return $this->_rolenames;
    }

    /**
     * Set rolenames on this instance.
     *
     * @param   array|null  $rolenames      The rolenames to use
     * @return  P4Cms_Cache_Frontend_Page   To maintain a fluent interface
     */
    public function setRolenames($rolenames)
    {
        if ((!is_array($rolenames) && !is_null($rolenames))
            ||
            (is_array($rolenames) && in_array(false, array_map('is_string', $rolenames)))
        ) {
            throw new InvalidArgumentException('Role names must be an array of strings or null');
        }

        $this->_rolenames = $rolenames;

        return $this;
    }

    /**
     * Callback for output buffering (shouldn't really be called manually)
     * If the current response is for a known action, and our options allow
     * caching this response, pushes a copy into cache for later usage.
     *
     * @param   string  $content    Buffered output
     * @return  string  Data to send to browser
     */
    public function _flush($content)
    {
        if ($this->_cancel) {
            return $content;
        }

        $request = Zend_Controller_Front::getInstance()->getRequest();

        // though we should always get back a request; be defensive
        if (!$request) {
            return $content;
        }

        $action  = $request->getModuleName() . '/'
                 . $request->getControllerName() . '/'
                 . $request->getActionName();

        // if this action isn't present return
        if (!isset($this->_specificOptions['actions'][$action])) {
            return $content;
        }

        // request was potentially cachable but missed; include a header
        headers_sent() ?: header('X-Page-Cache: Miss');

        // starting with default options, mix in the
        // actions options and any active options
        $options = $this->_specificOptions['default_options'];
        $options = $this->_mergeOptions($options, $this->_specificOptions['actions'][$action]);
        $options = $this->_mergeOptions($options, $this->_activeOptions);

        // if our cache is disabled or we cannot create a data id return
        $dataId = $this->_makeDataId($options);
        if (!$options['cache'] || !$dataId) {
            return $content;
        }

        // gzip content if compression is active and supported.
        // adds the Content-Encoding header to allow decoding.
        if ($options['compress'] && !headers_sent() && $this->_canCompress()) {
            $content = gzencode($content, 9);

            header('Content-Encoding: gzip');
            $this->_specificOptions['memorize_headers'][] = 'Content-Encoding';
        }

        // ensure content type is memorized if requested
        if ($this->_specificOptions['content_type_memorization']) {
            $this->_specificOptions['memorize_headers'][] = 'Content-Type';
        }

        // if we made it this far we have a cache-able response gather the data
        $storedHeaders = array();
        $keepHeaders   = array_map('strtolower', $this->_specificOptions['memorize_headers']);
        $keepHeaders   = array_unique($keepHeaders);
        foreach (headers_list() as $header) {
            $headerParts = explode(':', $header, 2);
            $headerName  = trim(array_shift($headerParts));
            $headerValue = trim(array_shift($headerParts));
            if (in_array(strtolower($headerName), $keepHeaders)) {
                $storedHeaders[] = array($headerName, $headerValue);
            }
        }

        // ensure a copy of the options are stored based on the request URI.
        $this->save(
            $options,
            $this->_makeUriId(),
            array(),
            $options['specific_lifetime'],
            $options['priority']
        );

        // store the actual data under the dataId (this is generated based on the options).
        $data = array(
            'content'   => $content,
            'headers'   => $storedHeaders,
            'etag'      => '"' . md5($content . serialize($storedHeaders)) . '"'
        );
        $this->save(
            $data,
            $dataId,
            $options['tags'],
            $options['specific_lifetime'],
            $options['priority']
        );

        // ensure the etag header is sent and exit at this point if
        // the client included a matching etag in their request
        $this->_handleEtag($data);

        return $content;
    }

    /**
     * This method will take care of sending the passed etag back out to the
     * client. It will also send a 304 not modified header and die if the
     * client has included a matching etag in their request.
     *
     * @param   array           $data       an array with 'etag' key
     * @param   bool            $doNotDie   for unit testing; if true we will simply return true
     *                                      instead of die'ing and the caller should then exit.
     * @return  bool                        true if etag matched, indicates no response need be
     *                                      sent (by default we die prior to return in this case),
     *                                      false otherwise
     */
    protected function _handleEtag($data, $doNotDie = false)
    {
        // normalize array input to a string or false if not present
        $etag = isset($data['etag']) ? $data['etag'] : false;

        // if we don't have an etag passed in or headers
        // have been sent we cannot continue
        if (!$etag || headers_sent()) {
            return false;
        }

        // remove the cache-control headers that get set
        // by php session_cache_limiter functionality.
        header_remove('Expires');
        header_remove('Cache-Control');
        header_remove('Pragma');

        // ensure the etag is sent back to client.
        header('ETag: ' . $data['etag']);

        // if the browser sent an etag; send back
        // not modified and die if its valid
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
            $_SERVER['HTTP_IF_NONE_MATCH'] == $data['etag']
        ) {
            header('HTTP/1.1 304 Not Modified');

            if ($doNotDie) {
                return true;
            }

            die();
        }

        return false;
    }

    /**
     * This method will make the URI based ID for the current
     * request. If caching occurs there will also be an instance
     *
     * @return  string  The cache ID to use
     */
    protected function _makeUriId()
    {
        $requestUri = $_SERVER['REQUEST_URI'];

        // strip the baseurl from the request uri if present
        if ($this->getBaseUrl() && strpos($requestUri, $this->getBaseUrl()) == 0) {
            $requestUri = substr($requestUri, strlen($this->getBaseUrl()));
        }

        return 'action_' . md5($requestUri);
    }

    /**
     * This method will generate a data id based on the passed options.
     * When we are reading an entry out of cache we first pull its
     * options using the uri id and then use this method to generate
     * a data id based on the uri and options we found.
     *
     * @param   array   $options    The action options to use
     * @return  string|bool         The data ID or false if this request shouldn't be cached
     */
    protected function _makeDataId($options)
    {
        $components = array('Username', 'Rolename', 'Get', 'Post', 'Session', 'Files', 'Cookies', 'Locale');
        $result     = '';
        foreach ($components as $component) {
            $lower = strtolower($component);
            $partialResult = $this->_makePartialDataId(
                $component,
                isset($options['cache_with_' . $lower])   ? $options['cache_with_' . $lower]   : true,
                isset($options['make_id_with_' . $lower]) ? $options['make_id_with_' . $lower] : false
            );

            if ($partialResult === false) {
                return false;
            }

            $result = $result . $partialResult;
        }

        // if compression is enabled adjust ID to indicate its use
        if ($options['compress']) {
            $result .= $this->_canCompress();
        }

        return 'action_data_' . md5($this->_makeUriId() . $result);
    }

    /**
     * Generates the data id chunk (or false) for the given paramater.
     *
     * @param   string  $param      Paramater name
     * @param   bool    $allow      If true, cache is still on even if there are some variables present
     * @param   bool    $include    If true, we have to use the content of the param to make a partial id
     * @return  string|false    Partial id (string) or false if validation has failed
     */
    protected function _makePartialDataId($param, $allow, $include)
    {
        $value = null;

        switch ($param) {
            case 'Get':
                $value = $_GET;
                break;
            case 'Post':
                $value = $_POST;
                break;
            case 'Cookies':
                if (isset($_COOKIE)) {
                    $value = $_COOKIE;
                } else {
                    $value = null;
                }
                break;
            case 'Files':
                $value = $_FILES;
                break;
            case 'Username':
                $value = $this->getUsername();

                // if the value was important and is unknown, abort caching
                if ((!$allow || $include) && $value === null) {
                    return false;
                }

                // Swap in null for empty strings to maintain normal flow.
                if (!strlen($value)) {
                    $value = null;
                }
                break;
            case 'Rolename':
                $value = $this->getRolenames();

                // if the value was important and is unknown, abort caching
                if ((!$allow || $include) && $value === null) {
                    return false;
                }
                break;
            case 'Session':
                // If a user has no cookies, they have no session, provide
                // an early exit to avoid starting one needlessly.
                if (!count($_COOKIE)) {
                    break;
                }

                $value = $this->_removeIgnoredSessionVariables();
                break;
            case 'Locale':
                // read out the locale if we don't already have it.
                // we cache the value the first time we encounter it to avoid
                // breaking caching in the unlikely circumstance the answer
                // changes during a cache-miss request.
                $this->_locale = $this->_locale ?: Zend_Locale::findLocale();

                $value = $this->_locale;
                break;
            default:
                return false;
        }

        if ($allow) {
            if ($include) {
                return serialize($value);
            }
            return '';
        }

        // if we made it here the value isn't allowed
        // fail if anything is present
        if (count($value) > 0) {
            return false;
        }

        return '';
    }

    /**
     * Merge options recursively; same approach as the protected
     * method in Zend_Application.
     *
     * @param   array   $array1     the defaults
     * @param   mixed   $array2     over-riding options to merge in
     * @return  array   The merged options
     */
    protected function _mergeOptions(array $array1, $array2 = null)
    {
        if (is_array($array2)) {
            foreach ($array2 as $key => $val) {
                if (is_array($array2[$key])) {
                    $array1[$key] = (array_key_exists($key, $array1) && is_array($array1[$key]))
                                  ? $this->_mergeOptions($array1[$key], $array2[$key])
                                  : $array2[$key];
                } else {
                    $array1[$key] = $val;
                }
            }
        }

        return $array1;
    }

    /**
     * Will remove the ignored session variables from $_SESSION variables.
     *
     * Further, any empty values will be removed recursively as these are
     * also ignored.
     *
     * @return  array|null  the session variables stripped of ignored/empty values.
     */
    protected function _removeIgnoredSessionVariables()
    {
        // ensure our session variable is always ignored
        // calling getIgnoredSessionVariables has the side effect
        // of ensuring the session is started; we must do this
        // prior to accessing the $_SESSION super global.
        $ignoredKeys = array_merge(
            $this->getIgnoredSessionVariables() ?: array(),
            array(static::SESSION_NAMESPACE)
        );

        $session = $_SESSION ?: array();

        // remove all ignored session keys from the session
        foreach ($ignoredKeys as $key) {


            // 'ignore keys' should be in the form of 'foo' or 'foo[bar][baz]'
            // transform them to look like '[foo]' or '[foo][bar][baz]'
            $key = preg_replace('/([^\[]+)(\[.*)?/', '[\\1]\\2', $key);

            // last stage of the transform, add single quotes around keys
            // changing our "[foo][bar]" style string to "['foo']['bar']"
            $key = str_replace(array('[', ']'), array("['", "']"), $key);

            // attempt to clear the session variable with this key
            eval('unset($session' . $key . ');');
        }


        // use a recursive callback to filter out all empty entries from session
        $recursiveEmpty = function($item) use (&$recursiveEmpty)
        {
            if (is_array($item)) {
                return array_filter($item, $recursiveEmpty);
            }
            if (count($item)) {
                return true;
            }
        };
        $session = array_filter($session, $recursiveEmpty);

        return $session;
    }

    /**
     * Return the static session object, initializing if necessary.
     *
     * @return Zend_Session_Namespace
     */
    protected static function _getSession()
    {
        if (!static::$_session instanceof Zend_Session_Namespace) {
            static::$_session = new Zend_Session_Namespace(static::SESSION_NAMESPACE);
        }

        return static::$_session;
    }

    /**
     * Ensure the ignore key only contains the characters:
     * a-z, A-Z, 0-9, '_', '-', '.', '[', ']', ' '
     *
     * @param   string  $key    The ignore key to validate
     * @return  bool    True if ignore key is valid, false otherwise
     */
    protected function _isValidIgnoreKey($key)
    {
        return is_string($key) && preg_match("/^[\w\.\-_\[\] ]+$/", $key);
    }

    /**
     * Checks if PHP and the active client both support compression.
     *
     * @return  bool    true if compression is possible false otherwise
     */
    protected function _canCompress()
    {
        // can't compress if php lacks gzip support
        if (!function_exists('gzencode')) {
            return false;
        }

        // given php is capable; base decision on client support
        $accept = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
        return strpos($accept, 'gzip') !== false;
    }
}
