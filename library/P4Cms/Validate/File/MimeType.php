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
class P4Cms_Validate_File_MimeType extends Zend_Validate_File_MimeType
{
    protected $_magicFiles = array();

    /**
     * Utility method to determine the mime-type of an arbitrary file.
     *
     * @param   string  $file   the path of the file to get the mime type of.
     * @return  string  the mime-type of the file.
     */
    public static function getTypeOfFile($file)
    {
        $validator = new static('');
        $validator->isValid($file);

        return $validator->_type;
    }
    
    /**
     * Utility method to validate that a mime-type group contains this mime-type
     * 
     * @param   string  $group  the mime-type group to search
     * @param   string  $type   the mime-type
     * @return  bool    whether the group contained the type 
     */
    public static function groupContainsType($group, $type)
    {
        $validator = new static($group);
        $mimetype = $validator->getMimeType(true);
        if (in_array($type, $mimetype)) {
            return true;
        }

        $types = explode('/', $type);
        $types = array_merge($types, explode('-', $type));
        $types = array_merge($types, explode(';', $type));
        foreach ($mimetype as $mime) {
            if (in_array($mime, $types)) {
                return true;
            }
        }
        
        return false;
    }
}
