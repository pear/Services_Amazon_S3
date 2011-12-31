# Services_Amazon_S3 #
This package provides an object-oriented interface to the [Amazon Simple
Storage Service (S3)](http://aws.amazon.com/s3/). This package is based on the
2006-03-01 REST API.

Features:
1. List, create and delete buckets, including buckets with location constraints
   (European buckets).
2. Create, read and delete objects including metadata.
3. List keys in a bucket using an SPL Iterator with support for paging, key
   prefixes and delimiters.
4. Manipulate access control lists for buckets and objects.
5. Specify the request style (virtualhost, cname, path style) and endpoint.
6. Get signed URLs to allow a trusted third party to access private files.
7. Access buckets and objects using PHP filesystem functions through a
   stream wrapper.

[Services_Amazon_S3](http://pear.php.net/package/Services_Amazon_S3) has been
migrated from [PEAR SVN](https://svn.php.net/repository/pear/packages/Services_Amazon_S3).

## Bugs and Issues ##
Please report all new issues via the [PEAR bug tracker](http://pear.php.net/bugs/search.php?cmd=display&package_name[]=Services_Amazon_S3).

Please submit pull requests for your bug reports!

## Testing ##
To test, run either
$ phpunit tests/
  or
$ pear run-tests -r

## Building ##
To build, simply
$ pear package

## Installing ##
To install from scratch
$ pear install package.xml

To upgrade
$ pear upgrade -f package.xml
