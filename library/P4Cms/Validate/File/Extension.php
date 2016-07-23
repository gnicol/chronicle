<?php
/**
 * Extends Extension validator to change the default error messages.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_File_Extension extends Zend_Validate_File_Extension
{
    protected $_messageTemplates = array(
        self::FALSE_EXTENSION => "File '%value%' has an invalid extension, expecting '%extension%'.",
        self::NOT_FOUND       => "File '%value%' is not readable or does not exist",
    );
}
