<?php
/**
 * Test methods for the File Utility class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_FileUtilityTest extends TestCase
{
    /**
     * Test deleting a directory and all its contents.
     */
    public function testDeleteRecursive()
    {
        $path = TEST_DATA_PATH . '/test-dir';
        $this->_createTestDirectory($path);
        $this->assertTrue(is_dir($path));

        P4Cms_FileUtility::deleteRecursive($path);
        $this->assertFalse(is_dir($path));

        // try again w. non-writable files.
        $this->_createTestDirectory($path);
        chmod($path . '/sub/sub/foo', 0000);
        $this->assertTrue(is_dir($path));

        P4Cms_FileUtility::deleteRecursive($path);
        $this->assertFalse(is_dir($path));
    }

    /**
     * Test providing a bad path to deleteRecursive.
     */
    public function testDeleteRecursiveWithBadPath()
    {
        $path = TEST_DATA_PATH . '/test-file-to-delete.txt';
        touch($path);

        try {
            P4Cms_FileUtility::deleteRecursive($path);
            $this->fail('Unexpected success');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(
                'Failed to delete path. Path is not a directory.',
                $e->getMessage(),
                'Expected exception.'
            );
        } catch (Exception $e) {
            $this->fail("$label - Unexpected exception (" . get_class($e) .') :'. $e->getMessage());
        }
    }

    /**
     * Test that constructor is private, preventing instances.
     */
    public function testConstructor()
    {
        $class = new ReflectionClass('P4Cms_FileUtility');
        $constructor = $class->getConstructor();
        $footprint = "$constructor";
        $this->assertRegExp('/private/', $footprint, 'Expected constructor to be private.');
    }

    /**
     * Create a test directory with contents so we can delete it.
     *
     * @param   string  $path   the test path to create.
     */
    protected function _createTestDirectory($path)
    {
        // make some dirs.
        mkdir($path);
        mkdir($path . '/sub');
        mkdir($path . '/sub/sub');
        mkdir($path . '/alt');

        // make some files.
        touch($path . '/foo');
        touch($path . '/sub/foo');
        touch($path . '/sub/sub/foo');
        touch($path . '/sub/sub/bar');
        touch($path . '/alt/foo');
    }

    /**
     * Test recursive md5 calculation.
     */
    public function testRecursiveMd5()
    {
        $path = TEST_DATA_PATH . '/test-dir';
        $this->_createTestDirectory($path);
        $this->assertTrue(is_dir($path), 'Test directory is not a directory.');

        $result = P4Cms_FileUtility::md5Recursive($path);
        $this->assertTrue(is_array($result), 'Result is not an array.');
        $this->assertTrue((count($result) == 5), 'Result count does not match expected.');

        $result = P4Cms_FileUtility::md5Recursive($path, null, array('foo'));
        $this->assertTrue(is_array($result), 'Result is not an array.');
        $this->assertTrue(
            (count($result) == 4),
            'Exclude result count does not match expected (expected 4, found '.count($result).'.'
        );

        $result = P4Cms_FileUtility::md5Recursive($path, null, array('sub/foo', 'sub/sub/bar'));
        $this->assertTrue(is_array($result), 'Result is not an array.');
        $this->assertTrue(
            (count($result) == 3),
            'Exclude result count does not match expected (expected 4, found '.count($result).'.'
        );

        try {
            $result = P4Cms_FileUtility::md5Recursive('foo');
        }
        catch (InvalidArgumentException $e) {
            $this->assertEquals(
                "Provided path is not a valid directory.",
                $e->getMessage(),
                'Expected error message.'
            );
        }
    }
}
