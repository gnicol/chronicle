<?php
/**
 * Provides storage for content entries.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Content extends P4Cms_Record_PubSubRecord
{
    const               TYPE_FIELD          = 'contentType';
    const               OWNER_FIELD         = 'contentOwner';

    protected static    $_storageSubPath    = 'content';
    protected static    $_topic             = 'p4cms.content.record';
    protected static    $_typeCache         = array();
    protected static    $_fileContentField  = 'file';
    protected static    $_fields            = array(
        self::TYPE_FIELD    => array(
            'mutator'       => 'setContentType'
        ),
        self::OWNER_FIELD   => array(
            'accessor'      => 'getOwner',
            'mutator'       => 'setOwner'
        )
    );
    protected static    $_uriCallback       = null;

    /**
     * Pub/sub topic documentation for parent fetchAll and count methods.
     *
     * @publishes   p4cms.content.record.query
     *              Adjust the passed query to influence the results of P4Cms_Content's
     *              fetch/fetchAll/count results (e.g. to exclude unpublished content).
     *              P4Cms_Record_Query      $query      The query that is about to be used to
     *                                                  retrieve records from storage.
     *              P4Cms_Record_Adapter    $adapter    The current storage connection adapter.
     */

    /**
     * Set the id of this content.
     * Extends parent to use ContentId validator instead of RecordId validator.
     *
     * @param   string|int|null     $id     the identifier of this entry.
     * @return  P4Cms_Content       provides fluent interface.
     */
    public function setId($id)
    {
        // normalize empty strings to null; this is helpful for form input data
        if (is_string($id) && !strlen($id)) {
            $id = null;
        }

        $validator = new P4Cms_Validate_ContentId;
        if ($id !== null && !$validator->isValid($id)) {
            throw new InvalidArgumentException("Cannot set id. Given id is invalid.");
        }

        return parent::setId($id);
    }

    /**
     * Set the content type definition to use for this content entry.
     *
     * @param   string|P4Cms_Content_Type   $type   either a string for the content type id
     *                                              of an instance of a content type.
     */
    public function setContentType($type)
    {
        if ($type instanceof P4Cms_Content_Type) {
            $type = $type->getId();
        }

        return $this->_setValue(static::TYPE_FIELD, $type);
    }

    /**
     * Get the content type id for this entry.
     *
     * @return  string  the id of this entry's content type.
     */
    public function getContentTypeId()
    {
        return $this->_getValue(static::TYPE_FIELD);
    }

    /**
     * Get the content type definition for this content entry.
     *
     * @return  P4Cms_Content_Type  instance of the content type for this content entry.
     */
    public function getContentType()
    {
        return static::_getContentType($this->getContentTypeId(), $this->getAdapter());
    }

    /**
     * Determine if this content entry has a valid content type.
     *
     * @return  bool    true if the entry has a valid content type.
     */
    public function hasValidContentType()
    {
        try {
            $type = $this->getContentType();
            if ($type->hasValidElements()) {
                return true;
            } else {
                return false;
            }
        } catch (P4Cms_Content_Exception $e) {
            return false;
        }
    }

    /**
     * Get all of the field names for this content entry.
     * Adds the fields defined by the content type.
     *
     * @return  array   a list of field names for this spec.
     */
    public function getFields()
    {
        $fields = array_flip(parent::getFields());

        // add fields from the content type.
        if ($this->hasValidContentType()) {
            $fields = $this->getContentType()->getElements() + $fields;
        }

        return array_keys($fields);
    }

    /**
     * Get the title for this piece of content.
     * If the content has no title, returns the id.
     *
     * @return  string  the title or the content id if no title is set.
     */
    public function getTitle()
    {
        $title = $this->hasField('title')
            ? trim($this->_getValue('title'))
            : null;

        return $title ?: $this->getId();
    }

    /**
     * Get the time that this content entry was last modified (submitted).
     *
     * @return  int     the modification timestamp.
     */
    public function getModTime()
    {
        return $this->_getP4File()->getStatus('headTime');
    }

    /**
     * Get an excerpt of the content. If the content entry has an 'excerpt' field,
     * it will be used; otherwise, the body field will be truncated. If there is
     * no body field, returns null.
     *
     * @param   int     $length         optional - the maximum length of the excerpt
     *                                  set to zero to turn off truncating.
     *                                  defaults to 100 characters.
     * @param   array   $options        Options influencing the returned excerpt. Valid options:
     *          bool    filterHtml      optional - set to true to convert HTML to text.
     *                                  defaults to true
     *          bool    fullExcerpt     optional - set to true to get full excerpt field; excerptField still truncated.
     *                                  defaults to false
     *          bool    keepEntities    optional - set to true to keep HTML entities intact during HTML filtering.
     *                                  defaults to false
     *          string  excerptField    optional - alternate field to use for excerpt if excerpt field does not exist.
     *                                  defaults to body
     * @return  string  the excerpt text
     */
    public function getExcerpt($length = 100, array $options = array())
    {
        // setup option defaults
        $options = array_merge(
            array(
                'filterHtml'    => true,
                'fullExcerpt'   => false,
                'keepEntities'  => false,
                'excerptField'  => 'body'
            ),
            $options
        );

        if ($this->hasField('excerpt')) {
            if ($options['fullExcerpt']) {
                $length = 0;
            }
            $excerpt = $this->getValue('excerpt');
        } else if ($this->hasField($options['excerptField'])) {
            $excerpt = $this->getValue($options['excerptField']);
        } else if ($this->hasField('body')) {
            $excerpt = $this->getValue('body');
        } else {
            return null;
        }

        // convert to plain-text.
        if ($options['filterHtml']) {
            $filter  = new P4Cms_Filter_HtmlToText(array('keepEntities' => $options['keepEntities']));
            $excerpt = $filter->filter($excerpt);
        }

        // truncate excerpt.
        if ($length !== 0) {
            $truncate = new P4Cms_View_Helper_Truncate;
            $excerpt  = $truncate->truncate($excerpt, $length, null, false);
        }

        return $excerpt;
    }

    /**
     * Get the URI to this content entry.
     * If an action is provided, will return a URI to perform the given action.
     *
     * @param   string  $action             optional - action to perform - defaults to 'view'.
     * @param   array   $params             optional - additional params to add to the uri.
     * @return  string                      the uri of the content entry.
     */
    public function getUri($action = 'view', $params = array())
    {
        return call_user_func(static::getUriCallback(), $this, $action, $params);
    }

    /**
     * Cast this content entry to a lucene document for indexing purposes.
     *
     * @return  Zend_Search_Lucene_Document     a lucene document suitable for indexing.
     */
    public function toLuceneDocument()
    {
        // create lucene document from this content entry.
        return new P4Cms_Content_LuceneDocument($this);
    }

    /**
     * Save this record. If record does not have an id, this will create one.
     * Extends save() to add to search index.
     *
     * @param   string              $description    optional - a description of the change.
     * @param   null|string|array   $options        optional - resolve flags, to be used if conflict
     *                                              occurs. See P4_File::resolve() for details.
     * @return  P4Cms_Record        provides a fluent interface
     *
     * @publishes   p4cms.search.update
     *              Perform search indexing related operations with the passed document. Called when
     *              content is saved, indicating it has been added or edited.
     *              Zend_Search_Lucene_Document|P4Cms_Content   $document   The entry being updated.
     *
     * @publishes   p4cms.content.record.preSave
     *              Perform operations on a content entry just prior to its being saved.
     *              P4Cms_Content   $entry  The content entry that is about to be saved.
     *
     * @publishes   p4cms.content.record.postSave
     *              Perform operations on a content entry just after it's saved (but before the
     *              batch it is in gets committed).
     *              P4Cms_Content   $entry  The content entry that has just been saved.
     */
    public function save($description = null, $options = null)
    {
        parent::save($description, $options);

        // update search index.
        P4Cms_PubSub::publish("p4cms.search.update", $this);

        return $this;
    }

    /**
     * Delete this record.
     * Extends delete() to remove from search index.
     *
     * @param   string  $description  optional - a description of the change.
     * @return  P4Cms_Record          provides fluent interface.
     *
     * @publishes   p4cms.search.delete
     *              Perform operations when an entry is deleted from the search-index. Note: Updates
     *              to existing entries are accomplished via delete/add.
     *              Zend_Search_Lucene_Document|P4Cms_Content   $document   The entry being deleted.
     *
     * @publishes   p4cms.content.record.delete
     *              Perform operations on a content entry just prior to deletion.
     *              P4Cms_Content   $entry  The content entry that is about to be deleted.
     */
    public function delete($description = null)
    {
        parent::delete($description);

        // remove from search index.
        P4Cms_PubSub::publish("p4cms.search.delete", $this);

        return $this;
    }

    /**
     * Get a field's value after output/display filters are applied.
     *
     * @param   string  $field  the name of the field to get the filtered value of.
     * @return  string  the filtered value of hte field.
     */
    public function getFilteredValue($field)
    {
        $type  = $this->getContentType();
        $value = $this->getExpandedValue($field);

        // apply element's display filters.
        foreach ($type->getDisplayFilters($field) as $filter) {
            $value = $filter->filter($value);
        }

        return $value;
    }

    /**
     * Get a field's expanded value. If a field contains
     * macros and macros are enabled for the field, this will
     * return the field value with macros evaluated.
     *
     * @param   string  $field  the name of the field to get the expanded value of.
     * @return  string  the expanded value of the field.
     */
    public function getExpandedValue($field)
    {
        $type    = $this->getContentType();
        $element = $type->getElement($field);
        $value   = $this->getValue($field);

        // if macros are enabled, invoke them.
        if (isset($element['options']['macros']['enabled'])) {
            $filter = new P4Cms_Filter_Macro;
            $filter->setContext(array('content' => $this, 'element' => $element));
            $value = $filter->filter($value);
        }

        return $value;
    }

    /**
     * Get a field's display value. The display value is the result
     * of rendering a field's display decorators. If a field element
     * has no decorators, the plain (expanded) value is returned.
     *
     * @param   string      $field      the name of the field to get the display value of.
     * @param   array       $options    optional - display options
     * @return  string  the display value of the field.
     */
    public function getDisplayValue($field, array $options = array())
    {
        $type    = $this->getContentType();
        $element = clone $type->getFormElement($field);
        $value   = $this->getFilteredValue($field);

        // set the associated content record (if possible) on the element
        // for decorators to access - requires enhanced element.
        if ($element instanceof P4Cms_Content_EnhancedElementInterface) {
            $element->setContentRecord($this);
        }

        // get decorators to render the element from options param or from
        // the content type.
        $decorators = isset($options['decorators'])
            ? $element->setDecorators($options['decorators'])->getDecorators()
            : $type->getDisplayDecorators($element);

        // if no decorators, just return the plain field value.
        if (empty($decorators)) {
            return $value;
        }

        // we have already applied display filters above, clear any
        // element input filters as we don't want them in this context.
        $element->clearFilters();

        // set the field value on the element for decorators to access
        // note, some elements (e.g. file/image) will ignore attempts to
        // set a value; therefore, decorators will not be able to retrieve
        // the field value from such elements directly.
        $element->setValue($value);

        // render display value using decorators.
        $content = '';
        foreach ($decorators as $decorator) {
            $decorator->setElement($element);
            if ($decorator instanceof P4Cms_Content_EnhancedDecoratorInterface) {
                $decorator->setContentRecord($this);
            }
            $content = $decorator->render($content);
        }
        return $content;
    }

    /**
     * Get the owner of this content entry.
     *
     * @return  string  id of owner user.
     */
    public function getOwner()
    {
        return $this->_getValue(static::OWNER_FIELD);
    }

    /**
     * Set the owner of this content entry.
     *
     * @param   P4Cms_User|string|null  $user   user to set as this content entry owner.
     * @return  P4Cms_Content           provides fluent interface.
     */
    public function setOwner($user)
    {
        if ($user instanceof P4Cms_User) {
            $user = $user->getId();
        } else if (!is_string($user) && !is_null($user)) {
            throw new InvalidArgumentException(
                "User must be an instance of P4Cms_User, a string or null."
            );
        }

        return $this->_setValue(static::OWNER_FIELD, $user);
    }

    /**
     * Set a field value to the contents of the given file.
     * Extended to capture file metadata such as mime-type and image size.
     *
     * @param   string          $field      the field to set the value of.
     * @param   string          $file       the full path to the file to read from.
     * @param   string          $name       optionally provide an explicit name
     *                                      if none is given, it will be basename of file.
     * @param   string          $type       optionally provide an explicit mime-type
     *                                      if none is given, it will be auto-detected.
     * @return  P4Cms_Record                provides fluent interface.
     * @throws  InvalidArgumentException    if the given file does not exist.
     */
    public function setValueFromFile($field, $file, $name = null, $type = null)
    {
        parent::setValueFromFile($field, $file);

        // attempt to capture file metadata - note, image size is expected
        // to fail (silently) for non-images or unsupported image formats.
        $metadata = array(
            'mimeType' => $type ?: P4Cms_FileUtility::getMimeType($file),
            'filename' => $name ?: basename($file),
            'fileSize' => filesize($file)
        );
        $dimensions = @getimagesize($file);
        if (is_array($dimensions)) {
            $metadata['dimensions'] = array('width' => $dimensions[0], 'height' => $dimensions[1]);
        }

        $this->setFieldMetadata($field, $metadata);

        return $this;
    }

    /**
     * Set the function to use when generating URI's for content entries.
     *
     * @param   null|callback   $function   The callback function for URI generation. The
     *                                      function should expect three parameters:
     *                                      - $content (P4Cms_Content)
     *                                      - $action  (string)
     *                                      - $params  (array)
     *                                      Returns a string (the uri).
     */
    public static function setUriCallback($function)
    {
        if (!is_callable($function) && $function !== null) {
            throw new InvalidArgumentException(
                'Cannot set URI callback. Expected a callable function or null.'
            );
        }

        static::$_uriCallback = $function;
    }

    /**
     * Determines if a valid URI callback has been set.
     *
     * @return  bool    True if valid URI callback set, False otherwise.
     */
    public static function hasUriCallback()
    {
        return is_callable(static::$_uriCallback);
    }


    /**
     * Returns the current URI callback if one has been set.
     *
     * @return  callback    The current URI callback.
     * @throws  P4Cms_Content_Exception     If no URI callback has been set.
     */
    public static function getUriCallback()
    {
        if (!static::hasUriCallback()) {
            throw new P4Cms_Content_Exception(
                'Cannot get URI callback, no URI callback has been set.'
            );
        }

        return static::$_uriCallback;
    }

    /**
     * Clear the static type cache.
     * If a valid adapter is passed, only that connections cache will be cleared; otherwise
     * all cached types are cleared on all connections.
     *
     * @param P4Cms_Record_Adapter  $adapter    optional - adapter to clear on or null for all
     */
    public static function clearTypeCache(P4Cms_Record_Adapter $adapter = null)
    {
        if (!$adapter) {
            static::$_typeCache = array();
            return;
        }

        $cacheKey = spl_object_hash($adapter);
        if (array_key_exists($cacheKey, static::$_typeCache)) {
            unset(static::$_typeCache[$cacheKey]);
        }
    }

    /**
     * Get the set of all content types in storage.
     * Caches and indexes (by id) the results of P4Cms_Content_Type::fetchAll().
     *
     * @param   P4Cms_Record_Adapter    $adapter    the adapter in use.
     * @return  array                   all content types indexed by content type id.
     */
    protected static function _getContentTypes(P4Cms_Record_Adapter $adapter)
    {
        // cache must be divided by storage adapter.
        $cacheKey = spl_object_hash($adapter);

        // load the content types (but only fetch them once).
        if (!array_key_exists($cacheKey, static::$_typeCache)) {
            $query = new P4Cms_Record_Query;
            $query->setIncludeDeleted(true);
            $types = P4Cms_Content_Type::fetchAll($query, $adapter);

            // cache the content types indexed by id.
            if ($types->count()) {
                $types = array_combine(
                    $types->invoke('getId'),
                    $types->toArray(true)
                );
            }

            static::$_typeCache[$cacheKey] = $types;
        }

        return static::$_typeCache[$cacheKey];
    }

    /**
     * Get a specific content type instance.
     * Utilizes _getContentTypes() to benefit from cache.
     *
     * @param   string                  $id         a string for the content type id
     * @param   P4Cms_Record_Adapter    $adapter    the adapter in use.
     * @return  P4Cms_Content_Type      an instance of the requested content type.
     */
    protected static function _getContentType($id, P4Cms_Record_Adapter $adapter)
    {
        $types = static::_getContentTypes($adapter);
        $type  = $id && isset($types[$id]) ? $types[$id] : null;

        // create a in-memory type if we couldn't locate one
        if (!$type) {
            $type  = new P4Cms_Content_Type;
            $type->setLabel("Missing Type" . ($id ? " ($id)" : ""));
        }

        return $type;
    }


    /**
     * Extends parent to pull defaults from content type definition.
     *
     * @param   string  $field  the name of the field to get the value of.
     * @return  mixed   the default value of the field - null for no default.
     */
    protected function _getDefaultValue($field)
    {
        // attempt to query content type for default.
        if ($field !== static::TYPE_FIELD) {
            try {
                $type    = $this->getContentType();
                $element = $type->getFormElement($field);
                $value   = $element->getValue();

                if ($value !== null) {
                    return $value;
                }
            } catch (Exception $e) {
                // intentionally ignore errors fetching content type values
            }
        }

        return parent::_getDefaultValue($field);
    }
}
