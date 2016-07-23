<?php
/**
 * Derivative of dojo container helper that provides control over
 * which dojo components are rendered.
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        support dojo.requireLocalization().
 * @todo        minify js.
 * @todo        "intern" external non-js files (namely dijit templates).
 * @todo        add support for build groups and produce a separate build
 *              file for each group. this will increase re-use of builds across
 *              requests and allow for a smaller build for anonymous users.
 * @todo        Make registerDijitLoader() safe to call multiple times per
 *              page. Presently, running this multiple times will result in
 *              the last declared set of 'zendDijits' to be the only declared
 *              set - additionally, they will be parsed multiple times.
 *              Possible fix is to extend registerDijitLoader() to include
 *              the 'var zendDijits' scoped inside the dijit loader addOnLoad
 *              function.
 */
class P4Cms_View_Helper_Dojo_Container extends Zend_Dojo_View_Helper_Dojo_Container
{
    protected   $_build         = false;
    protected   $_assetHandler  = null;
    protected   $_built         = false;
    protected   $_buildUri      = null;
    protected   $_documentRoot  = null;
    protected   $_locale        = null;
    protected   $_render        = array(
        'config'        => true,
        'scriptTag'     => true,
        'extras'        => true,
        'layers'        => true,
        'stylesheets'   => true
    );

    /**
     * Enable or disable automatic dojo builds.
     *
     * @param   bool    $build  set to true to enable, false to disable.
     * @return  P4Cms_View_Helper_Dojo_Container    provides fluent interface.
     */
    public function setAutoBuild($build)
    {
        $this->_build = (bool) $build;
        return $this;
    }

    /**
     * Set the file-system path to the document root.
     *
     * @param   string  $path                       the location of the public folder.
     * @return  P4Cms_View_Helper_Dojo_Container    provides fluent interface.
     */
    public function setDocumentRoot($path)
    {
        $this->_documentRoot = rtrim($path, '/\\');
        return $this;
    }

    /**
     * Get the file-system path to the document root.
     *
     * @return  string  the location of the public folder.
     */
    public function getDocumentRoot()
    {
        return $this->_documentRoot;
    }

    /**
     * Set the asset handler used to store the aggregated dojo file.
     *
     * @param   P4Cms_AssetHandlerInterface|null    $handler    The handler to use or null
     * @return  P4Cms_View_Helper_Dojo_Container    provides fluent interface.
     */
    public function setAssetHandler(P4Cms_AssetHandlerInterface $handler = null)
    {
        $this->_assetHandler = $handler;

        return $this;
    }

    /**
     * Get the asset handler used to store the aggregated dojo file.
     *
     * @return  P4Cms_AssetHandlerInterface|null    the handler to use or null
     */
    public function getAssetHandler()
    {
        return $this->_assetHandler;
    }

    /**
     * Set which dojo elements you want to be rendered.
     *
     * @param array $elements  list of dojo elements to render (e.g. array('extras', 'layers')).
     *                         valid elements are:
     *                           - config
     *                           - scriptTag
     *                           - extras
     *                           - layers
     *                           - stylesheets
     * @return  P4Cms_View_Helper_Dojo_Container    provides fluent interface.
     */
    public function setRender($elements)
    {
        foreach ($this->_render as $element => $render) {
            if (in_array($element, $elements)) {
                $this->_render[$element] = true;
            } else {
                $this->_render[$element] = false;
            }
        }

        return $this;
    }

    /**
     * Clear all onLoad functions.
     *
     * @return P4Cms_View_Helper_Dojo_Container
     */
    public function clearOnLoad()
    {
        $this->_onLoadActions = array();
        return $this;
    }

    /**
     * Clear all 'zend' onLoad functions.
     *
     * @return P4Cms_View_Helper_Dojo_Container
     */
    public function clearZendLoad()
    {
        $this->_zendLoadActions = array();
        return $this;
    }

