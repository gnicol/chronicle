<?php
/**
 * Test methods for the type group form element.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Test_TypeGroupElementTest extends ModuleTest
{
    /**
     * Create a type for testing.
     * 
     * @param string $id   The id to use for the test content type
     */
    public function _createTestType($id = 'test-type')
    {
        $elements = array(
            'title' => array(
                'type'      => 'text',
                'options'   => array('label' => 'Title', 'required' => true),
            ),
            'body'  => array(
                'type'      => 'textarea',
                'options'   => array('label' => 'Body'),
            ),
            'abstract'  => array(
                'type'      => 'textarea',
                'options'   => array('label' => 'Abstract'),
            ),
            'id'        => array(
                'type'      => 'text',
                'options'   => array('label' => 'ID', 'required' => true)
            )
        );
        
        $type = new P4Cms_Content_Type;
        $type->setId($id)
             ->setLabel("Test Type")
             ->setElements($elements)
             ->setValue('icon', file_get_contents(TEST_ASSETS_PATH . '/images/content-type-icon.png'))
             ->setFieldMetadata('icon', array("mimeType" => "image/png"))
             ->setGroup('test-group')
             ->save();

        return $type;
    }
    
    /**
     * Test instantiation.
     */
    public function testInstantiation()
    {
        $element = new Content_Form_Element_TypeGroup('test');
        $this->assertTrue($element instanceof Content_Form_Element_TypeGroup, 'Expected class');
    }
    
    /**
     * Test that the default values are set as expected.
     */
    public function testDefaults()
    {
        $element = new Content_Form_Element_TypeGroup('test');
        $options = $element->getMultiOptions();
        $this->assertEquals(array(), $options, 'Expected empty options due to no content types present.');
        
        $this->_createTestType();
        $element  = new Content_Form_Element_TypeGroup('test');
        $options  = $element->getMultiOptions();
        $expected = array(
            'test-group/'  => array('test-group/test-type' => 'Test Type'), 
            'test-group/*' => 'test-group'
        );
        $this->assertEquals($expected, $options, 'Expected test options to be present.');
    }
    
    /**
     * Test the normalization.
     */
    public function testNormalization()
    {
        $this->_createTestType();
        $element    = new Content_Form_Element_TypeGroup('test');
        $normalized = $element->getNormalizedTypes();
        $this->assertEquals(array(), $normalized, 'Expected empty array for normalized value.');
        
        $element->setValue(array('test-group/*'));
        $normalized = $element->getNormalizedTypes();
        $expected   = array(
            'test-group/test-type' => 'Test Type',
            '1'                    => 'test-type'
        );
        
        $this->assertEquals($expected, $normalized, 'Expected normalized value.');
    }
    
    /**
     * Test the functionality in setValue
     * Note that the order of results depends on which element is appended by the method.
     */
    public function testSetValue()
    {
        $element = new Content_Form_Element_TypeGroup('test');
        $this->assertEquals(null, $element->getValue(), 'Expected value to be null, line ' . __LINE__);
        
        $this->_createTestType();
        $this->_createTestType('test-type-2');
        $element = new Content_Form_Element_TypeGroup('test');
        
        $element->setValue(array('test-group/test-type'));
        $expected = array('test-group/test-type');
        $this->assertEquals(
            $expected, 
            $element->getValue(), 
            'Expected a single value when it is selected, line ' . __LINE__
        );
        
        $element->setValue(array('test-group/*'));
        $expected = array(
            'test-group/*',
            'test-group/test-type-2',
            'test-group/test-type'
        );
        $this->assertEquals(
            $expected, 
            $element->getValue(), 
            'Expected all types to be selected when group is selected, line ' . __LINE__
        );
        
        $element->setValue(
            array(
                'test-group/test-type',
                'test-group/test-type-2'
            )
        );
        $expected = array(
            'test-group/test-type',
            'test-group/test-type-2',
            'test-group/*'
        );
        $this->assertEquals(
            $expected, 
            $element->getValue(), 
            'Expected group to be selected when all types are selected, line ' . __LINE__
        );
    }
}
