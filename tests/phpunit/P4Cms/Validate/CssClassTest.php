<?php
/**
 * Test methods for the P4Cms Validate CssClass class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_CssClassTest extends TestCase
{
    /**
     * Test instantiation.
     */
    public function testInstantiation()
    {
        $validator = new P4Cms_Validate_CssClass;
        $this->assertTrue($validator instanceof P4Cms_Validate_CssClass, 'Expected class');
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
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': string, empty',
                'value'   => '',
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': numeric integer',
                'value'   => 123,
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': number string',
                'value'   => '123',
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': numeric float',
                'value'   => 12.3,
                'valid'   => false,
                'error'   => array(
                    'illegalCharacters' => "Only '-', '_' and alpha-numeric characters are permitted in CSS classes."
                ),
            ),
            array(
                'label'   => __LINE__ .': alphanumeric',
                'value'   => 'abc123',
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': all good',
                'value'   => 'abc-123_ABC',
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': period',
                'value'   => '.',
                'valid'   => false,
                'error'   => array(
                    'illegalCharacters' => "Only '-', '_' and alpha-numeric characters are permitted in CSS classes."
                ),
            ),
            array(
                'label'   => __LINE__ .': array',
                'value'   => array(),
                'valid'   => false,
                'error'   => array('invalidType' => 'Invalid type given.'),
            ),
            array(
                'label'   => __LINE__ .': object',
                'value'   => new stdClass,
                'valid'   => false,
                'error'   => array('invalidType' => 'Invalid type given.'),
            ),
            array(
                'label'   => __LINE__ .': two string classes',
                'value'   => 'one two',
                'valid'   => true,
                'error'   => array(),
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $validator = new P4Cms_Validate_CssClass;

            $this->assertSame(
                $test['valid'],
                $validator->isValid($test['value']),
                "$label - Expected validation result."
            );

            if (isset($test['error'])) {
                $this->assertSame(
                    $test['error'],
                    $validator->getMessages(),
                    "$label - Expected error message(s)"
                );
            }
        }
    }
}
