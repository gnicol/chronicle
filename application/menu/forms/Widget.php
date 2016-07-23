<?php
/**
 * This is the menu widget configuration form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_Form_Widget extends P4Cms_Form_SubForm
{
    /**
     * Defines the elements that make up the menu widget config form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        $menus = P4Cms_Menu::fetchAll();
        $menuNames = array();
        foreach ($menus as $menu) {
            $menuNames[$menu->getId()] = $menu->getLabel();
        }

        // tack on the various dynamic items so they can
        // pick from those as well
        $group             = 'Dynamic';
        $menuNames[$group] = array();
        $dynamicTypes      = P4Cms_Navigation_DynamicHandler::fetchAll();
        foreach ($dynamicTypes as $dynamicType) {
            $label = $dynamicType->getLabel();
            $value = 'P4Cms_Navigation_DynamicHandler/' . $dynamicType->getId();

            $menuNames[$group][$value] = $label;
        }
        natcasesort($menuNames[$group]);

        $this->addElement(
            'select',
            'menu',
            array(
                'label'         => 'Menu',
                'value'         => P4Cms_Menu::DEFAULT_MENU,
                'required'      => true,
                'description'   => "Choose a menu to display",
                'multiOptions'  => $menuNames,
                'onChange'      => "p4cms.menu.refreshSubForm(this.form);"
            )
        );

        // add option to select the display root
        $this->addElement(
            'select',
            P4Cms_Menu::MENU_ROOT,
            array(
                'value'         => '',
                'label'         => 'Display Root',
            )
        );
        $this->getElement(P4Cms_Menu::MENU_ROOT)
             ->getDecorator('htmlTag')
             ->setOption('class', 'menu-root');
        $this->_loadRootOptions();

        // add option to toggle inclusion of root item, when rooting a menu.
        $this->addElement(
            'checkbox',
            P4Cms_Menu::MENU_KEEP_ROOT,
            array(
                'label'         => 'Include Root Item',
                'description'   => 'Set the display root (starting point) to display from.<br/>'
                                .  'Optionally show the root item in the displayed menu.'
            )
        );
        P4Cms_Form::moveCheckboxLabel($this->getElement(P4Cms_Menu::MENU_KEEP_ROOT));
        $this->getElement(P4Cms_Menu::MENU_KEEP_ROOT)
             ->getDecorator('htmlTag')
             ->setOption('class', 'keep-root');
        $this->getElement(P4Cms_Menu::MENU_KEEP_ROOT)
             ->getDecorator('Description')
             ->setEscape(false);

        // add option to limit depth of the displayed menu.
        $options = array('' => 'Unlimited') + range(1, 10);
        $this->addElement(
            'select',
            P4Cms_Menu::MENU_MAX_DEPTH,
            array(
                'value'         => '',
                'label'         => 'Maximum Depth',
                'description'   => 'Set the maximum depth of items to display.',
                'multiOptions'  => $options
            )
        );

        // add option to limit depth of the displayed menu.
        $options = array('' => 'Unlimited')
                 + array_combine(range(1, 10),       range(1, 10))
                 + array_combine(range(15, 50, 5),   range(15, 50, 5))
                 + array_combine(range(60, 100, 10), range(60, 100, 10));
        $this->addElement(
            'select',
            P4Cms_Menu::MENU_MAX_ITEMS,
            array(
                'value'         => '',
                'label'         => 'Maximum Items',
                'description'   => 'Set the maximum number of items to display.',
                'multiOptions'  => $options
            )
        );
    }

    /**
     * Extends parent to ensure root options are updated.
     *
     * @param   array  $data  The form data to check for validity.
     * @return  boolean  When true, data is valid.
     */
    public function isValid($data)
    {
        // populate root options according to selected menu.
        $menu = isset($data['config']['menu'])
            ? $data['config']['menu']
            : '';
        $this->_loadRootOptions($menu);

        return parent::isValid($data);
    }

    /**
     * Extends parent to ensure root options are updated.
     *
     * @param   array      $defaults  Defaults for the menu widget.
     * @return  Zend_Form  Provide a fluent interface.
     */
    public function setDefaults(array $defaults)
    {
        $return = parent::setDefaults($defaults);
        $this->_loadRootOptions();

        return $return;
    }

    /**
     * Populates the root options based on the currently set menu.
     *
     * @param   string  $menuId optional - the menu (or handler id) to base root
     *                          options on (defaults to current value of menu field).
     */
    protected function _loadRootOptions($menuId = null)
    {
        // if the menu root element isn't present just return
        if (!$this->getElement(P4Cms_Menu::MENU_ROOT)) {
            return;
        }

        $options = array(
            '' => 'Show Entire Menu'
        );

        // if no menu was passed, get current setting
        if ($menuId === null) {
            $menuId = $this->getValue('menu');
        }

        // If no valid menu set, can't load root options
        try {
            $menu = P4Cms_Menu::fetchMenuOrHandlerAsMenu($menuId);
        } catch (Exception $e) {
            $this->getElement(P4Cms_Menu::MENU_ROOT)->setMultiOptions($options);
            return;
        }

        // use a recursive iterator iterator to flatten the list of menu items
        $iterator = new RecursiveIteratorIterator(
            $menu->getExpandedContainer(),
            RecursiveIteratorIterator::SELF_FIRST
        );

        // add each item to the list of available roots; keyed on ID and with an indented label
        $roots = array();
        foreach ($iterator as $item) {
            $id = P4Cms_Menu::getItemId($item);

            // skip over any items that don't have IDs
            if (empty($id)) {
                continue;
            }

            $indent = str_repeat(P4Cms_Form::UTF8_NBSP, $iterator->getDepth() * 2);
            $roots[$id] = $indent . $item->label;
        }

        if (count($roots)) {
            $options['Display Root'] = $roots;
        } else {
            $options['Empty Menu'] = array();
        }

        $this->getElement(P4Cms_Menu::MENU_ROOT)->setMultiOptions($options);
    }
}
