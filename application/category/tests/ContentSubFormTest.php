<?php
/**
 * Test the category content sub-form.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Category_Test_ContentSubFormTest extends ModuleTest
{
    /**
     * Test form initialization.
     */
    public function testFormInit()
    {
        // create some categories.
        Category_Model_Category::store('test');
        Category_Model_Category::store('test/sub1');
        Category_Model_Category::store('test/sub2');
        Category_Model_Category::store('test/sub3');
        Category_Model_Category::store('foo');
        Category_Model_Category::store('foo/bar');

        // create form and ensure it has cats.
        $form = new Category_Form_Content;

        $categories = $form->getElement('categories');
        $this->assertTrue($categories instanceof P4Cms_Form_Element_NestedCheckbox);

        $options = array(
            'foo'   => 'foo',
            'foo/'  => array(
                'foo/bar' => 'bar'
            ),
            'test'  => 'test',
            'test/' => array(
                'test/sub1' => 'sub1',
                'test/sub2' => 'sub2',
                'test/sub3' => 'sub3'
            )
        );
        $this->assertSame(
            $options,
            $categories->getMultiOptions(),
            "Expected same options in category sub-form."
        );
    }
}
