<?php
/**
 * This is the iframe widget configuration form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widget_Form_IframeWidget extends P4Cms_Form_SubForm
{
    /**
     * Defines the elements that make up the widget config form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        $this->addElement(
            'text',
            'iframeSrc',
            array(
                'label'         => 'URL',
                'required'      => true,
                'description'   => "Source URL for the iframe element",
            )
        );

        $this->addElement(
            'text',
            'iframeWidth',
            array(
                'label'         => 'Width',
            )
        );

        $this->addElement(
            'text',
            'iframeHeight',
            array(
                'label'         => 'Height',
            )
        );

        $this->addElement(
            'select',
            'iframeScroll',
            array(
                'label'         => 'Show Scrollbars',
                'multiOptions'  => array(
                    'auto'  => 'Automatically',
                    'yes'   => 'Always',
                    'no'    => 'Never'
                )
            )
        );
    }
}
