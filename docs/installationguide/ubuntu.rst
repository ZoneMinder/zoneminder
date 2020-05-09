Ubuntu
======

.. contents::

Easy Way: Ubuntu 18.04 (Bionic)
-------------------------------
These instructions are for a brand new ubuntu 18.04 system which does not have ZM
installed.


It is recommended that you use an Ubuntu Server install and select the LAMP option
during install to install Apache, MySQL and PHP. If you failed to do this you can
achieve the same result by running:

::

    sudo apt-get install tasksel
    sudo tasksel install lamp-server

During installation it will ask you to set up a master/root password for the MySQL.
Installing LAMP is not ZoneMinder specific so you will find plenty of resources to 
guide you with a quick search.

**Step 1:** Either run commands in this install using sudo or use the below to become root
::

    sudo -i

**Step 2:** Update Repos

.. topic :: Latest Release

    ZoneMinder is now part of the current standard Ubuntu repository, but
    sometimes the official repository can lag behind. To find out check our
    `releases page <https://github.com/ZoneMinder/zoneminder/releases>`_ for
    the latest release.
    
    Alternatively, the ZoneMinder project team maintains a `PPA <https://askubuntu.com/questions/4983/what-are-ppas-and-how-do-i-use-them>`_, which is updated immediately
    following a new release of ZoneMinder. To use this repository instead of the
    official Ubuntu repository, enter the following from the command line:

    ::

        add-apt-repository ppa:iconnor/zoneminder-1.34

Update repo and upgrade.

::

	apt-get update
        apt-get upgrade
        apt-get dist-upgrade


**Step 3:** Configure MySQL

.. sidebar :: Note

    The MySQL default configuration file (/etc/mysql/mysql.cnf)is read through
    several symbolic links beginning with /etc/mysql/my.cnf as follows:

    | /etc/mysql/my.cnf -> /etc/alternatives/my.cnf
    | /etc/alternatives/my.cnf -> /etc/mysql/mysql.cnf
    | /etc/mysql/mysql.cnf is a basic file

Certain new defaults in MySQL 5.7 cause some issues with ZoneMinder < 1.32.0,
the workaround is to modify the sql_mode setting of MySQL. Please note that these 
changes are NOT required for ZoneMinder 1.32.0 and some people have reported them 
causing problems in 1.32.0.

To better manage the MySQL server it is recommended to copy the sample config file and
replace the default my.cnf symbolic link.

::

        rm /etc/mysql/my.cnf  (this removes the current symbolic link)
        cp /etc/mysql/mysql.conf.d/mysqld.cnf /etc/mysql/my.cnf

To change MySQL settings:

::

        nano /etc/mysql/my.cnf

In the [mysqld] section add the following

::

        sql_mode = NO_ENGINE_SUBSTITUTION

CTRL+o then [Enter] to save

CTRL+x to exit

Restart MySQL

::

        systemctl restart mysql


**Step 4:** Install ZoneMinder

::

	apt-get install zoneminder

**Step 5:** Configure the ZoneMinder Database

This step should not be required on ZoneMinder 1.32.0.

::

	mysql -uroot -p < /usr/share/zoneminder/db/zm_create.sql
	mysql -uroot -p -e "grant lock tables,alter,drop,select,insert,update,delete,create,index,alter routine,create routine, trigger,execute on zm.* to 'zmuser'@localhost identified by 'zmpass';"


**Step 6:** Set permissions

Set /etc/zm/zm.conf to root:www-data 740 and www-data access to content

::

        chmod 740 /etc/zm/zm.conf
        chown root:www-data /etc/zm/zm.conf
        chown -R www-data:www-data /usr/share/zoneminder/

**Step 7:** Configure Apache correctly:

::

        a2enmod cgi
        a2enmod rewrite
        a2enconf zoneminder

You may also want to enable to following modules to improve caching performance

::

         a2enmod expires
         a2enmod headers

**Step 8:** Enable and start Zoneminder

::

        systemctl enable zoneminder
        systemctl start zoneminder

**Step 9:** Edit Timezone in PHP

::

        nano /etc/php/7.2/apache2/php.ini

