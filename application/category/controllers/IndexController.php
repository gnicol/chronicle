<?php
/**
 * Manages content operations (e.g. add).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Category_IndexController extends Zend_Controller_Action
{
    /**
     * Produce a presentation for the specified category;
     */
    public function indexAction()
    {
        // enforce permissions.
        $this->acl->check('categories', 'access');

        $view    = $this->view;
        $request = $this->getRequest();
        $id      = $request->getParam('category');

        // if a category is specified, display it; otherwise, start at root.
        if (strlen($id)) {
            try {
                $category = Category_Model_Category::fetch($id);
            } catch (P4Cms_Model_NotFoundException $e) {
                return $this->_forward('page-not-found', 'index', 'error');
            }

            // if the category specifies a valid content entry for its index
            // forward to the view action of the content controller.
            if ($category->hasIndexContent()) {
                return $this->_forward(
                    'view',
                    'index',
                    'content',
                    array('id' => $category->getValue('indexContent'))
                );
            }

            // populate view with current category and its contents.
            $view->category     = $category;
            $view->categories   = $category->getChildren();
            $options            = array(Category_Model_Category::OPTION_DEREFERENCE => true);
            $view->entries      = $category->getEntries($options);
            $view->headTitle()->set($category->getTitle());

        } else {
            $query = new P4Cms_Record_Query;
            $query->addPath('*')
                  ->setSortBy('title');

            $view->categories = Category_Model_Category::fetchAll($query);
            $view->headTitle()->set('Categories');
        }
    }
}
