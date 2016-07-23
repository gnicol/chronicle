<?php
/**
 * Themes and modules are 'packages'. Themes and modules have some
 * shared functionality and this class exists to avoid duplicating code.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
abstract class P4Cms_PackageAbstract extends P4Cms_Model
{
    const               PACKAGE_FILENAME    = 'package.ini';

    protected           $_path              = null;
    protected           $_packageInfo       = null;
    protected           $_dojoModules       = null;
    protected static    $_documentRoot      = null;
    protected static    $_packagesPaths     = array();

    /**
     * Return the name of this package as the id.
     * Needed to satisfy model config parent class.
     *
     * @return  string  the name of this package.
     * @todo    remove this method when class no longer extends model config.
     */
    public function getId()
    {
        return $this->getName();
    }

    /**
     * Fetch a single package by name from the set of packages.
     *
     * @param   string  $name                   the name of the package to fetch.
     * @return  P4Cms_PackageAbstract           the matching package if one exists.
     * @throws  P4Cms_Model_NotFoundException   if the requested package can't be found.
     */
    public static function fetch($name)
    {
        // throw exception if no name given.
        if (!is_string($name) || !$name) {
            throw new InvalidArgumentException(
                "Can't fetch package. No package name given."
            );
        }

        // validate package name - package must exist.
        if (!static::exists($name)) {
            throw new P4Cms_Model_NotFoundException(
               "Invalid package name: '" . $name . "'."
            );
        }

        // return requested package model.
        $packages = static::fetchAll();
        return $packages[strtolower($name)];
    }

    /**
     * Get all packages (of this class type) available to the system.
     *
     * Looks for packages under the paths that have been registered
     * via addPackagesPath().
     *
     * @return  P4Cms_Model_Iterator    all installed packages.
     */
    public static function fetchAll()
    {
        $cacheId = static::_getCacheId();
        $cached  = P4Cms_Cache::load($cacheId);
        if ($cached !== false) {
            return $cached;
        }

        // collect all packages.
        $packages = new P4Cms_Model_Iterator;
        foreach (static::getPackagesPaths() as $packagesPath) {
            if (is_dir($packagesPath)) {
                $directory = new DirectoryIterator($packagesPath);
                foreach ($directory as $entry) {
                    if ($entry->isDir()
                        && !$entry->isDot()
                        && is_file($entry->getPathname() . '/' . static::PACKAGE_FILENAME)
                    ) {
                        $package = new static;
                        $package->setPath($entry->getPathname());

                        // force a populate now if we are caching, to avoid
                        // repeated lazy loading when reading from cache.
                        if (P4Cms_Cache::canCache()) {
                            $package->populate();
                        }

                        $packages[strtolower($package->getName())] = $package;
                    }
                }
            }
        }

        // put packages in sorted order.
        $packages->sortBy('name', array(P4Cms_Model_Iterator::SORT_ALPHA));

        // cache packages.
        P4Cms_Cache::save($packages, $cacheId);

        return $packages;
    }

    /**
     * Clear the cached list of packages.
     *
     * @return  bool    true if the cache entry was cleared; otherwise false.
     */
    public static function clearCache()
    {
        return P4Cms_Cache::remove(static::_getCacheId());
    }

    /**
     * Read in relevant data for this package.
     *
     * Useful when caching packages as it ensures the cached
     * copies are primed with the information we care about.
     *
     * @return  P4Cms_PacakgeAbstract   provides fluent interface.
     */
    public function populate()
    {
        $this->getPackageInfo();

        // get dojo modules to determine which modules the user
        // has access to. we want this cached to avoid querying
        // acl every request, we are able to cache it because we
        // incorporate the user's roles into the cache key.
        $this->getDojoModules();
    }

    /**
     * Determine if a package with the given name exists.
     *
     * @param   string  $name   the name of the package to look for.
     * @return  bool    true if the named package exists.
     */
    public static function exists($name)
    {
        $packages = static::fetchAll();
        if (!isset($packages[strtolower($name)]) || empty($name)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Add a path to the set of paths from which packages (of this
     * class type) can be sourced.
     *
     * The order that packages paths are added is significant.
     * If a package exists in two paths, the path that was added last
     * wins.
     *
     * @param   string  $path   a path that can contain packages.
     */
    public static function addPackagesPath($path)
    {
        if (!in_array($path, static::$_packagesPaths)) {
            static::$_packagesPaths[] = $path;
        }
    }

    /**
     * Get the set of paths from which packages (of this class
     * type) can be sourced.
     *
     * @return  array   the list of paths that can contain packages.
     */
    public static function getPackagesPaths()
    {
        return static::$_packagesPaths;
    }

    /**
     * Set the set of paths from which packages (of this class
     * type) can be sourced.
     *
     * @param array $paths  the list of paths that can contain packages.
     */
    public static function setPackagesPaths($paths)
    {
        // don't do anything if $paths is not an array
        if (!is_array($paths)) {
            return;
        }

        static::$_packagesPaths = $paths;
    }

    /**
     * Clear the set of paths from which packages can be sourced.
     */
    public static function clearPackagesPaths()
    {
        static::$_packagesPaths = array();
    }

    /**
     * Set the full path to the package folder.
     *
     * @param   string  $path           the full path to the package folder.
     * @return  P4Cms_PackageAbstract   provides fluent interface.
     */
    public function setPath($path)
    {
        // ensure given path is a string.
        if (!is_string($path) || !$path) {
            throw new InvalidArgumentException("Cannot set path. Path is not a string.");
        }

        // ensure path exists.
        if (!is_dir($path)) {
            throw new P4Cms_Package_Exception(
                "Cannot set package path. Path does not exist."
            );
        }

        // set the path in the instance.
        $this->_path = rtrim($path, '/');

        return $this;
    }

    /**
     * Get the path to this package.
     *
     * @return  string                  the path to this package.
     * @throws  P4Cms_PackageException  if the path has not been set.
     */
    public function getPath()
    {
        if ($this->_path === null) {
            throw new P4Cms_Package_Exception("Cannot get path. Path has not been set.");
        }

        return $this->_path;
    }

    /**
     * Get the name of this package.
     * The name is dervied from the basename of the path.
     *
     * @return  string  the name of this package.
     */
    public function getName()
    {
        return basename($this->getPath());
    }

    /**
     * Get the package configuration by parsing the package file.
     *
     * @param   string  $key    optional - the name of a specific value to get,
     *                          null if no such key exists.
     * @return  array   an array containing the package definition.
     */
    public function getPackageInfo($key = null)
    {
        // parse package info file into array - if we haven't already
        $info = $this->_packageInfo;
        if ($info === null) {
            $packageFile = $this->getPath() . '/' . static::PACKAGE_FILENAME;
            if (is_readable($packageFile)) {
                try {
                    $config = new Zend_Config_Ini($packageFile);
                    $info   = $config->toArray();
                } catch (Zend_Config_Exception $e) {
                    P4Cms_Log::logException("Unable to read package information.", $e);
                }
            }
            $this->_packageInfo = isset($info) ? $info : array();
        }

        // if caller gave a key, return value at key, or null if no such key.
        if ($key) {
            return isset($info[$key]) ? $info[$key] : null;
        }

        return $info;
    }

    /**
     * Get a friendly label for this package. The value is taken from the
     * 'label' field of the package falling back to 'title' as an alternate
     * storage location and lastly running a 'ucfirst' on name.
     *
     * @return null|string  a friendly label for this package
     */
    public function getLabel()
    {
        $info  = $this->getPackageInfo() + array('label' => '', 'title' => '');
        $label = $info['label'] ?: $info['title'];
        return $label ?: ucfirst($this->getName());
    }

    /**
     * Get the description of this package from the package info file.
     *
     * @return null|string  the description of this package if it has one.
     */
    public function getDescription()
    {
        $info = $this->getPackageInfo();
        return isset($info['description']) ? (string) $info['description'] : null;
    }

    /**
     * Get the version of this package from the package info file.
     *
     * @return null|string  the version of this package if it has one.
     */
    public function getVersion()
    {
        $info = $this->getPackageInfo();
        return isset($info['version']) ? (string) $info['version'] : null;
    }

    /**
     * Determine if there is an icon for this package.
     *
     * @return  bool    true if this package has an icon.
     */
    public function hasIcon()
    {
        $info = $this->getPackageInfo();
        return isset($info['icon']) && is_string($info['icon']);
    }

    /**
     * Get the URI to the package icon file.
     *
     * @return  string                      the URI of the package icon.
     * @throws  P4Cms_Package_Exception     if there is no icon.
     */
    public function getIconUrl()
    {
        if (!$this->hasIcon()) {
            throw new P4Cms_Package_Exception(
                "Cannot get icon URI. This package has no icon."
            );
        }

        $info = $this->getPackageInfo();
        $uri  = $info['icon'];

        return (P4Cms_Uri::isRelativeUri($uri)) ? $this->getBaseUrl() . '/' . $uri : $uri;
    }

    /**
     * Get information about the maintainer of this package if available.
     * For example: name, email and url.
     *
     * @param   string  $field      optional - the name of a specific maintainer
     *                              field to get (e.g. name, email, url).
     * @return  array|string|null   array of all maintainer information, or a specific
     *                              field, or null if no maintainer info.
     */
    public function getMaintainerInfo($field = null)
    {
        $info = $this->getPackageInfo();
        if ($field) {
            return isset($info['maintainer'][$field]) ? $info['maintainer'][$field] : null;
        } else {
            return isset($info['maintainer']) && is_array($info['maintainer'])
                ? $info['maintainer'] : null;
        }
    }

    /**
     * Get the url to this package folder.
     *
     * @return  string  the base url of this package.
     */
    public function getBaseUrl()
    {
        // can't produce base url if the package is not under the public path.
        if (strpos($this->getPath(), static::getDocumentRoot()) !== 0) {
            throw new P4Cms_Package_Exception(
                "Cannot get package base url. Package is not under the public path."
            );
        }

        $request = Zend_Controller_Front::getInstance()->getRequest();
        if ($request instanceof Zend_Controller_Request_Http) {
            $baseUrl = $request->getBaseUrl();
        } else {
            $baseUrl = null;
        }

        $baseUrl = $baseUrl . "/" . str_replace(
            static::getDocumentRoot() . '/',
            '',
            $this->getPath()
        );

        // On Windows, getPath() returns a path containing backslashes.
        // Replace backslashes with the forward slashes.
        return str_replace('\\', '/', $baseUrl);
    }

    /**
     * Get meta listed in the package file in a format suitable for
     * passing to Zend's headMeta helper.
     *
     * Only supports arrays that include a key, so charset[] is not supported
     *
     * @return  array   associative array of meta included by this package.
    */
    public function getHtmlMeta()
    {
        // ensure metas is an array
        $info = $this->getPackageInfo();
        if (!isset($info['meta']) || !is_array($info['meta'])) {
            return array();
        }

        // build set of valid meta fields
        $meta   = array();
        $types  = $info['meta'];

        foreach ($types as $type => $fields) {
            foreach ($fields as $field => $content) {
                // content must be string with length.
                if (!is_string($content) || !strlen($content)) {
                    continue;
                }

                $meta[] = array(
                    'type'      => $type,
                    'field'     => $field,
                    'content'   => $content
                );
            }
        }

        return $meta;
    }

    /**
     * Get stylesheets listed in the package file in a format suitable for
     * passing to Zend's headLink helper.
     *
     * The package file groups stylesheets by media type for aesthetic reasons.
     * Here we flatten the list to make it easier to work with.
     *
     * @return  array   associative array of stylesheets included by this package.
     */
    public function getStylesheets()
    {
        // ensure stylesheets is an array.
        $info = $this->getPackageInfo();
        if (!isset($info['stylesheets']) || !is_array($info['stylesheets'])) {
            return array();
        }

        // build set of valid stylesheets.
        $styles = array();
        $groups = $info['stylesheets'];
        foreach ($groups as $name => $group) {
            // set media to 'all' if it's not provided or empty
            if (isset($group['media'])) {
                $media   = implode(', ', (array) $group['media']);
            }
            if (!isset($media) || !trim($media)) {
                $media   = 'all';
            }

            // conditional stylesheet
            $conditional = isset($group['condition']) && is_string($group['condition'])
                         ? $group['condition'] : '';

            // skip the stylesheet if no url set
            if (!isset($group['href'])) {
                continue;
            }

            // nomalize the hrefs to an array
            foreach ((array) $group['href'] as $url) {
                // url must be string with length.
                if (!is_string($url) || !strlen($url)) {
                    continue;
                }

                // make url relative to package baseUrl.
                if (P4Cms_Uri::isRelativeUri($url)) {
                    $url = $this->getBaseUrl() . '/' . $url;
                }

                // add to styles list.
                $style = array(
                    'href'        => $url,
                    'media'       => $media,
                    'conditional' => $conditional
                );
                $styles[] = $style;
            }
        }

        return $styles;
    }

    /**
     * Get tags listed in the package file.
     *
     * @return  array  The list of tags included in this package; the array could be empty.
     */
    public function getTags()
    {
        $info = $this->getPackageInfo();
        $tags = isset($info['tags']) ? preg_split('/,|\s/', $info['tags']) : array();
        $tags = array_filter(array_map('trim', $tags));

        return $tags;
    }

    /**
     * Get scripts listed in the package file in a format suitable for
     * passing to the headScript helper.
     *
     * The package file groups scripts by type for aesthetic reasons.
     * Here we flatten the list to make it easier to work with.
     *
     * @return  array   associative array of scripts included by this package.
     */
    public function getScripts()
    {
        // ensure scripts is an array.
        $info = $this->getPackageInfo();
        if (!isset($info['scripts']) || !is_array($info['scripts'])) {
            return array();
        }

        // build set of valid scripts.
        $scripts = array();
        $types   = $info['scripts'];
        foreach ($types as $type => $urls) {
            foreach ($urls as $url) {

                // url must be string with length.
                if (!is_string($url) || !strlen($url)) {
                    continue;
                }

                // make url relative to package baseUrl.
                if (P4Cms_Uri::isRelativeUri($url)) {
                    $url = $this->getBaseUrl() . '/' . $url;
                }

                // add to scripts list.
                $script = array(
                    'src'   => $url,
                    'type'  => "text/" . $type,
                    'attrs' => array()
                );
                $scripts[] = $script;
            }
        }

        return $scripts;
    }

    /**
     * Get all dojo modules that are defined by this module
     *
     * @return  array   a list of dojo modules
     */
    public function getDojoModules()
    {
        $info = $this->getPackageInfo();
        if (!isset($info['dojo']) || !is_array($info['dojo'])) {
            return array();
        }

        // if we already have a cached set return it.
        // note: we cache mainly for the acl checks.
        if ($this->_dojoModules) {
            return $this->_dojoModules;
        }

        $modules = array();
        $groups  = $info['dojo'];
        foreach ($groups as $name => $group) {
            if ($name === 'addOnLoad') {
                continue;
            }

            // path must be string with length.
            if (!isset($group['path']) || !is_string($group['path']) || !strlen($group['path'])) {
                continue;
            }

            // make path relative to package baseUrl.
            $path = $group['path'];
            if (P4Cms_Uri::isRelativeUri($path)) {
                $path = $this->getBaseUrl() . '/' . $path;
            }

            // dojo modules can be limited by acl. this is intended to avoid
            // loading modules for features that the user can't access anyway.
            // acl limits be must declared as a list of resources with each
            // resource having a list of privileges (may be comma delimited).
            $acl = array();
            if (isset($group['acl']) && is_array($group['acl'])) {
                foreach ($group['acl'] as $resource => $privileges) {
                    $privileges = is_array($privileges)
                         ? $privileges
                         : explode(",", $privileges);
                    $acl[$resource] = array_filter($privileges, 'trim');
                }
            }

            $module = array(
                'namespace' => $group['namespace'],
                'path'      => $path,
                'allowed'   => $this->_passesAcl($acl)
            );

            $modules[] = $module;
        }

        $this->_dojoModules = $modules;
        return $modules;
    }

    /**
     * Get dojo 'addOnLoad' entries for this package.
     *
     * @return  array   a list of addOnLoad scripts
     */
    public function getDojoOnLoads()
    {
        $info = $this->getPackageInfo();
        if (!isset($info['dojo']['addOnLoad'])
            || !is_array($info['dojo']['addOnLoad'])
        ) {
            return array();
        }

        return $info['dojo']['addOnLoad'];
    }

    /**
     * Get current view object from the view renderer.
     *
     * @return Zend_View_Interface  the current view object.
     */
    public static function getView()
    {
        $renderer = static::getViewRenderer();
        if (!$renderer->view) {
            $renderer->initView();
        }
        return $renderer->view;
    }

    /**
     * Get the P4CMS (theme-aware) view renderer - load it if necessary.
     *
     * @return P4Cms_Controller_Action_Helper_ViewRenderer the view renderer.
     */
    public static function getViewRenderer()
    {
        $renderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        if (!$renderer instanceof P4Cms_Controller_Action_Helper_ViewRenderer) {
            $renderer = new P4Cms_Controller_Action_Helper_ViewRenderer;
            Zend_Controller_Action_HelperBroker::addHelper($renderer);
        }
        return $renderer;
    }

    /**
     * Set the file-system path to the document root.
     *
     * @param   string  $path   the location of the public folder.
     */
    public static function setDocumentRoot($path)
    {
        static::$_documentRoot = rtrim($path, '/');
    }

    /**
     * Get the file-system path to the document root.
     *
     * @return  string  the location of the public folder.
     * @throws  P4Cms_Package_Exception     if the doc root has not been set.
     */
    public static function getDocumentRoot()
    {
        if (!strlen(static::$_documentRoot)) {
            throw new P4Cms_Package_Exception(
                "Cannot get document root. The document root has not been set."
            );
        }

        return static::$_documentRoot;
    }

    /**
     * Get any menus configured for this module.
     *
     * @return  array   list of menus from module.ini.
     */
    public function getMenus()
    {
        $info = $this->getPackageInfo();
        return isset($info['menus']) && is_array($info['menus']) ? $info['menus'] : array();
    }

    /**
     * Get the widget configuration defined by the package (grouped by region).
     *
     * @return  array   a list of regions and default widget configuration for those regions.
     */
    public function getWidgetConfig()
    {
        $info = $this->getPackageInfo();
        $widgets = array();
        if (isset($info['regions']) && is_array($info['regions'])) {
            $widgets = $info['regions'];
        }
        return $widgets;
    }

    /**
     * Get cache id for this class constructed from the called class name and
     * a serialized set of packages source paths. We also include the user's
     * roles as the applicable dojo modules depend on the user's permissions.
     */
    protected static function _getCacheId()
    {
        $packagesPaths = array_unique(static::getPackagesPaths());
        sort($packagesPaths);

        $roles = P4Cms_User::hasActive()
            ? P4Cms_User::fetchActive()->getRoles()->invoke('getId')
            : array();

        return get_called_class() . md5(serialize(array($packagesPaths, $roles)));
    }

    /**
     * Load the meta tags in this package into the view headMeta helper
     */
    protected function _loadHtmlMeta()
    {
        $view = $this->getView();
        foreach ($this->getHtmlMeta() as $meta) {
            switch ($meta['type']) {
                case 'httpEquiv':
                    $view->headMeta()->setHttpEquiv($meta['field'], $meta['content']);
                    break;
                case 'name':
                    $view->headMeta()->setName($meta['field'], $meta['content']);
                    break;
            }
        }
    }

    /**
     * Load the stylesheets in this package into the view headLink helper.
     */
    protected function _loadStylesheets()
    {
        $view = $this->getView();
        foreach ($this->getStylesheets() as $stylesheet) {
            $view->headLink()->appendStylesheet(
                $stylesheet['href'],
                $stylesheet['media'],
                $stylesheet['conditional'],
                array('buildGroup' => 'packages')
            );
        }
    }

    /**
     * Load the scripts in this package into the view headScript helper.
     */
    protected function _loadScripts()
    {
        $view = $this->getView();
        foreach ($this->getScripts() as $script) {
            $view->headScript()->appendFile($script['src'], $script['type'], $script['attrs']);
        }
    }

    /**
     * Takes care of the 'dojo' section of package config including
     * requires, provides and onLoad.
     */
    protected function _loadDojo()
    {
        // enable dojo view helper.
        $view = $this->getView();
        Zend_Dojo::enableView($view);

        // load defined dojo modules
        $dojoModules = $this->getDojoModules();
        foreach ($dojoModules as $module) {
            // always register every module path
            $view->dojo()->registerModulePath($module['namespace'], $module['path']);

            // require modules that pass acl
            if ($module['allowed']) {
                $view->dojo()->requireModule($module['namespace']);
            }
        }

        // deal with addOnLoad
        foreach ($this->getDojoOnLoads() as $onLoad) {
            $view->dojo()->addOnLoad($onLoad);
        }
    }

    /**
     * Checks whether a dojo module should be loaded based on the resource privileges
     * If any resource or privilege matches, the user needs this dojo module.
     *
     * @param   array   $acl    list of resources as keys with privileges as values
     * @return  bool    true if dojo module's resources/privilege are allowed by
     *                  the current user; false otherwise.
     *
     */
    protected function _passesAcl($acl)
    {
        // if item has no acl resource, nothing to check.
        if (empty($acl)) {
            return true;
        }

        // if no active user, can't check acl - assume the worst.
        if (!P4Cms_User::hasActive()) {
            return false;
        }

        // match any of the resource privileges
        foreach ($acl as $resource => $privileges) {
            foreach ($privileges as $privilege) {
                if (P4Cms_User::fetchActive()->isAllowed($resource, $privilege)) {
                    return true;
                }
            }
        }

        return false;
    }
}
