<?php
/**
 * Themes are collections of views, layouts, helpers, scripts, styles and
 * image files. Theme models are read-only.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Theme extends P4Cms_PackageAbstract
{
    const               PACKAGE_FILENAME    = 'theme.ini';
    const               DEFAULT_THEME       = 'default';
    const               DEFAULT_DOCTYPE     = 'XHTML1_STRICT';

    protected static    $_idField           = 'name';
    protected static    $_activeTheme       = null;
    protected static    $_packagesPaths     = array();
    protected static    $_fields            = array(
        'name'              => array(
            'accessor'      => 'getName',
            'mutator'       => 'setName'
        ),
        'label'             => array(
            'accessor'      => 'getLabel',
            'readOnly'      => true
        ),
        'version'           => array(
            'accessor'      => 'getVersion',
            'readOnly'      => true
        ),
        'description'       => array(
            'accessor'      => 'getDescription',
            'readOnly'      => true
        ),
        'maintainerInfo'    => array(
            'accessor'      => 'getMaintainerInfo',
            'readOnly'      => true
        ),
        'tags'              => array(
            'accessor'      => 'getTags',
            'readOnly'      => true
        )
    );

    /**
     * Load the theme into the environment.
     * Set the theme in the ViewRenderer.
     * Add the path to the theme's helpers.
     * Add stylesheets and javascripts to the view.
     * Load any required dojo modules.
     * Register any provided dojo modules.
     */
    public function load()
    {
        // set this instance as the active theme.
        static::$_activeTheme = $this;

        $view = static::getView();

        // add the current theme's helpers path to the view.
        $view->addHelperPath($this->getPath() . '/helpers', 'Theme_Helper_');

        // each theme can have own form decorators
        $decoratorsPath = $this->getPath() . '/decorators/';
        if (is_dir($decoratorsPath)) {
            P4Cms_Form::registerPrefixPath(
                "Theme_Decorator_",
                $decoratorsPath,
                Zend_Form::DECORATOR
            );
        }

        // add theme's layout scripts path.
        $view->addScriptPath($this->getLayoutsPath());

        // add theme's views path to the view
        $view->addScriptPath($this->getViewsPath());

        // set the (x)html document type.
        $view->doctype($this->getDoctype());

        // add theme meta, stylesheets and scripts to view.
        $this->_loadHtmlMeta();
        $this->_loadStylesheets();
        $this->_loadScripts();

        // load dojo components.
        $this->_loadDojo();
    }

    /**
     * Get the doctype for this theme or the default if none has been specified.
     * Defaults to: 'XHTML1_STRICT'.
     *
     * @return string   the doctype to use for this theme.
     */
    public function getDoctype()
    {
        $info = $this->getPackageInfo();
        return isset($info['doctype']) ? (string) $info['doctype'] : self::DEFAULT_DOCTYPE;
    }

    /**
     * Get the path to this theme's views.
     *
     * @return  string  the path to the view scripts.
     */
    public function getViewsPath()
    {
        return $this->getPath() . "/views";
    }

    /**
     * Get the path to this theme's layouts.
     *
     * @return  string  the path to the layout scripts.
     */
    public function getLayoutsPath()
    {
        return $this->getPath() . "/layouts";
    }

    /**
     * Fetch the active (currently loaded) theme.
     * Guaranteed to return the active theme model or throw an exception.
     *
     * @return  P4Cms_Theme             the currently active theme.
     * @throws  P4Cms_Theme_Exception   if there is no currently active theme.
     */
    public static function fetchActive()
    {
        if (!static::$_activeTheme || !static::$_activeTheme instanceof P4Cms_Theme) {
            throw new P4Cms_Theme_Exception("There is no active (currently loaded) theme.");
        }
        return static::$_activeTheme;
    }

    /**
     * Determine if there is an active (currently loaded) theme.
     *
     * @return  boolean     true if there is an active theme.
     */
    public static function hasActive()
    {
        try {
            static::fetchActive();
            return true;
        } catch (P4Cms_Theme_Exception $e) {
            return false;
        }
    }

    /**
     * Fetch the default theme.
     * Guaranteed to return the default theme model or throw an exception.
     *
     * @return  P4Cms_Theme the default theme.
     * @throws  P4Cms_Model_NotFoundException  if the default theme can't be fetched.
     */
    public static function fetchDefault()
    {
        return static::fetch(static::DEFAULT_THEME);
    }

    /**
     * Clear any package paths and active theme. Primarily used for testing.
     */
    public static function reset()
    {
        static::clearPackagesPaths();
        static::$_activeTheme = null;
    }
}
