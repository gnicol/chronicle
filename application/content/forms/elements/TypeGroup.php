<?php
/**
 * Extends P4Cms_Form_Element_NestedCheckbox to support getting default values and parsing
 * the values out into a normalized array.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_Element_TypeGroup extends P4Cms_Form_Element_NestedCheckbox
{
    /**
     * Constructor
     *
     * $spec may be:
     * - string: name of element
     * - array: options with which to configure element
     * - Zend_Config: Zend_Config with options for configuring element
     * 
     * Sets multiOptions to default value (content types organized by type group) and
     * adds a javascript event to handle group selection.
     *
     * @param  string|array|Zend_Config $spec    Zend provides no description for this parameter.
     * @param  array|Zend_Config        $options Zend provides no description for this parameter.
     * @return void
     */
    public function __construct($spec, $options = null)
    {
        $this->setMultiOptions($this->getDefaultOptions());
        if (is_array($options) && !array_key_exists('onClick', $options)) {
            $this->setAttrib(
                'onClick', 
                "if (this.value.slice(-2) == '/*') {
                    p4cms.ui.toggleChildCheckboxes(this);
                } else {
                    p4cms.ui.toggleParentCheckbox(this);
                }"
            );
        }
        
        parent::__construct($spec, $options);
    }
    
    /**
     * If there are content type groups in the system, create array with content types with 
     * following structure:
     *
     * [<GROUP:NAME>/*] => <GROUP:NAME>
     * [<GROUP:NAME>/]  => Array(
     *      [<GROUP:NAME>/<CONTENT_TYPE:ID>] => <CONTENT_TYPE:LABEL>
     * )
     * 
     * where GROUP loops through all groups and
     * CONTENT_TYPE loops through all content types of the GROUP
     * 
     * If there are no content type groups, returns an empty array.
     * 
     * @return array()  returns structured options array
     */
    public function getDefaultOptions()
    {
        // get all content types combined by groups
        $groups = P4Cms_Content_Type::fetchGroups();
        
        if (empty($groups)) {
            return array();
        }

        $options = array();
        foreach ($groups as $group => $types) {
            $prefix = $group . '/';

            // add group item
            $options[$prefix . '*'] = $group;

            // add all content types of the group.
            foreach ($types as $type) {
                $options[$prefix][$prefix . $type->getId()] = $type->getLabel();
            }
        }

        return $options;
    }
    
     /**
     * Set element value such that if all types are set, set group; 
     * if group is set, set all types.
     *
     * @param  array|null   $value  The value to set.
     * @return Zend_Form_Element Return this element, for chaining.
     */
    public function setValue(array $value = null)
    {
        $typeCounts = array();
        foreach ((array)$value as $key => $type) {
            list($prefix, $suffix) = explode('/', $type);
            $options               = $this->getMultiOption($prefix . '/');
            
            if (!isset($typeCounts[$prefix])) {
                $typeCounts[$prefix] = array(
                    'hasGroup' => false,
                    'current'  => 0, 
                    'expected' => count($options)
                );
            }
            
            // if a group, set all types
            if ($suffix == '*') {
                $typeCounts[$prefix]['hasGroup'] = true;
                $value  = array_unique(array_merge($value, array_keys($options)));
            } else {
                $typeCounts[$prefix]['current'] ++;
                // if all types are checked and the group isn't already there, add the group to the values
                if (
                    $typeCounts[$prefix]['current'] == $typeCounts[$prefix]['expected'] 
                    && !$typeCounts[$prefix]['hasGroup']
                ) {
                    $value[] = $prefix.'/*';
                }
            }
        }
        
        return parent::setValue($value);
    }
    
    /**
     * Converts the selected types into a normalized value, expanding parent groups
     * to include their children (whether recorded as checked or not), while removing the groups.
     * 
     * This is used to return a list of only checked content types (not groups) for use primarily in 
     * P4Cms_Record_Filter objects to filter queries by the checked content types.
     * 
     * @return array()  The normalized type values.
     */
    public function getNormalizedTypes()
    {
        $types = parent::getValue();

        // if no types selected, return an empty array
        if (!is_array($types)) {
            return array();
        }

        // expand type groups and strip group prefixes
        foreach ($types as $key => &$type) {
            if (substr($type, -2) == '/*') {
                // add all content types with given prefix from options
                $prefix = substr($type, 0, -1);
                $types  = array_merge($types, $this->getMultiOption($prefix));
                $type   = null;
            } else {
                $type   = preg_replace('#.*/#', '', $type);
            }
        }

        // return list with types removed from duplicities and items equal to null
        return array_unique(array_filter($types));
    }
}