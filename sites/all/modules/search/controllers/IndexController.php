<?php
/**
 * Searches the index and displays results.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Search_IndexController extends Zend_Controller_Action
{
    public $contexts = array(
        'index' => array('json' => 'get', 'partial'),
    );

    /**
     * Handles the search -- either show a search form or search results.
     *
     * @publishes   p4cms.search.results
     *              Return the passed search results after applying any filters.
     *              Zend_Search_Lucene_Search_QueryHit  $results    The list of search results.
     */
    public function indexAction()
    {
        // enforce permissions.
        $this->acl->check('search', 'access');

        // get the search form.
        $request = $this->getRequest();
        $url     = $this->getHelper('url')->url(array('module' => 'search'));
        $form    = new Search_Form_Basic;
        $form->setAction($url)
             ->setIdPrefix($request->getParam('formIdPrefix'))
             ->populate($request->getParams());
        $this->view->form = $form;

        // if no query, we're all done!
        if (!$query = $request->getParam('query')) {
            return;
        }

        // start the clock.
        $start = microtime(true);

        // attempt to search the index - don't let lucene
        // exceptions stop us - just surface the error.
        $hits = array();
        try {
            $hits = Search_Module::find($query);
        } catch (Zend_Search_Lucene_Exception $e) {
            $this->view->error = $e->getMessage();
        }

        // stop the clock.
        $lapse = microtime(true) - $start;

        // we need to filter results by acl, we do this here
        // because acl is a generic facility - other types of
        // filtering should probably happen via pub/sub.
        $results = array();
        foreach ($hits as $hit) {
            $document   = $hit->getDocument();
            $fields     = $document->getFieldNames();
            $resource   = in_array('resource', $fields)
                ? $document->getFieldValue('resource')
                : null;
            $privilege  = in_array('privilege', $fields)
                ? $document->getFieldValue('privilege')
                : null;

            // if no resource set, or acl passes, include this hit.
            if (!$resource || $this->acl->isAllowed($resource, $privilege)) {
                $results[] = $hit;
            }
        }

        // give third parties a change to influence the results.
        // this is done via the 'filter' technique of pub/sub whereby the first
        // argument passed to each subscriber is the return value of the last.
        $results = P4Cms_PubSub::filter(
            'p4cms.search.results',
            $results
        );

        // paginate results.
        $paginator = Zend_Paginator::factory($results);
        $paginator->setCurrentPageNumber($request->getParam('page', 1));
        $paginator->setItemCountPerPage(10);

        $this->view->time       = $lapse;
        $this->view->query      = $query;
        $this->view->results    = $results;
        $this->view->paginator  = $paginator;
    }
}
