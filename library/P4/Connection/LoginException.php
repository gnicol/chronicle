<?php
/**
 * Exception to be thrown when a login attempt fails.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Connection_LoginException extends P4_Exception
{
    const   IDENTITY_NOT_FOUND  = -1;
    const   IDENTITY_AMBIGUOUS  = -2;
    const   CREDENTIAL_INVALID  = -3;
}
