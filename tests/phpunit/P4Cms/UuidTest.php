<?php
/**
 * Test methods for the UUID class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_UuidTest extends TestCase
{
    /**
     * Test common usage of uuid.
     */
    public function testBasic()
    {
        $pattern = "/[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}/";
        $uuid    = new P4Cms_Uuid;
        $this->assertSame(1, preg_match($pattern, $uuid));
    }

    /**
     * Test bogus input.
     *
     * @expectedException   InvalidArgumentException
     */
    public function testSetObject()
    {
        $uuid = new P4Cms_Uuid;
        $uuid->set(new stdClass());
    }

    /**
     * Test bogus input.
     *
     * @expectedException   InvalidArgumentException
     */
    public function testSetInt()
    {
        $uuid = new P4Cms_Uuid;
        $uuid->set(123);
    }

    /**
     * Test bogus input.
     *
     * @expectedException   InvalidArgumentException
     */
    public function testSetInvalidFormat()
    {
        $uuid = new P4Cms_Uuid;
        $uuid->set('this is not a valid uuid');
    }

    /**
     * Test good/bad uuids.
     */
    public function testIsValid()
    {
        $uuid = new P4Cms_Uuid;
        $this->assertFalse($uuid->isValid('foo 550e8400-e29b-41d4-a716-446655440000 bar'));
        $this->assertTrue($uuid->isValid('550e8400-e29b-41d4-a716-446655440000'));
        $this->assertTrue($uuid->isValid('550E8400-E29B-41D4-A716-446655440000'));
    }

    /**
     * Test with given uuid.
     */
    public function testSetGetClear()
    {
        $test = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = new P4Cms_Uuid;
        $uuid->set($test);
        $this->assertSame($test, $uuid->get());

        // clearing.
        $uuid->set(null);
        $this->assertNotSame($test, $uuid->get());
    }

    /**
     * Verify UUIDs are unique.
     */
    public function testRandomness()
    {
        $uuids = array();
        for ($i = 0; $i < 1000; $i++) {
            $uuids[] = (string) new P4Cms_Uuid;
        }

        $this->assertSame($i, count(array_unique($uuids)));
    }
}
