Frequently Asked Questions (FAQ)
================================

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
^^^^^^^^^^^^^^^

1. In main console, go to Options->Users.

The "Zones" view for a Monitor is blank (I can't see / setup a Zone)
====================================================================

Snapshots and Zones images are stored in the `images` directory in your webroot.
Ensure that the `images` directory is writable by the user which ZoneMinder is
running as.  If the `images` directory is a symlink, ensure that your web server
has access to that directory as well.