Search for [Date] (Ctrl + w then type Date and press Enter) and change
date.timezone for your time zone, see [this](https://www.php.net/manual/en/timezones.php).
**Don't forget to remove the ; from in front of date.timezone**

::

        [Date]
        ; Defines the default timezone used by the date functions
        ; http://php.net/date.timezone
        date.timezone = America/New_York

CTRL+o then [Enter] to save

CTRL+x to exit

**Step 10:** Reload Apache service

::

	systemctl reload apache2

**Step 11:** Making sure ZoneMinder works

1. Open up a browser and go to ``http://hostname_or_ip/zm`` - should bring up ZoneMinder Console

2. (Optional API Check)Open up a tab in the same browser and go to ``http://hostname_or_ip/zm/api/host/getVersion.json``

    If it is working correctly you should get version information similar to the example below:

    ::

            {
                "version": "1.29.0",
                "apiversion": "1.29.0.1"
            }

**Congratulations**  Your installation is complete

PPA install may need some tweaking of ZMS_PATH in ZoneMinder options. `Socket_sendto or no live streaming`_

Easy Way: Ubuntu 16.04 (Xenial)
-------------------------------
These instructions are for a brand new ubuntu 16.04 system which does not have ZM
installed.


It is recommended that you use an Ubuntu Server install and select the LAMP option
during install to install Apache, MySQL and PHP. If you failed to do this you can
achieve the same result by running:

::

    sudo tasksel install lamp-server

During installation it will ask you to set up a master/root password for the MySQL.
Installing LAMP is not ZoneMinder specific so you will find plenty of resources to 
guide you with a quick search.

**Step 1:** Either run commands in this install using sudo or use the below to become root
::

    sudo -i

**Step 2:** Update Repos

.. topic :: Latest Release

    ZoneMinder is now part of the current standard Ubuntu repository, but
    sometimes the official repository can lag behind. To find out check our
    `releases page <https://github.com/ZoneMinder/zoneminder/releases>`_ for
    the latest release.
    
    Alternatively, the ZoneMinder project team maintains a `PPA <https://askubuntu.com/questions/4983/what-are-ppas-and-how-do-i-use-them>`_, which is updated immediately
    following a new release of ZoneMinder. To use this repository instead of the
    official Ubuntu repository, enter the following from the command line:

    ::

        add-apt-repository ppa:iconnor/zoneminder
        add-apt-repository ppa:iconnor/zoneminder-1.32

Update repo and upgrade.

::

	apt-get update
        apt-get upgrade
        apt-get dist-upgrade


**Step 3:** Configure MySQL

.. sidebar :: Note

    The MySQL default configuration file (/etc/mysql/mysql.cnf)is read through
    several symbolic links beginning with /etc/mysql/my.cnf as follows:

    | /etc/mysql/my.cnf -> /etc/alternatives/my.cnf
    | /etc/alternatives/my.cnf -> /etc/mysql/mysql.cnf
    | /etc/mysql/mysql.cnf is a basic file

Certain new defaults in MySQL 5.7 cause some issues with ZoneMinder < 1.32.0,
the workaround is to modify the sql_mode setting of MySQL. Please note that these 
changes are NOT required for ZoneMinder 1.32.0 and some people have reported them 
causing problems in 1.32.0.

To better manage the MySQL server it is recommended to copy the sample config file and
replace the default my.cnf symbolic link.

::

        rm /etc/mysql/my.cnf  (this removes the current symbolic link)
        cp /etc/mysql/mysql.conf.d/mysqld.cnf /etc/mysql/my.cnf

To change MySQL settings:

::

        nano /etc/mysql/my.cnf

In the [mysqld] section add the following

::

        sql_mode = NO_ENGINE_SUBSTITUTION

CTRL+o then [Enter] to save

CTRL+x to exit

Restart MySQL

::

        systemctl restart mysql


**Step 4:** Install ZoneMinder

::

	apt-get install zoneminder

**Step 5:** Configure the ZoneMinder Database

This step should not be required on ZoneMinder 1.32.0.

::

	mysql -uroot -p < /usr/share/zoneminder/db/zm_create.sql
	mysql -uroot -p -e "grant lock tables,alter,drop,select,insert,update,delete,create,index,alter routine,create routine, trigger,execute on zm.* to 'zmuser'@localhost identified by 'zmpass';"


**Step 6:** Set permissions

Set /etc/zm/zm.conf to root:www-data 740 and www-data access to content

::

        chmod 740 /etc/zm/zm.conf
        chown root:www-data /etc/zm/zm.conf
        chown -R www-data:www-data /usr/share/zoneminder/

**Step 7:** Configure Apache correctly:

::

        a2enmod cgi
        a2enmod rewrite
        a2enconf zoneminder

You may also want to enable to following modules to improve caching performance

::

         a2enmod expires
         a2enmod headers

**Step 8:** Enable and start Zoneminder

::

        systemctl enable zoneminder
        systemctl start zoneminder

**Step 9:** Edit Timezone in PHP

::

        nano /etc/php/7.0/apache2/php.ini

Search for [Date] (Ctrl + w then type Date and press Enter) and change
date.timezone for your time zone, see [this](https://www.php.net/manual/en/timezones.php).
**Don't forget to remove the ; from in front of date.timezone**

::

        [Date]
        ; Defines the default timezone used by the date functions
        ; http://php.net/date.timezone
        date.timezone = America/New_York

CTRL+o then [Enter] to save

CTRL+x to exit

**Step 10:** Reload Apache service

::

	systemctl reload apache2

**Step 11:** Making sure ZoneMinder works

1. Open up a browser and go to ``http://hostname_or_ip/zm`` - should bring up ZoneMinder Console

2. (Optional API Check)Open up a tab in the same browser and go to ``http://hostname_or_ip/zm/api/host/getVersion.json``

    If it is working correctly you should get version information similar to the example below:

    ::

            {
                "version": "1.29.0",
                "apiversion": "1.29.0.1"
            }

**Congratulations**  Your installation is complete

PPA install may need some tweaking of ZMS_PATH in ZoneMinder options. `Socket_sendto or no live streaming`_

Easy Way: Ubuntu 14.x (Trusty)
------------------------------
**These instructions are for a brand new ubuntu 14.x system which does not have ZM installed.**

**Step 1:** Either run commands in this install using sudo or use the below to become root

::

    sudo -i

**Step 2:** Install ZoneMinder

::

	add-apt-repository ppa:iconnor/zoneminder
	apt-get update
	apt-get install zoneminder

(just press OK for the prompts you get)

**Step 3:** Set up DB

::

	mysql -uroot -p < /usr/share/zoneminder/db/zm_create.sql
	mysql -uroot -p -e "grant select,insert,update,delete,create,alter,index,lock tables on zm.* to 'zmuser'@localhost identified by 'zmpass';"

**Step 4:** Set up Apache

::

	a2enconf zoneminder
	a2enmod rewrite
	a2enmod cgi

**Step 5:** Make zm.conf readable by web user.

::

	sudo chown www-data:www-data /etc/zm/zm.conf


**Step 6:** Edit Timezone in PHP

::

        nano /etc/php5/apache2/php.ini

Search for [Date] (Ctrl + w then type Date and press Enter) and change
date.timezone for your time zone, see [this](https://www.php.net/manual/en/timezones.php).
**Don't forget to remove the ; from in front of date.timezone**

::

        [Date]
        ; Defines the default timezone used by the date functions
        ; http://php.net/date.timezone
        date.timezone = America/New_York

CTRL+o then [Enter] to save

CTRL+x to exit

**Step 7:** Restart Apache service and start ZoneMinder

::

	service apache2 reload
        service zoneminder start


**Step 8:** Making sure ZoneMinder works

1. Open up a browser and go to ``http://hostname_or_ip/zm`` - should bring up ZoneMinder Console

2. (Optional API Check)Open up a tab in the same browser and go to ``http://hostname_or_ip/zm/api/host/getVersion.json``

    If it is working correctly you should get version information similar to the example below:

    ::

            {
                "version": "1.29.0",
                "apiversion": "1.29.0.1"
            }

**Congratulations**  Your installation is complete

Harder Way: Build Package From Source
-------------------------------------
(These instructions assume installation from source on a ubuntu 15.x+ system)

**Step 1:** Grab the package installer script

::

	wget https://raw.githubusercontent.com/ZoneMinder/ZoneMinder/master/utils/do_debian_package.sh
	chmod a+x do_debian_package.sh


**Step 2:** Update the system

::

	sudo apt-get update


**Step 3** Create the package

To build the latest master snapshot:

::

	./do_debian_package.sh --snapshot=NOW --branch=master --type=local


To build the latest stable release:

::

	./do_debian_package.sh --snapshot=stable --type=local


Note that the distribution will be guessed using ``lsb_release -a 2>/dev/null | grep Codename | awk '{print $2}'``
which simply extracts your distribution name - like "vivid", "trusty" etc. You
can always specify it using --distro=your distro name if you know it. As far as the script
goes, it checks if your distro is "trusty" in which case it pulls in pre-systemd
release configurations and if its not "trusty" it assumes its based on systemd
and pulls in systemd related config files.

(At the end the script will ask if you want to retain the checked out version of
ZoneMinder. If you are a developer and are making local changes, make sure you
select "y" so that the next time you do the build process mentioned here, it
keeps your changes. Selecting any other value than "y" or "Y" will delete the
checked out code and only retain the package)

This should now create a bunch of .deb files

**Step 4:** Install the package

::

	sudo gdebi zoneminder_<version>_<arch>.deb
	(example sudo gdebi zoneminder_1.29.0-vivid-2016012001_amd64.deb)


**This will report DB errors - ignore - you need to configure the DB and some other stuff**

**Step 5:** Post install configuration

Now that you have installed from your own package you can resume following the
standard install guide for your version, start at the step after Install Zoneminder.

Hints
-----
Make sure ZoneMinder and APIs work with security
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

1. Enable OPT_AUTH in ZoneMinder
2. Log out of ZoneMinder in browser
3. Open a new tab in the *same browser* (important) and go to
   ``http://localhost/zm/api/host/getVersion.json`` - should give you "Unauthorized"
   along with a lot more of text
4. Go to another tab in the SAME BROWSER (important) and log into ZM
5. Repeat step 3 and it should give you the ZM and API version

Socket_sendto or no live streaming
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

After you have setup your camera make sure you can view Monitor streams, if not
check some of the common causes:

* Check Apache cgi module is enabled.
* Check Apache /etc/apache2/conf-enabled/zoneminder.conf ScriptAlias matches PATH_ZMS.

        ScriptAlias **/zm/cgi-bin** /usr/lib/zoneminder/cgi-bin

        From console go to ``Options->Path`` and make sure PATH_ZMS is set to **/zm/cgi-bin/**\ nph-zms.


Changed Default DB User
^^^^^^^^^^^^^^^^^^^^^^^

If you have changed your DB login/password from zmuser/zmpass, you need to
update these values in zm.conf.

1. Edit zm.conf to change ZM_DB_USER and ZM_DB_PASS to the values you used.
