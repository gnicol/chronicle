<?php
/**
 * A specialized version of the content select element that is
 * pre-configured to work well for selecting and displaying images.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_Element_ImageSelect extends Content_Form_Element_ContentSelect
    implements  P4Cms_Content_EnhancedElementInterface
{
    /**
     * Filter browse dialog for image content by default.
     *
     * @param   mixed   $spec       zend provides no documentation for this param
     * @param   mixed   $options    zend provides no documentation for this param
     */
    public function __construct($spec, $options)
    {
        parent::__construct($spec, $options);

        if (!$this->getAttrib('browseOptions')) {
            $this->setAttrib('browseOptions', array('type' => array('types' => array("Assets/image"))));
        }
    }

    /**
     * Get the default display decorators to use when rendering
     * content elements of this type.
     *
     * Image display options can be influenced without redeclaring the
     * decorators via 'display.image'. For example:
     *
     *   gallery.display.image.height = 150
     *   gallery.display.image.link   = true
     *   gallery.display.image.target = _lightbox
     *
     * @return  array   decorators configuration array suitable for passing
     *                  to element setDecorators().
     */
    public function getDefaultDisplayDecorators()
    {
        // extract display options from content element definition
        // if this form element is being used in a content type.
        $options = array();
        if ($this->getContentRecord()) {
            $content = $this->getContentRecord();
            $element = $content->getContentType()->getElement($this->getName());
            $options = isset($element['display']['image'])
                ? (array) $element['display']['image']
                : $options;
        }

        return array(
            array(
                'decorator' => 'DisplaySelectedContent',
                'options'   => array(
                    'emptyMessage'  => '',
                    'fields'        => array(
                        'file'      => array(
                            'decorators' => array(
                                array(
                                    'decorator' => 'DisplayImage',
                                    'options'   => $options
                                )
                            )
                        )
                    )
                )
            )
        );
    }
}
