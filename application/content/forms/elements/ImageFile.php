<?php
/**
 * Extends Zend_Form_Element_File to provide special validation
 * and handling for image files.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_Element_ImageFile extends Content_Form_Element_File
{
    /**
     * Extend parent to include image validator.
     */
    public function init()
    {
        parent::init();
        $this->addValidator(
            'File_Extension',
            false,
            "gif, ico, jpg, jpeg, png, svg"
        );
    }

    /**
     * Get the default display decorators to use when rendering
     * content elements of this type.
     *
     * @return  array   decorators configuration array suitable for passing
     *                  to element setDecorators().
     */
    public function getDefaultDisplayDecorators()
    {
        return array(
            array(
                'decorator' => 'DisplayImage',
                'options'   => array(
                    'placement' => Content_Form_Decorator_DisplayImage::REPLACE
                )
            )
        );
    }
}
