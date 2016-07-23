<?php
/**
 * Test methods for the P4Cms Validate ContentTypeId class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_RecordIdTest extends TestCase
{
    /**
     * Test instantiation.
     */
    public function testInstantiation()
    {
        $validator = new P4Cms_Validate_RecordId;
        $this->assertTrue($validator instanceof P4Cms_Validate_RecordId, 'Expected class');
    }

    /**
     * Test isValid.
     */
    public function testIsValid()
    {
        $tests = $this->getTests();
        
        $tests[2]['valid'] = true;
        $tests[2]['error'] = array();

        foreach ($tests as $test) {
            $label = $test['label'];
            $validator = new P4Cms_Validate_RecordId;

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
     * Test isValid when forward slashes are disallowed
     */
    public function testAllowForwardSlashFalse()
    {
        $tests = $this->getTests();

        $validator = new P4Cms_Validate_RecordId;
        $this->assertTrue($validator->allowForwardSlash(), 'Expected starting state to match');
        
        $validator->setAllowForwardSlash(false);
        $this->assertFalse($validator->allowForwardSlash(), 'Expected state to match');
        
        $this->assertFalse(
            $validator->isValid('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789/_-.')
        );
        
        $this->assertSame(
            array('illegalCharsNoSlash'
                => "Only '-', '_', '.' and alpha-numeric characters are permitted in identifiers.",
            ),
            $validator->getMessages()
        );
        
        $this->assertTrue(
            $validator->isValid('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-.')
        );
        
        $this->assertFalse($validator->isValid('/'));
    }

    /**
     * Get the test cases.
     *
     * @return array   the test cases
     */
    private function getTests()
    {
        return array(
            array(
                'label'   => __LINE__ .': null',
                'value'   => null,
                'valid'   => false,
                'error'   => array(
                    'invalidType' => 'Only string and integer identifiers are permitted.'
                ),
            ),
            array(
                'label'   => __LINE__ .': empty string',
                'value'   => '',
                'valid'   => false,
                'error'   => array(
                    'emptyString' => 'Empty strings are not valid identifiers.'
                ),
            ),
            array(
                'label'   => __LINE__ .': numeric integer',
                'value'   => 123,
                'valid'   => true,
                'error'   => array()
            ),
            array(
                'label'   => __LINE__ .': numeric float',
                'value'   => 12.3,
                'valid'   => false,
                'error'   => array(
                    'invalidType' => 'Only string and integer identifiers are permitted.'
                ),
            ),
            array(
                'label'   => __LINE__ .': all valid',
                'value'   => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789/_-.',
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': %',
                'value'   => '%',
                'valid'   => false,
                'error'   => array(
                    'illegalCharacters'
                        => "Only '-', '/', '_', '.' and alpha-numeric characters are permitted in identifiers.",
                ),
            ),
            array(
                'label'   => __LINE__ .': %',
                'value'   => 'alksdjfalksdj/',
                'valid'   => false,
                'error'   => array(
                    'trailingSlash' => 'Trailing slashes are not permitted in identifiers.',
                ),
            ),
            array(
                'label'   => __LINE__ .': numeric string with trailing letter',
                'value'   => '123a',
                'valid'   => true,
                'error'   => array(),
            )
        );
    }
}
