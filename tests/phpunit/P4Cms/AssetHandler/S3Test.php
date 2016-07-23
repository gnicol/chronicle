<?php
/**
 * Test S3 based Asset Handler.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4Cms_AssetHandler_S3Test extends TestCase
{
    /**
     * test the exists and put methods
     */
    public function testExistsPut()
    {
        if ($this->_skipS3()) {
            return;
        }

        $id      = 'foo.txt';
        $service = new Zend_Service_Amazon_S3(S3_ACCESSKEY, S3_SECRETKEY);
        $handler = new P4Cms_AssetHandler_S3(
            array(
                'accessKey' => S3_ACCESSKEY,
                'secretKey' => S3_SECRETKEY
            )
        );

        $handler->setBucket(S3_BUCKET);

        // do not assert true for this, for it can be false if the bucket is empty
        $service->cleanBucket(S3_BUCKET);

        $this->assertFalse($handler->exists($id), 'starting state');

        $this->assertTrue($handler->put($id, 'test data'), 'put of data');

        $this->assertTrue($handler->exists($id), 'after put');
    }

    /**
     * Exists is invalid as no bucket has been set yet
     *
     * @expectedException Zend_Service_Amazon_S3_Exception
     */
    public function testInvalidExists()
    {
        $handler = new P4Cms_AssetHandler_S3;
        $handler->setAccessKey('test')
                ->setSecretKey('test')
                ->exists('foo');
    }

    /**
     * test constructor with and without options
     */
    public function testConstructor()
    {
        $handler = new P4Cms_AssetHandler_S3;

        $this->assertSame(null, $handler->getAccessKey(), 'access key default');
        $this->assertSame(null, $handler->getSecretKey(), 'secret key default');
        $this->assertSame(null, $handler->getBucket(),    'bucket default');

        $handler = new P4Cms_AssetHandler_S3(
            array(
                'accessKey' => 'accessKeyTest',
                'secretKey' => 'secretKeyTest',
                'bucket'    => 'dearLiza'
            )
        );

        $this->assertSame('accessKeyTest', $handler->getAccessKey(), 'access key custom');
        $this->assertSame('secretKeyTest', $handler->getSecretKey(), 'secret key custom');
        $this->assertSame('dearLiza',      $handler->getBucket(),    'bucket custom');
    }

    /**
     * test get/set bucket
     */
    public function testGetSetBucket()
    {
        $handler = new P4Cms_AssetHandler_S3;

        $this->assertSame(null, $handler->getBucket(), 'default');

        $handler->setBucket('a hole');
        $this->assertSame('a hole', $handler->getBucket(), 'custom');

        $handler->setBucket(null);
        $this->assertSame(null, $handler->getBucket(), 'blanked');
    }

    /**
     * test invalid bucket
     *
     * @expectedException InvalidArgumentException
     */
    public function testInvalidBucket()
    {
        $handler = new P4Cms_AssetHandler_S3(array('bucket' => 12));
    }

    /**
     * test get/set access key
     */
    public function testGetSetAccessKey()
    {
        $handler = new P4Cms_AssetHandler_S3;

        $this->assertSame(null, $handler->getAccessKey(), 'default');

        $handler->setAccessKey('a key shh.');
        $this->assertSame('a key shh.', $handler->getAccessKey(), 'custom');

        $handler->setAccessKey(null);
        $this->assertSame(null, $handler->getAccessKey(), 'blanked');
    }

    /**
     * test invalid access key
     *
     * @expectedException InvalidArgumentException
     */
    public function testInvalidAccessKey()
    {
        $handler = new P4Cms_AssetHandler_S3(array('accessKey' => 12));
    }

    /**
     * test get/set secret key
     */
    public function testGetSetSecretKey()
    {
        $handler = new P4Cms_AssetHandler_S3;

        $this->assertSame(null, $handler->getSecretKey(), 'default');

        $handler->setSecretKey('a key shh.');
        $this->assertSame('a key shh.', $handler->getSecretKey(), 'custom');

        $handler->setSecretKey(null);
        $this->assertSame(null, $handler->getSecretKey(), 'blanked');
    }

    /**
     * test invalid secret key
     *
     * @expectedException InvalidArgumentException
     */
    public function testInvalidSecretKey()
    {
        $handler = new P4Cms_AssetHandler_S3(array('secretKey' => 12));
    }

    /**
     * test uri
     */
    public function testUri()
    {
        $handler = new P4Cms_AssetHandler_S3(array('bucket' => 'dearLiza'));

        $base = 'http://' . Zend_Service_Amazon_S3::S3_ENDPOINT;
        $this->assertSame($base . '/dearLiza/foo', $handler->uri('foo'));
    }

    /**
     * verify is offsite
     */
    public function testIsOffsite()
    {
        $handler = new P4Cms_AssetHandler_S3;
        $this->assertTrue($handler->isOffsite());
    }

    /**
     * Checks if tests depending on S3 need to be skipped
     *
     * @return  bool    True if test should be skipped otherwise false
     */
    protected function _skipS3()
    {
        // pull in SECRETKEY from environment if needed
        if (!defined('S3_SECRETKEY') && getenv('P4CMS_TEST_S3_SECRETKEY')) {
            define('S3_SECRETKEY', getenv('P4CMS_TEST_S3_SECRETKEY'));
        }

        // pull in ACCESSKEY from environment if needed
        if (!defined('S3_ACCESSKEY') && getenv('P4CMS_TEST_S3_ACCESSKEY')) {
            define('S3_ACCESSKEY', getenv('P4CMS_TEST_S3_ACCESSKEY'));
        }

        // pull in BUCKET if needed
        if (!defined('S3_BUCKET') && getenv('P4CMS_TEST_S3_BUCKET')) {
            define('S3_BUCKET', getenv('P4CMS_TEST_S3_BUCKET'));
        }


        // if we are missing any defines, warn the tester.
        if (!defined('S3_SECRETKEY') || !defined('S3_ACCESSKEY') || !defined('S3_BUCKET')) {
            $this->markTestSkipped('The S3_SECRETKEY, S3_ACCESSKEY or S3_BUCKET not defined.');

            return true;
        }

        return false;
    }
}
