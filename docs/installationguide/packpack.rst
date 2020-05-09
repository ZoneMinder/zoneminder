All Distros - A Docker Way to Build ZoneMinder
===============================================

.. note:: If you are looking for an easy way to run ZoneMinder and not interested in building your own docker image, please refer to :doc:`easydocker`.

.. contents::

These instructions represent an alternative way to build ZoneMinder for any supported distro.

Advantages:

- Fewer steps and therefore much simpler
- Target distro agnostic - the steps are the same regardless of the target distro
- Host distro agnostic - the steps described here should work on any host Linux distro capable of running Bash and Docker

Background
------------------------------------

These instructions leverage the power of the automated build system recently implemented in the ZoneMinder project. Behind the scenes, a project called `packpack <https://github.com/packpack/packpack>`_ is utilized, to build ZoneMinder inside a Docker container.

Procedure
------------------------------------

**Step 1:** Verify the target distro.

- Open the project's `.travis.yml file <https://github.com/ZoneMinder/ZoneMinder/blob/master/.travis.yml#L27>`_ and verify the distro you want to build ZoneMinder for appears in the build matrix. The distros shown in the matrix are those known to build on ZoneMinder. If the distro you desire is in the list then continue to step two. 

- If the desired distro is not in the first list, then open the `packpack project README <https://github.com/packpack/packpack/blob/master/README.md>`_ and check if the desired distro is theoretically supported. If it is, then continue to step 2 with the understanding that you are heading out into uncharted territory. There could be problems. 

- If the desired distro does not appear in either list, then unfortuantely you cannot use the procedure described here.

- If the desired distro architecture is arm, refer to `Appendix A - Enable Qemu On the Host`_ to enable qemu emulation on your amd64 host machine.

**Step 2:** Install Docker.

You need to have a working installation of Docker so head over to the `Docker site <https://docs.docker.com/engine/installation/>`_ and get it working. Before continuing to the next step, verify you can run the Docker "Hello World" container as a normal user. To run a Docker container as a normal user, issue the following:

::

	sudo gpasswd -a <username> docker
	newgrp docker

Where <username> is, you guessed it, the user name you log in with.

**Step 3:** Git clone the ZoneMinder project.

Clone the ZoneMinder project if you have not done so already.

::

	git clone https://github.com/ZoneMinder/ZoneMinder
        cd ZoneMinder

Alternatively, if you have already cloned the repo and wish to update it, do the following.

::

	cd ZoneMinder
        git checkout master
        git pull origin master

**Step 4:** Checkout the revision of ZoneMinder you wish to build.

A freshly cloned ZoneMinder git repo already points to the most recent commit in the master branch. If you want the latest development code then continue to the next step. If instead, you want to build a stable release then perform the following step.

::

	git checkout <releasename>

Where <releasename> is one of the official ZoneMinder releases shown on the `releases page <https://github.com/ZoneMinder/ZoneMinder/releases>`_, such as 1.30.4.

**Step 5:** Build ZoneMinder

To start the build, simply execute the following command from the root folder of the local git repo:

::

	OS=<distroname> DIST=<distrorel> utils/packpack/startpackpack.sh

Where <distroname> is the name of the distro you wish to build on, such as fedora, and <distrorev> is release name or number of the distro you wish to build on. Redhat distros expect a number for <distrorev> while Debian and Ubuntu distros expect a name. For example:

::

	OS=fedora DIST=25 utils/packpack/startpackpack.sh

::

	OS=ubuntu DIST=xenial utils/packpack/startpackpack.sh

Once you enter the appropriate command, go get a coffee while a ZoneMinder package is built. When the build finished, you can find the resulting packages under a subfolder called "build".

Note that this will build packages with x86_64 architecture. This build method can also build on some distros (debian & ubuntu only at the moment) using i386 architecture. You can do that by adding "ARCH=i386" parameter.

::

	OS=ubuntu DIST=xenial ARCH=i386 utils/packpack/startpackpack.sh

For advanced users who really want to go out into uncharted waters, it is theoretically possible to build arm packages as well, as long as the host architecture is compatible.

::

	OS=ubuntu DIST=xenial ARCH=armhfp utils/packpack/startpackpack.sh

Building arm packages in this manner has not been tested by us, however.

Appendix A - Enable Qemu On the Host
------------------------------------

If you intend to build ZoneMinder packages for arm on an amd64 host, then Debian users can following these steps to enable transparent Qemu emulation:

::

	sudo apt-get install binfmt-support qemu qemu-user-static

Verify arm emulation is enabled by issuing:

::

	sudo update-binfmts --enable qemu-arm

You may get a message stating emulation for this processor is already enabled. 

More testing needs to be done for Redhat distros but it appears Fedora users can just run:

::

	sudo systemctl start systemd-binfmt

.. todo:: Verify the details behind enabling qemu emulation on redhat distros. Pull requests are welcome.
