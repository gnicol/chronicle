<?php
/**
 * Sites are used to group website configurations and contents.
 * Site configuration and content are stored in Perforce.
 *
 * This class models a particular branch (a stream) of a site.
 * The identifier for a site branch corresponds directly with
 * the id of a stream in Perforce.
 *
 * Arguably this class should not be called 'P4Cms_Site' as it
 * does not actually model a site, but rather a specific branch
 * of a site (a site contains many branches). We chose to treat
 * this as a implementation detail because in most cases the
 * calling code that interfaces with the site object does not
 * care that it is actually a branch. It is simpler to think of
 * it as a site.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Site extends P4Cms_Model
{
    const               ACL_RECORD_ID           = 'config/acl';
    const               SITE_PREFIX             = 'chronicle-';
    const               DEFAULT_BRANCH          = 'live';
    const               CACHE_KEY               = 'sites';

    const               FETCH_BY_SITE           = 'site';
    const               FETCH_BY_ACL            = 'acl';
    const               FETCH_SORT_FLAT         = 'flat';

    protected           $_acl                   = null;
    protected           $_adapter               = null;
    protected           $_config                = null;
    protected           $_connection            = null;
    protected           $_templateConnection    = null;
    protected           $_stream                = null;
    protected           $_parent                = null;

    protected static    $_idField               = 'id';
    protected static    $_sitesPackagesPath     = null;
    protected static    $_sitesDataPath         = null;
    protected static    $_activeSite            = null;

    /**
     * We need a custom sleep to exclude the adapter and connections.
     * Connection objects cannot be serialized.
     *
     * @return  array   list of properties to serialize
     */
    public function __sleep()
    {
        return array_diff(
            array_keys(get_object_vars($this)),
            array('_connection', '_templateConnection', '_adapter')
        );
    }

    /**
     * We need a custom wakeup to provide any unserialized connected
     * objects with valid connections.
     *
     * We use deferred connections to avoid actually creating the
     * connections until they are needed and to ensure that the
     * connected objects always use the same connection as the site.
     *
     * Note: the site config object takes care of itself. It always
     * defers to its associated site's adapter (e.g. in save()).
     */
    public function __wakeup()
    {
        if ($this->_stream) {
            $this->_stream->setConnection($this->getDeferredConnection());
        }
        if ($this->_acl) {
            $this->_acl->getRecord()->setAdapter($this->getDeferredAdapter());
        }
    }

    /**
     * Get the id of the site that this branch belongs to.
     *
     * @return  string|null     the site id for this branch.
     */
    public function getSiteId()
    {
        $id = $this->getId();

        if (!$id) {
            return null;
        }

        // try to extract the site id from the branch id.
        if (preg_match("#^//([^/]+)#", $id, $matches)) {
            return $matches[1];
        }

        throw new P4Cms_Site_Exception("Failed to get site id from site branch id.");
    }

    /**
     * Get the sub-folder of this site branch in the site depot.
     *
     * @return  string|null     the site branch sub-folder.
     */
    public function getBranchBasename()
    {
        return basename($this->getId()) ?: null;
    }

    /**
     * Check if a site exists with the given id.
     *
     * @param   mixed                       $id             the id to check for.
     * @param   P4_Connection_Interface     $connection     optional - a specific connection to use.
     * @return  bool                        true if the given id matches an existing site.
     */
    public static function exists($id, P4_Connection_Interface $connection = null)
    {
        try {
            static::fetch($id, $connection);
            return true;
        } catch (P4Cms_Model_NotFoundException $e) {
            return false;
        }
    }

    /**
     * Fetch a single site by id from the local sites list.
     *
     * @param   string                      $id             the id of the site to fetch.
     * @param   P4_Connection_Interface     $connection     optional - a specific connection to use.
     * @return  P4Cms_Site                  the matching site if one exists.
     * @throws  P4Cms_Model_NotFoundException   if the requested site can't be found.
     */
    public static function fetch($id, P4_Connection_Interface $connection = null)
    {
        // throw exception if no id given.
        if (!is_string($id) || !$id) {
            throw new InvalidArgumentException("No site id given.");
        }

        // find the identified site.
        $sites = static::fetchAll(null, $connection);
        if (!isset($sites[$id])) {
            throw new P4Cms_Model_NotFoundException("Cannot find the specified site.");
        }

        return $sites[$id];
    }

    /**
     * Get all sites/branches from Perforce as site models.
     * Makes heavy use of caching as this gets called numerous times per-request.
     *
     * @param   array|null               $options        optional - options to limit results
     *                                                       FETCH_BY_ACL - set to an array containing
     *                                                                      resource and/or privilige
     *                                                      FETCH_BY_SITE - set to site id to limit
     *                                                                      to branches of that site
     *                                                    FETCH_SORT_FLAT - set to sort by stream name
     *                                                                      ignoring hierachy
     * @param   P4_Connection_Interface  $connection     optional - a specific connection to use.
     * @return  P4Cms_Model_Iterator     all sites/branches.
     */
    public static function fetchAll(array $options = null, P4_Connection_Interface $connection = null)
    {
        // read sites from the global cache if possible.
        $sites = P4Cms_Cache::load(static::CACHE_KEY, 'global');
        if (!$sites instanceof P4Cms_Model_Iterator) {

            // failed to read sites out of cache, we need to read sites
            // out of perforce which means we need a connection.
            $connection = $connection ?: P4_Connection::getDefaultConnection();

            // fetch sites by querying streams in Perforce
            // chronicle sites are prefixed with 'chronicle-'
            // to distinguish them from other streams
            $streams = P4_Stream::fetchAll(
                array(
                    P4_Stream::FETCH_BY_PATH  => '//' . static::SITE_PREFIX . '*/*',
                    P4_Stream::SORT_RECURSIVE => true
                ),
                $connection
            );

            // each site will make use of this one connection
            // and in so doing change its client, we remember
            // the client here so we can restore it afterwards.
            $client = $connection->getClient();

            // generate site objects for each stream
            // we preload the site object with the stream, config and acl
            // so that next time the site object is read from cache it will
            // already have these objects set on it.
            $sites = new P4Cms_Model_Iterator;
            foreach ($streams as $stream) {
                $site = new static;
                $site->setId($stream->getId());

                // tell the site to use/customize the given connection
                // this will change the connection's client, but we will
                // set it back to the original value below.
                $site->setConnection($connection);

                // read in the stream by getting values and set it on the site.
                $stream->getValues();
                $site->_setStream($stream);

                // read in all of this site's config information.
                $site->getConfig()->getValues();

                // read in the acl (also primes acl roles).
                $site->getAcl();

                // the connection will be useless after this point as the
                // client will be changed; clear site's reference to it
                $site->setConnection(null);

                // give each site/branch a reference to its parent (if it has one)
                $parent = $site->getStream()->getParent();
                if ($parent && isset($sites[$parent])) {
                    $site->setParent($sites[$parent]);
                }

                // add this site to our list.
                $sites[$site->getId()] = $site;
            }

            // restore connection's original client.
            $connection->setClient($client);

            // sort by title of first mainline for each site
            $sites = static::_sortBySiteTitle($sites);

            // save sites to global cache.
            P4Cms_Cache::save($sites, static::CACHE_KEY, array(), null, null, 'global');
        }

        // normalize our options
        $options = (array) $options + array(
            static::FETCH_BY_SITE   => null,
            static::FETCH_BY_ACL    => null,
            static::FETCH_SORT_FLAT => null
        );

        // filter our result by site if requested
        if ($options[static::FETCH_BY_SITE]) {
            $sites->filterByCallback(
                function($site) use ($options)
                {
                    return $site->getSiteId() == $options[$site::FETCH_BY_SITE];
                }
            );
        }

        // filter by ACL if requested
        $user      = P4Cms_User::hasActive() ? P4Cms_User::fetchActive() : null;
        $acl       = (array) $options[static::FETCH_BY_ACL];
        $resource  = array_shift($acl);
        $privilege = array_shift($acl);
        if ($user && ($resource || $privilege)) {
            $sites->filterByCallback(
                function($site) use ($user, $resource, $privilege)
                {
                    return $user->isAllowed($resource, $privilege, $site->getAcl());
                }
            );
        }

        // re-sort by stream name (ignoring depth) if requested
        if ($options[static::FETCH_SORT_FLAT]) {
            $sites->sortByCallback(
                function($a, $b)
                {
                    return strnatcasecmp($a->getStream()->getName(), $b->getStream()->getName());
                }
            );
        }

        // in order for site getConnection() to work we want to give
        // each site a usable template connection if we can.
        if (!$connection && P4_Connection::hasDefaultConnection()) {
            $connection = P4_Connection::getDefaultConnection();
        }
        $sites->invoke('setTemplateConnection', array($connection));

        return $sites;
    }

    /**
     * Get the first site/branch that matches the request
     *
     * Each site has a list of urls that it will respond to. We begin by finding
     * the first site/branch that matches the request url. If no site matches,
     * we return the first site/branch. If there are no sites, returns false.
     *
     * Additionally, we support a convention of embedding the name of a specific
     * branch in the request url as the first path component (after the base url).
     * Such as: http://example.com/-dev- This permits multiple site branches
     * without configuring DNS and/or the web server with different URLs for each
     * branch.
     *
     * If the request url specifies a particular branch, we fetch and return that
     * site branch. If no such branch exists, an exception is thrown.
     *
     * @param   Zend_Controller_Request_Http    $request    a request to examine to determine
     *                                                      which site/branch to load.
     * @param   array|null                      $limit      optional - a whitelist of site/branch ids
     *                                                      that can be safely exposed, all other sites
     *                                                      will be ignored - null to allow all.
     * @param   P4_Connection_Interface         $connection optional - a specific connection to use.
     * @return  P4Cms_Site|false                the first matching site/branch
     */
    public static function fetchByRequest(
        Zend_Controller_Request_Http $request,
        array $limit = null,
        P4_Connection_Interface $connection = null)
    {
        // compose url from http host and request uri to find site.
        $requestUrl = Zend_Uri_Http::fromString(
            $request->getScheme()
            . '://'
            . $request->getHttpHost()
            . $request->getRequestUri()
        );

        // loop over urls in site/branches to find a matching prefix.
        $found = false;
        $sites = static::fetchAll(null, $connection);
        foreach ($sites as $site) {

            // skip sites that aren't in the limit whitelist.
            if ($limit && !in_array($site->getId(), $limit)) {
                continue;
            }

            // loop over urls served by this site.
            foreach ($site->getConfig()->getUrls() as $url) {
                // trim whitespace to improve our chances of a match
                $url = trim($url);

                // if url has no scheme - assume scheme of request
                if (!preg_match('#[a-z]+://#i', $url)) {
                    $url = $request->getScheme() . '://' . $url;
                }

                // convert url to Zend_Uri_Http object and skip invalid urls
                try {
                    $url = Zend_Uri_Http::fromString($url);
                } catch (Exception $e) {
                    continue;
                }

                // request scheme (protocol) must match.
                if ($url->getScheme() != $requestUrl->getScheme()) {
                    continue;
                }

                // http host must match.
                if ($url->getHost() != $requestUrl->getHost()) {
                    continue;
                }

                // if path specified - request url must start with path.
                if ($url->getPath() && strpos($requestUrl->getPath(), $url->getPath()) !== 0) {
                    continue;
                }

                // still here? site matches!
                $found = $site;
                break 2;
            }
        }

        // if we failed to find a precise match, assume the first site.
        $found = $found ?: $sites->first();

        // if still no site, or no specific branch requested, all done.
        if (!$found
            || !$request instanceof P4Cms_Controller_Request_Http
            || !$request->getBranchName()
        ) {
            return $found;
        }

        // url specifies a particular branch -- if it's not allowed by
        // the whitelist, simply return what we have found so far.
        $branch = '//' . $found->getSiteId() . '/' . $request->getBranchName();
        if ($limit && !in_array($branch, $limit)) {
            return $found;
        }

        // attempt to fetch the specified branch, if that branch cannot
        // be found, fallback to whatever branch we found previously.
        try {
            return static::fetch($branch, $connection);
        } catch (P4Cms_Model_NotFoundException $e) {
            return $found;
        }
    }

    /**
     * Fetch the active (currently loaded) site.
     * Guaranteed to return the active site model or throw an exception.
     *
     * @return  P4Cms_Site              the currently active site.
     * @throws  P4Cms_Site_Exception    if there is no currently active site.
     */
    public static function fetchActive()
    {
        if (!static::$_activeSite || !static::$_activeSite instanceof P4Cms_Site) {
            throw new P4Cms_Site_Exception("There is no active (currently loaded) site.");
        }
        return static::$_activeSite;
    }

    /**
     * Determine if there is an active (currently loaded) site.
     *
     * @return  boolean     true if there is an active site.
     */
    public static function hasActive()
    {
        try {
            static::fetchActive();
            return true;
        } catch (P4Cms_Site_Exception $e) {
            return false;
        }
    }

    /**
     * Clear the active site.
     */
    public static function clearActive()
    {
        static::$_activeSite = null;
    }

    /**
     * Warning: this method changes the client of the given connection.
     * Sets the perforce connection to use for this site and configures
     * it with a new client configured to use this site's stream.
     *
     * @param   P4_Connection_Interface|null    $connection     the connection to use or null.
     * @return  P4Cms_Site                      provides fluent interface.
     */
    public function setConnection(P4_Connection_Interface $connection = null)
    {
        $this->_connection = $connection
            ? $this->_customizeConnection($connection)
            : null;

        // wipe out the storage adapter anytime the connection changes.
        // this ensures that subsequent calls to getStorageAdapter()
        // will get the same connection that we have been handed here.
        $this->_adapter = null;

        // ensure that the stream always uses the same connection as the site.
        // if null was given a connection might be dynamically generated later.
        // we use a deferred connection to ensure the connection stays in sync.
        if ($this->_stream) {
            $this->_stream->setConnection($this->getDeferredConnection());
        }

        // the acl record also needs to be updated. it has an associated
        // p4 file object that needs to be cleared if the connection changes
        // (otherwise, it will have the old connection and related properties).
        // we use a 'deferred' adapter to delay creating a connection (in case
        // null was given) and to ensure the adapter stays in sync with the site.
        if ($this->_acl) {
            $this->_acl->getRecord()->setAdapter($this->getDeferredAdapter());
        }

        return $this;
    }

    /**
     * The connection to use as a template when generating a new connection.
     * If no template is set, the default connection is used.
     *
     * @param   P4_Connection_Interface|null    $connection     the template connection or null.
     * @return  P4Cms_Site                      provides fluent interface.
     */
    public function setTemplateConnection(P4_Connection_Interface $connection = null)
    {
        $this->_templateConnection = $connection;

        return $this;
    }

    /**
     * Check if this site/branch already has a perforce connection.
     * You cannot use getConnection() for this because it will always
     * try to return a connection.
     *
     * @return  bool    true if this site/branch already has a connection.
     */
    public function hasConnection()
    {
        return (bool) $this->_connection;
    }

    /**
     * Get the perforce connection for this site.
     *
     * If no connection has been explicitly set, a new connection will
     * be made using the current template (or the default connection as
     * a template) customized for the site.
     *
     * @return  P4_Connection_Interface     a connection to this site's perforce server.
     * @throws  P4Cms_Site_Exception        if no explicit, template or default connection is set.
     */
    public function getConnection()
    {
        // check for existing connection.
        if ($this->_connection instanceof P4_Connection_Interface) {
            return $this->_connection;
        }

        if (!$this->_templateConnection && !P4_Connection::hasDefaultConnection()) {
            throw new P4Cms_Site_Exception(
                "Cannot get connection. No explicit or default connection set."
            );
        }

        // if we don't have an existing connection create a
        // custom version of the template or default connection
        $template   = $this->_templateConnection ?: P4_Connection::getDefaultConnection();
        $connection = P4_Connection::factory(
            $template->getPort(),
            $template->getUser(),
            $template->getClient(),
            $template->getPassword(),
            $template->getTicket(),
            get_class($template)
        );

        // attempt to login if we don't already have a ticket.
        if (!$connection->getTicket()) {
            $connection->login();
        }

        // set connection will record this connection for future
        // calls and customize it to use the site client
        $this->setConnection($connection);

        return $connection;
    }

    /**
     * Get a 'deferred' connection. This can be used anywhere a regular
     * connection can be used.
     *
     * Getting a deferred connection will not cause the site to create
     * a connection until it is actually used. It will always link to
     * the site's current connection even if it is changed.
     *
     * @return  P4_Connection_Deferred  a connection linked to this site's connection.
     */
    public function getDeferredConnection()
    {
        $site = $this;
        return new P4_Connection_Deferred(
            function() use ($site)
            {
                return $site->getConnection();
            }
        );
    }

    /**
     * Load this site into the environment and set it as the active site.
     *
     * Establish a connection and record adapter for this site and set them
     * as the default. Also, updates package paths to point at site resources.
     *
     * @return  P4Cms_Site  provides fluent interface.
     */
    public function load()
    {
        // ensure paths we need to write to exist and are writable.
        P4Cms_FileUtility::createWritablePath($this->getDataPath());
        P4Cms_FileUtility::createWritablePath($this->getWorkspacesPath());

        // set this site's connection as the default connection for the environment.
        P4_Connection::setDefaultConnection($this->getConnection());

        // set this site's storage adapter as the default.
        P4Cms_Record::setDefaultAdapter($this->getStorageAdapter());

        // add the appropriate themes paths for this site.
        P4Cms_Theme::clearPackagesPaths();
        P4Cms_Theme::addPackagesPath(static::getSitesPackagesPath() . '/all/themes');
        P4Cms_Theme::addPackagesPath($this->getThemesPath());

        // add the appropriate modules paths for this site.
        P4Cms_Module::clearPackagesPaths();
        P4Cms_Module::addPackagesPath(static::getSitesPackagesPath() . '/all/modules');
        P4Cms_Module::addPackagesPath($this->getModulesPath());

        // set this instance as the active site.
        static::$_activeSite = $this;

        return $this;
    }

    /**
     * Get the path to this site's packages folder (not branch specific)
     *
     * @return  string  the path to this site's packages folder.
     */
    public function getPackagesPath()
    {
        return static::getSitesPackagesPath($this->getSiteId());
    }

    /**
     * Get the path to this site branch's data folder.
     *
     * @return string   the path to this site branch's data folder.
     */
    public function getDataPath()
    {
        return static::getSitesDataPath($this->getSiteId())
            . '/' . $this->getBranchBasename();
    }

    /**
     * Get the path to this site's p4 workspaces.
     *
     * @return  string  the path to the site workspaces.
     */
    public function getWorkspacesPath()
    {
        return $this->getDataPath() . '/workspaces';
    }

    /**
     * Get the path to this site's modules.
     *
     * @return  string  the path to this site's modules folder.
     */
    public function getModulesPath()
    {
        return $this->getPackagesPath() . '/modules';
    }

    /**
     * Get the path to this site's themes.
     *
     * @return  string  the path to this site's themes folder.
     */
    public function getThemesPath()
    {
        return $this->getPackagesPath() . '/themes';
    }

    /**
     * Get the path to this site's (writable) public resources.
     *
     * @return  string  the path to the site's (writable) public resources.
     */
    public function getResourcesPath()
    {
        return $this->getDataPath() . '/resources';
    }

    /**
     * Get the storage adapter to use when reading records from
     * and writing records to this site.
     *
     * @return  P4Cms_Record_Adapter    the storage adapter to use for this site branch.
     */
    public function getStorageAdapter()
    {
        if ($this->_adapter) {
            return $this->_adapter;
        }

        // no site adapter prepared, make a new one.
        $adapter = new P4Cms_Record_Adapter;

        // the adapter should use this site branch's connection
        // this will ensure it uses the appropriate stream client
        $adapter->setConnection($this->getConnection());

        // when composing record paths, use client-syntax as the base
        // this will ensure that paths resolve through the view.
        $adapter->setBasePath("//" . $this->getConnection()->getClient());

        // set the name of this site's 'umbrella' group in Perforce.
        // this is the parent group for all site roles and gives its
        // members read/write permission to this site's depot files
        // (it is site global, not branch specific).
        $adapter->setProperty(P4Cms_Acl_Role::PARENT_GROUP,  $this->getSiteId());

        // volatile records need to share a non-temp client to see
        // records because they store them as pending files - pick
        // a client name based on the site-branch id.
        $adapter->setProperty(
            P4Cms_Record_Volatile::CLIENT,
            str_replace('/', '-', trim($this->getId(), '/'))
        );

        // only make the adapter once.
        $this->_adapter = $adapter;

        return $adapter;
    }

    /**
     * Get a 'deferred' storage adapter. This can be used anywhere a regular
     * record adapter can be used.
     *
     * Getting a deferred adapter will not cause the site to create a storage
     * adapter until it is actually used. It will always link to the site's
     * current storage adapter even if it is changed.
     *
     * @return  P4Cms_Record_DeferredAdapter    an adapter linked to this site's adapter.
     */
    public function getDeferredAdapter()
    {
        $site = $this;
        return new P4Cms_Record_DeferredAdapter(
            function() use ($site)
            {
                return $site->getStorageAdapter();
            }
        );
    }

    /**
     * Get the stream object for this site branch.
     *
     * @return  P4_Stream|null  the stream for this site branch or null if we don't have an id.
     */
    public function getStream()
    {
        if ($this->_stream || !$this->getId()) {
            return $this->_stream;
        }

        $this->_stream = P4_Stream::fetch($this->getId(), $this->getConnection());

        return $this->_stream;
    }

    /**
     * Get the configuration object for this site branch.
     *
     * @return  P4Cms_Site_Config               the configuration record for this site branch.
     * @throws  P4Cms_Model_NotFoundException   if an invalid revision is given.
     */
    public function getConfig()
    {
        if (!$this->_config) {
            $this->_config = new P4Cms_Site_Config($this);
        }

        return $this->_config;
    }

    /**
     * Get the ACL for this site.
     *
     * @return  P4Cms_Acl   the acl defined for this site.
     */
    public function getAcl()
    {
        // load acl from storage if we haven't already done so.
        if (!$this->_acl instanceof P4Cms_Acl) {
            $adapter = $this->getStorageAdapter();
            try {
                $acl = P4Cms_Acl::fetch(static::ACL_RECORD_ID, $adapter);
            } catch (P4Cms_Model_NotFoundException $e) {

                // setup record storage for acl.
                $record = new P4Cms_Record;
                $record->setId(static::ACL_RECORD_ID)
                       ->setAdapter($adapter);

                // create new, empty, acl.
                $acl = new P4Cms_Acl;
                $acl->setRecord($record);

            }

            // load roles into acl.
            $acl->setRoles(P4Cms_Acl_Role::fetchAll(null, $adapter));

            $this->_acl = $acl;
        }

        return $this->_acl;
    }

    /**
     * Set a reference to this site/branch's parent branch.
     *
     * @param   P4Cms_Site|null     $parent     a reference to this branch's parent branch
     * @return  P4Cms_Site          provides fluent interface.
     */
    public function setParent(P4Cms_Site $parent = null)
    {
        $this->_parent = $parent;
        return $this;
    }

    /**
     * Get a reference to this site/branch's parent branch (if it has one).
     *
     * @return  P4Cms_Site|null     this branch's parent branch or null if no parent.
     */
    public function getParent()
    {
        return $this->_parent;
    }

    /**
     * Set the path to the sites packages folder.
     * See getSitesPackagesPath for details.
     *
     * @param   string  $path   the path to the sites folder.
     */
    public static function setSitesPackagesPath($path)
    {
        static::$_sitesPackagesPath = rtrim($path, '/');
    }

    /**
     * Get the path to the sites packages folder.
     *
     * This folder contains a sub-folder for each site (plus an all folder)
     * under which theme and module packages reside for each specific site.
     * The 'all' folder contains themes and modules available to all sites.
     *
     * If a site id is given this method will return the path to that specific
     * site's packages folder.
     *
     * @param   string|null     $siteId     optional - the id of a site to get its
     *                                      specific package path
     * @return  string                      the path to the sites folder.
     * @throws  P4Cms_Site_Exception        if the sites path has not been set.
     */
    public static function getSitesPackagesPath($siteId = null)
    {
        if (!strlen(static::$_sitesPackagesPath)) {
            throw new P4Cms_Site_Exception("The sites packages path has not been set.");
        }

        // if no site id given, simply return top-level sites packages path.
        if (!$siteId) {
            return static::$_sitesPackagesPath;
        }

        $validator = new P4Cms_Validate_SiteId;
        if (!$validator->isValid($siteId)) {
            throw new InvalidArgumentException(
                "Cannot get sites packages path. Given site id is malformed."
            );
        }

        // we strip the site id prefix to shorten the path.
        return static::$_sitesPackagesPath . '/' . substr($siteId, strlen(static::SITE_PREFIX));
    }

    /**
     * Set the path to the sites data folder (where sites data is stored).
     *
     * @param   string  $path   the path to the sites data folder.
     */
    public static function setSitesDataPath($path)
    {
        static::$_sitesDataPath = rtrim($path, '/');
    }

    /**
     * Get the path to the sites data folder.
     *
     * This writable folder contains a sub-folder for each site under which
     * site data is stored.
     *
     * If a site id is given this method will return the path to that specific
     * site's data folder.
     *
     * @param   string|null     $siteId     optional - the id of a site to get its
     *                                      specific package path
     * @return  string                      the path to the sites data folder.
     * @throws  P4Cms_Site_Exception        if the sites data path has not been set.
     * @throws  InvalidArgumentException    if an malformed site id is given.
     */
    public static function getSitesDataPath($siteId = null)
    {
        if (!strlen(static::$_sitesDataPath)) {
            throw new P4Cms_Site_Exception("The sites data path has not been set.");
        }

        // if no site id given, simply return top-level sites data path.
        if (!$siteId) {
            return static::$_sitesDataPath;
        }

        $validator = new P4Cms_Validate_SiteId;
        if (!$validator->isValid($siteId)) {
            throw new InvalidArgumentException(
                "Cannot get sites data path. Given site id is malformed."
            );
        }

        // we strip the site id prefix to shorten the path.
        return static::$_sitesDataPath . '/' . substr($siteId, strlen(static::SITE_PREFIX));
    }

    /**
     * Sort sites by the title of the first mainline within each site.
     * Maintains the existing order for the branches within each site.
     *
     * @param   P4Cms_Model_Iterator    $sites  sites already sorted by stream name/depth
     * @return  P4Cms_Model_Iterator    sorted result
     */
    protected static function _sortBySiteTitle(P4Cms_Model_Iterator $sites)
    {
        // create a model for each site which has the mainline's title and
        // holds an iterator of all the site's branches in the correct order
        $bySite = new P4Cms_Model_Iterator;
        foreach ($sites as $site) {
            $siteId = $site->getSiteId();
            if (!isset($bySite[$siteId])) {
                $bySite[$siteId] = new P4Cms_Model;
                $bySite[$siteId]->setValue('Title', $site->getConfig()->getTitle());
                $bySite[$siteId]->branches = new P4Cms_Model_Iterator;
            }
            $bySite[$siteId]->branches[$site->getId()] = $site;
        }

        // sort the sites by the title of the mainline
        $bySite->sortBy('Title', array(P4Cms_Model_Iterator::SORT_NATURAL));

        // glue all of the branches back into a single result now
        // that they are sorted by their associated site's title
        $result = new P4Cms_Model_Iterator;
        foreach ($bySite as $site) {
            $result->merge($site->branches);
        }

        return $result;
    }

    /**
     * Used by fetchAll to set the stream on a new instance.
     *
     * @param   P4_Stream|null  $stream     stream to use for this site
     * @return  P4Cms_Site                  provides fluent interface.
     */
    protected function _setStream(P4_Stream $stream = null)
    {
        $this->_stream = $stream;

        return $this;
    }

    /**
     * Customize the given connection for this site.
     *
     * Creates a new client configured to use this site's stream and
     * configures the given connection to use the new client.
     *
     * @param   P4_Connection_Interface     $connection     the connection to customize
     * @return  P4_Connection_Interface     the customized connection.
     */
    protected function _customizeConnection(P4_Connection_Interface $connection)
    {
        // we cannot customize the connection if we don't have an id (aka a stream id)
        if (!$this->getId()) {
            throw new P4Cms_Site_Exception(
                "Cannot customize connection. No stream id has been set."
            );
        }

        // to avoid problems that result from multiple processes
        // sharing one client (namely race conditions), we generate
        // a temporary client for each request.
        $tempClientId = P4_Client::makeTempId();

        // setup our temp client to use the site's stream.
        $root   = $this->getWorkspacesPath() . "/" . $tempClientId;
        $client = new P4_Client($connection);
        $client->setId($tempClientId)
               ->setStream($this->getId())
               ->setRoot($root);

        // create the client with the values we've setup above, using
        // makeTemp() so that it will be destroyed automatically.
        // provide a custom clean-up callback to delete the workspace folder.
        $cleanup = function($entry, $defaultCallback) use ($root)
        {
            $defaultCallback($entry);
            P4Cms_FileUtility::deleteRecursive($root);
        };
        P4_Client::makeTemp($client->getValues(), $cleanup, $connection);

        // use our newly created client.
        $connection->setClient($tempClientId);

        return $connection;
    }
}
