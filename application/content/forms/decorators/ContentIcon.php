<?php

/**
 * This decorator extends the htmltag decorator and implements the enhanced decorator interface
 * to add the icon from a content type when decorating content.  It introduces the 
 * content-type-icon-small css class, used to style smaller content type icons in a consistent
 * manner.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_Decorator_ContentIcon 
    extends Zend_Form_Decorator_HtmlTag 
    implements P4Cms_Content_EnhancedDecoratorInterface
{
    protected $_tag             = 'img';
    protected $_contentRecord   = null;
    protected $_cssClass        = 'content-type-icon';
 
    /**
     * Gets the uri of the icon from the content type of the entry
     * then renders using the parent method by setting applicable options.
     * If no entry is provided, does not perform render.
     * 
     * @param string    $content    The content to decorate.
     * @return string   The decorated content.
     */
    public function render($content)
    {
        if (!$this->getContentRecord()) {
            return $content;
        }
        
        $type    = $this->getContentRecord()->getContentType();
        $view    = $this->getElement()->getView();
        $iconUrl = $view->url(
            array (
                'module'        => 'content',
                'controller'    => 'type',
                'action'        => 'icon',
                'id'            => $type->getId()
            )
        );

        $this->setOption('class',  $this->_cssClass);
        $this->setOption('src',    $iconUrl);
        
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
     * @param  P4Cms_Content   $content     the associated content record for this element.
     * @return Content_Form_Decorator_ContentLink   return this instance, for chaining
     */
     public function setContentRecord($content)
     {
         $this->_contentRecord = $content;
         return $this;
     }
}