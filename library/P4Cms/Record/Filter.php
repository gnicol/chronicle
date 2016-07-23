<?php
/**
 * Constructs fstat filter expressions specifically for filtering
 * records via fetchAll().
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo    Figure out how to search for empty string as field value
 */
class P4Cms_Record_Filter extends P4_File_Filter
{
    // Define constant representing filter expression that will always be
    // evaluated as false.
    // Among several possible choices, we use single dash as an expression
    // for a filter that no record may pass. This will work simply because
    // dash is not a valid fstat field as record filters represent filter
    // expressions for the fstat command (to evaluate content of the fstat
    // fields).
    const     FALSE_EXPRESSION    = '-';

    // Store additional options that are not used in the construction of 
    // the expression
    protected $_options           = array();

    /**
     * The constructor accepts an optional string or array of filter conditions
     * to apply to the new filter object. String conditions are handled by 
     * P4_File_Filter.
     * 
     * Example simple form:
     * array(
     *    'fields'     => array('title' => 'News', 'body' => 'today'),
     *    'fstat'      => array('headAction' => 'edit', 'headType' => 'xtext'),
     *    'search'     => array('fields' => array('title', 'body'), 'query' => 'search keywords'),
     *    'categories' => array('foo', 'bar')
     *
     * Example complex form:
     * array(
     *    'fields' => array(
     *        array(
     *            'field'           => 'title',
     *            'value'           => 'News',
     *            'comparison'      => P4Cms_Record_Filter::COMPARE_EQUAL,
     *            'connective'      => P4Cms_Record_Filter::CONNECTIVE_OR,
     *            'caseInsensitive' => true),
     *        array(
     *            'field'           => 'body',
     *            'value'           => 'today',
     *            'comparison'      => P4Cms_Record_Filter::COMPARE_CONTAINS,
     *            'connective'      => P4Cms_Record_Filter::CONNECTIVE_OR)),
     *    'fstat' => array(
     *        array(
     *            'field'           => 'headAction',
     *            'value'           => 'edit',
     *            'comparison'      => P4Cms_Record_Filter::COMPARE_EQUAL,
     *            'connective'      => P4Cms_Record_Filter::CONNECTIVE_OR,
     *            'caseInsensitive' => false),
     *        array(
     *            'field'           => 'headType',
     *            'value'           => 'xtext',
     *            'comparison'      => P4Cms_Record_Filter::COMPARE_EQUAL,
     *            'connective'      => P4Cms_Record_Filter::CONNECTIVE_AND,
     *            'caseInsensitive' => false)),
     *    'search'     => array('fields' => array('title', 'body'), 'query' => 'search keywords'),
     *    'categories' => array('foo', 'bar')
     *       
     * @param  string|array  $filterOptions  string expression or array of filter options.
     */
    public function __construct($filterOptions = null)
    {
        // let parent handle string arguments.
        if (is_string($filterOptions)) {
            return parent::__construct($filterOptions);
        }

        // if argument is not an array with elements, get out
        if (!is_array($filterOptions) || empty($filterOptions)) {
            return;
        }

        // process different types of filter conditions:
        //  - record field conditions
        //  - fstat field conditions
        //  - search conditions
        //  - unknown conditions (stored as custom options).
        foreach ($filterOptions as $type => $filters) {
            if ($type == 'fields' || $type == 'fstat') {
                $method = $type == 'fields' ? 'add' : 'addFstat';
                foreach ($filters as $field => $filter) {
                    call_user_func_array(
                        array($this, $method),
                        $this->_normalizeFilterOptions($field, $filter)
                    );
                }
            } else if ($type == 'search') {
                if (isset($filters['fields']) && isset($filters['query'])) {
                    $this->addSearch($filters['fields'], $filters['query']);
                }
            } else {
                $this->setOption($type, $filters);
            }
        }
    }

    /**
     * Extend parents add to assume Record field (attribute) conditions are being added to the
     * filter. Will not work for 'file' fields.
     *
     * @param   string              $field      Record field to filter on
     * @param   null|string|array   $value      Value we are comparing to as string, null or array 
     *                                          of strings. If an array is given, condition will
     *                                          pass if any of the values satisfy the comparison.
     * @param   string              $comparison optional - comparison operator to use, defaults to Equal
     * @param   string              $connective optional - logical connective operator
     * @param   null|boolean        $caseInsensitive  optional - case-insensitive matching preference, default to null.
     * @return  P4Cms_Record_Filter provides fluent interface.
     */
    public function add(
        $field,
        $value,
        $comparison = self::COMPARE_EQUAL,
        $connective = self::CONNECTIVE_AND,
        $caseInsensitive = null
    )
    {
        if (!is_string($field) || empty($field)) {
            throw new InvalidArgumentException(
                "Cannot add condition. Field must be a non-empty string."
            );
        }
        return $this->addFstat('attr-' . $field, $value, $comparison, $connective, $caseInsensitive);
    }

