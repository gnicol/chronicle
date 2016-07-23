<?php
/**
 * Extends zend's cache manager to clear the built-in templates
 * as we don't want them and there is no way to get rid of them.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Cache_Manager extends Zend_Cache_Manager
{
    protected $_optionTemplates = array();
}
