<?php
/**
 * Implements collection of system information for display.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class System_Model_Info extends P4Cms_Model
{
    protected static $_fields = array(
        'title',
        'content',
        'order'
    );

    /**
     * Set the default view, then call parent as PHP does not let you reference
     * constants while declaring class member variables.
     *
     * @param array $values     Values to set for this model.
     */
    public function __construct($values = null)
    {
        static::$_fields['view'] = array(
            'default' => APPLICATION_PATH . '/system/views/scripts/default-info.phtml'
        );

        parent::__construct($values);
    }

    /**
     * Allows specific view scripts for different types of information by
     * setting the view script path and name off of the provided (or default)
     * view script for the object.
     *
     * @param  string $view     The view to render for this model.
     * @return string           The rendered view.
     */
    public function render($view)
    {
        $view = clone $view;

        $view->setScriptPath(dirname($this->getValue('view')));
        $view->content = $this->getValue('content');

        return $view->render(basename($this->getValue('view')));
    }
}
