<?php
/**
 * Validates string for suitability as a content id.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_ContentId extends P4Cms_Validate_RecordId
{
    const DOESNT_EXIST              = 'doesntExist';
    const EXISTS                    = 'exists';

    protected $_allowEmpty          = false;
    protected $_allowForwardSlash   = false;
    protected $_allowNonExistent    = true;
    protected $_allowExisting       = true;

    protected $_adapter             = null;

    /**
     * Add a message templates upon instantiation.
     */
    public function __construct()
    {
        $message = "The specified content id does not exist.";
        $this->_messageTemplates[self::DOESNT_EXIST] = $message;

        $message = "The specified content id already exists.";
        $this->_messageTemplates[self::EXISTS] = $message;
    }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Checks if the given string is a valid content type id.
     *
     * @param   string   $value  The value to validate.
     * @return  boolean  true if value is a valid content id, false otherwise.
     */
    public function isValid($value)
    {
        // If we allow empty values and receive one; skip parent and return success
        if ($this->allowEmpty() && !strlen($value)) {
            return true;
        }

        $result = parent::isValid($value);

        // If we passed the basic sanity checks and are set to
        // dis-allow non-existent entries validate entries presence
        if ($result && !$this->allowNonExistent()) {
            if (!P4Cms_Content::exists($value, null, $this->getAdapter())) {
                $this->_error(self::DOESNT_EXIST);
                $result = false;
            }
        }

        // dis-allow existing ids (ensure uniqueness)
        if ($result && !$this->allowExisting()) {
            if (P4Cms_Content::exists($value, null, $this->getAdapter())) {
                $this->_error(self::EXISTS);
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Returns the current setting for allowEmpty; default value is false.
     *
     * @return  bool    True if empty IDs are permitted, False otherwise
     */
    public function allowEmpty()
    {
        return (bool) $this->_allowEmpty;
    }

    /**
     * Updates the setting for allowEmpty.
     *
     * @param   bool    $allowed    True if empty IDs are permitted, False otherwise
     * @return  P4Cms_Validate_ContentId    To maintain a fluent interface
     */
    public function setAllowEmpty($allowed)
    {
        $this->_allowEmpty = (bool) $allowed;
        
        return $this;
    }

    /**
     * Returns the current setting for allowNonExistent; default value is true.
     *
     * @return  bool    True if non-existent IDs are permitted, False otherwise
     */
    public function allowNonExistent()
    {
        return (bool) $this->_allowNonExistent;
    }

    /**
     * Returns the current setting for allowExisting; default value is true.
     *
     * @return  bool    True if existing IDs are permitted, False otherwise
     */
    public function allowExisting()
    {
        return (bool) $this->_allowExisting;
    }

    /**
     * Updates the setting for allowNonExistent.
     *
     * @param   bool    $allowed    True if non-existent IDs are permitted, False otherwise
     * @return  P4Cms_Validate_ContentId    To maintain a fluent interface
     */
    public function setAllowNonExistent($allowed)
    {
        $this->_allowNonExistent = (bool) $allowed;

        return $this;
    }

    /**
     * Updates the setting for allowExisting.
     *
     * @param   bool    $allowed    True if existing IDs are permitted, False otherwise
     * @return  P4Cms_Validate_ContentId    To maintain a fluent interface
     */
    public function setAllowExisting($allowed)
    {
        $this->_allowExisting = (bool) $allowed;

        return $this;
    }

    /**
     * Returns the currently specified adapter or null if unset.
     *
     * @return  P4Cms_Record_Adapter    storage adapter to use for 'onlyExisting' checks or null.
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }

    /**
     * Allows the caller to specify an adapter which will be used when performing 'onlyExisting'
     * checks. Setting this is optional, the default adapter will be utilized if unset.
     *
     * @param   P4Cms_Record_Adapter|null   $adapter    storage adapter to use for 'onlyExisting' checks.
     * @return  P4Cms_Validate_ContentId    To maintain a fluent interface
     */
    public function setAdapter(P4Cms_Record_Adapter $adapter = null)
    {
        $this->_adapter = $adapter;

        return $this;
    }
}