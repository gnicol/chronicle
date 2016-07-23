<?php
/**
 * Test methods for the P4Cms Validate CategoryId class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_CategoryIdTest extends TestCase
{
    /**
     * Test instantiation.
     */
    public function testInstantiation()
    {
        $validator = new P4Cms_Validate_CategoryId;
        $this->assertTrue($validator instanceof P4Cms_Validate_CategoryId, 'Expected class');
    }

    /**
     * Test isValid
     */
    public function testIsValid()
    {
        $tests = $this->getTests();

        $invalidSequences = array(
            '%', '\\', '@', '#', '*',
        );
        foreach ($invalidSequences as $invalid) {
            $tests[] = array(
                'label'   => __LINE__ .": $invalid",
                'value'   => $invalid,
                'valid'   => false,
                'error'   => array(
                    'illegalCharacters' => "Only '-', '.' and alpha-numeric characters are permitted in category ids."
                ),
            );
        }

        $validator = new P4Cms_Validate_CategoryId;

        foreach ($tests as $test) {
            $label = $test['label'];
            $validator = new P4Cms_Validate_CategoryId;

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

    /**
     * Get the test cases.
     *
     * @return array  a list of test cases
     */
    private function getTests()
    {
        return array(
            array(
                'label'   => __LINE__ .': null',
                'value'   => null,
                'valid'   => false,
                'error'   => array("invalidType" => "Invalid type given."),
            ),
            array(
                'label'   => __LINE__ .': string, empty',
                'value'   => '',
                'valid'   => false,
                'error'   => array("isEmpty" => "Is an empty string."),
            ),
            array(
                'label'   => __LINE__ .': numeric integer',
                'value'   => 123,
                'valid'   => false,
                'error'   => array("invalidType" => "Invalid type given."),
            ),
            array(
                'label'   => __LINE__ .': numeric float',
                'value'   => 12.3,
                'valid'   => false,
                'error'   => array("invalidType" => "Invalid type given."),
            ),
            array(
                'label'   => __LINE__ .': number string',
                'value'   => '123',
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': alphanumeric',
                'value'   => 'abc123',
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': all valid',
                'value'   => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-._',
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': leading period',
                'value'   => '.abc',
                'valid'   => false,
                'error'   => array(
                    'leadingPeriod' => 'Leading periods are not permitted in category ids.',
                ),
            ),
            array(
                'label'   => __LINE__ .': leading dash',
                'value'   => '-abc',
                'valid'   => false,
                'error'   => array(
                    'leadingDash' => 'Leading dashes are not permitted in category ids.',
                ),
            ),
            array(
                'label'   => __LINE__ .': leading slash',
                'value'   => '/abc',
                'valid'   => false,
                'error'   => array(
                    'leadingSlash' => 'Leading slashes are not permitted in category ids.',
                ),
            ),
            array(
                'label'   => __LINE__ .': leading underscore',
                'value'   => '_abc',
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': index filename',
                'value'   => P4Cms_Categorization_Dir::CATEGORY_FILENAME,
                'valid'   => false,
                'error'   => array(
                    'reservedId' => 'Id is reserved for internal use.',
                ),
            ),
        );
    }
}
