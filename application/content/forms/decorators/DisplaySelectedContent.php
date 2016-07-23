<?php
/**
 * A simple wrapper around the content list view helper. For use with
 * the content select element and content editing (allows content
 * select elements to render a list of content when used with content).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        render single select as div not list
 */
class Content_Form_Decorator_DisplaySelectedContent extends Zend_Form_Decorator_Abstract
{
    /**
     * Renders the value (a list of content ids) using the content
     * list helper. Any options set on the decorator will be passed
     * through to the list helper.
     *
     * @param   string  $content  The content to render.
     * @return  string  the result of the content list view helper.
     */
    public function render($content)
    {
        $element = $this->getElement();
        $value   = $element::extractIds($element->getValue());
        $view    = $element->getView();
        $helper  = $view->getHelper('contentList');
        $query   = new P4Cms_Record_Query;

        // limit result to just the selected content.
        $query->setIds($value);

        // default is to sort entries in the order they were selected.
        $options = $this->getOptions();
        $options['postSort'] = isset($options['postSort'])
            ? $options['postSort']
            : array('id' => array(P4Cms_Model_Iterator::SORT_FIXED => $value));

        return $helper->contentList($query, $options)->render();
    }
}
