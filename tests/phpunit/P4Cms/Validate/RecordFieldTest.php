<?php
/**
 * Test methods for the P4Cms Validate SiteName class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_RecordFieldTest extends TestCase
{
    /**
     * Test instantiation.
     */
    public function testInstantiation()
    {
        $validator = new P4Cms_Validate_RecordField;
        $this->assertTrue($validator instanceof P4Cms_Validate_RecordField, 'Expected class');
    }

    /**
     * Test isValid.
     */
    public function testIsValid()
    {
        // note currently, RecordField validation is based on AttributeName
        // validation, which is based on KeyName validation. Only additional
        // RecordField validation is tested here.
        $tests = array(
            array(
                'label'   => __LINE__ .': no leading underscore',
                'value'   => 'abc',
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': leading underscore',
                'value'   => '_abc',
                'valid'   => false,
                'error'   => array(
                    'leadingUnderscore' => "First character cannot be underscore ('_').",
                ),
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $validator = new P4Cms_Validate_RecordField;

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
