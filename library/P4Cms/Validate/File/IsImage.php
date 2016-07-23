<?php
/**
 * Extends mime-type validator to clear the magic file search
 * paths. This allows the finfo() extension to fallback on
 * its internal mime database rather than using a database
 * found in the file-system which may be incompatible.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_File_IsImage extends Zend_Validate_File_IsImage
{
    protected $_magicFiles = array();
}
