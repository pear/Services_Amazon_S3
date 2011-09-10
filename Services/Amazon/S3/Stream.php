<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Services_Amazon_S3_Stream, a stream wrapper for Amazon S3
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2008-2009 Peytz & Co. A/S
 * Copyright (c) 2011 silverorange, Inc
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
 * @copyright 2008-2009 Peytz & Co. A/S, 2011 silverorange, Inc
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @version   SVN: $Id$
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */

/**
 * All necessary classes are included from S3.php.
 */
require_once 'Services/Amazon/S3.php';

/**
 * Services_Amazon_S3_Stream is a stream wrapper for Amazon S3. With this,
 * buckets and objects can be manipulated using the PHP filesystem functions,
 * e.g. fopen(), file_put_contents(), file_exists() etc.
 *
 * S3 has no notion of directories but support emulation to some extent using
 * the prefix and delimiter parameters when listing keys in a bucket.
 *
 * The path "s3://mybucket/myprefix/bar" corresponds to the object key
 * "myprefix/bar" in the bucket called "mybucket". "s3://mybucket/myprefix" is
 * considered a directory containing all objects with keys beginning with
 * "myprefix".
 *
 * This class is used by the PHP stream wrapper API and shouldn't be called
 * directly, except for {@link Services_Amazon_S3_Stream::register()}.
 *
 * Various options may be specified using
 * {@link Services_Amazon_S3_Stream::register()} or using a
 * {@link http://www.php.net/manual/en/ref.stream.php#stream.contexts PHP
 * stream context}.
 *
 * Available options are:
 * <ul>
 * <li>access_key_id     string 20-character key id</li>
 * <li>secret_access_key string 40-character secred key</li>
 * <li>http_config       array  is mapped to $s3->httpConfig</li>
 * <li>http_options      array  deprecated alias for http_config</li>
 * <li>use_ssl           bool   maps to $s3->useSSL</li>
 * <li>request_style     string maps to $s3->requestStyle</li>
 * <li>endpoint          string maps to $s3->endpoint</li>
 * <li>acl               string|Services_Amazon_S3_AccessControlList
 *                              applies this ACL to new files</li>
 * <li>strict            bool   use strict mode (see below) - the default is
 *                              false</li>
 * <li>dns_strict        bool   use strict mode for DNS names (see below) - the
 *                              default is true</li>
 * </ul>
 *
 * The wrapper assumes that all paths names are encoded in UTF-8.
 *
 * Strict Mode:
 *
 * S3 buckets has no directory structure but just treats a path with slashes as
 * verbatim string. Contrary to a regular filesystem this makes it possible to
 * create an object with the key "foo/bar/baz" without "foo/bar" being created
 * in advance. When strict mode is enabled, this wrapper makes extra sanity
 * checks to ensure that a directory exists before accessing it using opendir()
 * or creating a file or directory inside it using fopen() or mkdir(). Also it
 * verified that a file exists when calling unlink(), and that a directory does
 * not already exist when calling mkdir(). These extra sanity checks require
 * additional requests to the server. Most code will not require these checks,
 * so by default strict mode is disabled.
 *
 * DNS Strict Mode:
 *
 * S3 can use a virtual-host style method of accessing buckets (i.e.
 * mybucket.s3.amazonaws.com). When DNS strict mode is enabled (default) the
 * name of buckets must conform to the suggested bucket name syntax. This means
 * underscores are forbidden. When DNS strict mode is disabled, underscores
 * can be used for bucket names.
 *
 * @category  Services
 * @package   Services_Amazon_S3
 * @author    Christian Schmidt <chsc@peytz.dk>
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2008-2009 Peytz & Co. A/S, 2011 silverorange, Inc
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD
 * @version   Release: @release-version@
 * @link      http://pear.php.net/package/Services_Amazon_S3
 */
class Services_Amazon_S3_Stream
{
    // {{{ class constants

    /**
     * Directory with 0777 access - see "man 2 stat".
     */
    const MODE_DIRECTORY = 0040777;

    /**
     * Regular file with 0777 access - see "man 2 stat".
     */
    const MODE_FILE = 0100777;

