<?php
/**
 * This is a test thoroughly exercises the Spec_PluralAbstract via the PluralMock class.
 * It is used to thoroughly exercise the base plural spec functionality so latter implementors
 * can focus on testing only their own additions/modifications.
 *
 * The actual spec type represented by PluralMock is of no importance and should not be considered
 * tested in this context.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Spec_PluralTest extends TestCase
{
    /**
     * Setup Plural test object
     */
    public function setUp()
    {
        // disable mutators by default
        P4_Spec_PluralMock::setProtectedStaticVar('_mutators',  array());
        P4_Spec_PluralMock::setProtectedStaticVar('_accessors', array());

        P4_Spec_PluralMock::setProtectedStaticVar('_specType',  'job');
        P4_Spec_PluralMock::setProtectedStaticVar('_idField',   'Job');

        parent::setUp();
    }

    /**
     * Test setId
     */
    public function testSetIdBad()
    {
        $tests = array(
            __LINE__ .' empty string'   => '',
            __LINE__ .' pure numeric'   => '1234',
            __LINE__ .' bool'           => true,
            __LINE__ .' array'          => array(),
            __LINE__ .' int'            => 10,
            __LINE__ .' float'          => 10.10,
            __LINE__ .' space'          => ' ',
            __LINE__ .' tab'            => "\t",
            __LINE__ .' newline'        => "\n",
            __LINE__ .' inside space'   => 'te st',
            __LINE__ .' inside tab'     => "te\tst",
            __LINE__ .' inside newline' => "te\nst",
            __LINE__ .' hash'           => '#',
            __LINE__ .' inside hash'    => 'te#st',
            __LINE__ .' ampersand'      => '@',
            __LINE__ .' inside at'      => 'te@st',
            __LINE__ .' ...'            => '...',
            __LINE__ .' inside ...'     => 'te...st',
            __LINE__ .' *'              => '*',
            __LINE__ .' inside *'       => 'te*st',
        );

        foreach ($tests as $title => $value) {
            $spec = new P4_Spec_PluralMock;

            try {
                $spec->setId($value);

                $this->fail('Expected setId for: '.$title.' to fail');
            } catch (InvalidArgumentException $e) {
                $this->assertSame(
                    "Cannot set id. Id is invalid.",
                    $e->getMessage(),
                    $title.' Unexpected message in exception'
                );
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                throw $e;
            } catch (Exception $e) {
                $this->fail($title.' Unexpected Exception ('. get_class($e) .'): '. $e->getMessage());
            }
        }
    }

    /**
     * Test setId Good values, also ends up testing getId somewhat
     */
    public function testSetIdGood()
    {
        $tests = array(
            __LINE__ .' alpha string'     => 'abcd',
            __LINE__ .' trailing numeric' => 'abcd1234',
            __LINE__ .' leading numeric'  => '1234abcd',
            __LINE__ .' inside numeric'   => 'ab1234cd',
            __LINE__ .' null'             => null,
        );

        foreach ($tests as $title => $value) {
            $spec = new P4_Spec_PluralMock;

            try {
                $spec->setId($value);

                $this->assertSame(
                    $value,
                    $spec->getId(),
                    $title.' Expected matching input/output'
                );
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                throw $e;
            } catch (Exception $e) {
                $this->fail($title.' Unexpected Exception ('. get_class($e) .'): '. $e->getMessage());
            }
        }
    }

    /**
     * Test getId on an empty object
     */
    public function testGetIdEmptyObject()
    {
        $spec = new P4_Spec_PluralMock;

        $this->assertSame(
            null,
            $spec->getId(),
            'Expected default ID to be null'
        );
    }

    /**
     * Test getId and compare to other access methods
     */
    public function testGetId()
    {
        $value = 'abc123';
        $idFieldName = P4_Spec_PluralMock::callProtectedStaticFunc('_getIdField');

        $spec = new P4_Spec_PluralMock;

        $spec->setId($value);

        // Verify passed value returned by getId
        $this->assertSame(
            $value,
            $spec->getId(),
            'Expected id to match set value'
        );

        // Verify getValue on Id Field matches set value
        $this->assertSame(
            $value,
            $spec->getValue($idFieldName),
            'Expected getValue(idFieldName) to match set value'
        );

        // Verify getValues version of Id field mathes set value
        $fields = $spec->getValues();
        $this->assertSame(
            $value,
            $fields[$idFieldName],
            'Expected getValues()[idField] to match set value'
        );
    }

    /**
     * Test exists with Bad Id's.
     * This is somewhat pointless as we are testing a function in the mock object.
     * It does however give us confidence this function isn't causing issues.
     */
    public function testExistsBadId()
    {
        $this->assertFalse(
            P4_Spec_PluralMock::exists('BadId'),
            'Expected BadId would not exist'
        );

        // try with passed connection
        $connection = P4_Spec_PluralMock::getDefaultConnection();
        $this->assertFalse(
            P4_Spec_PluralMock::exists('BadId', $connection),
            'Expected BadId would not exist, using passed connection'
        );
    }

    /**
     * Test exists with Good Id's.
     * This is somewhat pointless as we are testing a function in the mock object.
     * It does however give us confidence this function isn't causing issues.
     */
    public function testExistsGoodId()
    {
        $spec = new P4_Spec_PluralMock;

        // Ensure a 'goodId' record exists
        $spec->setId('goodId')->setValue('Description', 'test!')->save();

        $this->assertTrue(
            P4_Spec_PluralMock::exists('goodId'),
            'Expected goodId would exist'
        );

        // try with passed connection
        $connection = P4_Spec_PluralMock::getDefaultConnection();
        $this->assertTrue(
            P4_Spec_PluralMock::exists('goodId', $connection),
            'Expected goodId would exist, using passed connection'
        );
    }
}
