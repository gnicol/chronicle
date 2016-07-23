<?php
/**
 * Extends Zend_Form_Element_Captcha. Our version extends the
 * render method to abort page caching and attempts to ignore
 * any new sessions variables to protect future requests.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_Element_Captcha extends Zend_Form_Element_Captcha
{
    /**
     * Extends render to cancel caching and ignore any new
     * session variables.
     *
     * @param   Zend_View_Interface     $view   the view we are rendering under
     * @return  string                  the result
     */
    public function render(Zend_View_Interface $view = null)
    {
        // simply return parent if no page cache is in use
        if (!P4Cms_Cache::canCache('page')) {
            return parent::render($view);
        }

        // we have a page cache; determine starting values
        // and cancel the caching of this request
        $existing = array_keys($_SESSION);
        $cache    = P4Cms_Cache::getCache('page');
        $cache->cancel();

        // let parent do the rendering and add any session variables
        $value = parent::render($view);

        // detect any new session variables and ignore them
        $added = array_diff(array_keys($_SESSION), $existing);
        foreach ($added as $ignored) {
            $cache->addIgnoredSessionVariable($ignored);
        }

        return $value;
    }
}