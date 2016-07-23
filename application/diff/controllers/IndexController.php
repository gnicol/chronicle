<?php
/**
 * Compares registered record types and presents differences.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Diff_IndexController extends Zend_Controller_Action
{
    public $contexts = array(
        'index'     => array('partial'),
        'compare'   => array('partial'),
    );

    /**
     * Render diff viewer.
     *
     * @publishes   p4cms.diff.options
     *              Modify the passed options to influence the diff results.
     *              P4Cms_Navigation            $options    A menu to hold diff actions.
     *              P4Cms_Record_RegisteredType $type       The type of record being diffed.
     *              P4Cms_Record                $left       The record displayed on the left side of
     *                                                      the diff.
     *              P4Cms_Record                $right      The record displayed on the right side
     *                                                      of the diff.
     */
    public function indexAction()
    {
        // force a partial context.
        $this->contextSwitch->initContext('partial');

        // fetch records - request specifies record type and left/right ids.
        $request = $this->getRequest();
        $type    = P4Cms_Record_RegisteredType::fetch($request->getParam('type'));
        $options = array('includeDeleted' => true);
        $left    = $type->fetchRecord($request->getParam('left'), $options);
        $right   = $type->fetchRecord($request->getParam('right'), $options);

        // allow third-parties to incluence diff options.
        $options = new P4Cms_Diff_OptionsCollection;
        P4Cms_PubSub::publish('p4cms.diff.options', $options, $type, $left, $right);

        // compare left and right, assign result to view.
        $diff    = new P4Cms_Diff;
        $results = $diff->compareModels($left, $right, $options);
        $this->view->diffResults = $results;
        $this->view->leftRecord  = $left;
        $this->view->rightRecord = $right;

        // get associated p4 file and change info
        $this->view->leftFile    = $left->toP4File();
        $this->view->leftChange  = $this->view->leftFile->getChange();
        $this->view->rightFile   = $right->toP4File();
        $this->view->rightChange = $this->view->rightFile->getChange();

        // determine if there are any differences to report
        $this->view->noDiffs = true;
        foreach ($results as $field => $diff) {
            if ($diff->hasDiffs()) {
                $this->view->noDiffs = false;
                break;
            }
        }
    }
}