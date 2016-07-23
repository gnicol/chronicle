<?php
/**
 * Displays comments and a comment form.
 *
 * Comments are organized by path; you must specified the path to
 * the set of comments you want to display (e.g. 'content/123').
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Comment_View_Helper_Comments extends Zend_View_Helper_Abstract
{
    /**
     * Render comments for the given path and show a comment form.
     * Options can influence whether or not comments are displayed
     * and whether or not the comment form appears.
     *
     * @param   string  $path       the path to the set of comments to display
     * @param   array   $options    optional - options to control:
     *                                  allowComments - if new comments can be posted
     *                               requireLoginPost - if login required to post comments
     *                                requireApproval - if comments are to be pended first
     *                                   showComments - if existing comments are shown
     *                               requireLoginView - if login required to view comments
     *                                    allowVoting - enable up/down voting of comments
     *                                 oneVotePerUser - limit users to one vote per comment
     *                                                  (and no anonymous voting)
     *
     * @param   string  $template   optional - name of template file to render to.
     * @return  string  the rendered comments and comment form.
     */
    public function comments($path, array $options = null, $template = 'comments.phtml')
    {
        // normalize options against default values.
        $options = Comment_Form_Content::getNormalizedOptions($options);
        
        // if we're not showing or allowing comments, nothing to do.
        if (!$options['allowComments'] && !$options['showComments']) {
            return;
        }

        // determine if we have an anonymous user
        // so that we can enforce require-login options.
        $anonymous = P4Cms_User::hasActive()
            ? P4Cms_User::fetchActive()->isAnonymous()
            : true;

        // determine if we should show comments.
        $showComments = $options['showComments'];
        if ($options['requireLoginView'] && $anonymous) {
            $showComments = false;
        }

        // determine if we should allow new comments.
        $allowComments = $options['allowComments'];
        if ($options['requireLoginPost'] && $anonymous) {
            $allowComments = false;
        }
        
        // determine if we should show/allow votes on comments.
        // if one vote per user, disallow anonymous voting.
        $showVotes       = $options['allowVoting'];
        $allowVoting     = $options['allowVoting'];
        $oneVotePerUser  = $options['oneVotePerUser'];
        $votedCommentIds = array();
        
        // if there is only one vote allowed per user, and we have
        // an authenticated user, build a list of all the comments
        // the user voted on -- if the user is anonymous, no voting.
        if ($oneVotePerUser && !$anonymous) {
            $votedComments = Comment_Model_Comment::fetchVotedComments(
                P4Cms_User::fetchActive()->getId(), 
                $path
            );
            $votedCommentIds = $votedComments->invoke('getId');
        } else if ($oneVotePerUser) {
            $allowVoting = false;
        }

        // if we're showing comments, fetch them.
        $comments = null;
        if ($showComments) {
            $filter   = new P4Cms_Record_Filter;
            $filter->add('status', Comment_Model_Comment::STATUS_APPROVED);
            $comments = Comment_Model_Comment::fetchAll(
                array(
                    'paths'  => array($path . '/...'),
                    'filter' => $filter
                )
            );

            // comments with most votes first, post-time is the tie-breaker.
            $numeric    = P4Cms_Model_Iterator::SORT_NUMERIC;
            $descending = P4Cms_Model_Iterator::SORT_DESCENDING;
            $ascending  = P4Cms_Model_Iterator::SORT_ASCENDING;
            $comments->sortBy(
                array(
                    'votes'     => array($numeric, $descending),
                    'postTime'  => array($numeric, $ascending)
                )
            );
        }

        // render comments via template in private scope
        // to avoid polluting the primary view object.
        $view = $this->view;
        $view->addScriptPath(dirname(__DIR__) . '/scripts');
        return $view->partial(
            $template,
            array(
                'path'              => $path,
                'comments'          => $comments,
                'showComments'      => $showComments,
                'allowComments'     => $allowComments,
                'showVotes'         => $showVotes,
                'allowVoting'       => $allowVoting,
                'oneVotePerUser'    => $oneVotePerUser,
                'votedCommentIds'   => $votedCommentIds
            )
        );
    }
}
