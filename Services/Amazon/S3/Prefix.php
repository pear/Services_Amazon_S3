<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Services_Amazon_S3_Prefix, represents a key prefix used for listing a
 * subset of objects in a bucket.
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
 * @copyright 2008 Peytz & Co. A/S
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @version   SVN: $Id$
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */

/**
 * All necessary classes are included from S3.php.
 */
require_once 'Services/Amazon/S3.php';

/**
 * Services_Amazon_S3_Prefix represents a key prefix used for listing a
 * subset of objects in a bucket.
 *
 * @category  Services
 * @package   Services_Amazon_S3
 * @author    Christian Schmidt <chsc@peytz.dk>
 * @copyright 2008 Peytz & Co. A/S
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @version   Release: @release-version@
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */
class Services_Amazon_S3_Prefix
{
    // {{{ public properties

    /**
     * The bucket containing this prefix.
     * @var Services_Amazon_S3
     */
    public $bucket;

    /**
     * This common prefix used by this
     * @var string UTF-8 encoded prefix
     */
    public $prefix;

    /**
     * This delimiter used when listing objects, or false if no delimiter is
     * used.
     * @var string|bool
     */
    public $delimiter = false;

    // }}}
    // {{{ __construct()

    /**
     * Constructor.
     *
     * @param Services_Amazon_S3_Resource_Bucket $bucket the bucket containing
     *                                                   this prefix
     * @param string                             $prefix the prefix string (UTF-8)
     */
    public function __construct(Services_Amazon_S3_Resource_Bucket $bucket,
        $prefix
    ) {
        $this->bucket = $bucket;
        $this->prefix = $prefix;
    }

    // }}}
    // {{{ getObjects()

    /**
     * Returns an iterator over objects whose key starting with this common
     * prefix.
     *
     * @return Services_Amazon_S3_ObjectIterator
     * @throws Services_Amazon_S3_Exception
     */
    public function getObjects()
    {
        return $this->bucket->getObjects($this->prefix, $this->delimiter);
    }

    // }}}
}

?>
