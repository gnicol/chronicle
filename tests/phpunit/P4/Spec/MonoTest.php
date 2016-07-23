<?php
/**
 * This is a test thoroughly exercises the SpecAbstract via the SpecMono class.
 * It is used to thoroughly exercise the base spec functionality so latter implementors
 * can focus on testing only their own additions/modifications.
 *
 * The actual spec type represented by SpecMono is of no importance and should not be considered
 * tested in this context.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Spec_MonoTest extends TestCase
{
    /**
     * Setup Mono test object
     */
    public function setUp()
    {
        // disable mutators by default
        P4_Spec_MonoMock::setProtectedStaticVar('_mutators',    array());
        P4_Spec_MonoMock::setProtectedStaticVar('_accessors',   array());
        P4_Spec_MonoMock::setProtectedStaticVar('_specType',    'typemap');

        parent::setUp();
    }

    /**
     * Constructor Test
     */
    public function testConstructor()
    {
        // Construct object with no passed values
        $mono = new P4_Spec_MonoMock;

        $expected = array (
            'TypeMap' => null
        );

        $this->assertSame(
            $expected,
            $mono->getValues(),
            'Expected starting fields to match'
        );
    }

    /**
     * Test retrieval of the spec definition
     */
    public function testGetSpecDefinition()
    {
        $specDef = P4_Spec_Definition::fetch('typemap');

        $this->assertSame(
            $specDef->getType(),
            'typemap',
            'Expected spec definition type to match'
        );

        $expected = array (
            'TypeMap' => array (
                'code' => '601',
                'dataType' => 'wlist',
                'displayLength' => '64',
                'fieldType' => 'default',
                'wordCount' => '2',
            ),
        );

        $this->assertSame(
            $expected,
            $specDef->getFields(),
            'Expected fields to match'
        );
    }

    /**
     * Test the has field function.
     */
    public function testHasField()
    {
        $tests = array (
            array (
                'label' => __LINE__ . " Empty String",
                'field' => '',
                'error' => true
            ),
            array (
                'label' => __LINE__ . " null",
                'field' => null,
                'error' => true
            ),
            array (
                'label' => __LINE__ . " bool",
                'field' => true,
                'error' => true
            ),
            array (
                'label' => __LINE__ . " int",
                'field' => 10,
                'error' => true
            ),
            array (
                'label' => __LINE__ . " float",
                'field' => 10.10,
                'error' => true
            ),
            array (
                'label' => __LINE__ . " bad field name",
                'field' => 'badField',
                'error' => true
            ),
            array (
                'label' => __LINE__ . " incorrect case",
                'field' => 'typeMap',
                'error' => true
            ),
            array (
                'label' => __LINE__ . " known good field",
                'field' => 'TypeMap',
                'error' => false
            ),
        );

        foreach ($tests as $test) {
            $mono = new P4_Spec_MonoMock;

            $result = $mono->hasField($test['field']);

            if ($test['error']) {
                $this->assertFalse($result, 'Unexpected false: '. $test['label']);
            } else {
                $this->assertTrue($result, 'Unexpected true: '. $test['label']);
            }
        }
    }

    /**
     * Test get/setFields
     */
    public function testGetSetValues()
    {
        $mono = new P4_Spec_MonoMock;

        $expected = array (
            'TypeMap' => array('blah //...','etc //test/...','oneMore "//test with space/..."')
        );

        $mono = new P4_Spec_MonoMock;
        $mono->setValues($expected);

        $this->assertSame(
            $expected,
            $mono->getValues(),
            'Expected passed fields to take'
        );
    }

    /**
     * Exercise get/set values with combinations of mutator/accessor
     */
    public function testGetSetValuesWithMutatorAccessor()
    {
        // Enable mutator and accessor and verify fields passed are affected
        P4_Spec_MonoMock::setProtectedStaticVar('_mutators',   array('TypeMap' => 'setTypeMapRemoveA'));
        P4_Spec_MonoMock::setProtectedStaticVar('_accessors',  array('TypeMap' => 'getTypeMapAppendA'));

        $raw = array (
            'TypeMap' => array('blah //...','etc //test/...','oneMore "//test with space/..."')
        );
        $mutated = array (
            'TypeMap' => array('blah //...A','etc //test/...A','oneMore "//test with space/..."A')
        );

        $mono = new P4_Spec_MonoMock;

        $mono->setValues($mutated);

        $this->assertSame(
            $raw,
            $mono->callProtectedFunc('_getValues'),
            'Expected _getValues to match unmodified version'
        );

        $this->assertSame(
            $mutated,
            $mono->getValues(),
            'Expected getValues to match mutated version'
        );


        // Enable mutator only and verify fields passed are affected
        P4_Spec_MonoMock::setProtectedStaticVar('_mutators',   array('TypeMap' => 'setTypeMapRemoveA'));
        P4_Spec_MonoMock::setProtectedStaticVar('_accessors',  array());

        $mono = new P4_Spec_MonoMock;

        $mono->setValues($mutated);

        $this->assertSame(
            $raw,
            $mono->callProtectedFunc('_getValues'),
            'Expected _getValues to match unmodified version'
        );

        $this->assertSame(
            $raw,
            $mono->getValues(),
            'Expected getValues to match raw version'
        );
    }

    /**
     * Test that setValues ignores exceptions.
     */
    public function testSetValuesIgnoresErrors()
    {
        $mono = new P4_Spec_MonoMock;

        $values = $mono->getValues();
        $values['JunkEntry'] = 'a junk entry';

        // invalid field would normally throw; ensure it doesn't here.
        try {
            $mono->setValues($values);

            $this->assertSame(
                array('TypeMap' => $values['TypeMap']),
                $mono->getValues(),
                'Expected values to match'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected Exception ('. get_class($e) .'): '. $e->getMessage());
        }
    }

    /**
     * Test set raw values
     */
    public function testSetRawValues()
    {
        // Enable mutator and accessor and verify setRawValues is unaffected
        P4_Spec_MonoMock::setProtectedStaticVar('_mutators',   array('TypeMap' => 'setTypeMapRemoveA'));
        P4_Spec_MonoMock::setProtectedStaticVar('_accessors',  array('TypeMap' => 'getTypeMapAppendA'));

        $raw = array (
            'TypeMap' => array('blah //...','etc //test/...','oneMore "//test with space/..."')
        );
        $mutated = array (
            'TypeMap' => array('blah //...A','etc //test/...A','oneMore "//test with space/..."A')
        );

        $mono = new P4_Spec_MonoMock;

        $mono->callProtectedFunc('_setValues', array($raw));

        $this->assertSame(
            $raw,
            $mono->callProtectedFunc('_getValues'),
            'Expected _getValues to match unmodified version'
        );

        $this->assertSame(
            $mutated,
            $mono->getValues(),
            'Expected getValues to match mutated version'
        );
    }

    /**
     * Test get/set Value
     */
    public function testGetSetValue()
    {
        $mono = new P4_Spec_MonoMock;

        $expected = array('blah //...','etc //test/...','oneMore "//test with space/..."');

        // Verify get value reflects setValues input
        $mono = new P4_Spec_MonoMock;
        $mono->setValues(array('TypeMap' => $expected));

        $this->assertSame(
            $expected,
            $mono->getValue('TypeMap'),
            'Expected setValues to take'
        );

        // Verify get value reflects set value input
        $mono = new P4_Spec_MonoMock;
        $mono->setValue('TypeMap', $expected);

        $this->assertSame(
            $expected,
            $mono->getValue('TypeMap'),
            'Expected setValue to take'
        );

        // Verify get values reflects set value input
        $this->assertSame(
            array('TypeMap' => $expected),
            $mono->getValues(),
            'Expected setValue to affect getValues'
        );
    }

    /**
     * Test get/set Value with mutator/accessor
     */
    public function testGetSetValueWithMutatorAccessor()
    {
        // Enable mutator and accessor and verify fields passed are affected
        P4_Spec_MonoMock::setProtectedStaticVar('_mutators',   array('TypeMap' => 'setTypeMapRemoveA'));
        P4_Spec_MonoMock::setProtectedStaticVar('_accessors',  array('TypeMap' => 'getTypeMapAppendA'));

        $raw     = array('blah //...','etc //test/...','oneMore "//test with space/..."');
        $mutated = array('blah //...A','etc //test/...A','oneMore "//test with space/..."A');


        $mono = new P4_Spec_MonoMock;

        $mono->setValue('TypeMap', $mutated);

        $this->assertSame(
            $raw,
            $mono->callProtectedFunc('_getValue', 'TypeMap'),
            'Expected _getValue to match unmodified version'
        );

        $this->assertSame(
            $mutated,
            $mono->getValue('TypeMap'),
            'Expected getValues to match mutated version'
        );


        // Enable mutator only and verify fields passed are affected
        P4_Spec_MonoMock::setProtectedStaticVar('_mutators',   array('TypeMap' => 'setTypeMapRemoveA'));
        P4_Spec_MonoMock::setProtectedStaticVar('_accessors',  array());

        $mono = new P4_Spec_MonoMock;

        $mono->setValue('TypeMap', $mutated);

        $this->assertSame(
            $raw,
            $mono->callProtectedFunc('_getValue', 'TypeMap'),
            'Expected _getValue to match unmodified version'
        );

        $this->assertSame(
            $raw,
            $mono->getValue('TypeMap'),
            'Expected getValue to match raw version'
        );
    }

    /**
     * Test getting a bad field fails
     */
    public function testGetValueBadField()
    {
        $mono = new P4_Spec_MonoMock;

        $this->assertFalse(
            $mono->hasField('BadFieldName'),
            'Expected BadFieldName field would not exist'
        );

        try {
            $mono->getValue('BadFieldName');

            $this->fail('Expected get value of BadFieldName would fail');
        } catch (P4_Spec_Exception $e) {
            $this->assertSame(
                "Can't get the value of a non-existant field.",
                $e->getMessage(),
                'Unexpected message in exception'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected Exception ('. get_class($e) .'): '. $e->getMessage());
        }
    }

    /**
     * Test setting a bad field fails
     */
    public function testSetValueBadField()
    {
        $mono = new P4_Spec_MonoMock;

        $this->assertFalse(
            $mono->hasField('BadFieldName'),
            'Expected BadFieldName field would not exist'
        );

        try {
            $mono->setValue('BadFieldName', 'blah');

            $this->fail('Expected set value of BadFieldName would fail');
        } catch (P4_Spec_Exception $e) {
            $this->assertSame(
                "Can't set the value of a non-existant field.",
                $e->getMessage(),
                'Unexpected message in exception'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected Exception ('. get_class($e) .'): '. $e->getMessage());
        }
    }

    /**
     * Test save.
     */
    public function testSave()
    {
        $values = array (
            'TypeMap' => array('ctext //...','xtext //test/...','xbinary "//test with space/..."')
        );

        $mono = new P4_Spec_MonoMock;

        $this->assertNotSame(
            $values,
            $mono->getValues(),
            'Expected mono starting values to be different'
        );

        $mono->setValues($values);
        $mono->save();

        $this->assertSame(
            $values,
            $mono->getValues(),
            'Expected updated values to match'
        );

        // Get a fresh instance to verify it is also ok
        $mono = P4_Spec_MonoMock::fetch();

        $this->assertSame(
            $values,
            $mono->getValues(),
            'Expected updated values to match in new instance'
        );

        // ensure new in-memory objects are still empty.
        $mono = new P4_Spec_MonoMock;
        $this->assertSame(
            array('TypeMap' => null),
            $mono->getValues(),
            'Expected updated values to match in new instance'
        );
    }

    /**
     * Test bad _specType values cause exception
     */
    public function testSpecTypeBad()
    {
        $tests = array(
            __LINE__.' null'            => null,
            __LINE__.' empty string'    => '',
            __LINE__.' spaces'          => '  ',
            __LINE__.' tabs'            => "\t"
        );

        foreach ($tests as $title => $value) {
            P4_Spec_MonoMock::setProtectedStaticVar('_specType', $value);

            $mono = new P4_Spec_MonoMock;

            try {
                $mono->getSpecDefinition();

                $this->fail($title.': Expected Exception');
            } catch (P4_Spec_Exception $e) {
                $this->assertSame(
                    'No type is defined for this specification.',
                    $e->getMessage(),
                    $title.': Unexpected message in exception'
                );
            } catch (Exception $e) {
                $this->fail($title.': Unexpected Exception ('. get_class($e) .'): '. $e->getMessage());
            }
        }
    }

    /**
     * Test get default value when no default is present
     */
    public function testGetDefaultValueNoDefault()
    {
        $mono = new P4_Spec_MonoMock;

        $this->assertSame(
            null,
            $mono->callProtectedFunc('_getDefaultValue', 'TypeMap'),
            'Expected default typemap value to match'
        );
    }

    /**
     * Test _setValue are defensive for bad fields
     */
    public function testProtectedSetValueDefensive()
    {
        $mono = new P4_Spec_MonoMock;

        $this->assertFalse(
            $mono->hasField('BadFieldName'),
            'Expected BadFieldName field would not exist'
        );

        try {
            $mono->callProtectedFunc('_setValue', array('BadFieldName', 'blah'));

            $this->fail('Expected _setValue of BadFieldName would fail');
        } catch (P4_Spec_Exception $e) {
            $this->assertSame(
                "Can't set the value of a non-existant field.",
                $e->getMessage(),
                'Unexpected message in exception'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected Exception ('. get_class($e) .'): '. $e->getMessage());
        }
    }

    /**
     * Test _getValue are defensive for bad fields
     */
    public function testProtectedGetValueDefensive()
    {
        $mono = new P4_Spec_MonoMock;

        $this->assertFalse(
            $mono->hasField('BadFieldName'),
            'Expected BadFieldName field would not exist'
        );

        try {
            $mono->callProtectedFunc('_getValue', array('BadFieldName', 'blah'));

            $this->fail('Expected _getValue of BadFieldName would fail');
        } catch (P4_Spec_Exception $e) {
            $this->assertSame(
                "Can't get the value of a non-existant field.",
                $e->getMessage(),
                'Unexpected message in exception'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected Exception ('. get_class($e) .'): '. $e->getMessage());
        }
    }
}