    /**
     * Clear all registered modules.
     *
     * @return P4Cms_View_Helper_Dojo_Container
     */
    public function clearModules()
    {
        $this->_modules = array();
        return $this;
    }

    /**
     * Clear all module paths.
     *
     * @return P4Cms_View_Helper_Dojo_Container
     */
    public function clearModulePaths()
    {
        $this->_modulePaths = array();
        return $this;
    }

    /**
     * Don't render config if disabled and ensure the baseUrl 
     * gets set if we have a dojo build (as indicated by buildUri)
     *
     * @return  string  rendered dojo config.
     */
    protected function _renderDjConfig()
    {
        if (!$this->_render['config']) {
            return;
        }
        
        // we need to explicitly set the baseUrl for builds because dojo 
        // won't be able to infer it correctly from the build location.
        if ($this->_buildUri) {
            $this->setDjConfigOption('baseUrl', dirname($this->getLocalPath()) . '/');
        }

        return parent::_renderDjConfig();
    }

    /**
     * Don't render dojo script tag if disabled or if we
     * have a dojo build (as indicated by buildUri)
     *
     * @return  string  rendered dojo script tag.
     */
    protected function _renderDojoScriptTag()
    {
        if ($this->_buildUri || !$this->_render['scriptTag']) {
            return;
        }

        return parent::_renderDojoScriptTag();
    }

    /**
     * Include dojo build in extras.
     * Don't render extras if disabled.
     *
     * @return  string  rendered dojo extras.
     */
    protected function _renderExtras()
    {
        if (!$this->_render['extras']) {
            return;
        }

        // if no build, just return parent.
        if (!$this->_buildUri) {
            return parent::_renderExtras();
        }

        // output script tag for build file - then clear it so
        // it won't be output twice if the view helper runs again.
        $html = '<script type="text/javascript" src="'
              .  $this->_buildUri . '"></script>';
        $this->_buildUri = null;

        // clear module paths so they aren't rendered twice.
        // (they are included in the dojo build file)
        $this->clearModulePaths();
        
        // add on parent's extras
        $html .= parent::_renderExtras();

        return $html;
    }

    /**
     * Don't render layers if disabled.
     *
     * @return  string  rendered dojo layers.
     */
    protected function _renderLayers()
    {
        if (!$this->_render['layers']) {
            return;
        }

        return parent::_renderLayers();
    }

    /**
     * Don't render stylesheets if disabled.
     *
     * @return  string  rendered dojo stylesheets.
     */
    protected function _renderStylesheets()
    {
        if (!$this->_render['stylesheets']) {
            return;
        }

        return parent::_renderStylesheets();
    }

    /**
     * Extend toString to perform auto build if enabled.
     */
    public function  __toString()
    {
        if ($this->_build && !$this->_built) {
            $this->_buildDojo();
        }

        return parent::__toString();
    }

