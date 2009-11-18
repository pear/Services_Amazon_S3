<?php

/**
 * Services_Amazon_S3_AccessControlList, permissions on buckets and objects.
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
 * @link      http://docs.amazonwebservices.com/AmazonS3/2006-03-01/RESTAccessPolicy.html
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */

require_once 'PEAR/Exception.php';

/**
 * This class represents an access control list for a bucket or an object.
 * Permissions can be granted to individual users and two predefined groups.
 *
 * A grantee is represented as an associate array. $grantee['type'] must be set
 * to either self::TYPE_CANONICAL_USER, self::AMAZON_CUSTOMER_BY_EMAIL or
 * self::TYPE_GROUP.
 *
 * If type is TYPE_CANONICAL_USER, $grantee['ID'] must be set to a canonical
 * Amazon account id. $grantee['displayName'] may be populated by the server.
 *
 * If type is TYPE_AMAZON_CUSTOMER_BY_EMAIL, $grantee['emailAddress'] must be
 * set to an email address of an Amazon customer. Note that the server converts
 * this type of grantee to a TYPE_CANONICAL_USER when the ACL is saved and
 * returned in future replies.
 *
 * If type is TYPE_GROUP, $grantee['URI'] must be set to either
 * self::URI_ALL_USERS or self::URI_AUTHENTICATED_USERS.
 *
 * Certain common ACL may be specified using the ACL_xxx string constants
 * defined in this class. These are expanded to a full ACL on the server and
 * will result in instances of this class when fetched from the server.
 *
 * @category  Services
 * @package   Services_Amazon_S3
 * @author    Christian Schmidt <chsc@peytz.dk>
 * @copyright 2008 Peytz & Co. A/S
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   Release:  @package_version@
 * @link      http://docs.amazonwebservices.com/AmazonS3/2006-03-01/S3_ACLs.html
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */ 
class Services_Amazon_S3_AccessControlList
{
    // Predefined access control lists

    /**
     * Canned access control list:
     * Nobody except the owner may read or write the resource.
     */
    const ACL_PRIVATE = 'private';

    /**
     * Canned access control list:
     * Everybody may read the resource (including accessing the object through
     * a browser without authentication).
     */
    const ACL_PUBLIC_READ = 'public-read';

    /**
     * Canned access control list:
     * Everybody may read or write the resource.
     */
    const ACL_PUBLIC_READ_WRITE = 'public-read-write';

    /**
     * Canned access control list:
     * Authenticated Amazon S3 users may read the resource.
     */
    const ACL_AUTHENTICATED_READ = 'authenticated-read';

    /**
     * No permissions - may be used to remove a grantee from the ACL.
     */
    const PERMISSION_NONE = 0;

    /**
     * Permission to read the resource.
     */
    const PERMISSION_READ = 1;

    /**
     * Permission to write the resource.
     */
    const PERMISSION_WRITE = 2;

    /**
     * Permission to read the access control policy of a resource.
     */
    const PERMISSION_READ_ACP = 4;

    /**
     * Permission to change the access control policy of a resource.
     */
    const PERMISSION_WRITE_ACP = 8;

    /**
     * Permission to read and write the resource and its access control policy.
     * This is a shortcut for a combination of all other permission flags.
     */
    const PERMISSION_FULL_CONTROL = 15;

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
    const URI_AUTHENTICATED_USERS =
        'http://acs.amazonaws.com/groups/global/AuthenticatedUsers';

    /**
     * Canonical user id of the anonymous/unauthenticated user.
     */
    const ID_ANONYMOUS = '65a011a29cdf8ec533ec3d1ccaae921c';

    /**
     * The resource this ACL applies to.
     * @var Services_Amazon_S3_Resource
     */
    public $resource;

    /**
     * The resource this ACL applies to.
     * @var array  array of grantees, i.e. associative arrays.
     */
    private $_grantees;

    /**
     * Map between permission strings and permission flags.
     */
    private static $_string2flag = array(
        'FULL_CONTROL' => self::PERMISSION_FULL_CONTROL,
        'READ'         => self::PERMISSION_READ,
        'WRITE'        => self::PERMISSION_WRITE,
        'READ_ACP'     => self::PERMISSION_READ_ACP,
        'WRITE_ACP'    => self::PERMISSION_WRITE_ACP,
    );

    /**
     * Constructor.
     *
     * @param Services_Amazon_S3_Resource $resource the bucket or object that
     *                                             this ACL applies to
     */
    public function __construct(Services_Amazon_S3_Resource $resource)
    {
        $this->resource = $resource;
    }

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
                    array('type' => self::TYPE_GROUP,
                          'URI'  => self::URI_AUTHENTICATED_USERS));
            }
            $permissions |= $this->getPermissions(
                array('type' => self::TYPE_GROUP,
                      'URI'  => self::URI_ALL_USERS));
        }
        return $permissions;
    }

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
            'string(/s3:AccessControlPolicy/s3:Owner/s3:ID)');
        $nlGrants      = $xPath->evaluate(
            '/s3:AccessControlPolicy/s3:AccessControlList/s3:Grant');

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

    /**
     * Saves this ACL to the server.
     *
     * @return void
     * @throws Services_Amazon_S3_Exception
     */
    public function save()
    {
        $doc   = new DOMDocument();
        $elACP = $doc->createElementNS(Services_Amazon_S3::NS_S3,
                                        'AccessControlPolicy');
        $doc->appendChild($elACP);

        $elOwner = $doc->createElementNS(Services_Amazon_S3::NS_S3, 'Owner');
        $elOwner->appendChild($doc->createElementNS(Services_Amazon_S3::NS_S3,
                                                    'ID', $this->ownerId));
        $elACP->appendChild($elOwner);

        $elACL = $doc->createElementNS(Services_Amazon_S3::NS_S3,
                                       'AccessControlList');
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
                        Services_Amazon_S3::NS_S3, 'Grant');
                    $elGrantee = $doc->createElementNS(
                        Services_Amazon_S3::NS_S3, 'Grantee');
                    $elGrant->appendChild($elGrantee);
                    $elGrantee->setAttributeNS(Services_Amazon_S3::NS_XSI,
                                               'xsi:type', $grantee['type']);
                    switch ($grantee['type']) {
                    case self::TYPE_CANONICAL_USER:
                        $elGrantee->appendChild($doc->createElementNS(
                            Services_Amazon_S3::NS_S3, 'ID', $grantee['ID']));
                        break;
                    case self::TYPE_AMAZON_CUSTOMER_BY_EMAIL:
                        $elGrantee->appendChild($doc->createElementNS(
                            Services_Amazon_S3::NS_S3, 'EmailAddress',
                            $grantee['emailAddress']));
                        break;
                    case self::TYPE_GROUP:
                        $elGrantee->appendChild($doc->createElementNS(
                            Services_Amazon_S3::NS_S3, 'URI', $grantee['URI']));
                        break;
                    }
                    $elGrant->appendChild($doc->createElementNS(
                        Services_Amazon_S3::NS_S3, 'Permission', $string));
                    $elACL->appendChild($elGrant);
                }
            }
        }
        $headers = array('content-type' => 'application/xml');
        $this->resource->s3->sendRequest($this->resource, '?acl', null,
                                         HTTP_Request2::METHOD_PUT, $headers,
                                         $doc->saveXML());
    }
} 

?>
