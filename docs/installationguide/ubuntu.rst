Ubuntu
======


PPA Install
-----------
Follow these instructions to install current release version on Ubuntu.:

Pre-requisite
^^^^^^^^^^^^^^^
It is important that you first apply any system software upgrades first to Ubuntu, especially if you have just created a new image of Ubuntu.
Not doing this may cause the PPA process to fail and complain about various unmet dependencies.


If you also plan to install the database in the same server (which is typically the case), first do:

::

	sudo apt-get install mysql-server

This will ask you for a user and password to configure for Zoneminder. 
Note that when you install the PPA, it will also create a  username of zmuser and a password of zmpass irrespective of what you select at this stage


Suggested changes to MySQL (Optional but recommended)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
For most of you Zoneminder will run just fine with the default MySQL settings. There are a couple of settings that may, in time, provide beneficial especially if you have a number of cameras and many events with a lot of files. One setting we recommend is the "innodb_file_per_table" This will be a default setting in MySQL 5.6 but should be added in MySQL 5.5 which comes with Ubuntu 14.04. A description can be found here: http://dev.mysql.com/doc/refman/5.5/en/innodb-multiple-tablespaces.html

To add "innodb_file_per_table" edit the my.cnf file:

``vi /etc/mysql/my.cnf``
Under [mysqld] add
``innodb_file_per_table``

Save and exit.

Restart MySQL
``service mysql restart``

Installing the actual Zoneminder package
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
::

  sudo apt-add-repository ppa:iconnor/zoneminder

Once you have updated the repository then update and install the package.:
  
::

  sudo apt-get update
  sudo apt-get install zoneminder


Post Install Configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^^

We are not done yet. There are some post install steps you need to perform:

We recommend you add a "sleep" command just after ``start() { `` in ``/etc/init.d/zoneminder``` to make sure mysql starts before ZoneMinder does. To do this,
simply modify ``/etc/init.d/zoneminder`` at around line 25 (where you will find the start function) to look like this:

::

	start() {
		echo -n "Making sure mysql started... Sleeping for 10 seconds..."
		sleep 10
		echo -n "Starting $prog: "

Next, we need to make sure apache knows about zoneminder's configuration for apache. 

::

	ln -s /etc/zm/apache.conf /etc/apache2/conf-available/zoneminder.conf

Then

::

	a2enconf zoneminder
	adduser www-data video


Finally, lets make sure we restart apache:

::

	service apache2 restart


You should now be able to view the zoneminder interface at ``http://localhost/zm`` (replace localhost with your server IP if you are accessing it remotely)

.. image:: images/zm_first_screen_post_install.png



Build Package From Source
-------------------------

A fresh build based on master branch running Ubuntu 1204 LTS.  Will likely work for other versions as well.::

  root@host:~# aptitude install -y apache2 mysql-server php5 php5-mysql build-essential libmysqlclient-dev libssl-dev libbz2-dev libpcre3-dev libdbi-perl libarchive-zip-perl libdate-manip-perl libdevice-serialport-perl libmime-perl libpcre3 libwww-perl libdbd-mysql-perl libsys-mmap-perl yasm automake autoconf libjpeg8-dev libjpeg8 apache2-mpm-prefork libapache2-mod-php5 php5-cli libphp-serialization-perl libgnutls-dev libjpeg8-dev libavcodec-dev libavformat-dev libswscale-dev libavutil-dev libv4l-dev libtool ffmpeg libnetpbm10-dev libavdevice-dev libmime-lite-perl dh-autoreconf dpatch;

  root@host:~# git clone https://github.com/ZoneMinder/ZoneMinder.git zoneminder;
  root@host:~# cd zoneminder;
  root@host:~# ln -s distros/ubuntu1204 debian;
  root@host:~# dpkg-checkbuilddeps;
  root@host:~# dpkg-buildpackage;


One level above you'll now find a deb package matching the architecture of the build host\:::

  root@host:~# ls -1 ~/zoneminder\*;
  /root/zoneminder_1.26.4-1_amd64.changes
  /root/zoneminder_1.26.4-1_amd64.deb
  /root/zoneminder_1.26.4-1.dsc
  /root/zoneminder_1.26.4-1.tar.gz


The dpkg command itself does not resolve dependencies. That's what high-level interfaces like aptitude and apt-get are normally for. Unfortunately, unlike RPM, there's no easy way to install a separate deb package not contained with any repository.

To overcome this "limitation" we'll use dpkg only to install the zoneminder package and apt-get to fetch all needed dependencies afterwards. Running dpkg-reconfigure in the end will ensure that the setup scripts e.g. for database provisioning were executed.::

  root@host:~# dpkg -i /root/zoneminder_1.26.4-1_amd64.deb; apt-get install -f;
  root@host:~# dpkg-reconfigure zoneminder;

Alternatively you may also use gdebi to automatically resolve dependencies during installation\:::

  root@host:~# aptitude install -y gdebi;
  root@host:~# gdebi /root/zoneminder_1.26.4-1_amd64.deb;

  sudo apt-get install apache2 mysql-server php5 php5-mysql build-essential libmysqlclient-dev libssl-dev libbz2-dev libpcre3-dev libdbi-perl libarchive-zip-perl libdate-manip-perl libdevice-serialport-perl libmime-perl libpcre3 libwww-perl libdbd-mysql-perl libsys-mmap-perl yasm automake autoconf libjpeg-turbo8-dev libjpeg-turbo8 apache2-mpm-prefork libapache2-mod-php5 php5-cli
