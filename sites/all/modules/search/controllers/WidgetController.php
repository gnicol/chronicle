<?php
/**
 * Puts the basic search form in a region.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Search_WidgetController extends P4Cms_Widget_ControllerAbstract
{
    /**
     * Display basic search form.
     */
    public function indexAction()
    {
        $request = $this->getRequest();

        // get the search form.
        $form = new Search_Form_Basic;
        $form->setIdPrefix($this->_getWidget()->getId() . "-");
        $form->setAction($this->_helper->url('index', 'index', 'search'));
        $form->populate($request->getParams());
        $this->view->form = $form;
    }
}
