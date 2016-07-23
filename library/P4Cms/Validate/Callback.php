<?php
/**
 * Specialized version of Zend_Validate_Callback that allows construction
 * without a callback function. This is useful when the callback function
 * wants a reference to the validator.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_Callback extends Zend_Validate_Callback
{
    /**
     * Make callback optional.
     *
     * @param   callable|null   $callback   the callback to use or null
     */
    public function __construct($callback = null)
    {
        if ($callback) {
            parent::__construct($callback);
        }
    }
}
