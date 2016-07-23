<?php
/**
 * Test methods for the P4Cms NestedCheckbox Element.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Form_Element_NestedCheckboxTest extends TestCase
{
    /**
     * Test instantiation.
     */
    public function testInstantiation()
    {
        $element = new P4Cms_Form_Element_NestedCheckbox('test');
        $this->assertTrue($element instanceof P4Cms_Form_Element_NestedCheckbox, 'Expected class');
    }

    /**
     * Verify non-nested options get properly output
     */
    public function testFlatOutput()
    {
        $element = new P4Cms_Form_Element_NestedCheckbox('test');

        $element->setMultiOptions(
            array(
                't1' => 'test1',
                't11' => 'Test11',
                't111'  => 'Test111',
                't1111' => 'Test1111',
                't2' => 'test1'
            )
        );

        $this->assertTrue($element->isValid('t1'), 'Expected value t1 to be valid');
        $this->assertTrue($element->isValid('t11'), 'Expected value t11 to be valid');
        $this->assertTrue($element->isValid('t111'), 'Expected value t111 to be valid');
        $this->assertTrue($element->isValid('t1111'), 'Expected value t1111 to be valid');
        $this->assertTrue($element->isValid('t2'), 'Expected value t2 to be valid');

        $this->assertFalse($element->isValid('t3'), 'Expected value t3 to be invalid');
        $this->assertFalse($element->isValid('t33'), 'Expected value t33 to be invalid');
    }

    /**
     * Verify nested options get properly output.
     */
    public function testNestedOutput()
    {
        $element = new P4Cms_Form_Element_NestedCheckbox('test');

        $element->setMultiOptions(
            array(
                't1' => 'test1',
                array(
                    't11' => 'Test11',
                    array(
                        't111'  => 'Test111',
                        array(
                            't1111' => 'Test1111'
                        )
                    )
                ),
                't2' => 'test1'
            )
        );

        $this->assertTrue($element->isValid('t1'), 'Expected value t1 to be valid');
        $this->assertTrue($element->isValid('t11'), 'Expected value t11 to be valid');
        $this->assertTrue($element->isValid('t111'), 'Expected value t111 to be valid');
        $this->assertTrue($element->isValid('t1111'), 'Expected value t1111 to be valid');
        $this->assertTrue($element->isValid('t2'), 'Expected value t2 to be valid');

        $this->assertFalse($element->isValid('t3'), 'Expected value t3 to be invalid');
        $this->assertFalse($element->isValid('t33'), 'Expected value t33 to be invalid');
    }
}
