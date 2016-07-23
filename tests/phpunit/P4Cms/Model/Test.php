<?php
/**
 * Test methods for the model package.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Model_Test extends TestCase
{
    /**
     * Create dummy model records for testing.
     */
    public function setUp()
    {
        $model = new P4Cms_Model_Implementation(
            array(
                'key' => 'valid-key',
                'foo' => 1,
                'bar' => 2,
                'baz' => 3
            )
        );
        $model->save();
        $model = new P4Cms_Model_Implementation(
            array(
                'key' => 'another-valid-key',
                'foo' => 5,
                'bar' => 6,
                'baz' => 7
            )
        );
        $model->save();
    }

    /**
     * Remove dummy model records.
     */
    public function tearDown()
    {
        P4Cms_Model_Implementation::clearRecords();
    }

    /**
     * Verify that model abstract fullfills interface contract.
     */
    public function testModelAbstract()
    {
        $model = new P4Cms_Model_Implementation;
        $this->assertEquals(
            $model->getFields(),
            array('key', 'foo', 'bar', 'baz', 'noWrite'),
            'Expected fields'
        );
        $this->assertTrue($model->hasField('key'), 'Expected field key');
        $this->assertTrue($model->hasField('foo'), 'Expected field foo');
        $this->assertTrue($model->hasField('bar'), 'Expected field bar');
        $this->assertTrue($model->hasField('baz'), 'Expected field baz');
        $this->assertTrue($model->hasField('noWrite'), 'Expected field noWrite');
        $this->assertFalse($model->hasField('bof'), 'Unexpected field bof');

        $model->setValue('foo', 'test-foo');
        $this->assertSame($model->getValue('foo'), 'test-foo', 'Expected foo value');

        $model->bar = 'test-bar';
        $this->assertSame($model->bar, 'test-bar', 'Expected bar value');

        $values = array(
            'key'       => null,
            'foo'       => 'test-foo',
            'bar'       => 'test-bar',
            'baz'       => 'test-baz',
            'noWrite'   => 'test-ignore'
        );
        $expected = array(
            'key'       => null,
            'foo'       => 'test-foo',
            'bar'       => 'test-bar',
            'baz'       => 'test-baz',
            'noWrite'   => 'test-bar/test-baz'
        );
        $model->setValues($values);
        $this->assertSame(
            $expected,
            $model->getValues(),
            'Expected values after setValues'
        );
        $this->assertTrue(
            P4Cms_Model_Implementation::fetch('valid-key') instanceof P4Cms_Model,
            'Expected key instance type'
        );

        // cover passing non-array values
        try {
            $model->setValues(null);
            $this->fail('Unexpected success setting non-array values');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(
                'Cannot set values. Values must be an array.',
                $e->getMessage(),
                'Expected exception message.'
            );
        } catch (Exception $e) {
            $this->fail(
                "$label: Unexpected Exception (" . get_class($e) . '): ' . $e->getMessage()
            );
        }

        // cover creating an instance from an array
        $model = $model->fromArray($values);
        $this->assertSame($expected, $model->getValues(), 'Expected values for instance from array');

        // cover unsetting a field
        $modified = $expected;
        $modified['baz'] = null;
        $modified['noWrite'] = 'test-bar/';
        unset($model->baz);
        $this->assertSame($modified, $model->getValues(), 'Expected values after unset');

        // cover creating a new instance
        $model = P4Cms_Model_Implementation::create($values);
        $this->assertSame($expected, $model->getValues(), 'Expected values for instance from create');

        // cover setting values, bypassing mutators
        $model->setRawValues($modified);
        $this->assertSame($model->getValues(), $modified, 'Expected values after set raw');

        try {
            P4Cms_Model_Implementation::fetch('invalid-key');
            $this->fail('Expected bogus key lookup to fail');
        } catch (P4Cms_Model_NotFoundException $e) {
            $this->assertSame("Can't find matching model.", $e->getMessage(), 'Expected error message');
        } catch (Exception $e) {
            $this->fail(__LINE__ .' - Unexpected exception: '. $e->getMessage());
        }
        $this->assertTrue(
            P4Cms_Model_Implementation::fetchAll() instanceof P4Cms_Model_Iterator,
            'Expected iterator type'
        );
        $this->assertSame($model->someMember, 'some-value', 'Expected member value');

        $model = new P4Cms_Model_Implementation(
            array('foo' => 1, 'bar' => 2, 'baz' => 3)
        );
        $this->assertEquals($model->foo, 1, 'Expected model foo value');
        $this->assertEquals($model->bar, 2, 'Expected model bar value');
        $this->assertEquals($model->baz, 3, 'Expected model baz value');
    }

    /**
     * Test setValue on read only field
     *
     * @expectedException P4Cms_Model_Exception
     */
    public function testSetValueOnReadOnly()
    {
        $model = new P4Cms_Model_Implementation;

        $this->assertTrue(
            $model->isReadOnlyField('noWrite'),
            'Expected noWrite field to be read only'
        );

        $model->setValue('noWrite', 'test');
    }

    /**
     * Test iterator population and traversal.
     */
    public function testModelIterator()
    {
        $iterator = new P4Cms_Model_Iterator;
        $this->assertEquals(count($iterator), 0, 'Expect fresh iterator to have no items.');

        $iterator[] = new P4Cms_Model_Implementation;
        $this->assertEquals(count($iterator), 1, __LINE__ .' - Expected count');
        $this->assertTrue(
            $iterator[0] instanceof P4Cms_Model_Implementation,
            'Expected iterator item object type'
        );

        $iterator[] = new P4Cms_Model_Implementation;
        $iterator[] = new P4Cms_Model_Implementation;
        $this->assertEquals(count($iterator), 3, __LINE__ .' - Expected count');

        $count = 0;
        foreach ($iterator as $model) {
            $count++;
        }
        $this->assertEquals($count, 3, 'Expected foreach count');

        $model = new P4Cms_Model_Implementation;
        $model->foo = 'bar';
        $iterator['arbitrary'] = $model;
        $this->assertEquals($iterator['arbitrary']->foo, 'bar', 'Expected arbitrary item value');
        $this->assertEquals($iterator[1]->foo, null, 'Expected explicit item value');

        $iterator->rewind();
        $this->assertSame($iterator->key(), 0, 'Expected position 0 key');

        $iterator->seek(3);
        $this->assertSame($iterator->key(), 'arbitrary', 'Expected position 3 key');

        $iterator->next();
        $this->assertFalse($iterator->next(), 'Expected end-of-iterator state');
    }

    /**
     * Test rejection of invalid elements in iterator.
     */
    public function testModelIteratorValidation()
    {
        try {
            $iterator = new P4Cms_Model_Iterator(array('foo'));
            $this->fail('Expect iterator creation with array to fail');
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(
                'Models array contains one or more invalid elements.',
                $e->getMessage(),
                'Expected error message'
            );
        } catch (Exception $e) {
            $this->fail(__LINE__ .' - Unexpected exception: '. $e->getMessage());
        }

        $iterator = new P4Cms_Model_Iterator;
        try {
            $iterator[] = 'foo';
            $this->fail('Expect treating iterator as an array to fail');
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(
                'Invalid model supplied.',
                $e->getMessage(),
                'Expected error message'
            );
        } catch (Exception $e) {
            $this->fail(__LINE__ .' - Unexpected exception: '. $e->getMessage());
        }
    }

    /**
     * Test the key exists method.
     */
    public function testIdExists()
    {
        $this->assertTrue(
            P4Cms_Model_Implementation::exists('valid-key'),
            'Expect valid key to exist'
        );
        $this->assertFalse(
            P4Cms_Model_Implementation::exists('invalid-key'),
            'Expect invalid key to not exist'
        );
    }

    /**
     * Ensure that model uses custom field accessor.
     */
    public function testCustomAccessor()
    {
        $model = new P4Cms_Model_Implementation(array('foo' => 'test'));
        $this->assertSame('test', $model->foo, 'Expected attribute value');
        $this->assertSame('test', $model->getFoo(), 'Expected named accessor value.');
        $this->assertSame('test',  $model->getValue('foo'), 'Expected parameter accessor value.');

        $model->setFooPrefix('test');
        $this->assertSame('testtest', $model->foo, 'Expected prefixed attribute value');
        $this->assertSame('testtest', $model->getFoo(), 'Expected prefixed accessor value.');
        $this->assertSame('testtest', $model->getValue('foo'), 'Expected prefixed parameter accessor value.');
    }

    /**
     * Ensure that model provides virtual field accessors.
     */
    public function testVirtualAccessors()
    {
        $model = new P4Cms_Model_Implementation(
            array(
                'bar' => 'bof',
                'baz' => 'baf'
            )
        );
        $this->assertSame($model->getBar(), 'bof', 'Expected bar value');
        $this->assertSame($model->getBaz(), 'baf', 'Expected baz value');
    }

    /**
     * Ensure that model uses custom field mutator.
     */
    public function testCustomMutator()
    {
        $model = new P4Cms_Model_Implementation;
        $model->baz = 'test1';
        $this->assertTrue($model->wasBazSet(), 'Expect attribute baz to be set');
        $this->assertSame($model->baz, 'test1', 'Expected attribute baz value');
        $model->clearBazSetFlag();

        $model->setBaz('test2');
        $this->assertTrue($model->wasBazSet(), 'Expect accessor baz to be set');
        $this->assertSame($model->baz, 'test2', 'Expected accessor baz value');
        $model->clearBazSetFlag();

        $model->setValue('baz', 'test3');
        $this->assertTrue($model->wasBazSet(), 'Expect parameter baz to be set');
        $this->assertSame($model->baz, 'test3', 'Expected parameter baz value');
    }

    /**
     * Ensure that model provides virtual field mutators.
     */
    public function testVirtualMutators()
    {
        $model = new P4Cms_Model_Implementation;
        $model->setFoo('test1');
        $model->setBar('test2');
        $this->assertSame($model->foo, 'test1', 'Expected foo value');
        $this->assertSame($model->bar, 'test2', 'Expected bar value');
    }

    /**
     * Test behavior of models with no fixed fields.
     */
    public function testAddingFields()
    {
        $model = new P4Cms_Model_Implementation;
        $this->assertFalse($model->hasField('anyFieldName'), "Did not expect field to exist.");
        $this->assertTrue(count($model->getFields()) == 5, "Expected five fields.");

        // test effect of setting 'someFieldName'.
        $model->setValue('someFieldName', 'value');
        $this->assertTrue($model->hasField('someFieldName'), "Expected field to exist.");
        $this->assertTrue(count($model->getFields()) == 6, "Expected six fields.");

        // test effect of setting 'someOtherFieldName'.
        $model->setValue('someOtherFieldName', 'value');
        $this->assertTrue($model->hasField('someOtherFieldName'), "Expected field to exist.");
        $this->assertTrue(count($model->getFields()) == 7, "Expected seven fields.");

        // try clearing a field.
        unset($model->someOtherFieldName);
        $this->assertFalse($model->hasField('someOtherFieldName'), "Expected field to not exist.");
        $this->assertTrue(count($model->getFields()) == 6, "Expected six fields.");
        
        // try clearing via unset method.
        $model->unsetValue('someFieldName');
        $this->assertFalse($model->hasField('someFieldName'), "Expected field to not exist.");
        $this->assertTrue(count($model->getFields()) == 5, "Expected five fields.");        
    }

    /**
     * Test setting public properties
     */
    public function testSettingPublicProperties()
    {
        $model = new P4Cms_Model_Implementation;
        
        // should not be a field.
        $model->notAField = 'not a field';
        $this->assertSame($model->notAField, 'not a field');
        $this->assertTrue(array_key_exists('notAField', get_object_vars($model)));
        $this->assertFalse($model->hasField('notAField'));
        
        // should be a field.
        $model->foo = 'is a field';
        $this->assertSame($model->foo, 'is a field');
        $this->assertTrue($model->hasField('foo'));
        $this->assertFalse(array_key_exists('foo', get_object_vars($model)));
    }    
}
