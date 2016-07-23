<?php
/**
 * Provides storage of content type definitions.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Content_Type extends P4Cms_Record
{
    protected           $_form              = null;
    protected           $_elements          = null;
    protected           $_hasValidElements  = null;
    protected           $_validationErrors  = array();
    protected static    $_fields            = array(
        'label'         => array(
            'accessor'  => 'getLabel',
            'mutator'   => 'setLabel',
        ),
        'group'         => array(
            'accessor'  => 'getGroup',
            'mutator'   => 'setGroup',
        ),
        'icon'          => array(
            'accessor'  => 'getIcon',
            'mutator'   => 'setIcon',
        ),
        'description'   => array(
            'accessor'  => 'getDescription',
            'mutator'   => 'setDescription',
        ),
        'elements'      => array(
            'accessor'  => 'getElements',
            'mutator'   => 'setElements',
        ),
        'layout'
    );

    protected static    $_fileContentField  = 'elements';
    protected static    $_storageSubPath    = 'content-types';

    /**
     * Set the id of the content type.
     * May contain alpha-numeric characters, dashes and underscores.
     *
     * @param   string|null         $id     the id of this content type.
     * @return  P4Cms_Content_Type  provides fluent interface.
     */
    public function setId($id)
    {
        $validator = new P4Cms_Validate_RecordId;
        $validator->setAllowForwardSlash(false);
        if (!$validator->isValid($id)) {
            throw new InvalidArgumentException(
                "Cannot set content-type id. Id contains invalid characters."
            );
        }

        return parent::setId($id);
    }

    /**
     * Get the current label.
     *
     * @return  string|null     The current label or null.
     */
    public function getLabel()
    {
        return $this->_getValue('label');
    }

    /**
     * Set a new label.
     *
     * @param   string|null     $label  The new label to use.
     * @return  P4Cms_Content_Type      To maintain a fluent interface.
     */
    public function setLabel($label)
    {
        if (!is_string($label) && !is_null($label)) {
            throw new InvalidArgumentException("Label must be a string or null.");
        }

        return $this->_setValue('label', $label);
    }

    /**
     * Get the current group.
     *
     * @return  string|null     The current group or null.
     */
    public function getGroup()
    {
        return $this->_getValue('group');
    }

    /**
     * Set a new group.
     *
     * @param   string|null     $group  The new group to use.
     * @return  P4Cms_Content_Type      To maintain a fluent interface.
     */
    public function setGroup($group)
    {
        if (!is_string($group) && !is_null($group)) {
            throw new InvalidArgumentException("Group must be a string or null.");
        }

        return $this->_setValue('group', $group);
    }

    /**
     * Get the current description.
     *
     * @return  string|null     The current description or null.
     */
    public function getDescription()
    {
        return $this->_getValue('description');
    }

    /**
     * Set a new description.
     *
     * @param   string|null     $description    The new description to use.
     * @return  P4Cms_Content_Type              To maintain a fluent interface.
     */
    public function setDescription($description)
    {
        if (!is_string($description) && !is_null($description)) {
            throw new InvalidArgumentException("Description must be a string or null.");
        }

        return $this->_setValue('description', $description);
    }

    /**
     * Get the current icon.
     *
     * @return  string|null     The current icon or null.
     */
    public function getIcon()
    {
        return $this->_getValue('icon');
    }

    /**
     * Set a new icon.
     *
     * @param   string|null     $icon   The new icon to use.
     * @return  P4Cms_Content_Type      To maintain a fluent interface.
     */
    public function setIcon($icon)
    {
        if (!is_string($icon) && !is_null($icon)) {
            throw new InvalidArgumentException("Icon must be a string or null.");
        }

        return $this->_setValue('icon', $icon);
    }

    /**
     * Get the layout script to use.
     *
     * @return  string|null     the name of the layout script to use.
     */
    public function getLayout()
    {
        return $this->_getValue('layout');
    }

    /**
     * Set the layout script to use.
     *
     * @param   string|null         $layout     the name of the layout to use.
     * @return  P4Cms_Content_Type  to maintain a fluent interface.
     */
    public function setLayout($layout)
    {
        if (!is_string($layout) && !is_null($layout)) {
            throw new InvalidArgumentException("Layout must be a string or null.");
        }

        return $this->_setValue('layout', $layout);
    }

    /**
     * Get a list of all known content types indexed by group name.
     *
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     * @return  array                   array, keyed on group name with P4Cms_Model_Iterator
     *                                  (containing Type models) for values.
     */
    public static function fetchGroups(P4Cms_Record_Adapter $adapter = null)
    {
        // grab all entries
        $entries = static::fetchAll($adapter);

        // sort the entries by 'order' first, 'group' second and 'label' third.
        $entries->sortBy(
            array(
                'order' => array(P4Cms_Model_Iterator::SORT_NUMERIC),
                'group' => array(P4Cms_Model_Iterator::SORT_NATURAL),
                'label' => array(P4Cms_Model_Iterator::SORT_NATURAL)
            )
        );

        // loop entries and index by group
        $groups = array();
        foreach ($entries as $entry) {
            $group = $entry->getValue("group");

            if (!isset($groups[$group])) {
                $groups[$group] = new P4Cms_Model_Iterator;
            }

            $groups[$group][] = $entry;
        }

        return $groups;
    }

    /**
     * Set the elements that make up this content type.
     *
     * @param   array|string|null   $elements   the list of elements (field definitions).
     *                                          if given as a string assumed to be INI format.
     * @return  P4Cms_Content_Type  provides fluent interface.
     */
    public function setElements($elements)
    {
        if (!is_null($elements) && !is_array($elements) && !is_string($elements)) {
            throw new InvalidArgumentException(
                "Cannot set elements. Elements must be given as an array, string or null."
            );
        }

        // if elements given as string, assumed to be INI format.
        if (is_string($elements)) {
            return $this->setElementsFromIni($elements);
        }

        // if elements given as non-null, convert to INI
        if (!is_null($elements)) {
            // convert elements array to INI format.
            $config     = new Zend_Config($elements);
            $writer     = new Zend_Config_Writer_Ini();
            $elements   = $writer->setConfig($config)->render();
        }

        // reset elements.
        $this->_hasValidElements = null;
        $this->_elements         = null;
        $this->_form             = null;

        return $this->_setValue('elements', $elements);
    }

    /**
     * Set the elements in INI format.
     *
     * @param   string  $elements   the list of elements (field definitions) in INI format.
     */
    public function setElementsFromIni($elements)
    {
        if (!is_string($elements)) {
            throw new InvalidArgumentException(
                "Cannot set elements. Elements must be a string."
            );
        }

        // reset elements.
        $this->_hasValidElements = null;
        $this->_elements         = null;
        $this->_form             = null;

        return $this->_setValue('elements', $elements);
    }

    /**
     * Get the elements that make up this content type.
     *
     * The field definitions array is based on the Zend Form
     * elements configuration format. For convenience, you can
     * call getFormElements() to get elements as form elements.
     *
     * @return  array   the list of elements (field definitions).
     */
    public function getElements()
    {
        if ($this->_elements === null) {
            $elements = $this->_getValue('elements');

            // convert elements INI string to an array using Zend_Config_Ini
            // write elements to a temp file to facilitate Zend_Config_Ini parsing.
            $tempFile = tempnam(sys_get_temp_dir(), 'type');
            file_put_contents($tempFile, $elements);
            $config   = new Zend_Config_Ini($tempFile);
            $elements = $config->toArray();
            unlink($tempFile);

            $elements = is_array($elements) ? $elements : array();

            // need to push filter options down an extra level to work-around:
            // http://framework.zend.com/issues/browse/ZF-11102
            // also need to verify that element plugins are available
            $form = new P4Cms_Form;
            $elementLoader = $form->getPluginLoader(P4Cms_Form::ELEMENT);
            foreach ($elements as &$element) {
                foreach (array('options', 'display') as $type) {
                    if (isset($element[$type]['filters']) && is_array($element[$type]['filters'])) {
                        foreach ($element[$type]['filters'] as &$filter) {
                            if (isset($filter['options']) && is_array($filter['options'])) {
                                $filter['options'] = array($filter['options']);
                            }
                        }
                    }
                }

                // default to text if element type is invalid and
                // disable rendering so its only shown in form mode.
                if (is_array($element) && isset($element['type']) && !$elementLoader->load($element['type'], false)) {
                    $element['type'] = 'text';
                    if (!isset($element['display']) || !is_array($element['display'])) {
                        $element['display'] = array();
                    }
                    $element['display']['render'] = false;
                }
            }

            $this->_elements = $elements;
        }

        return $this->_elements;
    }

    /**
     * Get the names of all elements in this type.
     *
     * @return  array   list of all element names.
     */
    public function getElementNames()
    {
        return array_keys($this->getElements());
    }

    /**
     * Get the named element for this content type.
     *
     * @param   string  $element  The name of the element to fetch.
     * @return  array   the field details for the named element or an empty array.
     */
    public function getElement($element)
    {
        $elements = $this->getElements();

        // return an empty array if invalid element specified
        if (!array_key_exists($element, $elements)) {
            return array();
        }

        return $elements[$element];
    }

    /**
     * Determine if this type has the given element name.
     *
     * @param   string  $name   the name of the element to check for.
     * @return  bool    true if the given element is in this type.
     */
    public function hasElement($name)
    {
        return array_key_exists($name, $this->getElements());
    }

    /**
     * Get the named element as a form element.
     *
     * @param   string  $element    the name of the element to get as a form element.
     * @return  Zend_Form_Element   the named element as a form element.
     */
    public function getFormElement($element)
    {
        $elements = $this->getFormElements();

        if (!array_key_exists($element, $elements)) {
            throw new P4Cms_Content_Exception(
                "Cannot get form element. The requested element is not among the form elements."
            );
        }

        return $elements[$element];
    }

    /**
     * Get all of the content type elements as form elements.
     *
     * @return  array   all of the type elements as form elements.
     */
    public function getFormElements()
    {
        if (!$this->_form instanceof P4Cms_Form) {
            $this->_form = new P4Cms_Form;
            $this->_form->setElements($this->getElements());
        }

        return $this->_form->getElements();
    }

    /**
     * Get the elements in INI format.
     *
     * @return  string  the list of elements (field definitions) in INI format.
     */
    public function getElementsAsIni()
    {
        return $this->_getValue('elements');
    }

    /**
     * Get the URI to add content of this type.
     *
     * @return  string  the URI to add content of this type.
     */
    public function getAddUri()
    {
        $content = new P4Cms_Content;
        $content->setContentType($this);

        return $content->getUri('add');
    }

    /**
     * Determine if this type has an icon to display.
     *
     * @return  bool    true if there is an icon, false otherwise.
     */
    public function hasIcon()
    {
        $metadata = $this->getFieldMetadata('icon');
        return (strlen($this->getValue('icon')) &&
                isset($metadata['mimeType']));
    }

    /**
     * Collect all of the default content types
     * and install any that are missing.
     *
     * By default, types that already exist (even in
     * deleted state) are ignored. If the clobberDeleted
     * option is set then deleted types are re-installed.
     *
     * @param   P4Cms_Record_Adapter    $adapter            optional - storage adapter to use.
     * @param   bool                    $clobberDeleted     optional - re-install deleted types.
     */
    public static function installDefaultTypes(
        P4Cms_Record_Adapter $adapter = null,
        $clobberDeleted = false)
    {
        // clear the module/theme cache
        P4Cms_Module::clearCache();
        P4Cms_Theme::clearCache();

        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        // get all enabled modules.
        $packages = P4Cms_Module::fetchAllEnabled();

        // add current theme to packages since it may provide content types.
        if (P4Cms_Theme::hasActive()) {
            $packages[] = P4Cms_Theme::fetchActive();
        }

        // install default content types for each package.
        foreach ($packages as $package) {
            static::installPackageDefaults($package, $adapter, $clobberDeleted);
        }
    }

    /**
     * Install the default content types contributed by a package.
     *
     * @param P4Cms_PackageAbstract    $package         the package whose content types will be installed
     * @param P4Cms_Record_Adapter     $adapter         optional - storage adapter to use.
     * @param boolean                  $clobberDeleted  optional - re-install deleted types.
     */
    public static function installPackageDefaults(
        P4Cms_PackageAbstract $package,
        P4Cms_Record_Adapter $adapter = null,
        $clobberDeleted = false)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        $info = $package->getPackageInfo();

        // get the default types provided by the package
        $types = isset($info['types']) && is_array($info['types'])
               ? $info['types']
               : array();

        // control whether deleted types are ignored.
        $existsOptions = array('includeDeleted' => !$clobberDeleted);
        foreach ($types as $id => $type) {
            // skip the existing content type
            if (static::exists($id, $existsOptions, $adapter)) {
                continue;
            }

            $type['id']  = $id;
            $contentType = new P4Cms_Content_Type($type, $adapter);

            // set icon if any
            if (isset($type['iconFile'])) {
                $contentType->setIconFromFile(
                    $package->getPath() . '/resources/' . $type['iconFile']
                );
            }

            $contentType->save();
        }
    }

    /**
     * Remove content types contributed by a package.
     * The content types are only removed if it has not been changed
     * and there does not exist any content entry of the type.
     *
     * @param P4Cms_PackageAbstract  $package   the package whose content types is to be removed
     * @param P4Cms_Record_Adapter   $adapter   optional - storage adapter to use.
     */
    public static function removePackageDefaults(
        P4Cms_PackageAbstract $package,
        P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        $info = $package->getPackageInfo();

        // get the default types provided by the package
        $types = isset($info['types']) && is_array($info['types'])
               ? $info['types']
               : array();

        foreach ($types as $id => $type) {
            $type['id']  = $id;
            $contentType = new P4Cms_Content_Type($type, $adapter);

            // delete the content type if it's not changed and
            // there is no content entries of this type
            if (static::exists($contentType->getId(), null, $adapter)
                && ($contentType->getContentCount() <= 0)
            ) {
                $storedType = static::fetch($id, null, $adapter);

                // check if the content type is different from the default
                if ($contentType->getValues() == $storedType->getValues()) {
                    $storedType->delete("Package '" . $package->getName() . "' disabled.");
                }
            }
        }
    }

    /**
     * Get the number of content entries of this type.
     *
     * @param  boolean  $includeDeleted  optional - include deleted content entries
     * @return integer                   the number of content entries of this type
     */
    public function getContentCount($includeDeleted = false)
    {
        // create a query to find all content entries of this type
        $filter = new P4Cms_Record_Filter;
        $filter->add('contentType', $this->getId(), P4Cms_Record_Filter::COMPARE_EQUAL);
        $query = new P4Cms_Record_Query();
        $query->addFilter($filter);

        if ($includeDeleted) {
            $query->setIncludeDeleted($includeDeleted);
        }

        return P4Cms_Content::count($query, $this->getAdapter());
    }

    /**
     * Determine if the elements are valid for use in
     * a form and a content type record.
     *
     * @return  bool    true if valid for use with content and forms.
     */
    public function hasValidElements()
    {
        // validate fields on first call - flag should be reset when elements change.
        if ($this->_hasValidElements === null) {
            $errors = $this->validateElements();
        }

        return $this->_hasValidElements;
    }

    /**
     * Validate the element for use in a form and a content type record.
     *
     * @return  array  the list of validation errors; an empty list means all elements are valid.
     */
    public function validateElements()
    {
        $this->_validationErrors = array();
        $this->_hasValidElements = true; // assume valid

        // ensure that element names validate as record field names.
        $validator = new P4Cms_Validate_RecordField;
        foreach (array_keys($this->getElements()) as $element) {
            if (!$validator->isValid($element)) {
                $this->_hasValidElements = false;
                $messages = array();
                foreach ($validator->getMessages() as $message) {
                    $messages[] = lcfirst(preg_replace("/\.$/", "", $message));
                }

                $this->_validationErrors[] = "Element '$element' failed validation: ". implode(', ', $messages);
            }
        }

        // ensure that elements can produce a form.
        if ($this->_hasValidElements) {
            try {
                $this->getFormElements();
            } catch (Exception $e) {
                $this->_validationErrors[] = $e->getMessage();
                $this->_validationErrors[] = 'Please check your input and be sure to use only valid element types.';
                $this->_hasValidElements = false;
            }
        }

        // ensure that the form has the same elements as the type
        if ($this->_hasValidElements) {
            if (array_keys($this->getElements()) != array_keys($this->getFormElements())) {
                $this->_validationErrors[] = 'One or more elements are not valid as form elements.';
                $this->_hasValidElements = false;
            }
        }

        return $this->_validationErrors;
    }

    /**
     * Accessor for validation errors.
     *
     * @return  array  the list of validation errors; an empty list means no errors have been recorded.
     */
    public function getValidationErrors()
    {
        return $this->_validationErrors;
    }

    /**
     * Get the display decorators for a given element.
     *
     * Display decorators can be specified for each element in the
     * content type. If no display decorators have been explicitly
     * set in the element definition, the method will look for
     * default decorators on the form element.
     *
     * If the form element implements the display decorators interface,
     * the decorators will be taken from getDefaultDisplayDecorators().
     *
     * @param   string|Zend_Form_Element    $element    element name or instance to get decorators for.
     * @return  array                       the display decorators for the given element.
     */
    public function getDisplayDecorators($element)
    {
        // element can be given as a string or an instance.
        if ($element instanceof Zend_Form_Element) {
            $formElement = clone $element;
            $element     = $formElement->getName();
        } else {
            $formElement = clone $this->getFormElement($element);
        }

        $decorators = array();
        $definition = $this->getElement($element);

        // always start with the value decorator.
        $formElement->setDecorators(array('Value'));

        // check for explicit decorators on the element definition.
        if (isset($definition['display']['decorators'])
            && is_array($definition['display']['decorators'])
        ) {
            $decorators = $definition['display']['decorators'];
            try {
                return $formElement->addDecorators($decorators)
                                   ->getDecorators();
            } catch (Exception $e) {
                P4Cms_Log::log(
                    "Failed to get user-specified decorators for field '"
                    . $element . "' in type '" . $this->getId() . "'.",
                    P4Cms_Log::ERR
                );
            }
        }

        // no explicit decorators, check for defaults on the form element.
        if ($formElement instanceof P4Cms_Content_EnhancedElementInterface) {
            $decorators = $formElement->getDefaultDisplayDecorators();
            try {
                $formElement->addDecorators($decorators);
            } catch (Exception $e) {
                P4Cms_Log::log(
                    "Failed to get default decorators for field '"
                    . $element . "' in type '" . $this->getId() . "'.",
                    P4Cms_Log::ERR
                );
            }
        }

        // include a label decorator if showLabel is true.
        if (isset($definition['display']['showLabel'])
            && $definition['display']['showLabel']
        ) {
            $formElement->addDecorator('Label');
        }

        return $formElement->getDecorators();
    }

    /**
     * Get the display/output filters for a given element.
     * Display filters can be specified for each element in the
     * content type.
     *
     * @param   string  $element    the name of the element to get display filters for.
     * @return  array   the filters for the named element.
     */
    public function getDisplayFilters($element)
    {
        $definition = $this->getElement($element);

        // early exit if no filters defined.
        if (!isset($definition['display']['filters'])
            || !is_array($definition['display']['filters'])
        ) {
            return array();
        }

        try {
            $formElement = clone $this->getFormElement($element);
            return $formElement->setFilters($definition['display']['filters'])
                               ->getFilters();
        } catch (Exception $e) {
            P4Cms_Log::log(
                "Failed to get user-specified filters for field '"
                . $element . "' in type '" . $this->getId() . "'.",
                P4Cms_Log::ERR
            );
        }

        return array();
    }

    /**
     * Save this record.
     * Extends parent to clear P4Cms_Content's type cache.
     *
     * @param   string  $description  optional - a description of the change.
     * @return  P4Cms_Record          provides a fluent interface
     */
    public function save($description = null)
    {
        P4Cms_Content::clearTypeCache($this->getAdapter());
        return parent::save($description);
    }

    /**
     * Delete this record.
     *
     * @param   string  $description  optional - a description of the change.
     * @return  P4Cms_Record          provides fluent interface.
     */
    public function delete($description = null)
    {
        P4Cms_Content::clearTypeCache($this->getAdapter());

        return parent::delete($description);
    }

    /**
     * Uses one method rather than two to set the icon for a content type.
     *
     * @param   string  $file       The file to use as an icon
     * @return  P4Cms_Content_Type  To maintain a fluent interface.
     */
    public function setIconFromFile($file)
    {
        $type = P4Cms_Validate_File_MimeType::getTypeOfFile($file);
        $data = file_get_contents($file);

        $this->setIcon($data)
             ->setFieldMetadata('icon', array('mimeType' => $type));

        return $this;
    }
}
