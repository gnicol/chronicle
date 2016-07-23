<?php
/**
 * Enhances Redirector by providing minor tweaks (e.g. not render original
 * action if redirecting).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Controller_Action_Helper_Redirector
    extends Zend_Controller_Action_Helper_Redirector
{
    /**
     * Whether or not calls to _redirect() should exit script execution
     * Changed default to false so that actions will complete, allowing 
     * them to be logged by the audit action helper.
     *
     * @var boolean
     */
    protected $_exit = false;

    /**
     * Whether or not redirector is being used with unit tests and thus
     * not exit (as that would break tests).
     *
     * @internal
     * @var bool
     */
    public static $unitTestEnabled = false;    

    /**
     * Automatically initialize redirector prior to dispatch.
     */
    public function preDispatch()
    {
        // make the helper easier to access from the controller.
        $controller = $this->getActionController();
        if ($controller instanceof Zend_Controller_Action) {
            $controller->redirector = $this;
        }
        
        return parent::preDispatch();
    }

    /**
     * Extend parent object to not render the view for the original action if we're redirecting,
     * as it won't be displayed.
     *
     * @param string    $url    The url to redirect to.
     * @return void
     */
    protected function _redirect($url)
    {
        parent::_redirect($url);

        $controller = $this->getActionController();
        if ($controller instanceof Zend_Controller_Action) {
            $controller->getHelper('ViewRenderer')->setNoRender();
        }
    }

    /**
     * Extended to do nothing if unit testing.
     *
     * @return void
     */
    public function redirectAndExit()
    {
        if (!static::$unitTestEnabled) {
            parent::redirectAndExit();
        }
    }
}
