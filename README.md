ZoneMinder
==========

[![Build Status](https://travis-ci.org/ZoneMinder/ZoneMinder.png)](https://travis-ci.org/ZoneMinder/ZoneMinder)

All documentation for ZoneMinder is now online at http://www.zoneminder.com/wiki/index.php/Documentation

## Overview

ZoneMinder is an integrated set of applications which provide a complete surveillance solution allowing capture, analysis, recording and monitoring of any CCTV or security cameras attached to a Linux based machine. It is designed to run on distributions which support the Video For Linux (V4L) interface and has been tested with video cameras attached to BTTV cards, various USB cameras and also supports most IP network cameras. 

## Requirements

If you are installing from a package, that package should provide all of the needed core components.

### Packages

If you are compiling from source, the below list contains the packages needed to get ZoneMinder built:

#### Debian / Ubuntu

```bash
sudo apt-get install apache2 mysql-server php5 php5-mysql build-essential libmysqlclient-dev libssl-dev libbz2-dev \
libpcre3-dev libdbi-perl libarchive-zip-perl libdate-manip-perl libdevice-serialport-perl libmime-perl libpcre3 \
libwww-perl libdbd-mysql-perl libsys-mmap-perl yasm subversion automake autoconf libjpeg-turbo8-dev libjpeg-turbo8 \
apache2-mpm-prefork libapache2-mod-php5 php5-cli
```

#### CentOS / Redhat

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
