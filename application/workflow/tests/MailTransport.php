<?php
/**
 * Custom email transport for Zend_Mail primarily intended for testing.
 * No real emails are sent. Instead of that, emails that would normally
 * be sent by _sendEmail() method are saved in the register and values
 * can be checked by calling getSentMails().
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Test_MailTransport extends Zend_Mail_Transport_Abstract
{
    protected $_sentMailsRegister = array();

    /**
     * Add email properties to the sent emails register.
     * Does not send any real email.
     */
    protected function _sendMail()
    {
        $this->_sentMailsRegister[] = array(
            'to'        => $this->recipients,
            'body'      => $this->body,
            'subject'   => $this->_mail->getSubject(),
            'headers'   => $this->_getHeaders(null),
        );
    }

    /**
     * Get list of emails registered by _sendMail() method since last reset.
     *
     * @return  array   list of emails registered by _sendMail() method.
     */
    public function getSentMails()
    {
        return $this->_sentMailsRegister;
    }

    /**
     * Reset sent emails register.
     */
    public function reset()
    {
        $this->_sentMailsRegister = array();
    }
}