<?php
/**
 * This is the image widget configuration form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widget_Form_ImageWidget extends P4Cms_Form_SubForm
{
    const   E_EMPTY_IMAGE_SOURCE        = "Image source type is required";
    const   E_EMPTY_IMAGE_CONTENT       = "Please select an image from the content";
    const   E_EMPTY_IMAGE_URL           = "Please enter a URL of the remote image";

    /**
     * Defines the elements that make up the image form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // define associate dijit for this form, which will handle all javascript
        // such as selecting an image from the content, autodetecting dimensions etc.
        $this->setAttrib('dojoType', 'p4cms.widget.ImageForm');

        // add field to hold content entry id value
        $this->addElement('hidden', 'contentId');

        // add field to specify content source
        $this->addElement(
            'radio',
            'imageSource',
            array(
                'label'         => 'Content Source',
                'multiOptions'  => array(
                    'content'   => 'Content',
                    'remote'    => 'External'
                )
            )
        );

        // add field to specify content title
        $this->addElement(
            'text',
            'contentTitle',
            array(
                'label'         => 'Content'
            )
        );

        // add field to specify remote image url
        $this->addElement(
            'text',
            'imageUrl',
            array(
                'label'         => 'URL',
                'value'         => 'http://'
            )
        );

        // add field to specify image size options
        $this->addElement(
            'select',
            'sizeType',
            array(
                'label'         => 'Size',
                'multiOptions'  => array(
                    'full'      => 'Full Size',
                    'custom'    => 'Custom Size'
                )
            )
        );

        // add field to specify image width
        $this->addElement(
            'text',
            'imageWidth',
            array(
                'label'         => 'Width'
            )
        );

        // add field to specify image height
        $this->addElement(
            'text',
            'imageHeight',
            array(
                'label'         => 'Height'
            )
        );

        // add field to specify lock image size ratio option
        $this->addElement(
            'checkbox',
            'lockRatio',
            array(
                'label'         => 'Scale Proportionally'
            )
        );
        // put the checkbox input before its labels
        P4Cms_Form::moveCheckboxLabel($this->getElement('lockRatio'));

        // add field to specify image margin
        $this->addElement(
            'text',
            'margin',
            array(
                'label'         => 'Margin'
            )
        );

        // add field to specify image alternate text
        $this->addElement(
            'text',
            'imageAlt',
            array(
                'label'         => 'Alt Text'
            )
        );

        // add field to collect optional link href
        $this->addElement(
            'text',
            'link',
            array(
                'label'         => 'Link',
                'description'   => 'Enter a url to visit when the image is clicked.'
            )
        );

        $this->addElement(
            'select',
            'linkTarget',
            array(
                'label'         => 'Open Link In',
                'multiOptions'  => array(
                    '_self'     => 'Current Window',
                    '_blank'    => 'A New Window',
                    '_lightbox' => 'A Light Box'
                )
            )
        );

        // add field to specify image alignment
        $this->addElement(
            'radio',
            'alignment',
            array(
                'label'         => 'Alignment',
                'multiOptions'  => array(
                    'none'      => 'None',
                    'left'      => 'Left',
                    'center'    => 'Center',
                    'right'     => 'Right',
                ),
                'value'         => 'none'
            )
        );
    }

    /**
     * Override parent to expand macros in the image url.
     *
     * @param   array   $defaults           form values to set
     * @return  Widget_Form_ImageWidget     provides fluent interface
     */
    public function setDefaults(array $defaults)
    {
        parent::setDefaults($defaults);

        // replace umage url with fully qualified image source path by expanding
        // macros it may contain
        $imageUrlElement = $this->getElement('imageUrl');
        if ($imageUrlElement) {
            $filter = new P4Cms_Filter_Macro;
            $imageUrlElement->setValue(
                $filter->filter($imageUrlElement->getValue())
            );
        }

        return $this;
    }

    /**
     * Extend parent to additionally validate image source data.
     *
     * @param   array   $data   form data to validate
     * @return  bool    true if form is valid, false otherwise
     */
    public function isValid($data)
    {
        $valid = parent::isValid($data);

        $belongsTo = $this->getElementsBelongTo();
        $data      = $belongsTo && isset($data[$belongsTo]) ? $data[$belongsTo] : $data;

        // ensure image source is specified:
        //  - image source radio value is present
        //  - image url is specified either via the content or by the remote url
        $imageSource = $data['imageSource'];
        if ($imageSource === 'content' && !$data['contentTitle']) {
            $this->getElement('contentTitle')->addError(self::E_EMPTY_IMAGE_CONTENT);
            $valid = false;
        } else if ($imageSource === 'remote' && !$data['imageUrl']) {
            $this->getElement('imageUrl')->addError(self::E_EMPTY_IMAGE_URL);
            $valid = false;
        } else if (!$imageSource) {
            $this->getElement('imageUrl')->addError(self::E_EMPTY_IMAGE_SOURCE);
            $valid = false;
        }

        return $valid;
    }
}