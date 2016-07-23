<?php
/**
 * Defines a dynamic handler which operates on dynamic menu items.
 *
 * It provides an expansion callback which will replace a dynamic menu item
 * with zero or more navigation pages/containers.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Navigation_DynamicHandler extends P4Cms_Model
{
    protected static    $_fields    = array(
        'label'             => array(
            'accessor'      => 'getLabel',
            'mutator'       => 'setLabel'
        ),
        'expansionCallback' => array(
            'accessor'      => 'getExpansionCallback',
            'mutator'       => 'setExpansionCallback'
        ),
        'formCallback'      => array(
            'accessor'      => 'getFormCallback',
            'mutator'       => 'setFormCallback'
        )
    );

    /**
     * Get an instance of the specified dynamic handler.
     *
     * @param   string  $id                         the id of the handler to get an instance of.
     * @return  P4Cms_Navigation_DynamicHandler     the requested dynamic handler.
     */
    public static function fetch($id)
    {
        $handlers = static::fetchAll();

        if (!isset($handlers[$id])) {
            // unable to find the requested handler.
            throw new P4Cms_Model_NotFoundException(
                "Cannot fetch handler. The requested handler does not exist."
            );
        }

        return $handlers[$id];
    }

    /**
     * Get all of the valid dynamic handlers that are available across all modules.
     *
     * @return  P4Cms_Model_Iterator    all dynamic handlers in the system.
     *
     * @publishes   p4cms.navigation.dynamicHandlers
     *              Return a P4Cms_Navigation_DynamicHandler (or array of Dynamic Handlers) to be
     *              included in the dynamic handler fetchAll results. The last subscriber to return
     *              a valid entry for a given ID wins. Dynamic menu handlers can provide
     *              dynamically-generated navigation entries.
     */
    public static function fetchAll()
    {
        $handlers = new P4Cms_Model_Iterator;
        $feedback = P4Cms_PubSub::publish('p4cms.navigation.dynamicHandlers');
        foreach ($feedback as $providedHandlers) {
            if (!is_array($feedback)) {
                $feedback = array($feedback);
            }

            foreach ($providedHandlers as $handler) {
                if ($handler instanceof P4Cms_Navigation_DynamicHandler
                    && $handler->isValid()) {
                    $handlers[$handler->getId()] = $handler;
                }
            }
        }

        return $handlers;
    }

    /**
     * Checks if the specified handler id exists or not.
     *
     * @param   string  $id     The id to check for
     * @return  bool            true if exists false otherwise
     */
    public static function exists($id)
    {
        $handlers = static::fetchAll();

        return isset($handlers[$id]);
    }

    /**
     * Get the human-friendly label for this dynamic handler.
     *
     * @return  string  the display label for this handler.
     */
    public function getLabel()
    {
        return $this->_getValue('label');
    }

    /**
     * Set the human-friendly label for this dynamic handler.
     *
     * @param   string  $label                      the display label for this handler.
     * @return  P4Cms_Navigation_DynamicHandler     provides fluent interface.
     */
    public function setLabel($label)
    {
        $this->_setValue('label', $label);

        return $this;
    }

    /**
     * Get the expansion callback for this dynamic handler.
     *
     * @return  callback    The callback function to provide replacement items
     * @throws  P4Cms_Navigation_Exception  If no expansion callback has been set
     */
    public function getExpansionCallback()
    {
        $callback = $this->_getValue('expansionCallback');

        if (!is_callable($callback)) {
            throw new P4Cms_Navigation_Exception(
                'Cannot get expansion callback, no valid callback has been set'
            );
        }

        return $callback;
    }

    /**
     * Check if this handler has a valid expansion callback.
     *
     * @return  bool    true if the handler has a valid callback, false otherwise
     */
    public function hasExpansionCallback()
    {
        return is_callable($this->_getValue('expansionCallback'));
    }

    /**
     * Call the expansion callback for this dynamic handler.
     *
     * @param   P4Cms_Navigation_Page_Dynamic   $item       the dynamic item to be expanded.
     * @param   array                           $options    options (hints) to influence expansion.
     * @return  array|Zend_Navigation_Container|null        the replacement menu items.
     * @see     setExpansionCallback for function signature details.
     */
    public function callExpansionCallback($item, $options)
    {
        $callback = $this->getExpansionCallback();
        return call_user_func($callback, $item, $options);
    }

    /**
     * Set the expansion callback for this dynamic handler.
     * The expected function signature is:
     *
     *  function($item, $options) { return array|Zend_Navigation_Container|null; }
     *
     * The options parameter is an array of options (such as max-depth, max-items)
     * that can be used as a hint to reduce the amount of work done to expand the
     * dynamic item. It is not necessary to honor these options as they will be
     * enforced by the P4Cms_Menu class.
     *
     * If the replacement items have unique identifiers, it is advisable to set
     * the id of each item in the 'expansionId' field. This will allow the system
     * to consistently locate each item (e.g. for the purposes of root selection).
     *
     * Note that the menu-root option will be set to an expansion id if the menu
     * root is within a expanded dynamic menu item.
     *
     * @param   string  $callback                   the callback for this handler.
     * @return  P4Cms_Navigation_DynamicHandler     provides fluent interface.
     * @throws  P4Cms_Navigation_Exception          if passed value is not callable
     */
    public function setExpansionCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new P4Cms_Navigation_Exception(
                'Cannot set expansion callback, passed value is not callable'
            );
        }

        $this->_setValue('expansionCallback', $callback);

        return $this;
    }

    /**
     * Verifies the current model has a callback and label.
     *
     * @return  bool    True - model is valid / False - model is not valid
     */
    public function isValid()
    {
        return $this->hasExpansionCallback() && strlen($this->getLabel());
    }

    /**
     * Retrieve the form callback for this dynamic page type.
     * See setFormCallback for more details.
     *
     * @return  null|callable   the callback or null
     */
    public function getFormCallback()
    {
        return $this->_getValue('formCallback');
    }

    /**
     * Set a form callback for this dynamic page type.
     *
     * The form callback will be executed by prepareForm to offer
     * dynamic handler an opportunity to modify a form for editing
     * dynamic menu items.
     *
     * The callback should expect a P4Cms_Form for its sole argument
     * and must return a P4Cms_Form.
     *
     * @param   null|callable   $callback           the callback to set or null.
     * @return  P4Cms_Navigation_DynamicHandler     provides fluent interface.
     */
    public function setFormCallback($callback)
    {
        if ($callback !== null && !is_callable($callback)) {
            throw new InvalidArgumentException('Form callback must be callable or null');
        }

        return $this->_setValue('formCallback', $callback);
    }

    /**
     * Give form callback an oportunity to modify the passed form.
     * If no callback is present the form is returned unmodified.
     *
     * @param   P4Cms_Form  $form   the menu item form to be modified
     * @return  P4Cms_Form  the form to use for this dynamic item type.
     */
    public function prepareForm(Zend_Form $form)
    {
        $callback = $this->getFormCallback();
        if (is_callable($callback)) {
            return $callback($form);
        }

        return $form;
    }
}