    // }}}
    // {{{ public properties

    /**
     * If a stream context is specified when creating the stream, this property
     * is set by PHP.
     * @var resource|null
     */
    public $context;

    // }}}
    // {{{ private properties

    /**
     * The S3 account instance currently in use.
     * @var Stream_Amazon_S3
     */
    private $_s3;

    /**
     * The bucket referenced by $path in the last call to $this->_parsePath().
     * @var Stream_Amazon_S3_Resource_Bucket
     */
    private $_bucket;

    /**
     * The object referenced by $path in the last call to $this->_parsePath().
     * @var Stream_Amazon_S3_Resource_Object
     */
    private $_object;

    /**
     * The key prefix specified by $path in the last call to
     * $this->_parsePath().
     * @var string a prefix with a trailing slash
     */
    private $_prefix;

    /**
     * File handle for temporary file opened by stream_open().
     * @var resource
     */
    private $_fileHandle;

    /**
     * The mode parameter used to open currently opened file.
     * @var string  e.g. "r", "w" etc.
     */
    private $_mode;

    /**
     * Iterator for directory opened with dir_opendir().
     * @var AppendIterator
     */
    private $_directoryIterator;

    /**
     * Do extra sanity checking.
     * @var bool
     */
    private $_strict = false;

    /**
     * Subdirectories of the directory opened with dir_opendir(). This is used
     * when emulating directories using placeholder filder to ensure that each
     * directory is only returned once.
     * @var array
     */
    private $_subdirectories;

    /**
     * Path to directory opened with dir_opendir().
     * @var string
     */
    private $_directoryPath;

    /**
     * "Template" for stat calls. All elements must be initialized.
     * @var array
     */
    private static $_stat = array(
        0         => 0,  // device number
        'dev'     => 0,
        1         => 0,  // inode number
        'ino'     => 0,
        2         => 0,  // inode protection mode
       'mode'     => 0,
        3         => 0,  // number of links
        'nlink'   => 0,
        4         => 0,  // userid of owner
        'uid'     => 0,
        5         => 0,  // groupid of owner
        'gid'     => 0,
        6         => -1, // device type, if inode device *
        'rdev'    => -1,
        7         => 0,  // size in bytes
        'size'    => 0,
        8         => 0,  // time of last access (Unix timestamp)
        'atime'   => 0,
        9         => 0,  // time of last modification (Unix timestamp)
        'mtime'   => 0,
        10        => 0,  // time of last inode change (Unix timestamp)
        'ctime'   => 0,  // time of last inode change (Unix timestamp)
        11        => -1, // blocksize of filesystem IO
        'blksize' => -1,
        12        => -1, // number of blocks allocated
        'blocks'  => -1,
    );

    // }}}
    // {{{ register()

    /**
     * Register this stream wrapper with the specified prefix and options.
     *
     * Unless anonymous access is wanted, the common way to register the
     * wrapper is this:
     * <code>
     * Services_Amazon_S3_Stream::register('s3',
     *   array('access_key_id'     => '0PN5J17HBGZHT7JJ3X82',
     *         'secret_access_key' => 'uV3F3YluFJax1cknvbcGwgjvx4QpvB+leU8dUj2o'));
     * </code>
     *
     * The wrapper may be registered multiple times with different prefixes to
     * allow access using different access keys.
     *
     * Options may also be specified using a PHP stream context.
     *
     * @param string $wrapper the wrapper scheme, i.e. the string preceding
     *                         "://" in paths like "s3://mybucket/mykey"
     * @param array  $options associate array of key-value pairs
     *
     * @return void
     */
    public static function register($wrapper = 's3', $options = array())
    {
        stream_wrapper_register($wrapper, __CLASS__);
        if ($options) {
            stream_context_get_default(array($wrapper => $options));
        }
    }

    // }}}
    // {{{ stream_open()

