<?php
/**
 * A workflow action that sends an email when transition occurs. Email is sent as a plain text,
 * where the body contains transition label, from- and to-state labels and, in the case of
 * provided record is an instance of P4Cms_Content class, content's url.
 *
 * Recognized email parameters specified via action options are:
 *
 * to       - (required if 'toRole' option is not specified) email recipients, can be a
 *            string or an array of strings; every value specifies either email address or
 *            username that will be expanded to the user email address; value can also
 *            contain coma-separated list of emails/users
 * toRole   - (required if 'to' option is not specified) role or list of roles that will be
 *            expanded to list of email addresses of the member users; value can also
 *            contain coma-separated list of roles
 * subject  - (optional) email subject, action provides default value if user doesn't specify
 *            this parameter
 * template - (optional) name of the template (may include the path relative to BASE_DIR)
 *            that will be rendered into email html body. Template will have access to
 *            the transition, record and instance of this class via 'transition', 'record' and
 *            'action' variables set to the template's view. If template is not provided by the
 *            user, then workflow module provides default one (see _renderTemplate() method
 *            for more details).
 * message  - (optional) if set, then it will be prepended to the email body
 *
 * In the case that email cannot be sent from some reason, error message is logged and the action
 * execution is silently terminated.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Workflow_Workflow_Action_SendEmail extends Workflow_ActionAbstract
{
    const   DEFAULT_TEMPLATE    = 'application/workflow/views/scripts/send-email-template.phtml';

    /**
     * Invoke this action for the given transition and record.
     *
     * @param   Workflow_Model_Transition   $transition     transition to invoke this action for.
     * @param   P4Cms_Record                $record         record to invoke this action for.
     * @return  Workflow_ActionInterface    provides fluent interface.
     */
    public function invoke(Workflow_Model_Transition $transition, P4Cms_Record $record)
    {
        // collect email options
        $to         = $this->_extract($this->getOption('to'));
        $toRole     = $this->_extract($this->getOption('toRole'));
        $subject    = $this->getOption('subject');
        $message    = $this->getOption('message');

        // list of email recipients
        $recipients = array();

        // get users from 'to' option (if set)
        if (is_array($to)) {
            // extract email addresses
            $emailValidator = new Zend_Validate_EmailAddress;
            $recipients     = array_filter($to, array($emailValidator, 'isValid'));

            // for other items (representing usernames) get emails from users details
            $usernames = array_diff($to, $recipients);
            if (count($usernames)) {
                foreach (P4Cms_User::fetchAll(array(P4Cms_User::FETCH_BY_NAME => $usernames)) as $user) {
                    $recipients[$user->getFullName()] = $user->getEmail();
                }
            }
        }

        // if toRole is specified, add member users to the recipient list
        if (is_array($toRole)) {
            foreach (P4Cms_User::fetchByRole($toRole) as $user) {
                $recipients[$user->getFullName()] = $user->getEmail();
            }
        }

        // early exit if recipients list is empty
        if (!count($recipients)) {
            P4Cms_Log::log("Cannot send email: no recipients specified.");
            return $this;
        }

        // early exit if from- or to-state is invalid
        try {
            $fromState = $transition->getFromState();
            $toState   = $transition->getToState();
        } catch (Workflow_Exception $e) {
            P4Cms_Log::log("Cannot send email: invalid 'from' or 'to' state.");
            return $this;
        }

        // provide default subject if not set by user
        if (!$subject) {
            $subject = 'Workflow Transition';
            // append record title if possible
            if ($record->hasField('title')) {
                $subject .= ': ' . $record->getValue('title');
            }
        }

        // compose html part of the email body
        try {
            $bodyHtml = $this->_renderTemplate($transition, $record);
        } catch (Exception $exception) {
            P4Cms_Log::logException("Cannot render email template.", $exception);
            return $this;
        }

        // if custom message is set, prepend it to the body
        if ($message) {
            $bodyHtml = '<p>' . $message . '</p>' . $bodyHtml;
        }

        // compose text part of the email body
        $htmlToText = new P4Cms_Filter_HtmlToText;
        $bodyText   = $htmlToText->filter($bodyHtml);

        // send email
        $this->_sendEmail($recipients, $subject, $bodyText, $bodyHtml);

        return $this;
    }

    /**
     * Send an email.
     * 
     * @param string|array  $to         email recipient.
     * @param string        $subject    email subject.
     * @param string        $bodyText   email body in the text format.
     * @param string        $bodyHtml   email body in the html format.
     */
    protected function _sendEmail($to, $subject, $bodyText, $bodyHtml)
    {
        $mail = new Zend_Mail();
        $mail->addTo($to)
             ->setSubject($subject);

        // set email body
        if ($bodyText) {
            $mail->setBodyText($bodyText);
        }
        if ($bodyHtml) {
            $mail->setBodyHtml($bodyHtml);
        }

        // set active user (if there is any) as sender
        if (P4Cms_User::hasActive()) {
            $user = P4Cms_User::fetchActive();
            $mail->setFrom($user->getEmail(), $user->getFullName());
        }

        // send the email
        try {
            $mail->send();
        } catch (Exception $exception) {
            P4Cms_Log::logException("Error when sending email.", $exception);
        }
    }

    /**
     * Convert a string or list of strings with separated items into an array of items.
     * Returns null for empty input.
     * 
     * @param   null|string|array   $input      string or list of strings to extract values from.
     * @param   string              $separator  items separator.
     * @return  null|array          array with string values extracted from input or null of
     *                              no input is given.
     */
    protected function _extract($input, $separator = ',')
    {
        // early exit if no input is given
        if (!$input) {
            return null;
        }

        $input  = (array) $input;
        $output = array();
        foreach ($input as $value) {
            // convert separated list of values into an array
            $values = explode($separator, $value);
            $values = array_map('trim', $values);

            // add values into output list
            $output = array_merge($output, $values);            
        }

        return $output;
    }

    /**
     * Return rendered template.
     *
     * @param   Workflow_Model_Transition   $transition     transition to invoke this action for.
     * @param   P4Cms_Record                $record         record to invoke this action for.
     * @return  string                      rendered template.
     */
    protected function _renderTemplate(Workflow_Model_Transition $transition, P4Cms_Record $record)
    {
        // assemble list with template candidates; first one that can be rendered
        // by the view script will be returned
        $candidates   = (array) $this->getOption('template');
        $candidates[] = static::DEFAULT_TEMPLATE;

        // we'll render template by the view cloned from MVC instance
        $view = clone Zend_Layout::getMvcInstance()->getView();

        // loop through template candidates and render first one that view
        // script can find in the script paths
        foreach ($candidates as $template) {
            // if template contains path, add path to the view script paths
            $templateDir = dirname($template);
            $name        = basename($template);
            if (strlen($templateDir) && $templateDir !== '.') {
                $path = BASE_PATH . '/' . $templateDir;
                if (is_dir($path) && is_readable($path . '/' . $name)) {
                    $view->addScriptPath($path);
                }
            }

            // return rendered template if view script can find it
            if ($view->getScriptPath(basename($name))) {                
                $view->transition = $transition;
                $view->record     = $record;
                $view->action     = $this;

                return $view->render($name);
            }
        }

        throw new Workflow_Exception("Cannot render template.");
    }
}