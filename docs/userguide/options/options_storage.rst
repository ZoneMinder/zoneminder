Options - Storage
--------------------

.. image:: images/Options_Storage.png

Storage tab is used to setup storage provider for recorded Events. To add new Storage use Add new Storage button.

By defaul storage on local drive should be automatically set up on installion.

Name: Storage names

Path: String path to storage location

Url: Used for S3 communication - format ``s3fs://username:password@s3.ca-central-1.amazonaws.com/bucket-name/events``

Supported storage types:
    - Local
      - Local/mounted or network storage in local network
    - s3fs
      - S3 mounted drive

Some users may require more advanced storage such as S3 provided by amazon or others.

S3 storage setup
----------------

Reffer to this guide for installion - https://github.com/s3fs-fuse/s3fs-fuse

Adding credentials to passwd_file

Create credentials file ``echo ACCESS_KEY_ID:SECRET_ACCESS_KEY > /etc/passwd-s3fs``

Set file permissions ``chmod 600 /etc/passwd-s3fs``


S3 mounting with fstab 
    ``s3fs#bucker_name /media/S3 fuse _netdev,allow_other,uid=33,url=https://s3.ca-central-1.amazonaws.com,passwd_file=/etc/passwd-s3fs,umask=022 0 0``

Setting up storage.
    1. Click on Add new Storage
    2. Set path to ``/media/S3``
    3. Add Url ``s3fs://username:password@s3.ca-central-1.amazonaws.com/bucket-name/events``
    4. Set type to s3fs
    5. Save settings and monitor logs for errors