    /**
     * Support for fopen(). Also used by file_get_contents() and
     * file_put_contents().
     *
     * @param string $path         the path to open
     * @param string $mode         the file mode ( "r", "wb" etc.)
     * @param int    $options      a bit mask of STREAM_USE_PATH and
     *                             STREAM_REPORT_ERRORS
     * @param string &$opened_path the file actually opened
     *
     * @return bool true on success
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->_parsePath($path);

        $error = false;

        // This stream is always binary, silently ignore "b" and "t"
        $mode = rtrim($mode, 'bt');
        $mode = substr($mode, 0, 1);

        if ($mode == 'x') {
            // We currently cannot support "x", because Amazon does not
            // support "If-None-Match: *" on PUT
            $error = '"x" file open mode not supported';
        } elseif (!strspn($mode, "rwa")) {
            $error = 'Unknown file open mode';
        } elseif (strpos($mode, "+")) {
            $error = 'S3 does not support simultaneous read/write connections';
        } else {
            if (!$this->_prefix) {
                $error = 'Cannot open bucket root';
            }
        }

        if ($error) {
            if ($options & STREAM_REPORT_ERRORS) {
                trigger_error($error, E_USER_WARNING);
            }
            return false;
        }

        $this->_mode = $mode;

        try {
            $found = false;
            if ($mode == 'r' || $mode == 'a') {
                // $this->_object is null, if $path ends with a slash or does
                // not contain a path component following the bucket name.
                $found = $this->_object ? $this->_object->load() : false;
            }
            if ($mode == 'r' && !$found) {
                $error = 'No such file';
            } else {
                // Verify that parent directory exists.
                if (!$found && $this->_strict && !is_dir(dirname($path))) {
                    $error = 'No such file';
                }
                $this->_fileHandle = tmpfile();
                if ($found) {
                    fwrite($this->_fileHandle, $this->_object->data);
                    if ($mode == 'r') {
                        rewind($this->_fileHandle);
                    }
                }
            }
        } catch (Services_Amazon_S3_Exception $e) {
            $error = $e->getMessage();
        }

        if ($error && ($options & STREAM_REPORT_ERRORS)) {
            trigger_error($error, E_USER_WARNING);
        }

        return !$error;
    }

    // }}}
    // {{{ stream_read()

    /**
     * Support for fread(), file_get_contents() etc.
     *
     * @param int $count maximum number of bytes to be read
     *
     * @return string|false  the read string, or false in case of an error
     * @see http://www.php.net/manual/en/function.fread.php
     */
    public function stream_read($count)
    {
        return fread($this->_fileHandle, $count);
    }

    // }}}
    // {{{ stream_write()

    /**
     * Support for fwrite(), file_put_contents() etc.
     *
     * @param string $data the data to be written
     *
     * @return int number of bytes written
     * @see http://www.php.net/manual/en/function.fwrite.php
     */
    public function stream_write($data)
    {
        return fwrite($this->_fileHandle, $data);
    }

    // }}}
    // {{{ stream_eof()

    /**
     * Support for feof().
     *
     * @return bool true if end-of-file has been reached, otherwise false
     * @see http://www.php.net/manual/en/function.feof.php
     */
    public function stream_eof()
    {
        return feof($this->_fileHandle);
    }

    // }}}
    // {{{ stream_seek()

    /**
     * Support for fseek().
     *
     * @param int $offset the byte offset to got to
     * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END
     *
     * @return bool true on success
     * @see http://www.php.net/manual/en/function.fseek.php
     */
    public function stream_seek($offset, $whence)
    {
        return fseek($this->_fileHandle, $offset, $whence);
    }

    // }}}
    // {{{ stream_flush()

    /**
     * Support for fflush().
     *
     * @return bool  true if data was successfully stored (or there was no
     *               data to store
     * @see http://www.php.net/manual/en/function.fflush.php
     */
    public function stream_flush()
    {
        return fflush($this->_fileHandle);
    }

    // }}}
    // {{{ stream_tell()

    /**
     * Support for ftell().
     *
     * @return int  byte offset from beginning of file
     * @see http://www.php.net/manual/en/function.ftell.php
     */
    public function stream_tell()
    {
        return ftell($this->_fileHandle);
    }

    // }}}
    // {{{ stream_stat()

