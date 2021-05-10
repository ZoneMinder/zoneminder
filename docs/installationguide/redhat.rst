Redhat
======

.. contents::

These instructions apply to all Redhat distros and their clones, including but not limited to: Fedora, RHEL, CentOS, Scientific Linux, and others. While the installation instructions are the same for each distro, the reason why one might use one distro over the other is different. A short description follows, which is intended to help you chose what distro best fits your needs.

Background: RHEL, CentOS, and Clones
------------------------------------

These distributions are classified as enterprise operating systems and have a long operating lifetime of many years. By design, they will not have the latest and greatest versions of any package. Instead, stable packages are the emphasis.

Replacing any core package in these distributions with a newer package from a third party is expressly verboten. The ZoneMinder development team will not do this, and neither should you. If you have the perception that you've got to have a newer version of php, mysql, gnome, apache, etc. then, rather than upgrade these packages, you should instead consider using a different distribution such as Fedora.

The ZoneMinder team will not provide support for systems which have had any core package replaced with a package from a third party.

Background: Fedora
------------------------------------

One can think of Fedora as RHEL or CentOS Beta. This is, in fact, what it is. Fedora is primarily geared towards development and testing of newer, sometimes bleeding edge, packages. The ZoneMinder team uses this distro to determine the interoperability of ZoneMinder with the latest and greatest versions of packages like mysql, apache, systemd, and others. If a problem is detected, it will be addressed long before it makes it way into RHEL.

Fedora has a short life-cycle of just 6 months. However, Fedora, and consequently ZoneMinder, is available on armv7 architecture. Rejoice, Raspberry Pi users!

If you desire newer packages than what is available in RHEL or CentOS, you should consider using Fedora.

How To Avoid Known Installation Problems
----------------------------------------

The following notes are based on real problems which have occurred by those who came before you:

- Zmrepo assumes you have installed the underlying distribution **using the official installation media for that distro**. Third party "Spins" may not work correctly.

- ZoneMinder is intended to be installed in an environment dedicated to ZoneMinder. While ZoneMinder will play well with many applications, some invariably will not. Asterisk is one such example.

- Be advised that you need to start with a clean system before installing ZoneMinder.

- If you have previously installed ZoneMinder from-source, then your system is **NOT** clean. You must manually search for and delete all ZoneMinder related files first (look under /usr/local). Issuing a "make uninstall" helps, but it will not do this for you correctly. You **WILL** have problems if you ignore this step.

- Unlike Debian/Ubuntu distros, it is not necessary, and not recommended, to install a LAMP stack ahead of time.

- Disable any other third party repos and uninstall any of ZoneMinder's third party dependencies, which might already be on the system, especially ffmpeg and vlc. Attempting to install dependencies yourself often causes problems.

- Each ZoneMinder rpm includes a README file under /usr/share/doc. You must follow all the steps in this README file, precisely, each and every time ZoneMinder is installed or upgraded. **Failure to do so is guaranteed to result in a non-functional system.**

How to Install ZoneMinder
-------------------------

ZoneMinder releases are now being hosted at RPM Fusion. New users should navigate the `RPM Fusion site <https://rpmfusion.org>`__ then follow the instructions to enable that repo. RHEL/CentOS users must also navaigate to the `EPEL Site <https://fedoraproject.org/wiki/EPEL>`_ and enable that repo as well. Once enabled, install ZoneMinder from the commandline:

::

    sudo dnf install zoneminder

Note that RHEL/CentOS 7 users should use yum instead of dnf.

Once ZoneMinder has been installed, it is critically important that you read the README file under /usr/share/doc/zoneminder. ZoneMinder will not run without completing the steps outlined in the README.

How to Install Nightly Development Builds
-----------------------------------------

ZoneMinder development packages, which represent the most recent build from our master branch, are available from `zmrepo <https://www.zoneminder.com>`_. 

The feedback we get from those who use these development packages is extremely helpful. However, please understand these packages are intended for testing the latest master branch only. They are not intended to be used on any production system. There will be new bugs, and new features may not be documented. This is bleeding edge, and there might be breakage. Please keep that in mind when using this repo. We know from our user forum that this can't be stated enough. 

How to Change from Zmrepo to RPM Fusion
---------------------------------------

As mentioned above, the place to get the latest ZoneMinder release is now `RPM Fusion <https://rpmfusion.org>`__. If you are currently using ZoneMinder release packages from Zmrepo, then the following steps will change you over to RPM Fusion:

- Navigate to the `RPM Fusion site <https://rpmfusion.org>`__ and enable RPM Fusion on your system
- Now issue the following from the command line:

::

    sudo dnf remove zmrepo
    sudo dnf update

Note that RHEL/CentOS 7 users should use yum instead of dnf.

How to Build Your Own ZoneMinder Package
------------------------------------------

If you are looking to do development or the available packages just don't suit you, then you can follow these steps to build your own ZoneMinder RPM.

Background
**********
The following method documents how to build ZoneMinder into an RPM package, for Fedora, Redhat, CentOS, and other compatible clones. This is exactly how the RPMS in zmrepo are built.

The method documented below was chosen because:

- All of ZoneMinder's dependencies are downloaded and installed automatically

- Cross platform capable. The build host does not have to be the same distro or release version as the target.

- Once your build environment is set up, few steps are required to run the build again in the future.

- Troubleshooting becomes easier if we are all building ZoneMinder the same way.

