Redhat
======

.. contents::

These instructions apply to all Redhat distros and their clones, including but not limited to: Fedora, RHEL, CentOS, Scientific Linux, and others. While the installation instructions are the same for each distro, the reason why one might use one distro over the other is different. A short description follows, which is intended to help you chose what distro best fits your needs.

Background: RHEL, CentOS, and Clones
------------------------------------

These distributions are classified as enterprise operating systems and have a long operating lifetime of many years. By design, they will not have the latest and greatest versions of any package. Instead, stable packages are the emphasis.

Replacing any core package in these distributions with a newer package from a third party is expressly verboten. The ZoneMinder development team will not do this, and neither should you. If you have the perception that you've got to have a newer version of mysql, gnome, apache, etc. then, rather than upgrade these packages, you should instead consider using a different distribution such as Fedora.

The ZoneMinder team will not provide support for systems which have had any core package replaced with a package from a third party.

Background: Fedora
------------------------------------

One can think of Fedora as RHEL or CentOS Beta. This is, in fact, what it is. Fedora is primarily geared towards development and testing of newer, sometimes bleeding edge, packages. The ZoneMinder team uses this distro to determine the interoperability of ZoneMinder with the latest and greatest versions of packages like mysql, apache, systemd, and others. If a problem is detected, it will be addressed long before it makes it way into RHEL.

Fedora has a short life-cycle of just 6 months. However, Fedora, and consequently ZoneMinder, is available on armv7 architecture. Rejoice, Raspberry Pi users!

If you desire newer packages than what is available in RHEL or CentOS, you should consider using Fedora.

Zmrepo – A ZoneMinder RPM Repository
------------------------------------

Zmrepo is a turn key solution. It will install all of ZoneMinder's dependencies for you. This is the easiest and the recommended way to install ZoneMinder on any system running a Redhat based distribution. 

Zmrepo supports the two most recent, major releases of each Redhat based distro.

The following notes are based on real problems which have occurred:

- Zmrepo assumes you have installed the underlying distribution **using the official installation media for that distro**. Third party "Spins" are not supported and may not work correctly.

- ZoneMinder is intended to be installed in an environment dedicated to ZoneMinder. While ZoneMinder will play well with many applications, some invariably will not. Asterisk is one such example.

- Be advised that you need to start with a clean system before using zmrepo.

- If you have previously installed ZoneMinder from-source, then your system is **NOT** clean. You must manually search for and delete all ZoneMinder related files before using zmrepo (look under /usr/local). Make uninstall helps, but it will not do this for you correctly. You **WILL** have problems if you ignore this step.

- It is not necessary, and not recommended, to install a LAMP stack ahead of time.

- Disable other third party repos and uninstall any of ZoneMinder's third party dependencies, which might already be on the system, especially ffmpeg and vlc. Attempting to install dependencies yourself often causes problems.

- Each ZoneMinder rpm includes a README file under /usr/share/doc. You must follow the all steps in this README file, precisely, each and every time ZoneMinder is installed or upgraded. **Failure to do so is guaranteed to result in a non-functional system.**

To begin the installation of ZoneMinder on your Redhat based distro, please navigate to: http://zmrepo.zoneminder.com

How to Build a (Custom) ZoneMinder Package
------------------------------------------

If you are looking to do development or the packages in zmrepo just don't suit you, then you should follow these steps to learn how to build your own ZoneMinder RPM.

Background
**********
The following method documents how to build ZoneMinder into an RPM package, compatible with Fedora, Redhat, CentOS, and other compatible clones. This is exactly how the RPMS in zmrepo are built.

The method documented below was chosen because:

- All of ZoneMinder's dependencies are downloaded and installed automatically

- Cross platform capable. The build host does not have to be the same distro or release version as the target.

- Once your build environment is set up, few steps are required to run the build again in the future.

- Troubleshooting becomes easier if we are all building ZoneMinder the same way.

The build instructions below make use of a custom script called "buildzm.sh". Advanced users are encouraged to view the contents of this script. Notice that the script doesn't really do a whole lot. The goal of the script is to simply make the process a little easier for the first time user. Once you become familar with the build process, you can issue the mock commands found in the buildzm.sh script yourself if you so desire.

***IMPORTANT***
Certain commands in these instructions require root privileges while other commands do not. Pay close attention to this. If the instructions below state to issue a command without a “sudo” prefix, then you should *not* be root while issuing the command. Getting this incorrect will result in a failed build.

Set Up Your Environment
***********************
Before you begin, set up an rpmbuild environment by following `this guide <http://wiki.centos.org/HowTos/SetupRpmBuildEnvironment>`_ by the CentOS developers.

Next, navigate to `Zmrepo <http://zmrepo.zoneminder.com/>`_, and follow the instructions to enable zmrepo on your system.  

With zmrepo enabled, issue the following command:

