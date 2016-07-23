<?php
/**
 * This form is specialized for quickly editing content 
 * menu items from the context of content editing.
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_Form_MenuItemContentQuick extends Menu_Form_MenuItemContent
{
    /**
     * Defines the elements that make up the 'quick' content menu item form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        parent::init();
        
        // remove elements we don't care to expose here.
        $remove = array('type', 'contentId', 'target', 'class', 'save');
        array_map(array($this, 'removeElement'), $remove);
        $this->removeDisplayGroup('buttons');
        
        // in content post-save (when we are actually dealing with this form),
        // we need access to fields that are normally ignored, expose them.
        $expose = array('uuid', 'menuId', 'position', 'location');
        foreach ($expose as $name) {
            $this->getElement($name)->setIgnore(false);
        }

        // tweak label element to change label and hide input by default.
        $label = $this->getElement('label');
        $label->getDecorator('htmlTag')->setOption('style', 'display: none;');
        
        // hide the onClick element while editing menu items in the context of 
        // editing content.
        $onClick = $this->getElement('onClick');
        $onClick->getDecorator('htmlTag')->setOption('style', 'display: none;');
        $onClick->getDecorator('label')->setOption('style', 'display: none;');
        
        // tweak checkbox onChange behavior for content editing context.
        // show/hide the label field and update its value as appropriate.
        $this->getElement('autoLabel')->setAttrib(
            "onClick", 
            "
            var fieldset = new dojo.NodeList(this).closest('fieldset')[0];
            var label    = dojo.query('input[type=text][name*=\'label\']', fieldset)[0];
            if (this.anim && this.anim.status() != 'stopped') {
                this.anim.stop();
            }
            if (this.checked) {
                dojo.attr(label, 'disabled', true);
                this.anim = p4cms.ui.hide(label.parentNode);
            } else {
                // update title to match content title
                var subForm  = new dojo.NodeList(this).closest('[dojotype=p4cms.content.SubForm]')[0];
                subForm      = dijit.byNode(subForm);
                var title    = dojo.query('input[name=title]', subForm.getContentEntry().domNode)[0];
                label.value = title ? title.value : '';
                
                dojo.removeAttr(label, 'disabled');
                this.anim = p4cms.ui.show(label.parentNode);
            }
            "
        );
        
        // add a button to delete the menu item.
        $this->addElement(
            'checkbox',
            'remove',
            array(
                'label'     => "Remove",
                'order'     => -1000,
                'class'     => 'menu-item-remove',
                'onClick'  => "
                    var checkbox = this;
                    p4cms.ui.hide(
                        new dojo.NodeList(checkbox).closest('fieldset')[0],
                        {onEnd: function(){dojo.attr(checkbox, 'checked', true);}}
                    );
                "
            )
        );
        $element = $this->getElement('remove');
        $this->moveCheckboxLabel($element);
        $element->getDecorator('htmlTag')->setOption('class', 'menu-item-remove');
    }
    
    /**
     * Extends parent to hide label field if using content's title.
     * 
     * @param   P4Cms_Record|array  $defaults   the default values to set on elements
     * @return  Zend_Form           provides fluent interface
     */
    public function setDefaults($defaults)
    {
        parent::setDefaults($defaults);
        
        $this->getElement('label')->getDecorator('htmlTag')->setOption(
            'style', 
            $this->getValue('label') ? null : 'display: none;'
        );
        
        return $this;
    }
    
    /**
     * Validate the form, if item is deleted, don't validate other fields.
     *
     * @param  array    $data   the data to validate.
     * @return boolean
     */
    public function isValid($data)
    {
        // dissolve a copy of data into a new values array so
        // we can find the remove element
        $values = $data;
        if ($this->isArray()) {
            $eBelongTo = $this->getElementsBelongTo();
            $values    = $this->_dissolveArrayValue($data, $eBelongTo);
        }

        // if we are doing a remove skip other checks
        if (isset($values['remove']) && $values['remove']) {
            return true;
        }
        
        return parent::isValid($data);
    }

    /**
     * Extended to only include menus we want to show when editing content.
     * 
     * @return  P4Cms_Model_Iterator    menus and menu items in a single flat list.
     */
    protected function _getLocations()
    {
        $filter = new P4Cms_Record_Filter;
        $filter->add('showInContentForm', '1');
        
        return P4Cms_Menu::fetchMixed(array('filter' => $filter));
    }    
}