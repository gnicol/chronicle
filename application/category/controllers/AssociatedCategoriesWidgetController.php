<?php
/**
 * A widget that displays the categories associated
 * with content, when content is being displayed.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Category_AssociatedCategoriesWidgetController extends P4Cms_Widget_ControllerAbstract
{
    /**
     * Display the text stored in the widget.
     */
    public function indexAction()
    {
        $this->view->display = false;
        $this->view->preamble = $this->getOption('preamble');
        $this->view->categories = array();

        $context = $this->widgetContext->getValues();
        if ($context and array_key_exists('contentId', $context)) {
            $categories = Category_Model_Category::fetchAllByEntry($context['contentId']);
            $this->view->categories = $categories;
            $this->view->display = true;
        }
    }

    /**
     * Get config sub-form to present additional options when
     * configuring a widget of this type.
     *
     * @param   P4Cms_Widget            $widget     the widget instance being configured.
     * @return  Zend_Form_SubForm|null  the sub-form to integrate into the default
     *                                  widget config form or null for no sub-form.
     */
    public static function getConfigSubForm($widget)
    {
        return new Category_Form_AssociatedCategoriesWidget;
    }
}
