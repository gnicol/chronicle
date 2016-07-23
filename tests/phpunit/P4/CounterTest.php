<?php
/**
 * Test methods for the P4 Counter class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_CounterTest extends TestCase
{
    /**
     * Test fetchAll method
     */
    public function testFetchAll()
    {
        $entries = array(
            'test1' => 'value1', 
            'test2' => 'value2', 
            'test3' => 'value3', 
            'test4' => 'value4',
            'test5' => 'value5',
            'test6' => 'value6',
        );
        
        $startEntries = array_combine(
            P4_Counter::fetchAll()->invoke('getId'),
            P4_Counter::fetchAll()->invoke('getValue')   
        );
        
        $this->assertSame(
            1,
            count($startEntries),
            'Expected matching number of entries to start'
        );

        // prime the data
        foreach ($entries as $id => $value) {
            $counter = new P4_Counter;
            $counter->setId($id)->setValue($value);
        }
        
        // merge in and sort the startEntries
        $entries = array_merge($entries, $startEntries);
        ksort($entries);

        
        // run a fetch all and validate result
        $counters = P4_Counter::fetchAll();
        foreach ($counters as $counter) {
            $this->assertTrue(
                array_key_exists($counter->getId(), $entries),
                'Expected counter '.$counter->getId().' to exist in our entries array'
            );
            
            $this->assertSame(
                $entries[$counter->getId()],
                $counter->getValue(),
                'Expected matching counter value for entry '.$counter->getId()
            );
        }
        
        // Verify fetchAll with made up option works
        $this->assertSame(
            count($entries),
            count(P4_Counter::fetchAll(array('fooBar' => true))),
            'Expected fetch all with made up option to match'
        );

        // Verify full FETCH_MAXIMUM works
        $this->assertSame(
            array_slice(array_keys($entries), 0, 3),
            P4_Counter::fetchAll(array(P4_Counter::FETCH_MAXIMUM => '3'))->invoke('getId'),
            'Expected fetch all with Maximum to match'
        );
    }
    
    /**
     * Test calling setValue without an ID
     */
    public function testSetValueNoId()
    {
        try {
            $counter = new P4_Counter;
            $counter->setValue('test');

            $this->fail('unexpected success');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4_Exception $e) {
            $this->assertSame(
                "Cannot set value. No id has been set.",
                $e->getMessage(),
                'unexpected exception message'
            );
        } catch (Exception $e) {
            $this->fail(': unexpected exception ('. get_class($e) .') '. $e->getMessage());
        }
    }

    /**
     * Test the get value function
     */
    public function testGetSetValue()
    {
        $counter = new P4_Counter();
        $counter->setId('test')->setValue('testValue');
        
        $counter = P4_Counter::fetch('test');
        
        $this->assertSame(
            'test',
            $counter->getId(),
            'Expected matching Id'
        );
        
        $this->assertSame(
            'testValue',
            $counter->getValue(),
            'Expected matching value'
        );
        
        P4_Counter::fetch('test')->setValue('testValue2');

        $this->assertSame(
            'testValue2',
            $counter->getValue(),
            'Expected matching value after outside modification'
        );
        
        $counter = new P4_Counter;
        $this->assertSame(
            null,
            $counter->getValue(),
            'Expected no-id counter value to match'
        );
        
        $counter->setId('newCounter');
        $this->assertSame(
            null,
            $counter->getValue(),
            'Expected non-existent counter value to match'
        );
    }
    
    /**
     * Test a good call to fetch
     */
    public function testFetch()
    {
        $counter = new P4_Counter();
        $counter->setId('test')->setValue('testValue');
        
        $counter = P4_Counter::fetch('test');
        
        $this->assertSame(
            'test',
            $counter->getId(),
            'Expected matching Id'
        );
        
        $this->assertSame(
            'testValue',
            $counter->getValue(),
            'Expected matching value'
        );
    }
    
    /**
     * Test fetch of non-existent record
     */
    public function testNonExistentFetch()
    {
        // ensure fetch fails for a non-existant counter.
        try {
            P4_Counter::fetch('alskdfj2134');
            $this->fail("Fetch should fail for a non-existant counter.");
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4_Exception $e) {
            $this->assertSame(
                "Cannot fetch counter. Counter does not exist.",
                $e->getMessage(),
                'unexpected exception message'
            );
        } catch (Exception $e) {
            $this->fail(': unexpected exception ('. get_class($e) .') '. $e->getMessage());
        }
    }

    /**
     * Test fetch of bad id record
     */
    public function testBadIdFetch()
    {
        // ensure fetch fails for a bad Id.
        try {
            P4_Counter::fetch('te/st');
            $this->fail("Fetch should fail for a il-formated counter.");
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                "Must supply a valid id to fetch.",
                $e->getMessage(),
                'unexpected exception message'
            );
        } catch (Exception $e) {
            $this->fail(': unexpected exception ('. get_class($e) .') '. $e->getMessage());
        }
    }
    
    /**
     * test bad values for setId
     */
    public function testBadSetId()
    {
        $tests = array(
            array(
                'title' => __LINE__." leading minus",
                'value' => '-test'
            ),
            array(
                'title' => __LINE__." forward slash",
                'value' => 'te/st'
            ),
            array(
                'title' => __LINE__." all numeric",
                'value' => '1234'
            ),            
        );   
        
        foreach ($tests as $test) {
            // ensure fetch fails for a non-existant counter.
            try {
                $counter = new P4_Counter;
                $counter->setId($test['value']);
                $this->fail($test['title'].': unexpected success');
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (InvalidArgumentException $e) {
                $this->assertSame(
                    "Cannot set id. Id is invalid.",
                    $e->getMessage(),
                    $test['title'].': unexpected exception message'
                );
            } catch (Exception $e) {
                $this->fail($test['title'].': : unexpected exception ('. get_class($e) .') '. $e->getMessage());
            }
        }
    }
    
    /**
     * Test exists
     */
    public function testExists()
    {
        // ensure id-exists returns false for ill formatted counter
        $this->assertFalse(P4_Counter::exists("-alsdjf"), "Leading - counter id should not exist.");
        $this->assertFalse(P4_Counter::exists("als/djf"), "Forward slash counter id should not exist.");
        
        // ensure id-exists returns false for non-existant counter
        $this->assertFalse(P4_Counter::exists("alsdjf"), "Given counter id should not exist.");

        // create counter and ensure it exists.
        $group = new P4_Counter;
        $group->setId('test')
              ->setValue('tester');
        $this->assertTrue(P4_Counter::exists("test"), "Given counter id should exist.");
    }
    
    /**
     * Test the increment function
     */
    public function testIncrement()
    {
        $counter = new P4_Counter;
        
        // Test counter that already exists, starting at 0
        $counter->setId('existing')->setValue(0);
        $this->assertSame(
            "1",
            $counter->increment(),
            'Expected matching value when starting at 0'
        );
        $this->assertSame(
            "1",
            P4_Counter::fetch('existing')->getValue(),
            'Expected matching value when starting at 0 on independent fetch'
        );
        $this->assertSame(
            "2",
            $counter->increment(),
            'Expected matching value after second increment'
        );
        $this->assertSame(
            "2",
            P4_Counter::fetch('existing')->getValue(),
            'Expected matching value after second increment on independent fetch'
        );

        // Test counter that already exists starting at 1        
        $counter->setValue(1);
        $this->assertSame(
            "2",
            $counter->increment(),
            'Expected matching value when starting at 2'
        );
        
        // Test increment will create a counter if it doesn't exist
        $counter = new P4_Counter;
        $counter->setId('newCounter');
        $this->assertSame(
            "1",
            $counter->increment(),
            'Expected matching value for new counter'
        );
        $this->assertSame(
            "1",
            P4_Counter::fetch('newCounter')->getValue(),
            'Expected matching value for new counter on independent fetch'
        );
    }
    
    /**
     * Test the increment function with bad starting value
     */
    public function testBadIncrement()
    {
        // Test counter that already exists, starting at 'bad'
        try {
            $counter = new P4_Counter;
            $counter->setId('existing')->setValue('bad');
            $counter->increment();
            $this->fail("Increment should fail for a il-valued counter.");
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4_Exception $e) {
            $this->assertSame(
                "Command failed: Can't increment counter 'existing' - value is not numeric.",
                $e->getMessage(),
                'unexpected exception message'
            );
        } catch (Exception $e) {
            $this->fail(': unexpected exception ('. get_class($e) .') '. $e->getMessage());
        }   
    }
    
    /**
     * Test the delete function
     */
    public function testDelete()
    {
        $counter = new P4_Counter;
        $counter->setId('test')->setValue('testValue');
        
        $this->assertTrue(P4_Counter::exists('test'), 'expected test entry to exist');
        
        $counter->delete('test');
        $this->assertFalse(P4_Counter::exists('test'), 'expected test entry was deleted');
        
        $counters = P4_Counter::fetchAll();
        $this->assertFalse(
            in_array('test', $counters->invoke('getId')),
            'expected deleted entry would not be returned by fetchall'
        );
    }
    
    /**
     * Test the delete function with non-existent id
     */
    public function testMissingIdDelete()
    {
        try {
            $counter = new P4_Counter;
            $counter->setId('missing')->delete();
            $this->fail("Delete should fail for a missing counter.");
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4_Exception $e) {
            $this->assertSame(
                "Cannot delete counter. Counter does not exist.",
                $e->getMessage(),
                'unexpected exception message'
            );
        } catch (Exception $e) {
            $this->fail(': unexpected exception ('. get_class($e) .') '. $e->getMessage());
        }   
    }
    
    /**
     * Test the delete function with no id
     */
    public function testNoIdDelete()
    {
        try {
            $counter = new P4_Counter;
            $counter->delete();
            $this->fail("Delete should fail when no id is set.");
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4_Exception $e) {
            $this->assertSame(
                "Cannot delete. No id has been set.",
                $e->getMessage(),
                'unexpected exception message'
            );
        } catch (Exception $e) {
            $this->fail(': unexpected exception ('. get_class($e) .') '. $e->getMessage());
        }   
    }

    /**
     * Test the force option.
     */
    public function testForce()
    {
        // ensure 'security' counter protected.
        $counter = new P4_Counter;
        $counter->setId('security');
        try {
            $counter->setValue(1);
            $this->fail("Expected exception");
        } catch (P4_Connection_CommandException $e) {
            $this->assertTrue(true);
        }

        // set a protected counter.
        $counter->setValue(1, true);
        $this->assertSame(1, (int) $counter->getValue(), "Expected security level 1");

        // now try to delete it.
        try {
            $counter->delete();
            $this->fail("Expected exception");
        } catch (P4_Connection_CommandException $e) {
            $this->assertTrue(true);
        }

        // delete with force.
        $counter->delete(true);
        $this->assertFalse(
            P4_Counter::exists('security'),
            "Expected 'security' counter to be deleted."
        );
    }
}
