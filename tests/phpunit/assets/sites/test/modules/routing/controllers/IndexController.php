<?php
/**
 * A mock implementation of an index controller.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Routing_IndexController
    extends     Zend_Controller_Action
{
    /**
     * A mock index action.
     *
     * @return null we don't actually do anything.
     */
    public function indexAction()
    {
        return;
    }

    /**
     * A mock test action.
     *
     * @return null we don't actually do anything.
     */
    public function testAction()
    {
        return;
    }
}
