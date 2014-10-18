FAQ
===

This is the FAQ page. Feel free to contribute any FAQs that you think are missing. 

Why can't I view all of my monitors in Montage view?
----------------------------------------------------

1. You will most likely need to increase `mysql_max_connections` in my.cnf.
2. If using Firefox, you may need to increase `network.http.max-persistent-connections-per-server` in `about:config`.


How do I enable ZoneMinder's security?
--------------------------------------

You may also consider to use the web server security, for example, htaccess files under Apache, or mod_auth.

1. In the console, click on Options.
2. Check the box next to "ZM_OPT_USE_AUTH".
3. Click Save
4. You will immediately be asked to login. The username is 'admin' and the password is 'admin'.

To Manage Users
---------------

1. In main console, go to Options->Users.

The "Zones" view for a Monitor is blank (I can't see / setup a Zone)
--------------------------------------------------------------------

Snapshots and Zones images are stored in the `images` directory in your webroot.
Ensure that the `images` directory is writable by the user which ZoneMinder is
running as.  If the `images` directory is a symlink, ensure that your web server
has access to that directory as well.

How do the 3 AlarmCheckMethods interact?
----------------------------------------

In example, if I set the alarm % to 5-10% and the filtered and blob to 1-100%, what happens?

1. If any of the min/max values is 0, the check that the value is applied to is skipped.
2. If you have a min-alarmed area and you're below that, then it quits.
3. If you have a max-alarmed area and you're above that, then it quits.
4. If you're on filtered or blobs

  1. and have a min filtered area that you're below then it quits
  2. and have a max filtered area that you're above then it quits

5. If you're on blobs

  1. any blob smaller than the min blob area (if set) is discarded
  2. any blob larger than the max blob area (if set) is discarded
  3. If there are less remaining blobs than the minimum-blobs, then it quits.
  4. If there are more remaining blobs than the maximum-blobs, then it quits.

If AlarmedPixels is selected, you can only enter min/max pixel threshold and
min/max alarmed area.  If FilteredPixels is selected, the Blob options are
disabled.  The Blob check method allows you to specify all options.  Filtered
adds more checks than alarmed, and blobs adds more checks than filtered.  The
final 'score' is calculated using final check method.
