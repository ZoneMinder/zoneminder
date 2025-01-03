ZoneMinder
==========

[![Join Slack](https://github.com/ozonesecurity/ozonebase/blob/master/img/slacksm.png?raw=true)](https://join.slack.com/t/zoneminder-chat/shared_invite/enQtNTU0NDkxMDM5NDQwLTdhZmQ5Y2M2NWQyN2JkYTBiN2ZkMzIzZGQ0MDliMTRmM2FjZWRlYzUwYTQ2MjMwMTVjMzQ1NjYxOTdmMjE2MTE "Join Slack")
<a href="https://discord.gg/tHYyP9k66q" title="Join Discord Server"><img src="https://assets-global.website-files.com/6257adef93867e50d84d30e2/636e0a6a49cf127bf92de1e2_icon_clyde_blurple_RGB.png" alt="drawing" width="50"/></a>


All documentation for ZoneMinder is now online at https://zoneminder.readthedocs.org

## Overview

ZoneMinder is an integrated set of applications which provide a complete surveillance solution allowing capture, analysis, recording and monitoring of any CCTV or security cameras attached to a Linux based machine. It is designed to run on distributions which support the Video For Linux (V4L) interface and has been tested with video cameras attached to BTTV cards, various USB cameras and also supports most IP network cameras. 

## Contacting the Development Team
Before creating an issue in our github forum, please read our posting rules:
https://github.com/ZoneMinder/ZoneMinder/wiki/Github-Posting-Rules

## Our Dockerfile has moved
Please file issues against the ZoneMinder Dockerfile here: 
https://github.com/ZoneMinder/zmdockerfiles

## Installation Methods

### Install from a Package Repository

This is the recommended method to install ZoneMinder onto your system. ZoneMinder packages are maintained for the following distros:

- Ubuntu via [Isaac Connor's PPA](https://launchpad.net/~iconnor)
- Debian from their [default repository](https://packages.debian.org/search?searchon=names&keywords=zoneminder) 
- RHEL/CentOS and clones via [RPM Fusion](http://rpmfusion.org)
- Fedora via [RPM Fusion](http://rpmfusion.org)
- OpenSuse via [third party repository](https://wiki.zoneminder.com/Installing_using_ZoneMinder_RPMs_for_SuSE)
- Mageia from their default repository
- Arch via the [AUR](https://aur.archlinux.org/packages/zoneminder/)
- Gentoo via [Portage Overlays](http://gpo.zugaina.org/www-misc/zoneminder)

If a repository that hosts ZoneMinder packages is not available for your distro, then you are encouraged to build your own package, rather than build from source.  While each distro is different in ways that set it apart from all the others, they are often similar enough to allow you to adapt another distro's package building instructions to your own.

### Building from Source is Discouraged

Historically, installing ZoneMinder onto your system required building from source code by issuing the traditional configure, make, make install commands.  To get ZoneMinder to build, all of its dependencies had to be determined and installed beforehand. Init and logrotate scripts had to be manually copied into place following the build.  Optional packages such as jscalendar and Cambozola had to be manually installed. Uninstalls could leave stale files around, which could cause problems during an upgrade.  Speaking of upgrades, when it comes time to upgrade all these manual steps must be repeated again.

Better methods exist today that do much of this for you. The current development team, along with other volunteers, have taken great strides in providing the resources necessary to avoid building from source.  


### Building a ZoneMinder Package ###

Building ZoneMinder into a package is not any harder than building from source.  As a matter of fact, if you have successfully built ZoneMinder from source in the past, then you may find these steps to be easier. 

When building a package, it is best to do this work in a separate environment, dedicated to development purposes. This could be as simple as creating a virtual machine, using Docker, or using mock.  All it takes is one “Oops” to regret doing this work on your production server.

Lastly, if you desire to build a development snapshot from the master branch, it is recommended you first build your package using an official release of ZoneMinder. This will help identify whether any problems you may encounter are caused by the build process or is a new issue in the master branch.

Please visit our [ReadtheDocs site](https://zoneminder.readthedocs.org/en/stable/installationguide/index.html) for distro specific instructions.

### Package Maintainers
Many of the ZoneMinder configuration variable default values are not configurable at build time through autotools or cmake.  A new tool called *zmeditconfigdata.sh* has been added to allow package maintainers to manipulate any variable stored in ConfigData.pm without patching the source. 

For example, let's say I have created a new ZoneMinder package that contains the cambozola javascript file.  However, by default cambozola support is turned off.  To fix that, add this to the packaging script:
```bash
./utils/zmeditconfigdata.sh ZM_OPT_CAMBOZOLA yes
```

Note that zmeditconfigdata.sh is intended to be called, from the root build folder, prior to running cmake or configure.

#### Docker

Docker is a system to run applications inside isolated containers. ZoneMinder, and the ZM webserver, will run using the 
Dockerfile contained in this repository. However, there is still work needed to ensure that the main ZM features work 
properly and are documented. 

## Contribution Model and Development

* Source hosted at [GitHub](https://github.com/ZoneMinder/ZoneMinder/)
* Report issues at [GitHub Issues](https://github.com/ZoneMinder/ZoneMinder/issues)
* Questions/feature requests in [Slack](https://zoneminder-chat.slack.com/) or [forums](https://forums.zoneminder.com)

Pull requests are very welcome!  If you would like to contribute, please follow
the following steps.  While step 3 is optional, it is preferred.

1. Fork the repo
2. Open an issue at our [GitHub Issues Tracker](https://github.com/ZoneMinder/ZoneMinder/issues).
   Follow the issue template to describe the bug or security issue you found. Please note feature
   requests or questions should be posted in our user forum or Slack channel.
3. Create your feature branch (`git checkout -b 456-my-new-feature`)
4. Commit your changes (`git commit -am 'Added some feature'`)
   It is preferred that you 'commit early and often' instead of bunching all
   changes into a single commit.
5. Push your branch to your fork on github (`git push origin 456-my-new-feature`)
6. Create new Pull Request
7. The team will then review, discuss and hopefully merge your changes.

[![Analytics](https://ga-beacon.appspot.com/UA-15147273-6/ZoneMinder/README.md)](https://github.com/igrigorik/ga-beacon)
