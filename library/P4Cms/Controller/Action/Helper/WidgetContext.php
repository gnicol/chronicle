<?php
/**
 * Provides a context facility to inform widgets (which can be
 * asynchronously loaded/operated) of arbitrary data.
 *
 * A 'data' controller can use this facility to convey information
 * to widgets like so:
 * $this->widgetContext->setValue('foo', 'bar');
 *
 * A 'widget' controller can use this facility to use information
 * provided like so:
 * $context = $this->widgetContext->getValues();
 * if (array_key_exists('foo', $context) { ... }
 * or
 * if ($this->widgetContext->getValues('foo')) { ... }
 *
 * The widget views and supporting javascript maintain the context
 * across requests to support async creation/configuration/reload.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Controller_Action_Helper_WidgetContext
    extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Zend_Session storage object.
     * @var Zend_Session
     */
    protected static $_context = array();

    /**
     * Flag indicating whether the current invocation is the
     * initial invocation (as opposed to a forward, redirect, or
     * other action manipulation). This is useful to ensure
     * a fresh context.
     */
    protected static $_initialDispatch = true;

    /**
     * Initialize by making ourself available to the controller
     * and cleaning out any cached initial dispatch values.
     */
    public function init()
    {
        // make this action helper available to the controller
        $this->getActionController()->widgetContext = $this;

        // clear context for initial dispatch
        if (static::$_initialDispatch) {
            static::$_initialDispatch = false;
            $this->clearContext();
        }
    }

    /**
     * Clear the context.
     *
     * @return  P4Cms_Controller_Action_Helper_WidgetContext  Provide a fluent interface.
     */
    public function clearContext()
    {
        static::$_context = array();
        return $this;
    }

    /**
     * Set a value in the context.
     *
     * @param   string  $key    The key to set in the context.
     * @param   mixed   $value  The (optional) value to set. If null, the value will be removed from the context.
     * @return  P4Cms_Controller_Action_Helper_WidgetContext  Provide a fluent interface.
     */
    public function setValue($key, $value = null)
    {
        if (!isset($value)) {
            unset(static::$_context[$key]);
            return $this;
        }

        static::$_context[$key] = $value;
        return $this;
    }

    /**
     * Set a number of values in the context at once.
     *
     * @param   array  $values  The key/value pairs to set in the context.
     * @return  P4Cms_Controller_Action_Helper_WidgetContext  Provide a fluent interface.
     */
    public function setValues($values)
    {
        foreach ($values as $key => $value) {
            $this->setValue($key, $value);
        }

        return $this;
    }

    /**
     * Provide the values from the context, or if specified, a named value within.
     *
     * @param   string  $key  Optional key to retrieve from context.
     * @return  mixed   The context, or value within the context.
     */
    public function getValues($key = null)
    {
        if (isset($key)) {
            return array_key_exists($key, static::$_context)
                ? static::$_context[$key]
                : null;
        }
        return static::$_context;
    }

    /**
     * Provide the context in encoded form, suitable for placing in HTML.
     *
     * @return  string  Encoded form of the context.
     */
    public function getEncodedValues()
    {
        $encoded = '';
        $values = $this->getValues();
        if (count($values)) {
            $encoded = Zend_Json::encode($values);
        }
        return $encoded;
    }

    /**
     * Set the context from its encoded form.
     *
     * @param   string  $encoded  A JSON-encoded context.
     * @return  P4Cms_Controller_Action_Helper_WidgetContext  Provide a fluent interface.
     */
    public function setEncodedValues($encoded)
    {
        if (isset($encoded) and strlen($encoded)) {
            $values = Zend_Json::decode($encoded);
            if ($values) {
                $this->setValues($values);
            }
        }
        return $this;
    }
}
