<?php
/**
 * This is the menu form to display while editing content.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_Form_Content extends P4Cms_Form_SubForm
{
    /**
     * Defines the elements that make up the menu form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // set the title of this form.
        $this->setLegend('Menus');
        
        // render the menu item sub-form into a hidden element
        // for javascript to use when adding more menu items.
        $this->addElement(
            'hidden',
            'template',
            array(
                'template'  => $this->getItemForm()->render(),
                'ignore'    => true,
                'disabled'  => true
            )
        );
        
        // a button to add another menu item - takes the menu item
        // form template, inserts it into the form and dijitizes it.
        $this->addElement(
            'button',
            'addMenuItem',
            array(
                'label'     => 'New Menu Item',
                'order'     => 1000,
                'ignore'    => true,
                'class'     => 'add-button',                
                'onClick'   => "
                    // get the menu item form template.
                    var form      = new dojo.NodeList(this.domNode).closest('form')[0];
                    var template  = dojo.query('input[name*=\'menus[template]\']', form)[0];
                    var itemForm  = dojo.attr(template, 'template');

                    // determine how many item forms we currently have.
                    var formCount = dojo.query('.menu-item-content-form', form).length;

                    // insert a copy of the template before the add button.
                    // modify template to incorporate item form count and dijitize
                    itemForm = dojo.place(itemForm, this.domNode, 'before');
                    dojo.style(itemForm, 'display', 'none');
                    dojo.query('*', itemForm).forEach(function(node){
                        var id    = dojo.attr(node, 'id');
                        var forId = dojo.attr(node, 'for');
                        var name  = dojo.attr(node, 'name');
                        if (id) {
                            id = 'menus-' + formCount + '-' + id;
                            dojo.attr(node, 'id', id);
                        }
                        if (forId) {
                            forId = 'menus-' + formCount + '-' + forId;
                            dojo.attr(node, 'for', forId);
                        }
                        if (name) {
                            name = 'menus[' + formCount + '][' + name + ']';
                            dojo.attr(node, 'name', name);
                        }
                    });
                    dojo.parser.parse(itemForm);
                    
                    // fade it in.
                    p4cms.ui.show(itemForm);
                "
            )
        );
    }
    
    /**
     * Get a form for quick add/edit of content menu items.
     * 
     * @param   array|null  $values         optional - values to populate the sub-form
     * @return  Menu_Form_MenuItemContent   a heavily modified content menu item form
     */
    public function getItemForm(array $values = null)
    {
        $form = new Menu_Form_MenuItemContentQuick;
        
        // decorate item form as a sub-form.
        P4Cms_Form::normalizeSubForm($form);
        
        // if values were given, set defaults on the item form.
        if ($values) {
            $form->setDefaults($values);
        }
        
        return $form;
    }
}
