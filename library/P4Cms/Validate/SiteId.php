<?php
/**
 * Validates string for suitability as a site id.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_SiteId extends Zend_Validate_Abstract
{
    const   INVALID_CHARACTERS      = 'invalidCharacters';
    const   MISSING_PREFIX          = 'missingPrefix';

    protected   $_messageTemplates  = array(
        self::INVALID_CHARACTERS
            => "A site id may only contain alphanumeric characters and dash ('-').",
        self::MISSING_PREFIX
            => "The given site id is missing the expected prefix."
    );

    /**
     * Checks if the given string appears to be in a valid site id format.
     *
     * @param   string   $value  the site id value to validate.
     * @return  boolean  true if value is valid, false otherwise.
     */
    public function isValid($value)
    {
        // check for invalid characters.
        if (preg_match('/[^a-z0-9\-]/i', $value)) {
            $this->_error(self::INVALID_CHARACTERS);
            return false;
        }

        // check for site id prefix.
        if (strpos($value, P4Cms_Site::SITE_PREFIX) !== 0) {
            $this->_error(self::MISSING_PREFIX);
            return false;
        }

        return true;
    }
}
