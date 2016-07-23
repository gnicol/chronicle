<?php
/**
 * Provides macro expansion capabilities
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Filter_Macro implements Zend_Filter_Interface
{
    const TOPIC = 'p4cms.macro.';

    protected $_context = null;

    /**
     * Set filtering context options during instantiation.
     *
     * Context must be given as an array. It is recommended that each element
     * of the context array be given a descriptive key. For example:
     *
     *  array('widget' => $widget)
     *
     * @param   null|array          $context  The context to provide to filter handlers.
     * @return  P4Cms_Filter_Macro  provide a fluent interface.
     */
    public function __construct(array $context = null)
    {
        $this->setContext($context);
        return $this;
    }

    /**
     * Get current filter context options.
     *
     * @return  array|null  The current filter content options.
     */
    public function getContext()
    {
        return $this->_context;
    }

    /**
     * Set filter context options.
     *
     * @param   null|array  $context    The filter context options to set.
     * @return  P4Cms_Filter_Macro      Provide a fluent interface.
     */
    public function setContext(array $context = null)
    {
        $this->_context = $context;
        return $this;
    }

    /**
     * Expand macros in the input string.
     * Macros take the unpaired form of:
     *
     *  {{macro:arg1,arg2,...}}
     *
     * A macro can be paired with a matching closing token in
     * which case it will receive the enclosed block of input as
     * its 'body' parameter:
     *
     *  {{macro:arg1,arg2,...}}
     *      body
     *  {{/macro}}
     *
     * Unpaired macros can be explicitly closed with trailing slash:
     *
     *  {{macro:arg1,arg2,.../}}
     *
     *
     * Each macro found in the input string produces call to publish
     * on a topic named for the macro:
     *
     *  p4cms.macro.<macro-name>
     *
     * To add more macros, simply subscribe to the appropriate topic.
     * The expected function signature for subscribers is:
     *
     *  function($args, $body, $context);
     *
     * $args    array               The arguments passed to the macro
     * $body    string|null         The enclosed body text for paired macros;
     *                              null otherwise
     * $context array               Any context provided by the caller that could be useful for macro expansion.
     *
     * The return value of the subscribed callback is taken to be the
     * replacement string. If the callback returns false, or if there is
     * no subscribed callback, the macro is left unexpanded.
     *
     * @param   string  $input  the input to expand macros for.
     * @return  string  the input string with macros evaluated.
     *
     * @publishes   p4cms.macro.<name>
     *              Return the result of macro expansion. Normally this would be the expanded text
     *              that will replace the macro for display. Returning false indicates that the
     *              encountered text should not be processed and be returned unchanged. If multiple
     *              subscribers are present, only the first result is examined (subsequent
     *              subscribers are ignored). When subscribing, the <name> portion of this topic
     *              should be replaced with the macro name.
     *              array   $args       The arguments passed to the macro.
     *              string  $body       The enclosed body text for paired macros; null otherwise.
     *              array   $context    An array containing context-specific information to assist
     *                                  macro expansion. Macros filtered by a P4Cms_Content entry
     *                                  receive the entry in the 'content' key, and the current form
     *                                  element in the 'element' key.
     */
    public function filter($input)
    {
        // capture the context and topic so thay can be passed pass into our anonymous function
        $context    = $this->getContext();
        $topic      = static::TOPIC;

        return preg_replace_callback(
            '/
            {{                              # macro open sequence
                \s*([^\/][^:]+?)\s*         # pull out name; enforces opening tags only by excluding
                                            # leading slash. Trims leading & trailing whitespace.
                (?:(:)\s*(.+?)\s*)?         # If a colon is present, get any listed arguments
                (?:
                    \/}}                    # If macro closes with slash }} we are done
                    |
                    }}                      # If macro closes with just }} look for closing tag
                    (?:(.+)                 # capture anything between the opening and closing tags
                        (?:{{\s*\/\1\s*}})  # Find same name with a leading slash
                    )?                      # flag that closing tag and body bit is optional
                )
            /sx',                           // s for multi-line matching and x to allow comments,
            function ($macro) use ($context, $topic)
            {
                $macro += array_fill(0, 5, null);   // Normalize array to always have 5 entries
                $name   = $macro[1];
                // shortcut for the literal macro; no 3rd-party expansion needed/allowed.
                if ($name === 'literal') {
                    return $macro[0];
                }

                $args   = $macro[2] == ':' ? str_getcsv($macro[3], ",") : array();
                $body   = $macro[4];
                $result = P4Cms_PubSub::publish($topic . $name, $args, $body, $context);

                return count($result) && $result[0] !== false
                    ? $result[0]
                    : $macro[0];
            },
            $input
        );
    }
}