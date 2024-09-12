Debian
======

.. contents::

Debian 12 (Bookworm)
--------------------

This procedure will guide you through the installation of ZoneMinder on Debian 12 (Bookworm).

**Step 1:** Setup Sudo (optional but recommended)

By default Debian does not come with sudo, so you have to install it and configure it manually.
This step is optional but recommended and the following instructions assume that you have setup sudo.
If you prefer to setup ZoneMinder as root, do it at your own risk and adapt the following instructions accordingly.

::

    apt install sudo
    usermod -a -G sudo <username>
    exit

Now your terminal session is back under your normal user. You can check that 
you are now part of the sudo group with the command ``groups``, "sudo" should
appear in the list. If not, run ``newgrp sudo`` and check again with ``groups``.

**Step 2:** Update system

Run the following commands.

::

    sudo apt update
    sudo apt upgrade

**Step 3:** Install MariaDB and do initial database configuration

Run the following commands.

::

    sudo apt install mariadb-server

Switch into root user and create database and database user

::

    sudo su
    mariadb
    CREATE DATABASE zm;
    CREATE USER zmuser@localhost IDENTIFIED BY 'zmpass';
    GRANT ALL ON zm.* TO zmuser@localhost;
    FLUSH PRIVILEGES;
    exit;
    exit

By default MariaDB uses `unix socket authentication`_, so no root user password is required (root MariaDB user access only available to local root Linux user). If you wish, you can set a root MariaDB password (and apply other security tweaks) by running `mariadb-secure-installation`_.

**Step 4:** Install zoneminder

By default Debian will install the version published in Debian (stable). However there may be newer versions by using backports.
At the time of this writing, bookworm (stable) ships with v.1.36.33.

To install the version in bookworm stable, just run the following command.

::

    sudo apt install zoneminder


If instead you prefer to install the newer version using backports, run the following commands.
The first line will add this bookworm-backports repository.
The backports repository is deactivated by default, so with the second line we explicityly state we want the backported version of zoneminder.

::

    sudo echo 'deb http://deb.debian.org/debian bookworm-backports main contrib' >> /etc/apt/sources.list
    sudo apt update
    sudo apt -t bookworm-backports install zoneminder


**Step 5:** Configure database

:: 

    mariadb -u zmuser -p zm < /usr/share/zoneminder/db/zm_create.sql

**Step 6:** Setup permissions for zm.conf

To make sure zoneminder can read the configuration file, run the following command.

::

    sudo chgrp -c www-data /etc/zm/zm.conf

**Step 7:** Tweak Apache configuration

::

    sudo a2enconf zoneminder
    sudo systemctl reload apache2.service
    sudo systemctl restart zoneminder.service
    sudo systemctl status zoneminder.service
    sudo systemctl enable zoneminder.service    # start zoneminder automatically at boot

If the zoneminder.service show to be active and without any errors, you should be able to access zoneminder at ``http://yourhostname/zm``

Debian 11 (Bullseye)
--------------------

This procedure will guide you through the installation of ZoneMinder on Debian 11 (Bullseye).

**Step 1:** Setup Sudo (optional but recommended)

By default Debian does not come with sudo, so you have to install it and configure it manually.
This step is optional but recommended and the following instructions assume that you have setup sudo.
If you prefer to setup ZoneMinder as root, do it at your own risk and adapt the following instructions accordingly.

::

    apt install sudo
    usermod -a -G sudo <username>
    exit

Now your terminal session is back under your normal user. You can check that 
you are now part of the sudo group with the command ``groups``, "sudo" should
appear in the list. If not, run ``newgrp sudo`` and check again with ``groups``.

**Step 2:** Update system and install zoneminder

Run the following commands.

::

    sudo apt update
    sudo apt upgrade
    sudo apt install mariadb-server

Switch into root user and create database and database user

::

    sudo su
    mariadb
    CREATE DATABASE zm;
    CREATE USER zmuser@localhost IDENTIFIED BY 'zmpass';
    GRANT ALL ON zm.* TO zmuser@localhost;
    FLUSH PRIVILEGES;
    exit;
    exit