    /**
     * Generate a single dojo javascript file containing:
     * 
     *  - the dojo base
     *  - module path registration
     *  - all of the the required modules 
     *    (recursively building dependencies)
     * 
     * Re-builds whenever a top-level module changes. 
     * Does not detect changes in indirectly required modules.
     */
    protected function _buildDojo()
    {
        // bail out if asset handler is unset.
        if (!$this->getAssetHandler()) {
            P4Cms_Log::log(
                "Failed to build dojo. Asset handler is unset.",
                P4Cms_Log::ERR
            );
            return;
        }

        // bail out if document root is unset.
        if (!$this->getDocumentRoot()) {
            P4Cms_Log::log(
                "Failed to build dojo. Document root is unset.",
                P4Cms_Log::ERR
            );
            return;
        }
        
        // collect module file info.
        $latest  = 0;
        $files   = array();
        $ignored = array();
        foreach ($this->getModules() as $module) {
            $file = $this->_moduleToFilename($module);
            if (!file_exists($file)) {
                $ignored[] = $module;
                continue;
            }

            $time    = filemtime($file);
            $files[] = $file;
            $latest  = $time > $latest ? $time : $latest;
        }

        // if we have no files, nothing to build.
        if (empty($files)) {
            return;
        }

        // determine if compression should be enabled
        $compressed = $this->_canGzipCompress() && $this->_clientAcceptsGzip();

        // generate build filename.
        // combine the list of required modules with the client's locale and
        // the latest mod time so any changes will produce a different build.
        $buildFile = 'dojo-' . md5(implode(',', $files) . $this->getLocale() . $latest) 
                   . ($compressed ? '.jsgz' : '.js');

        // if build file doesn't exist, (re)build.
        if (!$this->getAssetHandler()->exists($buildFile)) {
            $built = array();

            // start with dojo base
            $base = $this->_buildModule("dojo", $built);

            // follow-up dojo base immediately with module path registration.
            // must come before optional module build because a module could
            // dojo.require a registered module that we are unable to build.
            foreach ($this->getModulePaths() as $module => $path) {
                $base .= 'dojo.registerModulePath("' 
                       .  $this->view->escape($module) .  '", "'
                       .  $this->view->escape($path) . '");';
            }

            // now build each of the optional modules that have been required.
            $modules = "";
            foreach ($this->getModules() as $module) {
                $modules .= $this->_buildModule($module, $built);
            }

            // make module build conditional so it only runs once.
            $modules = $this->_makeConditional($modules, basename($buildFile));

            // combine the dojo base build with the optional modules build.
            $build = $base . $modules;

            // also compress if possible.
            if ($compressed) {
                $build = gzencode($build, 9);
            }

            // write out build file, on failure; skip aggregation.
            if (!$this->getAssetHandler()->put($buildFile, $build)) {
                return;
            }
        }

        // only keep required modules that we couldn't build.
        $this->_modules = $ignored;

        // set the build src link.
        $request         = Zend_Controller_Front::getInstance()->getRequest();
        $this->_buildUri = $this->getAssetHandler()->uri($buildFile);

        $this->_built = true;
    }

