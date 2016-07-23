<?php
/**
 * Extends Zend_Form_Element_Checkbox with slightly different casing
 * (capital 'B') to ensure that we always get the standard checkbox.
 * This works because p4cms form elements have higher precedence than
 * zend dojo elements.
 * 
 * Without this class, we would always get the dojo version of the 
 * checkbox on case-insensitive systems. Whereas, on case-sensitive 
 * systems we would only get the dojo version if the form asks for
 * the 'checkBox' element (as opposed to 'checkbox'.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_Element_CheckBox extends Zend_Form_Element_Checkbox
{
}