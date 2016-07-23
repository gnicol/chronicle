<?php
/**
 * This is the category form to display while editing content.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Category_Form_Content extends P4Cms_Form_SubForm
{
    /**
     * Defines the elements that make up the content config form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        // set the title of this form.
        $this->setLegend('Categories');

        // add a field to pick the content categories.
        // take the flat list of all categories and transform it into
        // a multi-dimensional array of options suitable for use with 
        // the nested checkbox
        $options    = array();
        $categories = Category_Model_Category::fetchAll();
        foreach ($categories as $category) {

            // explode parent id into ancestors if we have a parent.
            $ancestors = $category->getDepth()
                ? explode("/", $category->getParentId())
                : array();

            // build out the heirarchy of array keys for this
            // category, advancing the option pointer as we go.
            $option =& $options;
            foreach ($ancestors as $ancestor) {
                $key = $ancestor . "/";
                if (!array_key_exists($key, $option)) {
                    $option[$key] = array();
                }
                $option =& $option[$key];
            }
            $option[$category->getId()] = $category->getTitle();
        }

        $this->addElement(
            'NestedCheckbox',
            'categories',
            array(
                'multiOptions'  => $options,
                'emptyText'     => 'No Categories'
            )
        );

        // add a button to add more categories.
        // (if current user has permission to add categories)
        $user = P4Cms_User::fetchActive();
        if ($user->isAllowed('categories', 'add')) {
            $router     = Zend_Controller_Front::getInstance()->getRouter();
            $formHref   = $router->assemble(
                array(
                    'module'        => 'category',
                    'controller'    => 'manage',
                    'action'        => 'add',
                    'format'        => 'partial',
                    'short'         => true,
                    'formIdPrefix'  => 'addCategoryDialog' . $this->getIdPrefix()
                )
            );
            $this->addElement(
                'TooltipDialogButton',
                'addCategory',
                array(
                    'label'     => 'Add Category',
                    'href'      => $formHref,
                    'class'     => 'add-button',
                    'ignore'    => true
                )
            );
        }
    }
}
