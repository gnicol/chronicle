<?php
/**
 * Aggregating version of HeadLink helper.
 *
 * Combines css files (resolving relative urls).
 * Ensures that theme links come last.
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        absolutize @import statements
 */
class P4Cms_View_Helper_HeadLink extends Zend_View_Helper_HeadLink
{
    protected   $_aggregate     = false;
    protected   $_aggregated    = false;
    protected   $_assetHandler  = null;
    protected   $_documentRoot  = null;
    protected   $_includeTheme  = true;

    /**
     * Extend parent constructor to add 'id' to valid item keys.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_itemKeys[] = "id";
    }

    /**
     * Enable or disable css aggregation.
     *
     * @param   bool    $aggregate          set to true to enable, false to disable.
     * @return  P4Cms_View_Helper_HeadLink  provides fluent interface.
     */
    public function setAggregateCss($aggregate)
    {
        $this->_aggregate = (bool) $aggregate;
        return $this;
    }

    /**
     * Set the file-system path to the document root.
     * 
     * @param   string  $path               the location of the public folder.
     * @return  P4Cms_View_Helper_HeadLink  provides fluent interface.
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
     * Set the asset handler used to store the aggregated css file(s).
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
     * Get the asset handler used to store the aggregated css file(s).
     *
     * @return  P4Cms_AssetHandlerInterface|null    the handler to use or null
     */
    public function getAssetHandler()
    {
        return $this->_assetHandler;
    }

    /**
     * Extend toString to aggregate css files where possible and
     * ensure that theme links appear last, for CSS precedence.
     *
     * @param   string|int  $indent  Zend provides no documentation for this param.
     * @return  string
     */
    public function toString($indent = null)
    {
        $themePath = P4Cms_Theme::fetchActive()->getBaseUrl();
        $items     = array();
        $themes    = array();
        foreach ($this as $item) {
            if (strpos($item->href, $themePath) === false) {
                $items[]  = $item;
            } else if ($this->_includeTheme) {
                $themes[] = $item;
            }
        }
        $this->getContainer()->exchangeArray(array_merge($items, $themes));

        // aggregate css files (but only once).
        if ($this->_aggregate && !$this->_aggregated) {
            $this->_aggregateCss();
        }

        // when aggregation is not enabled, consolidate MSIE stylesheets which limits CSS links to 32.
        $consolidated = '';
        if (!$this->_aggregated) {
            $consolidated = $this->_consolidateForInternetExplorer();
        }

        return $consolidated . parent::toString();
    }

    /**
     * Enable or disable inclusion of theme links when rendering.
     * 
     * In some display contexts it might be desirable to disable theme
     * stylesheets so that they don't influence presentation.
     * 
     * @param   bool    $includeTheme       true to include theme links (the default) 
     *                                      false to exclude them
     * @return  P4Cms_View_Helper_HeadLink  provides fluent interface
     */
    public function setIncludeTheme($includeTheme)
    {
        $this->_includeTheme = (bool) $includeTheme;
        
        return $this;
    }
    
    /**
     * Retrieves the qualified base url.
     * 
     * @return  string|null     The qualified base url in use
     */
    public function getQualifiedBaseUrl()
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        if (!$request instanceof Zend_Controller_Request_Http) {
            throw new Zend_View_Exception(
                "Cannot assemble qualified base URL - not an http request."
            );
        }

