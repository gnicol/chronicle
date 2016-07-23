<?php
/**
 * Extends Zend_Form_Element_Textarea into the P4Cms namespace simply
 * to ensure that we get the standard version of the textarea element
 * and not the dojo version.
 * 
 * This works because P4Cms form elements have higher precedence than
 * Zend dojo elements.
 * 
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_Element_Textarea extends Zend_Form_Element_Textarea
{
}