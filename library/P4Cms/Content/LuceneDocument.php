<?php
/**
 * P4Cms Content Lucene Document.
 *
 * - Allows a Zend Search Lucene document to be created from a content entry.
 * - Determines if a P4Cms Content field should be indexed.
 * - Specifies how a Content field should be indexed.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        consider extracting additional metadata for certain document types
 */
class P4Cms_Content_LuceneDocument extends Zend_Search_Lucene_Document
{
    protected   $_content   = null;

    /**
     * Make a new lucene document instance for the given content entry.
     *
     * @param   P4Cms_Content   $content    the content entry to make a lucene document for.
     */
    public function __construct(P4Cms_Content $content)
    {
        $this->_content = $content;

        // setup the lucene document fields.
        $this->_loadFields();
    }

    /**
     * Get the content entry associated with the Lucene document.
     *
     * @return  P4Cms_Content   the content entry this lucene document represents.
     */
    public function getContentEntry()
    {
        return $this->_content;
    }

    /**
     * Convert the content fields into lucene document fields.
     */
    protected function _loadFields()
    {
        // convert field into lucene document field.
        foreach ($this->_getContentFields() as $name => $data) {

            // skip fields that should not be indexed.
            if ($this->_isIndexDisabled($name, $data)) {
                continue;
            }

            // convert and add field to document.
            $field = $this->_toLuceneField($name, $data);
            if ($field instanceof Zend_Search_Lucene_Field) {
                $this->addField($field);
            }
        }
    }

    /**
     * Collect all of the fields for the content entry with
     * information about the field and the value pulled from
     * the content entry.
     *
     * @return  array   the list of all content fields and their details/values.
     */
    protected function _getContentFields()
    {
        $entry = $this->getContentEntry();
        $type  = $this->getContentEntry()->getContentType();

        // start with default/built-in/required fields.
        $fields = array(
            'uri'           => array(
                'value'     => $entry->getUri(),
                'search'    => array('index'    => array('type' => 'keyword'))
            ),
            'title'         => array(
                'value'     => $entry->getTitle(),
                'search'    => array('index'    => array('type' => 'text'))
            ),
            'excerpt'       => array(
                'value'     => $entry->getExcerpt(),
                'search'    => array('index'    => array('type' => 'unindexed'))
            ),
            'contentId'     => array(
                'value'     => $entry->getId(),
                'search'    => array('index'    => array('type' => 'unindexed')),
                'metadata'  => array('mimeType' => 'text/plain')
            ),
            'contentType'   => array(
                'value'     => $entry->getContentTypeId(),
                'search'    => array('index'    => array('type' => 'unindexed')),
                'metadata'  => array('mimeType' => 'text/plain')
            ),
            'resource'      => array(
                'value'     => 'content',
                'search'    => array('index'    => array('type' => 'unindexed'))
            ),
            'privilege'     => array(
                'value'     => 'access',
                'search'    => array('index'    => array('type' => 'unindexed'))
            )
        );

        // add the fields from content type.
        $fields = $this->_mergeFields($fields, $type->getElements());

        // add in values and metadata from the content entry.
        foreach ($entry->getValues() as $field => $value) {
            if (array_key_exists($field, $fields)) {
                $fields[$field]['value']    = $value;
                $fields[$field]['metadata'] = $entry->getFieldMetadata($field);
            }

            // add filename if it does not exist already
            if (isset($fields[$field]['metadata']['filename']) && !array_key_exists('filename', $fields)) {
                $fields['filename']['value']  = $fields[$field]['metadata']['filename'];
                $fields['filename']['search'] = array('index' => array('type' => 'unstored'));
            }
        }

        return $fields;
    }

    /**
     * Convert from a field definition/value to a lucene document field.
     *
     * @param   string  $name                   the name of the field to convert.
     * @param   array   $data                   the details and value of the field.
     * @return  Zend_Search_Lucene_Field|null   lucene document field object or null if we
     *                                          can't create one.
     */
    protected function _toLuceneField($name, $data)
    {
        // presently we can't do anything reasonable with arrays/objects/etc.
        // in the meantime, we just defend against these data types.
        if (!array_key_exists('value', $data) || !is_scalar($data['value'])) {
            return null;
        }

        // write value to a temp file.
        $tempFile = tempnam(sys_get_temp_dir(), $name);
        file_put_contents($tempFile, $data['value']);

        // detect mime-type and encoding.
        $data['tempFile'] = $tempFile;
        $encoding         = $this->_detectEncoding($data);
        $data['encoding'] = $encoding ?: 'utf8'; // default to utf8
        $data['mimeType'] = isset($data['metadata']['mimeType'])
            ? $data['metadata']['mimeType']
            : P4Cms_Validate_File_MimeType::getTypeOfFile($tempFile);

        // determine lucene field type.
        $type = $this->_getLuceneFieldType($name, $data);

        // attempt to filter/prepare the value and
        // create lucene field of appropriate type.
        try {
            $value = $this->_prepareFieldValue($name, $data);
            $field = Zend_Search_Lucene_Field::$type(
                $name,
                $value,
                $data['encoding']
            );
        } catch (P4Cms_Content_Exception $e) {
            $field = null;
        }

        // clean-up temp.
        unlink($tempFile);

        return $field;
    }

