<?php
/**
 * A slight extension to Zend's File backend to add support for our
 * 'namespace' option. We implemented namespace support by mapping 
 * the 'namespace' option over to the existing 'file_name_prefix'.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Cache_Backend_File extends Zend_Cache_Backend_File
{
    /**
     * Extend parent to add the 'namespace' option.
     *
     * @param   array   $options    associative array of options
     */
    public function __construct(array $options = array())
    {
        $this->_options['namespace'] = false;
        return parent::__construct($options);
    }

    /**
     * Extends parent to work the 'namespace' option into the 
     * existing 'file_name_prefix' option.
     *
     * @param   string  $name   the option name
     * @param   mixed   $value  the options value
     */
    public function setOption($name, $value)
    {   
        // if we aren't touching a related setting let parent handle it
        if ($name != 'namespace' && $name != 'file_name_prefix') {
             parent::setOption($name, $value);
             return;
        }
        
        // pull out references to the namespace and file_name_prefix
        // then calculate our current namespaced based suffix.
        $namespace =& $this->_options['namespace'];
        $prefix    =& $this->_options['file_name_prefix'];
        $suffix    =  $namespace ? ('_' . md5($namespace)) : '';

        // strip the current namespace based suffix from the file
        // name prefix if its present
        if (substr($prefix, strlen($suffix) * -1) == $suffix) {
            $prefix = substr($prefix, 0, strlen($prefix) - strlen($suffix));
        }

        // let parent update the namespace/file_name_prefix
        parent::setOption($name, $value);

        // namespace may have changed; re-calculate suffix and
        // tack it onto our file_name_prefix
        $suffix = $namespace ? ('_' . md5($namespace)) : '';
        $this->_options['file_name_prefix'] = $prefix . $suffix;
    }
}