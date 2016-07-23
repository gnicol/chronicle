<?php
/**
 * Implementation of P4Cms_Record for testing with no default adapter.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Record_ImplementationNoDefaultAdapter extends P4Cms_Record_Implementation
{
    /**
     * Create a new record instance and (optionally) set the field values.
     * Extends parent to avoid setting the default adapter.
     *
     * @param   array   $values     associative array of keyed field values to load into the model.
     */
    public function __construct($values = null)
    {
        if (is_array($values)) {
            $this->setValues($values);
        }
    }
}
