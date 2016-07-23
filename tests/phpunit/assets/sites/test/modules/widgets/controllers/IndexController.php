<?php
/**
 * Provides a menu widget for use in regions.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Widgets_IndexController extends P4Cms_Widget_ControllerAbstract
{
    public  $contexts = array(
        'root-options'  => array('partial')
    );

    /**
     * Display the widget
     */
    public function indexAction()
    {
        // make the options available to the view.
        $options = $this->getOptions();
        $this->view->widgetOptions = $options;
    }
}
