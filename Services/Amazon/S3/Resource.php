<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Services_Amazon_S3_Resource, base class for Services_Amazon_S3_Resource_Bucket
 * and Services_Amazon_S3_Resource_Object.
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
 * @version   $Id$
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */

/**
 * All necessary classes are included from S3.php.
 */
require_once 'Services/Amazon/S3.php';

/**
 * Services_Amazon_S3_Resource is the base class for
 * Services_Amazon_S3_Resource_Bucket and Services_Amazon_S3_Resource_Object.
 *
 * @category  Services
 * @package   Services_Amazon_S3
 * @author    Christian Schmidt <chsc@peytz.dk>
 * @copyright 2008 Peytz & Co. A/S
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @version   @release-version@
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */
abstract class Services_Amazon_S3_Resource
{
    // {{{ public properties

    /**
     * The service instance this resource belongs to.
     * @var Services_Amazon_S3
     */
    public $s3;

    /**
     * The access control list for this bucket.
     * This may be one of the predefined ACLs specified by a
     * Services_Amazon_S3_AccessControlList::ACL_xxx constant, or a
     * Services_Amazon_S3_AccessControlList instance.
     * @var string|Services_Amazon_S3_AccessControlList
     */
    public $acl;

    // }}}
    // {{{ protected properties

    /**
     * Whether this object is known to exist or the server. This is updated in
     * self::load().
     * @var bool
     */
    protected $exists = false;

    // }}}
    // {{{ getURL()

    /**
     * Returns the URL of this resource.
     *
     * @return string  an absolute URL
     */
    public abstract function getURL();

    // }}}
    // {{{ getSignedUrl()

    /**
     * Returns an URL with credentials included in the query string. This will
     * allow access to private resources without further authentication, e.g.
     * using a web browser.
     *
     * @param int    $ttl         number of seconds the generated URL is
     *                            authorized
     * @param string $subResource e.g. "?acl", "?location", or "?torrent"
     *                            (including the question mark)
     *
     * @return string  an absolute URL
     * @throws Services_Amazon_S3_Exception
     */
    public function getSignedUrl($ttl, $subResource = false)
    {
        $expires   = time() + $ttl;
        $signature = $this->s3->getRequestSignature(HTTP_Request2::METHOD_GET,
                                                    $this,
                                                    $subResource,
                                                    array('date' => $expires));
        return $this->getURL() .
            ($subResource ? $subResource . '&' : '?') .
            'AWSAccessKeyId=' . rawurlencode($this->s3->accessKeyId) .
            '&Signature=' . rawurlencode($signature) .
            '&Expires=' . $expires;
    }

    // }}}
    // {{{ load()

    /**
     * Loads this resource from the server and propagates relevant properties.
     *
     * @return bool  true, if resource exists on server
     * @throws Services_Amazon_S3_Exception
     */
    public abstract function load();

    // }}}
    // {{{ save()

    /**
     * Saves this resource to the server (including its access control list).
     *
     * @return void
     * @throws Services_Amazon_S3_Exception
     */
    public abstract function save();

    // }}}
    // {{{ delete()

    /**
     * Deletes this resource from the server.
     *
     * @return void
     * @throws Services_Amazon_S3_Exception
     */
    public function delete()
    {
        $response = $this->s3->sendRequest($this, false, null,
                                          HTTP_Request2::METHOD_DELETE);
        if ($response->getStatus() != 204) {
            throw new Services_Amazon_S3_Exception($response);
        }
    }

    // }}}
    // {{{ loadACL()

    /**
     * Loads this resource's access control list from the server and
     * propagates the acl property.
     *
     * @return void
     * @throws Services_Amazon_S3_Exception
     */
    public function loadACL()
    {
        include_once 'Services/Amazon/S3/AccessControlList.php';
        $this->acl = new Services_Amazon_S3_AccessControlList($this);
        $this->acl->load();
    }

    // }}}
}

?>
