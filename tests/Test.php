<?php

if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "Services_Amazon_S3_Test::main");
}

require_once "PHPUnit/Framework/TestCase.php";
require_once "PHPUnit/Framework/TestSuite.php";

require_once 'Services/Amazon/S3.php';

$configFile = dirname(__FILE__) . '/config.php';
if (file_exists($configFile)) {
    include_once $configFile;
}

/**
 * Test class for Services_Amazon_S3.
 */
class Services_Amazon_S3_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test of bucket to use for tests.
     * @var string 40-character string
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
     * Runs the test methods of this class.
     */
    public static function main() {
        require_once "PHPUnit/TextUI/TestRunner.php";

        $suite  = new PHPUnit_Framework_TestSuite("Services_Amazon_S3_Test");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        // These constants must be set in order to run the tests
        if (!defined('ACCESS_KEY_ID') || !defined('SECRET_ACCESS_KEY')
            || !ACCESS_KEY_ID || !SECRET_ACCESS_KEY
        ) {
            $this->markTestSkipped('Credentials missing in config.php');
        }
        $this->s3 = Services_Amazon_S3::getAccount(ACCESS_KEY_ID,
                                                   SECRET_ACCESS_KEY);
        if (!isset($this->bucketName)) {
            $this->bucketName = 'pear-service-amazon-s3-' .
                                strtolower(ACCESS_KEY_ID);
        }
        $this->bucket = $this->s3->getBucket($this->bucketName);
        if ($this->bucket->load()) {
            // Clean up after old test
            $this->tearDown();

            $this->bucket = $this->s3->getBucket($this->bucketName);
        }

        $this->bucket->save();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        foreach ($this->bucket->getObjects() as $object) {
            $object->delete();
        }
        $this->bucket->delete();
    }

    /**
     * Verifies that the bucket created in setUp() actually exists.
     */
    public function testBucketExists()
    {
        $bucket = $this->s3->getBucket($this->bucketName);
        $this->assertTrue($bucket->load(), 'Test bucket was not created');
    }

    /**
     * Saves an object on the server, fetches it again and compares the fetched
     * object to the original.
     */
    public function testObjectRoundTrip()
    {
        // The two chr() generates é (e with an accent) in UTF-8
        $key = md5(microtime(true)) . '?#%/../ é' . chr(0xC3) . chr(0xA9);
        $object1 = $this->bucket->getObject($key);
        // Random binary data
        $object1->data = pack('H*', sha1(microtime(true)));
        $object1->contentType = 'foo/bar';
        $expiresUts = time() + 60;
        $object1->httpHeaders = array('ExPiReS' => gmdate('r', $expiresUts));
        $object1->userMetadata = array(
            'Foo' => 'loremIPSUM',
            'bAr' => ' 1 2 3 ',
            'baz' => ord('0xA9'),
            );
        $object1->save();

        $object2 = $this->bucket->getObject($key);
        $found = $object2->load();
        $this->assertTrue($found);
        $this->assertSame($object2->data, $object1->data);
        $this->assertEquals($object2->size, strlen($object1->data));
        $this->assertEquals($object2->contentType, $object1->contentType);
        $this->assertEquals($object2->eTag, $object1->eTag);
        $this->assertEquals(strtotime($object2->httpHeaders['expires']),
                            $expiresUts);
        $normalizedMetadata = array_map('trim', $object1->userMetadata);
        $normalizedMetadata = array_change_key_case($normalizedMetadata,
                                                    CASE_LOWER);
        $this->assertEquals($object2->userMetadata, $normalizedMetadata);
    }

    /**
     * Tries to fetch non-existing resources.
     */
    public function testNotFound()
    {
        // Non-existing 
        $key        = 'not-found-' . md5(microtime(true));
        $bucketName = 'not-found-' . $this->bucketName;

        // Non-existing object in existing bucket
        $object = $this->bucket->getObject($key);
        $this->assertFalse($object->load());

        // Non-existing bucket
        $bucket = $this->s3->getBucket($bucketName);
        $this->assertFalse($bucket->load());

        // Non-existing object in non-existing bucket
        $object = $bucket->getObject($key);
        $this->assertFalse($object->load());
    }

    /**
     * Tries to delete a bucket that is not empty.
     */
    public function testDeleteNonEmptyBucket()
    {
        $object = $this->bucket->getObject('dummy');
        $object->data = 'foo';
        $object->save();

        // Should complain about bucket not being empty
        $this->setExpectedException('Services_Amazon_S3_Exception');
        $this->bucket->delete();
    }

    /**
     * Tries to fetch an object using a key that is malformed UTF-8.
     */
    public function testMalformetUTF8()
    {
        $object = $this->bucket->getObject(chr(0xE9));
        try {
            $object->load();
        } catch (Services_Amazon_S3_Exception $exception) {
            $this->assertEquals($exception->getCode(),            400);
            $this->assertEquals($exception->getAmazonErrorCode(), 'InvalidURI');
            return;
        }
        // load() show always throw an exception
        $this->assertTrue(false);
    }
}

if (PHPUnit_MAIN_METHOD == "Services_Amazon_S3_Test::main") {
    Services_Amazon_S3_Test::main();
}
?>
