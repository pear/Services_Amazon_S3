<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Services_Amazon_S3_Resource_Bucket, represents an Amazon S3 bucket, i.e. a
 * container for objects
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2008 Peytz & Co. A/S
 * Copyright (c) 2010-2011 silverorange, Inc
 *
 * All rights reserved.
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
 * @author    Christian Schmidt <chsc@peytz.dk>
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2008 Peytz & Co. A/S, 2010-2011 silverorange, Inc
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @version   SVN: $Id$
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */

/**
 * All necessary classes are included from S3.php.
 */
require_once 'Services/Amazon/S3.php';

/**
 * Bucket logging status class
 */
require_once 'Services/Amazon/S3/LoggingStatus.php';

/**
 * Services_Amazon_S3_Resource_Bucket represents an Amazon S3 bucket, i.e. a
 * container for objects.
 *
 * @category  Services
 * @package   Services_Amazon_S3
 * @author    Christian Schmidt <chsc@peytz.dk>
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2008 Peytz & Co. A/S, 2010-2011 silverorange, Inc
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @version   Release: @release-version@
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */
class Services_Amazon_S3_Resource_Bucket extends Services_Amazon_S3_Resource
{
    // {{{ class constants

    /**
     * Access bucket using http://mybucket.s3.amazonaws.com. This will not
     * work for bucket names that are not "DNS-friendly".
     * If $s3->useSSL is true, the SSL wildcard certificate will only match
     * for buckets without periods in their name. This problem is usually
     * silently ignored, though, depending on the context options for the
     * ssl:// wrapper. This behavior is subject to change.
     * @link http://www.php.net/manual/da/transports.php#transports.inet
     */
    const REQUEST_STYLE_VIRTUAL_HOST = 'virtualhost';

    /**
     * Access bucket using http://s3.amazonaws.com/mybucket. Will not work for
     * buckets with location constraints, e.g. a bucket hosted at Amazon's
     * facility in Europe.
     */
    const REQUEST_STYLE_PATH = 'path';

    /**
     * Access bucket using http://mybucket.example.com/ (the name of the
     * bucket is "mybucket.example.tld"). The hostname mybucket.example.com
     * should be defined as a CNAME record pointing to
     * mybucket.example.tld.s3.amazonaws.com.
     * If $s3->useSSL is true, the
     * SSL certificate will not match the hostname (but currently this problem
     * is silently ignored by Net_Socket). This problem is usually silently
     * ignored, though, depending on the context options for the ssl://
     * wrapper. This behavior is subject to change.
     * @link http://www.php.net/manual/da/transports.php#transports.inet
     */
    const REQUEST_STYLE_CNAME = 'cname';

    // }}}
    // {{{ public properties

    /**
     * The name of this bucket.
     * @var string (UTF-8)
     * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/index.html?BucketRestrictions.html
     */
    public $name;

    /**
     * The geographical location constraint of this bucket.
     * @var string|bool  e.g. "EU", or false if no location constraint was
     *                    specified for this bucket.
     */
    public $locationConstraint;

    /**
     * Method for accessing this bucket. This value is initialized from
     * $this->s3->requestStyle.
     * @var string  a self::REQUEST_STYLE_xxx constant
     * @see Services_Amazon_S3_Resource_Bucket::$requestStyle
     * @link http://docs.amazonwebservices.com/AmazonS3/latest/dev/index.html?VirtualHosting.html
     */
    public $requestStyle;

    /**
     * The hostname of the endpoint used for requests done with
     * REQUEST_STYLE_PATH and REQUEST_STYLE_VIRTUAL_HOST. This value is
     * initialized from $this->s3->endpoint.
     * @see Services_Amazon_S3::$endpoint
     * @see Services_Amazon_S3_Resource_Bucket::REQUEST_STYLE_PATH
     */
    public $endpoint;

    /**
     * The logging status of this bucket
     *
     * @var Services_Amazon_S3_LoggingStatus
     *
     * @see Services_Amazon_S3_Resource_Bucket::loadLoggingStatus()
     */
    public $loggingStatus;

    // }}}
    // {{{ protected properties

    /**
     * Whether or not DNS strict mode is enabled
     *
     * @var boolean
     *
     * @see Services_Amazon_S3_Resource_Bucket::setDNSStrict()
     */
    protected $dnsStrict = true;

    // }}}
    // {{{ __construct()

