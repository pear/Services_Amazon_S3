<?php

/**
 * Services_Amazon_S3_Exception, general exception class.
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

require_once 'PEAR/Exception.php';

/**
 * Services_Amazon_S3_Exception is a general exception class that is used for
 * unexpected responses from the server (possibly caused by invalid data
 * supplied by the user) or invalid data supplied by the user.
 * "404 Not Found" errors are represented by the subclass
 * Services_Amazon_S3_NotFoundException.
 *
 * @category  Services
 * @package   Services_Amazon_S3
 * @author    Christian Schmidt <chsc@peytz.dk>
 * @copyright 2008 Peytz & Co. A/S
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   Release:  @package_version@
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */ 
class Services_Amazon_S3_Exception extends PEAR_Exception
{
    /**
     * The HTTP request that caused the unexpected response.    
     * The HTTP status code may indicate the error - see RFC 2616, section
     * 10 for an explanation of the different status codes.
     * The response body may contain an XML document containing an Amazon S3
     * error code.
     * @var HTTP_Request
     * @see HTTP_Request::getResponseCode()
     * @see HTTP_Request::getResponseBody()
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec10
     */
    public $request;

    /**
     * The Amazon S3 error code
     * @var string  e.g. "InvalidAccessKeyId"
     * @link http://docs.amazonwebservices.com/AmazonS3/2006-03-01/ErrorCodeList.html
     */
    private $_amazonErrorCode;

    /**
     * Constructor.
     *
     * @param string|HTTP_Request $messageOrRequest a string (UTF-8) describing
     *                                              the error, or the
     *                                              HTTP_Request that caused
     *                                              the exception
     * @param int                 $code             the error code
     */
    public function __construct($messageOrRequest, $code = 0)
    {
        $message = false;
        if ($messageOrRequest instanceof HTTP_Request) {
            $this->request = $messageOrRequest;
            $contentType   = $this->request->getResponseHeader('content-type'); 
            if ($contentType == 'application/xml' &&
                $this->request->getResponseBody()) {

                $prevUseInternalErrors = libxml_use_internal_errors(true);
                $doc = new DOMDocument();
                $ok = $doc->loadXML($this->request->getResponseBody());
                libxml_use_internal_errors($prevUseInternalErrors);
                if ($ok) {
                    $xPath = new DOMXPath($doc);
                    $this->_amazonErrorCode =
                        $xPath->evaluate('string(/Error/Code)');
                    $message               =
                        $xPath->evaluate('string(/Error/Message)');
                }
            }

            if (!$message) {
                $message = 'Bad response from server, URL: ' .
                           $this->request->getURL();
            }

            if (!$code) {
                $code = $this->request->getResponseCode();
            }
        } else {
            $message = (string) $messageOrRequest;
        }
        parent:: __construct($message, $code);
    }

    /**
     * The Amazon S3 error code.
     *
     * @return string  e.g. "InvalidAccessKeyId"
     * @link http://docs.amazonwebservices.com/AmazonS3/2006-03-01/ErrorCodeList.html
     */
    public function getAmazonErrorCode()
    {
        return $this->_amazonErrorCode;
    }
}

?>
