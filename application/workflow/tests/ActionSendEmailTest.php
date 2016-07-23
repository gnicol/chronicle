<?php
/**
 * Test workflow 'SendEmail' action class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Test_ActionSendEmailTest extends ModuleTest
{
    /**
     * Test invoke() method.
     */
    public function testInvoke()
    {
        // create few records to test action on
        $recordWithTitle = P4Cms_Record::create(
            array(
                'id'    => 1,
                'title' => 'foo title'
            )
        );
        $contentNoTitle = P4Cms_Content::create(
            array(
                'id'    => 2
            )
        );

        // create user to test email sender
        $joe = P4Cms_User::create(
            array(
                'id'        => 'joe',
                'fullName'  => 'Mr Joe',
                'email'     => 'joe@email.com'
            )
        )->save();

        // save current user
        $currentUser = P4Cms_User::hasActive() ? P4Cms_User::fetchActive() : null;

        // create transition model
        $workflow   = new Workflow_Model_Workflow;
        $transition = Workflow_Model_Transition::create(
            array(
                'label'     => 'test transition',
                'fromState' => new Workflow_Model_State(
                    array(
                        'label'     => 'from state',
                        'workflow'  => $workflow
                    )
                ),
                'toState'   => new Workflow_Model_State(
                    array(
                        'label'     => 'to state',
                        'workflow'  => $workflow
                    )
                ),
            )
        );

        // define tests
        // @todo test email body (its harder as email body is encoded before sending)
        $commonSubject = "Workflow Transition";
        $tests = array(
            array(
                'options'       => array(
                    'to'        => 'test@test.com'
                ),
                'transition'    => $transition,
                'record'        => $recordWithTitle,
                'expected'      => array(
                    'to'        => 'test@test.com',
                    'subject'   => "$commonSubject: foo title",
                ),
                'message'       => __LINE__ . ": no subject specified"
            ),
            array(
                'options'       => array(
                    'to'        => 'foo@test.com'
                ),
                'transition'    => $transition,
                'record'        => $contentNoTitle,
                'expected'      => array(
                    'to'        => 'foo@test.com',
                    'subject'   => "$commonSubject",
                    'headers'   => array(
                        'From'  => 'Mr Joe <joe@email.com>'
                    )
                ),                
                'user'          => $joe,
                'message'       => __LINE__ . ": no subject specified"
            ),
            array(
                'options'       => array(
                    'to'        => 'foo@test.com',
                    'subject'   => 'custom subject'
                ),
                'transition'    => $transition,
                'record'        => $contentNoTitle,
                'expected'      => array(
                    'to'        => 'foo@test.com',
                    'subject'   => "custom subject",
                ),
                'message'       => __LINE__ . ": custom subject"
            ),
            array(
                'options'       => array(
                    'to'        => array('foo@test.com', 'bar@test.com', 'baz@email.com'),
                    'subject'   => 'subject m'
                ),
                'transition'    => $transition,
                'record'        => $contentNoTitle,
                'expected'      => array(
                    'to'        => 'foo@test.com,bar@test.com,baz@email.com',
                    'subject'   => "subject m",
                ),
                'message'       => __LINE__ . ": custom subject, multiple recipients"
            ),
            array(
                'options'       => array(
                    'to'        => array('foo@test.com')
                ),
                'transition'    => $transition,
                'record'        => $recordWithTitle,
                'expected'      => array(
                    'to'        => 'foo@test.com',
                    'subject'   => "$commonSubject: foo title",
                ),
                'message'       => __LINE__ . ": no subject, multiple recipients"
            ),
            array(
                'options'       => array(
                    'to'        => 'joe'
                ),
                'transition'    => $transition,
                'record'        => $recordWithTitle,
                'expected'      => array(
                    'to'        => 'joe@email.com',
                    'subject'   => "$commonSubject: foo title",
                ),
                'message'       => __LINE__ . ": no subject, one recipient specified by username"
            ),
            array(
                'options'       => array(
                    'to'        => array('foo@test.com', 'joe')
                ),
                'transition'    => $transition,
                'record'        => $recordWithTitle,
                'expected'      => array(
                    'to'        => 'foo@test.com,joe@email.com',
                    'subject'   => "$commonSubject: foo title",
                ),
                'message'       => __LINE__ . ": no subject, multiple mixed recipients"
            ),
            array(
                'options'       => array(
                    'to'        => array('foo@test.com', 'joe', 'noexist', 'xyz@', '@.com', 'abc@cba.com')
                ),
                'transition'    => $transition,
                'record'        => $recordWithTitle,
                'expected'      => array(
                    'to'        => 'foo@test.com,abc@cba.com,joe@email.com',
                    'subject'   => "$commonSubject: foo title",
                ),
                'message'       => __LINE__ . ": no subject, multiple mixed recipients"
            ),
            array(
                'options'       => array(
                    'to'        => 'a@test.com, b@test.com , c@test.com'
                ),
                'transition'    => $transition,
                'record'        => $recordWithTitle,
                'expected'      => array(
                    'to'        => 'a@test.com,b@test.com,c@test.com',
                    'subject'   => "$commonSubject: foo title",
                ),
                'message'       => __LINE__ . ": no subject, single coma-separated recipients"
            ),
            array(
                'options'       => array(
                    'to'        => array('a@test.com , b@test.com', 'c@test.com')
                ),
                'transition'    => $transition,
                'record'        => $recordWithTitle,
                'expected'      => array(
                    'to'        => 'a@test.com,b@test.com,c@test.com',
                    'subject'   => "$commonSubject: foo title",
                ),
                'message'       => __LINE__ . ": no subject, multiple coma-separated recipients"
            ),
            array(
                'options'       => array(
                    'to'        => array('a@test.com , joe', 'c@test.com', 'd@test.com, e@test.com')
                ),
                'transition'    => $transition,
                'record'        => $recordWithTitle,
                'expected'      => array(
                    'to'        => 'a@test.com,c@test.com,d@test.com,e@test.com,joe@email.com',
                    'subject'   => "$commonSubject: foo title",
                ),
                'message'       => __LINE__ . ": no subject, multiple mixed recipients"
            )
        );

        // instantiate action class
        $class  = Workflow_Module::getPluginLoader('action')->load('sendEmail');
        $action = new $class;

        // set custom mail transport to actually not sent any real emails, but
        // check emails registered by sent() action instead
        $transport = new Workflow_Test_MailTransport;
        Zend_Mail::setDefaultTransport($transport);

        // run tests
        foreach ($tests as $test) {
            // set active user
            P4Cms_User::clearActive();
            if (isset($test['user'])) {
                P4Cms_User::setActive($test['user']);
            } else if ($currentUser) {
                P4Cms_User::setActive($currentUser);
            }

            // invoke action with particular options
            $transport->reset();
            $action->setOptions($test['options']);
            $action->invoke($test['transition'], $test['record']);

            // verify expected email properties
            $sentMails = $transport->getSentMails();

            // verify that exactly 1 email has been sent
            $this->assertSame(
                1,
                count($sentMails),
                "Expected 1 email has been sent."
            );

            $sentMail = current($sentMails);

            // verify headers first (if set)
            if (isset($test['expected']['headers'])) {
                foreach ($test['expected']['headers'] as $header => $value) {                    
                    // extract header value from email properties
                    $headerValue = null;
                    if (isset($sentMail['headers'][$header][0])) {
                        $headerValue = $sentMail['headers'][$header][0];
                    }

                    $this->assertSame(
                        $value,
                        $headerValue,
                        "Expected header value, line: " . $test['message']
                    );
                }

                unset($test['expected']['headers']);
            }

            // verify expected values have been set
            $this->assertSame(
                array(),
                array_diff($test['expected'], $sentMail),
                'Line ' . $test['message']
            );
        }

        // verify that custom template can be provided
        $transport->reset();
        $action->setOptions(
            array(
                'to'        => 'a@test.com',
                'template'  => 'application/workflow/tests/email-template-test.phtml'
            )
        );
        $action->invoke($transition, $recordWithTitle);

        // verify expected email properties
        $sentMails = $transport->getSentMails();
        $this->assertSame(
            1,
            count($sentMails),
            "Expected 1 email has been sent."
        );

        $sentMail = current($sentMails);
        $this->assertTrue(
            strpos($sentMail['body'], "[sendEmail testing template]") !== false,
            "Expected user-defined template has been used."
        );

        // verify that sendEmail provides default template in case of user-provided template cannot be rendered
        $transport->reset();
        $action->setOptions(
            array(
                'to'        => 'a@test.com',
                'template'  => 'unknown-template'
            )
        );
        $action->invoke($transition, $recordWithTitle);

        // verify expected email properties
        $sentMails = $transport->getSentMails();
        $this->assertSame(
            1,
            count($sentMails),
            "Expected 1 email has been sent."
        );

        $sentMail = current($sentMails);
        $this->assertTrue(
            strpos($sentMail['body'], "[sendEmail testing template]") === false,
            "Expected user-defined template has not been used."
        );
    }
}