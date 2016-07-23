<?php
/**
 * Provides a facility for dynamic menu entries. Dynamic menu items will be expanded
 * by the associated DynamicHandler when accessed via getExpandedContainer on a
 * Menu instance.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Navigation_Page_Dynamic extends Zend_Navigation_Page
{
    /**
     * Dynamic menu item handler.
     *
     * @var string|null
     */
    protected $_handler;

    /**
     * Returns the id of the dynamic handler associated with this item.
     *
     * @return  string|null     id of the associated dynamic handler or null
     */
    public function getHandler()
    {
        return $this->_handler;
    }

    /**
     * Set the id of the dynamic handler associated with this item.
     *
     * @param   string|null     $id             id of the associated dynamic handler or null for none
     * @return  P4Cms_Navigation_Page_Dynamic   to maintain a fluent interface
     */
    public function setHandler($id)
    {
        $this->_handler = $id;
        
        return $this;
    }

    /**
     * Implement getHref as it is abstract in parent.
     * We don't actually use it so returns ""
     *
     * @return string  the page's href
     */
    public function getHref()
    {
        return "";
    }

    /**
     * Returns an array representation of the page
     *
     * @return array  associative array containing all page properties
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            array(
                'handler' => $this->getHandler(),
                'label'   => $this->_label
            )
        );
    }

    /**
     * If we have a handler set, and no label has been set on
     * this page, returns the handlers label.
     * Otherwise returns the title as per normal.
     *
     * @return string|null  page title or null
     */
    public function getLabel()
    {
        if ($this->_label || !$this->getHandler()) {
            return $this->_label;
        }

        return P4Cms_Navigation_DynamicHandler::fetch($this->getHandler())->getLabel();
    }
}