By default MariaDB uses `unix socket authentication`_, so no root user password is required (root MariaDB user access only available to local root Linux user). If you wish, you can set a root MariaDB password (and apply other security tweaks) by running `mariadb-secure-installation`_.

**Step 4:** Install zoneminder

By default Debian will install the version published in Debian (stable). However you also have the option to install a newer version using backports.
For example, at the time of this writing, bullseye (stable) ships with v.1.34.x and bullseye-backports with v.1.36.x.

To install the version in bullseye stable, just run the following command.

::

    sudo apt install zoneminder

When mariadb is installed for the first time, it doesn't add a password to the root user. Therefore, for security, it is recommended to run ``mysql secure installation``.

If instead you prefer to install the newer version using backports, run the following commands.
The first line will add bullseye-backports repository.
The backports repository is deactivated by default, so with the second line we explicityly state we want the backported version of zoneminder.

::

    sudo echo 'deb http://deb.debian.org/debian bullseye-backports main contrib' >> /etc/apt/sources.list
    sudo apt update && sudo apt -t bullseye-backports install zoneminder


**Step 5:** Configure database

:: 

    mariadb -u zmuser -p zm < /usr/share/zoneminder/db/zm_create.sql

**Step 6:** Setup permissions for zm.conf

To make sure zoneminder can read the configuration file, run the following command.

::

    sudo chgrp -c www-data /etc/zm/zm.conf

Congratulations! You should now be able to access zoneminder at ``http://yourhostname/zm``

::

    sudo a2enconf zoneminder
    sudo systemctl reload apache2.service
    sudo systemctl restart zoneminder.service
    sudo systemctl status zoneminder.service

If the zoneminder.service show to be active and without any errors, you should be able to access zoneminder at ``http://yourhostname/zm``

Debian Buster
-------------

This procedure will guide you through the installation of ZoneMinder on Debian 10 (Buster).

**Step 1:** Make sure your system is up to date

Open a console and use ``su`` command to become root.

::

    apt update
    apt upgrade


**Step 2:** Setup Sudo (optional but recommended)

By default Debian does not come with sudo, so you have to install it and configure it manually.
This step is optional but recommended and the following instructions assume that you have setup sudo.
If you prefer to setup ZoneMinder as root, do it at your own risk and adapt the following instructions accordingly.

::

    apt install sudo
    usermod -a -G sudo <username>
    exit

Now your terminal session is back under your normal user. You can check that 
you are now part of the sudo group with the command ``groups``, "sudo" should
appear in the list. If not, run ``newgrp sudo`` and check again with ``groups``.


**Step 3:** Install Apache and MySQL

These are not dependencies for the ZoneMinder package as they could be
installed elsewhere. If they are not installed yet in your system, you have to
trigger their installation manually.

::

    sudo apt install apache2 default-mysql-server

**Step 4:** Add ZoneMinder's Package repository to your apt sources

ZoneMinder's Debian packages are not included in Debian's official package
repositories. To be able to install ZoneMinder with APT, you have to edit the
list of apt sources and add ZoneMinder's repository.

Add the following to the /etc/apt/sources.list.d/zoneminder.list file

::

    # ZoneMinder repository
    deb https://zmrepo.zoneminder.com/debian/release-1.36 buster/

You can do this using:

::

    echo "deb https://zmrepo.zoneminder.com/debian/release-1.36 buster/" | sudo tee /etc/apt/sources.list.d/zoneminder.list

Because ZoneMinder's package repository provides a secure connection through HTTPS, apt must be enabled for HTTPS.
::

    sudo apt install apt-transport-https

Ensure you have gnupg installed before importing the apt key in the following step.
::

    sudo apt install gnupg


Finally, download the GPG key for ZoneMinder's repository:
::

    wget -O - https://zmrepo.zoneminder.com/debian/archive-keyring.gpg | sudo apt-key add -


**Step 5:** Install ZoneMinder

::

    sudo apt update
    sudo apt install zoneminder

**Step 6:** Read the Readme

The rest of the install process is covered in the README.Debian, so feel free to have
a read.

::

    zcat /usr/share/doc/zoneminder/README.Debian.gz


**Step 7:** Enable ZoneMinder service

::

    sudo systemctl enable zoneminder.service

**Step 8:** Configure Apache

