<?php
/**
 * Test methods for the P4Cms Note Element.
 *
 * @copyright   2012 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_Element_NoteTest extends TestCase
{
    /**
     * Test instantiation.
     */
    public function testInstantiation()
    {
        $element = new P4Cms_Form_Element_Note('test');
        $this->assertTrue($element instanceof P4Cms_Form_Element_Note, 'Expected class');
    }

    /**
     * Verify the isValid method always returns true.
     */
    public function testIsValid()
    {
        $element = new P4Cms_Form_Element_Note('test');

        $this->assertTrue($element->isValid(null), 'Expected value null to be valid');
        $this->assertTrue($element->isValid(''), 'Expected value "" to be valid');
        $this->assertTrue($element->isValid('abcdef'), 'Expected value abcdef to be valid');
        $this->assertTrue($element->isValid(12), 'Expected positive number 12 to be valid');
        $this->assertTrue($element->isValid(-123), 'Expected negative number -123 to be valid');
        $this->assertTrue($element->isValid(0), 'Expected negative number -123 to be valid');
        $this->assertTrue($element->isValid($element), 'Expected object to be valid');
    }
}
