<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Services_Amazon_S3_LoggingStatus, logging status on buckets.
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2008 Peytz & Co. A/S
 * Copyright (c) 2010 silverorange, Inc
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
 * @author    Michael Gauthier <mike@silverorange.com>
 * @author    Christian Schmidt <chsc@peytz.dk>
 * @copyright 2008 Peytz & Co. A/S, 2010 silverorange Inc
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @version   SVN: $Id:$
 * @link      http://docs.amazonwebservices.com/AmazonS3/2006-03-01/ServerLogs.html
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */

require_once 'PEAR/Exception.php';
require_once 'Services/Amazon/S3/Resource/Bucket.php';

/**
 * This class represents logging status for a bucket
 *
 * Log access can be granted to individual users and two predefined groups.
 *
 * A grantee is represented as an associate array. The
 * <kbd>$grantee['type']</kbd> must be set to either
 * {@link Services_Amazon_S3_LoggingStatus::TYPE_CANONICAL_USER},
 * {@link Services_Amazon_S3_LoggingStatus::AMAZON_CUSTOMER_BY_EMAIL} or
 * {@link Services_Amazon_S3_LoggingStatus::TYPE_GROUP}.
 *
 * If type is <kbd>TYPE_CANONICAL_USER</kbd>, <kbd>$grantee['ID']</kbd> must
 * be set to a canonical Amazon account id. The
 * <kbd>$grantee['displayName']</kbd> may be populated by the server.
 *
 * If type is <kbd>TYPE_AMAZON_CUSTOMER_BY_EMAIL</kbd>,
 * <kbd>$grantee['emailAddress']</kbd> must be set to an email address of an
 * Amazon customer. Note that the server converts this type of grantee to a
 * <kbd>TYPE_CANONICAL_USER</kbd> when the logging status is saved and returned
 * in future replies.
 *
 * If type is <kbd>TYPE_GROUP</kbd>, <kbd>$grantee['URI']</kbd> must be set to
 * either {@link Services_Amazon_S3_LoggingStatus::URI_ALL_USERS} or
 * {@link Services_Amazon_S3_LoggingStatus::URI_AUTHENTICATED_USERS}.
 *
 * @category  Services
 * @package   Services_Amazon_S3
 * @author    Michael Gauthier <mike@silverorange.com>
 * @author    Christian Schmidt <chsc@peytz.dk>
 * @copyright 2008 Peytz & Co. A/S, 2010 silverorange Inc
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @version   Release: @release-version@
 * @link      http://docs.amazonwebservices.com/AmazonS3/2006-03-01/ServerLogs.html
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */
class Services_Amazon_S3_LoggingStatus
{
    // {{{ class constants

    /**
     * No permissions - may be used to remove a grantee from the ACL.
     */
    const PERMISSION_NONE = 0;

    /**
     * Permission to read the resource.
     */
    const PERMISSION_READ = 1;

    /**
     * A user recognized by his Amazon account id.
     */
    const TYPE_CANONICAL_USER = 'CanonicalUser';

    /**
     * A user recognized by his email address.
     */
    const TYPE_AMAZON_CUSTOMER_BY_EMAIL = 'AmazonCustomerByEmail';

    /**
     * A user group recognized by a URI.
     */
    const TYPE_GROUP = 'Group';

    /**
     * URI for the group containing all (including anonymous) users.
     */
    const URI_ALL_USERS = 'http://acs.amazonaws.com/groups/global/AllUsers';

    /**
     * URI for the group containing all users who has authenticated using an
     * Amazon S3 access key.
     */
    const URI_AUTHENTICATED_USERS
        = 'http://acs.amazonaws.com/groups/global/AuthenticatedUsers';

    /**
     * Canonical user id of the anonymous/unauthenticated user.
     */
    const ID_ANONYMOUS = '65a011a29cdf8ec533ec3d1ccaae921c';

    // }}}
    // {{{ public properties

    /**
     * The bucket this logging status applies to
     *
     * @var Services_Amazon_S3_Bucket
     *
     * @see Services_Amazon_S3_LoggingStatus::__construct()
     */
    public $bucket;

    // }}}
    // {{{ private properties

    /**
     * The resource this ACL applies to.
     * @var array  array of grantees, i.e. associative arrays.
     */
    private $_grantees;

    /**
     * Map between permission strings and permission flags.
     */
    private static $_string2flag = array(
        'READ'         => self::PERMISSION_READ,
    );

