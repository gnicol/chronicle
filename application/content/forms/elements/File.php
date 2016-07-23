<?php
/**
 * Extends Zend_Form_Element_File to provide support to
 * keep/remove/replace existing files and to enhance for
 * use with content records.
 *
 * Adds options to display a icon for an existing file.
 * Adds method to get the uploaded file contents.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_Element_File
    extends     Zend_Form_Element_File
    implements  P4Cms_Content_EnhancedElementInterface,
                P4Cms_Record_EnhancedElementInterface
{
    const       ACTION_KEEP         = 'keep';
    const       ACTION_REMOVE       = 'remove';
    const       ACTION_REPLACE      = 'replace';

    protected   $_contentRecord     = null;
    protected   $_existingFileInfo  = null;
    protected   $_formData          = null;

    /**
     * Set information about an existing file.
     *
     * Info can be set by passing an array of key/value pairs, or
     * by passing a string (key) as the first argument and a value
     * for the second argument to set a specific property.
     *
     * If there is an existing file, but nothing is known about
     * it, pass an empty array. Setting to false or null will indicate
     * there is no existing file (the default case).
     *
     * File info may contain any key/value pairs. The following keys
     * are used to inform how the file element is rendered:
     *
     *  - filename
     *  - mimeType
     *  - iconUri
     *
     * @param   array|string    $info   information about an existing file
     *                                  if a string is given, sets the named
     *                                  key in the file info array.
     * @param   string          $value  optional - value to set when called
     *                                  with a string (key) for the first param.
     * @return  P4Cms_Form_Element_ImageFile    provides fluent interface.
     */
    public function setExistingFileInfo($info, $value = null)
    {
        if (is_string($info)) {
            $key        = $info;
            $info       = $this->_existingFileInfo ?: array();
            $info[$key] = $value;
        }

        $this->_existingFileInfo = $info;

        return $this;
    }

    /**
     * Get any available information about an existing file.
     *
     * @return  array   information about an existing file
     *                  false if no existing file is set.
     */
    public function getExistingFileInfo()
    {
        if ($this->hasExistingFile()) {
            return $this->_existingFileInfo;
        }

        return false;
    }

    /**
     * Determine if this field has an existing file set.
     * Set existing file info to indicate there is an existing file.
     *
     * @return  bool    true if there is an existing file; false otherwise.
     */
    public function hasExistingFile()
    {
        if (is_array($this->_existingFileInfo)
            || array_key_exists($this->getActionFieldName(), $this->getFormData())
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine if the existing file has been marked for removal.
     *
     * @return  bool    true if the file is marked for removal; false otherwise.
     */
    public function isRemoved()
    {
        return ($this->getActionFieldValue() === self::ACTION_REMOVE);
    }

    /**
     * Determine if the existing file has been marked for replacement.
     *
     * @return  bool    true if the file is marked for replacement; false otherwise.
     */
    public function isReplaced()
    {
        return ($this->getActionFieldValue() === self::ACTION_REPLACE);
    }

    /**
     * Get the name of the action field (which indicates the disposition
     * of the existing file). Derived from this file element's name.
     *
     * @return  string  the name of the 'action' field associated with this file input.
     */
    public function getActionFieldName()
    {
        return $this->getName() . "-existing-file-action";
    }

    /**
     * Get the action for the existing file (e.g. keep, remove, replace).
     * Defaults to 'keep'.
     *
     * @return  string  the value of the existing file action field.
     */
    public function getActionFieldValue()
    {
        $data   = $this->getFormData();
        $action = $this->getActionFieldName();
        return array_key_exists($action, $data) ? $data[$action] : self::ACTION_KEEP;
    }

    /**
     * Get the contents of the uploaded file on the server.
     */
    public function getFileContent()
    {
        return file_get_contents($this->getFileTempName());
    }

    /**
     * Get the temporary name of the uploaded file on the server.
     */
    public function getFileTempName()
    {
        if (!$this->isUploaded()) {
            throw new Content_Exception("Cannot get file temp name if file not uploaded.");
        }

        $fileInfo = $this->getFileInfo();
        return $fileInfo[$this->getName()]['tmp_name'];
    }

    /**
     * Set the form data from which the existing file action will be read.
     * Normally it is unnecessary to set this as the element will read from
     * $_POST by default.
     *
     * @param   array   $data                   the form data to pull the file action from.
     * @return  P4Cms_Form_Element_ImageFile    provides fluent interface.
     */
    public function setFormData($data)
    {
        $this->_formData = $data;
    }

    /**
     * Get the form data from which the existing file action will be read.
     * Reads from $_POST directly unless data has been explicitly set via
     * setFormData().
     *
     * @return  array   the form data to pull file action from.
     */
    public function getFormData()
    {
        return isset($this->_formData) ? $this->_formData : $_POST;
    }

    /**
     * Get the associated content record (if set).
     *
     * @return  null|P4Cms_Content  the associated content record or null if none set.
     */
    public function getContentRecord()
    {
        return $this->_contentRecord;
    }

    /**
     * Set the associated content record for this element.
     *
     * @param   P4Cms_Content   $content  the associated content record for this element.
     */
    public function setContentRecord($content)
    {
        $this->_contentRecord = $content;
    }

    /**
     * Get the default display decorators to use when rendering
     * content elements of this type.
     *
     * @return  array   decorators configuration array suitable for passing
     *                  to element setDecorators().
     */
    public function getDefaultDisplayDecorators()
    {
        return array(
            array(
                'decorator' => 'DisplayFileLink',
                'options'   => array(
                    'placement' => Content_Form_Decorator_DisplayFileLink::REPLACE
                )
            )
        );
    }

    /**
     * Retrieve all validators; proxy to adapter
     *
     * @return array
     */
    public function getValidators()
    {
        $adapter    = $this->getTransferAdapter();
        $validators = $adapter->getValidators($this->getName());
        if (!$validators) {
            $validators = $adapter->getValidators();
        }

        return $validators;
    }

    /**
     * Validate upload
     * Overridden to handle content edit's "keep existing file" option.
     *
     * @param  string $value   File, can be optional, give null to validate all files
     * @param  mixed  $context optional context
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        if ($this->_validated) {
            return true;
        }

        $isRequired = $this->isRequired();

        if ($isRequired
            && $this->hasExistingFile()
            && $this->getActionFieldValue() == self::ACTION_KEEP
        ) {
            $this->setRequired(false);
        }

        $result = parent::isValid($value, $context);

        $this->setRequired($isRequired);

        return $result;
    }

    /**
     * Set the file value on the given record.
     *
     * File elements require special handling. Three cases:
     *
     *  - File is flagged for removal, clear the record value and metadata.
     *  - New file is uploaded, set the record value and metadata.
     *  - Existing file is 'kept', do nothing.
     *
     * @param   P4Cms_Record    $record                 the record to populate
     * @return  P4Cms_Record_EnhancedElementInterface   provides fluent interface
     */
    public function populateRecord(P4Cms_Record $record)
    {
        $field = $this->getName();

        // if file is flagged for removal, clear it.
        if ($this->isRemoved()) {
            $record->setValue($field, null);
            $record->setFieldMetadata($field, null);
        }

        // if a new file has been uploaded, store it.
        if ($this->isUploaded() && ($this->isReplaced() || !$this->hasExistingFile())) {
            $record->setValueFromFile(
                $field,
                $this->getFileTempName(),
                basename($this->getFileName()),
                $this->getMimeType()
            );
        }

        return $this;
    }

    /**
     * Populate the file element from the given record.
     * If there is an existing file, set file info on the form.
     *
     * @param   P4Cms_Record    $record                 the record to populate from
     * @return  P4Cms_Record_EnhancedElementInterface   provides fluent interface
     */
    public function populateFromRecord(P4Cms_Record $record)
    {
        $field = $this->getName();

        // nothing to do if record doesn't have a field with this name.
        if (!$record->hasField($field)) {
            return $this;
        }

        $metadata = $record->getFieldMetadata($field);
        if (is_array($metadata) && !empty($metadata)) {
            $this->setExistingFileInfo($metadata);
        }

        return $this;
    }
}
