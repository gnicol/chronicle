<?php
/**
 * Test methods for the categorization packages.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 * @todo        add adapter testing
 */
class P4Cms_Categorization_Test extends TestCase
{
    /**
     * Create dummy model records for testing.
     */
    public function setUp()
    {
        parent::setUp();

        // create a P4Cms_Record adapter
        $adapter = new P4Cms_Record_Adapter;
        $adapter->setConnection($this->p4)
                ->setBasePath("//depot");
        P4Cms_Record::setDefaultAdapter($adapter);
    }

    /**
     * Remove dummy model records.
     */
    public function tearDown()
    {
        // remove the P4Cms_Record adapter?
        P4Cms_Record::clearDefaultAdapter();

        parent::tearDown();
    }

    /**
     * Test idToFilespec.
     */
    public function testIdToFilespec()
    {
        $index = P4Cms_Categorization_Dir::CATEGORY_FILENAME;
        $tests = array(
            array(
                'label'          => __LINE__ .': category null',
                'id'             => null,
                'error'          => "Cannot get filespec for an empty id.",
                'dirFilespec'    => "//depot/folders/$index",
                'friendFilespec' => "//depot/friends/$index",
                'roundTripId'    => '',
            ),
            array(
                'label'          => __LINE__ .': category empty',
                'id'             => '',
                'error'          => "Cannot get filespec for an empty id.",
                'dirFilespec'    => "//depot/folders/$index",
                'friendFilespec' => "//depot/friends/$index",
                'roundTripId'    => '',
            ),
            array(
                'label'          => __LINE__ .': category number',
                'id'             => 123,
                'error'          => null,
                'dirFilespec'    => "//depot/folders/123/$index",
                'friendFilespec' => "//depot/friends/123/$index",
                'roundTripId'    => '123',
            ),
            array(
                'label'          => __LINE__ .': category numeric',
                'id'             => '123',
                'error'          => null,
                'dirFilespec'    => "//depot/folders/123/$index",
                'friendFilespec' => "//depot/friends/123/$index",
                'roundTripId'    => '123',
            ),
            array(
                'label'          => __LINE__ .': category alphanumeric',
                'id'             => 'abc123',
                'error'          => null,
                'dirFilespec'    => "//depot/folders/abc123/$index",
                'friendFilespec' => "//depot/friends/abc123/$index",
                'roundTripId'    => 'abc123',
            ),
            array(
                'label'          => __LINE__ .': category *',
                'id'             => '*',
                'error'          => null,
                'dirFilespec'    => "//depot/folders/*/$index",
                'friendFilespec' => "//depot/friends/*/$index",
                'roundTripId'    => '*',
            ),
            array(
                'label'          => __LINE__ .': category %%',
                'id'             => '%%',
                'error'          => null,
                'dirFilespec'    => "//depot/folders/%%/$index",
                'friendFilespec' => "//depot/friends/%%/$index",
                'roundTripId'    => '%%',
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $filespec = null;

            try {
                $filespec = P4Cms_Categorization_Dir::idToFilespec($test['id']);
                if (isset($test['error'])) {
                    $this->fail("$label - unexpected dir success.");
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (InvalidArgumentException $e) {
                if (isset($test['error'])) {
                    $this->assertSame(
                        $test['error'],
                        $e->getMessage(),
                        "$label - expected dir error."
                    );
                } else {
                    $this->fail("$label - unexpected dir exception:". $e->getMessage());
                }
            }

            if (!isset($test['error'])) {
                $this->assertSame(
                    $test['dirFilespec'],
                    $filespec,
                    "$label - expected dir filespec"
                );
                $roundTripId = P4Cms_Categorization_Dir::depotFileToId($filespec);
                $this->assertSame(
                    $test['roundTripId'],
                    $roundTripId,
                    "$label - expected dir roundTripId"
                );
            }

            try {
                $filespec = P4Cms_Categorization_Friend::idToFilespec($test['id']);
                if (isset($test['error'])) {
                    $this->fail("$label - unexpected friend success.");
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (InvalidArgumentException $e) {
                if (isset($test['error'])) {
                    $this->assertSame(
                        $test['error'],
                        $e->getMessage(),
                        "$label - expected friend error."
                    );
                } else {
                    $this->fail("$label - unexpected friend exception:". $e->getMessage());
                }
            }

            if (!isset($test['error'])) {
                $this->assertSame(
                    $test['friendFilespec'],
                    $filespec,
                    "$label - expected friend filespec"
                );
                $roundTripId = P4Cms_Categorization_Friend::depotFileToId($filespec);
                $this->assertSame(
                    $test['roundTripId'],
                    $roundTripId,
                    "$label - expected dir roundTripId"
                );
            }
        }
    }

    /**
     * Test nestingAllowed.
     */
    public function testNestingAllowed()
    {
        $this->assertTrue(P4Cms_Categorization_Dir::isNestingAllowed(), 'dir should allow nesting');
        $this->assertFalse(P4Cms_Categorization_Friend::isNestingAllowed(), 'friend should not allow nesting');
    }

    /**
     * Test checkNestability.
     */
    public function testCheckNestability()
    {
        // Dir should not cause an exception
        try {
            P4Cms_Categorization_Dir::callProtectedStaticFunc('_checkNestability');
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception for Dir ('
                . get_class($e) .') :'. $e->getMessage()
            );
        }

        // Friend should cause an exception.
        try {
            P4Cms_Categorization_Friend::callProtectedStaticFunc('_checkNestability');
            $this->fail("Unexpected success for Friend");
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4Cms_Categorization_Exception $e) {
            $this->assertSame(
                "This category does not permit nesting.",
                $e->getMessage(),
                "Expected error for Friend"
            );
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception for Friend ('
                . get_class($e) .') :'. $e->getMessage()
            );
        }
    }

    /**
     * Test existence of categories and their entries.
     */
    public function testExistence()
    {
        $index = P4Cms_Categorization_Dir::CATEGORY_FILENAME;
        $this->assertFalse(
            P4Cms_Categorization_Dir::exists('adir'),
            'adir should not exist.'
        );

        $category = new P4Cms_Categorization_Dir;
        $category->setId('adir');
        $this->assertFalse(
            $category->hasEntry('entry'),
            'adir/entry should not exist.'
        );

        $this->assertFalse(
            P4Cms_Categorization_Friend::exists('afriend'),
            'afriend should not exist.'
        );

        $encodedId = P4Cms_Categorization_Dir::encodeEntryId('entry');
        $file = new P4_File;
        $file->setFilespec("//depot/folders/adir/$encodedId")
             ->add()
             ->setLocalContents('')
             ->submit('added adir/entry');
        $category = new P4Cms_Categorization_Dir;
        $category->setId('adir');
        $this->assertTrue(
            $category->hasEntry('entry'),
            'adir/entry should now exist.'
        );
        $this->assertFalse(
            P4Cms_Categorization_Dir::exists('adir'),
            'adir should still not exist.'
        );

        $file2 = new P4_File;
        $file2->setFilespec("//depot/folders/adir/$index")
              ->add()
              ->setLocalContents('')
              ->submit('added adir metadata');
        $this->assertTrue(
            P4Cms_Categorization_Dir::exists('adir'),
            'adir should now exist.'
        );

        $file3 = new P4_File;
        $file3->setFilespec("//depot/friends/afriend/$index")
              ->add()
              ->setLocalContents('')
              ->submit('added afriend');
        $this->assertTrue(
            P4Cms_Categorization_Friend::exists('afriend'),
            'afriend should now exist.'
        );
    }

    /**
     * Test setId.
     */
    public function testSetId()
    {
        $index = P4Cms_Categorization_Dir::CATEGORY_FILENAME;
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'id'        => null,
                'class'     => 'P4Cms_Categorization_Dir',
                'error'     => null,
            ),
            array(
                'label'     => __LINE__ .': trailing underscore',
                'id'        => 'a_',
                'class'     => 'P4Cms_Categorization_Dir',
                'error'     => null,
            ),
            array(
                'label'     => __LINE__ .': leading period',
                'id'        => '.',
                'class'     => 'P4Cms_Categorization_Dir',
                'error'     => array(
                    'InvalidArgumentException'
                        => 'Cannot set id: Leading periods are not permitted in category ids.',
                ),
            ),
            array(
                'label'     => __LINE__ .': leading dash',
                'id'        => '-',
                'class'     => 'P4Cms_Categorization_Dir',
                'error'     => array(
                    'InvalidArgumentException'
                        => 'Cannot set id: Leading dashes are not permitted in category ids.',
                ),
            ),
            array(
                'label'     => __LINE__ .': trailing underscore',
                'id'        => 'a_',
                'class'     => 'P4Cms_Categorization_Dir',
                'error'     => null,
            ),
            array(
                'label'     => __LINE__ .': reserved index',
                'id'        => P4Cms_Categorization_CategoryAbstract::CATEGORY_FILENAME,
                'class'     => 'P4Cms_Categorization_Dir',
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set id: Id is reserved for internal use.',
                ),
            ),
            array(
                'label'     => __LINE__ .': / where nesting allowed',
                'id'        => 'a/b',
                'class'     => 'P4Cms_Categorization_Dir',
                'error'     => null,
            ),
            array(
                'label'     => __LINE__ .': / where nesting not allowed',
                'id'        => 'a/b',
                'class'     => 'P4Cms_Categorization_Friend',
                'error'     => array(
                    'P4Cms_Categorization_Exception' => 'This category does not permit nesting.',
                ),
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $object = null;
            try {
                $object = new $test['class'];
                $object->setId($test['id']);
                if (isset($test['error'])) {
                    $this->fail("$label - unexpected success");
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (Exception $e) {
                if (isset($test['error'])) {
                    $error = each($test['error']);
                    $this->assertSame(
                        $error[0],
                        get_class($e),
                        "$label - Expected exception. Got (". get_class($e) .') :'. $e->getMessage()
                    );
                    $this->assertSame(
                        $error[1],
                        $e->getMessage(),
                        "$label - Expected error message."
                    );
                } else {
                    $this->fail(
                        "$label - Unexpected exception ("
                        . get_class($e) .') :'. $e->getMessage()
                    );
                }
            }

            if (!isset($test['error'])) {
                $this->assertSame(
                    $test['id'],
                    $object->getId(),
                    "$label - Expected id."
                );
            }
        }
    }

    /**
     * Test remove.
     */
    public function testRemove()
    {
        // prepare file layout
        $index = P4Cms_Categorization_Dir::CATEGORY_FILENAME;
        $file1 = new P4_File;
        $file1->setFilespec("//depot/folders/adir/$index")
              ->add()
              ->setLocalContents('')
              ->submit('added adir metadata');
        $this->assertTrue(
            P4Cms_Categorization_Dir::exists('adir'),
            'adir should exist.'
        );

        $encodedId = P4Cms_Categorization_Dir::encodeEntryId('entry');
        $file2 = new P4_File;
        $file2->setFilespec("//depot/folders/adir/$encodedId")
              ->add()
              ->setLocalContents('')
              ->submit('added adir/entry');
        $category = new P4Cms_Categorization_Dir;
        $category->setId('adir');
        $this->assertTrue(
            $category->hasEntry('entry'),
            'adir/entry should exist.'
        );

        $file3 = new P4_File;
        $file3->setFilespec("//depot/folders/bdir/$index")
              ->add()
              ->setLocalContents('')
              ->submit('added bdir metadata');
        $this->assertTrue(
            P4Cms_Categorization_Dir::exists('bdir'),
            'bdir should exist.'
        );

        $file4 = new P4_File;
        $file4->setFilespec("//depot/folders/bdir/$encodedId")
              ->add()
              ->setLocalContents('')
              ->submit('added bdir/entry');
        $category = new P4Cms_Categorization_Dir;
        $category->setId('bdir');
        $this->assertTrue(
            $category->hasEntry('entry'),
            'bdir/entry should exist.'
        );

        $file5 = new P4_File;
        $file5->setFilespec("//depot/folders/bdir/subdir/$index")
              ->add()
              ->setLocalContents('')
              ->submit('added bdir/subdir metadata');
        $this->assertTrue(
            P4Cms_Categorization_Dir::exists('bdir/subdir'),
            'bdir/subdir should exist.'
        );

        $file6 = new P4_File;
        $file6->setFilespec("//depot/folders/bdir/subdir2/$index")
              ->add()
              ->setLocalContents('')
              ->submit('added bdir/subdir2 metadata');
        $this->assertTrue(
            P4Cms_Categorization_Dir::exists('bdir/subdir2'),
            'bdir/subdir2 should exist.'
        );

        // try to delete a subdir
        $path = 'bdir/subdir';
        try {
            P4Cms_Categorization_Dir::remove($path);
        } catch (Exception $e) {
            $this->fail(
                "Unexpected exception removing '$path' ("
                . get_class($e) .') :'. $e->getMessage()
            );
        }
        $this->assertTrue(
            P4Cms_Categorization_Dir::exists('bdir'),
            'bdir should exist.'
        );
        $this->assertFalse(
            P4Cms_Categorization_Dir::exists('bdir/subdir'),
            'bdir/subdir should no longer exist.'
        );
        $this->assertTrue(
            P4Cms_Categorization_Dir::exists('bdir/subdir2'),
            'bdir/subdir2 should still exist.'
        );

        // try to delete adir
        $path = 'adir';
        try {
            P4Cms_Categorization_Dir::remove($path);
        } catch (Exception $e) {
            $this->fail(
                "Unexpected exception removing '$path' ("
                . get_class($e) .') :'. $e->getMessage()
            );
        }
        $this->assertFalse(
            P4Cms_Categorization_Dir::exists('adir'),
            'adir should no longer exist.'
        );
        $category = new P4Cms_Categorization_Dir;
        $category->setId('adir');
        $this->assertFalse(
            $category->hasEntry('entry'),
            'adir/entry should no longer exist.'
        );

        // try to delete nested path
        $path = 'bdir';
        try {
            P4Cms_Categorization_Dir::remove($path);
        } catch (Exception $e) {
            $this->fail(
                "Unexpected exception removing '$path' ("
                . get_class($e) .') :'. $e->getMessage()
            );
        }
        $this->assertFalse(
            P4Cms_Categorization_Dir::exists('bdir'),
            'bdir should no longer exist.'
        );
        $category = new P4Cms_Categorization_Dir;
        $category->setId('bdir');
        $this->assertFalse(
            $category->hasEntry('entry'),
            'bdir/entry should no longer exist.'
        );
        $this->assertFalse(
            P4Cms_Categorization_Dir::exists('bdir/subdir2'),
            'bdir/subdir2 should no longer exist.'
        );

        // try to delete again
        $path = 'bdir';
        try {
            P4Cms_Categorization_Dir::remove($path);
            $this->fail("Unexpected success removing already removed path '$path'");
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4Cms_Categorization_Exception $e) {
            $this->assertSame(
                'Cannot delete category. Category does not exist.',
                $e->getMessage(),
                "Expected error removing already removed path '$path'"
            );
        }
    }

    /**
     * Test whether a category exists.
     */
    public function testExists()
    {
        $index = P4Cms_Categorization_Dir::CATEGORY_FILENAME;
        $this->assertFalse(
            P4Cms_Categorization_Dir::exists('dne'),
            'dne in root dir should not exist.'
        );

        // create an entry in each hierarchy for existence checks
        $file = new P4_File;
        $file->setFilespec("//depot/folders/newdir/$index")
             ->add()
             ->setLocalContents('')
             ->submit('added newdir');
        $file2 = new P4_File;
        $file2->setFilespec('//depot/folders/newdir/newentry')
              ->add()
              ->setLocalContents('')
              ->submit('added newdir/newentry');
        $file3 = new P4_File;
        $file3->setFilespec('//depot/friends/newfriend')
              ->add()
              ->setLocalContents('')
              ->submit('added newfriend');

        // test updated existance
        $this->assertTrue(
            P4Cms_Categorization_Dir::exists('newdir'),
            'newdir should exist.'
        );
        $this->assertFalse(
            P4Cms_Categorization_Dir::exists('newdir/'),
            'newdir/ should not exist.'
        );

        // category content should NOT be identified with exists.
        $this->assertFalse(
            P4Cms_Categorization_Dir::exists('newdir/newentry'),
            'newdir/newentry should exist.'
        );
        $this->assertFalse(
            P4Cms_Categorization_Friend::exists('newfriend'),
            'newfriend should exist.'
        );
    }

    /**
     * Test hasChildren and getChildren
     */
    public function testHasChildrenGetChildren()
    {
        $dir = new P4Cms_Categorization_Dir;

        $this->assertFalse(
            $dir->setId('nokids')->hasChildren(),
            'nokids should have no children'
        );
        $this->assertFalse(
            $dir->setId('somekids')->hasChildren(),
            'somekids should have no children yet'
        );
        $this->assertFalse(
            $dir->setId('somekids/anotherkid')->hasChildren(),
            'somekids/anotherkid should have no children'
        );

        // verify that friends do not have children
        $friend = new P4Cms_Categorization_Friend;
        try {
            $result = $friend->setId('bob')->hasChildren();
            $this->fail('Unexpected success with hasChildren on friend.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4Cms_Categorization_Exception $e) {
            $this->assertSame(
                'This category does not permit nesting.',
                $e->getMessage(),
                'Expected error with hasChildren on friend.'
            );
        }

        try {
            $children = $friend->getChildren();
            $this->fail('Unexpected success with getChildren on friend.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4Cms_Categorization_Exception $e) {
            $this->assertSame(
                'This category does not permit nesting.',
                $e->getMessage(),
                'Expected error with getChildren on friend.'
            );
        }

        // populate category
        $index = P4Cms_Categorization_Dir::CATEGORY_FILENAME;
        $file = new P4_File;
        $file->setFilespec("//depot/folders/somekids/$index")
             ->add()
             ->setLocalContents('')
             ->submit("added somekids/$index");
        $file2 = new P4_File;
        $file2->setFilespec("//depot/folders/somekids/anotherkid/$index")
              ->add()
              ->setLocalContents('')
              ->submit("added somekids/anotherkid/$index");
        $file3 = new P4_File;
        $file3->setFilespec("//depot/folders/somekids/anotherkid/deeper/$index")
              ->add()
              ->setLocalContents('')
              ->submit('added somekids/anotherkid/deeper_index');
        $file4 = new P4_File;
        $file4->setFilespec("//depot/folders/nokids/$index")
              ->add()
              ->setLocalContents('')
              ->submit("added nokids/$index");
        $file5 = new P4_File;
        $file5->setFilespec("//depot/friends/bob/$index")
              ->add()
              ->setLocalContents('')
              ->submit("added bob/$index");
        $file5 = new P4_File;
        $file5->setFilespec('//depot/friends/bob/invalid')
              ->add()
              ->setLocalContents('')
              ->submit('added bob/invalid');

        // verify that friends still do not have children
        $friend = new P4Cms_Categorization_Friend;
        try {
            $result = $friend->setId('bob')->hasChildren();
            $this->fail('Unexpected success with hasChildren on friend #2.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4Cms_Categorization_Exception $e) {
            $this->assertSame(
                'This category does not permit nesting.',
                $e->getMessage(),
                'Expected error with hasChildren on friend #2.'
            );
        }

        try {
            $children = $friend->getChildren();
            $this->fail('Unexpected success with getChildren on friend #2.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4Cms_Categorization_Exception $e) {
            $this->assertSame(
                'This category does not permit nesting.',
                $e->getMessage(),
                'Expected error with getChildren on friend #2.'
            );
        }

        // get child categories non-recursively
        $ids = array();
        $categories = $dir->setId('somekids')->getChildren();
        foreach ($categories as $category) {
            $ids[] = $category->getId();
        }
        $this->assertSame(
            array('somekids/anotherkid'),
            $ids,
            'Expected children in somekids, non-recursive'
        );

        // get child categories recursively
        $ids = array();
        $categories = $dir->setId('somekids')->getChildren(true);
        foreach ($categories as $category) {
            $ids[] = $category->getId();
        }
        $this->assertSame(
            array(
                'somekids/anotherkid',
                'somekids/anotherkid/deeper',
            ),
            $ids,
            'Expected children in somekids, recursive'
        );

        $this->assertFalse(
            $dir->setId('nokids')->hasChildren(),
            'nokids should have no children'
        );
        $this->assertTrue(
            $dir->setId('somekids')->hasChildren(),
            'somekids should have children now'
        );
        $this->assertTrue(
            $dir->setId('somekids/anotherkid')->hasChildren(),
            'somekids/anotherkid should have children'
        );
        $this->assertFalse(
            $dir->setId('somekids/anotherkid/deeper')->hasChildren(),
            'somekids/anotherkid/deeper should have no children'
        );
    }

    /**
     * Test hasParent() and getParent().
     */
    public function testHasParentGetParent()
    {
        // test with no id.
        $dir = new P4Cms_Categorization_Dir;
        $result = $dir->hasParent();
        $this->assertFalse($dir->hasParent(), 'Category with no id should have no parent.');

        try {
            $dir->getParent();
            $this->fail('Unexpected success with getParent on root category.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (P4Cms_Categorization_Exception $e) {
            $this->assertSame(
                'Cannot get parent. This category has no parent.',
                $e->getMessage(),
                'Expected error with getParent on root category.'
            );
        }

        // test with a variety of ids that do not (yet) exist.
        $testCategories = array(
            'category', 'category/sub'
        );

        foreach ($testCategories as $category) {
            $dir = new P4Cms_Categorization_Dir;
            $this->assertFalse(
                $dir->setId($category)->hasParent(),
                "Category '$category' should not have a parent."
            );
        }

        $index = P4Cms_Categorization_Dir::CATEGORY_FILENAME;
        $file = new P4_File;
        $file->setFilespec("//depot/folders/category/$index")
             ->add()
             ->setLocalContents('')
             ->submit("added category/$index");
        $file2 = new P4_File;
        $file2->setFilespec("//depot/folders/category/sub/$index")
              ->add()
              ->setLocalContents('')
              ->submit("added category/sub/$index");

        // test with ids that should now exist.
        $testCategories = array(
            'category/sub'  => 'category',
            'category/sub2' => 'category',
        );
        foreach ($testCategories as $category => $expected) {
            $dir = new P4Cms_Categorization_Dir;
            $this->assertTrue(
                $dir->setId($category)->hasParent(),
                "Expected '$category' to have a parent."
            );
            $parent = $dir->getParent();
            $this->assertSame($expected, $parent->getId(), "Expected parent id for '$category'");
        }
    }

    /**
     * Test create and delete.
     */
    public function testCreateDelete()
    {
        $query = P4_File_Query::create()->addFilespec('//depot/folders/...');
        $files = P4_File::fetchAll($query);
        $this->assertEquals(0, count($files), 'Expect no categories at outset.');

        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'class'     => 'P4Cms_Categorization_Dir',
                'values'    => array('id' => null),
                'error'     => array(
                    'P4Cms_Categorization_Exception' => 'Cannot save; category id is not set.'
                ),
            ),
            array(
                'label'     => __LINE__ .': empty string',
                'class'     => 'P4Cms_Categorization_Dir',
                'values'    => array('id' => null),
                'error'     => array(
                    'P4Cms_Categorization_Exception' => 'Cannot save; category id is not set.'
                ),
            ),
            array(
                'label'     => __LINE__ .': id with underscore',
                'class'     => 'P4Cms_Categorization_Dir',
                'values'    => array(
                    'id'            => 'a_b',
                    'title'         => 'a title',
                    'description'   => 'a description',
                ),
                'error'     => null,
            ),
            array(
                'label'     => __LINE__ .': id with leading period',
                'class'     => 'P4Cms_Categorization_Dir',
                'values'    => array('id' => '.ab'),
                'error'     => array(
                    'InvalidArgumentException'
                        => 'Cannot set id: Leading periods are not permitted in category ids.',
                ),
            ),
            array(
                'label'     => __LINE__ .': id matching CATEGORY_FILENAME',
                'class'     => 'P4Cms_Categorization_Dir',
                'values'    => array('id' => P4Cms_Categorization_CategoryAbstract::CATEGORY_FILENAME),
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot set id: Id is reserved for internal use.',
                ),
            ),
            array(
                'label'     => __LINE__ .': invalid nesting',
                'class'     => 'P4Cms_Categorization_Friend',
                'values'    => array('id' => 'a/b'),
                'error'     => array(
                    'P4Cms_Categorization_Exception' => 'This category does not permit nesting.',
                ),
            ),
            array(
                'label'     => __LINE__ .': non-existant path',
                'class'     => 'P4Cms_Categorization_Dir',
                'values'    => array('id' => 'a/b'),
                'error'     => array(
                    'InvalidArgumentException' => 'Cannot create new category;'
                        . ' category ancestry does not exist.',
                ),
            ),
            array(
                'label'     => __LINE__ .': success a',
                'class'     => 'P4Cms_Categorization_Dir',
                'values'    => array(
                    'id'            => 'a',
                    'title'         => 'a title',
                    'description'   => 'a description',
                ),
                'error'     => null,
            ),
            array(
                'label'     => __LINE__ .': success a/b',
                'class'     => 'P4Cms_Categorization_Dir',
                'values'    => array(
                    'id'            => 'a/b',
                    'title'         => 'a/b title',
                    'description'   => 'a/b description',
                ),
                'error'     => null,
            ),
            array(
                'label'     => __LINE__ .': success a/b/c',
                'class'     => 'P4Cms_Categorization_Dir',
                'values'    => array(
                    'id'            => 'a/b/c',
                    'title'         => 'a/b/c title',
                    'description'   => 'a/b/c description',
                ),
                'error'     => null,
            ),
            array(
                'label'     => __LINE__ .': success a',
                'class'     => 'P4Cms_Categorization_Dir',
                'values'    => array(
                    'id'            => 'a',
                    'title'         => 'a title',
                    'description'   => 'a description',
                ),
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $category = null;
            try {
                $category = $test['class']::store($test['values']);
                if (isset($test['error'])) {
                    $this->fail("$label - Unexpected success");
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (Exception $e) {
                if (isset($test['error'])) {
                    $actual = array(get_class($e) => $e->getMessage());
                    $this->assertSame($test['error'], $actual, "$label - expected exception");
                } else {
                    $this->fail(
                        "$label - Unexpected exception ("
                        . get_class($e) .') :'. $e->getMessage()
                    );
                }
            }

            if (!isset($test['error'])) {
                $this->assertSame($test['values']['id'], $category->getId(), "$label - expected id");
                foreach ($test['values'] as $key => $value) {
                    $this->assertSame(
                        $value,
                        $category->getValue($key),
                        "$label - expected value for '$key'"
                    );
                }
            }
        }

        // confirm files in depot
        $prefix = '//depot/folders';
        $index = P4Cms_Categorization_Dir::CATEGORY_FILENAME;
        $query = P4_File_Query::create()->addFilespec("$prefix/...");
        $files = P4_File::fetchAll($query);
        $filespecs = array();
        foreach ($files as $file) {
            $filespecs[] = $file->getFilespec();
        }
        $this->assertSame(
            array(
                "$prefix/a/$index",
                "$prefix/a/b/$index",
                "$prefix/a/b/c/$index",
                "$prefix/a_b/$index",
            ),
            $filespecs,
            'Expected categories after creation.'
        );

        // test deletions of constructed categories
        $this->assertTrue(
            (bool)P4_File::exists("$prefix/a/b/c/$index", null, true),
            'Expected third file to exist'
        );
        $category = new P4Cms_Categorization_Dir;
        $category->setId('a/b/c')->delete();
        $this->assertFalse(
            P4_File::exists("$prefix/a/b/c/$index", null, true),
            'Expected third file to no longer exist'
        );

        // try deleting a.
        $category = new P4Cms_Categorization_Dir;
        $category->setId('a');
        $category->delete();
        $this->assertFalse(P4_File::exists("$prefix/a/$index", null, true), 'Expected a to no longer exist');
        $this->assertFalse(P4_File::exists("$prefix/a/b/$index", null, true), 'Expected a/b to no longer exist');
    }

    /**
     * Test move.
     */
    public function testMove()
    {
        // create some categories containing entries
        try {
            P4Cms_Categorization_Dir::store('zero');
            P4Cms_Categorization_Dir::store('one');
            P4Cms_Categorization_Dir::store('one/sub1');
            $category = P4Cms_Categorization_Dir::store('two');
            $category->addEntry('a');
            $category = P4Cms_Categorization_Dir::store('three');
            $category->addEntries(array('b', 'c'));
            $category = P4Cms_Categorization_Dir::store('three/sub3');
            $category->addEntries(array('d'));
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception creating categories for move testing ('
                . get_class($e) .') '. $e->getMessage()
            );
        }

        $prefix = '//depot/folders';
        $index = P4Cms_Categorization_Dir::CATEGORY_FILENAME;
        $encodedA = P4Cms_Categorization_Dir::encodeEntryId('a');
        $encodedB = P4Cms_Categorization_Dir::encodeEntryId('b');
        $encodedC = P4Cms_Categorization_Dir::encodeEntryId('c');
        $encodedD = P4Cms_Categorization_Dir::encodeEntryId('d');

        $query = P4_File_Query::create()->addFilespec("$prefix/...");
        $files = P4_File::fetchAll($query);
        $filespecs = array();
        foreach ($files as $file) {
            $filespecs[] = $file->getFilespec();
        }
        $this->assertSame(
            array(
                "$prefix/one/$index",
                "$prefix/one/sub1/$index",
                "$prefix/three/$index",
                "$prefix/three/$encodedB",
                "$prefix/three/$encodedC",
                "$prefix/three/sub3/$index",
                "$prefix/three/sub3/$encodedD",
                "$prefix/two/$index",
                "$prefix/two/$encodedA",
                "$prefix/zero/$index",
            ),
            $filespecs,
            "Expected initial category layout."
        );

        $tests = array(
            array(
                'label'     => __LINE__ .': null source and target categories',
                'sourceId'  => null,
                'targetId'  => null,
                'error'     => 'Cannot move category; both the source and target category must be specified.',
                'expect'    => null,
            ),
            array(
                'label'     => __LINE__ .': null source category',
                'sourceId'  => null,
                'targetId'  => 'bogus',
                'error'     => 'Cannot move category; both the source and target category must be specified.',
                'expect'    => null,
            ),
            array(
                'label'     => __LINE__ .': null target category',
                'sourceId'  => 'bogus',
                'targetId'  => null,
                'error'     => 'Cannot move category; both the source and target category must be specified.',
                'expect'    => null,
            ),
            array(
                'label'     => __LINE__ .': root source category',
                'sourceId'  => '',
                'targetId'  => 'one',
                'error'     => 'Cannot move category; neither the source or target category can be "".',
                'expect'    => null,
            ),
            array(
                'label'     => __LINE__ .': root target category',
                'sourceId'  => 'one',
                'targetId'  => '',
                'error'     => 'Cannot move category; neither the source or target category can be "".',
                'expect'    => null,
            ),
            array(
                'label'     => __LINE__ .': non-existant source category',
                'sourceId'  => 'bogus',
                'targetId'  => 'bogus2',
                'error'     => 'Cannot move category; source category does not exist.',
                'expect'    => null,
            ),
            array(
                'label'     => __LINE__ .': source category == target category',
                'sourceId'  => 'one',
                'targetId'  => 'one',
                'error'     => 'Cannot move category; target category already exists.',
                'expect'    => null,
            ),
            array(
                'label'     => __LINE__ .': target category exists',
                'sourceId'  => 'one',
                'targetId'  => 'two',
                'error'     => 'Cannot move category; target category already exists.',
                'expect'    => null,
            ),
            array(
                'label'     => __LINE__ .': target category is subcat of source',
                'sourceId'  => 'three',
                'targetId'  => 'three/bogus',
                'error'     => 'Cannot move category; target category is within source category.',
                'expect'    => null,
            ),
            array(
                'label'     => __LINE__ .': move category with no contents',
                'sourceId'  => 'zero',
                'targetId'  => 'moved',
                'error'     => null,
                'expect'    => array(
                    "$prefix/moved/$index",
                    "$prefix/one/$index",
                    "$prefix/one/sub1/$index",
                    "$prefix/three/$index",
                    "$prefix/three/$encodedB",
                    "$prefix/three/$encodedC",
                    "$prefix/three/sub3/$index",
                    "$prefix/three/sub3/$encodedD",
                    "$prefix/two/$index",
                    "$prefix/two/$encodedA",
                ),
            ),
            array(
                'label'     => __LINE__ .': move back category with no contents',
                'sourceId'  => 'moved',
                'targetId'  => 'zero',
                'error'     => null,
                'expect'    => array(
                    "$prefix/one/$index",
                    "$prefix/one/sub1/$index",
                    "$prefix/three/$index",
                    "$prefix/three/$encodedB",
                    "$prefix/three/$encodedC",
                    "$prefix/three/sub3/$index",
                    "$prefix/three/sub3/$encodedD",
                    "$prefix/two/$index",
                    "$prefix/two/$encodedA",
                    "$prefix/zero/$index",
                ),
            ),
            array(
                'label'     => __LINE__ .': move a category with target id starts with source id',
                'sourceId'  => 'zero',
                'targetId'  => 'zeroOne',
                'error'     => null,
                'expect'    => array(
                    "$prefix/one/$index",
                    "$prefix/one/sub1/$index",
                    "$prefix/three/$index",
                    "$prefix/three/$encodedB",
                    "$prefix/three/$encodedC",
                    "$prefix/three/sub3/$index",
                    "$prefix/three/sub3/$encodedD",
                    "$prefix/two/$index",
                    "$prefix/two/$encodedA",
                    "$prefix/zeroOne/$index",
                ),
            ),
            array(
                'label'     => __LINE__ .': move a category with source id starts with target id',
                'sourceId'  => 'zeroOne',
                'targetId'  => 'zero',
                'error'     => null,
                'expect'    => array(
                    "$prefix/one/$index",
                    "$prefix/one/sub1/$index",
                    "$prefix/three/$index",
                    "$prefix/three/$encodedB",
                    "$prefix/three/$encodedC",
                    "$prefix/three/sub3/$index",
                    "$prefix/three/sub3/$encodedD",
                    "$prefix/two/$index",
                    "$prefix/two/$encodedA",
                    "$prefix/zero/$index",
                ),
            ),
            array(
                'label'     => __LINE__ .': move category with entries',
                'sourceId'  => 'two',
                'targetId'  => 'moved',
                'error'     => null,
                'expect'    => array(
                    "$prefix/moved/$index",
                    "$prefix/moved/$encodedA",
                    "$prefix/one/$index",
                    "$prefix/one/sub1/$index",
                    "$prefix/three/$index",
                    "$prefix/three/$encodedB",
                    "$prefix/three/$encodedC",
                    "$prefix/three/sub3/$index",
                    "$prefix/three/sub3/$encodedD",
                    "$prefix/zero/$index",
                ),
            ),
            array(
                'label'     => __LINE__ .': move back category with entries',
                'sourceId'  => 'moved',
                'targetId'  => 'two',
                'error'     => null,
                'expect'    => array(
                    "$prefix/one/$index",
                    "$prefix/one/sub1/$index",
                    "$prefix/three/$index",
                    "$prefix/three/$encodedB",
                    "$prefix/three/$encodedC",
                    "$prefix/three/sub3/$index",
                    "$prefix/three/sub3/$encodedD",
                    "$prefix/two/$index",
                    "$prefix/two/$encodedA",
                    "$prefix/zero/$index",
                ),
            ),
            array(
                'label'     => __LINE__ .': move category with entries and subcats',
                'sourceId'  => 'three',
                'targetId'  => 'moved',
                'error'     => null,
                'expect'    => array(
                    "$prefix/moved/$index",
                    "$prefix/moved/$encodedB",
                    "$prefix/moved/$encodedC",
                    "$prefix/moved/sub3/$index",
                    "$prefix/moved/sub3/$encodedD",
                    "$prefix/one/$index",
                    "$prefix/one/sub1/$index",
                    "$prefix/two/$index",
                    "$prefix/two/$encodedA",
                    "$prefix/zero/$index",
                ),
            ),
            array(
                'label'     => __LINE__ .': move back category with entries and subcats',
                'sourceId'  => 'moved',
                'targetId'  => 'three',
                'error'     => null,
                'expect'    => array(
                    "$prefix/one/$index",
                    "$prefix/one/sub1/$index",
                    "$prefix/three/$index",
                    "$prefix/three/$encodedB",
                    "$prefix/three/$encodedC",
                    "$prefix/three/sub3/$index",
                    "$prefix/three/sub3/$encodedD",
                    "$prefix/two/$index",
                    "$prefix/two/$encodedA",
                    "$prefix/zero/$index",
                ),
            ),
            array(
                'label'     => __LINE__ .': move category to subcat',
                'sourceId'  => 'three',
                'targetId'  => 'one/three',
                'error'     => null,
                'expect'    => array(
                    "$prefix/one/$index",
                    "$prefix/one/sub1/$index",
                    "$prefix/one/three/$index",
                    "$prefix/one/three/$encodedB",
                    "$prefix/one/three/$encodedC",
                    "$prefix/one/three/sub3/$index",
                    "$prefix/one/three/sub3/$encodedD",
                    "$prefix/two/$index",
                    "$prefix/two/$encodedA",
                    "$prefix/zero/$index",
                ),
            ),
            array(
                'label'     => __LINE__ .': move subcat back to category',
                'sourceId'  => 'one/three',
                'targetId'  => 'three',
                'error'     => null,
                'expect'    => array(
                    "$prefix/one/$index",
                    "$prefix/one/sub1/$index",
                    "$prefix/three/$index",
                    "$prefix/three/$encodedB",
                    "$prefix/three/$encodedC",
                    "$prefix/three/sub3/$index",
                    "$prefix/three/sub3/$encodedD",
                    "$prefix/two/$index",
                    "$prefix/two/$encodedA",
                    "$prefix/zero/$index",
                ),
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            try {
                P4Cms_Categorization_Dir::move($test['sourceId'], $test['targetId']);
                if (isset($test['error'])) {
                    $this->fail("$label - Unexpected success");
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (InvalidArgumentException $e) {
                if (isset($test['error'])) {
                    $this->assertSame(
                        $test['error'],
                        $e->getMessage(),
                        "$label - Expected error message."
                    );
                } else {
                    $this->fail("$label - Unexpected argument exception: ". $e->getMessage());
                }
            } catch (Exception $e) {
                $this->fail(
                    "$label - Unexpected exception ("
                    . get_class($e) .') :'. $e->getMessage()
                );
            }

            if (isset($test['expect'])) {
                $query = P4_File_Query::create()->addFilespec("$prefix/...");
                $files = P4_File::fetchAll($query);
                $filespecs = array();
                foreach ($files as $file) {
                    if ($file->isDeleted()) {
                        continue;
                    }
                    $filespecs[] = $file->getFilespec();
                }
                $this->assertSame(
                    $test['expect'],
                    $filespecs,
                    "$label - Expected category layout."
                );
            }
        }

    }

    /**
     * Test that addEntry accepts integer entries.
     */
    public function testAddIntegerEntry()
    {
        $friend = new P4Cms_Categorization_Friend;
        $friend->setId('test');
        $friend->addEntry(1);
        $entries = $friend->getEntries();
        $this->assertSame(array('1'), $entries, 'Expected entries');
    }

    /**
     * Test addEntry with bogus entry.
     */
    public function testAddBogusEntry()
    {
        $dir = new P4Cms_Categorization_Dir;
        $dir->setId('test');
        try {
            $dir->addEntry(null);
            $this->fail('Unexpected success adding a null entry.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                "Cannot add entries; all entries must either be strings or known entry types.",
                $e->getMessage(),
                'Expected error adding a null entry.'
            );
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception adding a null entry ('
                . get_class($e) .') :'. $e->getMessage()
            );
        }

        try {
            $dir->addEntries(array(null, 'four'));
            $this->fail('Unexpected success adding a list with bogus entry.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                "Cannot add entries; all entries must either be strings or known entry types.",
                $e->getMessage(),
                'Expected error adding a list with bogus entry.'
            );
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception adding a list with bogus entry ('
                . get_class($e) .') :'. $e->getMessage()
            );
        }

        try {
            $dir->addEntries(array(array(null, null)));
            $this->fail('Unexpected success adding a list with bogus entry+sort value.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                "Cannot add entries; all entries must either be strings or known entry types.",
                $e->getMessage(),
                'Expected error adding a list with bogus entry+sort value.'
            );
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception adding a list with bogus entry ('
                . get_class($e) .') :'. $e->getMessage()
            );
        }
    }

    /**
     * Test deleteEntry with bogus entry.
     */
    public function testDeleteBogusEntry()
    {
        $dir = new P4Cms_Categorization_Dir;
        $dir->setId('test');
        try {
            $dir->deleteEntry(null);
            $this->fail('Unexpected success deleting a null entry.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                "Cannot delete entries; all entries must either be strings or known entry types.",
                $e->getMessage(),
                'Expected error deleting a null entry.'
            );
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception deleting a null entry ('
                . get_class($e) .') :'. $e->getMessage()
            );
        }

        try {
            $dir->deleteEntries(array(null, 'four'));
            $this->fail('Unexpected success deleting list with bogus entry.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                "Cannot delete entries; all entries must either be strings or known entry types.",
                $e->getMessage(),
                'Expected error deleting list with bogus entry.'
            );
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception deleting list with bogus entry ('
                . get_class($e) .') :'. $e->getMessage()
            );
        }
    }

    /**
     * Test recursive entry fetching.
     */
    public function testGetEntriesRecursively()
    {
        $content = $this->_makeContent(
            array(
                'test1' => 'One',
                'test2' => 'Two',
                'test3' => 'Three',
                'test4' => 'Four',
            )
        );
        $dir = new P4Cms_Categorization_Dir;
        $dir->setId('deep')
            ->setTitle('Deep Category')
            ->setDescription('Deep Category Description')
            ->save();
        $dir->addEntry('test1');

        $dir2 = P4Cms_Categorization_Dir::store('deep/subdir1');
        $this->assertSame('deep/subdir1', $dir2->getId(), 'Exected id for dir2');
        $dir2->addEntry('test2');

        $dir3 = P4Cms_Categorization_Dir::store('deep/subdir1/deeper');
        $dir3->addEntry('test3');

        $dir4 = P4Cms_Categorization_Dir::store('deep/subdir1/deeper/subdir2');
        $dir4->addEntry('test4');

        // start with the parent directory
        $entries = $dir->getEntries();
        sort($entries);
        $this->assertSame(
            array('test1'),
            $entries,
            'Expected non-recursive entries for dir.'
        );

        $entries = $dir->getEntries(array('recursive' => true));
        $this->assertSame(
            array('test4', 'test1', 'test3', 'test2'),
            $entries,
            'Expected recursive entries for dir.'
        );

        // again with subdir1
        $entries = $dir2->getEntries();
        sort($entries);
        $this->assertSame(
            array('test2'),
            $entries,
            'Expected non-recursive entries for dir2.'
        );

        $entries = $dir2->getEntries(array('recursive' => true));
        $this->assertSame(
            array('test4', 'test3', 'test2'),
            $entries,
            'Expected recursive entries for dir2.'
        );

        // again, but fetch objects
        $entries = $dir2->getEntries(array('recursive' => true, 'dereference' => true));
        $this->assertEquals(3, count($entries), "Expected object count.");
        $this->assertSame('test4', $entries['test4']->id, 'Expected object id #1');
        $this->assertSame('test3', $entries['test3']->id, 'Expected object id #2');
        $this->assertSame('test2', $entries['test2']->id, 'Expected object id #3');
    }

    /**
     * Test encoding/decoding of entry ids.
     */
    public function testEncodeDecodeEntryId()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': encode null',
                'value'     => null,
                'method'    => 'encodeEntryId',
                'expect'    => null,
                'error'     => 'Cannot encode entry id; id not set or has no length.',
            ),
            array(
                'label'     => __LINE__ .': encode empty string',
                'value'     => '',
                'method'    => 'encodeEntryId',
                'expect'    => null,
                'error'     => 'Cannot encode entry id; id not set or has no length.',
            ),
            array(
                'label'     => __LINE__ .': encode string',
                'value'     => 'the quick brown fox jumped over the lazy dog',
                'method'    => 'encodeEntryId',
                'expect'
                    =>'_74686520717569636b2062726f776e20666f78206a756d706564206f76657220746865206c617a7920646f67',
                'error'     => null,
            ),
            array(
                'label'     => __LINE__ .': decode null',
                'value'     => null,
                'method'    => 'decodeEntryId',
                'expect'    => null,
                'error'     => 'Cannot decode entry id; encoded id not set or has no length.',
            ),
            array(
                'label'     => __LINE__ .': decode empty string',
                'value'     => '',
                'method'    => 'decodeEntryId',
                'expect'    => null,
                'error'     => 'Cannot decode entry id; encoded id not set or has no length.',
            ),
            array(
                'label'     => __LINE__ .': decode string',
                'value'
                    => '_74686520717569636b2062726f776e20666f78206a756d706564206f76657220746865206c617a7920646f67',
                'method'    => 'decodeEntryId',
                'expect'    => 'the quick brown fox jumped over the lazy dog',
                'error'     => null,
            ),
            array(
                'label'     => __LINE__ .': decode bogus encoding',
                'value'     => '_#',
                'method'    => 'decodeEntryId',
                'expect'    => null,
                'error'     => 'Cannot decode entry id; encoded id contains invalid characters.',
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $result = null;
            try {
                $result = P4Cms_Categorization_Dir::$test['method']($test['value']);
                if (isset($test['error'])) {
                    $this->fail("$label - Unexpected success");
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (InvalidArgumentException $e) {
                if (isset($test['error'])) {
                    $this->assertSame(
                        $test['error'],
                        $e->getMessage(),
                        "$label - expected error."
                    );
                } else {
                    $this->fail("$label - Unexpected argument exception: ". $e->getMessage());
                }
            } catch (Exception $e) {
                $this->fail(
                    "$label - Unexpected exception  ("
                    . get_class($e) .') :'. $e->getMessage()
                );
            }

            if (isset($test['expect'])) {
                $this->assertSame(
                    $test['expect'],
                    $result,
                    "$label - expected result"
                );
            }
        }

        // test roundtrip
        $testString = 'http://search.perforce.com/search?q=deleted%20files&site=kb!@#$%^&*()-_=+,<.>/?;:\'"[{]}\\';
        $this->assertSame(
            $testString,
            P4Cms_Categorization_Dir::decodeEntryId(P4Cms_Categorization_Dir::encodeEntryId($testString)),
            'Expected round trip to work.'
        );
    }

    /**
     * Test entry handling.
     */
    public function testEntryHandling()
    {
        // create several content entries.
        $content = $this->_makeContent(
            array(
                'test1' => 'One',
                'test2' => 'Two',
                'test3' => 'Three',
                'test4' => 'Four',
                'test5' => 'Five',
                'test6' => 'Six',
            )
        );

        $dir = new P4Cms_Categorization_Dir;
        $dir->setId('category')
            ->setTitle('Test Category')
            ->setDescription('Test Category Description')
            ->save();
        $this->assertFalse($dir->hasEntries(), 'Expect to have no entries at start');
        $this->assertEquals(0, count($dir->getEntries()), 'Expect entry count to be 0 at start');

        // add an entry
        $dir->addEntry('test1');
        $this->assertTrue($dir->hasEntries(), 'Expect to have entries after adding one');
        $entries = $dir->getEntries();
        $this->assertSame('test1', $entries[0], 'Expected entry');

        // add a couple more entries
        $dir->addEntries(array('test2', 'test3'));
        $this->assertTrue($dir->hasEntries(), 'Expect to have entries after adding two and three');
        $entries = $dir->getEntries();
        $this->assertSame(
            array('test1', 'test3', 'test2'),
            $entries,
            'Expected entries after add test2, test3'
        );

        // add an object
        $dir->addEntry($content[3]);
        $entries = $dir->getEntries();
        $this->assertSame(
            array('test4', 'test1', 'test3', 'test2'),
            $entries,
            'Expected entries after add test4'
        );

        // try adding an object that does not have a getId() method
        try {
            $object = new stdClass;
            $dir->addEntry($object);
            $this->fail('Unexpected success adding object without getId()');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                "Cannot add entries; all entries must either be strings or known entry types.",
                $e->getMessage(),
                'Expected error adding object without getId()'
            );
        }

        // add a couple more objects
        $dir->addEntries(array($content[4], $content[5]));
        $entries = $dir->getEntries();
        $this->assertSame(
            array('test5', 'test4', 'test1', 'test6', 'test3', 'test2'),
            $entries,
            'Expected entries after add test5, test6'
        );

        // try to add entries with a non-array
        try {
            $dir->addEntries('not an array');
            $this->fail('Unexpected success adding a non-list.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                'Cannot add entries; you must provide an array of entries.',
                $e->getMessage(),
                'Expected error adding a non-list.'
            );
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception adding a non-list ('
                . get_class($e) .') :'. $e->getMessage()
            );
        }

        // delete an entry
        $dir->deleteEntry('test1');
        $entries = $dir->getEntries();
        $this->assertSame(
            array('test5', 'test4', 'test6', 'test3', 'test2'),
            $entries,
            'Expected entries after delete one'
        );

        // delete entries
        $dir->deleteEntries(array('test2', $content[4]));
        $entries = $dir->getEntries();
        $this->assertSame(
            array('test4', 'test6', 'test3'),
            $entries,
            'Expected entries after delete test2, test5'
        );

        // try to delete a non-list
        try {
            $dir->deleteEntries('not a list');
            $this->fail('Unexpected success deleting a non-list.');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                'Cannot delete entries; you must provide an array of entries.',
                $e->getMessage(),
                'Expected error deleting a non-list.'
            );
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception deleting a non-list ('
                . get_class($e) .') :'. $e->getMessage()
            );
        }

    }

    /**
     * Test various methods when category not setup.
     */
    public function testCategoryWithoutSetup()
    {
        $dir = new P4Cms_Categorization_Dir;

        $tests = array(
            array(
                'label' => __LINE__ .': getEntries',
                'method'    => 'getEntries',
                'params'    => array(),
                'error'     => 'Cannot get entries; category id is not set.',
            ),
            array(
                'label' => __LINE__ .': getEntries (recursive)',
                'method'    => 'getEntries',
                'params'    => array('recursive' => true),
                'error'     => 'Cannot get entries; category id is not set.',
            ),
            array(
                'label' => __LINE__ .': deleteEntries',
                'method'    => 'deleteEntries',
                'params'    => array('1', '2', '3'),
                'error'     => 'Cannot delete entries; category id is not set.',
            ),
            array(
                'label' => __LINE__ .': addEntry',
                'method'    => 'addEntry',
                'params'    => 'entry',
                'error'     => 'Cannot add entries; category id is not set.',
            ),
            array(
                'label' => __LINE__ .': hasChildren',
                'method'    => 'hasChildren',
                'params'    => null,
                'error'     => 'Cannot get children; category id is not set.',
            ),
            array(
                'label' => __LINE__ .': delete',
                'method'    => 'delete',
                'params'    => null,
                'error'     => 'Cannot delete category; category id is not set.',
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $method = $test['method'];
            try {
                $dir->$method($test['params']);
                $this->fail("$label - Unexpected success.");
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (P4Cms_Categorization_Exception $e) {
                $this->assertSame(
                    $test['error'],
                    $e->getMessage(),
                    "$label - Expected error."
                );
            } catch (Exception $e) {
                $this->fail(
                    "$label - Unexpected exception ("
                    . get_class($e) .') :'. $e->getMessage()
                );
            }
        }

    }

    /**
     * Test getEntries with $dereference set.
     */
    public function testGetEntriesMakeObjects()
    {
        $content = $this->_makeContent(
            array(
                'test1' => 'One',
            )
        );

        // setup friend tests, where dereferenceEntry has not been implemented.
        $index = P4Cms_Categorization_Dir::CATEGORY_FILENAME;
        $file1 = new P4_File;
        $file1->setFilespec("//depot/friends/test/$index")
              ->add()
              ->setLocalContents('')
              ->submit("added friend test/$index");

        $encodedId = P4Cms_Categorization_Dir::encodeEntryId('test1');
        $file2 = new P4_File;
        $file2->setFilespec("//depot/friends/test/$encodedId")
              ->add()
              ->setLocalContents('')
              ->submit('added friend test/test1');

        // verify that the friend class won't create objects via getEntries
        $friend = new P4Cms_Categorization_Friend;
        $friend->setId('test');
        $entries = $friend->getEntries(array('dereference' => true));
        $this->assertSame(
            array('test1'),
            $entries,
            "Expected getEntries() return list of entry ids even if tried to dereference."
        );

        // setup dir tests, where dereference has been implemented.
        $file3 = new P4_File;
        $file3->setFilespec("//depot/folders/test/$index")
              ->add()
              ->setLocalContents('')
              ->submit("added dir test/$index");

        $encodedId = P4Cms_Categorization_Dir::encodeEntryId('test1');
        $file4 = new P4_File;
        $file4->setFilespec("//depot/folders/test/$encodedId")
              ->add()
              ->setLocalContents('')
              ->submit('added dir test/test1');

        // verify that the dir class can create objects via getEntries
        $dir = new P4Cms_Categorization_Dir;
        $entries = $dir->setId('test')->getEntries(array('dereference' => true));
        $this->assertEquals(1, count($entries), 'Expected entries count.');
        $this->assertSame('test1', $entries['test1']->id, 'Expected object id');
    }

    /**
     * Test dereferenceEntries.
     */
    public function testDereferenceEntries()
    {
        // create entries
        $create = array(
            'deref1' => 'One',
            'deref2' => 'Two',
            'deref3' => 'Three',
        );
        $content = $this->_makeContent($create);

        // verify that the friend class won't dereference entries.
        $friend = new P4Cms_Categorization_Friend;
        $friend->setId('test')
               ->addEntries(array_keys($create));
        $entries = $friend->getEntries(array('paths' => array(1, 2, 3)));
        $this->assertSame(
            array_keys($create),
            $entries,
            "Expected empty entries list."
        );

        // verify that the dir class can dereference entries.
        $dir = new P4Cms_Categorization_Dir;
        $dir->setId('test')
            ->addEntries(array_keys($create));
        $entries = $dir->getEntries(
            array(
                'dereference' => true,
                'paths'       => array_keys($create)
            )
        );
        $titles  = array();
        foreach ($entries as $entry) {
            $titles[] = $entry->getValue('title');
        }

        // assume entries are sorted by title by default
        $expected = array('One', 'Three', 'Two');
        $this->assertEquals($expected, $titles, 'Expected objects');
    }

    /**
     * Test adding an entry that already exists, and deleting an entry that does not exist.
     */
    public function testAddDupeAndDeleteUnknown()
    {
        $content = $this->_makeContent(
            array(
                'test1' => 'One',
            )
        );

        $dir = new P4Cms_Categorization_Dir;

        $dir->setId('category')
            ->setTitle('Test Category')
            ->setDescription('Test Category Description')
            ->save();
        $this->assertFalse($dir->hasEntries(), 'Expect to have no entries at start.');
        $this->assertEquals(0, count($dir->getEntries()), 'Expect entry count to be 0 at start.');

        // try deleting an entry
        $dir->deleteEntry('test1');

        // add an entry
        $dir->addEntry('test1');
        $this->assertTrue($dir->hasEntries(), 'Expect an entry');
        $this->assertEquals(1, count($dir->getEntries()), 'Expect entry count to be 1 after adding entry.');

        // try adding same entry again
        $dir->addEntry('test1');
        $this->assertEquals(1, count($dir->getEntries()), 'Expect entry count to be 1 after adding dupe.');

        // delete the entry
        $dir->deleteEntry('test1');
        $this->assertFalse($dir->hasEntries(), 'Expect to have no entries at end.');

        // add the entry again to make sure files with delete status have no influence.
        $dir->addEntry('test1');
        $this->assertEquals(1, count($dir->getEntries()), 'Expect entry count to be 1 after adding dupe.');
    }

    /**
     * Test setEntryCategories with bad parameters
     */
    public function testSetEntryCategoriesBadParams()
    {
        $tests = array(
            array(
                'label'         => __LINE__ .': null entry',
                'entry'         => null,
                'categories'    => null,
                'error'         => 'Cannot set categories; the entry must either be a string or known data structure.',
            ),
            array(
                'label'         => __LINE__ .': empty entry',
                'entry'         => '',
                'categories'    => null,
                'error'         => 'Cannot set categories; the entry must either be a string or known data structure.',
            ),
            array(
                'label'         => __LINE__ .': string entry, null categories',
                'entry'         => 'string',
                'categories'    => null,
                'error'         => 'Cannot set categories; categories must be an array.',
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            try {
                P4Cms_Categorization_Dir::setEntryCategories($test['entry'], 'A', $test['categories']);
                if (isset($test['error'])) {
                    $this->fail("$label - unexpected success");
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (InvalidArgumentException $e) {
                if (isset($test['error'])) {
                    $this->assertSame(
                        $test['error'],
                        $e->getMessage(),
                        "$label - Expected error"
                    );
                } else {
                    $this->fail("$label - unexpected argument exception: ". $e->getMessage());
                }
            } catch (Exception $e) {
                $this->fail("$label - Unexpected exception (" . get_class($e) .') :'. $e->getMessage());
            }
        }
    }

    /**
     * Test setEntryCategories behaviour.
     */
    public function testSetEntryCategories()
    {
        $tests = array(
            array(
                'label'         => __LINE__ .': foo, set no cats, with no cats (noop)',
                'entry'         => 'foo',
                'setCats'       => array(),
                'expectedCats'  => array(),
                'expectedSet'   => array(),
            ),
            array(
                'label'         => __LINE__ .': foo, set cats, with no cats (adds/creates)',
                'entry'         => 'foo',
                'setCats'       => array('one', 'one/two'),
                'expectedCats'  => array('one', 'one/two'),
                'expectedSet'   => array('one', 'one/two'),
            ),
            array(
                'label'         => __LINE__ .': bar, set no cats, with some cats (noop)',
                'entry'         => 'bar',
                'setCats'       => array(),
                'expectedCats'  => array('one', 'one/two'),
                'expectedSet'   => array(),
            ),
            array(
                'label'         => __LINE__ .': bar, set some cats, with some cats (adds/creates)',
                'entry'         => 'bar',
                'setCats'       => array('one', 'three'),
                'expectedCats'  => array('one', 'one/two', 'three'),
                'expectedSet'   => array('one', 'three'),
            ),
            array(
                'label'         => __LINE__ .': bar, set same cats, with some cats (noop)',
                'entry'         => 'bar',
                'setCats'       => array('one', 'three'),
                'expectedCats'  => array('one', 'one/two', 'three'),
                'expectedSet'   => array('one', 'three'),
            ),
            array(
                'label'         => __LINE__ .': baz, set some cats, with some cats (adds/creates)',
                'entry'         => 'baz',
                'setCats'       => array('one', 'four'),
                'expectedCats'  => array('four', 'one', 'one/two', 'three'),
                'expectedSet'   => array('four', 'one'),
            ),
            array(
                'label'         => __LINE__ .': baz, set some cats, with some cats (adds/deletes)',
                'entry'         => 'baz',
                'setCats'       => array('three', 'four'),
                'expectedCats'  => array('four', 'one', 'one/two', 'three'),
                'expectedSet'   => array('four', 'three'),
            ),
            array(
                'label'         => __LINE__ .': baz, set no cats, with some cats (deletes)',
                'entry'         => 'baz',
                'setCats'       => array(),
                'expectedCats'  => array('four', 'one', 'one/two', 'three'),
                'expectedSet'   => array(),
            ),
        );

        foreach ($tests as $test) {

            // create categories.
            foreach ($test['setCats'] as $id) {
                $category = new P4Cms_Categorization_Dir;
                $category->setId($id)->save();
            }
            $label = $test['label'];
            P4Cms_Categorization_Dir::setEntryCategories($test['entry'], $test['setCats']);

            // check that categories exist as expected
            $categories = P4Cms_Categorization_Dir::fetchAll();
            $ids = array();
            foreach ($categories as $cat) {
                $ids[] = $cat->getId();
            }
            $this->assertSame($test['expectedCats'], $ids, "$label - Expected category layout");

            // check that the categories associated are as expected
            $categories = P4Cms_Categorization_Dir::fetchAllByEntry($test['entry']);
            $ids = array();
            foreach ($categories as $cat) {
                $ids[] = $cat->getId();
            }
            $this->assertSame($test['expectedSet'], $ids, "$label - Expected category associations");
        }
    }

    /**
     * Test fetchAllByEntry with bad parameters.
     */
    public function testFetchAllByEntryBadParams()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'entry'     => null,
                'error'     => 'Cannot get categories; the entry must either be a string or known entry type.',
            ),
            array(
                'label'     => __LINE__ .': empty string',
                'entry'     => '',
                'error'     => 'Cannot get categories; the entry must either be a string or known entry type.',
            ),
            array(
                'label'     => __LINE__ .': string',
                'entry'     => 'string',
                'error'     => null,
                'expected'  => array(),
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            try {
                $categories = P4Cms_Categorization_Dir::fetchAllByEntry($test['entry']);
                if (isset($test['error'])) {
                    $this->fail("$label - unexpected success");
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (InvalidArgumentException $e) {
                if (isset($test['error'])) {
                    $this->assertSame(
                        $test['error'],
                        $e->getMessage(),
                        "$label - Expected error"
                    );
                } else {
                    $this->fail("$label - unexpected argument exception: ". $e->getMessage());
                }
            } catch (Exception $e) {
                $this->fail("$label - Unexpected exception (" . get_class($e) .') :'. $e->getMessage());
            }

            if (!isset($test['error'])) {
                $ids = array();
                foreach ($categories as $category) {
                    $ids[] = $category->getId();
                }
                $this->assertSame(
                    $test['expected'],
                    $ids,
                    "$label - expected category ids"
                );
            }
        }
    }

    /**
     * Test fetchAllByEntry behaviour.
     */
    public function testFetchAllByEntry()
    {
        // attempt to get categories when none are defined.
        $categories = P4Cms_Categorization_Dir::fetchAllByEntry('test');
        $ids = array();
        foreach ($categories as $category) {
            $ids[] = $category->getId();
        }
        $this->assertEquals(array(), $ids, 'Expect no categories when none defined');

        // make some categories, and try again
        $one = P4Cms_Categorization_Dir::store(array('id' => 'one', 'title' => 'One'));
        $two = P4Cms_Categorization_Dir::store(array('id' => 'one/two', 'title' => 'Two in One'));
        $categories = P4Cms_Categorization_Dir::fetchAllByEntry('test');
        $ids = array();
        foreach ($categories as $category) {
            $ids[] = $category->getId();
        }
        $this->assertEquals(array(), $ids, 'Expect no categories when some defined, but not populated');

        // add some entries, one for the target
        $one->addEntry('entry1');
        $two->addEntry('entry2');
        $two->addEntry('test');
        $categories = P4Cms_Categorization_Dir::fetchAllByEntry('test');
        $ids = array();
        foreach ($categories as $category) {
            $ids[] = $category->getId();
        }
        $this->assertEquals(array('one/two'), $ids, 'Expect one category after initial entry creation');

        // add another target entry.
        $one->addEntry('test');
        $categories = P4Cms_Categorization_Dir::fetchAllByEntry('test');
        $ids = array();
        foreach ($categories as $category) {
            $ids[] = $category->getId();
        }
        $this->assertEquals(
            array($one->getId(), $two->getId()),
            $ids,
            'Expect two category after second entry creation'
        );
    }

    /**
     * Test getDepth
     */
    public function testGetDepth()
    {
        $depths   = range(0, 10);
        $category = new P4Cms_Categorization_Dir;
        foreach ($depths as $depth) {
            $id = 'foo' . str_repeat('/foo', $depth);
            $this->assertSame(
                $depth,
                $category->setId($id)->getDepth(),
                "Expected category depth of $depth."
            );
        }
    }

    /**
     * Test get ancestors
     */
    public function testGetAncestors()
    {
        // make a category tree.
        P4Cms_Categorization_Dir::store(array("id" => "foo"));
        P4Cms_Categorization_Dir::store(array("id" => "foo/bar"));
        P4Cms_Categorization_Dir::store(array("id" => "foo/bar/baz"));
        P4Cms_Categorization_Dir::store(array("id" => "foo/bar/baz/bof"));

        // get leaf and grab ancestry.
        $category  = P4Cms_Categorization_Dir::fetch("foo/bar/baz/bof");
        $ancestors = $category->getAncestors();

        // ensure 3 ancestors.
        $this->assertSame(3, $ancestors->count());

        // ensure expected ids.
        $expect = array('foo', 'foo/bar', 'foo/bar/baz');
        $actual = $ancestors->invoke('getId');
        $this->assertSame($expect, $actual, 'Expected ids from getAncestors');

        // test just the ids
        $ids = $category->getAncestorIds();
        $this->assertSame($expect, $ids, 'Expected ids from getAncestorIds');

        $dir = new P4Cms_Categorization_Dir;
        $this->assertSame(
            array(),
            $dir->setId('one')->getAncestorIds(),
            'Expected ids when category has no ancestor'
        );
    }

    /**
     * Test simple accessor/mutator methods.
     */
    public function testAccessorsMutators()
    {
        $dir = new P4Cms_Categorization_Dir;
        $dir->setId('one/two')
            ->setTitle('title')
            ->setDescription('description');

        $this->assertEquals('one/two',      $dir->getId(),          'expected id');
        $this->assertEquals('two',          $dir->getBaseId(),      'expected base id');
        $this->assertEquals('title',        $dir->getTitle(),       'expected title');
        $this->assertEquals('description',  $dir->getDescription(), 'expected description');
    }

    /**
     * Test helper method to create specified content records.
     *
     * @param   array  $entries  An array of id => title for the content records to create.
     * @return  array  An array of the created content entries.
     */
    protected function _makeContent(array $entries)
    {
        $type = new P4Cms_Content_Type();
        $type->setId("basic-page")
             ->setLabel("Basic Page")
             ->setElements(
                array(
                    "title" => array(
                        "type"      => "text",
                        "options"   => array("label" => "Title", "required" => true)
                    ),
                    "description"   => array(
                        "type"      => "text",
                        "options"   => array("label" => "Description")
                    )
                )
             )
             ->save();

        $created = array();
        foreach ($entries as $id => $title) {
            $entry = new P4Cms_Content;
            $entry->setId($id)
                  ->setValue('contentType', 'basic-page')
                  ->setValue('title', $title)
                  ->save();
            $created[] = $entry;
        }

        return $created;

    }
}
