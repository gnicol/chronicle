<?php
/**
 * Extends Zend_Form to provide support for an id prefix and show errors by default.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form extends Zend_Dojo_Form
{
    const               CSRF_TOKEN_NAME         = '_csrfToken';

    /**
     * Useful for indenting items (e.g. in select elements)
     */
    const               UTF8_NBSP               = "\xc2\xa0";

    protected           $_storageAdapter        = null;
    protected           $_idPrefix              = null;

    /**
     * Optional form csrf protection, used to enable
     * verification and generation of the csrf token
     * for authenticated user.
     * @var boolean
     */
    protected           $_csrfProtection        = true;
    protected           $_populatedCsrfToken    = '';

    /**
     * Zend_Session storage object.
     * @var Zend_Session
     */
    protected   static  $_session               = null;
    protected   static  $_prefixPaths           = array();
    protected   static  $_libraryPaths          = array(
        array(
            'prefix'    => 'Zend_Dojo_Form_Element',
            'path'      => 'Zend/Dojo/Form/Element',
            'type'      => self::ELEMENT
        ),
        array(
            'prefix'    => 'Zend_Dojo_Form_Decorator',
            'path'      => 'Zend/Dojo/Form/Decorator',
            'type'      => self::DECORATOR
        ),
        array(
            'prefix'    => 'P4Cms_Form_Element',
            'path'      => 'P4Cms/Form/Element',
            'type'      => self::ELEMENT
        ),
        array(
            'prefix'    => 'P4Cms_Form_Decorator',
            'path'      => 'P4Cms/Form/Decorator',
            'type'      => self::DECORATOR
        ),
        array(
            'prefix'    => 'P4Cms_Validate',
            'path'      => 'P4Cms/Validate',
            'type'      => Zend_Form_Element::VALIDATE
        ),
        array(
            'prefix'    => 'P4Cms_Filter',
            'path'      => 'P4Cms/Filter',
            'type'      => Zend_Form_Element::FILTER
        ),
        array(
            'prefix'    => 'P4_Validate',
            'path'      => 'P4/Validate',
            'type'      => Zend_Form_Element::VALIDATE
        )
    );

    /**
     * Extend Zend_Dojo_Form's constructor to provide our own decorators.
     *
     * @param  array|Zend_Config|null $options  Zend provides no documentation for this param.
     * @return void
     */
    public function __construct($options = null)
    {
        // combine library prefix paths with
        // paths from the static registry.
        $prefixPaths = static::$_libraryPaths + static::$_prefixPaths;

        // add prefix paths to form instance.
        foreach ($prefixPaths as $prefixPath) {
            extract($prefixPath);

            // add element and decorator paths to form.
            if ($type === static::ELEMENT || $type === static::DECORATOR) {
                $this->addPrefixPath($prefix, $path, $type);
            }

            // add decorator, validator and filter paths to elements.
            if ($type !== static::ELEMENT) {
                $this->addElementPrefixPath($prefix, $path, $type);
            }

            // add decorator paths to display groups.
            if ($type === static::DECORATOR) {
                $this->addDisplayGroupPrefixPath($prefix, $path);
            }
        }

        // if no storage adapter specified, use default where available
        if (!isset($options['storageAdapter'])
            && P4Cms_Record::hasDefaultAdapter()
        ) {
            $this->_storageAdapter = P4Cms_Record::getDefaultAdapter();
        }

        parent::__construct($options);
    }

    /**
     * Retrieve all form element values
     *
     * Override parent to fix an issue where form structure and form->getValues()
     * are inconsistent if form has a sub-form with 'isArray' property set to false.
     *
     * See: http://framework.zend.com/issues/browse/ZF-12027
     *
     * @param   bool    $suppressArrayNotation  zend provides no description for this param.
     * @return  array   all form values organized by element/sub-form.
     * @todo    remove when issue ZF-12027 is resolved.
     */
    public function getValues($suppressArrayNotation = false)
    {
        $values = array();
        $eBelongTo = null;

        if ($this->isArray()) {
            $eBelongTo = $this->getElementsBelongTo();
        }

        foreach ($this->getElements() as $key => $element) {
            if (!$element->getIgnore()) {
                $merge = array();
                if (($belongsTo = $element->getBelongsTo()) !== $eBelongTo) {
                    if ('' !== (string)$belongsTo) {
                        $key = $belongsTo . '[' . $key . ']';
                    }
                }
                $merge = $this->_attachToArray($element->getValue(), $key);
                $values = $this->_array_replace_recursive($values, $merge);
            }
        }
        foreach ($this->getSubForms() as $key => $subForm) {
            $merge = array();
            if (!$subForm->isArray()) {
                $merge = $subForm->getValues();
            } else {
                $merge = $this->_attachToArray(
                    $subForm->getValues(true),
                    $subForm->getElementsBelongTo()
                );
            }
            $values = $this->_array_replace_recursive($values, $merge);
        }

        if (!$suppressArrayNotation &&
            $this->isArray() &&
            !$this->_getIsRendered()) {
            $values = $this->_attachToArray($values, $this->getElementsBelongTo());
        }

        return $values;
    }

    /**
     * Retrieve error messages from elements failing validations.
     *
     * Fix for an issue where output from parent method is not consistent with the form
     * structure when form contains nested sub-forms with 'isArray' flag set to false.
     * See description for getValues() method where we fix the same issue.
     *
     * @param   string  $name                   a element or sub-form to get messages for.
     * @param   bool    $suppressArrayNotation  zend provides no description for this param.
     * @return  array   list of error messages organized by element/sub-form
     * @todo    remove when issue ZF-12027 is resolved.
     */
    public function getMessages($name = null, $suppressArrayNotation = false)
    {
        if (null !== $name) {
            if (isset($this->_elements[$name])) {
                return $this->getElement($name)->getMessages();
            } else if (isset($this->_subForms[$name])) {
                return $this->getSubForm($name)->getMessages(null, true);
            }
            foreach ($this->getSubForms() as $key => $subForm) {
                if ($subForm->isArray()) {
                    $belongTo = $subForm->getElementsBelongTo();
                    if ($name == $this->_getArrayName($belongTo)) {
                        return $subForm->getMessages(null, true);
                    }
                }
            }
        }

        $customMessages = $this->_getErrorMessages();
        if ($this->isErrors() && !empty($customMessages)) {
            return $customMessages;
        }

        $messages = array();

        foreach ($this->getElements() as $name => $element) {
            $eMessages = $element->getMessages();
            if (!empty($eMessages)) {
                $messages[$name] = $eMessages;
            }
        }

        foreach ($this->getSubForms() as $key => $subForm) {
            $merge = $subForm->getMessages(null, true);
            if (!empty($merge)) {
                if ($subForm->isArray()) {
                    $merge = $this->_attachToArray(
                        $merge,
                        $subForm->getElementsBelongTo()
                    );
                }
                $messages = $this->_array_replace_recursive($messages, $merge);
            }
        }

        if (!$suppressArrayNotation &&
            $this->isArray() &&
            !$this->_getIsRendered()) {
            $messages = $this->_attachToArray($messages, $this->getElementsBelongTo());
        }

        return $messages;
    }

    /**
     * Add a new element.
     *
     * This is a wrapper around the parent function that provides more palatable
     * error messages for end users.
     *
     * @param   string|Zend_Form_Element  $element  The element to add.
     * @param   string                    $name     The name of the element.
     * @param   array|Zend_Config         $options  The options for the element.
     * @return  Zend_Form
     */
    public function addElement($element, $name = null, $options = null)
    {
        try {
            parent::addElement($element, $name, $options);
        } catch (Exception $e) {
            P4Cms_Log::log(
                'P4Cms_Form->addElement exception ('. get_class($e) .') - '. $e->getMessage(),
                P4Cms_Log::DEBUG
            );
            if (preg_match("/^Plugin by name '(.+)' was not found in the registry;/", $e->getMessage(), $matches)) {
                throw new Zend_Form_Exception('Element plugin "'. $matches[1] .'" not found.');
            } else {
                throw $e;
            }
        }
        return $this;
    }

    /**
     * Set a string to prefix element ids with.
     *
     * @param  string   $prefix the string to prefix element ids with.
     * @return P4Cms_Form_Decorator_IdPrefix    the decorator instance.
     */
    public function setIdPrefix($prefix)
    {
        $this->_idPrefix = (string) $prefix;
        return $this;
    }

    /**
     * Get the string used to prefix element ids.
     *
     * @return  string  the string used to prefix element ids.
     */
    public function getIdPrefix()
    {
        return $this->_idPrefix;
    }

    /**
     * Add id prefixes, then render the form.
     *
     * @param   Zend_View_Interface  $view  The Zend View Interface to render.
     * @return  string
     */
    public function render(Zend_View_Interface $view = null)
    {
        // prefix form element ids if id prefix is set.
        if ($this->getIdPrefix()) {
            static::prefixFormIds($this, $this->getIdPrefix());
        }

        return parent::render($view);
    }

    /**
     * Add "Errors" and "CsrfForm" to  the default set of decorators.
     *
     * @return void
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return;
        }

        $decorators = $this->getDecorators();
        $prepend    = Zend_Form_Decorator_Abstract::PREPEND;
        if (empty($decorators)) {
            $this->addDecorator('FormElements')
                 ->addDecorator('HtmlTag', array('tag' => 'dl', 'class' => 'zend_form_dojo'))
                 ->addDecorator('Errors',  array('placement' => $prepend))
                 ->addDecorator('Csrf',    array('placement' => $prepend))
                 ->addDecorator('DijitForm');
        }
    }

    /**
     * Add a form plugin path to be used whenever a form is instantiated.
     *
     * @param   string  $prefix     the class prefix (e.g. Foo_Form_Element)
     * @param   string  $path       the path containing the classes
     * @param   string  $type       the type of plugin (e.g. element, decorator
     *                              validator, filter)
     */
    public static function registerPrefixPath($prefix, $path, $type)
    {
        static::$_prefixPaths[$prefix] = array(
            'prefix'    => $prefix,
            'path'      => $path,
            'type'      => strtoupper($type)
        );
    }

    /**
     * Get any plugin prefix paths that are statically registered.
     *
     * @return  array   the list of registered plugin prefix paths.
     */
    public static function getPrefixPathRegistry()
    {
        return static::$_prefixPaths;
    }

    /**
     * Get any library prefix paths that are statically registered.
     *
     * @return  array   the list of registered library prefix paths.
     */
    public static function getLibraryPathRegistry()
    {
        return static::$_libraryPaths;
    }

    /**
     * Remove any registered form plugin prefix paths.
     */
    public static function clearPrefixPathRegistry()
    {
        static::$_prefixPaths = array();
    }

    /**
     * Helper method to get the validator of an element.
     * Calls isValid on the element to load the correct validators.
     * Attempts to preserve original errors and value.
     *
     * @param   string  $element            the name of the element to get the validator for.
     * @param   string  $validator          the name of the validator to get.
     * @return  Zend_Validate_Interface     the requested validator.
     */
    public function getElementValidator($element, $validator)
    {
        // create a copy of element (to avoid polluting the element)
        // and call isValid to load the validators.
        $temp = clone $this->getElement($element);
        $temp->isValid(true);

        return $this->getElement($element)
                    ->setValidators($temp->getValidators())
                    ->getValidator($validator);
    }

    /**
     * Get the values of the form flattened with array notation for keys.
     *
     * @return  array   the flattened form values.
     */
    public function getFlattenedValues()
    {
        $filter = new P4Cms_Filter_FlattenArray;
        return $filter->filter($this->getValues());
    }

    /**
     * Populate form
     *
     * Records CSRF token, if enabled and present, for later use, then
     * calls the parent populate method.
     *
     * @param  P4Cms_Record|array   $values    the values to populate from
     * @return P4Cms_Form           provides fluent interface
     */
    public function populate($values)
    {
        if ($this->hasCsrfProtection() && array_key_exists(static::CSRF_TOKEN_NAME, $values)) {
            $this->_populatedCsrfToken = $values[static::CSRF_TOKEN_NAME];
        }

        return $this->setDefaults($values);
    }

    /**
     * Set element values.
     *
     * Extended here to support setting values from a record object.
     * Accepting a record object allows 'enhanced' elements to look at
     * other aspects of the record and make decisions accordingly.
     *
     * @param   P4Cms_Record|array  $defaults   the default values to set on elements
     * @return  Zend_Form           provides fluent interface
     */
    public function setDefaults($defaults)
    {
        // handle record input.
        if ($defaults instanceof P4Cms_Record) {
            $record   = $defaults;
            $defaults = $record->getValues();

            // handle enhanced elements.
            foreach ($defaults as $field => $value) {
                $element = $this->getElement($field);
                if ($element instanceof P4Cms_Record_EnhancedElementInterface) {
                    $element->populateFromRecord($record);
                    unset($defaults[$field]);
                }
            }
        }

        // Zend form has a strange behavior where sub-forms will populate
        // from the entire values array, rather than their designated portion
        // of the values array if there is no matching key in defaults for
        // the sub-form (e.g. from $defaults instead of $defaults['subForm'])
        // and parent form has no element matching the key.
        //
        // This can have some unfortunate side-effects (top-level values
        // polluting the sub-forms) - to avoid this problem we ensure that
        // there is an entry in the defaults array for every sub-form.
        //
        // Example:
        // assuming we have a $form with element [foo] and a sub-form 'subForm'
        // with elements [foo] and [bar], then
        //    $form->setDefaults(array('foo' => 'foo_value'))
        // will populate only [foo] element, whereas
        //    $form->setDefaults(array('bar' => 'bar_value'))
        // will populate [subForm][bar] element. This fix is to prevent the
        // second case above (i.e. [subForm][bar] will be populated only if
        // defaults array contains 'subForm' => array('bar' => 'bar_value').
        foreach ($this->getSubForms() as $subForm) {
            $key = $subForm->getElementsBelongTo();
            if ($subForm->isArray() && !isset($defaults[$key])) {
                $defaults[$key] = array();
            }
        }

        return parent::setDefaults($defaults);
    }

    /**
     * Set the storage adapter to use to access records.
     *
     * @param   P4Cms_Record_Adapter    $adapter    the adapter to use for record access
     * @return  P4Cms_Form              provides fluent interface.
     */
    public function setStorageAdapter(P4Cms_Record_Adapter $adapter)
    {
        $this->_storageAdapter = $adapter;

        return $this;
    }

    /**
     * Get the storage adapter used by this form to access records.
     *
     * @return  P4Cms_Record_Adapter    the adapter used by this form.
     */
    public function getStorageAdapter()
    {
        if ($this->_storageAdapter instanceof P4Cms_Record_Adapter) {
            return $this->_storageAdapter;
        }

        throw new P4Cms_Form_Exception(
            "Cannot get storage adapter. Adapter has not been set."
        );
    }

    /**
     * Enables or disables the csrf protection for this form; defaults to enabled.
     *
     * @param boolean           $csrf   Whether or not to enable csrf protection
     * @return P4Cms_Form       provides fluid interface
     */
    public function setCsrfProtection($csrf)
    {
        $this->_csrfProtection = (boolean)$csrf;
        return $this;
    }

    /**
     * Returns whether or not this form has csrf protection enabled.
     *
     * Protection is always turned off for anonymous users. For authenticated
     * users protection is on by default but can optionally be disabled.
     *
     * @return boolean      whether or not csrf protection is enabled for the form
     */
    public function hasCsrfProtection()
    {
        return $this->_csrfProtection
            && P4Cms_User::hasActive()
            && !P4Cms_User::fetchActive()->isAnonymous();
    }

    /**
     * For authenticated users, returns the current session value or
     * generate a new value (and set on the session) if none is present.
     *
     * For anonymous users this method simply returns null as they
     * don't receive CSRF protection
     *
     * @return string|null csrf token for this form
     */
    public static function getCsrfToken()
    {
        // skip starting a session for anonymous users and simply return null
        if (!P4Cms_User::hasActive() || P4Cms_User::fetchActive()->isAnonymous()) {
            return null;
        }

        $session = static::_getSession();
        if (!$session->csrfToken) {
            $session->csrfToken = (string) new P4Cms_Uuid;

            // Don't let the presence of a CSRF token in the session
            // prevent caching of future unrelated requests.
            if (P4Cms_Cache::canCache('page')) {
                P4Cms_Cache::getCache('page')->addIgnoredSessionVariable('Forms[csrfToken]');
            }
        }

        return $session->csrfToken;
    }

    /**
     * This method is used to retrieve a populated csrf token for use in
     * validation.
     *
     * @param array $data  Array to search for csrf token, or empty array
     * @return string csrf token
     */
    private function getPopulatedCsrfToken($data = array())
    {
        if (!empty($this->_populatedCsrfToken)) {
            $csrfToken = $this->_populatedCsrfToken;
        } else {
            // a convenience so that module authors working with forms do
            // not have to do anything special to handle csrf validations
            $request = Zend_Controller_Front::getInstance()->getRequest();
            // either get token or null, if the token is not set
            $csrfToken = $request->getParam(static::CSRF_TOKEN_NAME);
        }

        return $csrfToken;
    }

    /**
     * Validate the form, including csrf check
     *
     * @param  array    $data   the data to validate.
     * @return boolean
     */
    public function isValid($data)
    {
        $isValid = parent::isValid($data);

        // validate the CSRF token if protection is enabled
        if ($this->hasCsrfProtection()) {
            if (array_key_exists(static::CSRF_TOKEN_NAME, $data)) {
                $this->_populatedCsrfToken = $data[static::CSRF_TOKEN_NAME];
            }
            if ($this->getPopulatedCsrfToken() != static::getCsrfToken()) {
                $isValid = false;
                $this->addError('Form failed security validation.');
            }
        }

        return $isValid;
    }

    /**
     * Helper function to adjust decorators on checkbox element
     * to position the checkbox label to the right of the checkbox,
     * instead of on the left hand side.
     *
     * @param   Zend_Form_Element   $element    the element to adjust decorators on.
     * @return  Zend_Form_Element   the updated element.
     */
    public static function moveCheckboxLabel(Zend_Form_Element $element)
    {
        // adjust how the auto-label element is decorated
        // to put the label immediately after the checkbox.
        $decorators = array(
            'Zend_Form_Decorator_ViewHelper' => null,
            'P4Cms_Form_Decorator_Label'     => null
        );
        $element->setDecorators(array_merge($decorators, $element->getDecorators()));
        $element->getDecorator('label')
                ->setOption('placement', 'append')
                ->setOption('tag', null);

        return $element;
    }

    /**
     * Prefix the ids of all elements, fieldsets and sub-forms within a form.
     *
     * @param   Zend_Form   $form       a specific form to prefix the ids of.
     * @param   string      $prefix     the prefix to use
     */
    public static function prefixFormIds(Zend_Form $form, $prefix)
    {
        // prefix id of form itself.
        static::_applyIdPrefix($form, $prefix);

        // prefix elements in form.
        foreach ($form->getElements() as $element) {
            static::_applyIdPrefix($element, $prefix);
        }

        // prefix display groups.
        foreach ($form->getDisplayGroups() as $displayGroup) {
            static::_applyIdPrefix($displayGroup, $prefix);
        }

        // prefix sub-forms.
        foreach ($form->getSubForms() as $subForm) {
            $subForm->setIdPrefix($prefix);
        }
    }

    /**
     * Ensure consistent presentation of sub-forms.
     *
     * @param   Zend_Form   $form   the sub-form to normalize.
     * @param   string      $name   the name of the sub-form.
     * @return  Zend_Form   the updated form.
     */
    public static function normalizeSubForm($form, $name = null)
    {
        $name = $name ?: $form->getName();

        $form->setDecorators(
            array(
                'FormElements',
                array(
                    'decorator' => 'HtmlTag',
                    'options'   => array('tag' => 'dl')
                ),
                'Fieldset',
                array(
                    'decorator' => array('DdTag' => 'HtmlTag'),
                    'options'   => array('tag'   => 'dd')
                ),
            )
        );

        // ensure form is identified with a css class.
        $class = $name ? $name . '-sub-form' : '';
        if (!preg_match("/(^| )$class( |$)/", $form->getAttrib('class'))) {
            $form->setAttrib('class', trim($class . ' ' . $form->getAttrib('class')));
        }

        // normalized sub-forms should always put values in an array.
        $form->setIsArray(true);

        return $form;
    }

    /**
     * Add prefix to id attribute of given element.
     *
     * @param   mixed   $element    the element to prefix the id of - can be a
     *                              form, fieldset or standard element.
     * @param   string  $prefix     the prefix to apply
     */
    protected static function _applyIdPrefix($element, $prefix)
    {
        // ensure prefix ends with a dash.
        $prefix = rtrim($prefix, '-') . '-';

        // prefix if id is not blank and not already prefixed.
        if (strlen($element->getId()) && strpos($element->getId(), $prefix) !== 0) {
            $id = $prefix . $element->getId();
            $element->setAttrib('id', $id);
        }
    }

    /**
     * Return the static session object, initializing if necessary.
     *
     * @return Zend_Session_Namespace
     */
    protected static function _getSession()
    {
        if (!static::$_session instanceof Zend_Session_Namespace) {
            static::$_session = new Zend_Session_Namespace('Forms');
        }

        return static::$_session;
    }
}
