<?php
/**
 * Test methods for the P4 Environment class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_EnvironmentTest extends TestCase
{
    /**
     * Test getArgMax method
     */
    public function testGetArgMax()
    {
        $argMax = P4_Environment::getArgMax();
        $this->assertTrue(isset($argMax), 'Expect argMax to be set');
        $this->assertTrue(is_integer($argMax), 'Expect argMax to be an integer');
        $this->assertTrue($argMax >= 250, 'Expect argMax to be larger than 250 bytes');
    }

    /**
     * test passing an invalid callback to addShutdownCallback
     */
    public function testAddingBadCallback()
    {
        try {
            P4_Environment::addShutdownCallback('bogus');
            $this->fail('Unexpected success adding a bad callback');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(
                'Cannot add shutdown callback. Given callback is not callable.',
                $e->getMessage(),
                'Expected exception message'
            );
        } catch (Exception $e) {
            $this->fail(
                "$label: Unexpected Exception (" . get_class($e) . '): ' . $e->getMessage()
            );
        }
    }

    /**
     * test passing an array of invalid callbacks to addShutdownCallback
     */
    public function testAddingBadCallbacks()
    {
        try {
            P4_Environment::setShutdownCallbacks(array('bogus'));
            $this->fail('Unexpected success adding bad callbacks');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(
                'Cannot add shutdown callback. Given callback is not callable.',
                $e->getMessage(),
                'Expected exception message'
            );
        } catch (Exception $e) {
            $this->fail(
                "$label: Unexpected Exception (" . get_class($e) . '): ' . $e->getMessage()
            );
        }
    }

    /**
     * Test getShutdownCallbacks.
     */
    public function testGetShutdownCallbacks()
    {
        $callbacks = P4_Environment::getShutdownCallbacks();
        $this->assertTrue(isset($callbacks), 'Expect callbacks to be defined');
        $this->assertTrue(is_array($callbacks), 'Expect callbacks to be an array');
    }
}
