<?php

/**
 * Services_Amazon_S3_ObjectIterator, an iterator over objects in a bucket
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
 * @author    Christian Schmidt <services.amazon.s3@chsc.dk>
 * @copyright 2008-2009 Peytz & Co. A/S
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   CVS: $Id$
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */

/**
 * All necessary classes are included from S3.php.
 */
require_once 'Services/Amazon/S3.php';

/**
 * Services_Amazon_S3_ObjectIterator is used for iterating over objects in a
 * bucket. It implements Iterator and can thus be used in a foreach loop:
 * <code>foreach ($bucket->getObjects() as $object) {</code>
 *
 * @category  Services
 * @package   Services_Amazon_S3
 * @author    Christian Schmidt <services.amazon.s3@chsc.dk>
 * @copyright 2008-2009 Peytz & Co. A/S
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/Services_Amazon_S3
 * @see       Services_Amazon_S3_Resource_Bucket::getObjects()
 */ 
class Services_Amazon_S3_ObjectIterator implements RecursiveIterator
{
    /**
     * The bucket to search.
     * @var Services_Amazon_S3_Resource_Bucket
     */
    public $bucket;

    /**
     * If specified, only objects whose keys begin with this string are
     * returned.
     * @var string|bool  a prefix, or false
     */
    public $prefix = false;

    /**
     * If specified, keys that contain this string after the prefix are rolled
     * up into a single Services_Amazon_S3_Prefix instance.
     * @var string|bool  a delimiter, or false
     */
    public $delimiter = false;

    /**
     * Maximum number of keys to fetch per request. A low value will result in
     * more requests to the server. This value is initialized from
     * $this->s3->maxKeys.
     * @var int|false  a positive integer, or false to let the server decide the
     *                 limit
     * @see Services_Amazon_S3::$maxKeys
     */
    public $maxKeys;

    /**
     * Current iterator value.
     * @var Services_Amazon_S3_Resource_Object|Services_Amazon_S3_Prefix
     */
    private $_current;

    /**
     * The value that should be used for the marker parameter in the next
     * request.
     * @var string
     */
    private $_nextMarker;

    /**
     * Do XPath queries on last request
     * @var DOMXPath
     */
    private $_xPath;

    /**
     * Nodelist of <Content> elements from last request
     * @var DOMNodelist
     */
    private $_nodeList;

    /**
     * Current index in $nodeList
     * @var int
     */
    private $_currentIndex = false;

    /**
     * Whether the last request was truncated, i.e. did not include all
     * remaining objects.
     * @var bool
     */
    private $_isTruncated = false;

    /**
     * Whether the last request was the first page in the paged result set.
     * @var bool
     */
    private $_isFirstPage = false;

    /**
     * Constructor. This is called from
     * Services_Amazon_S3_Resource_Bucket::getObjects().
     *
     * @param Services_Amazon_S3_Resource_Bucket $bucket bucket to iterate over
     */
    public function __construct(Services_Amazon_S3_Resource_Bucket $bucket)
    {
        $this->bucket  = $bucket;
        $this->maxKeys = $this->bucket->s3->maxKeys;
    }

    /**
     * Returns the current iterator value.
     *
     * @return Services_Amazon_S3_Resource_Object
     */
    public function current()
    {
        return $this->_current;
    }

    /**
     * Returns the key of the current iterator value.
     *
     * @return string
     */
    public function key()
    {
        if ($this->_current instanceof Services_Amazon_S3_Resource_Object) {
            return $this->_current->key;
        } elseif ($this->_current instanceof Services_Amazon_S3_Prefix) {
            return $this->_current->prefix;
        } else {
            return false;
        }
    }

    /**
     * Returns whether the current key represents a common prefix.
     *
     * @return string
     */
    public function hasChildren()
    {
        return $this->_current instanceof Services_Amazon_S3_Prefix;
    }

