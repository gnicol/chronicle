<?php
/**
 * This form is specialized for content menu items and provides
 * a content reference element and label enhancements.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_Form_MenuItemContent extends Menu_Form_MenuItem
{
    /**
     * Defines the elements that make up the content menu item form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        parent::init();

        // distinguish this form from other menu item forms for styling.
        $this->setAttrib('class', $this->getAttrib('class') . ' menu-item-content-form');

        // make the title optional and add a description
        $this->getElement('label')
             ->setRequired(false);

        $this->addElement(
            'checkbox',
            'autoLabel',
            array(
                'label'     => "Use content entry's title",
                'order'     => -35,
                'value'     => true,
                'ignore'    => true,
                'onClick'   => $this->_updateLabel()
                            .  "if (this.checked) {"
                            .  "    dojo.attr(label, 'disabled', 'true');"
                            .  "} else {"
                            .  "    dojo.removeAttr(label, 'disabled');"
                            .  "}"
            )
        );

        // adjust how the auto-label element is decorated
        // to put the label immediately after the checkbox.
        $element = $this->getElement('autoLabel');
        static::moveCheckboxLabel($element);

        // add a content reference field
        $this->addElement(
            'contentSelect',
            'contentId',
            array(
                'label'     => 'Entry',
                'required'  => true,
                'onChange'  => $this->_updateLabel(),
                'onLoad'    => $this->_updateLabel()
            )
        );

        $this->addElement(
            'select',
            'contentAction',
            array(
                'label'         => "Action",
                'required'      => true,
                'multiOptions'  => array(
                    'view'      => 'Go To Page',
                    'image'     => 'View Image',
                    'download'  => 'Download File'
                )
            )
        );
    }

    /**
     * Extends parent to deal with enabling/disabling label
     * field based on use of content's title.
     *
     * @param   P4Cms_Record|array  $defaults   the default values to set on elements
     * @return  Zend_Form           provides fluent interface
     */
    public function setDefaults($defaults)
    {
        parent::setDefaults($this->combineType($defaults));

        $noTitle = strlen($this->getValue('label')) <= 0;

        $this->getElement('label')
             ->setAttrib('disabled', $noTitle ?: null);

        $this->getElement('autoLabel')
             ->setValue($noTitle);

        return $this;
    }

    /**
     * Get javascript code to update the label input
     * from the content reference element.
     *
     * @return  string  the js to update the label.
     */
    protected function _updateLabel()
    {
        return "var label = dojo.query('input[name=label]',                    this.form)[0];"
             . "var title = dojo.query('input[name=contentId-title]',          this.form)[0];"
             . "var auto  = dojo.query('input[name=autoLabel][type=checkbox]', this.form)[0];"
             . "if (auto.checked) {"
             . "    label.value = title.value;"
             . "}";
    }
}