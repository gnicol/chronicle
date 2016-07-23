<?php
/**
 * Extends the Zend version to grant access to reverse and map.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Controller_Router_Route_Regex extends Zend_Controller_Router_Route_Regex
{
    /**
     * Will turn the passed route into a hash with the properties:
     *  string|null route
     *  string|null reverse
     *  array       defaults
     *  array       map
     *
     * @param Zend_Controller_Router_Route_Regex    $route  The route to array-ize
     */
    public static function toArray(Zend_Controller_Router_Route_Regex $route)
    {
        $properties = array();

        $properties['route']    = $route->_regex;
        $properties['reverse']  = $route->_reverse;
        $properties['defaults'] = $route->_defaults;
        $properties['map']      = $route->_map;

        return $properties;
    }

    /**
     * Extend parent to instantiate our class; they utilized 'self' so
     * no late static binding was occuring.
     *
     * @param   Zend_Config     $config     Configuration object
     * @return  Zend_Controller_Router_Route_Abstract   The configured instance
     */
    public static function getInstance(Zend_Config $config)
    {
        $defs = ($config->defaults instanceof Zend_Config) ? $config->defaults->toArray() : array();
        $map = ($config->map instanceof Zend_Config) ? $config->map->toArray() : array();
        $reverse = (isset($config->reverse)) ? $config->reverse : null;
        return new static($config->route, $defs, $map, $reverse);
    }

    /**
     * Extend parent to add any unknown data values to the query params part
     * of the url.
     *
     * @param   array   $data       An array of name (or index) and value pairs used as parameters
     * @param   bool    $reset      Should values be reset
     * @param   bool    $encode     Should data be encoded
     * @param   bool    $partial    Should this be treated as a partial
     * @return  string  Route path with user submitted parameters
     */
    public function assemble($data = array(), $reset = false, $encode = false, $partial = false)
    {
        $url = parent::assemble($data, $reset, $encode, $partial);

        // pull out any data variables which are not covered by the mapping
        $queryParams = array_diff_key(
            $data,
            array_merge(
                $this->getDefaults(),
                array_flip($this->getVariables())
            )
        );

        // tack on any query params.
        if (count($queryParams)) {
            $url .= "?" . http_build_query($queryParams);
        }

        return $url;
    }
}
