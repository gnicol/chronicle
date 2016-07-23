<?php
/**
 * Extends the Zend e-mail address validator to fix error message typo.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_EmailAddress extends Zend_Validate_EmailAddress
{
    /**
     * Replace all email validator messages and hostname validator messages with a single message.
     *
     * @return array
     */
    public function getMessages()
    {
        return array("'" . $this->value . "' is not a valid email address.");
    }
}
