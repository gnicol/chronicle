<?php
/**
 * This form is specialized for MVC menu items and provides
 * a drop-down menu to select the module/controller/action.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Menu_Form_MenuItemMvc extends Menu_Form_MenuItem
{
    const   ACTION_INTERFACE    = 'Zend_Controller_Action_Interface';
    
    /**
     * Defines the elements that make up the menu MVC item form.
     * Called automatically when the form object is created.
     */
    public function init()
    {
        parent::init();
        
        $this->addElement(
            'select',
            'action',
            array(
                'label'         => 'Action',
                'required'      => true,
                'multiOptions'  => $this->_getActionOptions()
            )
        );

        $this->addElement(
            'textarea',
            'params',
            array(
                'label'         => 'Parameters',
                'description'   => 'Enter additional action parameters in INI format.',
                'rows'          => 10,
                'cols'          => 80
            )
        );
    }
    
    /**
     * Extends parent to combine route params (module/controller/action)
     * into a single action field.
     * 
     * @param   P4Cms_Record|array  $defaults   the default values to set on elements
     * @return  Zend_Form           provides fluent interface
     */
    public function setDefaults($defaults)
    {
        $defaults = $this->_combineRouteParams($defaults);
        $defaults = $this->_combineParamsArray($defaults);
        
        return parent::setDefaults($defaults);
    }
    
    /**
     * Extends parent to combine combine route params (module/controller/action)
     * into a single action field, and change the params array, if it exists,
     * into an ini formatted string
     *
     * isValid never calls setDefaults, which is why this needs to be done here as well
     *
     * @param  array    $data   see parent
     * @return boolean
     */
    public function isValid($data)
    {
        return parent::isValid($this->_combineParamsArray($this->_combineRouteParams($data)));
    }
    
    /**
     * Extend parent to split action into discrete module/controller/action.
     * 
     * @param  bool $suppressArrayNotation see parent
     * @return array
     */
    public function getValues($suppressArrayNotation = false)
    {
        $values = parent::getValues($suppressArrayNotation);
        $values = $this->_splitRouteParams($values);
        $values = $this->_splitParamsIniString($values);

        return $values;
    }

    /**
     * Get a multi-options formatted list of all actions across all modules.
     * 
     * @return  array   a list of all controller actions grouped by module.
     */
    protected function _getActionOptions()
    {
        // front controller gives us access to route defaults.
        $front = Zend_Controller_Front::getInstance();
        
        // fetch all actions from all controllers in all modules.
        $options = array();
        foreach (P4Cms_Module::fetchAllEnabled() as $module) {
            
            // get the module's particulars
            $path        = $module->getPath() . "/controllers";
            $moduleLabel = $module->getName();
            $moduleId    = $this->_formatRoute($module->getId());
            
            // skip if there is no controllers directory
            if (!is_dir($path)) {
                continue;
            }
            
            // add an opt-group for this module.
            $options[$moduleLabel] = array();
            
            // examine each file under the module's controllers directory
            // if it is a controller, fetch all of its action methods
            // always look for the default controller first so it appears 
            // first - all other controllers should list alphabetically.
            $files = array_merge(
                array($front->getDefaultControllerName() . 'Controller.php'),
                scandir($path)
            );
            foreach ($files as $file) {
                
                // only consider files ending in 'controller'.
                $matches = array();
                if (!is_file($path . '/' . $file)
                    || (!preg_match('/((.*)Controller).php$/', $file, $matches))
                ) {
                    continue;
                }
                
                // skip invalid controller classes
                $class = ucfirst($moduleId) . '_' . $matches[1]; 
                if (!class_exists($class) 
                    || !in_array(static::ACTION_INTERFACE, class_implements($class), true)
                ) {
                    continue;
                }

                // get the controller label and id
                $controllerName  = $matches[2];
                $controllerLabel = $this->_formatLabel($controllerName);
                $controllerId    = $this->_formatRoute($controllerName);
                    
                // collect actions from the controller.
                // sort the actions alphabetically, but always put the
                // default action first so that it appears first in options
                $methods = get_class_methods($class);
                natsort($methods);
                array_unshift($methods, $front->getDefaultAction() . 'Action');
                foreach ($methods as $method) {
                    
                    // only consider methods ending in 'action'.
                    $matches = array();
                    if (!method_exists($class, $method) 
                        || !preg_match('/(.*)Action$/', $method, $matches)
                    ) {
                        continue;
                    }
                    
                    $actionName  = $matches[1];
                    $actionLabel = $this->_formatLabel($actionName);
                    $actionId    = $this->_formatRoute($actionName);

                    // combine module/controller/action to form a
                    // qualified action id for the option value.
                    $value = implode('/', array($moduleId, $controllerId, $actionId));

                    // format the action label for the select option
                    $label  = $moduleLabel;
                    $label .= $controllerId !== $front->getDefaultControllerName()
                        ? '/'. $controllerLabel
                        : '';
                    $label .= $actionId !== $front->getDefaultAction()
                        ? '/' . $actionLabel
                        : ' (Default Action)';

                    $options[$moduleLabel][$value] = $label;
                }
            }
        }
        
        // remove any empty opt-groups and return.
        return array_filter($options);
    }

    /**
     * Convert a camelCase formatted action or controller name
     * into a dash-separated version suitable for routing.
     * 
     * @param   string  $input  the camelCase formatted identifier.
     * @return  string  the route formatted (dash-separated) version.
     */
    protected function _formatRoute($input)
    {
        return P4Cms_Controller_Router_Route_Module::formatRouteParam(
            $input
        );
    }
    
    /**
     * Convert a camelCase formatted action or controller name
     * into a human-friendly (space-separated) label.
     * 
     * @param   string  $input  the camelCase formatted identifier.
     * @return  string  the human-friendly version.
     */
    protected function _formatLabel($input)
    {
        return trim(ucwords(preg_replace('/([A-Z])/', ' ${1}', $input)));
    }
    
    /**
     * Convert params INI string to an array using Zend_Config_Ini
     * write to a temp file to facilitate Zend_Config_Ini parsing
     *
     * @param   array   $values     values that contain the string to convert
     * @return  array   values with params converted to an array
     */
    protected function _splitParamsIniString($values)
    {
        $params = array();
        if (isset($values['params']) && strlen($values['params'])) {
            $tempFile = tempnam(sys_get_temp_dir(), 'menu-form');
            file_put_contents($tempFile, $values['params']);
            $config   = new Zend_Config_Ini($tempFile);
            $params   = $config->toArray();
            unlink($tempFile);
        }
        $values['params'] = is_array($params) ? $params : array();

        return $values;
    }
    
    /**
     * If parameters are set, present them in INI format.
     *
     * @param   array   $values     values that contain the array to convert
     * @return  array   values with params converted to an INI formatted strings
     */
    protected function _combineParamsArray($values)
    {
        if (isset($values['params']) && is_array($values['params'])) {
            $config = new Zend_Config($values['params']);
            $writer = new Zend_Config_Writer_Ini();
            $values['params'] = $writer->setConfig($config)->render();
        }
        
        return $values;
    }
    
    /**
     * Combine module/controller/action parameters to a single
     * action value in given values array. If any parameters are
     * missing, default values are used.
     *
     * @param   array   $values     values to combine route parameters in.
     * @return  array   values with module/controller/action combined to action.
     */
    protected function _combineRouteParams($values)
    {
        if (isset($values['module']) || isset($values['controller'])) {
            $front      = Zend_Controller_Front::getInstance();
            $module     = isset($values['module'])
                ? $values['module']
                : $front->getDefaultModule();
            $controller = isset($values['controller'])
                ? $values['controller']
                : $front->getDefaultControllerName();
            $action     = isset($values['action'])
                ? $values['action']
                : $front->getDefaultAction();
            
            $values['action'] = implode('/', array($module, $controller, $action));
            unset($values['module'], $values['controller']);
        }
        
        return $values;
    }
    
    /**
     * Split action parameter into discrete module/controller/action values.
     * If any values are missing, default values are used.
     * 
     * @param   array   $values     values array containing action to split.
     * @return  array   values with action split into module/controller/action.
     */
    protected function _splitRouteParams($values)
    {
        if (!isset($values['module']) && !isset($values['controller'])) {
            $front      = Zend_Controller_Front::getInstance();
            $action     = isset($values['action']) ? $values['action'] : '';
            $params     = explode('/', $action, 3);
            $module     = isset($params[0])
                ? $params[0]
                : $front->getDefaultModule();
            $controller = isset($params[1])
                ? $params[1]
                : $front->getDefaultControllerName();
            $action     = isset($params[2])
                ? $params[2]
                : $front->getDefaultAction();
            
            $values = array_merge(
                $values,
                array(
                    'module'     => $module,
                    'controller' => $controller,
                    'action'     => $action
                )
            );
        }
        
        return $values;
    }
}