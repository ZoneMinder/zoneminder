ZoneMinder
==========

[![Build Status](https://travis-ci.org/ZoneMinder/ZoneMinder.png)](https://travis-ci.org/ZoneMinder/ZoneMinder)

All documentation for ZoneMinder is now online at http://www.zoneminder.com/wiki/index.php/Documentation

## Overview

ZoneMinder is an integrated set of applications which provide a complete surveillance solution allowing capture, analysis, recording and monitoring of any CCTV or security cameras attached to a Linux based machine. It is designed to run on distributions which support the Video For Linux (V4L) interface and has been tested with video cameras attached to BTTV cards, various USB cameras and also supports most IP network cameras. 

## Requirements

If you are installing ZoneMinder from a package, that package should provide all of the needed core components.

### Packages

If you are compiling ZoneMinder from source, the below list contains the packages needed to get ZoneMinder built:

#### Debian

A fresh build based on the master branch running Debian 7 (wheezy):

```bash
root@host:~# aptitude install -y apache2 mysql-server php5 php5-mysql build-essential libmysqlclient-dev libssl-dev libbz2-dev libpcre3-dev libdbi-perl libarchive-zip-perl libdate-manip-perl libdevice-serialport-perl libmime-perl libpcre3 libwww-perl libdbd-mysql-perl libsys-mmap-perl yasm subversion automake autoconf libjpeg8-dev libjpeg8 apache2-mpm-prefork libapache2-mod-php5 php5-cli libphp-serialization-perl libgnutls-dev libjpeg8-dev libavcodec-dev libavformat-dev libswscale-dev libavutil-dev libv4l-dev libtool ffmpeg libnetpbm10-dev libavdevice-dev libmime-lite-perl dh-autoreconf dpatch;

root@host:~# git clone https://github.com/ZoneMinder/ZoneMinder.git zoneminder;
root@host:~# cd zoneminder;
root@host:~# dpkg-checkbuilddeps;
root@host:~# dpkg-buildpackage;
```

One level above in the fs hierarchy you'll now find a deb package matching the architecture of the build host (here - amd64):

```bash
root@host:~# ls -1 ~/zoneminder*;
/root/zoneminder_1.26.4-1_amd64.changes
/root/zoneminder_1.26.4-1_amd64.deb
/root/zoneminder_1.26.4-1.dsc
/root/zoneminder_1.26.4-1.tar.gz
```

The dpkg command itself does not resolve dependencies. That's what high-level interfaces like aptitude and apt-get are normally. fUnfortunately, unlike RPM, there's no easy way to install a separate deb package that's not contained with any repository. To overcome this "limitation" we'll use dpkg for initially installing the zoneminder package and apt-get to fetch all needed dependencies. Afterwards running dpkg-reconfigure ensures that the respective scripts for database provisioning were executed:
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

Two additional repositories must be added before one can build zoneminder on CentOS or RHEL:

1. RepoForge (formerly RPMForge) http://repoforge.org/use/
2. EPEL https://fedoraproject.org/wiki/EPEL

Once those are added, install the following:
```bash
sudo yum install automake bzip2-devel ffmpeg ffmpeg-devel gnutls-devel httpd libjpeg-turbo libjpeg-turbo-devel mysql-devel mysql-server pcre-devel \
perl-Archive-Tar perl-Archive-Zip perl-Convert-BinHex perl-Date-Manip perl-DBD-MySQL perl-DBI perl-Device-SerialPort perl-Email-Date-Format perl-IO-stringy \
perl-IO-Zlib perl-MailTools perl-MIME-Lite perl-MIME-tools perl-MIME-Types perl-Module-Load perl-Package-Constants perl-Sys-Mmap perl-Time-HiRes \
perl-TimeDate perl-YAML-Syck php php-cli php-mysql subversion x264
```

### ffmpeg

This release of ZoneMinder has been tested on and works with ffmpeg version N-55540-g93f4277.
