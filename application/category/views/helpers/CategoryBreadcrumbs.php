<?php
/**
 * Specialized breadcrumb helper that takes a category and produces breadcrumbs for it.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Category_View_Helper_CategoryBreadcrumbs extends Zend_View_Helper_Navigation_Breadcrumbs
{
    /**
     * Render breadcrumbs for the given category.
     *
     * @param   Category_Model_Category     $category   the category to render breadcrumbs for.
     * @return  string                      rendered breadcrumbs for the given category.
     */
    public function categoryBreadcrumbs($category)
    {
        // prepare breadcrumbs.
        $breadcrumbs = new Zend_Navigation(
            array(
                array(
                    'label'         => 'Categories',
                    'module'        => 'category',
                    'controller'    => 'index',
                    'action'        => 'index',
                    'active'        => true
                )
            )
        );

        // add category ancestry to breadcrumbs.
        if ($category instanceof Category_Model_Category) {
            $container = $breadcrumbs->current();
            $crumbs    = $category->getAncestors();
            $crumbs[]  = $category;
            foreach ($crumbs as $crumb) {
                $page = Zend_Navigation_Page::factory(
                    array(
                        'uri'           => $crumb->getUri(),
                        'label'         => $crumb->getTitle(),
                        'active'        => true,
                    )
                );

                // add page to parent container and advance container pointer.
                $container->addPage($page);
                $container = $page;
            }
        }
        return parent::breadcrumbs($breadcrumbs)->setSeparator('<span class="separator"><span>&gt;</span></span>');
    }
}
