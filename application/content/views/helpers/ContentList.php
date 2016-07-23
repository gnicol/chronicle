<?php
/**
 * View helper that displays a content list.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_View_Helper_ContentList extends Zend_View_Helper_Abstract
{
    protected   $_query          = null;
    protected   $_element        = null;
    protected   $_options        = array();
    protected   $_defaultOptions = array(
        'template'      => null,
        'dojoType'      => null,
        'emptyMessage'  => 'No content entries found.',
        'postSort'      => null,
        'rowOptions'    => array(),
        'entryOptions'  => array(),
        'fields'        => array(
            'title'     => array(
                'filters'    => array(),
                'decorators' => array('Value', 'ContentLink')
            )
        ),
        'width'         => null,
        'height'        => null
    );

    /**
     * A magic method which calls through to render; see render method for details.
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
     * Gets a rendered (decorated and filtered) field value.
     *
     * @param   string          $fieldIndex     The index to the options entry for the field to display.
     * @param   P4Cms_Content   $entry          The entry containing the field to display.
     * @param   array           $options        Options for this specific field/row/entry.
     * @return  string          Returns the display value of the specified field.
     */
    public function getDisplayValue($fieldIndex, $entry, $options)
    {
        $fieldName = $options['field'];

        // if the requested field doesn't exist, silently return an empty value.
        $type = $entry->getContentType();
        if (!$type->hasElement($fieldName)) {
            return '';
        }

        // filter the value.
        $value = $entry->getExpandedValue($fieldName);
        foreach ($this->getFilters($fieldIndex, $entry, $options) as $filter) {
            $value = $filter->filter($value);
        }

        // set the field value on the element for decorators to access
        // note, some elements (e.g. file/image) will ignore attempts to
        // set a value; therefore, decorators will not be able to retrieve
        // the field value from such elements directly - that is why we
        // also set the content entry on enhanced elements.
        $element = clone $type->getFormElement($fieldName);
        $element->setValue($value);
        if ($element instanceof P4Cms_Content_EnhancedElementInterface) {
            $element->setContentRecord($entry);
        }

        // render display value using decorators.
        $content = '';
        foreach ($this->getDecorators($fieldIndex, $entry, $options) as $decorator) {
            $decorator->setElement($element);
            if ($decorator instanceof P4Cms_Content_EnhancedDecoratorInterface) {
                $decorator->setContentRecord($entry);
            }
            $content = $decorator->render($content);
        }

        return $content;
    }

    /**
     * Get the decorator instances for a given field.
     *
     * @param   string          $fieldName  The name of the field for which to obtain decorators.
     * @param   P4Cms_Content   $entry      The entry containing the field to decorate.
     * @param   array           $options    Options for this specific field/row/entry.
     * @return  array           The list of decorators for the field.
     */
    public function getDecorators($fieldName, P4Cms_Content $entry, array $options)
    {
        // will be empty if none were provided
        if (empty($options['decorators'])) {
            return $entry->getContentType()->getDisplayDecorators($fieldName);
        }

        try {
            $element = $this->_getElement();
            $element->setDecorators($options['decorators']);
            return $element->getDecorators();
        } catch (Exception $e) {
            P4Cms_Log::log(
                "Failed to get user-specified decorators for field '"
                . $fieldName . "' when displaying content with id " . $entry->getId()
                . " as part of a list.",
                P4Cms_Log::ERR
            );
        }

        return array();
    }

    /**
     * Get the filter instances for a given field.
     *
     * @param   string          $fieldName  The name of the field for which to obtain filters.
     * @param   P4Cms_Content   $entry      The entry containing the field to filter.
     * @param   array           $options    Options for this specific field/row/entry.
     * @return  array           The list of filters for the field.
     */
    public function getFilters($fieldName, P4Cms_Content $entry, array $options)
    {
        // will be empty if no filters were provided
        if (empty($options['filters'])) {
            return $entry->getContentType()->getDisplayFilters($fieldName);
        }

        try {
            $element = $this->_getElement();
            $element->setFilters($options['filters']);
            return $element->getFilters();
        } catch (Exception $e) {
            P4Cms_Log::log(
                "Failed to get user-specified filters for field '"
                . $fieldName . "' when displaying content with id " . $entry->getId()
                . " as part of a list.",
                P4Cms_Log::ERR
            );
        }

        return array();
    }

    /**
     * Default method called for this view helper.
     *
     * For more information on query construction @see P4Cms_Record_Query
     *
     * The options array can declare a template to use to render content through, an optional
     * message to display if no entries are returned by the query, and a list of fields to render.
     *
     * The fields array can be as simple as:
     *
     *  array('field1', 'field2', ...)
     *
     * Or, more complex with display filters and decorators:
     *
     *  array(
     *      'field1' => array(
     *          'decorators' => array(
     *              'decorator1' => array('option1' => 'value', 'option2'),
     *              ...
     *          ),
     *          'filters'    => array('filter1'...)
     *      ),
     *      'optionIndex' => array(
     *          'field'      => 'field1',
     *          'decorators' => array(
     *              'decorator1' => array('option1' => 'value', 'option2'),
     *              ...
     *          ),
     *          'filters'    => array('filter1'...)
     *      ),
     *      'field2' => array(...)
     *  )
     *
     * Filters can contain options such as 'lucene' or 'categories' to have those modules subscribe
     * to and influence the query.  Note that these options do not work on subfilters.
     *
     * @param   P4Cms_Record_Query|array|null   $query      The query or array to determines the list of content.
     * @param   array                           $options    Optional array of options.
     * @return  Content_View_Helper_ContentList             A helper instance with the correct query/options.
     */
    public function contentList($query, array $options = array())
    {
        // P4Cms_Record::fetchAll normalizes the query for us on use
        $this->_query   = $query;

        $this->_options = $this->_normalizeOptions($options);

        return $this;
    }

    /**
     * Get list of fields configured for display including any
     * options for those fields such as filters and decorators.
     *
     * Options may differ according to the row or content entry we
     * are rendering. Row and entry specific options may be provided
     * via rowOptions and entryOptions respectively. Row options
     * should be keyed on the row number (one-based); whereas entry
     * options should be keyed by entry id.
     *
     * Note: row and entry options will be merged recursively with
     * the standard field options. Entry options will be merged last
     * and therefore take precedence.
     *
     * @param   int             $row    the row number to get field options for.
     * @param   P4Cms_Content   $entry  the entry to get field options for.
     * @return  array           A list of fields and field options.
     */
    public function getFields($row, P4Cms_Content $entry)
    {
        $options      = $this->_options['fields'];
        $rowOptions   = isset($this->_options['rowOptions'][$row]['fields'])
            ? $this->_options['rowOptions'][$row]['fields']
            : array();
        $entryOptions = isset($this->_options['entryOptions'][$entry->getId()]['fields'])
            ? $this->_options['entryOptions'][$entry->getId()]['fields']
            : array();

        // merge options with row-options, then with entry-options.
        return P4Cms_ArrayUtility::mergeRecursive(
            P4CMs_ArrayUtility::mergeRecursive($options, $rowOptions),
            $entryOptions
        );
    }

    /**
     * Accessor for the optional template path.
     *
     * @return string The path to the template.
     */
    public function getTemplate()
    {
        return $this->_options['template'];
    }

    /**
     * Render method for this view helper.
     * If a template has been supplied, renders the content list using the template, otherwise
     * renders the content list as an unordered html list.
     *
     * @return string The rendered list of content.
     */
    public function render()
    {
        $view    = clone $this->view;
        $entries = P4Cms_Content::fetchAll($this->_query);

        // allow caller to sort entries client-side
        // sorting capabilities of server are limited.
        if ($this->_options['postSort']) {
            $entries->sortBy($this->_options['postSort']);
        }

        // tag the page cache so it can be appropriately cleared later
        if (P4Cms_Cache::canCache('page')) {
            P4Cms_Cache::getCache('page')->addTag('p4cms_content')
                                         ->addTag('p4cms_content_type')
                                         ->addTag('p4cms_content_list');
        }

        // if there is a template: clone view, add items, render template
        if ($this->getTemplate()) {
            $view->entries = $entries;
            $view->options = $this->_options;

            return $view->render($this->getTemplate());
        }

        if (!count($entries)) {
            return $this->_options['emptyMessage'];
        }

        $count = 0;
        $html  = $this->openList();
        foreach ($entries as $entry) {
            $count++;
            $html .= '<li class="content-list-entry-' . $count
                  . ' content-list-entry-' . ($count % 2 ? 'odd' : 'even')
                  . ' content-list-type-' . $view->escapeAttr($entry->getContentTypeId()) . '">';
            foreach ($this->getFields($count, $entry) as $field => $options) {
                $html .= $this->getDisplayValue($field, $entry, $options);
            }
            $html .= '</li>' . PHP_EOL;
        }

        $html .= $this->closeList();
        return $html;
    }

    /**
     * Returns the start of the html list.
     *
     * @return string   The opening html for the list of content entries.
     */
    public function openList()
    {
        $dojoType = $this->_options['dojoType']
            ? ' dojoType="' . $this->view->escapeAttr($this->_options['dojoType']) . '"'
            : '';

        // handle the width and height options, if specified
        $width = $this->_options['width'];
        $width = $width
            ? 'width: ' . $this->view->escapeAttr($width)
                . ((string) intval($width) === $width ? 'px' : '') . ';'
            : '';
        $height = $this->_options['height'];
        $height = $height
            ? 'height: ' . $this->view->escapeAttr($height)
                . ((string) intval($height) === $height ? 'px' : '') . ';'
            : '';
        $separator = ($width && $height) ? ' ' : '';
        $style = ($width || $height) ? ' style="' . $width . $separator . $height .'"' : '';

        return '<ul class="content-list"' . $dojoType . $style . '>' . PHP_EOL;
    }

    /**
     * Returns the end of the html list.
     *
     * @return string   The closing html for the list of content entries.
     */
    public function closeList()
    {
        return '</ul>' . PHP_EOL;
    }

    /**
     * Returns a form element for the purpose of loading filters/decorators.
     *
     * return   Zend_Form_Element   a form element for loading filters/decorators.
     */
    protected function _getElement()
    {
        if ($this->_element instanceof Zend_Form_Element) {
            return $this->_element;
        }

        $form = new P4Cms_Form;
        $form->addElement('text', 'loader');

        $this->_element = $form->getElement('loader');

        return $this->_element;
    }

    /**
     * Normalizes options to ensure that it is in a consistent format.
     *
     *  - Ensures options contains defaults.
     *  - Fields is normalized to an array of field-name => field-options
     *  - Field options is normalized to an array declaring optional filters
     *    and decorators to augment presentation of field data.
     *
     * @param   array   $options    The un-normalized fields array.
     * @return  array   The normalized options array.
     */
    protected function _normalizeOptions(array $options)
    {
        $normalized = $options + $this->_defaultOptions;

        // ensure fields is an array.
        if (isset($options['fields']) && is_array($options['fields'])) {
            $normalized['fields'] = array();
        } else {
            $options['fields'] = $this->_defaultOptions['fields'];
        }

        // normalize fields to name/options w. filters and decorators.
        $defaults = array('filters' => array(), 'decorators' => array());
        foreach ($options['fields'] as $name => $value) {
            if (is_numeric($name) && is_string($value)) {
                $name  = $value;
                $value = $defaults;
            }

            // skip invalid field declarations.
            if (!is_string($name) || !is_array($value)) {
                continue;
            }

            // ensure field options has filter/decorator entries.
            $value += $defaults;

            // set field name value
            if (!array_key_exists('field', $value)) {
                $value['field'] = $name;
            }

            // ensure that the filters and decorators options are both arrays
            foreach (array('filters', 'decorators') as $option) {
                if (!is_array($value[$option])) {
                    $value[$option] = array();
                }
            }

            $normalized['fields'][$name] = $value;
        }

        return $normalized;
    }
}