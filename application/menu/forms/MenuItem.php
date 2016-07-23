<?php
/**
 * This is the menu item form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_Form_MenuItem extends P4Cms_Form
{
    /**
     * Defines the elements that make up the menu item form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // form should use p4cms-ui styles.
        $this->setAttrib('class', 'p4cms-ui menu-item-form');

        // set the method for the form to POST
        $this->setMethod('post');

        $this->addElement('hidden', 'uuid',   array('ignore' => true));
        $this->addElement('hidden', 'menuId', array('ignore' => true));

        $this->addElement(
            'text',
            'label',
            array(
                'label'         => 'Label',
                'order'         => -40,
                'required'      => true,
                'filters'       => array('StringTrim')
            )
        );
        $this->getElement('label')
             ->getDecorator('htmlTag')
             ->setOption('class', 'menu-item-label');

        $this->addElement(
            'select',
            'position',
            array(
                'label'         => 'Position',
                'order'         => -30,
                'required'      => true,
                'ignore'        => true,
                'multiOptions'  => array(
                    'before'    => 'Before',
                    'after'     => 'After',
                    'under'     => 'Under'
                )
            )
        );
        $this->getElement('position')
             ->getDecorator('htmlTag')
             ->setOption('class', 'menu-item-position');

        $this->addElement(
            'select',
            'location',
            array(
                'label'         => 'Location',
                'order'         => -20,
                'required'      => true,
                'ignore'        => true
            )
        );
        $this->getElement('location')
             ->getDecorator('label')
             ->setOption('tagClass', 'menu-item-location');
        $this->_updateLocationOptions();

        $this->addElement(
            'select',
            'type',
            array(
                'label'         => 'Type',
                'order'         => -10,
                'required'      => true,
                'value'         => 'Zend_Navigation_Page_Uri',
                'multiOptions'  => static::getTypeOptions()
            )
        );

        $this->addElement(
            'select',
            'target',
            array(
                'label'         => 'Target',
                'order'         => 100,
                'required'      => false,
                'multiOptions'  => array(
                    '_self'     => 'Current Window',
                    '_blank'    => 'New Window',
                    '_top'      => 'Top Window',
                    '_parent'   => 'Parent Window'
                )
            )
        );

        $this->addElement(
            'text',
            'class',
            array(
                'label'         => 'CSS Class',
                'order'         => 200,
                'required'      => false,
                'filters'       => array('StringTrim')
            )
        );

        $this->addElement(
            'textarea',
            'onClick',
            array(
                'label'         => 'Click Event',
                'order'         => 210,
                'required'      => false,
                'rows'          => 3,
                'cols'          => 60,
                'description'   => 'Optional JavaScript code for the onclick event.'
            )
        );

        $this->addElement(
            'SubmitButton',
            'save',
            array(
                'label'     => 'Save',
                'required'  => false,
                'class'     => 'preferred',
                'ignore'    => true
            )
        );

        // put the button in a fieldset.
        $this->addDisplayGroup(
            array('save'),
            'buttons',
            array(
                'order' => 300,
                'class' => 'buttons'
            )
        );
    }

    /**
     * Extend parent to deal with the combined type and
     * (virtual) handler fields for dynamic menu items.
     *
     * @param   string  $field  The field to retreive value of
     * @return  mixed   the requested fields value
     */
    public function getValue($field)
    {
        if ($field != 'type' && $field != 'handler') {
            return parent::getValue($field);
        }

        $values = $this->getValues();
        return isset($values[$field]) ? $values[$field] : null;
    }

    /**
     * Extend parent to split out the dynamic menu handler id
     * from the menu item type when present.
     *
     * @param  bool $suppressArrayNotation see parent
     * @return array
     */
    public function getValues($suppressArrayNotation = false)
    {
        $values = parent::getValues($suppressArrayNotation);

        if (isset($values['type'])) {
            $values = $this->splitType($values['type']) + $values;
        }

        return $values;
    }

    /**
     * Extends parent to combine handler field with type
     * when dealing with dynamic menu items.
     *
     * @param   P4Cms_Record|array  $defaults   the default values to set on elements
     * @return  Zend_Form           provides fluent interface
     */
    public function setDefaults($defaults)
    {
        parent::setDefaults($this->combineType($defaults));

        $this->_updateLocationOptions();

        return $this;
    }

    /**
     * Extends parent to combine handler field with type
     * when dealing with dynamic menu items.
     *
     * @param  array    $data   see parent
     * @return boolean
     */
    public function isValid($data)
    {
        return parent::isValid($this->combineType($data));
    }

    /**
     * For dynamic menu items this form adds the dynamic handler id
     * to the end of the type value (separated by a slash). This method
     * exists to aid in splitting these values apart again.
     *
     * @param   string  $type   the combined type/dynamic-handler id
     *                          (safe to call on type only values).
     * @return  array   an array containing the menu item type and optionally
     *                  the dynamic handler id (for dynamic menu items).
     */
    public function splitType($type)
    {
        $values = array('type' => $type);
        if (strpos($type, 'P4Cms_Navigation_Page_Dynamic/') === 0) {
            list($values['type'], $values['handler']) = explode('/', $type, 2);
        }

        return $values;
    }

    /**
     * For dynamic menu items we append the dynamic handler id
     * to the end of the type value (separated by a slash).
     *
     * @param   array   $values     form values array where the type and
     *                              dynamic handler are potentially separate.
     * @return  array   values with the dynamic handler id appended to the
     *                  type value and separated by a slash.
     */
    public function combineType($values)
    {
        if (isset($values['type'], $values['handler'])
            && $values['type'] == 'P4Cms_Navigation_Page_Dynamic'
        ) {
            $values['type'] .= '/' . $values['handler'];
            unset($values['handler']);
        }

        return $values;
    }

    /**
     * Generate the page type multi-options for use with the type field.
     * Static so it can be used elsewhere (e.g. menu grid filters).
     *
     * @param   bool    $checklist      optional - prepare options for a nested checklist
     *                                  instead of a select field (false by default).
     * @param   bool    $includeMenu    optional - include a 'Menu' entry in the options
     *                                  (false by default).
     * @return  array   list of type multi-options.
     */
    public static function getTypeOptions($checklist = false, $includeMenu = false)
    {
        // start with standard navigation page types (excluding dynamic types).
        $types = P4Cms_Navigation_PageTypeHandler::fetchAll();
        $types = $types->filter(
            'id',
            'P4Cms_Navigation_Page_Dynamic',
            P4Cms_Model_Iterator::FILTER_INVERSE
        );
        $types = array_combine(
            $types->invoke('getId'),
            $types->invoke('getLabel')
        );

        if ($includeMenu) {
            $types['P4Cms_Menu'] = 'Menu';
        }

        // sort the entries by label
        natsort($types);

        // if preparing for a nested checklist, include a group label.
        if ($checklist) {
            $types['P4Cms_Navigation_Page_Dynamic'] = 'Dynamic';
        }

        // put dynamic types in an opt-group.
        $group         = 'Dynamic';
        $types[$group] = array();
        $dynamicTypes  = P4Cms_Navigation_DynamicHandler::fetchAll();
        foreach ($dynamicTypes as $dynamicType) {
            $label = $dynamicType->getLabel();
            $value = 'P4Cms_Navigation_Page_Dynamic/' . $dynamicType->getId();

            $types[$group][$value] = $label;
        }
        natcasesort($types[$group]);

        // include a blank entry (except for checklists)
        if (!$checklist) {
            $types = array("" => "") + $types;
        }

        return $types;
    }

    /**
     * Fills in the multi-options for the location drop-down.
     * The entry being edited and its children are excluded from this
     * list to avoid recursive selection.
     *
     * If no location has been set but we do have a menu id this
     * method will also set the location and position appropriately.
     */
    protected function _updateLocationOptions()
    {
        $uuid      = $this->getValue('uuid');
        $menuId    = $this->getValue('menuId');
        $location  = $this->getValue('location');
        $locations = $this->_getLocations();

        // clear our location if it is not valid
        if ($location) {
            $items = $locations->filter('id', $location, P4Cms_Model_Iterator::FILTER_COPY);
            if (!count($items)) {
                $location = null;
            }
        }

        // Handle editing and adding cases:
        // - If we have an item UUID we are editing an existing item and need
        //   to deal with removing it from the list and setting location.
        // - Otherwise, if we have a menuId and no location we are adding
        //   and need to set the location to the end of the current list.
        if ($uuid) {
            $items = $locations->filter('menuItemId', $uuid, P4Cms_Model_Iterator::FILTER_COPY);

            // do the limiting and location setting if item could be found
            if ($items->count()) {
                $item = $items->first();

                // if we don't have an explicitly set location; determine one
                if (!$location) {
                    $previous = $item->getPreviousMenuItem();
                    $next     = $item->getNextMenuItem();
                    $parentId = $item->getParentId();

                    // run through the three possible positions in order of preference
                    if ($previous) {
                        $this->getElement('location')->setValue($previous->getId());
                        $this->getElement('position')->setValue('after');
                    } else if ($next) {
                        $this->getElement('location')->setValue($next->getId());
                        $this->getElement('position')->setValue('before');
                    } else if ($parentId) {
                        $this->getElement('location')->setValue($parentId);
                        $this->getElement('position')->setValue('under');
                    }
                }

                // remove our item and any children to avoid the user
                // making a recursive selection for location.
                $removalItems = new RecursiveIteratorIterator(
                    $item->getMenuItem(),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                $removalIds = array($uuid);
                foreach ($removalItems as $removalItem) {
                    $removalIds[] = $removalItem->uuid;
                }

                $locations->filter('menuItemId', $removalIds, P4Cms_Model_Iterator::FILTER_INVERSE);
            }
        } else if ($menuId && !$location) {
            $items = $locations->filter('menuId', $menuId, P4Cms_Model_Iterator::FILTER_COPY);
            $items->filter('depth', 1);

            // if the menu has children, set our position to be the last child
            // otherwise set our position as under the menu.
            if ($items->count()) {
                $this->getElement('location')->setValue(end($items)->getId());
                $this->getElement('position')->setValue('after');
            } else {
                $this->getElement('location')->setValue($menuId);
                $this->getElement('position')->setValue('under');
            }
        }

        // any modifications to the locations list are complete
        // at this point simply glue together the IDs and labels
        // (indented for depth) so we can set the multi-options
        $options = array('' => '');
        foreach ($locations as $location) {
            $prefix = str_repeat(static::UTF8_NBSP, $location->getDepth() * 2);
            $options[$location->getId()] = $prefix . $location->getLabel();
        }

        $this->getElement('location')->setMultiOptions($options);
    }

    /**
     * Get the possible locations for this menu item to be positioned relative to.
     * Retrieves all menus and all menu items in a single flat list.
     * (@see P4Cms_Menu::fetchMixed)
     *
     * @return  P4Cms_Model_Iterator    menus and menu items in a single flat list.
     */
    protected function _getLocations()
    {
        return P4Cms_Menu::fetchMixed();
    }
}