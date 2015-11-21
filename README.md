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

- Ubuntu via [Iconnor's PPA](https://launchpad.net/~iconnor/+archive/ubuntu/zoneminder) (Follow [these](https://wiki.zoneminder.com/Ubuntu) instructions)
- Debian from their [default repository](https://packages.debian.org/search?searchon=names&keywords=zoneminder) 
- RHEL/CentOS and clones via [zmrepo](http://zmrepo.zoneminder.com/)
- Fedora via [zmrepo](http://zmrepo.zoneminder.com/)
- OpenSuse via [third party repository](http://www.zoneminder.com/wiki/index.php/Installing_using_ZoneMinder_RPMs_for_SuSE)
- Maegia from their default repository

If a repository that hosts ZoneMinder packages is not available for your distro, then you are encouraged to build your own package, rather than build from source.  While each distro is different in ways that set it apart from all the others, they are often similar enough to allow you to adapt another distro's package building instructions to your own.


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