    /**
     * Constructor. This should only be used internally. New bucket instances
     * should be created using $s3->getBucket($name) or $s3->getBuckets().
     *
     * @param Services_Amazon_S3 $s3   the service instance
     * @param string             $name the name of the bucket (UTF-8)
     *
     * @see Services_Amazon_S3::getBucket()
     */
    public function __construct(Services_Amazon_S3 $s3, $name)
    {
        $this->s3           = $s3;
        $this->name         = $name;
        $this->requestStyle = $s3->requestStyle;
        $this->endpoint     = $s3->endpoint;
    }

    // }}}
    // {{{ getURL()

    /**
     * Returns the URL of this bucket.
     *
     * @return string  an absolute URL (with a trailing slash)
     * @throws Services_Amazon_S3_Exception
     */
    public function getURL()
    {
        // Bucket names must
        // - be between 3 and 255 characters long.
        // - contain only letters, digits, periods, dashes and underscores.
        // - start with a number or letter.
        // - not be in IP address style (e.g., "192.168.5.4").
        $exp = '/^(?=[a-z0-9])[a-z0-9._-]{3,255}(?<!\-)$/i';
        if (   preg_match($exp, $this->name) === 0
            || ip2long($this->name)
        ) {
            throw new Services_Amazon_S3_Exception(
                'Invalid bucket name: ' . $this->name
            );
        }
        $prefix = ($this->s3->useSSL ? 'https' : 'http') . '://';
        switch ($this->requestStyle) {
        case self::REQUEST_STYLE_VIRTUAL_HOST:
            if ($this->dnsStrict) {
                $expression = "/^                                         \n" .
                    "  (?:                                                \n" .
                    "    [a-z0-9]    # lower-case alpha-numeric           \n" .
                    "    |                                                \n" .
                    "    (?<!-|\.)\. # dot not preceeded by a dash or dot \n" .
                    "    |                                                \n" .
                    "    (?<!\.)-    # dash not preceeded by a dot        \n" .
                    "  ){3,63}       # between 3 and 63 chars long        \n" .
                    "  (?<!\-)       # not ending in dash                 \n" .
                    "$/x";
            } else {
                $expression = "/^                                              \n" .
                    "  (?:                                                     \n" .
                    "    [a-z0-9_]   # lower-case alpha-numeric, or underscore \n" .
                    "    |                                                     \n" .
                    "    (?<!-|\.)\. # dot not preceeded by a dash or dot      \n" .
                    "    |                                                     \n" .
                    "    (?<!\.)-    # dash not preceeded by a dot             \n" .
                    "  ){3,63}       # between 3 and 63 chars long             \n" .
                    "$/x";
            }
            if (!preg_match($expression, $this->name)) {
                throw new Services_Amazon_S3_Exception(
                    'Invalid bucket name when requestStyle is ' .
                    'REQUEST_STYLE_VIRTUAL_HOST: ' . $this->name
                );
            }
            return $prefix . $this->name . '.' . $this->endpoint . '/';
        case self::REQUEST_STYLE_PATH:
            return $prefix . $this->endpoint . '/' .
                rawurlencode($this->name) . '/';
        case self::REQUEST_STYLE_CNAME:
            return $prefix . $this->name . '/';
        default:
            throw new Services_Amazon_S3_Exception(
                'Invalid requestStyle: ' . $this->requestStyle
            );
        }
    }

    // }}}
    // {{{ getObject()

    /**
     * Returns the object with the specified key. The object may or may not
     * exist on the server. Use {@see Services_Amazon_S3_Resource_Object::load()}
     * to query the server.
     *
     * @param string $key the object's key
     *
     * @return Services_Amazon_S3_Resource_Object
     */
    public function getObject($key)
    {
        return new Services_Amazon_S3_Resource_Object($this, $key);
    }

    // }}}
    // {{{ getObjects()

    /**
     * Returns an iterator over Services_Amazon_S3_Resource_Object and
     * Services_Amazon_S3_Prefix instances in this bucket.
     * If $prefix is specified, only objects whose keys begin with this string
     * are returned.
     * If $delimiter is specified, keys that contain this string after the
     * prefix are rolled up into a single Services_Amazon_S3_Prefix instance.
     * If $delimiter is omitted, the iterator only returns
     * Services_Amazon_S3_Resource_Object instances.
     *
     * @param string $prefix    key prefix
     * @param string $delimiter delimiter, e.g. "/"
     *
     * @return Services_Amazon_S3_ObjectIterator  an SPL RecursiveIterator
     */
    public function getObjects($prefix = false, $delimiter = false)
    {
        include_once 'Services/Amazon/S3/ObjectIterator.php';
        $iterator = new Services_Amazon_S3_ObjectIterator($this);
        $iterator->prefix    = $prefix;
        $iterator->delimiter = $delimiter;
        return $iterator;
    }

