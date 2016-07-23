<?php
/**
 * Test the content type model.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Content_TypeTest extends TestCase
{
    /**
     * Set core modules path so that site load can find modules.
     * Load test sites and set the sites path to the test sites.
     */
    public function setUp()
    {
        parent::setUp();
        P4Cms_Module::reset();
        P4Cms_Module::setCoreModulesPath(TEST_ASSETS_PATH . '/core-modules');
        P4Cms_Module::addPackagesPath(TEST_ASSETS_PATH . '/sites/test/modules');

        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($this->p4)
                ->setBasePath("//depot");
        P4Cms_Record::setDefaultAdapter($adapter);

        P4_Connection::setDefaultConnection($this->p4);
    }

    /**
     * Cleanup.
     */
    public function tearDown()
    {
        P4Cms_Module::reset();
        P4Cms_Record::clearDefaultAdapter();
        P4_Connection::clearDefaultConnection();
        P4Cms_Content::setUriCallback(null);

        parent::tearDown();
    }

    /**
     * Test setId.
     */
    public function testSetId()
    {
        $type = new P4Cms_Content_Type;

        $tests = array(
            array(
                'label' => __LINE__ .': null',
                'id'    => null,
                'error' => true,
            ),
            array(
                'label' => __LINE__ .': numeric',
                'id'    => 123,
                'error' => false,
            ),
            array(
                'label' => __LINE__ .': number string',
                'id'    => '123',
                'error' => false,
            ),
            array(
                'label' => __LINE__ .': revision chars',
                'id'    => '/@#%%0',
                'error' => true,
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            try {
                $return = $type->setId($test['id']);
                if ($test['error']) {
                    $this->fail("$label - Unexpected success");
                }
            } catch (InvalidArgumentException $e) {
                if ($test['error']) {
                    $this->assertSame(
                        'Cannot set content-type id. Id contains invalid characters.',
                        $e->getMessage(),
                        "$label - expected error"
                    );
                } else {
                    $this->fail("$label - unexpected failure: ". $e->getMessage());
                }
            }

            if (!$test['error']) {
                $this->assertTrue(
                    $return instanceof P4Cms_Content_Type,
                    "$label - unexpected class from fluent interface: '"
                    . get_class($return) ."'"
                );
                $this->assertSame(
                    ($test['id'] === null ? null : (string) $test['id']),
                    $return->getId(),
                    "$label - Expected to get set id"
                );
            }
        }
    }

    /**
     * Test setElements.
     */
    public function testSetElements()
    {
        $type = new P4Cms_Content_Type;

        $sampleIni = <<<EOSINI
[test]
id = test
label = Test
validation.maxLength = 4
validation.minLength = 1
EOSINI;

        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'elements'  => null,
                'error'     => false,
                'expect'    => array(),
            ),
            array(
                'label'     => __LINE__ .': numeric',
                'elements'  => 123,
                'error'     => true,
                'expect'    => array(),
            ),
            array(
                'label'     => __LINE__ .': alphanumeric',
                'elements'  => 'abc123',
                'error'     => false,
                'expect'    => array(),
            ),
            array(
                'label'     => __LINE__ .': INI string',
                'elements'  => $sampleIni,
                'error'     => false,
                'expect'    => array(
                    'test' => array(
                        'id'            => 'test',
                        'label'         => 'Test',
                        'validation'    => array(
                            'maxLength' => '4',
                            'minLength' => '1',
                        )
                    ),
                ),
            ),
            array(
                'label'     => __LINE__ .': empty array',
                'elements'  => array(),
                'error'     => false,
                'expect'    => array(),
            ),
            array(
                'label'     => __LINE__ .': simple array',
                'elements'  => array('one', 2, '3three'),
                'error'     => false,
                'expect'    => array('one', '2', '3three'),
            ),
            array(
                'label'     => __LINE__ .': nested array',
                'elements'  => array(
                    'one'   => 1,
                    '2'     => array(
                        'two'  => 2,
                        'half' => 2.5,
                    ),
                    '3three' => array(
                        'validation' => array(
                            'maxLength' => 333,
                            'minLength' => 3,
                        ),
                        'label' => 'Three',
                        'id'    => '3three',
                    ),
                ),
                'error'     => false,
                'expect'    => array(
                    'one'   => '1',
                    '2'     => array(
                        'two'  => '2',
                        'half' => '2.5'
                    ),
                    '3three' => array(
                        'validation' => array(
                            'maxLength' => '333',
                            'minLength' => '3',
                        ),
                        'label' => 'Three',
                        'id'    => '3three'
                    ),
                ),
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            try {
                $return = $type->setElements($test['elements']);
                if ($test['error']) {
                    $this->fail("$label - Unexpected success");
                }
            } catch (InvalidArgumentException $e) {
                if ($test['error']) {
                    $this->assertSame(
                        'Cannot set elements. Elements must be given as an array, string or null.',
                        $e->getMessage(),
                        "$label - expected error"
                    );
                } else {
                    $this->fail("$label - unexpected failure: ". $e->getMessage());
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (Exception $e) {
                $this->fail(
                    "$label - unexpected exception (". get_class($e) .') '.  $e->getMessage()
                );
            }

            if (!$test['error']) {
                $this->assertTrue(
                    $return instanceof P4Cms_Content_Type,
                    "$label - unexpected class from fluent interface: '"
                    . get_class($return) ."'"
                );
                $this->assertSame(
                    $test['expect'],
                    $return->getElements(),
                    "$label - Expected elements after set"
                );
            }
        }
    }

    /**
     * Test setElementsFromIni.
     */
    public function testSetElementsFromIni()
    {
        $type = new P4Cms_Content_Type;

        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'elements'  => null,
                'error'     => true,
                'expect'    => null,
            ),
            array(
                'label'     => __LINE__ .': numeric',
                'elements'  => 123,
                'error'     => true,
                'expect'    => null,
            ),
            array(
                'label'     => __LINE__ .': alphanumeric',
                'elements'  => 'abc123',
                'error'     => false,
                'expect'    => 'abc123',
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            try {
                $return = $type->setElementsFromIni($test['elements']);
                if ($test['error']) {
                    $this->fail("$label - Unexpected success");
                }
            } catch (InvalidArgumentException $e) {
                if ($test['error']) {
                    $this->assertSame(
                        'Cannot set elements. Elements must be a string.',
                        $e->getMessage(),
                        "$label - expected error"
                    );
                } else {
                    $this->fail("$label - unexpected failure: ". $e->getMessage());
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (Exception $e) {
                $this->fail(
                    "$label - unexpected exception (". get_class($e) .') '.  $e->getMessage()
                );
            }

            if (!$test['error']) {
                $this->assertTrue(
                    $return instanceof P4Cms_Content_Type,
                    "$label - unexpected class from fluent interface: '"
                    . get_class($return) ."'"
                );
                $this->assertSame(
                    $test['expect'],
                    $return->getElementsAsIni(),
                    "$label - Expected elements after set"
                );
            }
        }
    }

    /**
     * Test getElement and getFormElement
     */
    public function testGetElementGetFormElement()
    {
        P4Cms_Content_Type::store(
            array(
                'id'        => 'test',
                'elements'  => <<<EOD
[id]
type = text

[test]
id = test
type = text
label = Test
validation.maxLength = 4
validation.minLength = 1

[test2]
id = test2
type = text
label = Test2

[test3]
id = test3
type = nonexistantelementtype
label = Test3
EOD
            )
        );

        $this->assertSame(
            array(),
            P4Cms_Content_Type::fetch('test')->getElement('non-existant'),
            'Expected matching result for made up element'
        );

        try {
            P4Cms_Content_Type::fetch('test')->getFormElement('non-existant');
            $this->fail('Expected requesting an invalid form element to throw');
        } catch (P4Cms_Content_Exception $e) {
        }

        $this->assertSame(
            array(
                'id'         => 'test',
                'type'       => 'text',
                'label'      => 'Test',
                'validation' => array(
                    'maxLength' => '4',
                    'minLength' => '1'
                )
            ),
            P4Cms_Content_Type::fetch('test')->getElement('test'),
            'Expected matching result for test element'
        );

        $this->assertSame(
            'test',
            P4Cms_Content_Type::fetch('test')->getFormElement('test')->getId(),
            'Expected matching result for test form element'
        );

        $this->assertSame(
            array(
                'id'         => 'test3',
                'type'       => 'text',
                'label'      => 'Test3',
                'display' => array(
                    'render' => false
                )
            ),
            P4Cms_Content_Type::fetch('test')->getElement('test3'),
            'Expected matching result for test3 element'
        );
    }

    /**
     * Test using a bad form definition
     */
    public function testBadElementDefinition()
    {

        P4Cms_Content_Type::store(
            array(
                'id'        => 'test',
                'elements'  => <<<EOD
[id]
type = text3131
EOD
            )
        );

        $this->assertSame(
            array(
                'id'    => array(
                    'type'  => 'text',
                    'display'   => array(
                        'render'    => false
                    )
                )
            ),
            P4Cms_Content_Type::fetch('test')->getElements(),
            'Expected element from bad definition'
        );
    }

    /**
     * Test getAddUri.
     */
    public function testGetAddUri()
    {
        $type = new P4Cms_Content_Type;
        $type->setId('testType');

        // confirm that the lack of a URI callback causes an exception
        try {
            $addUri = $type->getAddUri();
            $this->fail('Unexpected success calling getAddUri with no callback.');
        } catch (P4Cms_Content_Exception $e) {
            $this->assertSame(
                'Cannot get URI callback, no URI callback has been set.',
                $e->getMessage(),
                'Expected error calling getAddUri with no URI callback'
            );
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception getAddUri with no URI callback ('
                . get_class($e) .'): '. $e->getMessage()
            );
        }

        // enable the Types module to complete a successful getAddUri
        $module = P4Cms_Module::fetch('Types')->enable();
        $module->load();

        // try again now that URI callback should be set
        try {
            $addUri = $type->getAddUri();
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception calling getAddUri with test URI callback ('
                . get_class($e) .'): '. $e->getMessage()
            );
        }

        $this->assertSame(
            'testType/add/',
            $addUri,
            'Expected URL for default URI callback'
        );
    }

    /**
     * Test hasIcon.
     */
    public function testHasIcon()
    {
        // verify fresh type has no icon
        $type = new P4Cms_Content_Type;
        $this->assertFalse($type->hasIcon(), 'Expect no icon');

        // update the type to have an icon
        $type->setValue('icon', 'GIF89A!');
        $type->setFieldMetadata('icon', array("mimeType" => 'image/gif'));

        // verify type now has icon
        $this->assertTrue($type->hasIcon(), 'Expect no icon');
    }

    /**
     * Test installDefaultTypes.
     */
    public function testInstallDefaultTypes()
    {
        $query = P4_File_Query::create()->addFilespec('//depot/content-types/...');
        $files = P4_File::fetchAll($query);
        $this->assertSame(
            0,
            count($files),
            'Expect no content-types, initially'
        );

        $type = new P4Cms_Content_Type;

        // enable the Types module to complete a successful getAddUri
        $module = P4Cms_Module::fetch('Types')->enable();
        $module->load();

        P4Cms_Content_Type::installDefaultTypes();

        $files = P4_File::fetchAll($query);
        $filenames = array();
        foreach ($files as $file) {
            $filenames[] = basename($file->getFilespec());
        }

        $this->assertSame(
            array('testType'),
            $filenames,
            'Expected type records after installDefaultTypes'
        );
    }

    /**
     * Test hasValidElements.
     */
    public function testHasValidElements()
    {
        $type = new P4Cms_Content_Type;
        $this->assertTrue($type->hasValidElements(), 'Expect valid elements in fresh type');

        // set valid elements
        $elements = <<<EOE
[body]
id = "body"
label = "Body"
type = "text"
EOE;
        $type->setElementsFromIni($elements);
        $this->assertTrue($type->hasValidElements(), 'Expect valid elements');

        // set invalid elements
        $elements = <<<EOE
[_body]
id = "body"
label = "Body"
type = "text"
EOE;
        $type->setElementsFromIni($elements);
        $this->assertFalse($type->hasValidElements(), 'Expect invalid elements for invalid element id');

        // set an empty, invalid element, along with a good one
        $elements = <<<EOE
[body]
id = "body"
label = "Body"
type = "text"

[crash]
EOE;
        $type->setElementsFromIni($elements);
        $this->assertFalse($type->hasValidElements(), 'Expect valid elements for empty element section');
    }

    /**
     * Tests the basic accessors and mutators for:
     *  (get|set)Label
     *  (get|set)Group
     *  (get|set)Icon
     *  (get|set)Description
     */
    public function testAccessorsMutators()
    {
        $tests = array(
            array(
                'label' => __LINE__ . ' int',
                'value' => 10,
                'error' => true
            ),
            array(
                'label' => __LINE__ . ' float',
                'value' => 10,
                'error' => true
            ),
            array(
                'label' => __LINE__ . ' string',
                'value' => 'I am the very model of a modern major string',
                'error' => false
            ),
            array(
                'label' => __LINE__ . ' null',
                'value' => null,
                'error' => false
            )
        );
        $fields = array('Label', 'Group', 'Icon', 'Description');

        foreach ($tests as $test) {
            extract($test);

            foreach ($fields as $field) {
                try {
                    $type = new P4Cms_Content_Type;
                    $type->{'set'.$field}($value);

                    $this->assertFalse($error, $label. ' - Error should have occured');

                    $this->assertSame(
                        $value,
                        $type->{'get'.$field}(),
                        $label. ' - Expected matching value'
                    );
                } catch (InvalidArgumentException $e) {
                    $this->assertTrue($error, $label. ' - An error occurred but was not expected');
                }
            }
        }
    }

    /**
     * Test the fetch groups functionality
     */
    public function testFetchGroups()
    {
        $this->assertSame(
            array(),
            P4Cms_Content_Type::fetchGroups(),
            'Expected matching groups to start'
        );

        P4Cms_Content_Type::store(array('id' => 'a1', 'label' => 'a1', 'group' => 'group1'));
        P4Cms_Content_Type::store(array('id' => 'a2', 'label' => 'a2', 'group' => 'group1'));
        P4Cms_Content_Type::store(array('id' => 'a3', 'label' => 'a3', 'group' => 'group1'));
        P4Cms_Content_Type::store(array('id' => 'a',  'label' => 'a',  'group' => 'group3'));

        $groups = P4Cms_Content_Type::fetchGroups();
        $this->assertSame(
            array('group1', 'group3'),
            array_keys($groups),
            'Expected two groups'
        );
        $this->assertSame(
            array('a1', 'a2', 'a3'),
            $groups['group1']->invoke('getId'),
            'Expected matching entries in group1'
        );
        $this->assertSame(
            array('a'),
            $groups['group3']->invoke('getId'),
            'Expected matching entries in group3'
        );

        P4Cms_Content_Type::store(array('id' => 'b1', 'label' => 'b1', 'group' => 'group2'));
        P4Cms_Content_Type::store(array('id' => 'b2', 'label' => 'b2', 'group' => 'group2'));

        $groups = P4Cms_Content_Type::fetchGroups();
        $this->assertSame(
            array('group1', 'group2', 'group3'),
            array_keys($groups),
            'Expected three groups'
        );
        $this->assertSame(
            array('b1', 'b2'),
            $groups['group2']->invoke('getId'),
            'Expected matching entries in group2'
        );
    }

    /**
     * Test deleting a entry
     */
    public function testDelete()
    {
        $this->assertSame(
            array(),
            P4Cms_Content_Type::fetchAll()->invoke('getId'),
            'Expected matching entries to start'
        );

        P4Cms_Content_Type::store('a1');
        P4Cms_Content_Type::store('a2');

        $this->assertSame(
            array('a1', 'a2'),
            P4Cms_Content_Type::fetchAll()->invoke('getId'),
            'Expected two entries post save'
        );

        P4Cms_Content_Type::fetch('a1')->delete();

        $this->assertSame(
            array('a2'),
            P4Cms_Content_Type::fetchAll()->invoke('getId'),
            'Expected one entry post delete'
        );
    }

    /**
     * Test display decorators
     */
    public function testGetDisplayDecorators()
    {
        P4Cms_Content_Type::store(
            array(
                'id'        => 'test',
                'elements'  => <<<EOD
[id]
type = text

[file]
type = file
display.decorators[] = DtDdWrapper
EOD
            )
        );

        $type = P4Cms_Content_Type::fetch('test');
        $this->assertSame(
            array(
                "P4Cms_Form_Decorator_Value",
                "P4Cms_Form_Decorator_DtDdWrapper",
            ),
            array_keys($type->getDisplayDecorators('file')),
            'Expected matching decorators for file field'
        );

        $this->assertSame(
            array(
                "P4Cms_Form_Decorator_Value",
            ),
            array_keys($type->getDisplayDecorators('id')),
            'Expected matching decorators for id field'
        );
    }
}
