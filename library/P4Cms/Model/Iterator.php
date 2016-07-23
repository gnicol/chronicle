<?php
/**
 * Provide a common container for a set of models.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Model_Iterator extends P4_Model_Iterator
{
    /**
     * Define the type of models we want to accept in this iterator.
     */
    protected $_allowedModelClass = 'P4Cms_ModelInterface';
}