    /**
     * Add a fstat field condition to the filter. Equivalent to parent classes 'add' function.
     *
     * @param   string              $field      Fstat field to filter on
     * @param   null|string|array   $value      Value we are comparing to as string, null or array
     *                                          of strings. If an array is given, condition will
     *                                          pass if any of the values satisfy the comparison.
     * @param   string              $comparison optional - comparison operator to use, defaults to Equal
     * @param   string              $connective optional - logical connective operator
     * @param   null|boolean        $caseInsensitive  optional - case-insensitive matching preference, default to null.
     * @return  P4Cms_Record_Filter provides fluent interface.
     */
    public function addFstat(
        $field,
        $value,
        $comparison = self::COMPARE_EQUAL,
        $connective = self::CONNECTIVE_AND,
        $caseInsensitive = null
    )
    {
        return parent::_add($field, $value, $comparison, $connective, $caseInsensitive);
    }

    /**
     * Add filters to search for keywords across the given fields.
     *
     * Splits the given query string on whitespace and comma, then
     * adds case-insensitive filters that will match any records
     * that contain all of the keywords across the given fields
     *
     * @param   array|string            $fields     the fields to match on
     * @param   string                  $query      the user-supplied search string
     * @return  P4Cms_Record_Filter     provides fluent interface.
     */
    public function addSearch($fields, $query)
    {
        // normalize fields to array.
        $fields = (array) $fields;
        
        // split query into words.
        $query = preg_split('/[\s,]+/', trim($query));

        foreach ($query as $keyword) {
            $subFilter = new static();
            foreach ($fields as $field) {
                $subFilter->add(
                    $field,
                    $keyword,
                    static::COMPARE_CONTAINS,
                    static::CONNECTIVE_OR,
                    true
                );
            }
            $this->addSubFilter($subFilter, static::CONNECTIVE_AND);
        }

        return $this;
    }

    /**
     * Store custom filter options that don't correspond to built-in options.
     *
     * @param   string                 $name    the name of the option
     * @param   mixed                  $value   the value(s) of the option
     * @return  P4Cms_Record_Filter    provides fluent interface.
     */
    public function setOption($name, $value)
    {
        $this->_options[$name] = $value;

        return $this;
    }

    /**
     * Retrieve a custom option by name.
     *
     * @param   string  $name    the name of the option
     * @return  mixed   $value   the value(s) of the option or null if no such option.
     */
    public function getOption($name)
    {
        return isset($this->_options[$name]) ? $this->_options[$name] : null;
    }
    
    /**
    * Called from the constructor to normalize an array of options for field or fstat filters.
    * 
    * Two forms of input: simple and complex. 
    * 
    * In the simple case, the field (taken from the array key) is a string and the filter 
    * is the value we are filtering for (taken from the array value).
    *
    * In the complex case, the field is numeric and the filter is an array of filter options
    * including the field name and the value we are searching for.
    *
    * @param  string|null        $field   optional field name - if null, field is in filter array.
    * @param  string|array|null  $filter  filter options or filter value if field is given.
    * @return array              an array of filter options that has all of the required keys
    */
    protected function _normalizeFilterOptions($field, $filter) 
    {
        // determine if filter options are simple or complex
        // if field is an integer, we are assuming the complex usage
        // in which case filter must be an array of filter options.
        if (is_integer($field) && !is_array($filter)) {
            throw new InvalidArgumentException('Field filter options must be an array.');
        }

        // normalize complex and simple cases.
        //  - complex case is a numeric field and all filter options in an array under filter
        //  - simple case is a string field and value provided under filter
        if (is_integer($field)) {
            // complex case
            return array(
                'field'           => isset($filter['field'])           ? $filter['field']      : null,
                'value'           => isset($filter['value'])           ? $filter['value']      : null,
                'comparison'      => isset($filter['comparison'])      ? $filter['comparison'] : static::COMPARE_EQUAL,
                'connective'      => isset($filter['connective'])      ? $filter['connective'] : static::CONNECTIVE_AND,
                'caseInsensitive' => isset($filter['caseInsensitive']) ? $filter['caseInsensitive'] : null
            );
        } else {
            // simple case
            return array(
                'field'           => $field,
                'value'           => $filter,
                'comparison'      => static::COMPARE_EQUAL,
                'connective'      => static::CONNECTIVE_AND,
                'caseInsensitive' => null
            );
        }
    }
}
