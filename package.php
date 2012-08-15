#!/usr/bin/env php
<?php
error_reporting(0); // E_STRICT mess
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This is the package.xml generator for Services_Amazon_S3
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright 2009-2011 silverorange
 *
 * Copyright (c) 2008-2009, Peytz & Co. A/S
 * Copyright (c) 2009-2011, silverorange
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *  * Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 *  * Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in
 *    the documentation and/or other materials provided with the distribution.
 *  * Neither the name of the PHP_LexerGenerator nor the names of its
 *    contributors may be used to endorse or promote products derived
 *    from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Services
 * @package   Services_Amazon_S3
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2009-2011 silverorange
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @version   SVN: $Id$
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */

require_once 'PEAR/PackageFileManager2.php';
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$api_version     = '0.4.0';
$api_state       = 'alpha';

$release_version = '0.4.0';
$release_state   = 'alpha';
$release_notes   =
    " Request #17131 Ability to fetch the logging information per bucket.\n";

$description =
    "This package provides an object-oriented interface to the Amazon " .
    "Simple Storage Service (S3). This package is based on the 2006-03-01 " .
    "REST API.\n\n" .
    "Features:\n\n" .
    " 1. List, create and delete buckets, including buckets with location " .
    "    constraints (European buckets).\n" .
    " 2. Create, read and delete objects including metadata.\n" .
    " 3. List keys in a bucket using an SPL Iterator with support for " .
    "    paging, key prefixes and delimiters.\n" .
    " 4. Manipulate access control lists for buckets and objects.\n" .
    " 5. Specify the request style (virtualhost, cname, path style) and " .
    "    endpoint.\n" .
    " 6. Get signed URLs to allow a trusted third party to access private " .
    "    files.\n" .
    " 7. Access buckets and objects using PHP filesystem functions through " .
    "    a stream wrapper\n";

$package = new PEAR_PackageFileManager2();

$package->setOptions(
    array(
        'filelistgenerator'      => 'file',
        'simpleoutput'           => true,
        'baseinstalldir'         => '/',
        'packagedirectory'       => './',
        'dir_roles'              => array(
            'Services'           => 'php',
            'Services/Amazon'    => 'php',
            'Services/Amazon/S3' => 'php',
            'tests'              => 'test'
        ),
        'ignore'                  => array(
            'package.php',
            '*.tgz'
        )
    )
);

$package->setPackage('Services_Amazon_S3');
$package->setSummary('PHP API for Amazon S3 (Simple Storage Service)');
$package->setDescription($description);
$package->setChannel('pear.php.net');
$package->setPackageType('php');
$package->setLicense(
    'BSD',
    'http://www.opensource.org/licenses/bsd-license.php'
);

$package->setNotes($release_notes);
$package->setReleaseVersion($release_version);
$package->setReleaseStability($release_state);
$package->setAPIVersion($api_version);
$package->setAPIStability($api_state);

$package->addMaintainer(
    'lead',
    'schmidt',
    'Christian Schmidt',
    'chsc@peytz.dk'
);

$package->addMaintainer(
    'lead',
    'gauthierm',
    'Mike Gauthier',
    'mike@silverorange.com'
);

$package->addReplacement(
    'Services/Amazon/S3.php,Services/Amazon/S3/*,Services/Amazon/S3/Resource/*',
    'package-info',
    '@release-version@',
    'version'
);

$package->setPhpDep('5.1.1');

$package->addPackageDepWithChannel(
    'required',
    'PEAR',
    'pear.php.net',
    '1.3.2'
);

$package->addPackageDepWithChannel(
    'required',
    'Crypt_HMAC2',
    'pear.php.net',
    '0.2.1'
);

$package->addPackageDepWithChannel(
    'required',
    'HTTP_Request2',
    'pear.php.net',
    '0.5.1'
);

$package->addExtensionDep('required', 'spl');
$package->setPearInstallerDep('1.4.0a7');
$package->generateContents();

if (   isset($_GET['make'])
    || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')
) {
    $package->writePackageFile();
} else {
    $package->debugPackageFile();
}