    /**
     * Recursive function to build a module and all of its dependencies.
     * Works by expanding dojo.require statements into the contents of
     * the named module.
     *
     * Note this method is public so that we can call it from an anonymous
     * function. Consider it protected.
     *
     * @param   string  $module     the name of the module to build.
     * @param   array   &$built     optional - by reference - list of modules already built.
     * @return  string  the resulting js.
     */
    public function _buildModule($module, array &$built = array())
    {
        // early exit if file does not exist.
        $file = $this->_moduleToFilename($module);
        if (!is_file($file)) {
            return false;
        }

        // prevent infinite recursion.
        if (!array_key_exists($module, $built)) {
            $built[$module] = true;
        } else {
            return;
        }

        // read out file
        $build = file_get_contents($file);

        // make an alias to 'this' for the benefit of the anonymous functions
        // php 5.3 does not permit the use of 'this' inside closures
        $self = $this;
        
        // locate and replace dojo.requireLocalization() statements
        // with the appropriate translation package data
        $build = preg_replace_callback(
            '/d(?:ojo)?.requireLocalization\(([^)]+)\)\s*;?/i',
            function($match) use (&$built, $self)
            {
                // extract arguments from require localization call using
                // the str_getcsv function because it knows how to parse 
                // comma-delimited quoted strings.
                $args = str_getcsv($match[1]);
                
                // ensure requireLocalization was called with both module 
                // and bundle args or we won't know what to do with it.
                if (!isset($args[0], $args[1])) {
                    return $match[0];
                }
                
                $module  = $args[0];
                $bundle  = $args[1];
                $package = $module . '.nls.' . $bundle;
        
                // only build localization packages once.
                if (!array_key_exists($package, $built)) {
                    $built[$package] = true;
                } else {
                    return $match[0];
                }

                // find the best available localization package for the 
                // client's locale - nothing to do if we can't find one.
                $file = $self->getBestLocaleFile($module, $bundle);
                if (!$file) {
                    return $match[0];
                }

                // add the localization package to the dojo build.
                // this amounts to creating an 'nls' object named for the 
                // package with three elements: one for the exact locale, 
                // one for the language and one for 'ROOT' - this nls object
                // and each of its elements are then dojo.require()'d to 
                // register them as 'loadedModules' in dojo - note, we embed 
                // this all as an anonymous function that calls itself to 
                // provide local scope for the data variable.
                $locale = $self->getLocale();
                $lang   = reset(explode('_', $locale));
                $data   = file_get_contents($file);
                $js     = '(function(){'
                        . 'var data = ' . $data . ';'
                        . 'dojo.getObject("' . $package . '", true);'
                        . $package . ' = {'
                        . $locale . ': data,'
                        . $lang . ': data,'
                        . 'ROOT: data};'
                        . 'dojo.provide("' . $package . '");'
                        . 'dojo.provide("' . $package . '.' . $locale . '");'
                        . 'dojo.provide("' . $package . '.' . $lang . '");'
                        . 'dojo.provide("' . $package . '.ROOT");'
                        . '})();';
                        
                return $js . $match[0];
            },
            $build
        );

        // some dojo components (datagrid, I'm looking at you!) use the 
        // protected '_preloadLocalizations' method to get i18n packages.
        // we want to resolve these as well to save the http request.
        $build = preg_replace_callback(
            '/dojo.i18n._preloadLocalizations\([\'"]?([^\'")]+)[\'"]?[^)]*\)\s*;?/i',
            function($match) use (&$built, $self)
            {
                // only preload a given localization package once.
                $package = $match[1];
                if (!array_key_exists($package, $built)) {
                    $built[$package] = true;
                } else {
                    return $match[0];
                }

                // find the best available preload localization package for
                // the client's locale - nothing to do if we can't find one.
                $file = $self->getBestPreloadLocaleFile($package);
                if (!$file) {
                    return $match[0];
                }
                
                // add the preload localization file to the build
                // nothing special required, just insert the contents.
                return file_get_contents($file) . $match[0];
            },
            $build
        );
        
        // replace dojo.require() statements with
        // the dependencies they name where possible.
        // note: also recognizes and expands d.require().
        $build = preg_replace_callback(
            '/d(?:ojo)?.require\([\'"]?([^\'")]+)[\'"]?\)\s*;?/i',
            function($match) use (&$built, $self)
            {
                $build = $self->_buildModule($match[1], $built);
                if ($build !== false) {
                    return $build;
                }

                // could not satisfy dependency - keep require.
                return $match[0];
            },
            $build
        );
            
        // wrap module build in resource check.
        $build = $this->_makeConditional($build, $module);

        return $build;
    }
    
    /**
     * Get the client's (browser) locale - normalized to lower case 
     * because that is how dojo likes it.
     *
     * @param  bool    $hyphenate   optional - use hyphen instead of underscore to separate
     *                              the language from the territory (defaults to false).
     * @return string  the client's locale string.
     */
    public function getLocale($hyphenate = false)
    {
        if (!$this->_locale) {
            $this->_locale = strtolower(new Zend_Locale);
        }
        
        return $hyphenate 
            ? str_replace('_', '-', $this->_locale) 
            : $this->_locale;
    }

    /**
     * Finds the closest matching package for the client's locale.
     * Searches for the exact locale, then language, then 'root'.
     * For example, if the locale is 'en-us', looks for:
     *
     *  <module>/nls/en-us/<bundle>.js
     *  <module>/nls/en/<bundle>.js
     *  <module>/nls/<bundle>.js
     * 
     * @param  string  $module  the name of the dojo module to get a localization 
     *                          package for (e.g. 'dijit')
     * @param  string  $bundle  the name of the locale bundle to get (e.g. 'common')
     * @return string  the filename of the best matching localizaton package
     */
    public function getBestLocaleFile($module, $bundle)
    {
        $locale   = $this->getLocale(true);
        $path     = substr($this->_moduleToFilename($module), 0, -3) . '/nls/';
        $attempts = array($locale . '/', reset(explode('-', $locale)) . '/', '');
        
        foreach ($attempts as $attempt) {
            $file = $path . $attempt . $bundle . '.js';
            if (is_readable($file)) {
                return $file;
            }
        }
        
        return false;
    }

