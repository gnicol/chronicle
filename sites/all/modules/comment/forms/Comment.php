<?php
/**
 * A form for posting comments.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Comment_Form_Comment extends P4Cms_Form
{
    /**
     * Defines the elements that make up the comment form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        $this->setMethod('post')
             ->setAttrib('class', 'comment-form');

        $this->addElement(
            'hidden',
            'path',
            array(
                'required'  => true,
                'ignore'    => true
            )
        );

        // add name and email fields for anonymous users.
        $anonymous = P4Cms_User::hasActive()
            ? P4Cms_User::fetchActive()->isAnonymous()
            : true;
        if ($anonymous) {
            $this->addElement(
                'text',
                'name',
                array(
                    'label'         => 'Name',
                    'required'      => true
                )
            );

            $this->addElement(
                'text',
                'email',
                array(
                    'label'         => 'Email',
                    'size'          => 50,
                    'required'      => false,
                    'validators'    => array('EmailAddress')
                )
            );
        }
        
        $this->addElement(
            'textarea',
            'comment',
            array(
                'label'     => 'Comment',
                'rows'      => 3,
                'required'  => true
            )
        );

        // require captcha for anonymous users.
        if ($anonymous) {
            $this->addElement(
                'captcha',
                'captcha',
                array(
                    'label'      => 'Verification',
                    'required'   => true,
                    'captcha'    => array(
                        'captcha' => 'Figlet',
                        'wordLen' => 5,
                        'timeout' => 1800
                    )
                )
            );
        }

        $this->addElement(
            'submit',
            'post',
            array(
                'label'     => 'Post Comment',
                'ignore'    => true
            )
        );
    }
}
