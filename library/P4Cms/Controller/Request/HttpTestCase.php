<?php
/**
 * More testable version of http requests. Allows server variables to be mocked.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Controller_Request_HttpTestCase extends Zend_Controller_Request_Http
{
    protected   $_server    = array();

    /**
     * Get one or all server variables. Extended to read from test values first.
     *
     * @param   string  $key        optional - a specific server variable to get
     *                              if no $key is passed, returns the entire $_SERVER array
     * @param   mixed   $default    default value to use if key not found
     * @return  mixed   Returns null if key does not exist
     */
    public function getServer($key = null, $default = null)
    {
        if ($key && isset($this->_server[$key])) {
            return $this->_server[$key];
        }

        return parent::getServer($key, $default);
    }

    /**
     * Override a server variable for testing purposes.
     *
     * @param   string  $key    the name of the variable
     * @param   mixed   $value  the value of the variable
     */
    public function setServer($key, $value)
    {
        $this->_server[$key] = $value;

        return $this;
    }
}