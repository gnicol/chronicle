<?php
/**
 * Widgets are configurations of a widget-controller in a region.
 * The widget model provides read/write access to an individual
 * widget's configuration information.
 *
 * Each widget has an id that is unique within it's region. The id is
 * is determined by the order that that the widget was defined in.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Widget extends P4Cms_Record_Config
{
    protected static $_storageSubPath = 'widgets';
    protected static $_fields         = array(
        'id',
        'region',
        'type',
        'title',
        'showTitle',
        'order'         => array(
            'default'   => 0
        ),
        'class',
        'asynchronous'  => array(
            'default'   => false
        ),
        'config',
        'addTime'
    );
    protected static $_idField        = 'id';

    protected        $_error          = null;
    protected        $_exception      = null;

    /**
     * Create a new instance of a widget by specifying a widget type.
     * The type can be a type instance or a type id.
     *
     * If caller provides values, they will be merged with the widget
     * type defaults (given values win).
     *
     * @param   string|P4Cms_Widget_Type    $type       the type of widget to create.
     * @param   array                       $values     optional - widget values to use
     * @param   P4Cms_Record_Adapter        $adapter    optional - storage adapter to use.
     * @return  P4Cms_Widget                a newly created widget instance.
     * @throws  P4Cms_Widget_Exception      if the specified type is invalid.
     */
    public static function factory($type, array $values = null, P4Cms_Record_Adapter $adapter = null)
    {
        // lookup type model if id given.
        if (is_string($type) && P4Cms_Widget_Type::exists($type)) {
            $type = P4Cms_Widget_Type::fetch($type);
        }

        // validate type.
        if (!$type instanceof P4Cms_Widget_Type || !$type->isValid()) {
            throw new P4Cms_Widget_Exception(
                "Cannot create widget. The given widget type is invalid."
            );
        }

        // merge caller provided values with type defaults.
        $values = $values ?: array();
        $values = array_merge(
            array(
                'title'     => $type->label,
                'config'    => $type->getDefaults()
            ),
            $values
        );

        // instantiate widget setting type and defaults.
        return static::create($values, $adapter)->setType($type);
    }

    /**
     * Get the widgets contained in a given region.
     *
     * Widgets have a 'region' attribute which we utilize to locate them.
     *
     * @param   string                          $id         the id of the region
     * @param   P4Cms_Record_Query|array|null   $query      optional - query options to augment result.
     * @param   P4Cms_Record_Adapter            $adapter    optional - storage adapter to use.
     * @return  P4Cms_Record                    the requested widget(s)
     */
    public static function fetchByRegion($id, $query = null, P4Cms_Record_Adapter $adapter = null)
    {
        if (empty($id)) {
            throw new InvalidArgumentException(
                'Cannot fetch by region. Region id must be specified.'
            );
        }

        $query = static::_normalizeQuery($query);
        $query->addFilter(P4Cms_Record_Filter::create()->add('region', $id));

        $widgets = new P4Cms_Model_Iterator;
        foreach (static::fetchAll($query, $adapter) as $widget) {
            $type = $widget->getValue('type');
            if (P4Cms_Widget_Type::exists($type) && P4Cms_Widget_Type::fetch($type)->isValid()) {
                $widgets[$widget->getId()] = $widget;
            }
        }

        // put widgets in order. we do this client side as the server lacks
        // a purely numeric sort (negative order for example would be a problem)
        $widgets->sortBy(
            array(
                'order'   => array(P4Cms_Model_Iterator::SORT_NUMERIC),
                'addTime' => array(P4Cms_Model_Iterator::SORT_NUMERIC)
            )
        );

        return $widgets;
    }

    /**
     * Run this widget and return the output.
     *
     * @param   bool    $throwExceptions    optional - defaults to false to prevent widgets from
     *                                      halting execution - if you want to permit exceptions
     *                                      pass true for this argument.
     * @return  string  the output produced by the widget.
     */
    public function run($throwExceptions = false)
    {
        // try to run the widget controller.
        // suppress exceptions unless throw exceptions is true.
        $output = '';
        try {
            $view   = Zend_Layout::getMvcInstance()->getView();
            $type   = $this->getType();
            $params = $type->getRouteParams();
            
            // when an action parameter is included in a page request (e.g. ?action=foo),
            // the action will be retrieved by dispatcher->getActionMethod() because
            // $params['action'] is null -- it's not defined in the module.ini file.  
            // we provide a default 'index' action here to avoid the problem.
            $output = $view->action(
                $params['action'] ?: 'index',
                $params['controller'],
                $params['module'],
                array('widget' => $this)
            );
        } catch (Exception $e) {
            P4Cms_Log::logException("Failed to run widget.", $e);

            $this->_exception = $e;
            $this->_error     = $e->getMessage();

            if ($throwExceptions) {
                throw $e;
            }
        }

        return $output;
    }

    /**
     * Determine if this widget suffered an error during run.
     *
     * @return  bool    true if an error occurred.
     */
    public function hasError()
    {
        return is_string($this->_error) && strlen($this->_error);
    }

    /**
     * Get the error message if an error occurred during run.
     *
     * @return  string                  the error message.
     * @throw   P4Cms_Widget_Exception  if no error occured.
     */
    public function getError()
    {
        if (!$this->hasError()) {
            throw new P4Cms_Widget_Exception(
                "Cannot get error. No error occurred."
            );
        }

        return $this->_error;
    }

    /**
     * Determine if an exception occurred during run.
     *
     * @return  bool    true if an exception occurred.
     */
    public function hasException()
    {
        return $this->_exception instanceof Exception;
    }

    /**
     * Get the exception if one occurred.
     *
     * @return  Exception               the exception that occurred.
     * @throws  P4Cms_Widget_Exception  if no exception occurred.
     */
    public function getException()
    {
        if (!$this->hasException()) {
            throw new P4Cms_Widget_Exception(
                "Cannot get exception. No exception occurred."
            );
        }

        return $this->_exception;
    }

    /**
     * Set the type of this widget.
     *
     * @param   null|string|P4Cms_Widget_Type   $type   the type of widget (either id or instance).
     */
    public function setType($type)
    {
        // normalize type instance to id string.
        if ($type instanceof P4Cms_Widget_Type) {
            $type = $type->getId();
        }

        if (!is_string($type) && $type !== null) {
            throw new InvalidArgumentException(
                "Widget type must be a string, a widget type instance or null."
            );
        }

        return $this->_setValue('type', $type);
    }

    /**
     * Get the type of this widget.
     *
     * @return  P4Cms_Widget_Type       an instance of this widget's type.
     * @throws  P4Cms_Widget_Exception  if no valid type is set.
     */
    public function getType()
    {
        $type = $this->_getValue('type');

        // ensure type id is set.
        if (!is_string($type) || !strlen($type)) {
            throw new P4Cms_Widget_Exception(
                "Cannot get widget type. The type has not been set."
            );
        }

        return P4Cms_Widget_Type::fetch($type);
    }

    /**
     * Whether or not to load this widget asynchronously.
     * If true, widget should not be run during initial page
     * rendering process, but rather via a subsequent http
     * request.
     *
     * @return  bool    true if widget should be loaded asynchronously.
     */
    public function isAsynchronous()
    {
        return (bool) $this->asynchronous;
    }

    /**
     * Save this widget.
     * Extends parent to keep record of the time that the widget is added.
     *
     * @param   string  $description  optional - a description of the change.
     * @return  P4Cms_Record          provides a fluent interface
     */
    public function save($description = null)
    {
        if (!$this->getValue('addTime')) {
            $this->setValue('addTime', microtime(true));
        }

        return parent::save($description);
    }

    /**
     * Collect all default widgets and install any that are missing.
     *
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     */
    public static function installDefaults(P4Cms_Record_Adapter $adapter = null)
    {
        // clear the module/theme cache
        P4Cms_Module::clearCache();
        P4Cms_Theme::clearCache();

        $packages = P4Cms_Module::fetchAllEnabled();

        // add the active theme to the packages list
        if (P4Cms_Theme::hasActive()) {
            $packages[] = P4Cms_Theme::fetchActive();
        }

        // install default widgets for each package
        foreach ($packages as $package) {
            static::installPackageDefaults($package, $adapter);
        }
    }

    /**
     * Collect all default widgets from a package and install any that are missing.
     *
     * @param   P4Cms_PackageAbstract   $package          the package whose widgets are to be installed.
     * @param   P4Cms_Record_Adapter    $adapter          optional - storage adapter to use.
     * @param   boolean                 $restoreDelete    optional - restore the deleted widget.
     */
    public static function installPackageDefaults(
        P4Cms_PackageAbstract $package,
        P4Cms_Record_Adapter $adapter = null,
        $restoreDelete = false)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        // collect default widgets that have not been configured,
        // and instantiate the defined widgets
        $regionWidgetConfig = $package->getWidgetConfig();

        foreach ($regionWidgetConfig as $regionId => $widgetConfigs) {
            $widgets = array();
            foreach ($widgetConfigs as $id => $widgetConfig) {
                // create predictable uuid formatted id from package name, region id and the given id.
                $widgetId = $package->getName() . '-' . $regionId . '-' . $id;
                $widgetId = P4Cms_Uuid::fromMd5(md5($widgetId))->get();

                // skip existing widgets
                if (P4Cms_Widget::exists($widgetId, null, $adapter)) {
                    continue;
                }

                // Restore the widget if it has been deleted and $restoreDelete is set
                if ($restoreDelete &&
                    P4Cms_Widget::exists($widgetId, array('includeDeleted' => true), $adapter)
                ) {
                    $widget = P4Cms_Widget::fetch($widgetId, array('includeDeleted' => true), $adapter);
                    $widget->save();
                } else {
                    // try to create and save the widget.
                    try {
                        $type   = isset($widgetConfig['type']) ? $widgetConfig['type'] : null;
                        $widget = P4Cms_Widget::factory($type, $widgetConfig, $adapter);
                        $widget->setId($widgetId)
                               ->setValue('region', $regionId)
                               ->save();
                    } catch (P4Cms_Widget_Exception $e) {
                        continue;
                    }
                }
            }
        }
    }

    /**
     * Remove widgets provided by a package.
     *
     * @param   P4Cms_PackageAbstract   $package    the package whose widgets to be removed
     * @param   P4Cms_Record_Adapter    $adapter    optional - storage adapter to use.
     */
    public static function removePackageDefaults(
        P4Cms_PackageAbstract $package,
        P4Cms_Record_Adapter $adapter = null)
    {
        // if no adapter given, use default.
        $adapter = $adapter ?: static::getDefaultAdapter();

        // collect default regions from the package
        $regionWidgetConfig = $package->getWidgetConfig();

        foreach ($regionWidgetConfig as $regionId => $widgetConfigs) {
            foreach ($widgetConfigs as $id => $widgetConfig) {
                // create predictable uuid formatted id from package name, region id and the given id.
                $widgetId = $package->getName() . '-' . $regionId . '-' . $id;
                $widgetId = P4Cms_Uuid::fromMd5(md5($widgetId))->get();

                // delete the widget
                try {
                    P4Cms_Widget::remove($widgetId, $adapter);
                } catch (Exception $e) {
                    // we can't do much if the delete fails.
                    continue;
                }
            }
        }
    }
}
