<?php
/**
 * Provides a base implementation for models that utilize fields.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
abstract class P4_ModelAbstract extends P4_ConnectedAbstract implements P4_ModelInterface
{
    /**
     * Get the model data as an array.
     *
     * @return  array   the model data as an array.
     */
    public function toArray()
    {
        $values = array();
        foreach ($this->getFields() as $field) {
            $values[$field] = $this->getValue($field);
        }

        return $values;
    }
}
