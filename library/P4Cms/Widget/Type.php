<?php
/**
 * Encapsulates information about widget types. Widgets are just
 * configurable action controllers.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Widget_Type extends P4Cms_Model
{
    protected static $_types = null;

    /**
     * Determine if a given widget type exists.
     *
     * @param   string  $id     the id of the widget type to check.
     * @return  bool    true if the widget type exists.
     */
    public static function exists($id)
    {
        try {
            static::fetch($id);
        } catch (P4Cms_Model_NotFoundException $e) {
            return false;
        }

        return true;
    }

    /**
     * Get the requested widget type.
     *
     * @param   string  $id         the id of the widget type to get.
     * @return  P4Cms_Widget_Type   the requested widget type.
     */
    public static function fetch($id)
    {
        $types = static::fetchAll();

        if (!isset($types[$id])) {
            // unable to find the requested type.
            throw new P4Cms_Model_NotFoundException(
                'Cannot fetch widget type. The requested type does not exist.'
            );
        }

        return $types[$id];
    }

    /**
     * Get all of the valid widget types that are available across all modules.
     *
     * @return  P4Cms_Model_Iterator    all widget types in the system.
     */
    public static function fetchAll()
    {
        // only collect types once.
        if (static::$_types instanceof P4Cms_Model_Iterator) {
            return static::$_types;
        }

        $types = new P4Cms_Model_Iterator;
        foreach (P4Cms_Module::fetchAllEnabled() as $module) {
            foreach (static::_typesFromPackage($module) as $type) {
                $types[$type->getId()] = $type;
            }
        }

        // sort the types
        $types->uasort(
            function($a, $b) 
            {
                return strnatcasecmp($a->label, $b->label);
            }
        );

        // keep it!
        static::$_types = $types;
        return $types;
    }

    /**
     * Programatically add a type to the list of types.
     * Note: there is no persistent storage of these types - they
     * survive only for the duration of the process (or until they
     * are explicitly cleared).
     *
     * @param   P4Cms_Widget_Type           $type   the widget type to add.
     * @throws  InvalidArgumentException    if the given type is invalid.
     */
    public static function addType($type)
    {
        if (!$type instanceof P4Cms_Widget_Type) {
            throw new InvalidArgumentException(
                "Cannot add widget type. Type is not a valid widget type instance."
            );
        }

        $types = static::fetchAll();
        $types[$type->getId()] = $type;

        static::$_types = $types;
    }

    /**
     * Clear the types cache.
     */
    public static function clearCache()
    {
        static::$_types = null;
    }

    /**
     * Get the widget controller class name.
     *
     * @return  string  the full name of the widget controller class.
     */
    public function getControllerClassName()
    {
        // convert controller from route to class name format.
        $dispatcher = new Zend_Controller_Dispatcher_Standard;
        $controller = $this->getValue('controller');
        $controller = $dispatcher->formatControllerName($controller);

        return $this->getModulePackageName() . "_" . $controller;
    }


    /**
     * Get the package name of the module that provides this widget.
     *
     * @return  string  the package name of the widget's module.
     */
    public function getModulePackageName()
    {
        // convert module from route to class name format.
        $dispatcher = new Zend_Controller_Dispatcher_Standard;
        return $dispatcher->formatModuleName($this->getValue('module'));
    }

    /**
     * Get the widget module/controller/action route parameters
     * to invoke this widget.
     *
     * @return  array   the module, controller and action route parameters.
     */
    public function getRouteParams()
    {
        return array(
            'module'        => $this->_getValue('module'),
            'controller'    => $this->_getValue('controller'),
            'action'        => $this->_getValue('action')
        );
    }

    /**
     * Determine if this widget type is valid (e.g. does the
     * controller class exist?).
     *
     * @return  bool    true if this type is valid.
     */
    public function isValid()
    {
        // ensure type has an id.
        if (!strlen($this->getId())) {
            return false;
        }

        // ensure controller class is valid.
        $controller = $this->getControllerClassName();
        if (!class_exists($controller)
            || !is_subclass_of($controller, 'P4Cms_Widget_ControllerAbstract')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Determine if there is an icon for this widget type.
     *
     * @return  bool    true if this type has an icon.
     */
    public function hasIcon()
    {
        $icon = $this->_getValue('icon');
        return isset($icon) && is_string($icon);
    }

    /**
     * Get the URI to the widget type icon file.
     *
     * @return  string                  the URI of the type icon.
     * @throws  P4Cms_Widget_Exception  if there is no icon.
     */
    public function getIconUrl()
    {
        if (!$this->hasIcon()) {
            throw new P4Cms_Package_Exception(
                "Cannot get icon URI. This widget type has no icon."
            );
        }

        $icon = $this->_getValue('icon');

        return (P4Cms_Uri::isRelativeUri($icon))
            ? $this->getModule()->getBaseUrl() . '/' . $icon
            : $icon;
    }
    
    /**
     * Get an instance of the module that provides this widget.
     *
     * @return  P4Cms_Module    the module package that provides this widget.
     */
    public function getModule()
    {
        return P4Cms_Module::fetch($this->getValue('module'));
    }

    /**
     * Get the default options for this widget type.
     *
     * @return  array   widget defaults.
     */
    public function getDefaults()
    {
        $defaults = $this->_getValue('defaults');
        return is_array($defaults) ? $defaults : array();
    }

    /**
     * Extract the widget types from the given module.
     *
     * @param   P4Cms_Module    $module     the module to get types from.
     * @return  array           all of the widget types in package info.
     */
    protected static function _typesFromPackage($module)
    {
        $info = $module->getPackageInfo();

        // if no widgets, early exit.
        if (!isset($info['widgets']) || !is_array($info['widgets'])) {
            return array();
        }

        // turn widget info into type instances.
        $types = array();
        foreach ($info['widgets'] as $typeId => $typeInfo) {

            // qualify type id with module name.
            $typeId = $module->getRouteFormattedName() . "/" . $typeId;

            // add module route param to type info.
            $typeInfo['module'] = $module->getRouteFormattedName();
            
            $type = new static;
            $type->setId($typeId);
            $type->setValues($typeInfo);
            $types[$typeId] = $type;
        }

        return $types;
    }
}
