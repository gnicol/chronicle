<?php
/**
 * Extends Zend_Form_Element_Text to create a Note element type.
 * This class simply sets the view helper and overrides isValid() to
 * always return true.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_Element_Note extends Zend_Form_Element_Text
{
    /**
     * Use the FormNote view helper by default
     * @var string
     */
    public $helper = 'formNote';

    /**
     * Extends parent to always return true because it is a note.
     *
     * @param   string  $value      value to validate
     * @param   mixed   $context    optional context
     * @return  bool                true
     */
    public function isValid($value, $context = null)
    {
        return true;
    }

}
