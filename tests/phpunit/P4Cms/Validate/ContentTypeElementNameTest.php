<?php
/**
 * Test methods for the P4Cms Validate ContentTypeElementName class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_ContentTypeElementNameTest extends TestCase
{
    /**
     * Test instantiation.
     */
    public function testInstantiation()
    {
        $validator = new P4Cms_Validate_ContentTypeElementName;
        $this->assertTrue($validator instanceof P4Cms_Validate_ContentTypeElementName, 'Expected class');
    }

    /**
     * Test isValid.
     */
    public function testIsValid()
    {
        $tests = array(
            array(
                'label'   => __LINE__ .': null',
                'value'   => null,
                'valid'   => false,
                'error'   => array(
                    'isEmpty' => 'Is an empty string.',
                ),
            ),
            array(
                'label'   => __LINE__ .': empty string',
                'value'   => '',
                'valid'   => false,
                'error'   => array(
                    'isEmpty' => 'Is an empty string.',
                ),
            ),
            array(
                'label'   => __LINE__ .': numeric integer',
                'value'   => 123,
                'valid'   => false,
                'error'   => array(
                    'zendFormException' => 'Zend_Form failed to accept the field name.',
                ),
            ),
            array(
                'label'   => __LINE__ .': numeric float',
                'value'   => 12.3,
                'valid'   => false,
                'error'   => array(
                    'zendFormException' => 'Zend_Form failed to accept the field name.',
                ),
            ),
            array(
                'label'   => __LINE__ .': a valid name',
                'value'   => "title",
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': invalid element name.',
                'value'   => "t&t",
                'valid'   => false,
                'error'   => array(
                    'illegalElementName' => "Only '_' and alphanumeric characters are permitted in element names.",
                ),
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $validator = new P4Cms_Validate_ContentTypeElementName;

            $this->assertSame(
                $test['valid'],
                $validator->isValid($test['value']),
                "$label - Expected validation result."
            );

            $this->assertSame(
                $test['error'],
                $validator->getMessages(),
                "$label - Expected error message(s)"
            );
        }
    }
}
