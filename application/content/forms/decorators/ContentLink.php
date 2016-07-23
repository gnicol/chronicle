<?php
/**
 * Extends Zend_Form_Decorator_HtmlTag to make it aware of content records.
 * This allows the decorator to create links to view the content.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_Decorator_ContentLink
    extends Zend_Form_Decorator_HtmlTag 
    implements P4Cms_Content_EnhancedDecoratorInterface
{
    protected $_tag             = 'a';
    protected $_contentRecord   = null;

    /**
     * Sets the href option to the uri of the content record, 
     * then renders using the parent method.
     * 
     * @param string    $content    The content to decorate.
     * @return string               The decorated content.
     */
    public function render($content)
    {
        $this->setOption('href', $this->getContentRecord()->getUri());
        
        return parent::render($content);
    }
    
    /**
     * Get the associated content record (if set).
     * 
     * @return  null|P4Cms_Content  the associated content record or null if none set.
     */
    public function getContentRecord()
    {
        return $this->_contentRecord;
    }

    /**
     * Set the associated content record for this element.
     *
     * @param  P4Cms_Content                        $content    the associated content record for this element.
     * @return Content_Form_Decorator_ContentLink               return this instance, for chaining
     */
     public function setContentRecord($content)
     {
         $this->_contentRecord = $content;
         return $this;
     }
}