The following commands will setup the default /zm virtual directory and configure
required apache modules.

::

    sudo a2enconf zoneminder
    sudo a2enmod rewrite # this is enabled by default
    sudo a2enmod cgi # this is done automatically when installing the package. Redo this command manually only for troubleshooting.


**Step 9:** Edit Timezone in PHP

Automated way:
::

    sudo sed -i "s/;date.timezone =/date.timezone = $(sed 's/\//\\\//' /etc/timezone)/g" /etc/php/7.*/apache2/php.ini

Manual way
::

    sudo nano /etc/php/7.*/apache2/php.ini

Search for [Date] (Ctrl + w then type Date and press Enter) and change
date.timezone for your time zone. Don't forget to remove the ; from in front
of date.timezone.

::

        [Date]
        ; Defines the default timezone used by the date functions
        ; http://php.net/date.timezone
        date.timezone = America/New_York

CTRL+o then [Enter] to save

CTRL+x to exit


**Step 10:** Start ZoneMinder

Reload Apache to enable your changes and then start ZoneMinder.

::

    sudo systemctl reload apache2
    sudo systemctl start zoneminder

You are now ready to go with ZoneMinder. Open a browser and type either ``localhost/zm`` one the local machine or ``{IP-OF-ZM-SERVER}/zm`` if you connect from a remote computer.

Easy Way: Debian Stretch
------------------------

This procedure will guide you through the installation of ZoneMinder on Debian 9 (Stretch). This section has been tested with ZoneMinder 1.36 on Debian 9.8.

**Step 1:** Make sure your system is up to date

Open a console and use ``su`` command to become Root.

::

    apt update
    apt upgrade


**Step 2:** Setup Sudo (optional but recommended)

By default Debian does not come with sudo, so you have to install it and configure it manually. This step is optional but recommended and the following instructions assume that you have setup sudo. If you prefer to setup ZoneMinder as root, do it at your own risk and adapt the following instructions accordingly.

::

    apt install sudo
    usermod -a -G sudo <username>
    exit

Now your terminal session is back under your normal user. You can check that you are now part of the sudo group with the command ``groups``, "sudo" should appear in the list. If not, run ``newgrp sudo`` and check again with ``groups``.


**Step 3:** Install Apache and MySQL

These are not dependencies for the ZoneMinder package as they could be installed elsewhere. If they are not installed yet in your system, you have to trigger their installation manually.

::

    sudo apt install apache2 mysql-server

**Step 4:** Add ZoneMinder's Package repository to your apt sources

ZoneMinder's Debian packages are not included in Debian's official package repositories. To be able to install ZoneMinder with APT, you have to edit the list of apt sources and add ZoneMinder's repository.

::

    sudo nano /etc/apt/sources.list

Add the following to the bottom of the file

::

    # ZoneMinder repository
    deb https://zmrepo.zoneminder.com/debian/release-1.36 stretch/

CTRL+o and <Enter> to save
CTRL+x to exit

Because ZoneMinder's package repository provides a secure connection through HTTPS, apt must be enabled for HTTPS.
::

    sudo apt install apt-transport-https

Finally, download the GPG key for ZoneMinder's repository:
::

    wget -O - https://zmrepo.zoneminder.com/debian/archive-keyring.gpg | sudo apt-key add -


**Step 5:** Install ZoneMinder

::

    sudo apt update
    sudo apt install zoneminder

**Step 6:** Read the Readme

The rest of the install process is covered in the README.Debian, so feel free to have
a read.

::

    zcat /usr/share/doc/zoneminder/README.Debian.gz


**Step 7:** Enable ZoneMinder service

::

    sudo systemctl enable zoneminder.service

**Step 8:** Configure Apache

The following commands will setup the default /zm virtual directory and configure
required apache modules.

::

    sudo a2enconf zoneminder
    sudo a2enmod rewrite
    sudo a2enmod cgi # this is done automatically when installing the package. Redo this command manually only for troubleshooting.


**Step 9:** Edit Timezone in PHP

Automated way:
::

    sudo sed -i "s/;date.timezone =/date.timezone = $(sed 's/\//\\\//' /etc/timezone)/g" /etc/php/7.0/apache2/php.ini

