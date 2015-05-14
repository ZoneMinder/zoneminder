ZoneMinder
==========

[![Build Status](https://travis-ci.org/ZoneMinder/ZoneMinder.png)](https://travis-ci.org/ZoneMinder/ZoneMinder) [![Bountysource](https://api.bountysource.com/badge/team?team_id=204&style=bounties_received)](https://www.bountysource.com/teams/zoneminder/issues?utm_source=ZoneMinder&utm_medium=shield&utm_campaign=bounties_received)

All documentation for ZoneMinder is now online at http://www.zoneminder.com/wiki/index.php/Documentation

## Overview

ZoneMinder is an integrated set of applications which provide a complete surveillance solution allowing capture, analysis, recording and monitoring of any CCTV or security cameras attached to a Linux based machine. It is designed to run on distributions which support the Video For Linux (V4L) interface and has been tested with video cameras attached to BTTV cards, various USB cameras and also supports most IP network cameras. 

## Installation Methods

### Building from Source is Discouraged

Historically, installing ZoneMinder onto your system required building from source code by issuing the traditional configure, make, make install commands.  To get ZoneMinder to build, all of its dependencies had to be determined and installed beforehand. Init and logrotate scripts had to be manually copied into place following the build.  Optional packages such as jscalendar and Cambozola had to be manually installed. Uninstalls could leave stale files around, which could cause problems during an upgrade.  Speaking of upgrades, when it comes time to upgrade all these manual steps must be repeated again.

Better methods exist today that do much of this for you. The current development team has taken great strides in providing the resources necessary to avoid building from source.  

### Install from a Package Repository

This is the recommended method to install ZoneMinder onto your system. ZoneMinder packages are maintained for the following distros:

- Ubuntu via Iconnor's PPA: https://launchpad.net/~iconnor/+archive/ubuntu/zoneminder
- Debian from their default repository: https://packages.debian.org/search?searchon=names&keywords=zoneminder
- RHEL/CentOS and clones: http://zmrepo.zoneminder.com/
- Fedora: http://zmrepo.zoneminder.com/
- OpenSuse via third party repository: http://www.zoneminder.com/wiki/index.php/Installing_using_ZoneMinder_RPMs_for_SuSE
- Maegia from their default repository

If a repository that hosts ZoneMinder packages is not available for your distro, then you are encouraged to build your own package, rather than build from source.  While each distro is different in ways that set it apart from all the others, they are often similar enough to allow you to adapt another distro's package building instructions to your own.

### Building a ZoneMinder Package

Building ZoneMinder into a package is not any harder than building from source.  As a matter of fact, if you have successfully built ZoneMinder from source in the past, then you may find these steps to be easier. 

When building a package, it is best to do this work in a separate environment, dedicated to development purposes. This could be as simple as creating a virtual machine, using Docker, or using mock.  All it takes is one “Oops” to regret doing this work on your production server.

Lastly, if you desire to build a development snapshot from the master branch, it is recommended you first build your package using an official release of ZoneMinder. This will help identify whether any problems you may encounter are caused by the build process or is a new issue in the master branch.

What follows are instructions for various distros to build ZoneMinder into a package.

### Package Maintainters
Many of the ZoneMinder configration variable default values are not configurable at build time through autotools or cmake.  A new tool called *zmeditconfigdata.sh* has been added to allow package maintainers to manipulate any variable stored in ConfigData.pm without patching the source. 

For example, let's say I have created a new ZoneMinder package that contains the cambolzola javascript file.  However, by default cambozola support is turned off.  To fix that, add this to the pacakging script:
```bash
./utils/zmeditconfigdata.sh ZM_OPT_CAMBOZOLA yes
```

Note that zmeditconfigdata.sh is intended to be called, from the root build folder, prior to running cmake or configure.

#### Ubuntu

A fresh build based on master branch running Ubuntu 1204 LTS.  Will likely work for other versions as well.

```bash
root@host:~# aptitude install -y apache2 mysql-server php5 php5-mysql build-essential libmysqlclient-dev libssl-dev libbz2-dev libpcre3-dev libdbi-perl libarchive-zip-perl libdate-manip-perl libdevice-serialport-perl libmime-perl libpcre3 libwww-perl libdbd-mysql-perl libsys-mmap-perl yasm automake autoconf libjpeg8-dev libjpeg8 apache2-mpm-prefork libapache2-mod-php5 php5-cli libphp-serialization-perl libgnutls-dev libjpeg8-dev libavcodec-dev libavformat-dev libswscale-dev libavutil-dev libv4l-dev libtool ffmpeg libnetpbm10-dev libavdevice-dev libmime-lite-perl dh-autoreconf dpatch;

root@host:~# git clone https://github.com/ZoneMinder/ZoneMinder.git zoneminder;
root@host:~# cd zoneminder;
root@host:~# ln -s distros/ubuntu1204 debian;
root@host:~# dpkg-checkbuilddeps;
root@host:~# dpkg-buildpackage;
```

One level above you'll now find a deb package matching the architecture of the build host:

```bash
root@host:~# ls -1 ~/zoneminder*;
/root/zoneminder_1.26.4-1_amd64.changes
/root/zoneminder_1.26.4-1_amd64.deb
/root/zoneminder_1.26.4-1.dsc
/root/zoneminder_1.26.4-1.tar.gz
```

The dpkg command itself does not resolve dependencies. That's what high-level interfaces like aptitude and apt-get are normally for. Unfortunately, unlike RPM, there's no easy way to install a separate deb package not contained with any repository.

To overcome this "limitation" we'll use dpkg only to install the zoneminder package and apt-get to fetch all needed dependencies afterwards. Running dpkg-reconfigure in the end will ensure that the setup scripts e.g. for database provisioning were executed.

```bash
root@host:~# dpkg -i /root/zoneminder_1.26.4-1_amd64.deb; apt-get install -f;
root@host:~# dpkg-reconfigure zoneminder;
```
Alternatively you may also use gdebi to automatically resolve dependencies during installation:

```bash
root@host:~# aptitude install -y gdebi;
root@host:~# gdebi /root/zoneminder_1.26.4-1_amd64.deb;
```
```bash
sudo apt-get install apache2 mysql-server php5 php5-mysql build-essential libmysqlclient-dev libssl-dev libbz2-dev \
libpcre3-dev libdbi-perl libarchive-zip-perl libdate-manip-perl libdevice-serialport-perl libmime-perl libpcre3 \
libwww-perl libdbd-mysql-perl libsys-mmap-perl yasm automake autoconf libjpeg-turbo8-dev libjpeg-turbo8 \
apache2-mpm-prefork libapache2-mod-php5 php5-cli
```

#### Debian

A fresh build based on master branch running Debian 7 (wheezy):

```bash
root@host:~# aptitude install -y apache2 mysql-server php5 php5-mysql build-essential libmysqlclient-dev libssl-dev libbz2-dev libpcre3-dev libdbi-perl libarchive-zip-perl libdate-manip-perl libdevice-serialport-perl libmime-perl libpcre3 libwww-perl libdbd-mysql-perl libsys-mmap-perl yasm automake autoconf libjpeg8-dev libjpeg8 apache2-mpm-prefork libapache2-mod-php5 php5-cli libphp-serialization-perl libgnutls-dev libjpeg8-dev libavcodec-dev libavformat-dev libswscale-dev libavutil-dev libv4l-dev libtool ffmpeg libnetpbm10-dev libavdevice-dev libmime-lite-perl dh-autoreconf dpatch;

root@host:~# git clone https://github.com/ZoneMinder/ZoneMinder.git zoneminder;
root@host:~# cd zoneminder;
root@host:~# ln -s distros/debian;
root@host:~# dpkg-checkbuilddeps;
root@host:~# dpkg-buildpackage;
```

One level above you'll now find a deb package matching the architecture of the build host:

```bash
root@host:~# ls -1 ~/zoneminder*;
/root/zoneminder_1.26.4-1_amd64.changes
/root/zoneminder_1.26.4-1_amd64.deb
/root/zoneminder_1.26.4-1.dsc
/root/zoneminder_1.26.4-1.tar.gz
```

The dpkg command itself does not resolve dependencies. That's what high-level interfaces like aptitude and apt-get are normally for. Unfortunately, unlike RPM, there's no easy way to install a separate deb package not contained with any repository.

To overcome this "limitation" we'll use dpkg only to install the zoneminder package and apt-get to fetch all needed dependencies afterwards. Running dpkg-reconfigure in the end will ensure that the setup scripts e.g. for database provisioning were executed.

```bash
root@host:~# dpkg -i /root/zoneminder_1.26.4-1_amd64.deb; apt-get install -f;
root@host:~# dpkg-reconfigure zoneminder;
```
Alternatively you may also use gdebi to automatically resolve dependencies during installation:

```bash
root@host:~# aptitude install -y gdebi;
root@host:~# gdebi /root/zoneminder_1.26.4-1_amd64.deb;
```

#### CentOS / RHEL

DEPRECIATED.  The steps below are depreciated. I am in the middle of rewriting this entire section.

Additional repositories must be added before one can build zoneminder on CentOS or RHEL:

1. Zmrepo [ZoneMinder WiKi](http://www.zoneminder.com/wiki/index.php/CentOS#Zmrepo_-_A_ZoneMinder_repository_for_RPM_based_distros)
2. EPEL https://fedoraproject.org/wiki/EPEL
3. RPMFusion: http://rpmfusion.org/

When adding third party repositories, it is highly recommended that the user also install and configure yum priorities as documented in the [CentOS WiKi](http://wiki.centos.org/PackageManagement/Yum/Priorities)

Prioritize the repositories:

1. Base
2. EPEL
3. RPMFusion
4. Zmrepo

Once your repos are in order, install the following:
```bash
sudo yum install cmake bzip2-devel ffmpeg ffmpeg-devel gnutls-devel httpd libjpeg-turbo libjpeg-turbo-devel mysql-devel mysql-server pcre-devel \
perl-Archive-Tar perl-Archive-Zip perl-Convert-BinHex perl-Date-Manip perl-DBD-MySQL perl-DBI perl-Device-SerialPort perl-Email-Date-Format perl-IO-stringy \
perl-IO-Zlib perl-MailTools perl-MIME-Lite perl-MIME-tools perl-MIME-Types perl-Module-Load perl-Package-Constants perl-Sys-Mmap perl-Time-HiRes \
perl-TimeDate perl-YAML-Syck perl-X10 perl-URI-Encode php php-cli php-mysql x264 vlc-devel vlc-core \
libcurl libcurl-devel polkit-devel git
```

To build from the master branch:
```bash
git clone https://github.com/ZoneMinder/ZoneMinder.git
cd ZoneMinder
cmake .
make
sudo make install
```

IMPORTANT: Don't forget the trailing "." when calling cmake

#### Docker

Docker is a system to run applications inside isolated containers. ZoneMinder, and the ZM webserver, will run using the 
Dockerfile contained in this repository. However, there is still work needed to ensure that the main ZM features work 
properly and are documented. 

## Contribution Model and  Development

* Source hosted at [GitHub](https://github.com/ZoneMinder/ZoneMinder/)
* Report issues/questions/feature requests on [GitHub Issues](https://github.com/ZoneMinder/ZoneMinder/issues)

Pull requests are very welcome!  If you would like to contribute, please follow
the following steps.

1. Fork the repo
2. Open an issue at our [GitHub Issues Tracker](https://github.com/ZoneMinder/ZoneMinder/issues).
   Describe the bug that you've found, or the feature which you're asking for.
   Jot down the issue number (e.g. 456)
3. Create your feature branch (`git checkout -b 456-my-new-feature`)
4. Commit your changes (`git commit -am 'Added some feature'`)
   It is preferred that you 'commit early and often' instead of bunching all
   changes into a single commit.
5. Push your branch to your fork on github (`git push origin 456-my-new-feature`)
6. Create new Pull Request
7. The team will then review, discuss and hopefully merge your changes.

[![Analytics](https://ga-beacon.appspot.com/UA-15147273-6/ZoneMinder/README.md)](https://github.com/igrigorik/ga-beacon)