    // }}}
    // {{{ __construct()

    /**
     * Creates a new logging status object for a bucket
     *
     * @param Services_Amazon_S3_Bucket $bucket the bucket that this logging
     *                                          status applies to.
     */
    public function __construct(Services_Amazon_S3_Bucket $bucket)
    {
        $this->bucket = $bucket;
    }

    // }}}
    // {{{ getGrantees()

    /**
     * Returns an array of grantee arrays.
     *
     * @return array
     */
    public function getGrantees()
    {
        $grantees = array();
        foreach ($this->_grantees as $grantee) {
            unset($grantee['permissions']);
            $grantees[] = $grantee;
        }
        return $grantees;
    }

    // }}}
    // {{{ getPermissions()

    /**
     * Returns the permissions for the specified grantee.
     *
     * @param array $grantee an associative array
     * @param bool  $implied if true and $grantee[type] == TYPE_CANONICAL_USER,
     *                       include implied rights granted to "authenticated
     *                       users" or "all users"
     *
     * @return int  a mask of self::ACL_xxx values
     */
    public function getPermissions(array $grantee, $implied = false)
    {
        $key         = $this->_getGranteeKey($grantee);
        $permissions = isset($this->_grantees[$key])
            ? $this->_grantees[$key]['permissions'] : 0;

        if ($implied && $grantee['type'] == self::TYPE_CANONICAL_USER) {
            if ($grantee['ID'] != self::ID_ANONYMOUS) {
                $permissions |= $this->getPermissions(
                    array(
                        'type' => self::TYPE_GROUP,
                        'URI'  => self::URI_AUTHENTICATED_USERS
                    )
                );
            }
            $permissions |= $this->getPermissions(
                array(
                    'type' => self::TYPE_GROUP,
                    'URI'  => self::URI_ALL_USERS
                )
            );
        }
        return $permissions;
    }

    // }}}
    // {{{ setPermissions()

    /**
     * Returns the permissions for the specified grantee.
     *
     * @param array $grantee     an associative array
     * @param int   $permissions a mask of self::ACL_xxx values
     *
     * @return int  a mask of self::PERMISSION_xxx flags
     */
    public function setPermissions(array $grantee, $permissions)
    {
        $key = $this->_getGranteeKey($grantee);
        $this->_grantees[$key]                = $grantee;
        $this->_grantees[$key]['permissions'] = $permissions;
    }

    // }}}
    // {{{ _getGranteeKey()

    /**
     * Returns the array index in the $this->grantee array for the specified
     * grantee array.
     *
     * @param array $grantee an associative array
     *
     * @return string
     */
    private function _getGranteeKey(array $grantee)
    {
        if (!isset($grantee['type'])) {
            throw new Services_Amazon_S3_Exception(
                'Array index "type" not found');
        }
        switch ($grantee['type']) {
        case self::TYPE_CANONICAL_USER:
            $part2 = 'ID';
            break;
        case self::TYPE_GROUP:
            $part2 = 'URI';
            break;
        case self::TYPE_AMAZON_CUSTOMER_BY_EMAIL:
            $part2 = 'emailAddress';
            break;
        default:
            throw new Services_Amazon_S3_Exception(
                'Unknown type: ' . $grantee['type']);
        }
        if (!isset($grantee[$part2])) {
            throw new Services_Amazon_S3_Exception(
                'Array index "' . $part2 . '" not found');
        }
        return $grantee['type'] . ' ' . $grantee[$part2];
    }

    // }}}
    // {{{ load()

