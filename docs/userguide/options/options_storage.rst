Options - Storage
--------------------

.. image:: images/Options_Storage.png

Storage tab is used to setup storage areas for recorded Events. To add a new area use the Add New Storage button.

By default storage on local drive is automatically set up on installion. When no area is specified events will be 
stored to a default built-in location, which for example on Ubuntu is /var/cache/zoneminder/events.

Name: Storage names - can be anything

Path: String path to storage location for example /media/Videos

Url: Used for S3 communication - format ``s3fs://ACCESS_KEY_ID:SECRET_ACCESS_KEY@s3.ca-central-1.amazonaws.com/bucket-name/events``

Supported storage types:
    - Local
      - Local/mounted or network storage in local network
    - s3fs
      - S3 mounted drive

Some users may require more advanced storage such as S3 provided by amazon or others.

S3 storage setup
----------------

You must use s3fs to mount the S3 bucket in your fs tree.  Telling ZoneMinder that the location is S3 will let it use
more efficient code to send and delete the event data.  

Refer to this guide for installation and configuration of s3fs - https://github.com/s3fs-fuse/s3fs-fuse

Adding credentials to passwd_file

Create credentials file ``echo ACCESS_KEY_ID:SECRET_ACCESS_KEY > /etc/passwd-s3fs``

Set file permissions ``chmod 600 /etc/passwd-s3fs``


S3 mounting with fstab 
    ``s3fs#bucket_name /media/S3 fuse _netdev,allow_other,uid=33,url=https://s3.ca-central-1.amazonaws.com,passwd_file=/etc/passwd-s3fs,umask=022 0 0``

Setting up storage.
    1. Click on Add new Storage
    2. Set path to ``/media/S3``
    3. Add Url ``s3fs://username:password@s3.ca-central-1.amazonaws.com/bucket-name/events``
    4. Set type to s3fs
    5. Save settings and monitor logs for errors
