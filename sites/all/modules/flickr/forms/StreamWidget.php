<?php
/**
 * This is the flickr stream widget config form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Flickr_Form_StreamWidget extends P4Cms_Form_SubForm
{
    const           PAGE_SIZE           = 5;
    const           DIMENSION_SQUARE    = 'Square';
    const           DIMENSION_THUMBNAIL = 'Thumbnail';
    const           DIMENSION_SMALL     = 'Small';
    const           DIMENSION_MEDIUM    = 'Medium';
    const           DIMENSION_LARGE     = 'Large';
    const           SOURCE_TAG          = 'Tag';
    const           SOURCE_USER         = 'User';
    const           SOURCE_GROUP        = 'Group';

    // dimensions defined on the flickr api website
    public static   $sizeDimensions     = array(
        self::DIMENSION_SQUARE      => 75,
        self::DIMENSION_THUMBNAIL   => 100,
        self::DIMENSION_SMALL       => 240,
        self::DIMENSION_MEDIUM      => 500,
        self::DIMENSION_LARGE       => 640
     );

    private         $_defaultDelay      = 3;
    private         $_defaultShowTitle  = false;
    private         $_defaultImageSize  = self::DIMENSION_THUMBNAIL;
    private         $_defaultSource     = self::SOURCE_TAG;

    private         $_definedSources    = array(
        self::SOURCE_TAG,
        self::SOURCE_USER,
        self::SOURCE_GROUP
    );
    
    private         $_definedSizes      = array(
        self::DIMENSION_SQUARE      => self::DIMENSION_SQUARE,
        self::DIMENSION_THUMBNAIL   => self::DIMENSION_THUMBNAIL,
        self::DIMENSION_SMALL       => self::DIMENSION_SMALL,
        self::DIMENSION_MEDIUM      => self::DIMENSION_MEDIUM,
        self::DIMENSION_LARGE       => self::DIMENSION_LARGE
    );

    /**
     * Defines the elements that make up the config form.
     * Form controls size of image (flickr size: square, thumb, small, medium, large, original),
     * and source (user, topic, group).
     * Called automatically when the form object is created.
     */
    public function init()
    {
        $types = $this->addElement(
            'select',
            'sourceType',
            array(
                'label'         => 'Image Source Type',
                'multiOptions'  => array_combine($this->_definedSources, $this->_definedSources),
                'value'         => $this->_defaultSource,
                'onChange'      => 
                        "var prefix = this.id.replace(/[^-]+-sourceType$/, '');"
                      . "p4cms.ui.hide(prefix + 'config-streamSourceTag',   {duration: 0});"
                      . "p4cms.ui.hide(prefix + 'config-streamSourceUser',  {duration: 0});"
                      . "p4cms.ui.hide(prefix + 'config-streamSourceGroup', {duration: 0});"
                      . "p4cms.ui.show(prefix + 'config-streamSource' + this.value);"
            )
        );

        // control visibility based on the selected source
        $this->addElement(
            'text',
            'sourceTag',
            array( 'label'      => 'Flickr Tags')
        );

        $this->addDisplayGroup(
            array('sourceTag'),
            'streamSourceTag',
            array('style'       => 'display: none;')
        );

        $this->addElement(
            'text',
            'sourceUser',
            array('label'       => 'Flickr Username')
        );

        $this->addDisplayGroup(
            array('sourceUser'),
            'streamSourceUser',
            array('style'       => 'display: none;')
        );

        $this->addElement(
            'text',
            'sourceGroup',
            array('label'       => 'Flickr Group Id')
        );

        $this->addDisplayGroup(
            array('sourceGroup'),
            'streamSourceGroup',
            array('style'       => 'display: none;')
        );

        $size = $this->addElement(
            'select',
            'imageSize',
            array(
                'label'         => 'Image Size',
                'multiOptions'  => $this->_definedSizes,
                'value'         => $this->_defaultImageSize
            )
        );

        $this->addElement(
            'checkbox',
            'showImageTitle',
            array(
                'label'         => 'Show Image Title',
                'value'         => $this->_defaultShowTitle
            )
        );

        $this->addElement(
            'text',
            'imageDelay',
            array(
                'label'         => 'Image Delay (seconds)',
                'value'         => $this->_defaultDelay,
                'validators'    => array(
                    'digits'
                )
            )
        );
    }

    /**
     * Whenever values are set on the form, show/hide the appopriate source key fieldset.
     *
     * @param   array   $defaults           the values to populate the form
     * @return  Widget_Form_StreamWidget     provides fluent interface
     */
    public function setDefaults(array $defaults)
    {
        if (isset($defaults['config']['sourceType'])) {
            $fieldsetName = 'streamSource' . $defaults['config']['sourceType'];
            $this->getDisplayGroup($fieldsetName)->setAttrib('style', 'display: block;');
        } else {
            $fieldsetName = 'streamSource' . $this->_defaultSource;
            $this->getDisplayGroup($fieldsetName)->setAttrib('style', 'display: block;');
        }

        return parent::setDefaults($defaults);
    }

    /**
     * Ensures that the appopriate data is entered, depending on the selected image source.
     *
     * @param array     $data   the array of form submission data to validate
     * @return boolean          whether or not $data is valid
     */
    public function isValid($data)
    {
        if (!parent::isValid($data)) {
            return false;
        }

        $sourceKey = 'source' . $data['config']['sourceType'];
        if (empty($data['config'][$sourceKey])) {
            $this->getElement($sourceKey)->addError(
                "You must enter a " . $this->getElement($sourceKey)->getLabel() . "."
            );
            return false;
        }
        return true;
    }
}
