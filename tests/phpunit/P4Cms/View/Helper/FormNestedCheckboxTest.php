<?php
/**
 * Test methods for the P4Cms FormNestedCheckbox View Helper.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_View_Helper_FormNestedCheckboxTest extends TestCase
{
    /**
     * Test instantiation.
     */
    public function testInstantiation()
    {
        $helper = new P4Cms_View_Helper_FormNestedCheckbox;
        $this->assertTrue($helper instanceof P4Cms_View_Helper_FormNestedCheckbox, 'Expected class');
    }

    /**
     * Verify non-nested options get properly output
     */
    public function testFlatOutput()
    {
        $helper = new P4Cms_View_Helper_FormNestedCheckbox;
        $helper->setView(new Zend_View());

        $output = $helper->formNestedCheckbox(
            'test', null, null,
            array(
                't1' => 'Test1',
                't2' => 'Test2',
                't3' => 'Test3'
            )
        );

        $expected = '<ul class="nested-checkbox">' . "\n"
                  . '<li class=""><label class="" for="test-t1"><input type="checkbox" name="test[]" '
                  . 'id="test-t1" value="t1" class="">Test1</label></li>' . "\n"
                  . '<li class=""><label class="" for="test-t2"><input type="checkbox" name="test[]" '
                  . 'id="test-t2" value="t2" class="">Test2</label></li>' . "\n"
                  . '<li class=""><label class="" for="test-t3"><input type="checkbox" name="test[]" '
                  . 'id="test-t3" value="t3" class="">Test3</label></li></ul>';


        $this->assertSame($expected, $output, 'Expected matching output.');
    }

    /**
     * Verify nested options get properly output.
     */
    public function testNestedOutput()
    {
        $helper = new P4Cms_View_Helper_FormNestedCheckbox;
        $helper->setView(new Zend_View());

        $output = $helper->formNestedCheckbox(
            'test', null, null,
            array(
                't1' => 'Test1',
                0    => array(
                    't11' => 'Test11',
                    0     => array(
                        't111'  => 'Test111',
                        't111b' => 'Test111b'
                    ),
                    't11b'  => 'Test11b'
                ),
                't2' => 'Test2',
                't3' => 'Test3'
            )
        );

        $expected = '<ul class="nested-checkbox">' . "\n"
                  . '<li class=""><label class="" for="test-t1"><input type="checkbox" name="test[]" '
                  . 'id="test-t1" value="t1" class="">Test1</label></li><ul>' . "\n"
                  . '<li class=""><label class="" for="test-t11"><input type="checkbox" name="test[]" '
                  . 'id="test-t11" value="t11" class="">Test11</label></li><ul>' . "\n"
                  . '<li class=""><label class="" for="test-t111"><input type="checkbox" name="test[]" '
                  . 'id="test-t111" value="t111" class="">Test111</label></li>' . "\n"
                  . '<li class=""><label class="" for="test-t111b"><input type="checkbox" name="test[]" '
                  . 'id="test-t111b" value="t111b" class="">Test111b</label></li></ul><li class="">'
                  . '<label class="" for="test-t11b"><input type="checkbox" name="test[]" id="test-t11b" '
                  . 'value="t11b" class="">Test11b</label></li></ul><li class=""><label class="" '
                  . 'for="test-t2"><input type="checkbox" name="test[]" id="test-t2" value="t2" '
                  . 'class="">Test2</label></li>' . "\n"
                  . '<li class=""><label class="" for="test-t3"><input type="checkbox" name="test[]" '
                  . 'id="test-t3" value="t3" class="">Test3</label></li></ul>';


        $this->assertSame($expected, $output, 'Expected matching output.');
    }
}
