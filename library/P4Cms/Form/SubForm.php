<?php
/**
 * Extends Zend_Form_SubForm to be aware of our prefix/elementPrefix paths.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_SubForm extends Zend_Dojo_Form_SubForm
{
    protected $_idPrefix;

    /**
     * Extend Zend_Dojo_Form's constructor to provide our own decorators.
     *
     * @param  array|Zend_Config|null $options  Zend provides no documentation for this param.
     * @return void
     */
    public function __construct($options = null)
    {
        // combine library prefix paths with paths from
        // the P4Cms_Form static registry.
        $prefixPaths = P4Cms_Form::getLibraryPathRegistry() + P4Cms_Form::getPrefixPathRegistry();

        // add prefix paths to form instance.
        foreach ($prefixPaths as $prefixPath) {
            extract($prefixPath);

            // add element and decorator paths to form.
            if ($type === static::ELEMENT || $type === static::DECORATOR) {
                $this->addPrefixPath($prefix, $path, $type);
            }

            // add decorator, validator and filter paths to elements.
            if ($type !== static::ELEMENT) {
                $this->addElementPrefixPath($prefix, $path, $type);
            }

            // add decorator paths to display groups.
            if ($type === static::DECORATOR) {
                $this->addDisplayGroupPrefixPath($prefix, $path);
            }
        }
        
        parent::__construct($options);
    }

    /**
     * Add id prefixes, then render the form.
     *
     * @param   Zend_View_Interface  $view  The Zend View Interface to render.
     * @return  string
     */
    public function render(Zend_View_Interface $view = null)
    {
        // prefix form element ids if id prefix is set.
        if ($this->getIdPrefix()) {
            P4Cms_Form::prefixFormIds($this, $this->getIdPrefix());
        }

        return parent::render($view);
    }

    /**
     * Set a string to prefix element ids with.
     *
     * @param   string              $prefix the string to prefix element ids with.
     * @return  P4Cms_Form_SubForm  provides fluent interface.
     */
    public function setIdPrefix($prefix)
    {
        $this->_idPrefix = (string) $prefix;
        return $this;
    }

    /**
     * Get the string used to prefix element ids.
     *
     * @return  string  the string used to prefix element ids.
     */
    public function getIdPrefix()
    {
        return $this->_idPrefix;
    }
}
