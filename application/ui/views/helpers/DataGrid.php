<?php
/**
 * Dojo DataGrid widget view helper.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Ui_View_Helper_DataGrid extends Zend_Dojo_View_Helper_Dijit
{
    // dojo modules required to render the widgets
    protected $_gridModule          = 'p4cms.ui.grid.DataGrid';
    protected $_gridFooterModule    = 'p4cms.ui.grid.Footer';
    protected $_storeModule         = 'dojox.data.QueryReadStore';

    // class templates for html elements wrapping given blocks
    // (options form, data grid, grid footer etc.); @ will be
    // replaced by the value returned by _getGridLabel()
    protected $_formClassTemplate    = 'grid-options @-grid-options';
    protected $_wrapperClassTemplate = 'data-grid @-grid';

    // default data grid attributes
    protected $_defaultAttribs  = array(
        'rowsPerPage'       => 25,
        'dynamicHeight'     => true,
        'selectionMode'     => 'none',
        'disableFocus'      => true,
        'disableSort'       => array('_item')
    );

    protected $_namespace   = null;
    protected $_options     = array();
    private   $_cache       = array();

    /**
     * Return a string representing markup for p4cms.ui.DataGrid widget.
     * Also appends following elements (depending on options, see below):
     * - grid options form
     * - data store used by grid
     * - grid footer
     * - grid context row menu
     * 
     * Publishes a 'render' topic just prior to generating data grid markup.
     * This allows interested third-parties to make adjustments (e.g. change
     * options to add columns) at render time.
     *
     * @param   string|null $namespace  namespace for data grid related objects.
     * @param   array|null  $options    helper options, following keys are recognized:
     *                                  form
     *                                      grid options form
     *                                  url (required)
     *                                      data store url to fetch data from
     *                                  storeId
     *                                      id of data store, if not set, helper will set one
     *                                  columns (required)
     *                                      grid columns definition, values must be either
     *                                      strings or arrays with following meaning:
     *                                      string - value will be used as storage field to get
     *                                          value for the column from,
     *                                          label will be capitalized value, no formatter
     *                                      array - key will be used as storage field (unless
     *                                          field is not set in array values), value defines
     *                                          attributes (label, formatter, width etc.)
     *                                  plugins
     *                                      array with grid plugins, name of the plugin is in key
     *                                      and plugin options in the value that must be array with
     *                                      following keys:
     *                                          module  - optional, required dojo module
     *                                          options - plugin options, required if module is
     *                                                    specified
     *                                          alternativelly, if no module is required, 'options'
     *                                          key may be omitted
     *                                  actions
     *                                      P4Cms_Navigation object, if set it will be used
     *                                      for attaching context row menu
     *                                  gridLabel
     *                                      used for expanding class templates, if not set
     *                                      helper will generate one, see _getGridLabel() method
     *                                  footer
     *                                      options for grid footer, folowing two keys are
     *                                      recognized:
     *                                      buttons - array with buttons options: (keys = button labels)
     *                                          attribs - button attributes
     *                                          order   - optional, button order in the grid footer
     *                                          buttons will be rendered as dijit.form.Button
     *                                          widgets and placed in the grid footer
     *                                      attribs - attributes merged to the footer widget, 
     *                                          gridId is always set by the helper
     *                                  pageSize
     *                                      if set, influences rowsPerPage, keepRows and rowCount
     *                                      attribs if they are not present
     *                                  attribs
     *                                      data grid attributes, they are merged with 
     *                                      defaultAttribsprovided by this class before rendering
     * @return  string                  markup for data grid and attached widgets.
     */
    public function render($namespace = null, array $options = null)
    {
        // get namespace from the parameter (if provided) or from the storage
        $namespace = $namespace ?: $this->getNamespace();

        // set the namespace to ensure it is valid
        $this->setNamespace($namespace);

        if ($options !== null) {
            $this->setOptions($options);
        }
        
        // allow third-parties to participate (e.g. change options) before we render.
        try {
            P4Cms_PubSub::publish(
                $this->getNamespace() . '.render', 
                $this
            );
        } catch (Exception $e) {
            P4Cms_Log::logException("Error rendering data grid.", $e);
        }        

        // get rendered elements layouts
        $htmlForm                   = $this->_getFormLayout();
        $htmlStore                  = $this->_getDataStoreLayout();
        $htmlGrid                   = $this->_getDataGridLayout();
        $htmlActionsMenu            = $this->_getActionsMenu();
        $htmlGridFooter             = $this->_getGridFooterLayout();

        // set attributes for grid wrapper container
        $wrapperAttribs = array(
            'class' => $this->_getExpandedClassTemplate('wrapper')
        );

        return $htmlForm
            . '<div' . $this->_htmlAttribs($wrapperAttribs) . '>' . self::EOL
            . $htmlStore
            . $htmlGrid
            . $htmlActionsMenu
            . $htmlGridFooter
            . '</div>' . self::EOL;
    }

    /**
     * If called without arguments, return instance of this class, otherwise
     * render DataGrid widget.
     * 
     * @param   string|null $namespace  namespace for data grid related objects.
     * @param   array|null  $options    helper options.
     * @return  Ui_View_Helper_DataGrid|string  instance of this class if called with no paramns,
     *                                          otherwise markup for data grid and attached widgets.
     */
    public function dataGrid($namespace = null, array $options = null)
    {
        // return instance of this class if no parameters provided
        if ($namespace === null && $options === null) {
            return $this;
        }

        return $this->render($namespace, $options);
    }

    /**
     * Return Zend_Dojo_Data container holding data for data grid.
     * 
     * This method takes a paginator object. The ultimate goal is to get each item in
     * the paginator into array form. This can be accomplished by passing a paginator
     * of arrays, objects with a 'toArray()' method, or a item callback that converts
     * each item into an array (and may optionally manipulate/filter item data).
     * 
     * When output data array is being assembled, 'data.item' topic is published
     * allowing each data item to be filtered by interested third-parties. When
     * output data assembling is done, 'data' topic is published to allow modifications
     * of whole output data array (passed to subscribers as Zend_Dojo_Data object).
     * 
     * @param   Zend_Paginator  $paginator      paginator object containing data to output.
     * @param   callback        $itemCallback   optional - callback to invoke for each item in the 
     *                                          paginator, should return an array of item data.
     * @param   string          $identifier     optional - specifies output data identifier ('id'
     *                                                     by default).
     * @throws  InvalidArgumentException        if callback is not callable
     * @throws  Zend_Dojo_View_Exception        if unable to convert paginator item to an array
     * @return  Zend_Dojo_Data                  dojo data container suitable for printing
     */
    public function dojoData(Zend_Paginator $paginator, $itemCallback = null, $identifier = 'id')
    {
        // ensure itemCallback is callable function
        if ($itemCallback && !is_callable($itemCallback)) {
            throw new InvalidArgumentException("Data item callback must be callable function.");
        }

        // assemble output data
        $data = array();
        foreach ($paginator as $model) {
            // get data item from provided callback function or attempt to convert to array.
            if ($itemCallback) {
                $item = call_user_func($itemCallback, $model, $this);
            } else if (is_object($model) && method_exists($model, 'toArray')) {
                $item = $model->toArray();
            } else {
                $item = $model;
            }
            
            // verify that item is in array form.
            if (!is_array($item)) {
                throw new Zend_Dojo_View_Exception("Unable to convert paginator item to array.");
            }
            
            // allow data item modification by interested parties
            try {
                $item = P4Cms_PubSub::filter(
                    $this->getNamespace() . '.data.item',
                    $item,
                    $model,
                    $this
                );
            } catch (Exception $e) {
                P4Cms_Log::logException("Error building data grid item data.", $e);
            }
            
            $data[] = $item;
        }

        // put items in dojo data container to be printed
        $data = new Zend_Dojo_Data((string) $identifier, $data);
        $data->setMetadata('numRows', $paginator->getTotalItemCount());

        // allow data container modification by interested parties
        try {
            P4Cms_PubSub::publish(
                $this->getNamespace() . '.data',
                $data,
                $this
            );
        } catch (Exception $e) {
            P4Cms_Log::logException("Error building data grid data.", $e);
        }
        
        return $data;        
    }
    
    /**
     * Set namespace for data grid related objects.
     *
     * @param   string $namespace           namespace to use.
     * @return  Ui_View_Helper_DataGrid     provides fluent interface.
     */
    public function setNamespace($namespace)
    {
        if (!is_string($namespace) || !strlen($namespace)) {
            throw new InvalidArgumentException("Namespace must be a non-empty string.");
        }

        $this->_namespace = $namespace;
        return $this;
    }

    /**
     * Return the namespace value.
     *
     * @return string|null  namespace to use.
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }

    /**
     * Set helper options.
     *
     * @param   array $options              helper options.
     * @return  Ui_View_Helper_DataGrid     provides fluent interface.
     */
    public function setOptions(array $options)
    {
        $this->_options = array();
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }
        return $this;
    }

    /**
     * Set helper single option.
     *
     * @param   string  $key                option key to set.
     * @param   mixed   $value              option value to set.
     * @return  Ui_View_Helper_DataGrid     provides fluent interface.
     */
    public function setOption($key, $value)
    {
        $key = (string) $key;
        $this->_options[$key] = $value;

        // clear normalizedColumns cache if column attribute was altered
        if ($key == 'columns') {
            unset($this->_cache['normalizedColumns']);
        }

        return $this;
    }

    /**
     * Return helper options.
     *
     * @return array    helper options.
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Retrieve a single helper option.
     *
     * @param   string  $key    key to get helper option for.
     * @return  mixed           single helper option for given key or null if option not found.
     */
    public function getOption($key)
    {
        $key = (string) $key;
        if (!isset($this->_options[$key])) {
            return null;
        }

        return $this->_options[$key];
    }

    /**
     * Retrieve given helper attribute.
     *
     * @param   string  $key    key to get helper attribute for.
     * @return  mixed           single helper attribute for given key
     *                          or null if attribute is not set.
     */
    public function getAttrib($key)
    {
        $key     = (string) $key;
        $attribs = $this->getOption('attribs');
        if (!$attribs || !isset($attribs[$key])) {
            return null;
        }

        return $attribs[$key];
    }

    /**
     * Set helper attribute.
     *
     * @param   string  $key                attribute key to set.
     * @param   mixed   $value              attribute value to set.
     * @return  Ui_View_Helper_DataGrid     provides fluent interface.
     */
    public function setAttrib($key, $value)
    {
        $attribs = $this->getOption('attribs');
        if (!is_array($attribs)) {
            $attribs = array();
        }

        $key           = (string) $key;
        $attribs[$key] = $value;
        $this->setOption('attribs', $attribs);

        return $this;
    }

    /**
     * Return pre-defined label and attributes for data grid actions column.
     *
     * @return array    attributes for default actions field.
     */
    public function getDefaultActionsColumn()
    {
        $this->dojo->requireModule("p4cms.ui.grid.formatters.ActionsButton");
        return array(
            'label'      => 'Actions',
            'fixedWidth' => '9%',
            'field'      => '_item',
            'formatter'  => 'p4cms.ui.grid.formatters.ActionsButton',
            'classes'    => 'actions',
            'order'      => 999
        );
    }

    /**
     * Normalizes the passed columns array and merges in any passed
     * default properties.
     *
     * Columns present in defaults but not present in the passed
     * columns array will be excluded from the result.
     *
     * @param   array   $columns    a list which columns to include and, optionally, custom options
     * @param   array   $defaults   the list of default columns and thier options
     * @return  array   result of merging default options with custom column properties
     */
    public function mergeColumnDefaults($columns, $defaults)
    {
        $columns = $this->normalizeColumns($columns);

        foreach ($columns as $name => &$properties) {
            if (isset($defaults[$name])) {
                $properties += $defaults[$name];
            }
        }

        return $columns;
    }

    /**
     * Normalize the passed columns array. The resulting array will
     * have the column name as keys and an array (possibly empty) of
     * properties for values.
     *
     * @param   array   $columns    the array to normalize
     * @return  arrray  the normalize array
     */
    public function normalizeColumns($columns)
    {
        $normalized = array();
        foreach ((array) $columns as $key => $values) {
            if (is_int($key) && is_string($values)) {
                $normalized[$values] = array();
                continue;
            }

            $normalized[$key] = is_array($values) ? $values : array();
        }

        return $normalized;
    }

    /**
     * Add column with given attributes to the columns option. If $isSortable
     * parameter is false, also adds column to the list of columns with disabled
     * sorting.
     *
     * @param string    $field              dojo data field that added column is
     *                                      associated with
     * @param array     $attributes         added column attributes
     * @param boolean   $isSortable         if false, then column will be added to
     *                                      the disableSort list
     */
    public function addColumn($field, array $attributes = array(), $isSortable = true)
    {
        $field   = (string) $field;
        $columns = (array) $this->getOption('columns');
        if (isset($columns[$field])) {
            throw new Zend_Dojo_View_Exception(
                "Cannot add column: field $field already exists."
            );
        }

        // add column to the columns option
        $columns[$field] = $attributes;
        $this->setOption('columns', $columns);

        // if not sortable, add column to the list of columns with disabled sorting
        if (!$isSortable) {
            $attribs     = (array) $this->getOption('attribs');
            $default     = isset($this->_defaultAttribs['disableSort']) 
                ? $this->_defaultAttribs['disableSort']
                : array();
            $disableSort = isset($attribs['disableSort'])
                ? $attribs['disableSort']
                : $default;

            if (is_array($disableSort) && !in_array($field, $disableSort)) {
                $disableSort[] = $field;
            }

            $attribs['disableSort'] = $disableSort;
            $this->setOption('attribs', $attribs);
        }
    }

    /**
     * Add button into the grid footer. Existing button with the same label
     * will be overriden.
     * 
     * @param   string  $label      button label
     * @param   array   $options    optional, button options
     */
    public function addButton($label, array $options = array())
    {
        // get footer buttons from options
        $footer  = $this->getOption('footer') ?: array();
        $buttons = isset($footer['buttons']) ? $footer['buttons'] : array();

        // add button with given label and options
        $buttons[$label] = $options;

        // update footer        
        $footer['buttons'] = $buttons;
        $this->setOption('footer', $footer);
    }

    /**
     * Get the actions menu for this data grid's 'actions' pub/sub topic.
     *
     * @return  P4Cms_Navigation    a navigation container populated with items via pub/sub.
     */
    public function getPublishedActions()
    {
        $actions = new P4Cms_Navigation;
        P4Cms_PubSub::publish($this->getNamespace() . '.actions', $actions);
        
        return $actions;
    }
    
    /**
     * Return class template with @ replaced by grid label.
     * 
     * @param string $name  name of the template.
     * @return string       expanded template or empty string if template not found.
     */
    protected function _getExpandedClassTemplate($name)
    {
        $templateName = '_' . strtolower($name) . 'ClassTemplate';
        if (!property_exists($this, $templateName)) {
            return '';
        }

        return str_replace('@', $this->_getGridLabel(), $this->$templateName);
    }

    /**
     * Return grid label from cahce. If not set, get it from paroptionsams
     * (defaults to module name if not specified in options).
     *
     * @return string   grid label.
     */
    protected function _getGridLabel()
    {
        if (!isset($this->_cache['gridLabel'])) {
            $this->_cache['gridLabel'] = isset($this->_options['gridLabel'])
                ? $this->_options['gridLabel']
                : $this->_getNormController();
        }

        return $this->_cache['gridLabel'];
    }

    /**
     * Return markup for actions dijit menu.
     *
     * @return string   actions menu markup.
     */
    protected function _getActionsMenu()
    {
        $menuId = $this->_getActionsMenuId();
        if (!$menuId) {
            return '';
        }

        $dijitMenu = $this->view->navigation()->findHelper('dijitMenu');
        return $dijitMenu->renderMenu(
            $this->_options['actions'],
            array(
                'attribs'    => array(
                    'id'     => $menuId,
                    'style'  => 'display: none;'
               )
           )
        );
    }

    /**
     * Return actions menu id from cache. If not set yet, sets menu id and returns it.
     * If actions are not defined, sets menu id to null.
     *
     * @return string|null  actions menu id.
     */
    protected function _getActionsMenuId()
    {
        if (!isset($this->_cache['actionsMenuId'])) {
            if (isset($this->_options['actions']) && count($this->_options['actions'])) {
                $this->_cache['actionsMenuId'] = $this->_getGridLabel() . '-grid';
            } else {
                $this->_cache['actionsMenuId'] = null;
            }
        }

        return $this->_cache['actionsMenuId'];
    }

    /**
     * Return id of data store attached to the grid.
     * Returns storeId if set in options, otherwise grid id + '.store'.
     *
     * @return string   data store id.
     */
    protected function _getDataStoreId()
    {
        if (!isset($this->_cache['storeId'])) {
            $this->_cache['storeId'] = isset($this->_options['storeId'])
                ? $this->_options['storeId']
                : $this->_namespace . '.store';
        }

        return $this->_cache['storeId'];
    }

    /**
     * Return id of data grid instance.
     *
     * @return string   data grid instance id.
     */
    protected function _getDataGridId()
    {
        return $this->_namespace . '.instance';
    }

    /**
     * Return module name from the request.
     *
     * @return string   name of current module.
     */
    protected function _getModule()
    {
        if (!isset($this->_cache['module'])) {
            $this->_cache['module'] = 
                Zend_Controller_Front::getInstance()->getRequest()->getModuleName();
        }

        return $this->_cache['module'];
    }

    /**
     * Return normalized controller name, which is name of the controller if
     * its not 'index', otherwise name of the module.
     *
     * @return string   controller name (if not index) or module name.
     */
    protected function _getNormController()
    {
        if (!isset($this->_cache['normController'])) {
            $controller = Zend_Controller_Front::getInstance()->getRequest()->getControllerName();
            $this->_cache['normController'] = $controller == 'index' 
                ? $this->_getModule()
                : $controller;
        }

        return $this->_cache['normController'];
    }

    /**
     * Return markup for data grid options form.
     * 
     * @return string   grid options markup.
     */
    protected function _getFormLayout()
    {
        if (!isset($this->_options['form'])) {
            return '';
        }

        $attribs = array(
            'class' => $this->_getExpandedClassTemplate('form')
        );

        return '<div' . $this->_htmlAttribs($attribs) . '>' . self::EOL
            . $this->_options['form']
            . '</div>' . self::EOL;
    }

    /**
     * Return markup for data grid store.
     *
     * @return string   grid store markup.
     */
    protected function _getDataStoreLayout()
    {
        if (!isset($this->_options['url'])) {
            throw new Zend_Dojo_View_Exception("You must set an url.");
        }

        // prepare store widget
        $this->_module = $this->_storeModule;
        $attribs       = $this->_prepareDijit(
            array(
                'url'   => $this->_options['url'],
                'jsId'  => $this->_getDataStoreId()
            ),
            array(),
            'layout',
            $this->_storeModule
        );

        return '<div' . $this->_htmlAttribs($attribs) . '></div>' . self::EOL;
    }

    /**
     * Return markup for data grid footer.
     * 
     * @return string   data grid footer markup.
     */
    protected function _getGridFooterLayout()
    {
        // merge footer attribs
        $attribs = isset($this->_options['footer']['attribs'])
            ? $this->_options['footer']['attribs']
            : array();
        $attribs['gridId'] = $this->_getDataGridId();

        // prepare footer widget
        $this->_module = $this->_gridFooterModule;
        $attribs = $this->_prepareDijit(
            $attribs,
            array(),
            'layout',
            $this->_gridFooterModule
        );

        return '<div' . $this->_htmlAttribs($attribs) . '>' . self::EOL
            . $this->_getGridFooterButtonsLayout()
            . '</div>' . self::EOL;
    }

    /**
     * Return markup for grid footer buttons specified in 'footerButtons' option.
     *
     * @return string   markup for grid footer buttons.
     */
    protected function _getGridFooterButtonsLayout()
    {
        if (!isset($this->_options['footer']['buttons'])
            || !is_array($this->_options['footer']['buttons'])
        ) {
            return '';
        }
        $buttons = $this->_options['footer']['buttons'];

        // if button order was not provided by user, set it to match button's offset
        // in the buttons array, so the buttons will be rendered in the order as were
        // added
        $buttonOffset = 0;
        foreach ($buttons as &$button) {
            if (!isset($button['order'])) {
                $button['order'] = ++$buttonOffset;
            }
        }

        // sort buttons according to their order
        uasort(
            $buttons,
            function($a, $b)
            {
                $orderA = (int) $a['order'];
                $orderB = (int) $b['order'];

                if ($orderA === $orderB) {
                    return 0;
                }
                return $orderA < $orderB ? -1 : 1;
            }
        );

        $html = '<div class="button">' . self::EOL;
        foreach ($buttons as $label => $data) {
            $params  = isset($data['params']) ? $data['params'] : array();
            $attribs = isset($data['attribs']) ? $data['attribs'] : array();
            $html   .= $this->view->button(null, $label, $params, $attribs);
        }
        $html .= '</div>' . self::EOL;

        return $html;
    }

    /**
     * Return array with normalized columns attribute having following properties:
     * - 'field' key is present in column attributes array
     * - 'label' key is present in column attributes array (if label was not not set, set value
     *   to capitalized field value)
     * - column items are sorted with respect to the 'order' attribute
     * - 'order' key is not present in column attributes array
     *
     * @return array    array with normalized columns attributes
     */
    protected function _getNormalizedColumns()
    {
        if (!isset($this->_cache['normalizedColumns'])) {
            $columns           = (array) $this->getOption('columns');
            $normalizedColumns = array();
            $index             = 0;
            foreach ($columns as $field => $data) {
                // determine column order
                if (is_array($data) && isset($data['order'])) {
                    $order = (int) $data['order'];
                } else {
                    while (array_key_exists($index, $normalizedColumns)) {
                        ++$index;
                    }
                    $order = $index;
                }

                // normalize attributes
                if (is_string($data)) {
                    $column  = array(
                        'field' => $data
                    );
                } else if (!is_array($data)) {
                    throw new Zend_Dojo_View_Exception(
                        "Value of columns option must be a string or an array."
                    );
                } else {
                    if (!isset($data['field'])) {
                        $data['field'] = $field;
                    }
                    $column = $data;
                }

                // add label if not set
                if (!isset($column['label'])) {
                    $column['label'] = ucfirst($column['field']);
                }

                // remove order attribute if set
                if (isset($column['order'])) {
                    unset($column['order']);
                }
                
                // ensure width related options are present.
                $column += array('width' => null, 'fixedWidth' => null, 'minWidth' => null);

                $normalizedColumns[$order] = $column;
            }

            ksort($normalizedColumns);

            // update columns width to ensure they sum up to 100%
            $normalizedColumns = $this->_scaleColumns($normalizedColumns);

            $this->_cache['normalizedColumns'] = $normalizedColumns;
        }
        
        return $this->_cache['normalizedColumns'];
    }

    /**
     * Updates percentage-based column widths to sum up to 100%. Columns will only 
     * be updated if all column widths are specified as percentages or are not defined.
     * 
     * When calculating widths, attempts to preserve 'fixedWidth' values and honor
     * 'minWidth' constraints. However, if the sum of all fixedWidth and/or minWidth
     * values exceeds 100%, they too will be scaled down.
     *
     * @param   array   $columns    list of columns to scale widths of.
     * @return  array   columns array where width of each element has been updated
     *                  such that they sum up to 100% - if it's not possible to scale
     *                  column widths, the original column definitions are returned.
     */
    protected function _scaleColumns(array $columns)
    {
        $widths    = array();
        $minWidths = array();
        $fixedKeys = array();

        // collect data from original columns definition
        // ensuring that specified widths use percentages
        foreach ($columns as $key => $column) {
            
            // check for non-percentage based widths and exit if we find any
            foreach (array('width', 'fixedWidth', 'minWidth') as $widthType) {
                if ($column[$widthType] && !preg_match('/^[0-9]+%$/', $column[$widthType])) {
                    return $columns;
                }
            }

            // if width is fixed, set it and continue
            if ($column['fixedWidth']) {
                $widths[$key] = intval($column['fixedWidth']);
                $fixedKeys[]  = $key;
                continue;
            }

            // process columns with minimum width
            if ($column['minWidth']) {
                $minWidths[$key] = intval($column['minWidth']);
                $widths[$key]    = $minWidths[$key];
            }

            if ($column['width']) {
                $widths[$key] = intval($column['width']);
            }
        }

        // get keys of columns with flexible width
        $flexibleKeys = array_diff(array_keys($widths), $fixedKeys);

        // scale down flexible columns until total width is <= 100%
        // or no further scaling is possible (i.e. because all columns 
        // have a fixed width or have reached a min-width constraint)
        while (array_sum($widths) > 100 && count($flexibleKeys)) {
            // calculate total width of fixed and flexible columns
            $fixedWidth    = 0;
            $flexibleWidth = 0;
            foreach ($widths as $key => $width) {
                if (in_array($key, $flexibleKeys)) {
                    $flexibleWidth += $width;
                } else {
                    $fixedWidth += $width;
                }
            }

            // if total width of fixed columns is over 100%, we can't scale columns
            // and honor fixed widths; so we exit this loop and force them smaller
            if ($fixedWidth > 100) {
                break;
            }

            // compute multiplication coefficient (scale) to shrink flexible columns
            $scale = (100 - $fixedWidth) / $flexibleWidth;

            // shrink flexible columns by scaling factor
            foreach ($flexibleKeys as $index => $key) {
                $newWidth = floor($widths[$key] * $scale);

                // if new width is lower than min-width, use min-width instead 
                // and remove column from the set of flexible-width columns
                if (isset($minWidths[$key]) && $newWidth < $minWidths[$key]) {
                    $newWidth = $minWidths[$key];
                    unset($flexibleKeys[$index]);
                }

                $widths[$key] = $newWidth;
            }
        }

        // if total width is over 100%, it means that we were unable to scale columns
        // while preserving fixed-width or min-width constraints; we scale it here
        // regardless of those constraints
        if (array_sum($widths) > 100) {
            $scale  = 100 / array_sum($widths);
            foreach ($widths as $key => $value) {
                $widths[$key] = floor($value * $scale);
            }
        }

        // at this point, columns are scaled 100%, however due rounding, the sum 
        // might be lower than 100% - to make it exactly 100% we pick one column 
        // and adjust its width - if possible we use a flexible column
        $adjust = count($flexibleKeys)
            ? reset($flexibleKeys)
            : key($widths);

        // copy computed widths to original columns
        foreach ($widths as $key => $width) {
            $width = ($key === $adjust)
                ? $width + (100 - array_sum($widths))
                : $width;

            $columns[$key]['width'] = $width . '%';
        }

        return $columns;
    }

    /**
     * Return markup for data grid head.
     *
     * @return string   data grid head markup.
     */
    protected function _getGridHeadLayout()
    {
        if (!isset($this->_options['columns'])) {
            throw new Zend_Dojo_View_Exception("You must set columns in options.");
        }

        $html = '<thead>' . self::EOL . '<tr>' . self::EOL;
        foreach ($this->_getNormalizedColumns() as $columnAttribs) {
            $label = $columnAttribs['label'];
            unset($columnAttribs['label']);
            $html .= '<th' . $this->_htmlAttribs($columnAttribs) . '>' . $label . '</th>' . self::EOL;
        }

        return $html . '</tr>' . self::EOL . '</thead>' . self::EOL;
    }

    /**
     * Returns plugins attribute suitable for data grid widget.
     * Loads all required dojo modules specified in plugins definition.
     * Also sets up grid rowMenu in menus plugin if there are actions set.
     *
     * @return  array  data grid widget 'plugin' attribute
     */
    protected function _getDataGridPlugins()
    {
        $plugins = isset($this->_options['plugins']) ? $this->_options['plugins'] : array();
        
        // if there are actions set, add row menu plugin
        $menuId = $this->_getActionsMenuId();
        if ($menuId) {
            $plugins['menus'] = array(
                'module'  => 'dojox.grid.enhanced.plugins.Menu',
                'options' => array(
                    'rowMenu' => 'dijitmenu-' . $menuId
                )
            );
        }

        // assemble list of normalized plugins that can be used as a plugin
        // attribute for data grid widget
        $normalized = array();
        foreach ($plugins as $name => $data) {
            // if 'module' is specified, insert dojo module and set
            // plugin options either from 'options' (required if module
            // is also present) of from the whole data otherwise
            if (isset($data['module'])) {
                $this->dojo->requireModule($data['module']);

                // in this case, plugin options must be specied in the 'options' array
                if (!isset($data['options'])) {
                    throw new Zend_Dojo_View_Exception(
                        "Missing 'options' key in the plugin definition"
                      . " (required if 'module' key is present)."
                    );
                }
            }

            $normalized[$name] = isset($data['options']) ? $data['options'] : $data;
        }

        return $normalized;
    }

    /**
     * Return data grid attributes as result of merging default attributes
     * with attributes set in options and encoding attributes with array
     * values.
     *
     * @return array    list with attributes for datagrid.
     */
    protected function _getDataGridAttribs()
    {
        $attribs = isset($this->_options['attribs'])
            ? array_merge($this->_defaultAttribs, $this->_options['attribs'])
            : $this->_defaultAttribs;

        $attribs['jsId']    = $this->_getDataGridId();
        $attribs['store']   = $this->_getDataStoreId();

        // add a query attribute if the form contains populated filter options 
        // so that the datagrid is filtered without user interaction
        if (isset($this->_options['form'])) {
            $query            = $this->_options['form']->getValues();
            $filter           = new P4Cms_Filter_FlattenArray;
            $attribs['query'] = $filter->filter($query);
        }

        // add plugins if there are some
        $plugins = $this->_getDataGridPlugins();
        if ($plugins) {
            $attribs['plugins'] = $plugins;
        }

        // encode array attribs
        foreach ($attribs as $key => &$value) {
            if (is_array($value)) {
                $value = Zend_Json::encode($value);
            }
        }

        // set pagination options
        if (isset($this->_options['pageSize'])) {
            $attribs['rowsPerPage'] = $this->_options['pageSize'];
        }
        if (!isset($attribs['rowCount'])) {
            $attribs['rowCount'] = $attribs['rowsPerPage'];
        }
        if (!isset($attribs['keepRows'])) {
            $attribs['keepRows'] = $attribs['rowsPerPage'] * 10;
        }

        return $attribs;
    }

    /**
     * Return markup for data grid.
     *
     * @return  string  data grid markup.
     */
    protected function _getDataGridLayout()
    {
        // get markup for grid header
        $htmlGridHead = $this->_getGridHeadLayout();

        // prepare grid widget
        $this->_module = $this->_gridModule;
        $attribs       = $this->_prepareDijit(
            $this->_getDataGridAttribs(),
            array(),
            'layout',
            $this->_gridModule
        );

        return '<table' . $this->_htmlAttribs($attribs) . '>' . self::EOL
            . $htmlGridHead
            . '</table>' . self::EOL;
    }
}