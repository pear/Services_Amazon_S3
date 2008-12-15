<?php

/**
 * Services_Amazon_S3_Resource_Bucket, represents an Amazon S3 bucket, i.e. a
 * container for objects
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2008, Peytz & Co. A/S
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in
 *       the documentation and/or other materials provided with the distribution.
 *     * Neither the name of the PHP_LexerGenerator nor the names of its
 *       contributors may be used to endorse or promote products derived
 *       from this software without specific prior written permission.
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
 * @copyright 2008 Peytz & Co. A/S
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */

/**
 * All necessary classes are included from S3.php.
 */
require_once 'Services/Amazon/S3.php';

/**
 * Services_Amazon_S3_Resource_Bucket represents an Amazon S3 bucket, i.e. a
 * container for objects.
 *
 * @category  Services
 * @package   Services_Amazon_S3
 * @author    Christian Schmidt <chsc@peytz.dk>
 * @copyright 2008 Peytz & Co. A/S
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */ 
class Services_Amazon_S3_Resource_Bucket extends Services_Amazon_S3_Resource
{
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

    /**
     * The name of this bucket.
     * @var string (UTF-8)
     * @link http://docs.amazonwebservices.com/AmazonS3/2006-03-01/BucketRestrictions.html
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
     * @link http://docs.amazonwebservices.com/AmazonS3/2006-03-01/VirtualHosting.html
     */
    public $requestStyle;

    /**
     * The hostname of the endpoint used for requests done with
     * REQUEST_STYLE_PATH. This value is initialized from $this->s3->endpoint.
     * @see Services_Amazon_S3::$endpoint
     * @see Services_Amazon_S3_Resource_Bucket::REQUEST_STYLE_PATH
     */
    public $endpoint;

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
        if (!preg_match('/^(?=[a-z0-9])[a-z0-9._-]{3,255}(?<!\-)$/i',
                        $this->name)
            || ip2long($this->name)) {

            throw new Services_Amazon_S3_Exception(
                'Invalid bucket name: ' . $this->name);
        }
        $prefix = ($this->s3->useSSL ? 'https' : 'http') . '://';
        switch ($this->requestStyle) {
        case self::REQUEST_STYLE_VIRTUAL_HOST:
            // When using virtual hosted-style requests, bucket names must also
            // - be between 3 and 63 characters long.
            // - be all lowercase.
            // - not end with a dash.
            // - not contain periods next to dashes.
            // - not contain two periods in a row.
            if (!preg_match('/^(?:[a-z0-9]|(?<!-|\.)\.|(?<!\.)-){3,63}(?<!\-)$/', $this->name)) {
                throw new Services_Amazon_S3_Exception(
                    'Invalid bucket name when requestStyle is ' .
                    'REQUEST_STYLE_VIRTUAL_HOST: ' . $this->name);
            }
            return $prefix . $this->name . '.s3.amazonaws.com/';
        case self::REQUEST_STYLE_PATH:
            return $prefix . $this->endpoint . '/' .
                rawurlencode($this->name) . '/';
        case self::REQUEST_STYLE_CNAME:
            return $prefix . $this->name . '/';
        default:
            throw new Services_Amazon_S3_Exception(
                'Invalid requestStyle: ' . $this->requestStyle);
        }
    }

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

    /**
     * Loads this resource from the server and propagates relevant properties.  
     *
     * @return bool  true, if resource exists on server
     * @throws Services_Amazon_S3_Exception
     */
    public function load()
    {
        try {
            $this->s3->sendRequest($this, false, null,
                                   HTTP_REQUEST_METHOD_HEAD);
            $this->exists = true;
        } catch (Services_Amazon_S3_NotFoundException $e) {
            // Trying to load an non-existing bucket should not trigger an
            // exception - it is the proper way to detect whether an object
            // exists.
            return false;
        }
        return true;
    }

    /**
     * Saves this resource to the server. On existing buckets, only the ACL
     * is saved. When saving an existing bucket, load() should be called in
     * advance.
     *
     * @return void
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
                $body =  '<?xml version="1.0" encoding="ISO-8859-1"?>' .
                         '<CreateBucketConfiguration' .
                         ' xmlns="http://s3.amazonaws.com/doc/2006-03-01/">' .
                          '<LocationConstraint>' .
                            htmlspecialchars($this->locationConstraint) .
                          '</LocationConstraint>' .
                        '</CreateBucketConfiguration>';
            } else {
                $body = false;
                // Setting length explicitly is currently required by
                // HTTP_Request
                $headers['content-length'] = 0;
            }
            $request = $this->s3->sendRequest($this, false, null,
                                              HTTP_REQUEST_METHOD_PUT,
                                              $headers, $body);

        } elseif (is_string($this->acl)) {
            // Setting length explicitly for empty bodies is currently required
            // by HTTP_Request
            $headers['content-length'] = 0;
            $this->s3->sendRequest($this, '?acl', null,
                                   HTTP_REQUEST_METHOD_PUT, $headers);
        }

        if ($this->acl instanceof Services_Amazon_S3_AccessControlList) {
            $this->acl->save();
        }
    }

    /**
     * Queries the server for the location constraint for this bucket and
     * propagates the locationConstraint property.
     *
     * @return void
     * @throws Services_Amazon_S3_Exception
     */
    public function loadLocationConstraint()
    {
        $request = $this->s3->sendRequest($this, '?location');
        $xPath   = Services_Amazon_S3::getDOMXPath($request);
        $s       = $xPath->evaluate('string(/s3:LocationConstraint)');

        $this->locationConstraint = $s ? $s : false;
    }
}

?>
