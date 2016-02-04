### Building a ZoneMinder Package

**Note:** these instructions are only required if you need to build your own package. This process is non-trivial. If you haven't already explored existing packages that work out of the box, please make sure you have read [the main README](README.md)

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
This will create a sub-folder called ZoneMinder, which will contain the latest developement.

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

