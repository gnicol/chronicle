<?php
/**
 * Modelling and storage of comment entries.
 *
 * Comments are grouped by path. Each comment entry id should (typically)
 * be stored in a sub-folder under comments (e.g. comments/content/123)
 * where '123' is the id of the content entry the comment is associated with.
 *
 * We permit numeric ids when adding because we want to include numeric content
 * (or other) record ids as path components of the comment id.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Comment_Model_Comment extends P4Cms_Record
{
    const               STATUS_APPROVED     = 'approved';
    const               STATUS_PENDING      = 'pending';
    const               STATUS_REJECTED     = 'rejected';

    protected static    $_storageSubPath    = 'comments';
    protected static    $_idField           = 'id';
    protected static    $_fields            = array(
        'id',
        'user',
        'name',
        'comment',
        'postTime'  => array(
            'accessor'  => 'getPostTime',
            'mutator'   => 'setPostTime'
        ),
        'votes'     => array(
            'accessor'  => 'getVotes',
            'mutator'   => 'setVotes'
        ),
        'status'    => array(
            'mutator'   => 'setStatus'
        )
    );

    /**
     * Extend parent to join against users and expand user name
     * into full name and email where appropriate.
     *
     * @param   P4Cms_Record_Query|array|null   $query      optional - query options to augment result.
     * @param   P4Cms_Record_Adapter            $adapter    optional - storage adapter to use.
     * @return  P4Cms_Model_Iterator            all records of this type.
     */
    public static function fetchAll($query = null, P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $adapter  = $adapter ?: static::getDefaultAdapter();

        // let parent do initial fetch.
        $comments = parent::fetchAll($query, $adapter);

        // fill in full-name and email of users if we have any comments.
        // we do this because comments from authenticated users are
        // stored without the full-name/email, just the username.
        if (count($comments)) {
            $p4     = $adapter->getConnection();
            $result = $p4->run('users', $comments->invoke('getValue', array('user')));
            $lookup = array();
            foreach ($result->getData() as $user) {
                $lookup[$user['User']] = array(
                    'name'  => $user['FullName'],
                    'email' => $user['Email']
                );
            }
            foreach ($comments as $comment) {
                if (isset($comment->user) && isset($lookup[$comment->user])) {
                    $comment->setValues($lookup[$comment->user]);
                }
            }
        }

        return $comments;
    }

    /**
     * Get the recorded time this comment was posted.
     *
     * @return  int     the time this comment was allegedly posted.
     */
    public function getPostTime()
    {
        return intval($this->_getValue('postTime'));
    }

    /**
     * Explicitly set the time that this comment was posted.
     *
     * @param   int     $time           the time this comment was posted
     * @return  Comment_Model_Comment   provides fluent interface.
     */
    public function setPostTime($time)
    {
        return $this->_setValue('postTime', $time);
    }

    /**
     * Get the votes counted for this comment.
     *
     * @return  int     the number of votes for this comment.
     */
    public function getVotes()
    {
        return intval($this->_getValue('votes'));
    }

    /**
     * Explicitly set the number of votes for this comment
     *
     * @param   int     $votes          the number of votes this comment should have.
     * @return  Comment_Model_Comment   provides fluent interface.
     */
    public function setVotes($votes)
    {
        return $this->_setValue('votes', $votes);
    }

    /**
     * Adjust the number of votes on this comment up by one.
     *
     * @return  Comment_Model_Comment   provides fluent interface.
     */
    public function voteUp()
    {
        return $this->setVotes($this->getVotes() + 1);
    }

    /**
     * Adjust the number of votes on this comment down by one.
     *
     * @return  Comment_Model_Comment   provides fluent interface.
     */
    public function voteDown()
    {
        return $this->setVotes($this->getVotes() - 1);
    }

    /**
     * Set the status of this comment
     *
     * @param   string  $status             the state to set this comment to.
     * @return  Comment_Model_Comment       provides fluent interface.
     * @throws  InvalidArgumentException    if given status is not a valid state.
     */
    public function setStatus($status)
    {
        $states = array(
            static::STATUS_APPROVED,
            static::STATUS_PENDING,
            static::STATUS_REJECTED
        );

        if (!in_array($status, $states, true)) {
            throw new InvalidArgumentException(
                "Cannot set status of comment. Given status is not a valid state."
            );
        }

        return $this->_setValue('status', $status);
    }

    /**
     * Get all comments voted on by the given user under the given path.
     *
     * Any change that contains the word 'vote' in the description is
     * considered to be a vote. This is a rather fragile linkage. If the
     * change description for comment votes is altered, this will stop
     * working.
     *
     * Optimized to only lookup the comment ids. If you wish to read
     * other fields, you will incur a populate per comment.
     *
     * @param   string                  $user       the id of the user to fetch voted comments for.
     * @param   string                  $path       the path under which to fetch comments.
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  P4Cms_Model_Iterator    all comments voted on by the given user under the given path.
     *
     */
    public static function fetchVotedComments($user, $path, P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        // ensure path has no trailing slash.
        $path = rtrim($path, '/');

        // fetch all changes made by the given user against this path.
        $changes = P4_Change::fetchAll(
            array(
                P4_Change::FETCH_BY_USER     => $user,
                P4_Change::FETCH_BY_FILESPEC => static::getStoragePath() . '/' . $path . '/...'
            ),
            $adapter->getConnection()
        );

        // further limit changes to those with the word 'vote' in the description.
        $changes->filter(
            'Description',
            'Vote',
            array(
                P4_Model_Iterator::FILTER_NO_CASE,
                P4_Model_Iterator::FILTER_CONTAINS
            )
        );

        // if no changes, nothing more to query.
        if (!$changes->count()) {
            return new P4Cms_Model_Iterator;
        }

        // fetch comments affected by these changes.
        $ids      = array_map('strval', $changes->invoke('getId'));
        $filter   = new P4Cms_Record_Filter;
        $filter->addFstat('headChange', $ids);
        $comments = Comment_Model_Comment::fetchAll(
            array(
                'filter'      => $filter,
                'paths'       => array($path . '/...'),
                'limitFields' => 'depotFile'
            ),
            $adapter
        );

        return $comments;
    }
}