        return $request->getScheme() . "://" . $request->getHttpHost() . $request->getBaseUrl();
    }

    /**
     * Internet Explorer limits the number of CSS files that can be linked to 32.
     * See the MSDN blog post: http://blogs.msdn.com/b/ieinternals/archive/2011/05/14/10164546.aspx
     * It is possible to work around this limitation by using the @import CSS pragma.
     * 
     * This method emits one or more style blocks containing sufficient @import directives
     * to ensure that all styles get loaded. However, nothing is done if the CSS has
     * already been aggregated or the requesting browser does not appear to be MSIE.
     */
    protected function _consolidateForInternetExplorer()
    {
        // refuse to do anything for non-MSIE browsers
        $userAgent = array_key_exists('HTTP_USER_AGENT', $_SERVER)
            ? $_SERVER['HTTP_USER_AGENT']
            : '';
        if (!preg_match('/^Mozilla.+MSIE ([0-9]+[\.0-9]*)/', $userAgent)) {
            return '';
        }

        // group css files by media.
        $notConsolidated = array();
        $groups = array();
        foreach ($this as $item) {

            // only consolidate CSS.
            if ($item->type !== 'text/css') {
                $notConsolidated[] = $item;
                continue;
            }

            // group files by media and conditional stylesheet
            $name = !empty($item->conditionalStylesheet)
                ? $item->media . '-' . $item->conditionalStylesheet
                : $item->media;
            $name = preg_replace('/[^a-zA-Z0-9 .-]/', '', $name);
            if (!isset($groups[$name])) {
                $groups[$name] = array(
                    'urls'          => array(),
                    'attributes'    => array(
                        'media'         => $item->media,
                        'conditional'   => $item->conditionalStylesheet
                    )
                );
            }

            $groups[$name]['urls'][]   = $item->href;
        }

        // only keep ignored links in the list.
        $this->getContainer()->exchangeArray($notConsolidated);

        // process media groups and construct headStyle content featuring @import statements.
        // we clone the headLink helper, give it a fresh container, and render its output here
        // to avoid potential issues with/without headStyle execution elsewhere.
        $headStyle = clone $this->view->getHelper('headStyle');
        $headStyle->setContainer(new Zend_View_Helper_Placeholder_Container);
        foreach ($groups as $name => $group) {
            // batch stylesheets up to 31 at a time to meet MSIE limitations.
            while (count($group['urls'])) {
                $batch = array_splice($group['urls'], 0, 31);
                $styles = "@import url('" . join("');\n@import url('", $batch) ."');";
                $headStyle->appendStyle($styles, $group['attributes']);
            }
        }
        return $headStyle->toString();
    }

    /**
     * Aggregate local, unconditional css files by media type.
     * Resolves relative urls and concatenates css files into
     * build files. Rebuilds whenever a file changes.
     */
    protected function _aggregateCss()
    {
        // bail out if no asset handler is configured
        if (!$this->getAssetHandler()) {
            P4Cms_Log::log(
                "Failed to aggregate CSS. Asset Handler is unset.",
                P4Cms_Log::ERR
            );
            return;
        }

        // bail out if document root is unset.
        if (!$this->getDocumentRoot()) {
            P4Cms_Log::log(
                "Failed to aggregate CSS. Document root is unset.",
                P4Cms_Log::ERR
            );
            return;
        }

        // group css files by media.
        $ignore = array();
        $groups = array();
        foreach ($this as $item) {

            // only aggregate CSS links that are local.
            if ($item->type !== 'text/css' || P4Cms_Uri::hasScheme($item->href)) {
                $ignore[] = $item;
                continue;
            }

            // ignore if file does not exist.
            $file = $this->getDocumentRoot() . $item->href;
            if (!file_exists($file)) {
                $ignore[] = $item;
                continue;
            }

            // group files by media, build group, and conditional files.
            // build groups are named collections of css files that should be
            // aggregated together
            $parts = array($item->media);
            if (isset($item->extras['buildGroup'])) {
                $parts[] = $item->extras['buildGroup'];
            }
            $parts[] = $item->conditionalStylesheet;
            $name = join('-', $parts);
            $name = preg_replace('/[^a-zA-Z0-9 .-]/', '', $name);
            $name = str_replace(' ', '-', $name);
            if (!isset($groups[$name])) {
                $groups[$name] = array(
                    'time'          => 0,
                    'media'         => $item->media,
                    'files'         => array(),
                    'conditional'   => $item->conditionalStylesheet
                );
            }

            // add link to the group.
            $time                       = filemtime($file);
            $groups[$name]['files'][]   = $file;
            $groups[$name]['time']      = $time < $groups[$name]['time']
                ? $groups[$name]['time']
                : $time;
        }

        // determine if compression should be enabled
        $compressed = $this->_canGzipCompress() && $this->_clientAcceptsGzip();

        // process build groups.
        $styles = array();
        foreach ($groups as $name => $group) {
            // generate build filename.
            $buildFile = $name . "-" . md5(implode(',', $group['files']) . $group['time']) 
                       . ($compressed ? '.cssgz' : '.css');

            // rebuild if file does not exist.
            if (!$this->getAssetHandler()->exists($buildFile)) {
                $css = "";
                foreach ($group['files'] as $file) {
                    $content = file_get_contents($file);
                    $content = $this->_stripCharset($content);
                    $content = $this->_minifyCss($content);
                    $content = $this->_resolveCssUrls($content, $file);
                    $css    .= $content;
                }

                // also compress if possible.
                if ($compressed) {
                    $css = gzencode($css, 9);
                }

                // write out aggregate file - if any fail, abort aggregation.
                if (!$this->getAssetHandler()->put($buildFile, $css)) {
                    return;
                }
            }

            // we just capture the stylesheet at this point as we want
            // to cleanly abort aggregation if any stylesheets fail.
            $styles[] = array(
                'uri'         => $this->getAssetHandler()->uri($buildFile), 
                'media'       => $group['media'],
                'conditional' => $group['conditional']
            );
        }

        // if we made it this far aggregation worked; update the
        // list to only contain ignored and aggregated links.
        $this->getContainer()->exchangeArray($ignore);
        foreach ($styles as $style) {
            $this->appendStylesheet($style['uri'], $style['media'], $style['conditional']);
        }

        $this->_aggregated = true;
    }

    /**
     * Resolve relative URLs in the given css content against
     * the document root. This ensures the links continue to
     * work post aggregation.
     *
     * @param   string  $content    the css to resolve links in.
     * @param   string  $file       the original location of the css file.
     * @return  string  the css with relative URLs replace.
     */
    protected function _resolveCssUrls($content, $file)
    {
        $baseUrl  = $this->getAssetHandler()->isOffsite() ? $this->getQualifiedBaseUrl() : '';
        $basePath = $baseUrl . str_replace($this->getDocumentRoot(), '', dirname($file));
        return preg_replace_callback(
            '/url\([\'"]?([^\'")]+)[\'"]?\)/i',
            function ($matches) use ($baseUrl, $basePath)
            {
                // if it is a full url with schema just return as-is
                if (P4Cms_Uri::hasScheme($matches[1])) {
                    return $matches[0];
                }

                // if it isn't relative, but is lacking a schema, glue in the baseUrl
                if (!P4Cms_Uri::isRelativeUri($matches[1])) {
                    return "url('" . $baseUrl . "/" . $matches[1] . "')";
                }

                // if it's relative, glue in base path (which includes baseUrl)
                return "url('" . $basePath . "/" . $matches[1] . "')";
            },
            $content
        );
    }

    /**
     * Minify CSS
     * Strips comments and unnecessary whitespace.
     *
     * @param   string  $content    the css to minify.
     * @return  string  the input css with comments removed.
     * @todo    employ more complex minification.
     */
    protected function _minifyCss($content)
    {
        // strip comments.
        $content = preg_replace('#/\*.*\*/#Us', '', $content);

        // strip needless whitespace.
        $content = preg_replace('#\s*([\s{},:;])\s*#s', '\\1', $content);

        return $content;
    }

    /**
     * Remove @charset declarations from file for standards
     * compliance (only one such declaration may appear in a
     * stylesheet and it must be first line - aggregation
     * breaks these rules).
     *
     * @param   string  $content    the css to strip @charset's from.
     * @return  string  the css with @charset's removed.
     */
    protected function _stripCharset($content)
    {
        return preg_replace(
            '/^@charset\s+[\'"](\S*)\b[\'"];/i',
            '',
            $content
        );
    }

    /**
     * Is the linked stylesheet a duplicate?
     * Extended to protect against empty 'rel' property.
     *
     * @param  string $uri Style sheet
     * @return bool
     */
    protected function _isDuplicateStylesheet($uri)
    {
        foreach ($this->getContainer() as $item) {
            if (isset($item->rel) && ($item->rel == 'stylesheet') && ($item->href == $uri)) {
                return true;
            }
        }
        return false;
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
        $accepts = isset($request)
            ? $request->getHeader('Accept-Encoding')
            : '';

        return strpos($accepts, 'gzip') !== false;
    }
}
