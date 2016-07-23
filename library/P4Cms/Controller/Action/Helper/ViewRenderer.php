<?php
/**
 * Enhances ViewRenderer to provide themeing capabilities.
 *
 * Themes can override the built-in view scripts by placing a custom
 * view script in the appropriate place inside the theme directory.
 * For example:
 *
 *  <theme-dir>/views/<module>/<controller>/<action>.phtml
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Controller_Action_Helper_ViewRenderer
    extends Zend_Controller_Action_Helper_ViewRenderer
{
    /**
     * Extend init to make views theme-able by adding the
     * the theme's (resolved) views directory to the path stack.
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        // if there is an active theme - add view and layout script paths.
        if (P4Cms_Theme::hasActive()) {

            // determine appropriate view scripts path for current request.
            $dispatcher = $this->getFrontController()->getDispatcher();
            $moduleName = $this->getRequest()->getModuleName();
            $theme      = P4Cms_Theme::fetchActive();
            $path       = $theme->getViewsPath();
            if ($moduleName) {
                $path .= "/" . lcfirst($dispatcher->formatModuleName($moduleName));
            }

            // add theme's view scripts path if needed.
            if (!in_array($path . "/", $this->view->getScriptPaths())
                && is_dir($path)
            ) {
                $this->view->addScriptPath($path);
            }
        }
    }
}
