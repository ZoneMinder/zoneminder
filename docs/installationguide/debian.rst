Debian
======

.. contents::

Easy Way: Debian Jessie
-----------------------

**Step 1:** Setup Sudo

By default Debian does not come with sudo. Log in as root or use su command.
N.B. The instructions below are for setting up sudo for your current account, you can
do this as root if you prefer.

::
    
    aptitude update
    aptitude install sudo
    usermod -a -G sudo <username>
    exit

Logout or try ``newgrp`` to reload user groups

**Step 2:** Run sudo and update

Now run session using sudo and ensure system is updated.
::

    sudo -i
    aptitude safe-upgrade

**Step 3:** Install Apache and MySQL

These are not dependencies for the package as they could
be installed elsewhere.

::

    aptitude install apache2 mysql-server

**Step 4:** Edit sources.list to add jessie-backports

::

    nano /etc/apt/sources.list

Add the following to the bottom of the file

::

    # Backports repository
    deb http://httpredir.debian.org/debian jessie-backports main contrib non-free

CTRL+o and <Enter> to save
CTRL+x to exit

**Step 5:** Install ZoneMinder

::

    aptitude update
    aptitude install zoneminder

**Step 6:** Read the Readme

The rest of the install process is covered in the README.Debian, so feel free to have
a read.

::

    gunzip /usr/share/doc/zoneminder/README.Debian.gz
    cat /usr/share/doc/zoneminder/README.Debian

**Step 7:** Setup Database

Install the zm database and setup the user account. Refer to Hints in Ubuntu install
should you choose to change default database user and password.

::

    cat /usr/share/zoneminder/db/zm_create.sql | sudo mysql --defaults-file=/etc/mysql/debian.cnf
    echo 'grant lock tables,alter,create,select,insert,update,delete,index on zm.* to 'zmuser'@localhost identified by "zmpass";'    | sudo mysql --defaults-file=/etc/mysql/debian.cnf mysql

** Step 8:** zm.conf Permissions

Adjust permissions to the zm.conf file to allow web account to access it.

::

    chgrp -c www-data /etc/zm/zm.conf

**Step 9:** Setup ZoneMinder service

::

    systemctl enable zoneminder.service

**Step 10:** Configure Apache

The following commands will setup the default /zm virtual directory and configure
required apache modules.

::

    a2enconf zoneminder
    a2enmod cgi
    a2enmod rewrite

**Step 11:** Edit Timezone in PHP

::

    nano /etc/php5/apache2/php.ini

Search for [Date] (Ctrl + w then type Date and press Enter) and change 
date.timezone for your time zone. **Don't forget to remove the ; from in front
of date.timezone**

::

        [Date]
        ; Defines the default timezone used by the date functions
        ; http://php.net/date.timezone
        date.timezone = America/New_York

CTRL+o then [Enter] to save

CTRL+x to exit

**Step 12:** Start ZoneMinder

Reload Apache to enable your changes and then start ZoneMinder.

::

    systemctl reload apache2
    systemctl start zoneminder

**Step 13:** Making sure ZoneMinder works

1. Open up a browser and go to ``http://hostname_or_ip/zm`` - should bring up ZoneMinder Console

2. (Optional API Check)Open up a tab in the same browser and go to ``http://hostname_or_ip/zm/api/host/getVersion.json``

    If it is working correctly you should get version information similar to the example below:

    ::

            {
                "version": "1.29.0",
                "apiversion": "1.29.0.1"
            }

**Congratulations**  Your installation is complete