    /**
     * Loads this ACL from the server.
     *
     * @return void
     * @throws Services_Amazon_S3_Exception
     */
    public function load()
    {
        $response = $this->resource->s3->sendRequest($this->resource, '?acl');
        $xPath    = Services_Amazon_S3::getDOMXPath($response);

        $this->ownerId = $xPath->evaluate(
            'string(/s3:AccessControlPolicy/s3:Owner/s3:ID)'
        );
        $nlGrants = $xPath->evaluate(
            '/s3:AccessControlPolicy/s3:AccessControlList/s3:Grant'
        );

        $this->_grantees = array();
        foreach ($nlGrants as $elGrant) {
            $type      = $xPath->evaluate('string(s3:Grantee/@xsi:type)', $elGrant);
            $nlGrantee = $xPath->evaluate('s3:Grantee', $elGrant);
            $elGrantee = $nlGrantee->item(0);
            switch ($type) {
            case self::TYPE_CANONICAL_USER:
                $grantee = array(
                    'type'        => self::TYPE_CANONICAL_USER,
                    'ID'          =>
                        $xPath->evaluate('string(s3:ID)', $elGrantee),
                    // DisplayName is empty for anonymous user
                    'displayName' =>
                        $xPath->evaluate('string(s3:DisplayName)', $elGrantee),
                    );
                break;
            case self::TYPE_GROUP:
                $grantee = array(
                    'type'  => self::TYPE_GROUP,
                    'URI'   => $xPath->evaluate('string(s3:URI)', $elGrantee),
                    );
                break;
            default:
                // TYPE_AMAZON_CUSTOMER_BY_EMAIL is never sent by the server
                throw new Services_Amazon_S3_Exception(
                    'Invalid grantee type : ' . $type, $response);
            }

            $key = $this->_getGranteeKey($grantee);
            if (!isset($this->_grantees[$key])) {
                $this->_grantees[$key]                = $grantee;
                $this->_grantees[$key]['permissions'] = 0;
            }

            $permission = $xPath->evaluate('string(s3:Permission)', $elGrant);
            if (!isset(self::$_string2flag[$permission])) {
                throw new Services_Amazon_S3_Exception(
                    'Invalid permission value: ' . $permission, $response);
            }
            $this->_grantees[$key]['permissions'] |= self::$_string2flag[$permission];
        }
    }

    // }}}
    // {{{ save()

    /**
     * Saves this ACL to the server.
     *
     * @return void
     * @throws Services_Amazon_S3_Exception
     */
    public function save()
    {
        $doc   = new DOMDocument();
        $elACP = $doc->createElementNS(
            Services_Amazon_S3::NS_S3,
            'AccessControlPolicy'
        );
        $doc->appendChild($elACP);

        $elOwner = $doc->createElementNS(Services_Amazon_S3::NS_S3, 'Owner');
        $elOwner->appendChild(
            $doc->createElementNS(
                Services_Amazon_S3::NS_S3,
                'ID',
                $this->ownerId
            )
        );
        $elACP->appendChild($elOwner);

        $elACL = $doc->createElementNS(
            Services_Amazon_S3::NS_S3,
            'AccessControlList'
        );
        $elACP->appendChild($elACL);

        foreach ($this->_grantees as $grantee) {
            $permission = $grantee['permissions'];

            // Make the XML document as small as possible by sending
            // FULL_CONTROL rather than four separate grants for READ, WRITE,
            // READ_ACP and WRITE_ACP.
            foreach (self::$_string2flag as $string => $flag) {
                if (($permission & $flag) == $flag) {
                    $permission ^= $flag;

                    $elGrant   = $doc->createElementNS(
                        Services_Amazon_S3::NS_S3,
                        'Grant'
                    );
                    $elGrantee = $doc->createElementNS(
                        Services_Amazon_S3::NS_S3,
                        'Grantee'
                    );
                    $elGrant->appendChild($elGrantee);
                    $elGrantee->setAttributeNS(
                        Services_Amazon_S3::NS_XSI,
                        'xsi:type',
                        $grantee['type']
                    );
                    switch ($grantee['type']) {
                    case self::TYPE_CANONICAL_USER:
                        $elGrantee->appendChild(
                            $doc->createElementNS(
                                Services_Amazon_S3::NS_S3,
                                'ID',
                                $grantee['ID']
                            )
                        );
                        break;
                    case self::TYPE_AMAZON_CUSTOMER_BY_EMAIL:
                        $elGrantee->appendChild(
                            $doc->createElementNS(
                                Services_Amazon_S3::NS_S3,
                                'EmailAddress',
                                $grantee['emailAddress']
                            )
                        );
                        break;
                    case self::TYPE_GROUP:
                        $elGrantee->appendChild(
                            $doc->createElementNS(
                                Services_Amazon_S3::NS_S3,
                                'URI',
                                $grantee['URI']
                            )
                        );
                        break;
                    }
                    $elGrant->appendChild(
                        $doc->createElementNS(
                            Services_Amazon_S3::NS_S3,
                            'Permission',
                            $string
                        )
                    );
                    $elACL->appendChild($elGrant);
                }
            }
        }
        $headers = array('content-type' => 'application/xml');
        $this->resource->s3->sendRequest(
            $this->resource,
            '?acl',
            null,
            HTTP_Request2::METHOD_PUT,
            $headers,
            $doc->saveXML()
        );
    }

    // }}}
}

?>