::

    sudo yum install zmrepo-mock-configs mock


Add your user account to the group mock:

::

    sudo gpasswd -a {your account name} mock


Your build environment is now set up.  

Build from SRPM
***************
To continue, you need a ZoneMinder SRPM.  For starters, let's use one of the SRPMS from zmrepo.  Go browse the `Zmrepo <http://zmrepo.zoneminder.com/>`_ site and choose an appropriate SRPM and place it into the ~/rpmbuild/SRPMS folder.  

For CentOS 7, I have chosen the following SRPM:

::

    wget -P ~/rpmbuild/SRPMS http://zmrepo.zoneminder.com/el/7/SRPMS/zoneminder-1.28.1-2.el7.centos.src.rpm


Now comes the fun part. To build ZoneMinder, issue the following command:

::

    buildzm.sh zmrepo-el7-x86_64 ~/rpmbuild/SRPMS/zoneminder-1.28.1-2.el7.centos.src.rpm


Want to build ZoneMinder for Fedora, instead of CentOS, from the same host?  Once you download the Fedora SRPM, issue the following:

::

    buildzm.sh zmrepo-f21-x86_64 ~/rpmbuild/SRPMS/zoneminder-1.28.1-1.fc21.src.rpm

Notice that the buildzm.sh tool requires the following parameters:

::

    buildzm.sh MOCKCONFIG ZONEMINDER_SRPM

The list of available Mock config files are available here:

::

    ls /etc/mock/zmrepo*.cfg


You choose the config file based on the desired distro (e.g. el6, el7, f20, f21) and basearch (e.g. x86, x86_64, arhmhfp). Notice that, when specifying the Mock config as a commandline parameter, you should leave off the ".cfg" filename extension.

Installation
************
Once the build completes, you will be presented with a folder containing the RPM's that were built.  Copy the newly built ZoneMinder RPM to the desired system, enable zmrepo per the instruction on the `Zmrepo <http://zmrepo.zoneminder.com/>`_
website, and then install the rpm by issuing the appropriate yum install command. Finish the installation by following the zoneminder setup instructions in the distro specific readme file, named README.{distroname}, which will be installed into the /usr/share/doc/zoneminder* folder. 

Finally, you may want to consider editing the zmrepo repo file under /etc/yum.repos.d and placing an “exclude=zoneminder*” line into the config file.  This will prevent your system from overwriting your manually built RPM with the ZoneMinder RPM found in the repo.

How to Modify the Source Prior to Build
***************************************
Before attempting this part of the instructions, make sure and follow the previous instructions for building one of the unmodified SRPMS from zmrepo. Knowing this part works will assist in troubleshooting should something go wrong.

These instructions may vary depending on what exactly you want to do.  The following example assumes you want to build a development snapshot from the master branch.

From the previous instructions, we downloaded a CentOS 7 ZoneMinder SRPM and placed it into ~/rpmbuild/SRPMS. For this example, install it onto your system:

::

    rpm -ivh ~/rpmbuild/SRPMS/zoneminder-1.28.1-2.el7.centos.src.rpm


IMPORTANT: This operation must be done with your normal user account. Do *not* perform this command as root.

Make sure you have git installed:

::

    sudo yum install git


Now clone the ZoneMinder git repository:

::

    cd
    git clone https://github.com/ZoneMinder/ZoneMinder
    cd ZoneMinder

This will create a sub-folder called ZoneMinder, which will contain the latest development.

We want to turn this into a tarball, but first we need to figure out what to name it. Look here:

::

    ls ~/rpmbuild/SOURCES

The tarball from the previsouly installed SRPM should be there. This is the name we will use.  For this example, the name is ZoneMinder-1.28.1.tar.gz. From the root folder of the local ZoneMinder git repository, execute the following:

::

    git archive --prefix=ZoneMinder-1.28.1/ -o ~/rpmbuild/SOURCES/zoneminder-1.28.1.tar.gz HEAD

Note that we are overwriting the original tarball. If you wish to keep the original tarball then create a copy prior to creating the new tarball.

From the root of the local ZoneMinder git repo, execute the following:

::

    rpmbuild -bs --nodeps distros/redhat/zoneminder.spec

Notice we used the rpm specfile that is part of the latest master branch you just downloaded, rather than the one that may be in your ~/rpmbbuild/SOURCES folder.

This step will overwrite the SRPM you originally downloaded, so you may want to back it up prior to completing this step. Note that the name of the specfile will vary slightly depending on the target distro.

You should now have a new SRPM under ~/rpmbuild/SRPMS. In our example, the SRPM is called zoneminder-1.28.1-2.el7.centos.src.rpm. Now follow the previous instructions that describe how to use the buildzm script, using ~/rpmbuild/SRPMS/zoneminder-1.28.1-2.el7.centos.src.rpm as the path to your SRPM.


