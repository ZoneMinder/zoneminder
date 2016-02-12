ZoneMinder
==========

[![Build Status](https://travis-ci.org/ZoneMinder/ZoneMinder.png)](https://travis-ci.org/ZoneMinder/ZoneMinder) [![Bountysource](https://api.bountysource.com/badge/team?team_id=204&style=bounties_received)](https://www.bountysource.com/teams/zoneminder/issues?utm_source=ZoneMinder&utm_medium=shield&utm_campaign=bounties_received)

All documentation for ZoneMinder is now online at http://www.zoneminder.com/wiki/index.php/Documentation

## Overview

ZoneMinder is an integrated set of applications which provide a complete surveillance solution allowing capture, analysis, recording and monitoring of any CCTV or security cameras attached to a Linux based machine. It is designed to run on distributions which support the Video For Linux (V4L) interface and has been tested with video cameras attached to BTTV cards, various USB cameras and also supports most IP network cameras. 

## Contacting the Development Team
Before creating an issue in our github forum, please read our posting rules:
https://github.com/ZoneMinder/ZoneMinder/wiki/Github-Posting-Rules

## Installation Methods

### Building from Source is Discouraged

Historically, installing ZoneMinder onto your system required building from source code by issuing the traditional configure, make, make install commands.  To get ZoneMinder to build, all of its dependencies had to be determined and installed beforehand. Init and logrotate scripts had to be manually copied into place following the build.  Optional packages such as jscalendar and Cambozola had to be manually installed. Uninstalls could leave stale files around, which could cause problems during an upgrade.  Speaking of upgrades, when it comes time to upgrade all these manual steps must be repeated again.

Better methods exist today that do much of this for you. The current development team, along with other volunteers, have taken great strides in providing the resources necessary to avoid building from source.  

### Install from a Package Repository

This is the recommended method to install ZoneMinder onto your system. ZoneMinder packages are maintained for the following distros:

- Ubuntu via [Iconnor's PPA](https://launchpad.net/~iconnor/+archive/ubuntu/zoneminder)
- Debian from their [default repository](https://packages.debian.org/search?searchon=names&keywords=zoneminder) 
- RHEL/CentOS and clones via [zmrepo](http://zmrepo.zoneminder.com/)
- Fedora via [zmrepo](http://zmrepo.zoneminder.com/)
- OpenSuse via [third party repository](http://www.zoneminder.com/wiki/index.php/Installing_using_ZoneMinder_RPMs_for_SuSE)
- Maegia from their default repository

If a repository that hosts ZoneMinder packages is not available for your distro, then you are encouraged to build your own package, rather than build from source.  While each distro is different in ways that set it apart from all the others, they are often similar enough to allow you to adapt another distro's package building instructions to your own.

### Building a ZoneMinder Package

Building ZoneMinder into a package is not any harder than building from source.  As a matter of fact, if you have successfully built ZoneMinder from source in the past, then you may find these steps to be easier. 

When building a package, it is best to do this work in a separate environment, dedicated to development purposes. This could be as simple as creating a virtual machine, using Docker, or using mock.  All it takes is one “Oops” to regret doing this work on your production server.

Lastly, if you desire to build a development snapshot from the master branch, it is recommended you first build your package using an official release of ZoneMinder. This will help identify whether any problems you may encounter are caused by the build process or is a new issue in the master branch.

What follows are instructions for various distros to build ZoneMinder into a package.

### Package Maintainers
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

#### Fedora / CentOS / RHEL

##### Background
The following method documents how to build ZoneMinder into an RPM package, compatible with Fedora, Redhat, CentOS, and other compatible clones. This is exactly how the RPMS in zmrepo are built.

The method documented below was chosen because:
- All of ZoneMinder's dependencies are downloaded and installed automatically
- Cross platform capable. The build host does not have to be the same distro or release version as the target.
- Once your build environment is set up, few steps are required to run the build again in the future.
- Troubleshooting becomes easier if we are all building ZoneMinder the same way.

The build instructions below make use of a custom script called "buildzm.sh". Advanced users are encouraged to view the contents of this script. Notice that the script doesn't really do a whole lot. The goal of the script is to simply make the process a little easier for the first time user. Once you become familar with the build process, you can issue the mock commands found in the buildzm.sh script yourself if you so desire.

***IMPORTANT***
Certain commands in these instructions require root privileges while other commands do not. Pay close attention to this. If the instructions below state to issue a command without a “sudo” prefix, then you should *not* be root while issuing the command. Getting this incorrect will result in a failed build.

##### Set Up Your Environment
Before you begin, set up an rpmbuild environment by following [this guide](http://wiki.centos.org/HowTos/SetupRpmBuildEnvironment) by the CentOS developers.

Next, navigate to [Zmrepo](http://zmrepo.zoneminder.com/), and follow the instructions to enable zmrepo on your system.  

With zmrepo enabled, issue the following command:
````bash
sudo yum install zmrepo-mock-configs mock
```

Add your user account to the group mock:
```bash
sudo gpasswd -a {your account name} mock
```

Your build environment is now set up.  

##### Build from SRPM
To continue, you need a ZoneMinder SRPM.  For starters, let's use one of the SRPMS from zmrepo.  Go browse the [Zmrepo](http://zmrepo.zoneminder.com/) site and choose an appropriate SRPM and place it into the ~/rpmbuild/SRPMS folder.  

For CentOS 7, I have chosen the following SRPM:
```bash
wget -P ~/rpmbuild/SRPMS http://zmrepo.zoneminder.com/el/7/SRPMS/zoneminder-1.28.1-2.el7.centos.src.rpm
```

Now comes the fun part. To build ZoneMinder, issue the following command:
```bash
buildzm.sh zmrepo-el7-x86_64 ~/rpmbuild/SRPMS/zoneminder-1.28.1-2.el7.centos.src.rpm
```

Want to build ZoneMinder for Fedora, instead of CentOS, from the same host?  Once you download the Fedora SRPM, issue the following:
```bash
buildzm.sh zmrepo-f21-x86_64 ~/rpmbuild/SRPMS/zoneminder-1.28.1-1.fc21.src.rpm
```
Notice that the buildzm.sh tool requires the following parameters:
```bash
buildzm.sh MOCKCONFIG ZONEMINDER_SRPM
```
The list of available Mock config files are available here:
```bash
ls /etc/mock/zmrepo*.cfg
```

You choose the config file based on the desired distro (e.g. el6, el7, f20, f21) and basearch (e.g. x86, x86_64, arhmhfp). Notice that, when specifying the Mock config as a commandline parameter, you should leave off the ".cfg" filename extension.

##### Installation
Once the build completes, you will be presented with a folder containing the RPM's that were built.  Copy the newly built ZoneMinder RPM to the desired system, enable zmrepo per the instruction on the [Zmrepo](http://zmrepo.zoneminder.com/) website, and then install the rpm by issuing the appropriate yum install command. Finish the installation by following the zoneminder setup instructions in the distro specific readme file, named README.{distroname}, which will be installed into the /usr/share/doc/zoneminder* folder. 

Finally, you may want to consider editing the zmrepo repo file under /etc/yum.repos.d and placing an “exclude=zoneminder*” line into the config file.  This will prevent your system from overwriting your manually built RPM with the ZoneMinder RPM found in the repo.

##### How to Modify the Source Prior to Build
** UNFINISHED **

Before attempting this part of the instructions, make sure and follow the previous instructions for building one of the unmodified SRPMS from zmrepo. Knowing this part works will assist in troubleshooting should something go wrong.

These instructions may vary depending on what exactly you want to do.  The following example assumes you want to build a development snapshot from the master branch.

From the previous instructions, we downloaded a CentOS 7 ZoneMinder SRPM and placed it into ~/rpmbuild/SRPMS. For this example, install it onto your system:
```bash
rpm -Uvh ~/rpmbuild/SRPMS/zoneminder-1.28.1-2.el7.centos.src.rpm
```

IMPORTANT: This operation must be done with your normal user account. Do *not* perform this command as root.

Make sure you have git installed:
```bash
sudo yum install git
```

Now clone the ZoneMinder git repository:
```bash
git clone https://github.com/ZoneMinder/ZoneMinder
```
This will create a sub-folder called ZoneMinder, which will contain the latest development.

We want to turn this into a tarball, but first we need to figure out what to name it. Look here:
```bash
ls ~/rpmbuild/SOURCES
```
The tarball from the previsouly installed SRPM should be there. This is the name we will use.  For this example, the name is ZoneMinder-1.28.1.tar.gz.  From one folder above the local ZoneMinder git repository, execute the following:
```bash
mv ZoneMinder ZoneMinder-1.28.1
tar -cvzf ~/rpmbuild/SOURCES/ZoneMinder-1.28.1.tar.gz ZoneMinder-1.28.1/*
```
The trailing "/*" leaves off the hidden dot "." file and folders from the git repo, which is what we want.
Note that we are overwriting the original tarball. If you wish to keep the original tarball then create a copy prior to creating the new tarball.

Now build a new src.rpm:
```bash
rpmbuild -bs --nodeps ~/rpmbuild/SPECS/zoneminder.el7.spec
```
This step will overwrite the SRPM you originally downloaded, so you may want to back it up prior to completing this step. Note that the name of the specfile will vary slightly depending on what distro you are building for.

You should now have a a new SRPM under ~/rpmbuild/SRPMS. In our example, the SRPM is called zoneminder-1.28.1-2.el7.centos.src.rpm. Now follow the previous instructions that describe how to use the buildzm script, using ~/rpmbuild/SRPMS/zoneminder-1.28.1-2.el7.centos.src.rpm as the path to your SRPM.

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
