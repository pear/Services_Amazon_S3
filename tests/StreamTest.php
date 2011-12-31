<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

require_once 'Services/Amazon/S3.php';
require_once 'Services/Amazon/S3/Stream.php';
require_once 'Services/Amazon/S3/AccessControlList.php';

$configFile = dirname(__FILE__) . '/config.php';
if (file_exists($configFile)) {
    include_once $configFile;
}

/**
 * Test class for Services_Amazon_S3.
 */
class Services_Amazon_S3_StreamTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test of bucket to use for tests.
     * @var string
     */
    public $bucketName;

    /**
     * @var Services_Amazon_S3
     */
    private $s3;

    /**
     * @var Services_Amazon_S3_Resource_Bucket
     */
    private $bucket;

    /**
     * Creates a bucket for testing.
     */
    protected function setUp()
    {
        // set default time zone for tests
        date_default_timezone_set('UTC');

        // These constants must be set in order to run the tests
        if (   !defined('ACCESS_KEY_ID')
            || !defined('SECRET_ACCESS_KEY')
            || !ACCESS_KEY_ID
            || !SECRET_ACCESS_KEY
        ) {
            $this->markTestSkipped('Credentials missing in config.php');
        }

        if (!isset($this->bucketName)) {
            $this->bucketName = 'pear-service-amazon-s3-' .
                                strtolower(ACCESS_KEY_ID);
        }

        $this->s3 = Services_Amazon_S3::getAccount(
            ACCESS_KEY_ID,
            SECRET_ACCESS_KEY
        );

        $this->bucket = $this->s3->getBucket($this->bucketName);
        if ($this->bucket->load()) {
            // Clean up after old test
            $this->tearDown();

            $this->bucket = $this->s3->getBucket($this->bucketName);
        }
        $this->bucket->save();

        Services_Amazon_S3_Stream::register('pear-service-amazon-s3',
            array('access_key_id'     => ACCESS_KEY_ID,
                  'secret_access_key' => SECRET_ACCESS_KEY,
                  'acl' => Services_Amazon_S3_AccessControlList::ACL_PUBLIC_READ,
            )
        );
    }

    /**
     * Deletes the test bucket and all objects in it.
     */
    protected function tearDown()
    {
        foreach ($this->bucket->getObjects() as $object) {
            $object->delete();
        }
        $this->bucket->delete();
        if (in_array('pear-service-amazon-s3', stream_get_wrappers())) {
            stream_wrapper_unregister('pear-service-amazon-s3');
        }
    }

    /**
     * Tests mkdir() and rmdir().
     */
    public function testDirectoryFunctions()
    {
        $dir = 'pear-service-amazon-s3://' . $this->bucketName . '/dir';
        $this->assertFalse(file_exists($dir));
        $this->assertTrue(mkdir($dir));
        $this->assertTrue(is_dir($dir));
        $this->assertTrue(is_readable($dir));
        $this->assertTrue(is_writable($dir));
        $this->assertFalse(is_file($dir));
        $this->assertTrue(rmdir($dir));
        clearstatcache();
        $this->assertFalse(file_exists($dir));
    }

    /**
     * Verifies that the bucket created in setUp() actually exists.
     */
    public function testFileFunctions()
    {
        $file1 = 'pear-service-amazon-s3://' . $this->bucketName . '/foo.txt';
        $file2 = 'pear-service-amazon-s3://' . $this->bucketName . '/dir/bar.txt';

        $this->assertFalse(file_exists($file1));
        $this->assertEquals(file_put_contents($file1, 'lorem ipsum'), 11);
        $this->assertEquals(filesize($file1), 11);
        $this->assertTrue(is_file($file1));
        $this->assertFalse(is_dir($file1));
        $this->assertTrue(is_readable($file1));
        $this->assertTrue(is_writable($file1));
        $this->assertEquals(file_get_contents($file1), 'lorem ipsum');

        $this->assertTrue(mkdir(dirname($file2)));
        $this->assertTrue(rename($file1, $file2));
        $this->assertEquals(file_get_contents($file2), 'lorem ipsum');
        clearstatcache();
        $this->assertFalse(file_exists($file1));
        $this->assertTrue(is_file($file2));

        $this->assertTrue(rename($file2, $file1));
        $this->assertEquals(file_get_contents($file1), 'lorem ipsum');
    }
}

?>
