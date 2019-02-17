Dedicated Drive, Partition, or Network Share
============================================

One of the first steps the end user must perform after installing ZoneMinder is to dedicate an entire partition, drive, or network share for ZoneMinder's event storage.
The reason being, ZoneMinder will, by design, fill up your hard disk, and you don't want to do that to your root volume!

The following steps apply to ZoneMinder 1.31 or newer, running on a typical Linux system, which uses systemd.
If you are using an older version of ZoneMinder, please follow the legacy steps in the `ZoneMinder Wiki <https://wiki.zoneminder.com/Using_a_dedicated_Hard_Drive>`_.

**Step 1:** Stop ZoneMinder
::

    sudo systemctl stop zoneminder

**Step 2:** Mount your dedicated drive, partition, or network share to the local filesystem in any folder of your choosing.
We recommend you use systemd to manage the mount points. 
Instructions on how to accomplish this can be found `here <https://zoneminder.blogspot.com/p/blog-page.html>`__ and `here <https://wiki.zoneminder.com/Common_Issues_with_Zoneminder_Installation_on_Ubuntu#Use_Systemd_to_Mount_Internal_Drive_or_NAS>`__.
Note that bind mounting ZoneMinder's images folder is optional. Newer version of ZoneMinder write very little, if anything, to the images folder.
Verify the dedicated drive, partition, or network share is successfully mounted before proceeding to the next step.

**Step 3:** Set the owner and group to that of the web server user account. Debian based distros typically use "www-data" as the web server user account while many rpm based distros use "apache".
::

    sudo chown -R apache:apache /path/to/your/zoneminder/events/folder
    sudo chown -R apache:apache /path/to/your/zoneminder/images/folder

Recall from the previous step, the images folder is optional.

**Step 4:** Create a config file under /etc/zm/conf.d using your favorite text editor. Name the file anything you want just as long as it ends in ".conf".
Add the following content to the file and save your changes:
::

    ZM_DIR_EVENTS=/full/path/to/the/events/folder

**Step 5:** Start ZoneMinder and inspect the ZoneMinder log files for errors.
::

    sudo systemctl start zoneminder
