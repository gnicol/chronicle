<?php
/**
 * This is the image rotater widget configuration form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widget_Form_ImageRotatorWidget extends P4Cms_Form_SubForm
{
    /**
     * Defines the elements that make up the image form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // add field to specify images
        $this->addElement(
            'ImageSelect',
            'images',
            array(
                'label'         => 'Images',
                'multiple'      => true,
                'extraFields'   => array('caption', 'link'),
                'browseOptions' => array(
                    'type' => array(
                        'types' => array("Assets/image")
                    )
                )
            )
        );

        // add field to specify image width
        $this->addElement(
            'text',
            'imageWidth',
            array(
                'label'         => 'Width',
                'class'         => 'image-width',
                'description'   => "When blank, the region's width is used."
            )
        );

        // add field to specify image height
        $this->addElement(
            'text',
            'imageHeight',
            array(
                'label'         => 'Height',
                'class'         => 'image-height',
            )
        );

    }
}