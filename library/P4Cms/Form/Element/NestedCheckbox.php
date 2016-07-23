<?php
/**
 * Extends Zend_Form_Element_MultiCheckbox to support nesting items in UL's.
 * This class simply sets the view helper which does all the actual work.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_Element_NestedCheckbox extends Zend_Form_Element_MultiCheckbox
{
    /**
     * Use formNestedCheckbox view helper by default
     * @var string
     */
    public $helper = 'formNestedCheckbox';

    /**
     * Extends parent to ensure the 'InArray' validator gets a recursively flattened
     * list of valid inputs.
     *
     * @param  string $value  name of checkbox to validate
     * @param  mixed $context optional context
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        if ($this->registerInArrayValidator()) {
            if (!$this->getValidator('InArray')) {
                $options = array();

                // create a recursive function we will use to flatten
                // the multi-dimensional array of options
                $flatten = function($array, &$flat) use (&$flatten)
                {
                    foreach ($array as $optValue => $optLabel) {
                        if (is_array($optLabel)) {
                            $flatten($optLabel, $flat);
                            continue;
                        }

                        $flat[] = $optValue;
                    }
                };

                // populate options with the flattened version of multi-option values
                $flatten($this->getMultiOptions(), $options);

                $this->addValidator(
                    'InArray',
                    true,
                    array($options)
                );
            }
        }
        return parent::isValid($value, $context);
    }

}
