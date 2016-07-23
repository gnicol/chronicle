<?php
/**
 * Extend the stock display selected content decorator to force higher
 * res image options when rendering the 'gallery' content type.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Theme_Decorator_DisplaySelectedContent extends Content_Form_Decorator_DisplaySelectedContent
    implements P4Cms_Content_EnhancedDecoratorInterface
{
    protected   $_content   = null;

    /**
     * Get the associated content record (if set).
     *
     * @return  null|P4Cms_Content  the associated content record or null if none set.
     */
    public function getContentRecord()
    {
        return $this->_content;
    }

    /**
     * Set the associated content record for this element.
     *
     * @param  P4Cms_Content|null   $content    the associated content record for this element.
     */
    public function setContentRecord($content)
    {
        $this->_content = $content;
        return $this;
    }

    /**
     * Retrieve options
     * Extend parent to force higher res output for the gallery type.
     * The first image is put out with a max of 1000 x 1000 and all
     * later images are put with a max of 500 x 500.
     *
     * @return array
     */
    public function getOptions()
    {
        if (!$this->getContentRecord()
            || $this->getContentRecord()->getContentType()->getId() !== 'gallery'
        ) {
            return parent::getOptions();
        }

        $options = parent::getOptions();
        $options['rowOptions'] = array(
            1 => array(
                'fields' => array(
                    'file'  => array(
                        'decorators'    => array(
                            array(
                                'decorator' => 'DisplayImage',
                                'options'   => array(
                                    'maxWidth'  => 1000,
                                    'maxHeight' => 1000,
                                    'link'      => true,
                                    'target'    => '_lightbox'
                                )
                            )
                        )
                    )
                )
            )
        );

        $options['fields'] = array(
            'file'  => array(
                'decorators'    => array(
                    array(
                        'decorator' => 'DisplayImage',
                        'options'   => array(
                            'maxWidth'  => 500,
                            'maxHeight' => 500,
                            'link'      => true,
                            'target'    => '_lightbox'
                        )
                    )
                )
            )
        );

        return $options;
    }
}