Manual way
::

    sudo nano /etc/php/7.0/apache2/php.ini

Search for [Date] (Ctrl + w then type Date and press Enter) and change
date.timezone for your time zone. Don't forget to remove the ; from in front
of date.timezone.

::

        [Date]
        ; Defines the default timezone used by the date functions
        ; http://php.net/date.timezone
        date.timezone = America/New_York

CTRL+o then [Enter] to save

CTRL+x to exit


**Step 10:** Start ZoneMinder

Reload Apache to enable your changes and then start ZoneMinder.

::

    sudo systemctl reload apache2
    sudo systemctl start zoneminder

You are now ready to go with ZoneMinder. Open a browser and type either ``localhost/zm`` one the local machine or ``{IP-OF-ZM-SERVER}/zm`` if you connect from a remote computer.


Easy Way: Debian Jessie
-----------------------

**Step 1:** Setup Sudo

By default Debian does not come with sudo. Log in as root or use su command.
N.B. The instructions below are for setting up sudo for your current account, you can
do this as root if you prefer.

::

    apt-get update
    apt-get install sudo
    usermod -a -G sudo <username>
    exit

Logout or try ``newgrp`` to reload user groups

**Step 2:** Run sudo and update

Now run session using sudo and ensure system is updated.
::

    sudo -i
    apt-get upgrade

**Step 3:** Install Apache and MySQL

These are not dependencies for the package as they could
be installed elsewhere.

::

    apt-get install apache2 mysql-server

**Step 4:** Edit sources.list to add jessie-backports

::

    nano /etc/apt/sources.list

Add the following to the bottom of the file

::

    # Backports repository
    deb http://archive.debian.org/debian/ jessie-backports main contrib non-free

CTRL+o and <Enter> to save
CTRL+x to exit

Run the following

::

    echo 'Acquire::Check-Valid-Until no;' > /etc/apt/apt.conf.d/99no-check-valid-until

**Step 5:** Install ZoneMinder

::

    apt-get update
    apt-get install zoneminder

**Step 6:** Read the Readme

The rest of the install process is covered in the README.Debian, so feel free to have
a read.

::

    zcat /usr/share/doc/zoneminder/README.Debian.gz

**Step 7:** Setup Database

Install the zm database and setup the user account. Refer to Hints in Ubuntu install
should you choose to change default database user and password.

::

    cat /usr/share/zoneminder/db/zm_create.sql | sudo mysql --defaults-file=/etc/mysql/debian.cnf
    echo 'grant lock tables,alter,create,select,insert,update,delete,index on zm.* to 'zmuser'@localhost identified by "zmpass";'    | sudo mysql --defaults-file=/etc/mysql/debian.cnf mysql

**Step 8:** zm.conf Permissions

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


**Step 12:** Please check the configuration

    1. Check path of ZM_PATH in '/etc/zm/conf.d/zmcustom.conf' is ZM_PATH_ZMS=/zm/cgi-bin/nph-zms
        ::
            cat /etc/zm/conf.d/zmcustom.conf
            
    2. Check config of /etc/apache2/conf-enabled/zoneminder.conf has the same ScriptAlias /zm/cgi-bin that is configured
       in ZM_PATH. The part /nph-zms has to be left out of the ScriptAlias
       
        ScriptAlias /zm/cgi-bin "/usr/lib/zoneminder/cgi-bin"
        <Directory "/usr/lib/zoneminder/cgi-bin">
        
        ::
            cat /etc/apache2/conf-enabled/zoneminder.conf 

**Step 13:** Start ZoneMinder

Reload Apache to enable your changes and then start ZoneMinder.

::

    systemctl reload apache2
    systemctl start zoneminder

**Step 14:** Making sure ZoneMinder works

1. Open up a browser and go to ``http://hostname_or_ip/zm`` - should bring up ZoneMinder Console

2. (Optional API Check)Open up a tab in the same browser and go to ``http://hostname_or_ip/zm/api/host/getVersion.json``

    If it is working correctly you should get version information similar to the example below:

    ::

            {
                "version": "1.34.0",
                "apiversion": "1.34.0.1"
            }

**Congratulations**  Your installation is complete
