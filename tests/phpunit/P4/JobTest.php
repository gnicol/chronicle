<?php
/**
 * Test methods for the P4 Job class.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_JobTest extends TestCase
{
    /**
     * Test fetching a job.
     */
    public function testFetch()
    {
        // ensure fetch fails for a non-existant job.
        $jobId = 'alskdfj2134';
        try {
            P4_Job::fetch($jobId);
            $this->fail('Fetch should fail for a non-existant job.');
        } catch (P4_Spec_NotFoundException $e) {
            $this->assertSame(
                "Cannot fetch job $jobId. Record does not exist.",
                $e->getMessage(),
                'Expected error fetching a non-existant job.'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected exception fetching a non-existant job.');
        }

        // ensure fetch fails with an empty id
        $jobId = '';
        try {
            P4_Job::fetch($jobId);
            $this->fail('Unexpected success fetching an empty job id.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(
                'Must supply a valid id to fetch.',
                $e->getMessage(),
                'Expected error fetching an empty job id.'
            );
        } catch (Exception $e) {
            $this->fail('Unexpected exception fetching an empty job id.');
        }
    }

    /**
     * Test exists.
     */
    public function testExists()
    {
        // ensure id-exists returns false for non-existant job
        $this->assertFalse(P4_Job::exists('alsdjf'), 'Given job id should not exist.');

        // ensure id-exists returns false for invalid job
        $this->assertFalse(P4_Job::exists('-job1'), 'Invalid job id should not exist.');

        // create job and ensure it exists.
        $job = new P4_Job;
        $job->setValue('Description', 'test')->save();
        $this->assertTrue(P4_Job::exists('job000001'), 'Given job id should exist.');
    }

    /**
     * Test saving a job.
     */
    public function testSave()
    {
        $job = new P4_Job;
        $description = 'test!';
        $job->setValue('Description', $description);

        // demonstrate that pre-save description is unmodified.
        $this->assertSame(
            $description,
            $job->getValue('Description'),
            'Expected pre-fetch description'
        );

        $job->save();
        $firstId = 'job000001';
        $this->assertSame($firstId, $job->getId(), 'Expected id');

        $job = P4_Job::fetch($firstId);
        $this->assertSame($firstId, $job->getId(), 'Expected id');

        // demonstrate that post-save description has had whitespace
        // management performed by the server.
        $this->assertSame(
            "$description\n",
            $job->getValue('Description'),
            'Expected post-fetch description'
        );
    }

    /**
     * Test deleting a job.
     */
    public function testDelete()
    {
        // make a few jobs
        $expectedIds = array();
        $expectedDescriptions = array();
        for ($i = 0; $i < 5; $i++) {
            $job = new P4_Job;
            $description = "job $i\n";
            $job->setValue('Description', $description);
            $job->save();
            $expectedIds[] = $job->getId();
            $expectedDescriptions[] = $description;
        }

        $jobs = P4_Job::fetchAll();
        $this->assertTrue($jobs->count() == 5, 'Expected job count');
        $jobIds = array();
        $descriptions = array();
        foreach ($jobs as $job) {
            $jobIds[] = $job->getId();
            $descriptions[] = $job->getValue('Description');
        }
        $this->assertSame(
            $expectedIds,
            $jobIds,
            'Expected job ids'
        );
        $this->assertSame(
            $expectedDescriptions,
            $descriptions,
            'Expected job descriptions'
        );
        $theId = $jobs[2]->getId();
        $this->assertTrue(
            P4_Job::exists($theId),
            'Given job id should exist.'
        );

        // now delete a job
        $job = $jobs[2];
        $job->delete();

        // adjust expectations
        array_splice($expectedIds, 2, 1);
        array_splice($expectedDescriptions, 2, 1);

        // refetch and test that the deleted job no longer exists
        // and that the non-deleted jobs still exist
        $jobs = P4_Job::fetchAll();
        $this->assertTrue($jobs->count() == 4, 'Expected job count');
        $jobIds = array();
        $descriptions = array();
        foreach ($jobs as $job) {
            $jobIds[] = $job->getId();
            $descriptions[] = $job->getValue('Description');
        }
        $this->assertSame(
            $expectedIds,
            $jobIds,
            'Expected job ids'
        );
        $this->assertSame(
            $expectedDescriptions,
            $descriptions,
            'Expected job descriptions'
        );
        $this->assertFalse(
            P4_Job::exists($theId),
            'Given job id should not exist.'
        );
    }

    /**
     * Test fetchAll.
     */
    public function testFetchAll()
    {
        $expectedJobIds = array();
        $expectedDescriptions = array();
        for ($i = 0; $i < 10; $i++) {
            $job = new P4_Job;
            $description = "test job $i";
            $job->setValue('Description', $description);
            $job->save();
            $expectedDescriptions[] = "$description\n";
            $expectedJobIds[] = $job->getId();
        }

        $jobs = P4_Job::fetchAll();
        $this->assertTrue($jobs->count() == 10, 'Expected job count');
        $jobIds = array();
        $descriptions = array();
        foreach ($jobs as $job) {
            $jobIds[] = $job->getId();
            $descriptions[] = $job->getValue('Description');
        }
        $this->assertSame(
            $expectedJobIds,
            $jobIds,
            'Expected job ids'
        );
        $this->assertSame(
            $expectedDescriptions,
            $descriptions,
            'Expected job descriptions'
        );
    }

    /**
     * get/set value are tested already for code indexed mutators. Test them for
     * fields without mutator/accessors.
     */
    public function testGetValueSetValue()
    {
        // add the field 'NewField' to do our testing on.
        $fields = P4_Spec_Definition::fetch('job')->getFields();
        $fields['NewField'] = array (
            'code' => '110',
            'dataType' => 'word',
            'displayLength' => '32',
            'fieldType' => 'required',
        );
        P4_Spec_Definition::fetch('job')->setFields($fields)->save();

        // test in memory object
        $job = new P4_Job;
        $job->setDescription('test');

        $this->assertSame(
            null,
            $job->getValue('NewField'),
            'Expected matching starting value'
        );

        $job->setValue('NewField', 'test');

        $this->assertSame(
            'test',
            $job->getValue('NewField'),
            'Expected matching value after set'
        );

        // save it and refetch to verify it is still good
        $job->save();
        $job = P4_Job::fetch($job->getId());

        $this->assertSame(
            'test',
            $job->getValue('NewField'),
            'Expected matching value after save/fetch'
        );
    }

    /**
     * Test setting invalid inputs for Status/User/Description
     */
    public function testBadSetStatusUserDescription()
    {
        $methods = array(
            'setStatus'      => 'Status must be a string or null',
            'setUser'        => 'User must be a string, P4_User or null',
            'setDescription' => 'Description must be a string or null',
        );

        $tests = array(
            array(
                'title' => __LINE__.' int',
                'value' => 10
            ),
            array(
                'title' => __LINE__.' bool',
                'value' => true
            ),
            array(
                'title' => __LINE__.' float',
                'value' => 10.0
            ),
            array(
                'title' => __LINE__.' P4_Client',
                'value' => P4_Client::fetchAll(array(P4_Client::FETCH_MAXIMUM => 1))
            ),
        );

        foreach ($methods as $method => $expectedError) {
            foreach ($tests as $test) {
                try {
                    $job = new P4_Job;

                    $job->{$method}($test['value']);

                    $this->fail(
                        $method.' '.$test['title'].': Unexpected success'
                    );
                } catch (PHPUnit_Framework_AssertionFailedError $e) {
                    $this->fail($e->getMessage());
                } catch (InvalidArgumentException $e) {
                    $this->assertSame(
                        $expectedError,
                        $e->getMessage(),
                        $method.' '.$test['title'].': unexpected exception message'
                    );
                } catch (Exception $e) {
                    $this->fail(
                        $method.' '.$test['title'].
                        ': unexpected exception ('. get_class($e) .') '.
                        $e->getMessage()
                    );
                }
            }
        }
    }

    /**
     * Test setting valid inputs for Status
     */
    public function testGoodSetStatus()
    {
        $tests = array(
            array(
                'title' => __LINE__.' empty string',
                'value' => ''
            ),
            array(
                'title' => __LINE__.' null',
                'value' => null
            ),
            array(
                'title' => __LINE__.' valid string',
                'value' => 'closed',
                'canSave' => true,
            ),
            array(
                'title' => __LINE__.' valid string',
                'value' => 'suspended',
                'canSave' => true,
            ),
            array(
                'title' => __LINE__.' valid string',
                'value' => 'open',
                'canSave' => true,
            ),
        );

        foreach ($tests as $test) {
            $title = $test['title'];
            $value = $test['value'];
            $out   = array_key_exists('out', $test) ? $test['out'] : $test['value'];

            $job = new P4_Job;

            // in memory test via setStatus
            $job->setStatus($value);
            $this->assertSame(
                $out,
                $job->getStatus(),
                $title.': expected to match set value'
            );

            $this->assertSame(
                $out,
                $job->getValue('Status'),
                $title.': Expected getValue() to match'
            );

            // via setValue comparison
            $job = new P4_Job;
            $job->setValue('Status', $value);
            $this->assertSame(
                $out,
                $job->getValue('Status'),
                $title.': Expected getStatus to match getValue'
            );

            $this->assertSame(
                $out,
                $job->getStatus(),
                $title.': Expected setValue to match getStatus'
            );

            if (array_key_exists('canSave', $test)) {
                // post save test
                $job->setDescription('blah')->setUser('user1')->save();
                $this->assertSame(
                    $out,
                    $job->getStatus(),
                    'Expected getStatus to match post save'
                );

                $this->assertSame(
                    $out,
                    $job->getValue('Status'),
                    'Expected getValue(Status) to match post save'
                );

                // post fetch test
                $job = P4_Job::fetch($job->getId());
                $this->assertSame(
                    $out,
                    $job->getStatus(),
                    'Expected getStatus to match post fetch'
                );

                $this->assertSame(
                    $out,
                    $job->getValue('Status'),
                    'Expected getValue(Status) to match post fetch'
                );
            }
        }
    }

    /**
     * Test setting valid inputs for User
     */
    public function testGoodSetUser()
    {
        $user = new P4_User;
        $user->setId('bob');
        $tests = array(
            array(
                'title' => __LINE__.' empty string',
                'value' => ''
            ),
            array(
                'title' => __LINE__.' null',
                'value' => null
            ),
            array(
                'title' => __LINE__.' valid string',
                'value' => 'user1',
                'canSave' => true,
            ),
            array(
                'title' => __LINE__.' valid object',
                'value' => $user,
                'out'   => $user->getId(),
                'canSave' => true,
            ),
        );

        foreach ($tests as $test) {
            $title = $test['title'];
            $value = $test['value'];
            $out   = array_key_exists('out', $test) ? $test['out'] : $test['value'];

            $job = new P4_Job;

            // in memory test via setField
            $job->setUser($value);
            $this->assertSame(
                $out,
                $job->getUser(),
                $title.': expected to match set value'
            );

            $this->assertSame(
                $out,
                $job->getValue('User'),
                $title.': Expected getValue() to match'
            );

            // via setValue comparison
            $job = new P4_Job;
            $job->setValue('User', $value);
            $this->assertSame(
                $out,
                $job->getValue('User'),
                $title.': Expected to match getValue'
            );

            $this->assertSame(
                $out,
                $job->getUser(),
                $title.': Expected setValue to match getUser'
            );

            if (array_key_exists('canSave', $test)) {
                // post save test
                $job->setDescription('blah')->setStatus('open')->save();
                $this->assertSame(
                    $out,
                    $job->getUser(),
                    'Expected getUser to match post save'
                );

                $this->assertSame(
                    $out,
                    $job->getValue('User'),
                    'Expected getValue(User) to match post save'
                );

                // post fetch test
                $job = P4_Job::fetch($job->getId());
                $this->assertSame(
                    $out,
                    $job->getUser(),
                    'Expected getUser to match post fetch'
                );

                $this->assertSame(
                    $out,
                    $job->getValue('User'),
                    'Expected getValue(User) to match post fetch'
                );
            }
        }
    }

    /**
     * Test setting valid inputs for Description
     */
    public function testGoodSetDescription()
    {
        $tests = array(
            array(
                'title' => __LINE__.' empty string',
                'value' => ''
            ),
            array(
                'title' => __LINE__.' null',
                'value' => null
            ),
            array(
                'title' => __LINE__.' valid string',
                'value' => "test description\n",
                'canSave' => true,
            ),
            array(
                'title' => __LINE__.' valid multi-line string',
                'value' => "test of\nmultiline\ndescriptoin!\n",
                'canSave' => true,
            ),
        );

        foreach ($tests as $test) {
            $title = $test['title'];
            $value = $test['value'];
            $out   = array_key_exists('out', $test) ? $test['out'] : $test['value'];

            $job = new P4_Job;

            // in memory test via setField
            $job->setDescription($value);
            $this->assertSame(
                $out,
                $job->getDescription(),
                $title.': expected to match set value'
            );

            $this->assertSame(
                $out,
                $job->getValue('Description'),
                $title.': Expected getValue() to match'
            );

            // via setValue comparison
            $job = new P4_Job;
            $job->setValue('Description', $value);
            $this->assertSame(
                $out,
                $job->getValue('Description'),
                $title.': Expected to match getValue'
            );

            $this->assertSame(
                $out,
                $job->getDescription(),
                $title.': Expected setValue to match getDescription'
            );

            if (array_key_exists('canSave', $test)) {
                // post save test
                $job->setUser('user1')->setStatus('open')->save();
                $this->assertSame(
                    $out,
                    $job->getDescription(),
                    'Expected getDescription to match post save'
                );

                $this->assertSame(
                    $out,
                    $job->getValue('Description'),
                    'Expected getValue(Description) to match post save'
                );

                // post fetch test
                $job = P4_Job::fetch($job->getId());

                $this->assertSame(
                    $out,
                    $job->getDescription(),
                    'Expected getDescription to match post fetch'
                );

                $this->assertSame(
                    $out,
                    $job->getValue('Description'),
                    'Expected getValue(Description) to match post fetch'
                );
            }
        }
    }

    /**
     * test the get date function
     */
    public function testGetDate()
    {
        $job = new P4_Job;

        $this->assertSame(
            null,
            $job->getDate(),
            'Expected starting date to match'
        );

        $job->setUser('user1')->setStatus('open')->setDescription('blah')->save();

        $this->assertLessThan(
            2,
            abs(strtotime($job->getDate()) - time()),
            'Expected time to be within range post save'
        );
    }

    /**
     * Tests invalid options and option combos
     */
    public function testFetchAllBadOptions()
    {
        $tests = array(
            array(
                'title'     => __LINE__.' integer filter',
                'options'   => array(P4_Job::FETCH_BY_FILTER => 0),
                'exception' => 'Fetch by Filter expects a non-empty string as input'
            ),
            array(
                'title'     => __LINE__.' empty string filter',
                'options'   => array(P4_Job::FETCH_BY_FILTER => ""),
                'exception' => 'Fetch by Filter expects a non-empty string as input'
            ),
            array(
                'title'     => __LINE__.' empty string filter w/whitespace',
                'options'   => array(P4_Job::FETCH_BY_FILTER => "     "),
                'exception' => 'Fetch by Filter expects a non-empty string as input'
            ),
            array(
                'title'     => __LINE__.' integer filter',
                'options'   => array(P4_Job::FETCH_BY_FILTER => 10),
                'exception' => 'Fetch by Filter expects a non-empty string as input'
            ),
        );

        foreach ($tests as $test) {
            try {
                P4_Job::fetchAll($test['options']);

                $this->fail($test['title'].': unexpected success');
            } catch (PHPUnit_Framework_AssertionFailedError $e) {
                $this->fail($e->getMessage());
            } catch (InvalidArgumentException $e) {
                $this->assertSame(
                    $test['exception'],
                    $e->getMessage(),
                    $test['title'].': unexpected exception message'
                );
            } catch (Exception $e) {
                $this->fail(
                    $test['title'].
                    ': unexpected exception ('. get_class($e) .') '.
                    $e->getMessage()
                );
            }
        }
    }

    /**
     * Test fetchAll with FETCH_DESCRIPTION = false and = true
     */
    public function testFetchAllDescriptions()
    {
        for ($i=0; $i<4; $i++) {
            $job = new P4_Job;
            $job->setId((string)$i)->setUser('user'.$i)->setStatus('open')
                ->setDescription('test: '.$i."\n")->save();
        }

        // eval a mock object into existence which adds a 'getRawValues' function
        $mockCode = 'class P4_JobMock extends P4_Job {
                        public function getRawValues()
                        {
                            return $this->_values;
                        }
                    }';

        if (!class_exists('P4_JobMock')) {
            eval($mockCode);
        }

        // Test with FETCH_DESCRIPTION off
        $jobs = P4_JobMock::fetchAll(array(P4_Job::FETCH_DESCRIPTION => false));
        foreach ($jobs as $job) {
            $this->assertFalse(
                array_key_exists('Description', $job->getRawValues()),
                'Job: '.$job->getId().' expected Description to be non-existent'
            );

            $this->assertSame(
                "test: ".$job->getId()."\n",
                $job->getDescription(),
                'Job: '.$job->getId().' expected Description to autoload'
            );
        }

        // Test with FETCH_DESCRIPTION on
        $jobs = P4_JobMock::fetchAll(array(P4_Job::FETCH_DESCRIPTION => true));
        foreach ($jobs as $job) {
            $this->assertTrue(
                array_key_exists('Description', $job->getRawValues()),
                'Job: '.$job->getId().' expected Description to exist'
            );

            $values = $job->getRawValues();
            $this->assertSame(
                "test: ".$job->getId()."\n",
                $values['Description'],
                'Job: '.$job->getId().' expected Description to match'
            );

            $this->assertSame(
                "test: ".$job->getId()."\n",
                $job->getDescription(),
                'Job: '.$job->getId().' expected Description to match via accessor'
            );
        }

        // Test default is FETCH_DESCRIPTION on
        $explicitJobs = P4_JobMock::fetchAll(array(P4_Job::FETCH_DESCRIPTION => true));
        $defaultJobs  = P4_JobMock::fetchAll();

        foreach ($explicitJobs as $key => $job) {
            $this->assertSame(
                $job->getRawValues(),
                $defaultJobs[$key]->getRawValues(),
                'Expeted default to be fetch description = true'
            );
        }
    }
}
