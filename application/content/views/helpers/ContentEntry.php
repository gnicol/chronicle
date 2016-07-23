<?php
/**
 * View helper that displays a content entry.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_View_Helper_ContentEntry extends Zend_View_Helper_HtmlElement
{
    const               OPT_FIELDS          = 'fields';
    const               OPT_FIELD_OPTIONS   = 'fieldOptions';
    const               OPT_PRELOAD_FORM    = 'preloadForm';
    const               OPT_TAG_NAME        = 'tagName';
    const               OPT_DISPLAY         = 'display';

    const               DATA_INCLUDE_FIELDS = 'fields';
    const               DATA_INCLUDE_CHANGE = 'change';
    const               DATA_INCLUDE_STATUS = 'status';
    const               DATA_INCLUDE_OPENED = 'opened';

    protected           $_elementDojoType   = 'p4cms.content.Element';
    protected           $_elementDefaultTag = 'div';

    protected           $_entryDojoType     = 'p4cms.content.Entry';
    protected           $_entryElementType  = 'div';

    protected           $_entry             = null;
    protected           $_options           = null;

    protected static    $_defaultEntry      = null;
    protected static    $_defaultOptions    = null;

    /**
     * The default method called for this helper. If no params are passed
     * the default entry/options will be active. If an entry is passed the
     * passed options are always used with it (even if null). If non null
     * options are passed they will always be used.
     *
     * @param   P4Cms_Content|string|null   $entry      The entry to use or null for default
     * @param   array                       $options    The options to use or null for default
     *                                                  when using the default entry
     * @return  Content_View_Helper_ContentEntry    A helper instance with the correct options/entry
     */
    public function contentEntry($entry = null, array $options = null)
    {
        // if there is no entry and no options
        // return the active helper.
        if (is_null($entry) && is_null($options)) {
            return $this;
        }

        // if we made it here we must have a new entry and/or options.
        // create an instance and set the passed entry/options; if either
        // is invalid the new entry will fall back to the active entry
        // options which are stored statically
        $helper = new static;

        // normalize entry to an object for the case of a passed id
        if (!is_null($entry) && !$entry instanceof P4Cms_Content) {
            $entry = P4Cms_Content::fetch($entry);
        }

        $helper->view     = $this->view;
        $helper->_entry   = $entry;
        $helper->_options = $options;

        return $helper;
    }

    /**
     * Set a default entry and options to be used if this helper
     * is called without pasing arguments. This is normaly used
     * by the controller to set the active entry and recommended
     * options.
     *
     * @param P4Cms_Content|null    $entry      entry to use or null for default
     * @param array|null            $options    options to use or null for default
     */
    public function setDefaults(P4Cms_Content $entry = null, array $options = null)
    {
        // normalize entry to an object for the case of a passed id
        if (!is_null($entry) && !$entry instanceof P4Cms_Content) {
            $entry = P4Cms_Content::fetch($entry);
        }

        static::$_defaultEntry   = $entry;
        static::$_defaultOptions = $options;
    }

    /**
     * Returns the default entry or null.
     *
     * @return  P4Cms_Content|null  The default entry if we have one.
     */
    public function getDefaultEntry()
    {
       return static::$_defaultEntry;
    }

    /**
     * Returns the default options array or null.
     *
     * @return array|null   The default options array if we have one.
     */
    public function getDefaultOptions()
    {
        return static::$_defaultOptions;
    }

    /**
     * Return the entry being used for this helper instance. Will return
     * the entry passed to contentEntry() if valid falling back to the
     * default entry.
     *
     * @return P4Cms_Content|null   The active content entry or null
     */
    public function getEntry()
    {
        if ($this->_entry instanceof P4Cms_Content) {
            return $this->_entry;
        }

        if (!static::$_defaultEntry instanceof P4Cms_Content) {
            throw new P4Cms_Content_Exception(
                "No entry has been set on the Content Entry helper"
            );
        }

        return static::$_defaultEntry;
    }

    /**
     * Return the options being used for this helper instance. The default
     * options are returned if we are on the default entry and null options
     * were passed to contentEntry(). If we are using a non-default content
     * entry the passed options are always returned even when null.
     *
     * We only ever use the default options with the default entry as settings
     * such as include form are likely to be set here and are not generally
     * advisable when rendering other content entries.
     *
     * @return P4Cms_Content|null   The active content entry or null
     */
    public function getOptions()
    {
        if ($this->_options != null || $this->_entry instanceof P4Cms_Content) {
            return $this->_options;
        }

        return static::$_defaultOptions;
    }

    /**
     * A magic method which calls through to render; see render for details.
     *
     * @return  string  The output of our render method.
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            return "";
        }
    }

    /**
     * Output the appropriate markup to display a content entry and
     * all of its associated fields.
     *
     * @return  string                  the markup for a content entry.
     *
     * @publishes   p4cms.content.render
     *              Return the passed HTML after applying any modifications. Allows customization of
     *              the markup for a content entry.
     *              string          $html       The HTML markup for a content entry.
     *              P4Cms_Content   $content    The content entry being rendered.
     *              array           $options    An array of options for the view helper to influence
     *                                          rendering.
     */
    public function render()
    {
        // start the content dijit.
        $html = $this->open();

        // show the rendered fields of the content entry.
        foreach ($this->getRenderedFields() as $field) {
            $html .= $this->element($field);
        }

        // close the content entry.
        $html .= $this->close();

        // allow third-parties to influence rendering.
        $html = P4Cms_PubSub::filter('p4cms.content.render', $html, $this->getEntry(), $this->getOptions());

        return $html;
    }

    /**
     * Get all rendered fields of the content entry.
     *
     * @return  array   list of all fields of the content entry
     */
    public function getRenderedFields()
    {
        $entry   = $this->getEntry();
        $options = $this->getOptions();

        // if no fields specified, display all fields.
        $type   = $entry->getContentType();
        $fields = isset($options[static::OPT_FIELDS]) && is_array($options[static::OPT_FIELDS])
            ? $options[static::OPT_FIELDS]
            : null;
        if ($fields === null) {
            $fields = $type->getElementNames();
        }

        $rendered = array();
        foreach ($fields as $field) {
            if (!$type->hasElement($field)) {
                continue;
            }

            // if rendering this field is disabled, skip it.
            $fieldDefinition = $type->getElement($field);
            if (isset($fieldDefinition['display']['render'])
                && !$fieldDefinition['display']['render']
            ) {
                continue;
            }

            $rendered[] = $field;
        }

        return $rendered;
    }

    /**
     * Output the appropriate markup to open the active entry.
     * Produce dijit markup if user can edit this content entry or plain html otherwise.
     *
     * @return  string  the opening markup for a content entry.
     */
    public function open()
    {
        $entry   = $this->getEntry();
        $options = $this->getOptions();

        // pull out the version and headVersion if possible; defaults to blank
        $version     = '';
        $headVersion = '';
        if ($entry->getId()) {
            $file = $entry->toP4File();
            if ($file->hasStatusField('headRev')) {
                $version     = $file->getStatus('headRev');
                $headVersion = P4_File::fetch(
                    P4_File::stripRevspec($file->getFilespec())
                )->getStatus('headRev');
            }
        }

        // prepare html attributes.
        $attribs = array(
            "id"                => "content-entry-" . $entry->getId(),
            "class"             => "content-entry content-entry-type-". $entry->getContentTypeId(),
            "dojoType"          => $this->_entryDojoType,
            "contentType"       => $entry->getContentTypeId(),
            "contentId"         => $entry->getId(),
            "contentTitle"      => $entry->getTitle(),
            "contentVersion"    => $version,
            "headVersion"       => $headVersion,
            "deleted"           => Zend_Json::encode($entry->isDeleted())
        );

        // add all privileges for which user has access to 'content/entryID' resource.
        $user       = P4Cms_User::fetchActive();
        $resource   = 'content/' . $entry->getId();
        $privileges = $user->getAllowedPrivileges($resource);

        // add list with privileges to the dijit attributes.
        $attribs["allowedPrivileges"] = implode(", ", $privileges);

        // declare entry markup (_htmlAttrib() does escaping).
        $html = "<" . $this->_entryElementType . $this->_htmlAttribs($attribs) . ">";

        // optionally preload the form.
        if ($this->_shouldPreloadForm($entry, $privileges, $options)) {
            $formOptions = array('entry' => $entry);

            // if request specifies an id prefix, add it to options.
            $request = Zend_Controller_Front::getInstance()->getRequest();
            $formOptions['idPrefix'] = $request->getParam('formIdPrefix');

            $form = new Content_Form_Content($formOptions);
            $form->populate();

            $iconUrl = $this->view->url(
                array(
                    'module'        => 'content',
                    'controller'    => 'type',
                    'action'        => 'icon',
                    'id'            => $entry->getContentType()->getId()
                )
            );

            // render form into markup.
            $html .= "<div class='p4cms-ui content-form-container' "
                  .  "dojoType='dojox.layout.ContentPane' "
                  .  "style='display: none'>"
                  .  "<div class=\"content-type-heading\">"
                  .  "<img src=\"$iconUrl\" width=\"64\" height=\"64\"/>"
                  .  "<span>" . $this->view->escape($entry->getContentType()->getLabel()) ."</span>"
                  .  "</div>\n"
                  .  $form->render()
                  .  "</div>";
        }

        return $html;
    }

    /**
     * Indicate whether an element exists in the content entry.
     *
     * @param   string  $name       the name of the element/field to display.
     * @return  bool    true if the element exists in the entry, false otherwise
     */
    public function hasElement($name)
    {
        return $this->getEntry()->hasField($name) ? true : false;
    }

    /**
     * Output the appropriate markup to display a content element.
     *
     * @param   string                  $name       the name of the element/field to display.
     * @param   null|array              $options    options to influence entry markup.
     * @return  string|bool             the markup for the content element or false
     */
    public function element($name, array $options = null)
    {
        // utilize the default options if none were passed
        $defaults   = $this->getOptions();
        $optionsKey = static::OPT_FIELD_OPTIONS;
        $options    = is_null($options) && isset($defaults[$optionsKey], $defaults[$optionsKey][$name])
            ? $defaults[$optionsKey][$name]
            : $options;

        // get the entry and, defensive of null, the type
        $entry = $this->getEntry();
        $type  = $entry ? $entry->getContentType() : null;

        // return false if entry or element aren't known
        // to avoid throwing an exception
        if (!$entry || !$type->hasElement($name)) {
            return false;
        }

        // determine display options
        $displayOptions = isset($options[static::OPT_DISPLAY])
            ? $options[static::OPT_DISPLAY]
            : array();

        $element    = $type->getFormElement($name);
        $definition = $type->getElement($name);
        $label      = $element->getLabel() ? : $name;
        $value      = $entry->getDisplayValue($name, $displayOptions);

        // prepare html attributes.
        $attribs = array(
            "class"         => "content-element content-element-type-" . $definition['type']
                            .  " content-element-" . $name,
            "dojoType"      => $this->_elementDojoType,
            "elementName"   => $name,
            "elementLabel"  => $label,
            "contentId"     => $entry->getId(),
            "required"      => $element->isRequired() ? 'true' : 'false'
        );

        // allow caller to specify an element id.
        if (isset($options['id'])) {
            $attribs['id'] = $options['id'];
        }

        $tagName = $this->_getTagName($definition, $options);

        // _htmlAttribs() does HTML attribute escaping;
        // $value is escaped in P4Cms_Content::getDisplayValue() as long as
        // proper display filters are defined in element's definition
        return "<" . $tagName . $this->_htmlAttribs($attribs) . ">"
            . $value . "</" . $tagName . ">";
    }

    /**
     * Output the closing tag for a content entry dijit.
     *
     * @return  string  the closing tag for a content entry.
     *
     * @publishes   p4cms.content.render.close
     *              Return the passed HTML after applying any modifications. Allows customization of
     *              the closing HTML markup for a content entry.
     *              string                              $html       The closing HTML markup for the
     *                                                              content entry.
     *              Content_View_Helper_ContentEntry    $helper     The view helper rendering the
     *                                                              content entry.
     */
    public function close()
    {
        $html = "</" . $this->_entryElementType . ">";

        // allow third-parties to influence rendering.
        $html = P4Cms_PubSub::filter(
            'p4cms.content.render.close',
            $html,
            $this
        );

        return $html;
    }

    /**
     * Return an array with data about the content entry.
     *
     * Supports the options:
     *  DATA_INCLUDE_FIELDS => true/false/array of field ids (true is default)
     *  DATA_INCLUDE_CHANGE => true/false                    (false is default)
     *  DATA_INCLUDE_STATUS => true/false                    (false is default)
     *  DATA_INCLUDE_OPENED => true/false                    (false is default)
     *
     * @param   array|null  $options    Any custom options to use
     * @return  array       The requested entry details
     */
    public function data(array $options = null)
    {
        // normalize options to array and set defaults
        $options = ((array) $options) + array(
            static::DATA_INCLUDE_FIELDS => true,
            static::DATA_INCLUDE_CHANGE => false,
            static::DATA_INCLUDE_STATUS => false,
            static::DATA_INCLUDE_OPENED => false
        );

        // the data array will be our final return
        $data = array();

        // deal with the fields option
        $fields = $options[static::DATA_INCLUDE_FIELDS];
        if (!empty($fields) && (bool)$fields) {
            $data['fields'] = $this->getEntry()->getValues();

            // if we have an array of allowed fields enforce it
            if (is_array($fields)) {
                foreach ($data['fields'] as $key => $value) {
                    if (!in_array($key, $fields)) {
                        unset($data['fields'][$key]);
                    }
                }
            }
        }

        // deal with the opened option
        if ($options[static::DATA_INCLUDE_OPENED] && $this->getEntry()->getId()) {
            try {
                $data['opened'] = P4Cms_Content_Opened::fetch(
                    $this->getEntry()->getId(),
                    $this->getEntry()->getAdapter()
                )->getUsers();
            } catch (P4Cms_Record_NotFoundException $e) {
                // no data to add no-one has it open
            }
        }

        $file = $this->getEntry()->toP4File();

        // include the change details if requested
        if ((bool) $options[static::DATA_INCLUDE_CHANGE]) {
            $data['change'] = $file->getChange()->getValues();
        }

        // include the status if requested
        if ((bool) $options[static::DATA_INCLUDE_STATUS]) {
            $data['status'] = array(
                'Version'   => $file->getStatus('headRev'),
                'Action'    => $file->getStatus('headAction')
            );
        }

        return $data;
    }

    /**
     * Get the view object set on this helper.
     *
     * @return  Zend_View_Interface     the view object set on this helper.
     * @throws  Content_Exception       if no view object set.
     */
    public function getView()
    {
        if (!$this->view instanceof Zend_View_Interface) {
            throw new Content_Exception(
                "Cannot get view object. No valid view object has been set."
            );
        }

        return $this->view;
    }

    /**
     * Get the html tag name to use for this element.
     *
     * @param   array       $definition     the element definition.
     * @param   null|array  $options        options to influence entry markup.
     * @return  string  the html tag name for this element.
     */
    protected function _getTagName($definition, array $options = null)
    {
        if (is_array($options) && isset($options[static::OPT_TAG_NAME])) {
            return $options[static::OPT_TAG_NAME];
        }

        if (isset($definition['display'][static::OPT_TAG_NAME])) {
            return $definition['display'][static::OPT_TAG_NAME];
        }

        return $this->_elementDefaultTag;
    }

    /**
     * Determine if the form should be preloaded. Form will only be
     * preloaded if preload option is set and user has permission.
     *
     * @param   string|P4Cms_Content    $entry      a content entry id or instance.
     * @param   array                   $privileges privileges current user has for this entry.
     * @param   null|array              $options    options to influence entry markup.
     * @return  string                  the opening markup for a content entry.
     */
    protected function _shouldPreloadForm($entry, array $privileges, array $options = null)
    {
        // if user has no add/edit permission for this entry, return false.
        $privilege = $entry->hasId() ? 'edit' : 'add';
        if (!in_array($privilege, $privileges)) {
            return false;
        }

        $preloadOption = Content_View_Helper_ContentEntry::OPT_PRELOAD_FORM;
        if (isset($options[$preloadOption])) {
            return $options[$preloadOption];
        }

        return false;
    }
}
