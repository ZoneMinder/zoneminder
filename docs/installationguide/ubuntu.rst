Ubuntu
======

PPA Install
-----------
Follow these instructions to install current release version on Ubuntu 13.04 or under.:

  sudo apt-add-repository ppa:iconnor/zoneminder

Or Ubuntu 14.10 you will need to install the Snapshot PPA from the master branch instead.:

  sudo apt-add-repository ppa:iconnor/zoneminder-master

Once you have updated the repository then update and install the package.:
  
  sudo apt-get update
  sudo apt-get install zoneminder



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