    // }}}
    // {{{ setDNSStrict()

    /**
     * Sets the DNS strict mode for this bucket
     *
     * S3 can use a virtual-host style method of accessing buckets (i.e.
     * mybucket.s3.amazonaws.com). When DNS strict mode is enabled (default)
     * the name of buckets must conform to the suggested bucket name syntax.
     * This means underscores are forbidden. When DNS strict mode is disabled,
     * underscores can be used for bucket names.
     *
     * @param boolean $strict whether or not to enable DNS strict mode for this
     *                        bucket.
     *
     * @return Services_Amazon_S3_Resource_Bucket the current object for fluent
     *                                            interface.
     *
     * @see Services_Amazon_S3_Resource_Bucket::$requestStyle
     */
    public function setDNSStrict($strict)
    {
        $this->dnsStrict = ($strict) ? true : false;
        return $this;
    }

    // }}}
    // {{{ load()

    /**
     * Loads this resource from the server and propagates relevant properties.
     *
     * @return bool  true, if resource exists on server
     * @throws Services_Amazon_S3_Exception
     */
    public function load()
    {
        try {
            $this->s3->sendRequest(
                $this,
                false,
                null,
                HTTP_Request2::METHOD_HEAD
            );
            $this->exists = true;
        } catch (Services_Amazon_S3_NotFoundException $e) {
            // Trying to load an non-existing bucket should not trigger an
            // exception - it is the proper way to detect whether an object
            // exists.
            return false;
        }
        return true;
    }

    // }}}
    // {{{ save()

    /**
     * Saves this bucket to the server
     *
     * For existing buckets, only the ACL and logging status are saved. When
     * saving an existing bucket,
     * {@link Services_Amazon_S3_Resource_Bucket::load()} should be called in
     * advance so as not to overwrite the existing bucket on save.
     *
     * @return void
     *
     * @throws Services_Amazon_S3_Exception
     */
    public function save()
    {
        $headers = array();
        if (is_string($this->acl)) {
            $headers['x-amz-acl'] = $this->acl;
        }
        if (!$this->exists) {
            if ($this->locationConstraint) {
                $body = '<?xml version="1.0" encoding="ISO-8859-1"?' . '>' .
                        '<CreateBucketConfiguration' .
                        ' xmlns="http://s3.amazonaws.com/doc/2006-03-01/">' .
                        '<LocationConstraint>' .
                        htmlspecialchars($this->locationConstraint) .
                        '</LocationConstraint>' .
                        '</CreateBucketConfiguration>';
            } else {
                $body = '';
            }
            $response = $this->s3->sendRequest(
                $this,
                false,
                null,
                HTTP_Request2::METHOD_PUT,
                $headers,
                $body
            );
        } elseif (is_string($this->acl)) {
            $this->s3->sendRequest(
                $this,
                '?acl',
                null,
                HTTP_Request2::METHOD_PUT,
                $headers
            );
        }

        if ($this->acl instanceof Services_Amazon_S3_AccessControlList) {
            $this->acl->save();
        }

        if ($this->loggingStatus instanceof Services_Amazon_S3_LoggingStatus) {
            $this->loggingStatus->save();
        }
    }

    // }}}
    // {{{ loadLocationConstraint()

    /**
     * Queries the server for the location constraint for this bucket and
     * propagates the locationConstraint property.
     *
     * @return void
     * @throws Services_Amazon_S3_Exception
     */
    public function loadLocationConstraint()
    {
        $response = $this->s3->sendRequest($this, '?location');
        $xPath    = Services_Amazon_S3::getDOMXPath($response);
        $s        = $xPath->evaluate('string(/s3:LocationConstraint)');

        $this->locationConstraint = $s ? $s : false;
    }

    // }}}
    // {{{ loadLoggingStatus()

    /**
     * Loads this bucket's logging status from the server
     *
     * Upon loading, the
     * {@link Services_Amazon_S3_Resource_Bucket::$loggingStatus} property
     * will be set to the loaded {@link Services_Amazon_S3_LoggingStatus}
     * object.
     *
     * @return void
     *
     * @throws Services_Amazon_S3_Exception
     */
    public function loadLoggingStatus()
    {
        include_once 'Services/Amazon/S3/LoggingStatus.php';
    }

    // }}}
}

?>