    /**
     * Support for fstat().
     *
     * @return array  an array with file status, or false in case of an error
     *                - see fstat() for a description of this array
     * @see http://www.php.net/manual/en/function.fstat.php
     */
    public function stream_stat()
    {
        $fstat = fstat($this->_fileHandle);
        // Make output of stream_stat() comparable to url_stat() so only set
        // the keys that are set by url_stat()
        $stat = self::$_stat;
        $stat['mtime'] = $stat[9]  = $fstat['mtime'];
        $stat['ctime'] = $stat[10] = $fstat['ctime'];
        $stat['size']  = $stat[7]  = $fstat['size'];
        $stat['mode']  = $stat[2]  = $fstat['mode'];
        return $stat;
    }

    // }}}
    // {{{ stream_close()

    /**
     * Support for fclose().
     *
     * @return void
     * @see http://www.php.net/manual/en/function.fclose.php
     */
    public function stream_close()
    {
        $ok = true;
        $modes = array('w', 'a', 'c', 'x');
        if (in_array($this->_mode, $modes)) {
            $length = ftell($this->_fileHandle);
            if ($length) {
                rewind($this->_fileHandle);
                $this->_object->data = fread($this->_fileHandle, $length);
            } else {
                $this->_object->data = '';
            }
            // default_acl has been deprecated in favor of acl.
            if (isset($this->_options['default_acl'])) {
                $this->_object->acl = $this->_options['default_acl'];
            }
            if (isset($this->_options['acl'])) {
                $this->_object->acl = $this->_options['acl'];
            }
            if (isset($this->_options['http_headers'])) {
                $this->_object->httpHeaders = $this->_options['http_headers'];
            }
            if (isset($this->_options['user_metadata'])) {
                $this->_object->userMetadata = $this->_options['user_metadata'];
            }
            if (isset($this->_options['content_type'])) {
                $this->_object->contentType = $this->_options['content_type'];
            }
            try {
                $this->_object->save();
            } catch (Services_Amazon_S3_Exception $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);
                $ok = false;
            }
        }
        $ok = fclose($this->_fileHandle) && $ok;
        return $ok;
    }

    // }}}
    // {{{ unlink()

    /**
     * Support for unlink().
     *
     * @param string $path the file path
     *
     * @return bool  true if file was successfully deleted
     * @see http://www.php.net/manual/en/function.unlink.php
     */
    public function unlink($path)
    {
        $this->_parsePath($path);
        try {
            // Deleting a non-existing bucket causes an exception to be thrown,
            // but deleting a non-existing object does not
            if ($this->_object) {
                $mode = Services_Amazon_S3_Resource_Object::LOAD_METADATA_ONLY;
                if (   $this->_strict
                    && !$this->_object->load($mode)
                ) {
                    // Object does not exist
                    trigger_error('File does not exist', E_USER_WARNING);
                    return false;
                }
                $this->_object->delete();
            } else {
                // $path is "s3://" or "s3://mybucket/"
                trigger_error('Cannot unlink directory', E_USER_WARNING);
                return false;
            }
        } catch (Services_Amazon_S3_Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
        return true;
    }

    // }}}
    // {{{ rename()

    /**
     * Support for rename(). The renaming isn't atomic as rename() usually is,
     * but the write part is.
     *
     * @param string $fromPath the path to the file to rename
     * @param string $toPath   the new path to the file
     *
     * @return bool  true if file was successfully renamed
     * @see http://www.php.net/manual/en/function.rename.php
     */
    public function rename($fromPath, $toPath)
    {
        $this->_parsePath($fromPath);
        list($bucketName, $key) = $this->_parsePath($toPath, false);

        $toBucket = $this->_s3->getBucket($bucketName);
        if (isset($this->_options['dns_strict'])) {
            $toBucket->setDNSStrict($this->_options['dns_strict']);
        }
        $toObject = $toBucket->getObject($key);

        try {
            if (!$this->_object->load()) {
                trigger_error('Source does not exist', E_USER_WARNING);
                return false;
            }
            $this->_object->loadACL();

            $toObject->data         = $this->_object->data;
            $toObject->contentType  = $this->_object->contentType;
            $toObject->userMetadata = $this->_object->userMetadata;
            $toObject->acl          = $this->_object->acl;
            $toObject->save();

            $this->_object->delete();
        } catch (Services_Amazon_S3_Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return false;
        }
        return true;
    }

    // }}}
    // {{{ mkdir()

    /**
     * Support for mkdir().
     *
     * @param string $path    the directory path
     * @param int    $mode    permission flags - see mkdir()
     * @param int    $options a bit mask of STREAM_REPORT_ERRORS and
     *                        STREAM_MKDIR_RECURSIVE
     *
     * @return bool true if directory was successfully created
     * @see http://www.php.net/manual/en/function.mkdir.php
     */
    public function mkdir($path, $mode, $options)
    {
        $this->_parsePath($path);
        if ($this->_strict) {
            $error = false;
            if (file_exists($path)) {
                $error = 'Already exists';
            } elseif (!($options & STREAM_MKDIR_RECURSIVE) &&
                      !is_dir(dirname($path))) {

                $error = 'Parent directory not found';
            }

            if ($error) {
                if ($options & STREAM_REPORT_ERRORS) {
                    trigger_error($error, E_USER_WARNING);
                }
                return false;
            }
        }

        // S3 does not support folders, so create an empty placeholder
        // file. The chosen filename appears to be a de facto standard
        // for S3 clients, see e.g.
        // http://deadprogrammersociety.blogspot.com/2008/01/making-s3-folders-in-ruby.html
        if ($this->_object) {
            return (bool) file_put_contents(
                $path . '_$folder$',
                'This is a placeholder created by ' . __METHOD__
            );
        } else {
            $this->_bucket->save();
            return true;
        }
    }

    // }}}
    // {{{ rmdir()

    /**
     * Support for rmdir().
     *
     * Note: This method uses @ to suppress errors from unlink().
     *
     * @param string $path    the directory path
     * @param int    $options a bit mask of STREAM_REPORT_ERRORS
     *
     * @return bool true if directory was successfully removed
     * @see http://www.php.net/manual/en/function.rmdir.php
     */
    public function rmdir($path, $options)
    {
        $this->_parsePath($path);
        if ($this->_prefix) {
            if ($this->_strict && $this->_isPrefix()) {
                if ($options & STREAM_REPORT_ERRORS) {
                    trigger_error('Directory not empty', E_USER_WARNING);
                }
                return false;
            }
            $path .= '_$folder$';
            if ($options & STREAM_REPORT_ERRORS) {
                return unlink($path);
            } else {
                return @unlink($path);
            }
        } else {
            try {
                $this->_bucket->delete();
            } catch (Services_Amazon_S3_Exception $e) {
                if ($options & STREAM_REPORT_ERRORS) {
                    trigger_error($e->getMessage(), E_USER_WARNING);
                }
                return false;
            }
            return true;
        }
    }

    // }}}
    // {{{ stat()

    /**
     * Support for stat().
     *
     * @param string $path  the to get information about.
     * @param int    $flags a bit mask of STREAM_URL_STAT_LINK and
     *                      STREAM_URL_STAT_QUIET
     *
     * @return array an array with file status, or false in case of an error
     *                - see fstat() for a description of this array
     * @see http://www.php.net/manual/en/function.stat.php
     */
    public function url_stat($path, $flags)
    {
        $this->_parsePath($path);
        $stat = false;
        try {
            // $path is "s3://" or "s3://mybucket/"
            if (!$this->_prefix) {
                if (!$this->_bucket || $this->_bucket->load()) {
                    $stat = self::$_stat;
                    $stat['mode'] = $stat[2] = self::MODE_DIRECTORY;
                }
            } else {
                $mode = Services_Amazon_S3_Resource_Object::LOAD_METADATA_ONLY;
                if (   $this->_object
                    && $this->_object->load($mode)
                ) {
                    $stat = self::$_stat;
                    $stat['mtime'] = $stat[9]  = $this->_object->lastModified;
                    $stat['ctime'] = $stat[10] = $this->_object->lastModified;
                    $stat['size']  = $stat[7]  = $this->_object->size;
                    $stat['mode']  = $stat[2]  = self::MODE_FILE;
                } else {
                    if (   $this->_isPrefix()
                        || substr($this->_prefix, -10) != '_$folder$/'
                        && file_exists($path . '_$folder$')
                    ) {
                        $stat['mode'] = $stat[2] = self::MODE_DIRECTORY;
                    }
                }
            }
        } catch (Services_Amazon_S3_Exception $e) {
            $error = $e->getMessage();
            if (!($flags & STREAM_URL_STAT_QUIET)) {
                trigger_error($e->getMessage(), E_USER_WARNING);
            }
            return false;
        }

        if (!$stat && !($flags & STREAM_URL_STAT_QUIET)) {
            trigger_error('File or directory not found', E_USER_WARNING);
        }
        return $stat;
    }

    // }}}
    // {{{ dir_opendir()

    /**
     * Support for opendir().
     *
     * @param string $path    the path to the directory
     * @param string $options unknown (parameter is not documented in PHP Manual)
     *
     * @return bool true on success
     * @see http://www.php.net/manual/en/function.opendir.php
     */
    public function dir_opendir($path, $options)
    {
        $this->_parsePath($path);

        if ($this->_bucket) {
            // Get Services_Amazon_S3_ObjectIterator
            $resourceIterator = $this->_bucket->getObjects($this->_prefix, '/');
        } else {
            // When path is simply "s3://", iterator over buckets owned by
            // this account. Note that the account may have access to buckets
            // owned by other accounts and is thus able to access buckets that
            // are not returned by readdir().
            $resourceIterator = $this->_s3->getBuckets()->getIterator();
        }

        try {
            $resourceIterator->rewind();
        } catch (Services_Amazon_S3_Exception $e) {
            // Bucket does not exist or a communication error
            return false;
        }

        if (!$resourceIterator->valid()) {
            // No objects found with specified index - make sure this is in
            // fact a directory
            if ($this->_strict && !is_dir($path)) {
                return false;
            }
        }

        $this->_subdirectories    = array();
        $this->_directoryIterator = new AppendIterator();
        $dotDirectoryIterator     = new ArrayIterator(array('.'  => '.',
                                                            '..' => '..'));
        $this->_directoryIterator->append($dotDirectoryIterator);
        $this->_directoryIterator->append($resourceIterator);
        try {
            $this->_directoryIterator->rewind();
        } catch (Services_Amazon_S3_Exception $e) {
            return false;
        }
        return true;
    }

    // }}}
    // {{{ dir_readdir()

    /**
     * Support for readdir().
     *
     * @return string|bool  the next filename, or false if there are no more
     *                      files in the directory
     * @see http://www.php.net/manual/en/function.readdir.php
     */
    public function dir_readdir()
    {
        while ($this->_directoryIterator->valid()) {
            $key      = $this->_directoryIterator->key();
            $current  = $this->_directoryIterator->current();
            $iterator = $this->_directoryIterator->getInnerIterator();
            try {
                $this->_directoryIterator->next();
            } catch (Services_Amazon_S3_Exception $e) {
                return false;
            }
            if (is_string($current)) {
                // Return "." or ".."
                return $key;
            }
            if ($iterator instanceof Services_Amazon_S3_ObjectIterator) {
                // Strip prefix and any trailing slash
                $key = rtrim(substr($key, strlen($iterator->prefix)), '/');
            } else {
                // When iterating over buckets, $current is a bucket instance
                $key = $current->name;
            }

            // Subdirectories may be represented in two different ways - make
            // sure not to report duplicates
            $subdirectory = false;
            if (substr($key, -9) == '_$folder$') {
                $subdirectory = $key = substr($key, 0, -9);
            } elseif ($current instanceof Services_Amazon_S3_Prefix) {
                $subdirectory = $key;
            }
            if ($subdirectory) {
                if (!in_array($key, $this->_subdirectories)) {
                    $this->_subdirectories[] = $key;
                    return $key;
                }
            } else {
                return $key;
            }
        }
        return false;
    }

    // }}}
    // {{{ dir_rewinddir()

    /**
     * Support for rewinddir().
     *
     * @return bool  always returns true
     * @see http://www.php.net/manual/en/function.rewinddir.php
     * @legacy
     */
    public function dir_rewinddir()
    {
        $this->_subdirectories = array();
        try {
            $this->_directoryIterator->rewind();
        } catch (Services_Amazon_S3_Exception $e) {
            return false;
        }
        return true;
    }

    // }}}
    // {{{ dir_closedir()

    /**
     * Support for closedir().
     *
     * @return bool  always returns true
     * @see http://www.php.net/manual/en/function.closedir.php
     */
    public function dir_closedir()
    {
        unset($this->_directoryIterator);
        return true;
    }

    // }}}
    // {{{ _parsePath()

    /**
     * Breaks a path "s3://mybucket/foo/bar.gif" into a bucket name "mybucket"
     * and a key "bar.gif". If $populateProperties is true (or omitted),
     * various properties are populated on the current instance.
     *
     * @param string $path               a path including the stream wrapper
     *                                   prefix for this wrapper, e.g.
     *                                   s3://foo/bar.txt
     * @param bool   $populateProperties populate various properties on $this
     *
     * @return array   tuple containing (bucketName, key) - key may be false
     */
    private function _parsePath($path, $populateProperties = true)
    {
        if (!preg_match("@^([^:]+)://([^/]*)(/(.*))?$@", $path, $matches)) {
            return array(false, false);
        }
        $wrapper    = $matches[1]; // the string used in stream_wrapper_register()
        $bucketName = $matches[2];
        $key        = isset($matches[4]) ? $matches[4] : false;
        if ($populateProperties) {
            $this->_prefix = $key ? rtrim($key, '/') . '/' : '';

            // If stream wrapper was invoked with a specific stream context,
            // this is set in $this->context
            if (!$this->context) {
                $this->context = stream_context_get_default();
            }
            // Array of options for all stram wrappers
            $options = stream_context_get_options($this->context);
            $this->_options = isset($options[$wrapper])
                ? $options[$wrapper] : array();

            if (   isset($this->_options['access_key_id'])
                && isset($this->_options['secret_access_key'])
            ) {
                $this->_s3 = Services_Amazon_S3::getAccount(
                    $this->_options['access_key_id'],
                    $this->_options['secret_access_key']
                );
            } else {
                $this->_s3 = Services_Amazon_S3::getAnonymousAccount();
            }

            // backwards compatibility, see Bug #18292.
            if (isset($this->_options['http_options'])) {
                $this->_s3->httpConfig = array_merge(
                    $this->_s3->httpConfig,
                    $this->_options['http_options']
                );
            }

            // Various options
            if (isset($this->_options['http_config'])) {
                $this->_s3->httpConfig = array_merge(
                    $this->_s3->httpConfig,
                    $this->_options['http_config']
                );
            }

            if (isset($this->_options['use_ssl'])) {
                $this->_s3->useSSL = (bool) $this->_options['use_ssl'];
            }
            if (isset($this->_options['request_style'])) {
                $this->_s3->requestStyle = $this->_options['request_style'];
            }
            if (isset($this->_options['endpoint'])) {
                $this->_s3->endpoint = $this->_options['endpoint'];
            }
            if (isset($this->_options['strict'])) {
                $this->_strict = $this->_options['strict'];
            }

            if ($bucketName) {
                $this->_bucket = $this->_s3->getBucket($bucketName);
                if (isset($this->_options['dns_strict'])) {
                    $this->_bucket->setDNSStrict($this->_options['dns_strict']);
                }
                // If $path ends with "/", it is a signal that this is a
                // directory
                if ($key && substr($key, -1) != '/') {
                    $this->_object = $this->_bucket->getObject($key);
                }
            }
        }
        return array($bucketName, $key);
    }

    // }}}
    // {{{ _isPrefix()

    /**
     * Returns whether there are objects in $this->_bucket whose keys begin
     * with $this->_prefix.
     *
     * @return bool  true if there is at least one key
     */
    private function _isPrefix()
    {
        // Save resources by fetching only one key
        $objectIterator = $this->_bucket->getObjects($this->_prefix, '/');
        $objectIterator->maxKeys = 1;
        $objectIterator->rewind();
        return $objectIterator->valid();
    }

    // }}}
}

?>
