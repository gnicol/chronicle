<?php
/**
 * Test diff
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_Diff_Test extends TestCase
{
    /**
     * Test diff of assets where results have been manually inspected.
     */
    public function testCompareAssetOne()
    {
        $left   = file_get_contents(TEST_ASSETS_PATH . '/files/diff-left-1.txt');
        $right  = file_get_contents(TEST_ASSETS_PATH . '/files/diff-right-1.txt');
        
        $left   = str_replace("\r\n", "\n", $left);
        $right  = str_replace("\r\n", "\n", $right);
        
        $diff   = new P4Cms_Diff;
        $result = $diff->compare($left, $right);

        $this->assertTrue($result instanceof P4Cms_Diff_Result);

        // ensure we got the (previously manually inspected) expected result.
        $this->assertSame(
            unserialize(file_get_contents(TEST_ASSETS_PATH . '/files/diff-expected-1.txt')),
            $result->getRawResult(),
            "Unexpected diff result."
        );

        // exercise result.
        $this->assertTrue($result instanceof P4Cms_Diff_Result);
        $this->assertTrue($result->hasDiffs());
        $this->assertSame(4, $result->getDiffCount());
        $this->assertSame(9, count($result->getChunks()));
        $this->assertSame(4, count($result->getDiffChunks()));

        // exercise chunks.
        $chunks   = $result->getChunks();
        $expected = array(
            array('same',   6),
            array('change', 39),
            array('same',   26),
            array('change', 2),
            array('same',   214),
            array('insert', 4),
            array('same',   120),
            array('insert', 16),
            array('same',   46)
        );
        for ($i = 0; $i < count($chunks); $i++) {
            $chunk = $chunks[$i];
            $this->assertSame($expected[$i][0], $chunk->getChunkType());
            $this->assertSame($expected[$i][1], $chunk->getMaxValueCount());
            $this->assertTrue(
                call_user_func(array($chunk, 'is' . ucfirst($expected[$i][0])))
            );
        }

        // rebuild left-hand from chunks.
        $lines = array();
        foreach ($chunks as $chunk) {
            $lines = array_merge($lines, $chunk->getLeft());
        }
        $this->assertSame($left, implode("\n", $lines));

        // rebuild right-hand from chunks.
        $lines = array();
        foreach ($chunks as $chunk) {
            $lines = array_merge($lines, $chunk->getRight());
        }
        $this->assertSame($right, implode("\n", $lines));
    }

    /**
     * Try to compare models with fields.
     */
    public function testCompareModels()
    {
        $left = new P4Cms_Model(
            array(
                'one'   => "a",
                'two'   => "b",
                'three' => "foo bar\n"
                        .  "baz bof\r"
                        .  "zig zag\n"
            )
        );

        $right = new P4Cms_Model(
            array(
                'one'   => "b",
                'two'   => "b",
                'three' => "foo barnacle\r\n"
                        .  "baz bof\n"
                        .  "ziggery\n"
            )
        );

        $diff   = new P4Cms_Diff;
        $result = $diff->compareModels($left, $right);

        $this->assertTrue($result instanceof P4Cms_Diff_ResultCollection);
        $keys = array();
        foreach ($result as $field => $diffResult) {
            $keys[] = $field;
        }
        $this->assertEquals(array('one', 'two', 'three'), $keys, 'Expected fields in result.');
        $this->assertEquals(3, $result->getDiffCount(), 'Expected diff count.');
        $this->assertEquals(2, $result['three']->getDiffCount(), 'Expected diffs for three.');
        $rawResult = $result['three']->getRawResult();
        $this->assertEquals(1, count($rawResult[0]['i']), 'Expected raw result for three.');

        // make sure we can skip fields
        $options = new P4Cms_Diff_OptionsCollection;
        $options['two'] = new P4Cms_Diff_Options;
        $options['two']->setSkipped(true);
        $result = $diff->compareModels($left, $right, $options);

        $this->assertTrue($result instanceof P4Cms_Diff_ResultCollection);
        $this->assertEquals(3, $result->getDiffCount(), 'Expected diff count.');
        $keys = array();
        foreach ($result as $field => $diffResult) {
            $keys[] = $field;
        }
        $this->assertEquals(array('one', 'three'), $keys, 'Expected fields in result.');
        $this->assertEquals(2, $result['three']->getDiffCount(), 'Expected diffs for three.');
        $rawResult = $result['three']->getRawResult();
        $this->assertEquals(1, count($rawResult[0]['i']), 'Expected raw result for three.');
    }

    /**
     * Test that diff behaves as expected with identical inputs
     */
    public function testCompareIdentical()
    {
        $left  = file_get_contents(TEST_ASSETS_PATH . '/files/diff-left-1.txt');
        $right = $left;

        $diff   = new P4Cms_Diff;
        $result = $diff->compare($left, $right);

        $this->assertTrue($result instanceof P4Cms_Diff_Result);
        $this->assertFalse($result->hasDiffs());
        $this->assertSame(0, $result->getDiffCount());
        $this->assertSame(1, count($result->getChunks()));
        $this->assertSame(0, count($result->getDiffChunks()));
    }

    /**
     * Test whitespace detection.
     */
    public function testWhitespaceChanges()
    {
        $left   = "this is a string";
        $right  = "this is  a string";
        $diff   = new P4Cms_Diff;
        $result = $diff->compare($left, $right);
        $chunks = $result->getDiffChunks();

        $this->assertTrue($result->isWhitespaceChange());
        $this->assertTrue(current($chunks)->isWhitespaceChange());

        $left  .= "\nsecond line\nthird line";
        $right .= "\nsecond line\ndifferent third line";
        $diff   = new P4Cms_Diff;
        $result = $diff->compare($left, $right);
        $chunks = $result->getDiffChunks();
        
        $this->assertFalse($result->isWhitespaceChange());
        $this->assertTrue(current($chunks)->isWhitespaceChange());
        $this->assertFalse(end($chunks)->isWhitespaceChange());

        // try semantic flag.
        $left   = "this is a string";
        $right  = "this is astring";
        $diff   = new P4Cms_Diff;
        $result = $diff->compare($left, $right);
        $chunks = $result->getDiffChunks();
        $this->assertFalse($result->isWhitespaceChange());
        $this->assertTrue($result->isWhitespaceChange(false));
    }

    /**
     * Test split arguments option.
     */
    public function testSplitArgs()
    {
        $left  = "foo bar\nzig\nzag";
        $right = "foo baz\nzig\nzag";
        $diff  = new P4Cms_Diff;

        // default should be lines.
        $result = $diff->compare($left, $right);
        $chunks = $result->getChunks();
        $this->assertSame(2, count($chunks));

        // try with chars.
        $options = new P4Cms_Diff_Options;
        $options->setSplitArgs(P4Cms_Diff_Options::PATTERN_CHARS, PREG_SPLIT_NO_EMPTY);
        $result = $diff->compare($left, $right, $options);
        $chunks = $result->getChunks();
        $this->assertSame(3, count($chunks));
        $this->assertSame("foo ba", implode('', $chunks[0]->getLeft()));
        $this->assertSame("foo ba", implode('', $chunks[0]->getRight()));
        $this->assertSame("r", implode('', $chunks[1]->getLeft()));
        $this->assertSame("z", implode('', $chunks[1]->getRight()));
        $this->assertSame("\nzig\nzag", implode('', $chunks[2]->getLeft()));
        $this->assertSame("\nzig\nzag", implode('', $chunks[2]->getRight()));
    }

    /**
     * Test sub-diff feature of chunks
     */
    public function testSubDiff()
    {
        $left  = "foo bar\nzig\nzag";
        $right = "foo baz\nzig\nzag";
        $diff  = new P4Cms_Diff;

        // initial compare on line boundaries.
        $result = $diff->compare($left, $right);
        $chunks = $result->getChunks();
        $this->assertSame(2, count($chunks));

        // do a sub-diff on first line (defaults to char boundary)
        $subResult = $chunks[0]->getSubDiff(0);
        $subChunks = $subResult->getChunks();
        $this->assertTrue($subResult instanceof P4Cms_Diff_Result);
        $this->assertTrue(is_array($subChunks));
        $this->assertSame(2, count($subChunks));
        $this->assertSame('same', $subChunks[0]->getChunkType());
        $this->assertSame("foo ba", implode('', $subChunks[0]->getLeft()));
        $this->assertSame("foo ba", implode('', $subChunks[0]->getRight()));
        $this->assertSame('change', $subChunks[1]->getChunkType());
        $this->assertSame("r", implode('', $subChunks[1]->getLeft()));
        $this->assertSame("z", implode('', $subChunks[1]->getRight()));
    }

    /**
     * Test diffing of binaries.
     */
    public function testBinaryDiff()
    {
        // two different files.
        $left   = file_get_contents(TEST_ASSETS_PATH . '/files/test1.pdf');
        $right  = file_get_contents(TEST_ASSETS_PATH . '/files/test2.pdf');
        $diff   = new P4Cms_Diff;
        $result = $diff->compareBinaries($left, $right);
        $chunks = $result->getChunks();

        $this->assertTrue($result->hasDiffs());
        $this->assertSame(1, $result->getDiffCount());
        $this->assertSame(1, count($result->getChunks()));
        $this->assertSame($left, $chunks[0]->getLeft(0));
        $this->assertSame($right, $chunks[0]->getRight(0));

        // ensure calling indirectly works.
        $options = new P4Cms_Diff_Options;
        $options->setBinaryDiff(true);
        $result  = $diff->compare($left, $right, $options);
        $chunks  = $result->getChunks();

        $this->assertTrue($result->hasDiffs());
        $this->assertSame(1, $result->getDiffCount());
        $this->assertSame(1, count($result->getChunks()));
        $this->assertSame($left, $chunks[0]->getLeft(0));
        $this->assertSame($right, $chunks[0]->getRight(0));

        // two identical files.
        $left   = file_get_contents(TEST_ASSETS_PATH . '/images/content-type-icon.png');
        $right  = $left;
        $result = $diff->compare($left, $right, $options);
        $chunks = $result->getChunks();

        $this->assertFalse($result->hasDiffs());
        $this->assertSame(0, $result->getDiffCount());
        $this->assertSame(1, count($result->getChunks()));
        $this->assertSame($left, $chunks[0]->getLeft(0));
        $this->assertSame($right, $chunks[0]->getRight(0));

        // empty left (insertion)
        $left   = null;
        $result = $diff->compare($left, $right, $options);
        $chunks = $result->getChunks();
        $this->assertSame('insert', $chunks[0]->getChunkType());
        
        // empty right (deletion)
        $left   = $right;
        $right  = '';
        $result = $diff->compare($left, $right, $options);
        $chunks = $result->getChunks();
        $this->assertSame('delete', $chunks[0]->getChunkType());
    }
}