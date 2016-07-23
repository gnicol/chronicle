<?php
/**
 * Processes application's periodic tasks.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Cron_IndexController extends Zend_Controller_Action
{
    public $contexts = array(
        'index' => array('json')
    );

    /**
     * Run cron to process application's periodic tasks.
     */
    public function indexAction()
    {
        // explicitly set json context as thats all we provide
        $this->contextSwitch->initContext('json');

        // if background is set, close the session early but
        // continue running the cron checks and tasks
        $background = $this->getRequest()->getParam('background');
        if ($background) {
            $this->getHelper('browserDisconnect')->disconnect(null, 0);
        } else {
            // force json context
            $this->contextSwitch->initContext('json');
        }

        // run cron and pass returned report to the view
        $this->view->report = Cron_Model_Cron::run();
    }
}