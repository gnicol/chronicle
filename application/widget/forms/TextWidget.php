<?php
/**
 * This is the text widget configuration form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widget_Form_TextWidget extends P4Cms_Form_SubForm
{
    /**
     * Defines the elements that make up the text widget config form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        $this->addElement(
            'editor',
            'text',
            array(
                'label'         => 'Text',
                'required'      => true,
                'description'   => "Enter the text to display.",
                'attribs'       => array(
                    "height"    => 200
                )
            )
        );
        $this->getElement('text')
             ->getDecorator('label')
             ->setOption(
                'helpUri',
                Zend_Controller_Front::getInstance()->getBaseUrl()
                . '/' . Ui_Controller_Helper_HelpUrl::HELP_BASE_URL . '/'
                . 'widgets.macros.html'
             );
    }
}
