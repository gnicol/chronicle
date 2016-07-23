<?php
/**
 * Provides a contract for decorators to be enhanced for
 * use with content records:
 *
 *  - Specifies methods to associate a decorator with a content
 *    record. This is useful when rendering display decorators
 *    for arbitrary elements as it allows decorators to access the 
 *    content record and pull out other information (e.g. a view link
 *    when decorating a title element).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
interface P4Cms_Content_EnhancedDecoratorInterface
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
}
