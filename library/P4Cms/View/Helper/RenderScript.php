<?php
/**
 * View helper to render an arbitrary script.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_RenderScript extends Zend_View_Helper_Abstract
{
    /**
     * Render the given script. Filename must be fully qualified.
     *
     * @param   string  $filename   the full path to the script to render.
     * @return  string  the result of rendering the given script.
     */
    public function renderScript($filename)
    {
        $view = clone $this->view;
        $view->addScriptPath(dirname($filename));
        return $view->render(basename($filename));
    }
}
