<?php
/**
 * Auto applys the pinboard display decorator to an otherwise
 * standard text element. This has the added bonus of making
 * the pinboard elements identifiable to javascript.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Pinterest_Form_Element_Pinboard
    extends Zend_Form_Element_Text
    implements P4Cms_Content_EnhancedElementInterface
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
     * @param  P4Cms_Content  $content  the associated content record for this element.
     */
     public function setContentRecord($content)
     {
         $this->_content = $content;
         return $this;
     }

    /**
     * Get the default decorators to use when rendering content
     * element values of this type for display.
     *
     * @return  array   decorators configuration array suitable for passing
     *                  to element setDecorators().
     */
    public function getDefaultDisplayDecorators()
    {
        return array(
            array(
                'decorator' => 'Pinboard'
            )
        );
    }
}