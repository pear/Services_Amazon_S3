<?php

/**
 * Services_Amazon_S3_Resource_Object, represents an Amazon S3 object, i.e. a
 * file stored in the S3 storage service
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
 * Services_Amazon_S3_Resource_Object represents an Amazon S3 object, i.e. a file
 * stored in the S3 storage service.
 *
 * @category  Services
 * @package   Services_Amazon_S3
 * @author    Christian Schmidt <chsc@peytz.dk>
 * @copyright 2008 Peytz & Co. A/S
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */ 
class Services_Amazon_S3_Resource_Object extends Services_Amazon_S3_Resource
{
    /**
     * Load only metadata, not data.
     * @see Services_Amazon_S3_Resource_Object::load()
     */
    const LOAD_METADATA_ONLY = 0;

    /**
     * Load both data and metadata.
     * @see Services_Amazon_S3_Resource_Object::load()
     */
    const LOAD_DATA = 1;

    /**
     * The bucket containing this object.
     * @var Services_Amazon_S3
     */
    public $bucket;

    /**
     * This object's identifier within the bucket
     * @var string (UTF-8)
     */
    public $key;

    /**
     * The object data.
     * @var string  a (possibly very long) string
     */
    public $data;

    /**
     * The media of the object, e.g. "image/gif" or "text/html; charset=UTF-8".
     * @var string  a media type as defined in RFC 2616 section 3.7
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec3.7
     */
    public $contentType;

    /**
     * The size of the object (excluding metadata) in bytes. This is property
     * is read-only.
     * @var int  a positive integer
     */
    public $size;

    /**
     * The last modified timestamp of the file. This property is read-only.
     * @var int  a Unix timestamp
     */
    public $lastModified;

    /**
     * The ETag of the resource. This property is read-only.
     * @var string
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.19
     */
    public $eTag;

    /**
     * The user metadata as key-value pairs.
     * Metadata keys should match the definition of "token" in RFC 2616 section
     * 2.2. This includes letters a-z, digits 0-9 and hyphen (-). Keys are
     * are case-insensitive and are always returned in lowercase. Keys should
     * not include the "x-amz-meta-" prefix used by the S3 REST API.
     * Values may consist of all ISO-8859-1 characters, except control
     * characters (0-31). Leading and trailing space is stripped.
     * @var array  an associative array
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec2.2
     */
    public $userMetadata = array();

    /**
     * Additional HTTP headers that are returned when resource is fetching
     * using the rest API. Only the field names listed self::$allowedHeaders
     * are allowed. Field names are transferred and returned in lowercase
     * for easier comparison.
     * @var array  associative array of name-value pairs
     * @see Services_Amazon_S3_Resource_Object::$allowedHeaders
     */
    public $httpHeaders = array();

    /**
     * Additional allowed header field names that may be used as keys in the
     * $this->httpHeaders array.
     * @see Services_Amazon_S3_Resource_Object::$httpHeaders
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.5.1
     */
    public static $allowedHeaders = array(
        'cache-control', 'content-md5', 'content-disposition',
        'content-encoding', 'expires');

    /**
     * Constructor. This should only be used internally. New object instances
     * should be created using $bucket->getObject($name) or
     * $bucket->getObjects().
     *
     * @param Services_Amazon_S3_Resource_Bucket $bucket the bucket containing
     *                                                   this object
     * @param string                             $key    the key within the
     *                                                   bucket (UTF-8)
     */
    public function __construct(Services_Amazon_S3_Resource_Bucket $bucket, $key)
    {
        $this->bucket = $bucket;
        $this->key    = $key;
        $this->s3     = $bucket->s3;
    }

    /**
     * Returns the URL of this object.
     *
     * @return string  an absolute URL
     * @throws Services_Amazon_S3_Exception
     */
    public function getURL()
    {
        return $this->bucket->getURL() . rawurlencode($this->key);
    }

    /**
     * Returns a URL of a .torrent file for this object.
     *
     * @return string  an absolute URL
     * @throws Services_Amazon_S3_Exception
     */
    public function getTorrentURL()
    {
        return $this->getURL() . '?torrent';
    }

    /**
     * Loads this object from the server.
     * If the file is found, the properties contentType, size, lastModified,
     * eTag and userMetadata are propagated. If $fetchBody is true, the
     * property data is propagated as well.
     *
     * @param string $what a self::LOAD_xxx constant
     *
     * @return bool  true if the object exists on the server
     * @throws Services_Amazon_S3_Exception
     */
    public function load($what = self::LOAD_DATA)
    {
        $method = ($what == self::LOAD_METADATA_ONLY)
            ? HTTP_REQUEST_METHOD_HEAD : HTTP_REQUEST_METHOD_GET;

        try {
            $request = $this->s3->sendRequest($this, false, null, $method);
        } catch (Services_Amazon_S3_NotFoundException $e) {
            // Trying to load an non-existing object should not trigger an
            // exception - it is the proper way to detect whether an object
            // exists.
            return false;
        }

        $this->exists       = true;
        $this->eTag         = $request->getResponseHeader('etag');
        $this->size         = intval(
            $request->getResponseHeader('content-length'));
        $this->lastModified = strtotime(
            $request->getResponseHeader('last-modified'));
        $this->contentType  =
            $request->getResponseHeader('content-type');
        $this->userMetadata = array();
        $this->httpHeaders  = array();
        foreach ($request->getResponseHeader() as $name => $value) {
            $name = strtolower($name);
            if (strncmp($name, 'x-amz-meta-', 11) == 0) {
                $this->userMetadata[substr($name, 11)] = $value;
            } elseif (in_array($name, self::$allowedHeaders)) {
                $this->httpHeaders[$name] = $value;
            }
        }
        if ($method == HTTP_REQUEST_METHOD_GET) {
            $this->data = $request->getResponseBody();
        } else {
            $this->data = null;
        }
        return true;
    }

    /**
     * Saves this object to the server.
     *
     * @return void
     */
    public function save()
    {
        if (!$this->exists && !isset($this->data)) {
            throw new Services_Amazon_S3_Exception(
                'Cannot save object when data has not been loaded or set');
        }

        // When overwriting an object, the existing acl is lost
        if ($this->exists && !$this->acl) {
            $this->loadACL();
        }

        $headers = array();
        if ($this->contentType) {
            $headers['content-type'] = $this->contentType;
        } else {
            // If no Content-Type is assigned by the client, S3 uses
            // binary/octet-stream. However, HTTP_Request 1.4.2 does not
            // support PUT without a Content-Type, so we must specify it
            // explicitly.
            $headers['content-type'] = 'binary/octet-stream';
        }
        if (strlen($this->data) == 0) {
            // HTTP_Request 1.4.2 does not send Content-Type when body is empty,
            // so remove this before signing the request
            unset($headers['content-type']);
        }
        foreach ($this->userMetadata as $name => $value) {
            $headers['x-amz-meta-' . strtolower($name)] = $value;
        }

        foreach ($this->httpHeaders as $name => $value) {
            $name = strtolower($name);
            if (in_array($name, self::$allowedHeaders)) {
                $headers[$name] = $value;
            }
        }
        if (is_string($this->acl)) {
            $headers['x-amz-acl'] = $this->acl;
        }

        $request = $this->s3->sendRequest($this, false, null,
                                          HTTP_REQUEST_METHOD_PUT,
                                          $headers, $this->data);

        $this->eTag = $request->getResponseHeader('etag');
        if ($this->acl instanceof Services_Amazon_S3_AccessControlList) {
            $this->acl->save();
        }
    }
}

?>
