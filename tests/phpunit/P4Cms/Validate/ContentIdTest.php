<?php
/**
 * Test methods for the P4Cms Validate ContentId class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Validate_ContentIdTest extends TestCase
{
    /**
     * Test instantiation.
     */
    public function testInstantiation()
    {
        $validator = new P4Cms_Validate_ContentId;
        $this->assertTrue($validator instanceof P4Cms_Validate_ContentId, 'Expected class');
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
                'error'   => array(),
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
                'value'   => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-.',
                'valid'   => true,
                'error'   => array(),
            ),
            array(
                'label'   => __LINE__ .': /',
                'value'   => '/',
                'valid'   => false,
                'error'   => array(
                    'illegalCharsNoSlash'
                        => "Only '-', '_', '.' and alpha-numeric characters are permitted in identifiers."
                ),
            ),
            array(
                'label'   => __LINE__ .': a/b',
                'value'   => 'a/b',
                'valid'   => false,
                'error'   => array(
                    'illegalCharsNoSlash'
                        => "Only '-', '_', '.' and alpha-numeric characters are permitted in identifiers."
                ),
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $validator = new P4Cms_Validate_ContentId;

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
     * Test isValid when empty values are allowed
     */
    public function testAllowEmptyTrue()
    {
        $tests = array(
            array(
                'label'   => __LINE__ .': null',
                'value'   => null,
                'valid'   => true,
                'error'   => array()
            ),
            array(
                'label'   => __LINE__ .': empty string',
                'value'   => '',
                'valid'   => true,
                'error'   => array()
            ),
            array(
                'label'   => __LINE__ .': numeric integer',
                'value'   => 123,
                'valid'   => true,
                'error'   => array(),
            )
        );

        $validator = new P4Cms_Validate_ContentId;

        $this->assertFalse($validator->allowEmpty(), 'Expected starting state to match');

        foreach ($tests as $test) {
            $label = $test['label'];
            $validator = new P4Cms_Validate_ContentId;
            $validator->setAllowEmpty(true);
            $this->assertTrue($validator->allowEmpty(), "$label - Expected matching state");

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
     * Test isValid when non-existent entries are dis-allowed
     */
    public function testAllowNonExistentFalse()
    {
        $tests = array(
            array(
                'label'   => __LINE__ .': null',
                'value'   => null,
                'valid'   => false,
                'error'   => array(
                    'invalidType' => "Only string and integer identifiers are permitted."
                )
            ),
            array(
                'label'   => __LINE__ .': empty string',
                'value'   => '',
                'valid'   => false,
                'error'   => array(
                    'emptyString' => "Empty strings are not valid identifiers."
                )
            ),
            array(
                'label'   => __LINE__ .': numeric integer',
                'value'   => 123,
                'valid'   => false,
                'error'   => array(
                    'doesntExist' => "The specified content id does not exist."
                ),
            ),
            array(
                'label'   => __LINE__ .': missing entry',
                'value'   => 'madeUp',
                'valid'   => false,
                'error'   => array(
                    'doesntExist' => "The specified content id does not exist."
                ),
            ),
            array(
                'label'   => __LINE__ .': valid entry',
                'value'   => 'test',
                'valid'   => true,
                'error'   => array(),
            )
        );

        $validator = new P4Cms_Validate_ContentId;

        $this->assertTrue($validator->allowNonExistent(), 'Expected starting state to match');

        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($this->p4)
                ->setBasePath("//depot");

        P4Cms_Content::store('test', $adapter);
                
        foreach ($tests as $test) {
            $label = $test['label'];
            $validator = new P4Cms_Validate_ContentId;
            $validator->setAllowNonExistent(false)
                      ->setAdapter($adapter);
            $this->assertFalse($validator->allowNonExistent(), "$label - Expected matching state");

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


        // Lastly, verify it will use the default connection if one is set.

        P4Cms_Record::setDefaultAdapter($adapter);
        $validator = new P4Cms_Validate_ContentId;
        $validator->setAllowNonExistent(false);
        
        $this->assertSame(null, $validator->getAdapter(), 'Expected empty adapter on validator');
        $this->assertTrue($validator->isValid('test'), 'Expected success when using default adapter');
    }

    /**
     * Test isValid when ids must be unique.
     */
    public function testAllowExistingFalse()
    {
        $validator = new P4Cms_Validate_ContentId;

        $this->assertTrue($validator->allowExisting(), 'Expected starting state to match');

        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($this->p4)
                ->setBasePath("//depot");

        P4Cms_Content::store('test', $adapter);

        $validator->setAllowExisting(true)->setAdapter($adapter);
        $this->assertTrue($validator->isValid('test'));

        $validator->setAllowExisting(false);
        $this->assertFalse($validator->isValid('test'));
    }
}