    /**
     * Finds the best preloadable locale file for the client's locale.
     * Searches for the exact locale, then language, then 'root'.
     * For example, if the locale is 'en-us', looks for:
     *
     *  <module>_en-us.js
     *  <module>_en.js
     *  <module>_ROOT.js
     * 
     * @param  string  $package     the name of the preloadable locale package
     *                              (e.g. 'dojox.grid.nls.DataGrid')
     * @return string  the filename of the best matching localizaton package
     */
    public function getBestPreloadLocaleFile($package)
    {
        $locale   = $this->getLocale(true);
        $path     = substr($this->_moduleToFilename($package), 0, -3);
        $attempts = array($locale, reset(explode('-', $locale)), 'ROOT');
        
        foreach ($attempts as $attempt) {
            $file = $path . "_" . $attempt . '.js';
            if (is_readable($file)) {
                return $file;
            }
        }
        
        return false;
    }
    
    /**
     * Attempt to determine the local filename for a given dojo module.
     *
     * @param   string  $module     the name of the dojo module to get the filename for.
     * @return  string  the likely filename of the module.
     */
    protected function _moduleToFilename($module)
    {
        $paths = $this->getModulePaths();

        // special handling for 'dojo' base.
        if ($module === 'dojo') {
            return $this->getDocumentRoot() . $this->getLocalPath();
        }
        
        // add dojo paths.
        $basePath       = dirname(dirname($this->getLocalPath()));
        $paths['dojo']  = $basePath . '/dojo';
        $paths['dijit'] = $basePath . '/dijit';
        $paths['dojox'] = $basePath . '/dojox';
        
        // find path to module.
        $path  = null;
        $parts = explode(".", $module);
        $extra = array();
        while ($parts && !$path) {
            $key = implode(".", $parts);
            if (isset($paths[$key])) {
                $path = $paths[$key];
            } else {
                array_unshift($extra, array_pop($parts));
            }
        }

        if (!$path) {
            return null;
        }

        $file = $this->getDocumentRoot() . $path
              . (count($extra) ? "/" . implode("/", $extra) : "")
              . ".js";
        return $file;
    }

    /**
     * Wrap the given js in a hasResource check unless it already has one.
     *
     * @param   string  $js         the js to wrap
     * @param   string  $module     the originating module.
     * @return  string  the conditional js.
     */
    protected function _makeConditional($js, $module)
    {
        // special handling for the dojo base.
        // make base conditional on dojo being undefined.
        if ($module == 'dojo') {
            return "\nif (typeof dojo === 'undefined') {" . $js . "\n}";
        }
        
        // if already conditional, do nothing (already compiled)
        $pattern = '/dojo._hasResource\[[\'"]' . $module . '[\'"]\]/';
        if (preg_match($pattern, $js)) {
            return $js;
        }

        // wrap in has resource conditional and return.
        return "\nif(!dojo._hasResource['$module']){"
             . "dojo._hasResource['$module']=true;"
             . $js
             . "\n}";
    }

    /**
     * Check if this PHP can generate gzip compressed data.
     *
     * @return  bool    true if this PHP has gzip support.
     */
    protected function _canGzipCompress()
    {
        return function_exists('gzencode');
    }

    /**
     * Check if the client can accept gzip encoded content.
     *
     * @return  bool    true if the client supports gzip; false otherwise.
     */
    protected function _clientAcceptsGzip()
    {
        $front   = Zend_Controller_Front::getInstance();
        $request = $front->getRequest();
        $accepts = $request->getHeader('Accept-Encoding');

        return strpos($accepts, 'gzip') !== false;
    }
}
