<?php
if (!class_exists('P4_Exception', false)) {

/**
 * Exception to be thrown when an error occurs in the P4 package.
 *
 * If using the p4php extension, this class is already defined. To avoid 
 * redefinition errors, we only define the class if it doesn't already exist.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Exception extends Exception
{
}

}
