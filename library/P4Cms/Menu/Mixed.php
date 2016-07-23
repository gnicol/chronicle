<?php
/**
 * This model is used to normalize P4Cms_Menu and Zend_Navigation_Page classes
 * into a unifom model allowing them to be listed together.
 *
 * Please note a number of the fields in this model are read only:
 * id, menuId, menuItemId, label
 *
 * All of these are derived from the assiciated Menu and, optionally, Menu Item.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Menu_Mixed extends P4Cms_Model
{
    protected static    $_idField           = 'id';
    protected static    $_fields            = array(
        'id'            => array(
            'accessor'  => 'getId',
            'readOnly'  => true
        ),
        'menuId'        => array(
            'accessor'  => 'getMenuId',
            'readOnly'  => true
        ),
        'menuItemId'    => array(
            'accessor'  => 'getMenuItemId',
            'readOnly'  => true
        ),
        'type'          => array(
            'accessor'  => 'getType',
            'readOnly'  => true
        ),
        'depth'         => array(
            'accessor'  => 'getDepth',
            'mutator'   => 'setDepth',
            'default'   => 0
        ),
        'parentId'      => array(
            'accessor'  => 'getParentId',
            'readOnly'  => true
        ),
        'label'         => array(
            'accessor'  => 'getLabel',
            'readOnly'  => true
        )
    );

    protected           $_menu              = null;
    protected           $_menuItem          = null;
    protected           $_parentMenuItem    = null;

    /**
     * Get the id of this record. Extends parent to
     * dynamically generate the ID based on the Menu
     * and Menu Item.
     *
     * This is a read only field calculated off of the
     * menu and menu item.
     *
     * @return  mixed   the value of the id field.
     */
    public function getId()
    {
        if (!$this->hasMenu() || !$this->getMenuId()) {
            return null;
        }

        if (!$this->hasMenuItem()) {
            return $this->getMenuId();
        }

        return $this->getMenuId() . '/' . $this->getMenuItemId();
    }

    /**
     * Retrieve the identifier for this menu or menu item type.
     * The class name is used to id the type of each entry.
     *
     * @return  string|null     the id for this entry type.
     */
    public function getType()
    {
        if ($this->hasMenuItem()) {
            return get_class($this->getMenuItem());
        } else if ($this->hasMenu()) {
            return get_class($this->getMenu());
        }
        
        return null;
    }

    /**
     * Returns the depth of this item in the heigharchy.
     * It is intended all Menu's will be a depth of 0 and all Menu Items
     * will be a depth of >= 1. In practice implementors could violate this
     * intent.
     *
     * @return  int     The depth
     */
    public function getDepth()
    {
        return $this->_getValue('depth');
    }

    /**
     * Set the depth of this item. See getDepth for more details.
     *
     * @param   int     $depth  The depth to use
     * @return  P4Cms_Menu_Mixed        To maintain a fluent interface
     */
    public function setDepth($depth)
    {
        if (!is_int($depth)) {
            throw new InvalidArgumentException('Depth must be an int');
        }

        return $this->_setValue('depth', $depth);
    }

    /**
     * Return the parent P4Cms_Menu_Mixed id if any.
     *
     * This is a read only field calculated off of the 
     * parent menu item, menu item and menu.
     *
     * @return  string|null     The parent's mixed ID
     */
    public function getParentId()
    {
        if ($this->hasParentMenuItem() && $this->hasMenu()) {
            return $this->getMenuId() . '/' . $this->getParentMenuItem()->uuid;
        }

        if ($this->hasMenu() && $this->hasMenuItem()) {
            return $this->getMenuId();
        }

        return null;
    }

    /**
     * Return the label for this model. If a menu item has been set the
     * label is returned from that. Otherwise, we fall back to the menu's
     * name and lastly empty string.
     *
     * This is a read only field calculated off of the menu item or menu.
     *
     * @return  string  The label for this instance.
     */
    public function getLabel()
    {
        if ($this->hasMenuItem()) {
            return $this->getMenuItem()->getLabel();
        }

        if ($this->hasMenu()) {
            return $this->getMenu()->getLabel();
        }

        return '';
    }

    /**
     * Determine if this instance has a menu object set on it.
     *
     * @return  bool    True if a menu has been set false otherwise.
     */
    public function hasMenu()
    {
        return $this->_menu !== null;
    }

    /**
     * Return the menu associated with this instance.
     *
     * @return  P4Cms_Menu|null     The associated menu or null if none
     */
    public function getMenu()
    {
        return $this->_menu;
    }

    /**
     * Returns the ID off of the menu if one has been set.
     * Safe to call even if no menu has been set.
     *
     * This is a read only field calculated off of the menu.
     *
     * @return  string|null     The associated Menu's ID or null
     */
    public function getMenuId()
    {
        if (!$this->hasMenu()) {
            return null;
        }

        return $this->getMenu()->getId();
    }

    /**
     * Set a menu on this instance.
     *
     * @param   P4Cms_Menu|null     $menu   A menu or null.
     * @return  P4Cms_Menu_Mixed        To maintain a fluent interface
     */
    public function setMenu(P4Cms_Menu $menu = null)
    {
        $this->_menu = $menu;

        return $this;
    }

    /**
     * Determine if this instance has a menu item object set on it.
     *
     * @return  bool    True if a menu item has been set false otherwise.
     */
    public function hasMenuItem()
    {
        return $this->_menuItem !== null;
    }

    /**
     * Return the menu item associated with this instance.
     *
     * @return  Zend_Navigation_Page|null     The associated menu item or null if none
     */
    public function getMenuItem()
    {
        return $this->_menuItem;
    }

    /**
     * Returns the ID off of the menu item if one has been set.
     * Safe to call even if no menu item has been set.
     *
     * This is a read only field calculated off of the menu item.
     *
     * @return  string|null     The associated Menu Item's UUID or null
     */
    public function getMenuItemId()
    {
        if (!$this->hasMenuItem()) {
            return null;
        }

        return $this->getMenuItem()->uuid;
    }

    /**
     * Set a menu item on this instance.
     *
     * @param   Zend_Navigation_Page|null   $menuItem   A menu item or null.
     * @return  P4Cms_Menu_Mixed        To maintain a fluent interface
     */
    public function setMenuItem(Zend_Navigation_Page $menuItem = null)
    {
        $this->_menuItem = $menuItem;
    }

    /**
     * The Zend_Navigation_Page based object which is our parent or null.
     *
     * @return  Zend_Navigation_Page|null   The parent page that was set on this model.
     */
    public function getParentMenuItem()
    {
        return $this->_parentMenuItem;
    }

    /**
     * Determine if this instance has a parent menu item object set on it.
     *
     * @return  bool    True if a parent menu item has been set false otherwise.
     */
    public function hasParentMenuItem()
    {
        return $this->_parentMenuItem !== null;
    }

    /**
     * Set a new parent menu item on this instance or null.
     *
     * @param   Zend_Navigation_Page|null   $menuItem   The parent menu item or null
     * @return  P4Cms_Menu_Mixed        To maintain a fluent interface
     */
    public function setParentMenuItem(Zend_Navigation_Page $menuItem = null)
    {
        $this->_parentMenuItem = $menuItem;

        return $this;
    }
    
    /**
     * Return the parent container for this menu item be it another
     * page or a menu. Falls back to returning null for non-menu
     * items or items where parent cannot be determined.
     * 
     * @return  Zend_Navigation_Container|null  The parent container or null
     */
    public function getParentContainer()
    {
        if ($this->hasParentMenuItem()) {
            return $this->getParentMenuItem();
        }
        
        if ($this->hasMenu()) {
            return $this->getMenu()->getContainer();
        }
        
        return null;
    }
    
    /**
     * Returns the previous menu item that lives at the same level as
     * this models menu item. For non-menu item mixed models or when
     * no previous item exists null is returned.
     * 
     * @return  P4Cms_Menu_Mixed|null   The previous menu item or null
     */
    public function getPreviousMenuItem()
    {
        $container = $this->getParentContainer();
        if (!$container || !$this->hasMenuItem()) {
            return null;
        }
        
        $previous = null;
        foreach ($container as $page) {
            if ($page->uuid == $this->getMenuItem()->uuid) {
                break;
            }
            
            $previous = $page;
        }

        if (!$previous) {
            return null;
        }

        $mixed = new static;
        $mixed->setMenu($this->getMenu())
              ->setParentMenuItem($this->getParentMenuItem())
              ->setMenuItem($previous);
        
        return $mixed;
    }
    
    /**
     * Returns the nexdt menu item that lives at the same level as
     * this models menu item. For non-menu item mixed models or when
     * no next item exists null is returned.
     * 
     * @return  P4Cms_Menu_Mixed|null   The next menu item or null
     */
    public function getNextMenuItem()
    {
        $container = $this->getParentContainer();
        if (!$container || !$this->hasMenuItem()) {
            return null;
        }

        foreach ($container as $page) {
            if ($page->uuid == $this->getMenuItem()->uuid) {
                break;
            }
        }

        $container->next();

        if (!$container->valid()) {
            return null;
        }

        $next = $container->current();

        $mixed = new static;
        $mixed->setMenu($this->getMenu())
              ->setParentMenuItem($this->getParentMenuItem())
              ->setMenuItem($next);
        
        return $mixed;
    }
}
