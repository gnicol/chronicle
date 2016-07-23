<?php
/**
 * Provides:
 *  - Comment post facility (receive new comments)
 *  - Ability to vote up/down existing comments
 *  - Interface to moderate comments
 *  - Ability to delete comments
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Comment_IndexController extends Zend_Controller_Action
{
    public $contexts = array(
        'post'      => array('partial', 'json'),
        'vote-up'   => array('json'),
        'vote-down' => array('json'),
        'moderate'  => array('json'),
        'delete'    => array('json')
    );

    /**
     * Provide a comment form and save them when posted.
     */
    public function postAction()
    {
        $view       = $this->view;
        $request    = $this->getRequest();
        $path       = $request->getParam('path');
        $user       = P4Cms_User::fetchActive();

        // setup form.
        $form       = new Comment_Form_Comment;
        $view->form = $form;
        $form->setDefault('path', $request->getParam('path'));
        $form->setAction(
            $this->getHelper('url')->url(
                array(
                    'module'        => 'comment',
                    'controller'    => 'index',
                    'action'        => 'post'
                )
            )
        );

        // can't post without a path.
        if (!$path) {
            throw new Comment_Exception(
                "Cannot post comments without specifying a comment path."
            );
        }

        // verify posts are allowed to this path.
        $options = $this->_getOptionsForPath($path);
        if (!$options['allowComments']
            || ($options['requireLoginPost'] && $user->isAnonymous())
        ) {
            $message  = $options['requireLoginPost'] ? "Anonymous comments " : "Comments ";
            $message .= "are not permitted on this content entry.";
            throw new P4Cms_AccessDeniedException($message);
        }

        // if not posted, all done.
        if (!$request->isPost()) {
            return;
        }

        // valid post, save the comment
        // otherwise, set errors on the view
        if ($form->isValid($request->getParams())) {
            $comment = new Comment_Model_Comment;
            $id      = trim($form->getValue('path'), '\\/') . '/'
                     . (string) new P4Cms_Uuid;

            // if explicit approval is required, mark as pending
            // otherwise, mark as approved automatically.
            $status = $options['requireApproval']
                ? Comment_Model_Comment::STATUS_PENDING
                : Comment_Model_Comment::STATUS_APPROVED;

            $comment->setValues($form->getValues())
                    ->setValue('user',     $user->getId())
                    ->setValue('postTime', time())
                    ->setValue('status',   $status)
                    ->setId($id)
                    ->save();

        } else {
            $view->errors = $form->getMessages();
        }
    }

    /**
     * Vote up a given comment.
     */
    public function voteUpAction()
    {
        $this->_vote(true);
    }

    /**
     * Vote down a given comment.
     */
    public function voteDownAction()
    {
        $this->_vote(false);
    }

    /**
     * Moderate comments.
     *
     * @publishes   p4cms.comment.grid.actions
     *              Modify the passed menu (add/modify/delete items) to influence the actions shown
     *              on entries in the Moderate Comments grid.
     *              P4Cms_Navigation            $actions    A menu to hold grid actions.
     *
     * @publishes   p4cms.comment.grid.data.item
     *              Return the passed item after appling any modifications (add properties, change
     *              values, etc.) to influence the row values sent to the Moderate Comments grid.
     *              array                       $item       The item to potentially modify.
     *              mixed                       $model      The original object/array that was used
     *                                                      to make the item.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.comment.grid.data
     *              Adjust the passed data (add properties, modify values, etc.) to influence the
     *              row values sent to the Moderate Comments grid.
     *              Zend_Dojo_Data              $data       The data to be filtered.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     *
     * @publishes   p4cms.comment.grid.populate
     *              Adjust the passed query (possibly based on values in the passed form) to
     *              influence which comments will be shown on the Moderate Comments grid.
     *              P4Cms_Record_Query      $query          The query used to filter comments.
     *              P4Cms_Form_PubSubForm   $form           A form containing filter options.
     *
     * @publishes   p4cms.comment.grid.render
     *              Make adjustments to the datagrid helper's options pre-render (e.g. change
     *              options to add columns) for the Moderate Comments grid.
     *              Ui_View_Helper_DataGrid     $helper     The view helper that broadcast this
     *                                                      topic.
     */
    public function moderateAction()
    {
        // enforce permissions
        $this->acl->check('comments', 'moderate');

        // use the management layout (for traditional requests)
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->getHelper('layout')->setLayout('manage-layout');
            $this->getHelper('helpUrl')->setUrl('comments.manage.html');
        }

        // setup grid options form
        $request   = $this->getRequest();
        $namespace = 'p4cms.comment.grid';
        $form      = new Ui_Form_GridOptions(array('namespace' => $namespace));
        $form->populate($request->getParams());

        // collect the actions from interested parties
        $actions = new P4Cms_Navigation;
        P4Cms_PubSub::publish($namespace . '.actions', $actions);

        // setup view
        $view             = $this->view;
        $view->form       = $form;
        $view->actions    = $actions;
        $view->pageSize   = $request->getParam('count', 100);
        $view->rowOffset  = $request->getParam('start', 0);
        $view->pageOffset = round($view->rowOffset / $view->pageSize, 0) + 1;
        $view->headTitle()->set('Moderate Comments');

        // set data-grid view helper namespace
        $helper = $view->dataGrid();
        $helper->setNamespace($namespace);

        // early exit for standard requests
        if (!$this->contextSwitch->getCurrentContext()) {
            return;
        }

        // construct comment query.
        $query = new P4Cms_Record_Query;
        $query->setRecordClass('Comment_Model_Comment');

        // prepare sorting options
        $sortKey = $request->getParam('sort', 'postTime');
        if (substr($sortKey, 0, 1) == '-') {
            $query->setSortBy(
                substr($sortKey, 1),
                array(P4Cms_Record_Query::SORT_ASCENDING)
            );
        } else {
            $query->setSortBy(
                $sortKey,
                array(P4Cms_Record_Query::SORT_DESCENDING)
            );
        }

        // allow third-parties to influence query.
        try {
            P4Cms_PubSub::publish($namespace . '.populate', $query, $form);
        } catch (Exception $e) {
            P4Cms_Log::logException("Error building comments list.", $e);
        }

        // add query to the view.
        $view->query = $query;
    }

    /**
     * Delete a comment.
     */
    public function deleteAction()
    {
        // enforce permissions
        $this->acl->check('comments', 'moderate');

        $request = $this->getRequest();

        // require post request method.
        if (!$request->isPost()) {
            throw new P4Cms_AccessDeniedException(
                "Cannot delete comment. Request method must be http post."
            );
        }

        $comment = Comment_Model_Comment::fetch($request->getParam('id'));
        $comment->delete();

        // setup view.
        $this->view->comment = $comment;

        // add notification and redirect for traditional requests.
        if (!$this->contextSwitch->getCurrentContext()) {
            P4Cms_Notifications::add('Comment deleted.', P4Cms_Notifications::SEVERITY_SUCCESS);

            $this->redirector->gotoSimple('moderate');
        }
    }

    /**
     * Change the state of a comment.
     */
    public function statusAction()
    {
        // enforce permissions
        $this->acl->check('comments', 'moderate');

        // require post request method.
        $request = $this->getRequest();
        if (!$request->isPost()) {
            throw new P4Cms_AccessDeniedException(
                "Cannot change comment status. Request method must be http post."
            );
        }

        // update comment status.
        $state   = $request->getParam('state');
        $comment = Comment_Model_Comment::fetch($request->getParam('id'));
        $comment->setStatus($state)
                ->save("Changed comment status to '$state'.");

        // setup view.
        $this->view->comment = $comment;

        // add notification and redirect for traditional requests.
        if (!$this->contextSwitch->getCurrentContext()) {
            P4Cms_Notifications::add("Comment $state.", P4Cms_Notifications::SEVERITY_SUCCESS);

            $this->redirector->gotoSimple('moderate');
        }
    }

    /**
     * Vote a comment up or down. Shared by vote-up/down actions.
     *
     * @param   bool    $up     optional - vote up (defaults to true)
     *                          pass false to vote down.
     */
    protected function _vote($up = true)
    {
        $user    = P4Cms_User::fetchActive();
        $request = $this->getRequest();
        $comment = Comment_Model_Comment::fetch($request->getParam('id'));

        // check if voting is allowed.
        $options = $this->_getOptionsForPath(dirname($comment->getId()));
        if (!$options['allowVoting']) {
            throw new P4Cms_AccessDeniedException(
                "Voting is not permitted on this comment."
            );
        }

        // if one vote per-user, no anonymous voting.
        if ($options['oneVotePerUser'] && $user->isAnonymous()) {
            throw new P4Cms_AccessDeniedException(
                "Anonymous voting is not permitted."
            );
        }

        // only one vote per-user - if the user has already
        // voted on this comment, don't let them vote again
        if ($options['oneVotePerUser']) {
            $votedComments = Comment_Model_Comment::fetchVotedComments(
                P4Cms_User::fetchActive()->getId(),
                dirname($comment->getId())
            );

            $hasVoted = in_array($comment->getId(), $votedComments->invoke('getId'));

            if ($hasVoted) {
                throw new P4Cms_AccessDeniedException(
                    "Only one vote is allowed per-user, per-comment."
                );
            }
        }

        // up/down as appropriate.
        if ($up) {
            $comment->voteUp()->save('Vote up.');
        } else {
            $comment->voteDown()->save('Vote down.');
        }

        $this->view->comment = $comment;
        $this->view->options = $options;

        // redirect traditional requests.
        if (!$this->contextSwitch->getCurrentContext()) {
            $this->redirector->gotoUrl($request->getBaseUrl());
        }
    }

    /**
     * Get comment options for the given path. Options for content paths
     * are pulled from the identified content entry (if one exists).
     *
     * @param   string  $path   the comment path to get options for.
     * @return  array   normalized comment options array.
     */
    protected function _getOptionsForPath($path)
    {
        $options = array();

        // if this is a content path, we need to check the
        // content entry for comment settings.
        $matches = array();
        if (preg_match('#content/(.+)#', $path, $matches)) {
            $id       = $matches[1];
            $entry    = P4Cms_Content::fetch($id, array('includeDeleted' => true));
            $options  = $entry->getValue('comments');
        }

        return Comment_Form_Content::getNormalizedOptions($options);
    }
}
