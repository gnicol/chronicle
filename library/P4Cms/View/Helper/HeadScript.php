<?php
/**
 * Aggregating version of HeadScript helper (combines JS files)
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        absolutize @import statements
 */
class P4Cms_View_Helper_HeadScript extends Zend_View_Helper_HeadScript
{
    protected   $_aggregate     = false;
    protected   $_aggregated    = false;
    protected   $_assetHandler  = null;
    protected   $_documentRoot  = null;

    /**
     * Enable or disable JS aggregation.
     *
     * @param   bool    $aggregate              set to true to enable, false to disable.
     * @return  P4Cms_View_Helper_HeadScript    provides fluent interface.
     */
    public function setAggregateJs($aggregate)
    {
        $this->_aggregate = (bool) $aggregate;
        return $this;
    }

    /**
     * Set the file-system path to the document root.
     * 
     * @param   string  $path                   the location of the public folder.
     * @return  P4Cms_View_Helper_HeadScript    provides fluent interface.
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
     * Set the asset handler used to store the aggregated JS file(s).
     *
     * @param   P4Cms_AssetHandlerInterface|null    $handler    The handler to use or null
     * @return  P4Cms_View_Helper_HeadScript        provides fluent interface.
     */
    public function setAssetHandler(P4Cms_AssetHandlerInterface $handler = null)
    {
        $this->_assetHandler = $handler;

        return $this;
    }

    /**
     * Get the asset handler used to store the aggregated JS file(s).
     *
     * @return  P4Cms_AssetHandlerInterface|null    the handler to use or null
     */
    public function getAssetHandler()
    {
        return $this->_assetHandler;
    }

    /**
     * Extend toString to aggregate JS files where possible.
     *
     * @param   string|int  $indent  Zend provides no documentation for this param.
     * @return  string
     */
    public function toString($indent = null)
    {
        // aggregate JS files (but only once).
        if ($this->_aggregate && !$this->_aggregated) {
            $this->_aggregateJs();
        }

        return parent::toString();
    }

    /**
     * Aggregate local unconditional JS files.
     * Rebuilds whenever a file changes.
     */
    protected function _aggregateJs()
    {
        // bail out if no asset handler is configured
        if (!$this->getAssetHandler()) {
            P4Cms_Log::log(
                "Failed to aggregate JS. Asset Handler is unset.",
                P4Cms_Log::ERR
            );
            return;
        }

        // bail out if document root is unset.
        if (!$this->getDocumentRoot()) {
            P4Cms_Log::log(
                "Failed to aggregate JS. Document root is unset.",
                P4Cms_Log::ERR
            );
            return;
        }

        // identify JS we can aggregate.
        $time   = 0;
        $files  = array();
        $ignore = array();
        $this->getContainer()->ksort();        
        foreach ($this as $item) {
            
            if (!$this->_isValid($item)) {
                continue;
            }

            // normalize the attributes.
            $attributes = $item->attributes + array(
                'src'           => null,
                'conditional'   => null,
                'charset'       => null,
                'defer'         => null
            );
            
            // only aggregate scripts that:
            //  - are type text/javascript
            //  - have a 'src' attribute
            //  - are local
            //  - are not conditional
            //  - have no explicit charset
            //  - are not deferred
            //  - exist in the document root
            $file = $this->getDocumentRoot() . $attributes['src'];
            if (($item->type !== 'text/javascript' 
                || !$attributes['src']
                || P4Cms_Uri::hasScheme($attributes['src']))
                || $attributes['conditional']
                || $attributes['charset']
                || $attributes['defer']
                || !file_exists($file)
            ) {
                $ignore[] = $item;
                continue;
            }
            
            $files[] = $file;
            $time    = max($time, filemtime($file));
        }
        
        // nothing to do if there are no files to aggregate.
        if (!$files) {
            return;
        }

        // determine if compression should be enabled
        $compressed = $this->_canGzipCompress() && $this->_clientAcceptsGzip();

        // generate build filename.
        $buildFile = md5(implode(',', $files) . $time) 
                   . ($compressed ? '.jsgz' : '.js');

        // rebuild if file does not exist.
        if (!$this->getAssetHandler()->exists($buildFile)) {
            $js = "";
            foreach ($files as $file) {
                $js .= file_get_contents($file) . "\n";
            }

            // also compress if possible.
            if ($compressed) {
                $js = gzencode($js, 9);
            }

            // write out aggregate file - if it fails, abort aggregation.
            if (!$this->getAssetHandler()->put($buildFile, $js)) {
                return;
            }
        }
        
        // if we made it this far aggregation worked; update the
        // list to only contain ignored plus the aggregated scripts.
        $this->getContainer()->exchangeArray($ignore);
        $this->appendFile($this->getAssetHandler()->uri($buildFile));
        
        $this->_aggregated = true;
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
