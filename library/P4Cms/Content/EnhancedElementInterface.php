<?php
/**
 * Provides a contract for form elements to be enhanced for
 * use with content records:
 *
 *  - Specifies methods to associate an element with a content
 *    record. This is useful when rendering "display" decorators
 *    for an element as it allows decorators to access the content
 *    record and pull out other information (e.g. a download link
 *    when decorating a file element).
 *
 *  - Specifies method to provide default decorators to use when
 *    rendering content values for display. Not to be confused with
 *    standard form element decorators which render form inputs.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
interface P4Cms_Content_EnhancedElementInterface
{
    /**
     * Get the associated content record (if set).
     * 
     * @return  null|P4Cms_Content  the associated content record or null if none set.
     */
    public function getContentRecord();

    /**
     * Set the associated content record for this element.
     *
     * @param  P4Cms_Content  $content  the associated content record for this element.
     */
     public function setContentRecord($content);

    /**
     * Get the default decorators to use when rendering content
     * element values of this type for display.
     *
     * @return  array   decorators configuration array suitable for passing
     *                  to element setDecorators().
     */
    public function getDefaultDisplayDecorators();
}