    /**
     * Determine the correct lucene field type to use for the given
     * content field definition/value. Checks for explicit index type
     * in field data - defaults to 'unstored'.
     *
     * @param   string  $name   the name of the field to convert.
     * @param   array   $data   the details and value of the field.
     * @return  string          the type of lucene field to use:
     *                             keyword - [ ] tokenized  [x] indexed  [x] stored
     *                           unindexed - [ ] tokenized  [ ] indexed  [x] stored
     *                              binary - [ ] tokenized  [ ] indexed  [x] stored
     *                                text - [x] tokenized  [x] indexed  [x] stored
     *                            unstored - [x] tokenized  [x] indexed  [ ] stored
     */
    protected function _getLuceneFieldType($name, $data)
    {
        $types = array('keyword', 'unindexed', 'binary', 'text', 'unstored');

        // if the field definition specifies a valid type, use it.
        if (isset($data['search']['index']['type'])
            && in_array($data['search']['index']['type'], $types)
        ) {
            return $data['search']['index']['type'];
        }

        return 'unstored';
    }

    /**
     * Determine if a given field should not be indexed.
     *
     * @param   string  $name   the name of the field to be indexed.
     * @param   array   $data   the details and value of the field.
     * @return  bool    true if we should not index this field; false otherwise.
     */
    protected function _isIndexDisabled($name, $data)
    {
        return isset($data['search']['index']['disabled'])
            && $data['search']['index']['disabled'];
    }

    /**
     * Get the filters to apply to the given field value before it is indexed.
     * The filters to use can be specified in the content type field definition.
     *
     * @param   string  $name   the name of the field to be indexed.
     * @param   array   $data   the details and value of the field.
     * @return  array   the set of filters to apply to the field value.
     * @todo    automatically select filters based on mime-type and/or file extension
     *          alternatively, publish as pub/sub topic to collect filters.
     */
    protected function _getIndexFilters($name, $data)
    {
        // early exit if the field definition does not specify filters.
        if (!isset($data['search']['index']['filters'])) {
            return array();
        }

        $options = array('fieldName' => $name, 'fieldData' => $data);
        $filters = $data['search']['index']['filters'];

        // add field name and data to filter options.
        /*foreach ($filters as $filter) {
            $filter['options'] = isset($filter['options'])
                ? array_merge($options, $filter['options'])
                : $options;
        }*/

        // use a form with a dummy element to leverage filter plugin loading.
        $form = new P4Cms_Form;
        $form->addElement('text', 'dummy', array('filters' => $filters));
        return $form->getElement('dummy')->getFilters();
    }

    /**
     * Prepare a field value for indexing by applying filters to it.
     *
     * @param   string  $name               the name of the field to be indexed.
     * @param   array   $data               the details and value of the field.
     * @return  string  $value              the prepared value.
     * @throws  P4Cms_Content_Exception     if the value cannot be prepared.
     */
    protected function _prepareFieldValue($name, $data)
    {
        $filters = $this->_getIndexFilters($name, $data);

        // filters are required for non-text values.
        if (empty($filters) && strpos($data['mimeType'], 'text/') !== 0) {
            throw new P4Cms_Content_Exception(
                "Cannot prepare non-plain-text value without filters."
            );
        }

        // apply filters to value and return result.
        $value = $data['value'];
        foreach ($filters as $filter) {
            $value = $filter->filter($value);
        }

        return $value;
    }

    /**
     * Detect the encoding of a string.
     *
     * @param string  $data  the data to be checked.
     * @return string        the encoding or false if cannot be detected.
     */
    protected function _detectEncoding($data)
    {
        if (extension_loaded('mbstring')) {
            $encoding = mb_detect_encoding($data['value']);
        } else {
            $finfo = finfo_open(FILEINFO_MIME);

            // get mime type and encoding for the file
            $mime = finfo_file($finfo, $data['tempFile']);
            preg_match('/^(.*)\/(.*); charset=(.*)$/', $mime, $matches);

            $encoding = isset($matches[3]) ? trim($matches[3]) : false;
        }

        return $encoding;
    }

    /**
     * Merge two sets of fields.
     *
     * Options in the base fields will be replaced by the ones from
     * append fields and the default settings in the base will be
     * kept if none is set in the appending fields.
     *
     * This works like array_merge_recursive but instead of making
     * values with the same key an array, the value in the first array
     * is replaced.
     *
     * @param array  $a  the base fields.
     * @param array  $b  the append fields.
     * @return array     the merged fields.
     */
    protected function _mergeFields($a, $b)
    {
        if (!is_array($a)) {
            $a = empty($a) ? array() : array($a);
        }

        if (!is_array($b)) {
            $b = array($b);
        }

        foreach ($b as $key => $value) {
            if (!array_key_exists($key, $a) and !is_numeric($key)) {
                $a[$key] = $b[$key];
                continue;
            }

            if (is_array($value) or is_array($a[$key])) {
                $a[$key] = $this->_mergeFields($a[$key], $b[$key]);
            } else if (is_numeric($key)) {
                if (!in_array($value, $a)) {
                    $a[] = $value;
                }
            } else {
                $a[$key] = $value;
            }
        }

        return $a;
    }
}
