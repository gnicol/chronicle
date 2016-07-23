<?php
/**
 * Test methods for the P4 Typemap class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_TypemapTest extends TestCase
{
    /**
     * Test getting values
     */
    public function testGetValues()
    {
        // Test default values
        $typemap = new P4_Typemap;
        $expected = array (
            'TypeMap' => array()
        );

        $this->assertSame(
            $expected,
            $typemap->getValues(),
            'Expected default values to match'
        );

        // set new values.
        $values = array(
            array(
                'type'  => 'text',
                'path'  => '//test/...'
            )
        );

        $typemap->setTypemap($values);

        // Verify instance reflects updated values via accessor
        $this->assertSame(
            $values,
            $typemap->getTypemap(),
            'Expected instance values to match'
        );

        // Verify field and accessor give same result
        $this->assertSame(
            $typemap->getValue('TypeMap'),
            $typemap->getTypemap(),
            'Expected instance values in field to match accessor'
        );

        $this->assertSame(
            array('TypeMap' => $values),
            $typemap->getValues(),
            'Expected instance values array to match'
        );

        // test save.
        $typemap->save();

        $typemap = P4_Typemap::fetch();

        $this->assertSame(
            $values,
            $typemap->getTypemap(),
            'Expected saved values to match'
        );

        // Verify field and accessor give same result
        $this->assertSame(
            $typemap->getValue('TypeMap'),
            $typemap->getTypemap(),
            'Expected saved values in field to match accessor'
        );

        $this->assertSame(
            array('TypeMap' => $values),
            $typemap->getValues(),
            'Expected saved values array to match'
        );
    }

    /**
     * Test setting valid and invalid values.
     */
    public function testSetValues()
    {
        $tests = array(
            array(
                'label' => __LINE__ . " string input",
                'value' => "text //test/path/...",
                'error' => true
            ),
            array(
                'label' => __LINE__ . " bool input",
                'value' => true,
                'error' => true
            ),
            array(
                'label' => __LINE__ . " string input, doubled-up space",
                'value' => array("text  //test/..."),
                'error' => true
            ),
            array(
                'label' => __LINE__ . " string input, missing field",
                'value' => array("//test/..."),
                'error' => true
            ),
            array(
                'label' => __LINE__ . " string input, space in unquoted path",
                'value' => array("binary //test path/..."),
                'error' => true
            ),
            array(
                'label' => __LINE__ . " array input, name has space",
                'value' => array (
                    0 => array (
                        'type' => "resource ",
                        'path' => "//test with spaces/..."
                    )
                ),
                'error' => true,
            ),
            array(
                'label' => __LINE__ . " array input, missing type",
                'value' => array (
                    0 => array (
                        'path' => "//test with spaces/..."
                    )
                ),
                'error' => true,
            ),
            array(
                'label' => __LINE__ . " array input, missing path",
                'value' => array (
                    0 => array (
                        'type' => "utf16",
                    )
                ),
                'error' => true,
            ),
            array(
                'label' => __LINE__ . " one string input, path has spaces.",
                'value' => array('unicode "//test with spaces/..."'),
                'error' => false,
                'out'   => array (
                    0 => array (
                        'type' => "unicode",
                        'path' => "//test with spaces/..."
                    )
                )
            ),
            array(
                'label' => __LINE__ . " one array input, path has spaces.",
                'value'   => array (
                    0 => array (
                        'type' => "text",
                        'path' => "//test with spaces/..."
                    )
                ),
                'error' => false,
            ),
            array(
                'label' => __LINE__ . " one string input.",
                'value' => array("text //..."),
                'error' => false,
                'out'   => array (
                    0 => array (
                        'type' => "text",
                        'path' => "//..."
                    )
                )
            ),
            array(
                'label' => __LINE__ . " four string input",
                'value' => array(
                    "text //...",
                    "binary //test1/...",
                    "symlink //test2/...",
                    "apple //test3/..."
                ),
                'out'   => array (
                    0 => array (
                        'type' => "text",
                        'path' => "//..."
                    ),
                    1 => array (
                        'type' => "binary",
                        'path' => "//test1/..."
                    ),
                    2 => array (
                        'type' => "symlink",
                        'path' => "//test2/..."
                    ),
                    3 => array (
                        'type' => "apple",
                        'path' => "//test3/..."
                    )
                ),
                'error' => false
            ),
            array(
                'label' => __LINE__ . " four array input",
                'value'   => array (
                    0 => array (
                        'type' => "text",
                        'path' => "//..."
                    ),
                    1 => array (
                        'type' => "binary",
                        'path' => "//test1/..."
                    ),
                    2 => array (
                        'type' => "symlink",
                        'path' => "//test2/..."
                    ),
                    3 => array (
                        'type' => "apple",
                        'path' => "//test3/..."
                    )
                ),
                'error' => false
            ),
            array(
                'label' => __LINE__ . " mixed string/array input",
                'value' => array(
                    "text //...",
                    array (
                        'type' => "binary",
                        'path' => "//test1/..."
                    ),
                    'symlink "//test2 with spaces/..."',
                    3 => array (
                        'type' => "apple",
                        'path' => "//test3/..."
                    )
                ),
                'out'   => array (
                    0 => array (
                        'type' => "text",
                        'path' => "//..."
                    ),
                    1 => array (
                        'type' => "binary",
                        'path' => "//test1/..."
                    ),
                    2 => array (
                        'type' => "symlink",
                        'path' => "//test2 with spaces/..."
                    ),
                    3 => array (
                        'type' => "apple",
                        'path' => "//test3/..."
                    )
                ),
                'error' => false
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];

            $protect = new P4_Typemap;

            try {
                $protect->setTypemap($test['value']);

                if ($test['error']) {
                    $this->fail("$label: Unexpected success.");
                }

                $expected = array_key_exists('out', $test) ? $test['out'] : $test['value'];

                $this->assertSame(
                    $expected,
                    $protect->getTypemap(),
                    "$label: Unexpected Output"
                );
            } catch (InvalidArgumentException $e) {
                if (!$test['error']) {
                    $this->fail("$label: Unexpected failure.");
                } else {
                    $this->assertTrue(true, "$label: Expected exception found");
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (Exception $e) {
                $this->fail(
                    "$label: Unexpected Exception (" . get_class($e) . '): ' . $e->getMessage()
                );
            }
        }
    }
}
