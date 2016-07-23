<?php
/**
 * Parent class for all TestCases.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class TestCase extends PHPUnit_Framework_TestCase
{
    public  $utility;
    public  $p4;

    /**
     * Setup test directories and a functioning perforce server.
     */
    public function setUp()
    {
        $this->utility = new TestUtility(get_class($this), $this->getName());
        $this->utility->setUp($this);

        parent::setUp();
    }

    /**
     * Clean up after ourselves.
     */
    public function tearDown()
    {
        if ($this->utility) {
            $this->utility->tearDown($this);
            unset($this->utility);
        }

        parent::tearDown();
    }
}
