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
     * Runs the test methods of this class.
     */
    public static function main() {
        require_once "PHPUnit/TextUI/TestRunner.php";

        $suite  = new PHPUnit_Framework_TestSuite("Services_Amazon_S3_Test");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Creates a bucket for testing.
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
     * Deletes the test bucket and all objects in it.
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
     * Iterates over the objects in a bucket.
     */
    public function testObjectIterator()
    {
        $keys = array('abc', 'def', 'ghi/jkl', 'ghi/mno');
        foreach ($keys as $key) {
            $object = $this->bucket->getObject($key);
            $object->data = 'lorem ipsum';
            $object->save();
        }

        // Iterate over the objects using "/" as delimiter.
        $iterator = $this->bucket->getObjects('', '/');
        $this->assertFalse($iterator->valid());

        // Inspect first object, "abc".
        $iterator->next();
        $this->assertTrue($iterator->valid());
        $object = $iterator->current();
        $this->assertTrue($object instanceof Services_Amazon_S3_Resource_Object);
        $this->assertEquals('abc', $iterator->key());
        $this->assertEquals('abc', $object->key);
        $this->assertEquals(11, $object->size);
        $this->assertNull($object->data);
        // Allow local clock skew of up to 30 minutes.
        $this->assertGreaterThan(time() - 1800, $object->lastModified);
        $this->assertLessThan(time() + 1800, $object->lastModified);

        // Inspect next object, "def".
        $iterator->next();
        $object = $iterator->current();
        $this->assertEquals('def', $object->key);

        // Inspect prefix "ghi/".
        $iterator->next();
        $object = $iterator->current();
        $this->assertTrue($object instanceof Services_Amazon_S3_Prefix);
        $this->assertEquals('ghi/', $object->prefix);
        $this->assertEquals('ghi/', $iterator->key());

        // Verify that there are no more elements.
        $iterator->next();
        $this->assertFalse($iterator->valid());


        // Iterate over the objects with the prefix "ghi/".
        $iterator = $this->bucket->getObjects('ghi/', '/');
        $iterator->next();
        $object = $iterator->current();
        $this->assertEquals('ghi/jkl', $object->key);

        $iterator->next();
        $objectMno = $iterator->current();
        $this->assertEquals('ghi/mno', $objectMno->key);

        // Verify that there are no more elements.
        $iterator->next();
        $this->assertFalse($iterator->valid());


        // Test iterating over more objects than is returned in one HTTP
        // request.
        $iterator = $this->bucket->getObjects();
        $iterator->maxKeys = 2;
        $objects = iterator_to_array($iterator);
        $this->assertEquals($keys, array_keys($objects));

        // Test the same again but with a delimiter (slightly different code
        // path in Services_Amazon_S3_ObjectIterator::_sendRequest()).
        $iterator = $this->bucket->getObjects('', '/');
        $iterator->maxKeys = 2;
        $objects = iterator_to_array($iterator);
        $this->assertEquals(array('abc', 'def', 'ghi/'), array_keys($objects));
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
    public function testMalformedUTF8()
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
