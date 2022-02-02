Debian
======

.. contents::

Easy Way: Debian 11 (Bullseye)
------------------------------

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
    sudo apt install zoneminder

By default MariaDB uses `unix socket authentication`_, so no root user password is required (root MariaDB user access only available to local root Linux user). If you wish, you can set a root MariaDB password (and apply other security tweaks) by running `mariadb-secure-installation`_.

**Step 3:** Setup permissions for zm.conf

To make sure zoneminder can read the configuration file, run the following command.

::

    sudo chgrp -c www-data /etc/zm/zm.conf

Congratulations! You should now be able to access zoneminder at ``http://yourhostname/zm``

Easy Way: Debian Buster
------------------------

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

.. _unix socket authentication: https://mariadb.com/kb/en/authentication-plugin-unix-socket/
.. _mariadb-secure-installation: https://mariadb.com/kb/en/mysql_secure_installation/
