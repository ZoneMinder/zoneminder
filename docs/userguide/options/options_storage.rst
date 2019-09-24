Options - Storage
-----------------

.. image:: images/Options_Storage.png

Storage tab is used for setting up multiple storage areas for storing events.
To add a new server use the Add New Storage button. The storage area will require a name and a path. The path should be absolute and if multiple servers are in use, each should have access to it using the same path. The Url field is used for S3 type storage.  S3 storage should be mounted in the filesystem using s3fs and can also be specified in the Url for more efficient access.  
The Do Deletes option tell ZoneMinder whether to actually perform delete operations when deleting events.  S3fs systems often do deletes in a cron job or other background task and doing the deletes can overload an S3 system.

To delete a storage area mark that area and click the Delete button.
