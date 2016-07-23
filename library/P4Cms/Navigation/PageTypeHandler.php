<?php
/**
 * Defines a page type handler which provides management details for
 * a particular zend navigation container type.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Navigation_PageTypeHandler extends P4Cms_Model
{
    protected static    $_idField   = 'id';

    protected static    $_fields    = array(
        'label'             => array(
            'accessor'      => 'getLabel',
            'mutator'       => 'setLabel'
        ),
        'formCallback'      => array(
            'accessor'      => 'getFormCallback',
            'mutator'       => 'setFormCallback'
        )
    );

    /**
     * Get an instance of the appropriate page handler for the specified class.
     *
     * @param   string|object   $class  the page class to get a handler for.
     * @return  P4Cms_Navigation_PageTypeHandler    the page handler.
     */
    public static function fetch($class)
    {
        // normalize input to a string
        if (!is_string($class)) {
            $class = get_class($class);
        }

        // look for the corresponding handler.
        $handlers = static::fetchAll();
        if (isset($handlers[$class])) {
            return $handlers[$class];
        }

        // no matching handler, return a generic handler.
        return new static(array('id' => $class));
    }

    /**
     * Get all of the valid page type handlers that are available across all modules.
     *
     * @return  P4Cms_Model_Iterator    all page type handlers in the system.
     *
     * @publishes   p4cms.navigation.pageTypeHandlers
     *              Return a P4Cms_Navigation_PageTypeHandler (or array of Page Type Handlers) to be
     *              included in the page type handler fetchAll results. The last subscriber to
     *              return a valid entry for a given ID wins. Page type handlers can provide custom
     *              page types for use in navigation hierarchies.
     */
    public static function fetchAll()
    {
        $handlers = new P4Cms_Model_Iterator;
        $feedback = P4Cms_PubSub::publish('p4cms.navigation.pageTypeHandlers');
        foreach ($feedback as $providedHandlers) {
            // normalize result to always be an array
            if (!is_array($providedHandlers)) {
                $providedHandlers = array($providedHandlers);
            }

            foreach ($providedHandlers as $handler) {
                if ($handler instanceof static
                    && $handler->isValid()) {
                    $handlers[$handler->getId()] = $handler;
                }
            }
        }

        return $handlers;
    }

    /**
     * Get the human-friendly label for this page type handler.
     * If no explicit label has been set the last component of the
     * class name will be used.
     *
     * @return  string  the label for this page handler class
     */
    public function getLabel()
    {
        return $this->_getValue('label')
               ?: ltrim(strrchr($this->getId(), '_'), '_');
    }

    /**
     * Set the human-friendly label for this page type handler.
     *
     * @param   string  $label   the display label for this page type handler.
     * @return  P4Cms_Navigation_PageTypeHandler    provides fluent interface.
     */
    public function setLabel($label)
    {
        return $this->_setValue('label', $label);
    }

    /**
     * Retrieve the form callback for this type. See setFormCallback
     * for more details.
     *
     * @return  null|callable   The callback or null
     */
    public function getFormCallback()
    {
        return $this->_getValue('formCallback');
    }

    /**
     * Set a form callback for this type. The form callback will be
     * executed by prepareForm to offer type an opportunity to modify
     * the default form.
     *
     * The callback should expect a P4Cms_Form for its sole argument
     * and must return a P4Cms_Form.
     *
     * @param   null|callable   $callback   The callback to set or null.
     * @return  P4Cms_Navigation_PageTypeHandler    provides fluent interface.
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
     * @param   P4Cms_Form  $form   The default form to be modified
     * @return  P4Cms_Form  The form to use for this type
     */
    public function prepareForm(P4Cms_Form $form)
    {
        $callback = $this->getFormCallback();
        if (is_callable($callback)) {
            return $callback($form);
        }

        return $form;
    }

    /**
     * Verifies the current model has a valid className (id).
     *
     * @return  bool    True - model is valid / False - model is not valid
     */
    public function isValid()
    {
        return strlen($this->getId())
               && is_subclass_of($this->getId(), 'Zend_Navigation_Container');
    }
}
