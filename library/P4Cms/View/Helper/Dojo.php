<?php
/**
 * Derivative of dojo view helper designed that provides control
 * over what dojo elements are rendered.
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_Dojo extends Zend_Dojo_View_Helper_Dojo
{
    /**
     * Use our configurable dojo container helper.
     *
     * @return void
     */
    public function __construct()
    {
        $registry = Zend_Registry::getInstance();
        if (!isset($registry[__CLASS__])) {
            require_once 'P4Cms/View/Helper/Dojo/Container.php';
            $container = new P4Cms_View_Helper_Dojo_Container();
            $registry[__CLASS__] = $container;
        }
        $this->_container = $registry[__CLASS__];
    }

    /**
     * Set elements to render.
     *
     * @param array $elements Optional elements to render
     * @return SOMETHING
     */
    public function dojo($elements = null)
    {
        if ($elements !== null) {
            $this->_container->setRender((array) $elements);
        } else {
            $this->_container->setRender(
                array(
                    'config',
                    'scriptTag',
                    'extras',
                    'layers',
                    'stylesheets'
                )
            );
        }

        return parent::dojo();
    }

    /**
     * Retrieve dojo view helper container (holds dojo data and
     * rendering logic).
     *
     * @return  Zend_Dojo_View_Helper_Dojo_Container
     */
    public function getContainer()
    {
        return $this->_container;
    }

    /**
     * Retrieve dojo view helper container (holds dojo data and
     * rendering logic).
     *
     * @param   Zend_Dojo_View_Helper_Dojo_Container    $container  the container to use.
     * @return  P4Cms_View_Helper_Dojo                  provides fluent interface.
     */
    public function setContainer($container)
    {
        $this->_container = $container;
        return $this;
    }
}
