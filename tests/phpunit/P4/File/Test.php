<?php
/**
 * Test methods for the P4 Model Iterator.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_File_Test extends TestCase
{
    /**
     * Test fetchAll filespec handling.
     */
    public function testFetchAllSingular()
    {
        $tests = array(
            array(
                'label' => __LINE__ .': null query',
                'query' => P4_File_Query::create(),
                'error' => 'Cannot fetch files. No filespecs provided in query.',
            ),
            array(
                'label' => __LINE__ .': valid, but nonexistant filespec',
                'query' => P4_File_Query::create()->addFilespec('//depot/foobartesting'),
                'error' => '',
            ),
            array(
                'label' => __LINE__ .': valid, but nonexistant depot filespec',
                'query' => P4_File_Query::create()->addFilespec('//depotadsadsa/foobartesting'),
                'error' => '',
            )
        );

        foreach ($tests as $test) {
            try {
                $files = P4_File::fetchAll($test['query']);
                if ($test['error']) {
                    $this->fail($test['label'] .' - unexpected success');
                }
            } catch (InvalidArgumentException $e) {
                if ($test['error']) {
                    $this->assertSame(
                        $test['error'],
                        $e->getMessage(),
                        $test['label'] .' - Expected error'
                    );
                } else {
                    $this->fail($test['error'] .' - unexpected failure: '.  $e->getMessage());
                }
            } catch (Exception $e) {
                $this->fail($test['label'] .' - Unexpected exception: '.  $e->getMessage());
            }

        }
    }

    /**
     * Test fetchAll with multiple filespecs.
     */
    public function testFetchAllMultiple()
    {
        $basePath = '//depot/';
        $testFiles = $this->_prepareFetchAllTests($basePath, false);

        $query = P4_File_Query::create()->addFilespecs(array($basePath .'*small.txt', $basePath .'medium.jpg'));
        $files = P4_File::fetchAll($query);

        $filenames = array();
        foreach ($files as $file) {
            $filenames[] = $file->getFilespec();
        }
        $this->assertSame(
            array(
                '//depot/another_small.txt',
                '//depot/small.txt',
                '//depot/medium.jpg',
            ),
            $filenames,
            "Expected matching filenames when using multiple filespecs."
        );
    }

    /**
     * Test sync and flush.
     */
    public function testSyncFlush()
    {
        // ensure sync of non-existent file throws exception.
        $file = new P4_File;
        $file->setFilespec('//depot/non-existent-file');
        try {
            $file->sync();
            $this->fail("Excepted exception syncing non-existent file.");
        } catch (P4_File_Exception $e) {
            $this->assertTrue(true);
        }

        // ensure flush of non-existent file throws exception.
        try {
            $file->flush();
            $this->fail("Excepted exception syncing non-existent file.");
        } catch (P4_File_Exception $e) {
            $this->assertTrue(true);
        }

        // create a file
        $file = new P4_File;
        $content = 'Content.';
        $file->setFilespec('//depot/file.txt')
             ->add()
             ->setLocalContents($content)
             ->submit('Add file.');
        $this->assertTrue(
            is_file($file->getLocalFilename()),
            'Expect file to exist after create/submit.'
        );

        // delete client file
        $file->deleteLocalFile();
        $this->assertFalse(
            is_file($file->getLocalFilename()),
            'Expect file to no longer exist after delete.'
        );

        // sync the file; should fail as server thinks we have the file.
        $file->sync();
        $this->assertFalse(
            is_file($file->getLocalFilename()),
            'Expect file to still exist after sync without force.'
        );

        // sync again, with force.
        $file->sync(true);
        $this->assertTrue(
            is_file($file->getLocalFilename()),
            'Expect file to now exist after sync with force.'
        );

        // verify content
        $this->assertSame(
            $content,
            $file->getLocalContents(),
            'Expected content after sync.'
        );

        // revise content and flush; sync should not affect client file.
        $newContent = 'Some new content.';
        $file->setLocalContents($newContent)
             ->flush()
             ->sync();
        $this->assertSame(
            $newContent,
            $file->getLocalContents(),
            'Expected content after flush/sync'
        );

        // force sync again, content should revert to original.
        $file->sync(true);
        $this->assertSame(
            $content,
            $file->getLocalContents(),
            'Expected content after force sync'
        );
    }

    /**
     * Test getBasename.
     */
    public function testGetBasename()
    {
        $file = new P4_File;
        $path = 'a/path/to/a/file.txt';
        $root = $this->p4->getClientRoot();
        $filespec = "$root/$path";
        $file->setFilespec($filespec);

        $this->assertEquals(
            'file.txt',
            $file->getBasename(),
            'Expected basename'
        );
    }

    /**
     * Test getFileSize and getLocalFileSize.
     */
    public function testGetFileSizeAndGetLocalFileSize()
    {

        $basePath = '//depot/testFileSize/';
        $files = $this->_prepareFetchAllTests($basePath);

        $expectedSizes = array(
            'small.txt'         => 13,
            'another_small.txt' => 13,
            'medium.jpg'        => 40,
            'ze_medium_2.jpg'   => 40,
            'large.txt'         => 3599,
            'opened.txt'        => 7,
        );

        foreach ($files as $file) {
            $filename = basename($file->getFilespec());
            if ($filename === 'opened.txt') {
                $this->assertSame(
                    $expectedSizes[$filename],
                    $file->getLocalFileSize(),
                    "Expected filesize for '$filename'"
                );
            } else {
                $this->assertSame(
                    $expectedSizes[$filename],
                    $file->getFilesize(),
                    "Expected filesize for '$filename'"
                );
            }
        }

        // test getting sizes for non-existant files.
        $file = new P4_File;
        $file->setFilespec('//depot/does_not_exist.txt');

        try {
            $size = $file->getFileSize();
            $this->fail("Unexpected success for getFileSize().");
        } catch (P4_File_Exception $e) {
            $this->assertSame(
                'The file does not have a fileSize attribute.',
                $e->getMessage(),
                'Expected error for getFileSize().'
            );
        } catch (Exception $e) {
            $this->fail(
                "Unexpected exception for getFileSize() ("
                . get_class($e) .') :'. $e->getMessage()
            );
        }

        try {
            $size = $file->getLocalFileSize();
            $this->fail("Unexpected success for getLocalFileSize().");
        } catch (P4_File_Exception $e) {
            $this->assertSame(
                'The local file does not exist.',
                $e->getMessage(),
                'Expected error for getLocalFileSize().'
            );
        } catch (Exception $e) {
            $this->fail(
                "Unexpected exception for getLocalFileSize() ("
                . get_class($e) .') :'. $e->getMessage()
            );
        }
    }

    /**
     * Test filename in local-file syntax.
     */
    public function testFilenameInLocalSyntax()
    {
        $file = new P4_File;
        $path = 'a/path/to/a/file.txt';
        $root = $this->p4->getClientRoot();
        $filespec = "$root/$path";
        $file->setFilespec($filespec);

        $this->assertSame(
            $filespec,
            $file->getLocalFilename(),
            'Expected local filename.'
        );

        $this->assertSame(
            "//depot/$path",
            $file->getDepotFilename(),
            'Expected depot filename.'
        );

        // test file object with no filespec set
        $file = new P4_File;
        try {
            $file->getLocalFilename();
            $this->fail('Unexpected success for getLocalFilename with no filespec');
        } catch (P4_File_Exception $e) {
            $this->assertSame(
                'Cannot complete operation, no filespec has been specified',
                $e->getMessage(),
                'Expected error for getLocalFilename with no filespec.'
            );
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception for getLocalFilename with no filespec ('
                . get_class($e) .') :'. $e->getMessage()
            );
        }
        try {
            $file->getDepotFilename();
            $this->fail('Unexpected success for getDepotFilename with no filespec');
        } catch (P4_File_Exception $e) {
            $this->assertSame(
                'Cannot complete operation, no filespec has been specified',
                $e->getMessage(),
                'Expected error for getDepotFilename with no filespec.'
            );
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception for getDepotFilename with no filespec ('
                . get_class($e) .') :'. $e->getMessage()
            );
        }
    }

    /**
     * Test count method.
     */
    public function testCount()
    {
        if (getenv('SKIP_LONG_TESTS')) {
            $this->markTestSkipped('long fetchAll test skipped, because asked');
            return;
        }

        $basePath = '//depot/testFetchOptions/';
        $testFiles = $this->_prepareFetchAllTests($basePath, true);

        $tests = array(
            // sorting tests
            array(
                'label'     => __LINE__ .': defaults',
                'query'     => P4_File_Query::create(),
                'expected'  => 6,
            ),
            array(
                'label'     => __LINE__ .': reversed default sort',
                'query'     => P4_File_Query::create()->setReverseOrder(true),
                'expected'  => 6,
            ),
            array(
                'label'     => __LINE__ .': sort',
                'query'     => P4_File_Query::create()->setSortBy('fileSize'),
                'expected'  => 6,
            ),
            array(
                'label'     => __LINE__ .': reversed sort',
                'query'     => P4_File_Query::create()->setReverseOrder(true)->setSortBy('fileSize'),
                'expected'  => 6,
            ),

            // limit results tests (limits should have no effect)
            array(
                'label'     => __LINE__ .': limit',
                'query'     => P4_File_Query::create()->setMaxFiles(1),
                'expected'  => 1,
            ),
            array(
                'label'     => __LINE__ .': reversed limit',
                'query'     => P4_File_Query::create()->setMaxFiles(1)->setReverseOrder(true),
                'expected'  => 1,
            ),

            // filtering tests
            array(
                'label'     => __LINE__ .': filter fileSize > 1000',
                'query'     => P4_File_Query::create()->setFilter('fileSize > 1000'),
                'expected'  => 1,
            ),
            array(
                'label'     => __LINE__ .': filter fileSize <= 1000',
                'query'     => P4_File_Query::create()->setFilter('fileSize <= 1000'),
                'expected'  => 4,
            ),
            array(
                'label'     => __LINE__ .': filter scrunchy',
                'query'     => P4_File_Query::create()->setFilter('scrunchy'),
                'expected'  => 0,
            ),
            array(
                'label'     => __LINE__ .': filter with existing rowNumber',
                'query'     => P4_File_Query::create()->setFilter('fileSize <= 1000 & rowNumber >= 4'),
                'expected'  => 1,
            ),
            array(
                'label'     => __LINE__ .': filter with rowNumber existing',
                'query'     => P4_File_Query::create()->setFilter('rowNumber >= 5 & fileSize <= 1000'),
                'expected'  => 0,
            ),
            array(
                'label'     => __LINE__ .': filter with existing rowNumber existing',
                'query'     => P4_File_Query::create()->setFilter('fileSize <= 1000 rowNumber >= 2 & fileSize <= 1000'),
                'expected'  => 3,
            ),

            // change and content tests
            array(
                'label'     => __LINE__ .': change 1',
                'query'     => P4_File_Query::create()->setLimitToChangelist(1),
                'expected'  => 1,
            ),

            // opened tests
            array(
                'label'     => __LINE__ .': opened',
                'query'     => P4_File_Query::create()->setLimitToOpened(true),
                'expected'  => 1,
            ),
        );

        foreach ($tests as $test) {
            $label  = $test['label'];
            $file   = new P4_File;
            $count  = null;

            $test['query']->addFilespec($basePath .'...');
            try {
                $count = P4_File::count($test['query']);
            } catch (Exception $e) {
                $this->fail("$label - unexpected failure (". get_class($e) .': '. $e->getMessage());
            }

            if (array_key_exists('dump', $test)) {
                var_dump($file->getConnection()->run('fstat', array('-Ol', '//...')));
                exit;
            }

            $this->assertEquals(
                $test['expected'],
                $count,
                "$label - expected count"
            );
        }
    }

    /**
     * Test count with no filespec.
     */
    public function testCountWithoutFilespec()
    {
        try {
            $count = P4_File::count(new P4_File_Query);
            $this->fail('Unexpected success');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                'Cannot count files. No filespecs provided in query.',
                $e->getMessage(),
                'Expected exception'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected exception ('. get_class($e) .') :'. $e->getMessage());
        }
    }

    /**
     * Test fetchAll options.
     *
     * @todo fixup and remove skip for sorting tests when/if server sorts
     * by size/data properly.
     */
    public function testFetchAllOptions()
    {
        if (getenv('SKIP_LONG_TESTS')) {
            $this->markTestSkipped('long fetchAll test skipped, because asked');
            return;
        }

        $basePath = '//depot/testFetchOptions/';
        $testFiles = $this->_prepareFetchAllTests($basePath, true);

        $tests = array(
            // sorting tests
            array(
                'label'     => __LINE__ .': defaults',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...'),
                'expected'  => array(
                    'another_small.txt',
                    'large.txt',
                    'medium.jpg',
                    'opened.txt',
                    'small.txt',
                    'ze_medium_2.jpg',
                ),
                'content'   => false,
            ),
            array(
                'label'     => __LINE__ .': reversed default sort',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setReverseOrder(true),
                'expected'  => array(
                    'ze_medium_2.jpg',
                    'small.txt',
                    'opened.txt',
                    'medium.jpg',
                    'large.txt',
                    'another_small.txt',
                ),
                'content'   => false,
            ),
            array(
                'label'     => __LINE__ .': filesize sort',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setSortBy(P4_File_Query::SORT_FILE_SIZE),
                'expected'  => array(
                    'opened.txt',
                    'another_small.txt',
                    'small.txt',
                    'medium.jpg',
                    'ze_medium_2.jpg',
                    'large.txt',
                ),
                'content'   => false,
            ),

            array(
                'label'     => __LINE__ .': reversed filesize sort',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setSortBy(P4_File_Query::SORT_FILE_SIZE)
                                                      ->setReverseOrder(true),
                'expected'  => array(
                    'large.txt',
                    'medium.jpg',
                    'ze_medium_2.jpg',
                    'another_small.txt',
                    'small.txt',
                    'opened.txt',
                ),
                'content'   => false,
            ),
            array(
                'label'     => __LINE__ .': date sort',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setSortBy(P4_File_Query::SORT_DATE),
                'expected'  => array(
                    'opened.txt',
                    'small.txt',
                    'medium.jpg',
                    'large.txt',
                    'another_small.txt',
                    'ze_medium_2.jpg',
                ),
                'content'   => false,
            ),
            array(
                'label'     => __LINE__ .': reversed date sort',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setSortBy(P4_File_Query::SORT_DATE)
                                                      ->setReverseOrder(true),
                'expected'  => array(
                    'ze_medium_2.jpg',
                    'another_small.txt',
                    'large.txt',
                    'medium.jpg',
                    'small.txt',
                    'opened.txt',
                ),
                'content'   => false,
            ),
            array(
                'label'     => __LINE__ .': filetype sort',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setSortBy(P4_File_Query::SORT_FILE_TYPE),
                'expected'  => array(
                    'medium.jpg',
                    'ze_medium_2.jpg',
                    'another_small.txt',
                    'large.txt',
                    'opened.txt',
                    'small.txt',
                ),
                'content'   => false,
            ),
            array(
                'label'     => __LINE__ .': reversed filetype sort',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setSortBy(P4_File_Query::SORT_FILE_TYPE)
                                                      ->setReverseOrder(true),
                'expected'  => array(
                    'another_small.txt',
                    'large.txt',
                    'opened.txt',
                    'small.txt',
                    'medium.jpg',
                    'ze_medium_2.jpg',
                ),
                'content'   => false,
            ),
            array(
                'label'     => __LINE__ .': attribute sort',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setSortBy('foo'),
                'expected'  => array(
                    'opened.txt',
                    'ze_medium_2.jpg',
                    'large.txt',
                    'medium.jpg',
                    'another_small.txt',
                    'small.txt',
                ),
                'content'   => false,
            ),
            array(
                'label'     => __LINE__ .': reversed attribute sort',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setSortBy('foo', array(P4_File_Query::SORT_DESCENDING)),
                'expected'  => array(
                    'another_small.txt',
                    'small.txt',
                    'medium.jpg',
                    'large.txt',
                    'ze_medium_2.jpg',
                    'opened.txt',
                ),
                'content'   => false,
            ),
            array(
                'label'     => __LINE__ .': attribute + date sort',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setSortBy(array('foo', P4_File_Query::SORT_DATE)),
                'expected'  => array(
                    'opened.txt',
                    'ze_medium_2.jpg',
                    'large.txt',
                    'medium.jpg',
                    'small.txt',
                    'another_small.txt',
                ),
                'content'   => false,
            ),

            // limit results tests
            array(
                'label'     => __LINE__ .': limit 1',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setMaxFiles(1),
                'expected'  => array(
                    'another_small.txt',
                ),
                'content'   => false,
            ),
            array(
                'label'     => __LINE__ .': reversed limit 1',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setMaxFiles(1)
                                                      ->setReverseOrder(true),
                'expected'  => array(
                    'ze_medium_2.jpg',
                ),
                'content'   => false,
            ),
            array(
                'label'     => __LINE__ .': limit 2',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setMaxFiles(2),
                'expected'  => array(
                    'another_small.txt',
                    'large.txt',
                ),
                'content'   => false,
            ),
            array(
                'label'     => __LINE__ .': reversed limit 2',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setMaxFiles(2)
                                                      ->setReverseOrder(true),
                'expected'  => array(
                    'ze_medium_2.jpg',
                    'small.txt',
                ),
                'content'   => false,
            ),

            // filtering tests
            array(
                'label'     => __LINE__ .': filter fileSize > 1000',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setFilter('fileSize > 1000'),
                'expected'  => array(
                    'large.txt',
                ),
                'content'   => false,
            ),
            array(
                'label'     => __LINE__ .': filter fileSize <= 1000',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setFilter('fileSize <= 1000'),
                'expected'  => array(
                    'another_small.txt',
                    'medium.jpg',
                    'small.txt',
                    'ze_medium_2.jpg',
                ),
                'content'   => false,
            ),

            // change and content tests
            array(
                'label'     => __LINE__ .': change 1',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setLimitToChangelist(1),
                'expected'  => array(
                    basename($testFiles[0]->getFilespec()),
                ),
                'content'   => false,
            ),
            array(
                'label'     => __LINE__ .': change 2',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setLimitToChangelist(2),
                'expected'  => array(
                    basename($testFiles[1]->getFilespec()),
                ),
                'content'   => true,
            ),
            array(
                'label'     => __LINE__ .': change 3',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setLimitToChangelist(3),
                'expected'  => array(
                    basename($testFiles[2]->getFilespec()),
                ),
                'content'   => true,
            ),
            array(
                'label'     => __LINE__ .': change 4',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setLimitToChangelist(4),
                'expected'  => array(
                    basename($testFiles[3]->getFilespec()),
                ),
                'content'   => true,
            ),
            array(
                'label'     => __LINE__ .': change 5',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setLimitToChangelist(5),
                'expected'  => array(
                    basename($testFiles[4]->getFilespec()),
                ),
                'content'   => true,
            ),
            array(
                'label'     => __LINE__ .': change default',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setLimitToChangelist('default'),
                'expected'  => array(
                    basename($testFiles[5]->getFilespec()),
                ),
                'content'   => true,
            ),

            // opened tests
            array(
                'label'     => __LINE__ .': opened',
                'query'     => P4_File_Query::create()->addFilespec($basePath .'...')
                                                      ->setLimitToOpened(true),
                'expected'  => array(
                    basename($testFiles[5]->getFilespec()),
                ),
                'content'   => true,
            ),
        );

        $showSizes = 0;
        foreach ($tests as $test) {
            $label = $test['label'];
            $file  = new P4_File;

            if (array_key_exists('dumpQuery', $test) and $test['dumpQuery']) {
                print var_export($test['query']->toArray(), true);
            }
            try {
                $files = P4_File::fetchAll($test['query']);
            } catch (Exception $e) {
                $this->fail("$label - unexpected failure: ". $e->getMessage());
            }

            if (array_key_exists('dump', $test)) {
                var_dump($file->getConnection()->run('info'));
                var_dump($file->getConnection()->run('fstat', array('-Oal', '//depot/testFetchOptions/...')));
                exit;
            }

            $this->assertEquals(
                count($test['expected']),
                count($files),
                "$label - expected file count"
            );
            $fileNames = array();
            foreach ($files as $file) {
                $filename = $file->getFilespec();
                $filesize = $file->getLocalFileSize();
                $fileNames[] = basename($file->getFilespec());
                if ($showSizes) print "'$filename' is '$filesize' bytes.\n";
            }
            $showSizes = 0;

            $this->assertSame(
                $test['expected'],
                $fileNames,
                "$label - expected file list"
            );

            if ($test['content']) {
                for ($i = 0; $i < count($test['expected']); $i++) {
                    $method = ($testFiles[$test['expected'][$i]]->isOpened()) ? 'getLocalContents' : 'getDepotContents';
                    $this->assertSame(
                        $testFiles[$test['expected'][$i]]->$method(),
                        $files[$i]->$method(),
                        "$label - Expected content for file #$i"
                    );
                }
            }
        }
    }

    /**
     * Verify we are doing a proper natural order sort on attributes
     */
    public function testNaturalSort()
    {
        $basePath = "//depot/test/";
        $file = new P4_File;
        $file->setFilespec($basePath .'10.txt')
             ->open()
             ->setLocalContents('i am a file')
             ->setAttribute('foo', 'give-me-a-10')
             ->submit('10');
        $file->setFilespec($basePath .'2.txt')
             ->open()
             ->setLocalContents('i am a file')
             ->setAttribute('foo', 'give-me-a-2')
             ->submit('2');
        $file->setFilespec($basePath .'1.txt')
             ->open()
             ->setLocalContents('i am a file')
             ->setAttribute('foo', 'give-me-a-1')
             ->submit('1');
        $file->setFilespec($basePath .'just1.txt')
             ->open()
             ->setLocalContents('i am a file')
             ->setAttribute('foo', '1')
             ->submit('just digit 1');
        $file->setFilespec($basePath .'a.txt')
             ->open()
             ->setLocalContents('i am a file')
             ->setAttribute('foo', 'a')
             ->submit('a');
        $file->setFilespec($basePath .'Aa.txt')
             ->open()
             ->setLocalContents('i am a file')
             ->setAttribute('foo', 'Aa')
             ->submit('A');
        $file->setFilespec($basePath .'z.txt')
             ->open()
             ->setLocalContents('i am a file')
             ->setAttribute('foo', 'z')
             ->submit('z');
        $file->setFilespec($basePath .'Zz.txt')
             ->open()
             ->setLocalContents('i am a file')
             ->setAttribute('foo', 'Zz')
             ->submit('Zz');

        $foos = P4_File::fetchAll(
            P4_File_Query::create()->setFilespecs($basePath . "...")->setSortBy('foo')
        );

        $this->assertSame(
            array(
                '1',
                'a',
                'Aa',
                'give-me-a-1',
                'give-me-a-2',
                'give-me-a-10',
                'z',
                'Zz'
            ),
            $foos->invoke('getAttribute', array('foo')),
            'Expecting sorted attributes'
        );
    }

    /**
     * Test an already opened file.
     */
    public function testAlreadyOpened()
    {
        $file = new P4_File;
        $file->setFilespec('//depot/notAlreadyOpened.txt');
        $this->assertFalse($file->exists($file->getFilespec()), 'Expect file to not exist #1.');
        $this->assertFalse($file->isOpened(), 'Expect file to not be opened #1.');

        $file->open()->setLocalContents('Some content.');
        $this->assertFalse($file->exists($file->getFilespec()), 'Still expect file to not exist #1.');
        $this->assertTrue($file->isOpened(), 'Expect file to be opened #1.');

        $file->open();
        $this->assertFalse($file->exists($file->getFilespec()), 'Still expect file to not exist #1.');
        $this->assertTrue($file->isOpened(), 'Expect file to still be opened #1.');
        $file->revert();

        // once again, with initial add instead of open
        $file = new P4_File;
        $file->setFilespec('//depot/notAlreadyOpened.txt');
        $this->assertFalse($file->exists($file->getFilespec()), 'Expect file to not exist #2.');
        $this->assertFalse($file->isOpened(), 'Expect file to not be opened. #2');

        $file->add(null, 'binary')->setLocalContents('Some content.');
        $this->assertFalse($file->exists($file->getFilespec()), 'Still expect file to not exist #2.');
        $this->assertTrue($file->isOpened(), 'Expect file to be opened. #2');

        $file->open();
        $this->assertFalse($file->exists($file->getFilespec()), 'Still expect file to not exist #2.');
        $this->assertTrue($file->isOpened(), 'Expect file to still be opened #2.');
    }

    /**
     * Test file deletion.
     *
     * @todo enable when appropriate error handling exists
     */
    public function testFileDeletion()
    {
        // first, create a file to delete.
        $file = new P4_File;
        $file->setFilespec('//depot/a_file_to_delete.txt');
        $this->assertFalse($file->exists($file->getFilespec()), 'Expect file to not exist.');
        $file->open()
             ->setLocalContents('Some content to delete.')
             ->submit('Adding a file to delete.');
        $this->assertTrue((bool)$file->exists($file->getFilespec()), 'Expect file to exist.');
        $this->assertFalse($file->isDeleted(), 'Expect file to not have deleted status after create');

        // expect file to still exist if exclude deleted is true.
        $this->assertTrue(
            (bool)P4_File::exists($file->getFilespec(), null, true),
            "Expected file to exist with excluded deleted = true."
        );

        // test deleting the opened file.
        $file->open();
        $this->assertTrue($file->isOpened(), 'Expect file to be opened.');

        // first, with force set to false
        try {
            $file->delete(null, false);
            $this->fail('Unexpected success deleting an opened file without force.');
        } catch (P4_File_Exception $e) {
            $this->assertSame(
                "Failed to open file for delete: //depot/a_file_to_delete.txt"
                . " - can't delete (already opened for edit)",
                $e->getMessage(),
                'Expected error message deleting an opened file without force.'
            );
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (Exception $e) {
            $this->fail('Unexpected exception on delete opened file: '.  $e->getMessage());
        }

        // again with force defaulting to true
        try {
            $file->delete();
        } catch (Exception $e) {
            $this->fail('Unexpected exception on delete opened file: '.  $e->getMessage());
        }

        // test deleting the file again.
        try {
            $file->delete();
        } catch (Exception $e) {
            $this->fail('Unexpected exception on delete opened file again: '.  $e->getMessage());
        }

        // now submit the deletion.
        $file->submit('Delete the file.');
        $this->assertTrue((bool)$file->exists($file->getFilespec()), 'Expect file to still exist.');
        $this->assertTrue(
            $file->hasStatusField('headAction')
            && $file->getStatus('headAction') == 'delete',
            'Expect file to be deleted'
        );
        $this->assertTrue($file->isDeleted(), 'Expect file to have deleted status after delete.');

        // expect file to not exist if exclude deleted is true.
        $this->assertFalse(
            P4_File::exists($file->getFilespec(), null, true),
            "Expected file to not exist with excluded deleted = true."
        );

        // test deleting the file again after submission.
        try {
            $file->delete();
            $this->fail('Unexpected success deleting an already deleted file');
        } catch (P4_File_Exception $e) {
            $this->assertSame(
                "Failed to open file for delete: //depot/a_file_to_delete.txt - file(s) not on client.",
                $e->getMessage(),
                'Expected error message deleting an already deleted file.'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected exception on delete deleted file: '.  $e->getMessage());
        }
    }

    /**
     * Test edit after delete.
     */
    public function testEditAfterDelete()
    {
        $file = new P4_File;
        $file->setFilespec('//depot/file.txt')
             ->add()
             ->setLocalContents('Some content.')
             ->submit('Created it.');

        // delete the file, and then attempt to edit it with force=false.
        $file->delete();
        try {
            $file->edit(null, null, false);
            $this->fail('Unexpected success editing a file opened for delete');
        } catch (P4_File_Exception $e) {
            $this->assertSame(
                "Failed to open file for edit: //depot/file.txt - can't edit (already opened for delete)",
                $e->getMessage(),
                'Expected error editing a file opened for delete.'
            );
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception editing a file opened for delete ('
                . get_class($e) .'): '. $e->getMessage()
            );
        }

        // try to edit again, with force set. Should succeed.
        $file->edit(null, null, true)
             ->setLocalContents('New text.')
             ->submit('Update after delete.');
    }

    /**
     * Test invalid submit description.
     */
    public function testInvalidSubmitDescription()
    {
        $tests = array(
            array(
                'label'         => __LINE__ .': null description',
                'description'   => null,
                'error'         => new InvalidArgumentException(
                    'Cannot submit. Description must be a non-empty string.'
                ),
            ),
            array(
                'label'         => __LINE__ .': empty description',
                'description'   => '',
                'error'         => new InvalidArgumentException(
                    'Cannot submit. Description must be a non-empty string.'
                ),
            ),
            array(
                'label'         => __LINE__ .': array description',
                'description'   => array('1', '2'),
                'error'         => new InvalidArgumentException(
                    'Cannot submit. Description must be a non-empty string.'
                ),
            ),
            array(
                'label'         => __LINE__ .': 0 description',
                'description'   => 0,
                'error'         => new InvalidArgumentException(
                    'Cannot submit. Description must be a non-empty string.'
                ),
            ),
            array(
                'label'         => __LINE__ .': 1 description',
                'description'   => 1,
                'error'         => new InvalidArgumentException(
                    'Cannot submit. Description must be a non-empty string.'
                ),
            ),
            array(
                'label'         => __LINE__ .': string description',
                'description'   => 'abcde',
                'error'         => false,
            ),
        );

        $counter = 0;
        foreach ($tests as $test) {
            $label = $test['label'];
            $file = new P4_File;
            $file->setFilespec('//depot/file'. $counter++ .'.txt')
                 ->add()
                 ->setLocalContents('Content');
            try {
                $file->submit($test['description']);
                if ($test['error']) {
                    $this->fail("$label - unexpected success");
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (Exception $e) {
                if ($test['error']) {
                    $this->assertSame(
                        get_class($test['error']),
                        get_class($e),
                        "$label - expected exception class: ". $e->getMessage()
                    );
                    $this->assertSame(
                        $test['error']->getMessage(),
                        $e->getMessage(),
                        "$label - expected error message."
                    );
                } else {
                    $this->fail(
                        "$label - unexpected exception ("
                        . get_class($e) .') :'. $e->getMessage()
                    );
                }
            }
        }
    }

    /**
     * Test setStatusCache.
     */
    public function testSetStatusCache()
    {
        $file = new P4_File();
        $file->setFilespec('//depot/file')->add()->setLocalContents('A')->submit('A');
        $originalStatus = $modifiedStatus = $file->getStatus();
        foreach ($modifiedStatus as $key => $value) {
            $modifiedStatus[$key] = 'head';
        }
        $file->setStatusCache($modifiedStatus);
        $this->assertSame(
            $modifiedStatus,
            $file->getStatus(),
            'Expected modified status'
        );

        $file->clearStatusCache();
        $this->assertSame(
            $originalStatus,
            $file->getStatus(),
            'Expected original status'
        );

        try {
            $file->setStatusCache(1);
            $this->fail('Unexpected success setting numeric status');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                'Cannot set status cache. Status must be an array.',
                $e->getMessage(),
                'Expected error setting numeric status'
            );
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception setting numeric status ('
                . get_class($e) .') '. $e->getMessage()
            );
        }
    }

    /**
     * Test open attributes.
     */
    public function testOpenAttributes()
    {
        // setup a file
        $file = new P4_File;
        $file->setFilespec('//depot/attribute_test.txt');
        $this->assertFalse($file->exists($file->getFilespec()), 'Expect file to not exist.');
        $file->open();

        // test that it has no attributes
        $attributes = $file->getAttributes();
        $this->assertSame(0, count($attributes), 'Expected attribute count on new file.');

        // verify lack of attribute
        $this->assertFalse($file->hasAttribute('foobar'), 'Expect depot foobar to not exist.');
        $this->assertFalse($file->hasOpenAttribute('foobar'), 'Expect client foobar to not exist.');

        // set the attribute
        $file->setAttribute('foobar', 'bazbar', false, false);

        // check that set attribute requires a string
        try {
            $file->setAttribute('fooerror', 2, false, false);
            $this->fail('Unexpected success setting attribute without string');
        } catch (PHPUnit_Framework_AssertionFailedError $e) {
            $this->fail($e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(
                'Cannot set attribute. Value must be a string.',
                $e->getMessage(),
                'Expected exception setting attribute without string'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected exception ('. get_class($e) .': '.  $e->getMessage());
        }

        // check that depot attribute is not set
        $attributes = $file->getAttributes(false);
        $this->assertSame(0, count($attributes), 'Expected depot attribute count after setting attribute.');

        // check that client attribute is set
        $attributes = $file->getAttributes(true);
        $this->assertSame(1, count($attributes), 'Expected client attribute count after setting attribute.');
        $this->assertTrue($file->hasOpenAttribute('foobar'), 'Expect client foobar to exist.');
        $this->assertFalse($file->hasAttribute('foobar'), 'Expect depot foobar to not exist.');

        // verify the attribute value
        $this->assertSame(
            'bazbar',
            $file->getOpenAttribute('foobar'),
            'Expected client foobar value.'
        );
        try {
            $value = $file->getAttribute('foobar');
            $this->fail('Unexpected success getting depot foobar.');
        } catch (P4_File_Exception $e) {
            $this->assertSame(
                "Can't fetch status. The requested field "
                . "('attr-foobar') does not exist.",
                $e->getMessage(),
                'Expected exception using getAttribute on open attribute'
            );
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception when getting open attribute: '.  $e->getMessage()
                ."\n$e"
            );
        }

        // clear the attribute
        $file->clearAttribute('foobar', false);

        // verify lack of attribute
        $this->assertFalse($file->hasOpenAttribute('foobar'), 'Expect client foobar to no longer exist.');
        $this->assertFalse($file->hasAttribute('foobar'), 'Expect depot foobar to still not exist.');
    }

    /**
     * Test depot attributes.
     */
    public function testDepotAttributes()
    {
        // setup a file
        $file = new P4_File;
        $file->setFilespec('//depot/attribute_test.txt');
        $this->assertFalse($file->exists($file->getFilespec()), 'Expect file to not exist.');
        $file->open()
             ->setLocalContents('Some content.')
             ->submit('Attribute file for testing');

        // verify lack of attribute
        $this->assertFalse($file->hasOpenAttribute('foobar'), 'Expect client foobar to not exist.');
        $this->assertFalse($file->hasAttribute('foobar'), 'Expect depot foobar to not exist.');

        // set the attribute
        $file->setAttribute('foobar', 'bazbar', false, true);

        // check that client attribute is not set
        $attributes = $file->getAttributes(true);
        $this->assertSame(0, count($attributes), 'Expected client attribute count after setting attribute.');

        // check that depot attribute is set
        $attributes = $file->getAttributes(false);
        $this->assertSame(1, count($attributes), 'Expected depot attribute count after setting attribute.');
        $this->assertFalse($file->hasOpenAttribute('foobar'), 'Expect client foobar to not exist.');
        $this->assertTrue($file->hasAttribute('foobar'), 'Expect depot foobar to exist.');

        // verify the attribute value
        $this->assertSame(
            'bazbar',
            $file->getAttribute('foobar'),
            'Expected depot foobar value.'
        );
        try {
            $value = $file->getOpenAttribute('foobar');
            $this->fail('Unexpected success getting client foobar.');
        } catch (P4_File_Exception $e) {
            $this->assertSame(
                "Can't fetch status. The requested field "
                . "('openattr-foobar') does not exist.",
                $e->getMessage(),
                'Expected exception using getOpenAttribute on attribute'
            );
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception when getting attribute: '.  $e->getMessage()
                ."\n$e"
            );
        }

        // clear the attribute
        $file->clearAttribute('foobar', true);

        // verify lack of attribute
        $this->assertFalse($file->hasOpenAttribute('foobar'), 'Expect client foobar to still not exist.');
        $this->assertFalse($file->hasAttribute('foobar'), 'Expect depot foobar to no longer exist.');
    }


    /**
     * Test attribute propagation.
     */
    public function testAttributePropagation()
    {
        // setup a file
        $file = new P4_File;
        $file->setFilespec('//depot/attribute_test.txt');
        $this->assertFalse($file->exists($file->getFilespec()), 'Expect file to not exist.');
        $file->open();

        // verify lack of attribute
        $this->assertFalse($file->hasOpenAttribute('foobar'), 'Expect client foobar to not exist.');
        $this->assertFalse($file->hasAttribute('foobar'), 'Expect depot foobar to not exist.');

        // set the attribute with propagate, and test existence
        $file->setAttribute('foobar', 'bazbar', true, false);
        $this->assertTrue($file->hasOpenAttribute('foobar'), 'Expect client foobar to exist.');
        $this->assertFalse($file->hasAttribute('foobar'), 'Expect depot foobar to not exist.');
        $this->assertSame(
            'bazbar',
            $file->getOpenAttribute('foobar'),
            'Expected client foobar value.'
        );

        // set an additional attribute without propagate, and test existence
        $file->setAttribute('nopropagate', 'loseme', false, false);
        $this->assertTrue($file->hasOpenAttribute('nopropagate'), 'Expect client nopropagate to exist.');
        $this->assertFalse($file->hasAttribute('nopropagate'), 'Expect depot nopropagate to not exist.');
        $this->assertSame(
            'loseme',
            $file->getOpenAttribute('nopropagate'),
            'Expected client nopropagate value.'
        );

        // submit the file #1
        $file->setLocalContents('Some content.')
             ->submit('Attribute file for testing');

        // use a fresh file object, and verify attributes
        $file2 = new P4_File;
        $file2->setFilespec('//depot/attribute_test.txt');
        $this->assertFalse($file2->hasOpenAttribute('foobar'), 'Expect client foobar to no longer exist.');
        $this->assertTrue($file2->hasAttribute('foobar'), 'Expect depot foobar to exist now.');
        $this->assertSame(
            'bazbar',
            $file2->getAttribute('foobar'),
            'Expected depot foobar value.'
        );
        // nopropagate should only be in depot, for this revision
        $this->assertFalse($file2->hasOpenAttribute('nopropagate'), 'Expect client nopropagate to no longer exist.');
        $this->assertTrue($file2->hasAttribute('nopropagate'), 'Expect depot nopropagate to exist now.');

        // now open the file and test whether the depot attribute has been opened
        $file2->open();
        $this->assertTrue($file2->hasOpenAttribute('foobar'), 'Expect client foobar to have been opened.');
        $this->assertTrue($file2->hasAttribute('foobar'), 'Expect depot foobar to exist now.');
        $this->assertSame(
            'bazbar',
            $file2->getOpenAttribute('foobar'),
            'Expected client foobar value.'
        );
        // make sure nopropagate does not get promoted to client
        $this->assertFalse(
            $file2->hasOpenAttribute('nopropagate'),
            'Expect client nopropagate to not exist after open.'
        );

        // submit the file #2, and make sure attribute propagates
        $file2->setLocalContents('Some new content.')
              ->submit('Change attribute file content');

        // use a fresh file object
        $file3 = new P4_File;
        $file3->setFilespec('//depot/attribute_test.txt');

        // verify original attribute exists
        $this->assertFalse($file3->hasOpenAttribute('foobar'), 'Expect client foobar to still not exist.');
        $this->assertTrue($file3->hasAttribute('foobar'), 'Expect depot foobar to still exist.');
        $this->assertSame(
            'bazbar',
            $file3->getAttribute('foobar'),
            'Expected client foobar value.'
        );

        // now open the file and test whether the depot attribute has been opened
        $file3->open();
        $this->assertTrue($file3->hasOpenAttribute('foobar'), 'Expect client foobar to have been opened.');
        $this->assertTrue($file3->hasAttribute('foobar'), 'Expect depot foobar to exist now.');
        $this->assertSame(
            'bazbar',
            $file3->getOpenAttribute('foobar'),
            'Expected client foobar value.'
        );

        // verify nopropagate attribute does not exist
        $this->assertFalse(
            $file3->hasOpenAttribute('nopropagate'),
            'Expect client nopropagate to still not exist.'
        );
        $this->assertFalse(
            $file3->hasAttribute('nopropagate'),
            'Expect depot nopropagate to no longer exist.'
        );

        // clear attribute at depot
        $file3->clearAttribute('foobar', false);
        $this->assertFalse(
            $file3->hasOpenAttribute('foobar'),
            'Expect client foobar to not exist after clear.'
        );
        $this->assertTrue(
            $file3->hasAttribute('foobar'),
            'Expect depot foobar to still exist after clear.'
        );

        // submit an update to verify lack of propagation
        $file3->setLocalContents('Final content.');
        $file3->submit('Update attribute file content');

        // use a fresh file object
        $file4 = new P4_File;
        $file4->setFilespec('//depot/attribute_test.txt');

        // verify depot attribute still does not exist
        $this->assertFalse(
            $file4->hasAttribute('foobar'),
            'Expect depot foobar to still be cleared.'
        );

        // verify non-existant depot attribute does not get promoted to client
        // after opening
        $file4->open();
        $this->assertFalse(
            $file4->hasOpenAttribute('foobar'),
            'Expect client foobar to still, still, still not exist.'
        );
    }

    /**
     * Test setAttributes.
     */
    public function testSetAttributes()
    {
        // setup a file
        $file = new P4_File;
        $file->setFilespec('//depot/multi_attribute_test.txt');
        $this->assertFalse($file->exists($file->getFilespec()), 'Expect file to not exist.');
        $file->open();

        // verify lack of attribute
        $attributes = $file->getAttributes(true);
        $this->assertSame(0, count($attributes), 'Expected initial attribute count.');

        // first, verify that we get an exception unless the
        // attributes array is an array
        try {
            $file->setAttributes(null);
            $this->fail('Unexpected success without attributes.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                "Can't set attributes. Attributes must be an array.",
                $e->getMessage(),
                'Expected error message.'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected exception with no attributes: '.  $e->getMessage());
        }

        // set a list of client, no-propagate attributes
        $attributeList = array(
            'foobar'    => 'bazbar',
            'another'   => 'value',
            'third'     => 'three',
        );
        $file->setAttributes($attributeList, false, false);

        $attributes = $file->getAttributes(true);
        $this->assertSame(3, count($attributes), 'Expected attribute count after set.');

        foreach ($attributeList as $key => $value) {
            $this->assertTrue(
                $file->hasOpenAttribute($key),
                "Expect client '$key' to be set."
            );
            $this->assertSame(
                $value,
                $file->getOpenAttribute($key),
                "Exected value for '$key'"
            );
        }

        // test that invalid attributes are rejected.
        $tests = array(
            '1234',
            '-test',
            'test foo',
            'test*',
            'test...',
            'test#',
            'test@',
            123,
            'foo/bar',
            "test\x00",
            array("lkasdfj\tasdf"),
        );
        for ($i = 0; $i < count($tests); $i++) {
            try {
                $file->setAttribute($tests[$i], 'foo');
                $this->fail('Test #' . $i . ': Unexpected success setting invalid attribute.');
            } catch (InvalidArgumentException $e) {
                $this->assertTrue(true);
            } catch (Exception $e) {
                $this->fail('Test #' . $i . ': Unexpected exception setting invalid attribute.');
            }
        }
    }

    /**
     * test deleteLocalFile.
     */
    public function testDeleteLocalFile()
    {
        $file = new P4_File;
        $filespec = '//depot/a_file_to_delete.txt';
        $file->setFilespec($filespec);
        $this->assertFalse($file->exists($filespec), 'Expect depot file to not exist.');
        $this->assertFalse(file_exists($file->getLocalFilename()), 'Expect local file to not exist.');

        try {
            $file->deleteLocalFile();
            $this->fail('Unexpected success deleting file that does not exist.');
        } catch (P4_File_Exception $e) {
            $this->assertSame(
                "Cannot delete local file. File does not exist.",
                $e->getMessage(),
                'Expected error deleting a non-existant file'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected exception deleting non-existant file: '.  $e->getMessage());
        }

        // create file
        $file->open()
             ->setLocalContents('Some file content.')
             ->submit('Establish file.');
        $this->assertTrue((bool)$file->exists($filespec), 'Expect depot file to exist.');
        $this->assertTrue((bool)file_exists($file->getLocalFilename()), 'Expect local file to exist.');
        $this->assertFalse($file->isDeleted(), 'Expect file to not have deleted status after create');

        // delete the local file
        $file->deleteLocalFile();
        $this->assertTrue((bool)$file->exists($filespec), 'Expect depot file to exist.');
        $this->assertFalse(file_exists($file->getLocalFilename()), 'Expect local file to no longer exist.');
        $this->assertFalse($file->isDeleted(), 'Expect file to not have deleted status after deleting local file');
    }

    /**
     * Test opening for add.
     */
    public function testAdd()
    {
        $file = new P4_File;
        $filespec = '//depot/a_file_to_delete.txt';
        $file->setFilespec($filespec);
        $this->assertFalse($file->exists($filespec), 'Expect depot file to not exist.');
        $this->assertFalse(file_exists($file->getLocalFilename()), 'Expect local file to not exist.');

        $content = 'Some content.';
        $bytes = file_put_contents($file->getLocalFilename(), $content);
        $this->assertSame(strlen($content), $bytes, 'Expected content length');

        // open for add
        $object = $file->add();
        $this->assertSame($file, $object, 'Expect fluent interface return');
        $this->assertTrue($file->isOpened(), 'Expect file to be opened.');

        // open it again
        $object = $file->add();

        // revert file
        $file->revert();
        $this->assertFalse($file->isOpened(), 'Expect file to not be opened.');

        // add with type
        $object = $file->add(null, 'text');
        $this->assertTrue($file->isOpened(), 'Expect file to be opened again.');

        // submit the file, open it, and then try to add it.
        $file->submit('adding the file');
        $file->open();
        try {
            $file->add();
            $this->fail('Unexpected success adding a file opened for edit.');
        } catch (P4_File_Exception $e) {
            $this->assertSame(
                "Failed to open file for add: //depot/a_file_to_delete.txt - can't add (already opened for edit)",
                $e->getMessage(),
                'Expected error adding a file opened for edit.'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected exception adding a file opened for edit: '.  $e->getMessage());
        }

        // test adding into a specific change.
        $change = new P4_Change;
        $change->setDescription('test')->save();
        $file = new P4_File;
        $file->setFilespec('//depot/testeroo')
             ->touchLocalFile()
             ->add($change->getId());
        $this->assertSame(
            intval($file->getStatus('change')),
            $change->getId(),
            "Expected file to be open in specified change."
        );
        $this->assertSame(
            array('//depot/testeroo'),
            $change->getFiles(),
            "Expected file in change."
        );

        // test adding into a different change.
        $change = new P4_Change;
        $change->setDescription('test2')->save();
        $file->add($change->getId());
        $this->assertSame(
            intval($file->getStatus('change')),
            $change->getId(),
            "Expected file to be open in specified change."
        );
    }

    /**
     * Test fetching a file, plus content cache manipulation.
     */
    public function testFetch()
    {
        $file = new P4_File;
        $filespec = '//depot/a_file_to_fetch.txt';

        // test non-existant file
        $file->setFilespec($filespec);
        try {
            $theFile = P4_File::fetch($filespec);
            $this->fail('Unexpected success fetching a non-existant file.');
        } catch (P4_File_NotFoundException $e) {
            $this->assertSame(
                "Cannot fetch file '$filespec'. File does not exist.",
                $e->getMessage(),
                'Expected error while fetching a non-existant file.'
            );
        } catch (Exception $e) {
            $this->fail(
                'Unexpected exception fetching a non-existant file: '
                . $e->getMessage()
            );
        }

        // now make the file exist
        $file->add();
        $contents = 'This file has some content.';
        $file->setLocalContents($contents);
        $file->submit('Make the file available for fetching.');

        $theFile = P4_File::fetch($filespec);
        $this->assertSame($contents, $theFile->getDepotContents(), 'Expected content.');

        // manipulate the content cache
        $newContents = 'And now for something comepletely different.';
        $theFile->setContentCache($newContents);
        $this->assertSame($newContents, $theFile->getDepotContents(), 'Expected new content.');

        // clear the content cache, original content should then be available
        $theFile->clearContentCache();
        $this->assertSame($contents, $theFile->getDepotContents(), 'Expected content.');

        // test manipulation of client content
        $theFile->open();
        $clientContent = 'Different client content.';
        file_put_contents($theFile->getLocalFilename(), $clientContent);
        $this->assertSame($clientContent, $theFile->getLocalContents(), 'Expected client content.');
        $this->assertSame($contents, $theFile->getDepotContents(), 'Expected depot content to be unchanged.');
    }

    /**
     * Test getLocalContent.
     */
    public function testGetLocalContents()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': no setup',
                'filespec'  => null,
                'add'       => false,
                'content'   => null,
                'error'     => new P4_File_Exception(
                    'Cannot complete operation, no filespec has been specified'
                ),
            ),
            array(
                'label'     => __LINE__ .': only filespec',
                'filespec'  => '//depot/file',
                'add'       => false,
                'content'   => null,
                'error'     => new P4_File_Exception(
                    'Cannot get local file contents. Local file does not exist.'
                ),
            ),
            array(
                'label'     => __LINE__ .': filespec + content',
                'filespec'  => '//depot/file',
                'add'       => true,
                'content'   => 'Some content.',
                'error'     => false,
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];

            // prep test conditions
            $file = new P4_File;
            if (isset($test['filespec'])) {
                $file->setFilespec($test['filespec']);
            }
            if (isset($test['content'])) {
                $file->add();
                $file->setLocalContents($test['content']);
            }

            try {
                $content = $file->getLocalContents();
                if ($test['error']) {
                    $this->fail("$label - Unexpected success");
                }
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (Exception $e) {
                if (!$test['error']) {
                    $this->fail(
                        "$label - Unexpected exception ("
                        . get_class($e) .') '. $e->getMessage()
                    );
                } else {
                    $this->assertSame(
                        get_class($test['error']),
                        get_class($e),
                        "$label - Expected error class"
                    );
                    $this->assertSame(
                        $test['error']->getMessage(),
                        $e->getMessage(),
                        "$label - Expected error message"
                    );
                }
            }

            if (!$test['error']) {
                $this->assertSame(
                    $test['content'],
                    $content,
                    "$label - Expected content"
                );
            }
        }
    }

    /**
     * Test annotated content.
     */
    public function testAnnotatedContent()
    {
        // make a file with several lines
        $lines = array(
            "The first line in the file." . PHP_EOL,
            "The second line for testing." . PHP_EOL,
            "And another for completion.",
        );
        $content = join('', $lines);

        /**
         * Becuase we are using p4 print instead of syncing down the
         * file, the newline replacement will not happen on Windows, causing
         * the test to fail on Windows but pass on Linux.
         */
        $expectedContent = str_replace("\r", '', $content);

        $file = new P4_File;
        $file->setFilespec('//depot/annotated_file.txt')
             ->add(null, 'text')
             ->setLocalContents($content, $lines)
             ->submit('Add the first version');
        $this->assertSame($expectedContent, $file->getDepotContents(), 'Expected content #1');

        $annotations = array();
        foreach ($lines as $line) {
            $annotations[] = array(
                'upper' => '1',
                'lower' => '1',
                'data'  => $line,
            );
        }
        $this->assertSame($annotations, $file->getAnnotateContent(), 'Expected annotate #1');

        // exercise the cache
        $this->assertSame($annotations, $file->getAnnotateContent(), 'Expected annotate #2');
        $file->clearAnnotateCache();
        $this->assertSame($annotations, $file->getAnnotateContent(), 'Expected annotate #2');
    }

    /**
     * Test reopen.
     */
    public function testReopen()
    {
        $file = new P4_File;
        $file->setFilespec('//depot/file.txt')
             ->add(null, 'binary')
             ->setLocalContents('Content.');

        $this->assertSame(
            'binary',
            $file->getStatus('type'),
            'Expect the type explicitly set.'
        );

        $tests = array(
            array(
                'label'     => __LINE__ .': no params',
                'change'    => null,
                'type'      => null,
                'error'     => new InvalidArgumentException(
                    'Cannot reopen file. You must provide a change and/or a filetype.'
                ),
                'expect'    => false,
            ),
            array(
                'label'     => __LINE__ .': default change',
                'change'    => 'default',
                'type'      => null,
                'error'     => false,
                'expect'    => 'binary',
            ),
            array(
                'label'     => __LINE__ .': bogus change',
                'change'    => 'bogus',
                'type'      => null,
                'error'     => new P4_Connection_CommandException(
                    "Command failed: Invalid changelist number 'bogus'."
                ),
                'expect'    => 'binary',
            ),
            array(
                'label'     => __LINE__ .': binary type',
                'change'    => null,
                'type'      => 'binary',
                'error'     => false,
                'expect'    => 'binary',
            ),
            array(
                'label'     => __LINE__ .': text type',
                'change'    => null,
                'type'      => 'text',
                'error'     => false,
                'expect'    => 'text',
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            try {
                $file->reopen($test['change'], $test['type']);
                if ($test['error']) {
                    $this->fail("$label - unexpected success");
                }
            } catch (Exception $e) {
                if ($test['error']) {
                    $this->assertSame(
                        get_class($test['error']),
                        get_class($e),
                        "$label - Expected exception class: ". $e->getMessage()
                    );
                    $this->assertSame(
                        $test['error']->getMessage(),
                        rtrim($e->getMessage()),
                        "$label - Expected exception message."
                    );
                } else {
                    $this->fail(
                        "$label - unexpected exception ("
                        . get_class($e) .'): '. $e->getMessage()
                    );
                }
            }

            if (!$test['error']) {
                $this->assertSame(
                    $test['expect'],
                    $file->getStatus('type'),
                    "$label - expected type"
                );
            }
        }


    }

    /**
     * Test where functionality, with getDepotPath/getDepotFilename.
     */
    public function testWhere()
    {
        $file = new P4_File;
        $filename = 'a_file.txt';
        $file->setFilespec("//depot/$filename")
             ->add()
             ->setLocalContents('This file has some content.')
             ->submit('Make the file available.');

        $where = $file->where();
        $this->assertSame(
            array(
                0   => "//depot/$filename",
                1   => "//test-client/$filename",
                2   => $file->getLocalFilename(),
            ),
            $where,
            'Expected where values'
        );

        // test getDepotPath and getDepotFilename
        $this->assertSame($file->getDepotPath(), dirname($where[0]), 'Expected depot path');
        $this->assertSame($file->getDepotFilename(), $where[0], 'Expected depot filename');
    }

    /**
     * Test hasRevspec.
     */
    public function testHasRevspec()
    {
        $tests = array(
            '//depot/file.txt'      => false,
            '//depot/file.txt#head' => true,
            '//depot/file.txt@1'    => true,
        );

        foreach ($tests as $filespec => $expected) {
            $result = P4_File::hasRevspec($filespec);
            $this->assertSame($expected, $result, "Expected result for '$filespec'");
        }
    }

    /**
     * Test _validateFilespec via exists()
     */
    public function test_ValidateFilespec()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': valid filespec',
                'filespec'  => '//depot/file.txt',
                'error'     => false,
            ),
            array(
                'label'     => __LINE__ .': non-string filespec',
                'filespec'  => false,
                'error'     => true,
            ),
            array(
                'label'     => __LINE__ .': wildcard filespec',
                'filespec'  => '//depot/file*',
                'error'     => true,
            ),
            array(
                'label'     => __LINE__ .': multi-file filespec',
                'filespec'  => '//depot/...',
                'error'     => true,
            ),
        );

        foreach ($tests as $test) {
            try {
                $result = P4_File::exists($test['filespec']);
                if ($test['error']) {
                    $this->fail($test['label'] .' - unexpected success');
                }
            } catch (P4_File_Exception $e) {
                if ($test['error']) {
                    $this->assertSame(
                        'Invalid filespec provided. In this context, filespecs'
                        . ' must be a reference to a single file.',
                        $e->getMessage(),
                        $test['label'] .' - expected error message'
                    );
                } else {
                    $this->fail($test['label'] .' - unexpected failure');
                }
            } catch (Exception $e) {
                $this->fail($test['label'] .' - unexpected exception: '.  $e->getMessage());
            }
        }
    }

    /**
     * Test lock/unlock.
     */
    public function testLockUnlock()
    {
        // In order to test lock/unlock properly, we need to create another user.
        $password = 'testUser1pass';
        $user2 = new P4_User;
        $user2->setValues(
            array(
                'User'      => 'testUser1',
                'Email'     => 'testUser1@testhost',
                'FullName'  => 'Test User 1',
                'Password'  => $password,
            )
        )->save();

        // create the user's client spec
        $client = new P4_Client;
        $client->setId($this->utility->getP4Params('client') .'-'. $user2->getId());
        $client->setRoot($this->utility->getP4Params('clientRoot') .'/'. $user2->getId())
               ->setView(array('//depot/... //'. $client->getId() .'/...'))
               ->setOwner($user2->getId())
               ->save();

        // create a connection for the new user
        $userP4 = P4_Connection::factory(
            $this->utility->getP4Params('port'), $user2->getId(), $client->getId(), $password, null, null
        );

        // and now the tests
        // have testuser create a file, submit it, and then open for edit and lock it
        $testFile = new P4_File($userP4);
        $testFile->setFilespec('//depot/test_user_file.txt')
                 ->add()
                 ->setLocalContents('Test user content')
                 ->submit('Adding test user file.')
                 ->open()
                 ->lock();

        // have superuser attempt to open/submit the file, expect failure
        $superFile = new P4_File($this->p4);
        $superFile->setFilespec('//depot/test_user_file.txt')
                  ->open()
                  ->setLocalContents('Super user content.');
        try {
            $superFile->submit('Place super content in file.');
            $this->fail('Unexpected success submitting to a locked file.');
        } catch (P4_Connection_CommandException $e) {
            $this->assertRegExp(
                "/Command failed: No files to submit.*"
                . "File\(s\) couldn't be locked.*"
                . "Submit failed -- fix problems above then use 'p4 submit -c 2'./s",
                $e->getMessage(),
                'Expected error submitting to a locked file.'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected exception after submit to a locked file: '.  $e->getMessage());
        }

        // have the testuser unlock the file
        $testFile->unlock();

        // have the superuser attempt to open/submit the file, expect success
        $superFile = new P4_File($this->p4);
        $superFile->setFilespec('//depot/test_user_file.txt')
                  ->edit()
                  ->setLocalContents('Super user content.')
                  ->submit('Place super content in file.');
        $this->assertTrue(true, 'Expect submit to succeed');
        $testFile->revert();
    }

    /**
     * Prepare files for fetchAll testing.
     *
     * @param   string  $basePath  base depot path for file creation; default='//depot/'
     * @param   bool    $sleep     Sleep between file creation to create unique timestamps,
     *                             true = 1 second sleep, false (default) no sleep.
     * @return  array  The list of P4_File objects created.
     */
    protected function _prepareFetchAllTests($basePath = '//depot/', $sleep = false)
    {
        $files = array();
        // make a small file
        $file = new P4_File;
        $file->setFilespec($basePath .'small.txt')
             ->open()
             ->setLocalContents('A small file.')
             ->setAttribute('foo', 'small')
             ->submit('Add a small file.');
        $files[] = $file;
        if ($sleep) {
            sleep(1); // guarantee next file has different timestamp
        }

        // make a medium-sized file
        $file = new P4_File;
        $file->setFilespec($basePath .'medium.jpg')
             ->add(null, 'binary')
             ->setLocalContents('This is a medium-sized file for testing.')
             ->setAttribute('foo', 'medium')
             ->submit('Add a medium file.');
        $files[] = $file;
        if ($sleep) {
            sleep(1); // guarantee next file has different timestamp
        }

        // make a large-sized file
        $largeContent = array();
        for ($i = 0; $i < 100; $i++) {
            $largeContent[] = 'Large files have plenty of content.';
        }
        $file = new P4_File;
        $file->setFilespec($basePath .'large.txt')
             ->open()
             ->setLocalContents(join(' ', $largeContent))
             ->setAttribute('foo', 'large')
             ->submit('Add a large file.');
        $files[] = $file;
        if ($sleep) {
            sleep(1); // guarantee next file has different timestamp
        }

        // make another small file
        $file = new P4_File;
        $file->setFilespec($basePath .'another_small.txt')
             ->open()
             ->setLocalContents('A small file.')
             ->setAttribute('foo', 'small')
             ->submit('Add another small file.');
        $files[] = $file;
        if ($sleep) {
            sleep(1); // guarantee next file has different timestamp
        }

        // make another medium file
        $file = new P4_File;
        $file->setFilespec($basePath .'ze_medium_2.jpg')
             ->add(null, 'binary')
             ->setLocalContents('This is a medium-sized file for testing.')
             ->setAttribute('foo', 'another medium')
             ->submit('Add another medium file.');
        $files[] = $file;
        if ($sleep) {
            sleep(1); // guarantee next file has different timestamp
        }

        // make a pending file
        $file = new P4_File;
        $file->setFilespec($basePath .'opened.txt')
             ->add()
             ->setLocalContents('opened.')
             ->setAttribute('foo', 'opened');
        $this->assertTrue(
            file_put_contents($file->getLocalFilename(), 'opened.') !== false,
            'Should be able to write opened file to client workspace.'
        );
        $files[] = $file;
        // the final sleep is not necessary here.

        // add keys by filename for easier lookups
        foreach ($files as $file) {
            $files[basename($file->getFilespec())] = $file;
        }
        return $files;
    }

    /**
     * Test stripRevspec.
     */
    public function testStripRevspec()
    {
        $tests = array(
            array(
                'label'     => __LINE__ .': null',
                'revspec'   => null,
                'expect'    => null,
            ),
            array(
                'label'     => __LINE__ .': empty string',
                'revspec'   => '',
                'expect'    => '',
            ),
            array(
                'label'     => __LINE__ .': numeric',
                'revspec'   => 123,
                'expect'    => 123,
            ),
            array(
                'label'     => __LINE__ .': string',
                'revspec'   => 'abc',
                'expect'    => 'abc',
            ),
            array(
                'label'     => __LINE__ .': #',
                'revspec'   => '#',
                'expect'    => '',
            ),
            array(
                'label'     => __LINE__ .': string#',
                'revspec'   => 'abc#',
                'expect'    => 'abc',
            ),
            array(
                'label'     => __LINE__ .': #string',
                'revspec'   => '#abc',
                'expect'    => '',
            ),
            array(
                'label'     => __LINE__ .': @',
                'revspec'   => '@',
                'expect'    => '',
            ),
            array(
                'label'     => __LINE__ .': string@',
                'revspec'   => 'abc@',
                'expect'    => 'abc',
            ),
            array(
                'label'     => __LINE__ .': @string',
                'revspec'   => '@abc',
                'expect'    => '',
            ),

            array(
                'label'     => __LINE__ .': depot file',
                'revspec'   => '//depot/file.txt',
                'expect'    => '//depot/file.txt',
            ),
            array(
                'label'     => __LINE__ .': depot file #have',
                'revspec'   => '//depot/file.txt#have',
                'expect'    => '//depot/file.txt',
            ),
            array(
                'label'     => __LINE__ .': depot file #head',
                'revspec'   => '//depot/file.txt#head',
                'expect'    => '//depot/file.txt',
            ),
            array(
                'label'     => __LINE__ .': depot file #none',
                'revspec'   => '//depot/file.txt#none',
                'expect'    => '//depot/file.txt',
            ),
            array(
                'label'     => __LINE__ .': depot file #123',
                'revspec'   => '//depot/file.txt#123',
                'expect'    => '//depot/file.txt',
            ),
            array(
                'label'     => __LINE__ .': depot file @123',
                'revspec'   => '//depot/file.txt@123',
                'expect'    => '//depot/file.txt',
            ),
            array(
                'label'     => __LINE__ .': depot file @test-ws',
                'revspec'   => '//depot/file.txt@test-ws',
                'expect'    => '//depot/file.txt',
            ),
            array(
                'label'     => __LINE__ .': depot file @2009-12-31',
                'revspec'   => '//depot/file.txt@2009-12-31',
                'expect'    => '//depot/file.txt',
            ),
            array(
                'label'     => __LINE__ .': depot file #head@2009-12-31',
                'revspec'   => '//depot/file.txt#head@2009-12-31',
                'expect'    => '//depot/file.txt',
            ),
            array(
                'label'     => __LINE__ .': depot file @2009-12-31#head',
                'revspec'   => '//depot/file.txt@2009-12-31#head',
                'expect'    => '//depot/file.txt',
            ),
        );

        foreach ($tests as $test) {
            $label = $test['label'];
            $actual = P4_File::stripRevspec($test['revspec']);
            $this->assertSame(
                $test['expect'],
                $actual,
                "$label - Expected result"
            );
        }
    }

    /**
     * Ensure that binary data in files and attributes is handled safely.
     */
    public function testBinarySafeness()
    {
        // make test data that contains null bytes.
        $data = str_repeat("deadbeefcafe\0", 1000);

        // make file obj to stick data in.
        $file = new P4_File;
        $file->setFilespec('//depot/test-file')
             ->setLocalContents($data);

        // make sure we wrote the local data correctly.
        $this->assertSame(
            $data,
            $file->getLocalContents(),
            'Expected matching data.'
        );

        // open for add and ensure type is binary.
        $file->add();
        $this->assertTrue($file->isOpened(), 'Expected file to be open.');
        $this->assertSame(
            'binary',
            $file->getStatus('type'),
            'Expected binary file type.'
        );
        $file->submit('Test of binary data.');

        // ensure depot contents match test data.
        $this->assertSame(
            $data,
            $file->getDepotContents(),
            'Expected matching depot vs. in-memory data post submit.'
        );
        $this->assertSame(
            $file->getLocalContents(),
            $file->getDepotContents(),
            'Expected matching depot vs. local data post submit.'
        );

        // try again with fresh objects.
        $file = P4_File::fetch('//depot/test-file');
        $this->assertSame(
            $data,
            $file->getDepotContents(),
            'Expected matching data w. fresh file obj.'
        );
        $query = P4_File_Query::create()->addFilespec('//depot/...');
        $files = P4_File::fetchAll($query);
        $this->assertSame(
            $data,
            $files->current()->getDepotContents(),
            'Expected matching data via fetch all'
        );

        // re-type to text and try again (should still be binary safe).
        $file->edit(null, 'text')->submit('now text');
        $this->assertSame(
            'text',
            $file->getStatus('headType'),
            'Expected text file type.'
        );
        $this->assertSame(
            $data,
            $file->getDepotContents(),
            'Expected matching data even w. text file type.'
        );
        $file = P4_File::fetch('//depot/test-file');
        $this->assertSame(
            $data,
            $file->getDepotContents(),
            'Expected matching data w. text type and fresh file obj.'
        );

        // try binary data in attributes.
        $file = new P4_File;
        $file->setFilespec('//depot/file-w-attr')
             ->touchLocalFile()
             ->add()
             ->setAttribute('foo', $data);
        $this->assertSame(
            $data,
            $file->getOpenAttribute('foo'),
            'Expected matching data in attribute.'
        );

        // submit and check again.
        $file->submit('binary attr.');
        $this->assertSame(
            $data,
            $file->getAttribute('foo'),
            'Expected matching data in attribute post submit.'
        );

        // fetch and check again.
        $file = P4_File::fetch('//depot/file-w-attr');
        $this->assertSame(
            $data,
            $file->getAttribute('foo'),
            'Expected matching data in attribute post submit w. fresh file obj.'
        );
    }

    /**
     * Test retrieving changes related to a given file
     */
    public function testGetChanges()
    {
        $file = new P4_File;

        try {
            $file->getChanges();
            $this->fail('expected exception when no filespec is set');
        } catch (P4_File_Exception $e) {
            $this->assertSame(
                "Cannot complete operation, no filespec has been specified",
                $e->getMessage()
            );
        }

        $file->setFilespec("//depot/testFile2")
             ->add()
             ->setLocalContents('should not see me')
             ->submit('create a different file');

        $file = new P4_File;
        $file->setFilespec("//depot/testFile")
             ->add()
             ->setLocalContents('0')
             ->submit('create file');

        for ($i=1; $i <= 10; $i++) {
            $file->edit()
                 ->setLocalContents($i)
                 ->submit('Test save ' . $i);
        }

        $this->assertSame(
            array (
                0  => 'Test save 10',
                1  => 'Test save 9',
                2  => 'Test save 8',
                3  => 'Test save 7',
                4  => 'Test save 6',
                5  => 'Test save 5',
                6  => 'Test save 4',
                7  => 'Test save 3',
                8  => 'Test save 2',
                9  => 'Test save 1',
                10 => 'create file',
            ),
            array_map('trim', $file->getChanges()->invoke('getDescription'))
        );
    }

    /**
     * Test retrieving the change related to a given file
     */
    public function testGetChange()
    {
        $file = new P4_File;

        try {
            $file->getChange();
            $this->fail('expected exception when no filespec is set');
        } catch (P4_File_Exception $e) {
            $this->assertSame(
                "Cannot complete operation, no filespec has been specified",
                $e->getMessage()
            );
        }

        $file->setFilespec("//depot/testFile2")
             ->add()
             ->setLocalContents('should not see me')
             ->submit('create a different file');

        $file = new P4_File;
        $file->setFilespec("//depot/testFile")
             ->add()
             ->setLocalContents('0')
             ->submit('create file');

        for ($i=1; $i <= 10; $i++) {
            $file->edit()
                 ->setLocalContents($i)
                 ->submit('Test save ' . $i);
        }

        $file = P4_File::fetch('//depot/testFile#2');
        $this->assertEquals('Test save 1', trim($file->getChange()->getDescription()), 'Expected change description');
    }

    /**
     * Test retrieving previor revisions of a given file
     */
    public function testFetchRevision()
    {
        $file = new P4_File;
        $file->setFilespec("//depot/testFile")
             ->add()
             ->setLocalContents('0')
             ->submit('create file');

        for ($i=1; $i <= 10; $i++) {
            $file->edit()
                 ->setLocalContents($i)
                 ->submit('Test save ' . $i);
        }

        $this->assertSame(11, count($file->getChanges()), 'expected matching number of changes');

        for ($i=1; $i <= 11; $i++) {
            $file = P4_File::fetch("//depot/testFile#$i");

            $this->assertSame(
                (string)($i-1),
                $file->getDepotContents(),
                'expected matching value for revision '.$i
            );
        }
    }

    /**
     * Test rolling back a files content
     */
    public function testRollback()
    {
        $file = new P4_File;
        $file->setFilespec("//depot/testFile")
             ->add()
             ->setLocalContents('0')
             ->submit('create file');

        for ($i=1; $i <= 10; $i++) {
            $file->edit()
                 ->setLocalContents($i)
                 ->setAttribute('number', (string)$i)
                 ->submit('Test save ' . $i);
        }

        $this->assertSame(11, count($file->getChanges()), 'expected matching number of changes');

        $file = P4_File::fetch('//depot/testFile#2');
        $this->assertSame(
            '//depot/testFile#2',
            $file->getFilespec(),
            'expected rev in filespec pre rollback'
        );

        $file->sync()
             ->edit()
             ->submit('rollin rollin rollin', P4_File::RESOLVE_ACCEPT_YOURS);

        $this->assertSame(
            '//depot/testFile',
            $file->getFilespec(),
            'expected updated filespec post rollback'
        );
        $this->assertSame(
            '1',
            P4_File::fetch('//depot/testFile')->getDepotContents(),
            'expected matching content post rollback'
        );
        $this->assertSame(
            '1',
            P4_File::fetch('//depot/testFile')->getAttribute('number'),
            'expected matching attribute post rollback'
        );
    }

    /**
     * Test competing adds
     *
     * @expectedException P4_Connection_ConflictException
     */
    public function testCompetingAdds()
    {
        // create a second workspace.
        $clientOne = P4_Client::fetch($this->p4->getClient());
        $clientTwo = new P4_Client;
        $clientTwo->setId($clientOne->getId() . "-2")
                  ->setRoot($clientOne->getRoot() . "-2")
                  ->setView(array('//depot/... //' . $clientOne->getId() . '-2/...'))
                  ->save();

        // create a second connection.
        $p4One = $this->p4;
        $p4Two = P4_Connection::factory(
            $p4One->getPort(),
            $p4One->getUser(),
            $clientTwo->getId(),
            $p4One->getPassword()
        )->connect();

        $fileOne = new P4_File($p4One);
        $fileOne->setFilespec('//depot/foo')
                ->setLocalContents('test one')
                ->add();

        $fileTwo = new P4_File($p4Two);
        $fileTwo->setFilespec('//depot/foo')
                ->setLocalContents('test two')
                ->add();

        // conflict (can't help the user!)
        $fileOne->submit('test one');
        $fileTwo->submit('test two', P4_File::RESOLVE_ACCEPT_YOURS);
    }

    /**
     * Test competing edits
     */
    public function testCompetingEdits()
    {
        // create a second workspace.
        $clientOne = P4_Client::fetch($this->p4->getClient());
        $clientTwo = new P4_Client;
        $clientTwo->setId($clientOne->getId() . "-2")
                  ->setRoot($clientOne->getRoot() . "-2")
                  ->setView(array('//depot/... //' . $clientOne->getId() . '-2/...'))
                  ->save();

        // create a second connection.
        $p4One = $this->p4;
        $p4Two = P4_Connection::factory(
            $p4One->getPort(),
            $p4One->getUser(),
            $clientTwo->getId(),
            $p4One->getPassword()
        )->connect();

        $fileOne = new P4_File($p4One);
        $fileOne->setFilespec('//depot/foo')
                ->setLocalContents('test one')
                ->add()
                ->submit('inital add');

        $fileOne = P4_File::fetch('//depot/foo', $p4One);
        $fileOne->edit()->setLocalContents('test two');

        $fileTwo = P4_File::fetch('//depot/foo', $p4Two);
        $fileTwo->sync()->edit()->setLocalContents('test three');

        // conflict
        $fileOne->submit('test two')->edit()->setLocalContents('test laksdfj')->submit('lkasdfj');
        $fileTwo->submit('test three', P4_File::RESOLVE_ACCEPT_YOURS);
    }

    /**
     * Test edit of deleted file.
     *
     * @expectedException  P4_File_Exception
     */
    public function testEditOfDeleted()
    {
        $file = new P4_File;
        $file->setFilespec('//depot/foo')
             ->setLocalContents('one')
             ->add()
             ->submit('one');

        $file->edit()
             ->setLocalContents('two')
             ->submit('two');

        $file->delete()
             ->submit('three');

        $file = P4_File::fetch('//depot/foo#1');

        // should throw.
        $file->sync()->edit();
    }

    /**
     * Test delete of deleted file.
     *
     * @expectedException  P4_File_Exception
     */
    public function testDeleteOfDeleted()
    {
        $file = new P4_File;
        $file->setFilespec('//depot/foo')
             ->setLocalContents('one')
             ->add()
             ->submit('one');

        $file->edit()
             ->setLocalContents('two')
             ->submit('two');

        $file->delete()
             ->submit('three');

        $file = P4_File::fetch('//depot/foo#1');

        // should throw.
        $file->sync()->delete();
    }

    /**
     * Test a corner case -- sync, edit and submit a file after removing it
     * from the client workspace and opening it for delete with -v.
     */
    public function testDeleteSyncEdit()
    {
        $file = new P4_File;
        $file->setFilespec('//depot/foo')
             ->touchLocalFile()
             ->add()
             ->submit('test');

        $file->setFilespec('//depot/foo#0')->sync();

        $file = new P4_File;
        $file->setFilespec('//depot/foo')
             ->delete();

        $file->sync()->edit()->submit('test');
    }

    /**
     * Verify attribute setting doesn't exceed p4d's N_OPTS limit.
     */
    public function testOptionLimit()
    {
        $file = new P4_File;
        $file->setFilespec('//depot/foo');
        $file->open();

        $limit      = P4_Connection_Abstract::OPTION_LIMIT + 1;
        $attributes = array_fill(0, $limit, 'attribute');
        $file->clearAttributes($attributes);
    }

    /**
     * Test revert and revert unchanged.
     */
    public function testRevert()
    {
        $file = new P4_File;
        $file->setFilespec('//depot/foo')
             ->setLocalContents('test content')
             ->add()
             ->submit('adding file');

        // no change, should revert.
        $file->edit()
             ->revert(P4_File::REVERT_UNCHANGED);
        $this->assertFalse($file->isOpened());

        // content change, should not revert.
        $file->edit()
             ->setLocalContents('new content')
             ->revert(P4_File::REVERT_UNCHANGED);
        $this->assertTrue($file->isOpened());

        // should revert regardless.
        $file->revert();
        $this->assertFalse($file->isOpened());
    }
}