***IMPORTANT***
Certain commands in these instructions require root privileges while other commands do not. Pay close attention to this. If the instructions below state to issue a command without a “sudo” prefix, then you should *not* be root while issuing the command. Getting this incorrect will result in a failed build, or worse a broken system.

Set Up Your Environment
***********************
Before you begin, set up an rpmbuild environment by following `this guide <https://wiki.centos.org/HowTos/SetupRpmBuildEnvironment>`_ by the CentOS developers.

In addition, make sure RPM Fusion is enabled as described in the previous section `How to Install ZoneMinder`_.  

With RPM Fusion enabled, issue the following command:

::

    sudo yum install mock-rpmfusion-free mock


Add your user account to the group mock:

::

    sudo gpasswd -a {your account name} mock


Your build environment is now set up.  

Build from SRPM
***************
To continue, you need a ZoneMinder SRPM. If you wish to rebuild a ZoneMinder release, then browse the `RPM Fusion site <https://rpmfusion.org/>`__. If instead you wish to rebuild the latest source rpm from our master branch then browse the `Zmrepo site <http://zmrepo.zoneminder.com/>`_.

For this example, I'll use one of the source rpms from zmrepo:   

::

    wget -P ~/rpmbuild/SRPMS http://zmrepo.zoneminder.com/el/7/SRPMS/zoneminder-1.31.1-1.el7.centos.src.rpm


Now comes the fun part. To build ZoneMinder, issue the following command:

::

    mock -r epel-7-x86_64-rpmfusion_free ~/rpmbuild/SRPMS/zoneminder-1.31.1-1.el7.centos.src.rpm


Want to build ZoneMinder for Fedora, instead of CentOS, from the same host?  Once you download the Fedora SRPM, issue the following:

::

    mock -r fedora-26-x86_64-rpmfusion_free ~/rpmbuild/SRPMS/zoneminder-1.31.1-1.el7.centos.src.rpm

Notice that the mock tool requires the following parameters:

::

    mock -r MOCKCONFIG ZONEMINDER_SRPM

The list of available Mock config files are available here:

::

    ls /etc/mock/*rpmfusion_free.cfg


You choose the config file based on the desired distro (e.g. el7, f29, f30) and basearch (e.g. x86, x86_64, arhmhfp). Notice that, when specifying the Mock config as a commandline parameter, you should leave off the ".cfg" filename extension.

Installation
************
Once the build completes, you will be presented with a message stating where the newly built rpms can be found. It will look similar to this:

::

    INFO: Results and/or logs in: /var/lib/mock/fedora-26-x86_64/result

Copy the newly built ZoneMinder RPMs to the desired system, enable RPM Fusion as described in `How to Install ZoneMinder`_, and then install the rpm by issuing the appropriate yum/dnf install command. Finish the installation by following the zoneminder setup instructions in the distro specific readme file, named README.{distroname}, which will be installed into the /usr/share/doc/zoneminder* folder. 

Finally, you may want to consider editing the rpmfusion repo file under /etc/yum.repos.d and placing an “exclude=zoneminder*” line into the config file.  This will prevent your system from overwriting your manually built RPM with the ZoneMinder RPM found in the repo.

How to Create Your Own Source RPM
*********************************
In the previous section we described how to rebuild an existing ZoneMinder SRPM. The instructions which follow show how to build the ZoneMinder git source tree into a source rpm, which can be used in the previous section to build an rpm.

Make sure git and rpmdevtools are installed:

::

    sudo yum install git rpmdevtools


Now clone the ZoneMinder git repository from your home folder:

::

    cd
    git clone https://github.com/ZoneMinder/zoneminder
    cd zoneminder

This will create a sub-folder called ZoneMinder, which will contain the latest development source code.

If you have previsouly cloned the ZoneMinder git repo and wish to update it to the most recent, then issue these commands instead:

::

    cd ~/zoneminder
    git pull origin master
    
Get the crud submodule tarball:

::

    spectool -f -g -R -s 1 ~/zoneminder/distros/redhat/zoneminder.spec

At this point, you can make changes to the source code. Depending on what you want to do with those changes, you generally want to create a new branch first:

::

    cd ~/zoneminder
    git checkout -b mynewbranch

Again, depending on what you want to do with those changes, you may want to commit your changes:

::

    cd ~/zoneminder
    git add .
    git commit

Once you have made your changes, it is time to turn your work into a new tarball, but first we need to look in the rpm specfile:

::

    less ~/zoneminder/distros/redhat/zoneminder.spec
    
Scroll down until you see the Version field. Note the value, which will be in the format x.xx.x. Now create the tarball with the following command:

::

    cd ~/zoneminder
    git archive --prefix=zoneminder-1.33.4/ -o ~/rpmbuild/SOURCES/zoneminder-1.33.4.tar.gz HEAD

Replace "1.33.4" with the Version shown in the rpm specfile.

From the root of the local ZoneMinder git repo, execute the following:

::

    cd ~/zoneminder
    rpmbuild -bs --nodeps distros/redhat/zoneminder.spec

This step will create a source rpm and it will tell you where it was saved. For example:

::

    Wrote: /home/abauer/rpmbuild/SRPMS/zoneminder-1.33.4-1.fc26.src.rpm
    
Now follow the previous instructions `Build from SRPM`_ which describe how to build that source rpm into an rpm.