    /**
     * Returns an iterator over objects whose key starting with the common
     * prefix represented by this iterator's current key.
     *
     * @return Services_Amazon_S3_ObjectIterator
     * @throws Services_Amazon_S3_Exception
     */
    public function getChildren()
    {
        if ($this->_current instanceof Services_Amazon_S3_Prefix) {
            return $this->_current->getObjects();
        }
    }

    /**
     * Resets the internal iterator pointer.
     *
     * @return void
     * @throws Services_Amazon_S3_Exception
     */
    public function rewind()
    {
        if ($this->_isFirstPage) {
            // If the first page is already loaded, don't trigger a reload
            $this->_currentIndex = -1;
        } else {
            $this->_nodeList    = null;
            $this->_isTruncated = false;
        }
        $this->next();
    }

    /**
     * Returns whether the internal iterator pointer points to an existing
     * value.
     *
     * @return bool
     */
    public function valid()
    {
        return isset($this->_nodeList) &&
               $this->_currentIndex < $this->_nodeList->length ||
               $this->_isTruncated;
    }

    /**
     * Advances the internal iterator pointer.
     *
     * @return void
     * @throws Services_Amazon_S3_Exception
     */
    public function next()
    {
        if (!isset($this->_nodeList) ||
            ++$this->_currentIndex >= $this->_nodeList->length &&
            $this->_isTruncated) {

            $this->_sendRequest();
        }

        $node = $this->_nodeList->item($this->_currentIndex);

        // No more objects
        if (!$node) {
            return;
        }

        if ($node->localName == 'Contents') {
            $key    = $this->_xPath->evaluate('string(s3:Key)', $node);
            $object = new Services_Amazon_S3_Resource_Object($this->bucket, $key);

            // Initialize properties present in the returned XML.
            $object->size = $this->_xPath->evaluate('string(s3:Size)', $node);
            $object->eTag = $this->_xPath->evaluate('string(s3:ETag)', $node);
            $lastModified =
                $this->_xPath->evaluate('string(s3:LastModified)', $node);
            $object->lastModified = strtotime($lastModified);

            $this->_current = $object;
        } else {
            include_once 'Services/Amazon/S3/Prefix.php';
            // $node is a text node
            $prefix        = $node->data;
            $this->_current = new Services_Amazon_S3_Prefix($this->bucket, $prefix);
        }
    }

    /**
     * Fetches a list of the next $maxKeys entries from the web service.
     *
     * @return void
     * @see Services_Amazon_S3::$maxKeys
     * @throws Services_Amazon_S3_Exception
     */
    private function _sendRequest()
    {
        $this->_isFirstPage = !$this->_isTruncated;
        $query = array();
        if ($this->maxKeys) {
            $query['max-keys'] = $this->maxKeys;
        }
        if ($this->_isTruncated) {
            $query['marker'] = $this->_nextMarker;
        }
        if ($this->delimiter) {
            $query['delimiter'] = $this->delimiter;
        }
        if ($this->prefix) {
            $query['prefix'] = $this->prefix;
        }

        $response = $this->bucket->s3->sendRequest($this->bucket, '', $query);

        $this->_xPath        = Services_Amazon_S3::getDOMXPath($response);
        $this->_nodeList     = $this->_xPath->evaluate(
            '/s3:ListBucketResult/s3:Contents |
             /s3:ListBucketResult/s3:CommonPrefixes/s3:Prefix/text()');
        $this->_isTruncated  = $this->_xPath->evaluate(
            'string(/s3:ListBucketResult/s3:IsTruncated) = "true"');
        // <NextMarker> is only present when a delimiter is specified.
        if ($this->delimiter) {
            $this->_nextMarker = $this->_xPath->evaluate(
                'string(/s3:ListBucketResult/s3:NextMarker)');
        } else {
            $this->_nextMarker = $this->_xPath->evaluate(
                'string(/s3:ListBucketResult/s3:Contents[last()]/s3:Key)');
        }
        $this->_currentIndex = 0;
    }
}

?>
