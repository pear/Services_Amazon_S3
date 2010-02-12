<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Services_Amazon_S3_AllTests::main');
}

require_once 'PHPUnit/TextUI/TestRunner.php';

require_once dirname(__FILE__) . '/Test.php';
require_once dirname(__FILE__) . '/StreamTest.php';

$configFile = dirname(__FILE__) . '/config.php';
if (file_exists($configFile)) {
    include_once $configFile;
}

class Services_Amazon_S3_AllTests extends PHPUnit_Framework_TestSuite
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    protected function setUp() {
        // These constants must be set in order to run the tests
        if (!defined('ACCESS_KEY_ID') || !defined('SECRET_ACCESS_KEY')
            || !ACCESS_KEY_ID || !SECRET_ACCESS_KEY
        ) {
            $this->markTestSuiteSkipped('Credentials missing in config.php');
        }
    }

    public static function suite()
    {
        $suite = new Services_Amazon_S3_AllTests('Services_Amazon_S3 Tests');
        $suite->addTestSuite('Services_Amazon_S3_Test');
        $suite->addTestSuite('Services_Amazon_S3_StreamTest');

        return $suite;
    }
}

// exec test suite
if (PHPUnit_MAIN_METHOD == 'Services_Amazon_S3_AllTests::main') {
    Services_Amazon_S3_AllTests::main();
}

?>
