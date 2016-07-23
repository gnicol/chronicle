<?php
/**
 * The comment module provides a comment facility that is integrated with
 * content. Each content entry may specify comment options to control
 * whether or not comments are allowed or should be displayed. Additionally,
 * each entry may specify if login is required to post or to view comments.
 *
 * The comment facility is generic such that comments can be read-from
 * and written-to arbitrary paths. In this way, comments may be associated
 * with other entities in the system.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Comment_Module extends P4Cms_Module_Integration
{
    /**
     * When this module loads, subscribe to content rendering to
     * render comments with content entries where appropriate.
     * Also subscribes to content editing (forms) to include options
     * to control comments on each content entry.
     */
    public static function load()
    {
        P4Cms_PubSub::subscribe('p4cms.content.render.close',
            function($html, $helper)
            {
                $entry = $helper->getEntry();

                // if we don't have an entry id or the entry being rendered
                // isn't the default we won't append comments
                if (!$entry->getId()
                    || $entry->getId() != $helper->getDefaultEntry()->getId()
                ) {
                    return $html;
                }

                // let comment view helper take care of the rest.
                $html = $helper->getView()->comments(
                    "content/" . $entry->getId(),
                    (array) $entry->getValue('comments')
                ) . $html;

                return $html;
            }
        );

        // participate in content editing by providing a subform.
        P4Cms_PubSub::subscribe('p4cms.content.form.subForms',
            function(Content_Form_Content $form)
            {
                return new Comment_Form_Content(
                    array(
                        'name'  => 'comments',
                        'order' => -10
                    )
                );
            }
        );

        // provide comment grid (moderate) actions
        P4Cms_PubSub::subscribe('p4cms.comment.grid.actions',
            function($actions)
            {
                $actions->addPages(
                    array(
                        array(
                            'label'     => 'Approve',
                            'onClick'   => 'p4cms.comment.grid.Actions.onClickApprove();',
                            'onShow'    => 'p4cms.comment.grid.Actions.onShowApprove(this);',
                            'order'     => '10'
                        ),
                        array(
                            'label'     => 'Reject',
                            'onClick'   => 'p4cms.comment.grid.Actions.onClickReject();',
                            'onShow'    => 'p4cms.comment.grid.Actions.onShowReject(this);',
                            'order'     => '20'
                        ),
                        array(
                            'label'     => 'Pend',
                            'onClick'   => 'p4cms.comment.grid.Actions.onClickPend();',
                            'onShow'    => 'p4cms.comment.grid.Actions.onShowPend(this);',
                            'order'     => '30'
                        ),
                        array(
                            'label'     => 'Delete',
                            'onClick'   => 'p4cms.comment.grid.Actions.onClickDelete();',
                            'order'     => '40'
                        ),
                        array(
                            'label'     => '-',
                            'onShow'    => 'p4cms.comment.grid.Actions.onShowView(this);',
                            'order'     => '50'
                        ),
                        array(
                            'label'     => 'View Content Entry',
                            'onClick'   => 'p4cms.comment.grid.Actions.onClickView();',
                            'onShow'    => 'p4cms.comment.grid.Actions.onShowView(this);',
                            'order'     => '60'
                        ),
                    )
                );
            }
        );

        // provide form to search comments
        P4Cms_PubSub::subscribe('p4cms.comment.grid.form.subForms',
            function(Zend_Form $form)
            {
                return new Ui_Form_GridSearch;
            }
        );

        // filter comment list by search term
        P4Cms_PubSub::subscribe('p4cms.comment.grid.populate',
            function(P4Cms_Record_Query $query, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract search query.
                $keywords = isset($values['search']['query'])
                    ? $values['search']['query']
                    : null;

                // early exit if no search text.
                if (!$keywords) {
                    return;
                }

                // add a text search filter to the comment query.
                $filter = new P4Cms_Record_Filter;
                $fields = array('comment', 'user', 'name');
                $filter->addSearch($fields, $keywords);
                $query->addFilter($filter);
            }
        );

        // provide form to filter comments by status.
        P4Cms_PubSub::subscribe('p4cms.comment.grid.form.subForms',
            function(Zend_Form $form)
            {
                $form = new P4Cms_Form_SubForm;
                $form->setName('status');
                $form->addElement(
                    'radio',
                    'status',
                    array(
                        'label'         => 'Status',
                        'multiOptions'  => array(
                            ''                                     => 'Any State',
                            Comment_Model_Comment::STATUS_PENDING  => 'Only Pending',
                            Comment_Model_Comment::STATUS_APPROVED => 'Only Approved',
                            Comment_Model_Comment::STATUS_REJECTED => 'Only Rejected'
                        ),
                        'autoApply'     => true,
                        'value'         => ''
                    )
                );

                return $form;
            }
        );

        // filter comment list by status
        P4Cms_PubSub::subscribe('p4cms.comment.grid.populate',
            function(P4Cms_Record_Query $query, Zend_Form $form)
            {
                $values = $form->getValues();

                // extract status filter.
                $status = isset($values['status']['status'])
                    ? $values['status']['status']
                    : null;

                // early exit if no status filter.
                if (!$status) {
                    return;
                }

                // add a status filter to the comment query.
                $filter = new P4Cms_Record_Filter;
                $filter->add('status', $status);
                $query->addFilter($filter);
            }
        );

        // organize comment records when pulling changes.
        P4Cms_PubSub::subscribe(
            'p4cms.site.branch.pull.groupPaths',
            function($paths, $source, $target, $result)
            {
                $paths->addSubGroup(
                    array(
                        'label'         => 'Comments',
                        'basePaths'     => $target->getId() . '/comments/...',
                        'inheritPaths'  => $target->getId() . '/comments/...'
                    )
                );
            }
        );
    }
}
