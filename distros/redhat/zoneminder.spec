# Leaving this to allow one to build zoneminder-http subpackage using arbitrary user account
%global zmuid_final apache
%global zmgid_final apache

# Crud is configured as a git submodule
%global crud_version 3.1.0-zm

# CakePHP-Enum-Behavior is configured as a git submodule
%global ceb_version 1.0-zm

%global sslcert %{_sysconfdir}/pki/tls/certs/localhost.crt
%global sslkey %{_sysconfdir}/pki/tls/private/localhost.key

# This will tell zoneminder's cmake process we are building against a known distro
%global zmtargetdistro %{?rhel:el%{rhel}}%{!?rhel:fc%{fedora}}

# Fedora needs apcu backwards compatibility module
%if 0%{?fedora}
%global with_apcu_bc 1
%endif

# Newer php's keep json functions in a subpackage
%if 0%{?fedora} || 0%{?rhel} >= 8
%global with_php_json 1
%endif

# The default for everything but el7 these days
%global _hardened_build 1

Name: zoneminder
Version: 1.35.3
Release: 1%{?dist}
Summary: A camera monitoring and analysis tool
Group: System Environment/Daemons
# Mootools is inder the MIT license: http://mootools.net/
# CakePHP is under the MIT license: https://github.com/cakephp/cakephp
# Crud is under the MIT license: https://github.com/FriendsOfCake/crud
# CakePHP-Enum-Behavior is under the MIT license: https://github.com/asper/CakePHP-Enum-Behavior
License: GPLv2+ and LGPLv2+ and MIT
URL: http://www.zoneminder.com/

Source0: https://github.com/ZoneMinder/ZoneMinder/archive/%{version}.tar.gz#/zoneminder-%{version}.tar.gz
Source1: https://github.com/ZoneMinder/crud/archive/v%{crud_version}.tar.gz#/crud-%{crud_version}.tar.gz
Source2: https://github.com/ZoneMinder/CakePHP-Enum-Behavior/archive/%{ceb_version}.tar.gz#/cakephp-enum-behavior-%{ceb_version}.tar.gz

BuildRequires: systemd-devel
BuildRequires: mariadb-devel
BuildRequires: perl-podlators
BuildRequires: polkit-devel
BuildRequires: cmake3
BuildRequires: gnutls-devel
BuildRequires: bzip2-devel
BuildRequires: pcre-devel 
BuildRequires: libjpeg-turbo-devel
BuildRequires: findutils
BuildRequires: coreutils
BuildRequires: net-tools
BuildRequires: perl
BuildRequires: perl-generators
BuildRequires: perl(Archive::Tar)
BuildRequires: perl(Archive::Zip)
BuildRequires: perl(Date::Manip)
BuildRequires: perl(DBD::mysql)
BuildRequires: perl(ExtUtils::MakeMaker)
BuildRequires: perl(LWP::UserAgent)
BuildRequires: perl(MIME::Entity)
BuildRequires: perl(MIME::Lite)
BuildRequires: perl(PHP::Serialization)
BuildRequires: perl(Sys::Mmap)
BuildRequires: perl(Time::HiRes)
BuildRequires: perl(Net::SFTP::Foreign)
BuildRequires: perl(Expect)
BuildRequires: perl(Sys::Syslog)
BuildRequires: gcc 
BuildRequires: gcc-c++
BuildRequires: vlc-devel
BuildRequires: libcurl-devel
BuildRequires: libv4l-devel
BuildRequires: desktop-file-utils
BuildRequires: gzip
BuildRequires: zlib-devel

# ZoneMinder looks for and records the location of the ffmpeg binary during build
BuildRequires: ffmpeg
BuildRequires: ffmpeg-devel

# Required for mp4 container support
BuildRequires: libmp4v2-devel
BuildRequires: x264-devel

# Allow existing user base to seamlessly transition to sub-packages
Requires: %{name}-common%{?_isa} = %{version}-%{release}
Requires: %{name}-httpd%{?_isa} = %{version}-%{release}

%description
ZoneMinder is a set of applications which is intended to provide a complete
solution allowing you to capture, analyze, record and monitor any cameras you
have attached to a Linux based machine. It is designed to run on kernels which
support the Video For Linux (V4L) interface and has been tested with cameras
attached to BTTV cards, various USB cameras and IP network cameras. It is
designed to support as many cameras as you can attach to your computer without
too much degradation of performance.

This is a meta package for backwards compatibility with the existing
ZoneMinder user base.

%package common
Summary: Common files for ZoneMinder, not tied to a specific web server

Requires: php-mysqli
Requires: php-common
Requires: php-gd
%{?with_php_json:Requires: php-json}
Requires: php-pecl-apcu
%{?with_apcu_bc:Requires: php-pecl-apcu-bc}
Requires: cambozola
Requires: net-tools
Requires: psmisc
Requires: polkit
Requires: libjpeg-turbo
Requires: vlc-core
Requires: ffmpeg
Requires: perl(:MODULE_COMPAT_%(eval "`%{__perl} -V:version`"; echo $version))
Requires: perl(DBD::mysql)
Requires: perl(Archive::Tar)
Requires: perl(Archive::Zip)
Requires: perl(MIME::Entity)
Requires: perl(MIME::Lite)
Requires: perl(Net::SMTP)
Requires: perl(Net::FTP)
Requires: perl(LWP::Protocol::https)
Requires: ca-certificates
Requires: zip
%{?systemd_requires}

Requires(post): %{_bindir}/gpasswd
Requires(post): %{_bindir}/chown

%description common
ZoneMinder is a set of applications which is intended to provide a complete
solution allowing you to capture, analyze, record and monitor any cameras you
have attached to a Linux based machine. It is designed to run on kernels which
support the Video For Linux (V4L) interface and has been tested with cameras
attached to BTTV cards, various USB cameras and IP network cameras. It is
designed to support as many cameras as you can attach to your computer without
too much degradation of performance.

This is a meta-package that exists solely to allow the existing user base to
seamlessly transition to sub-packages.

%package httpd
Summary: ZoneMinder configuration for Apache web server
Requires: %{name}-common%{?_isa} = %{version}-%{release}
Requires: httpd
Requires: php

Conflicts: %{name}-nginx

%description httpd
ZoneMinder is a set of applications which is intended to provide a complete
solution allowing you to capture, analyze, record and monitor any cameras you
have attached to a Linux based machine. It is designed to run on kernels which
support the Video For Linux (V4L) interface and has been tested with cameras
attached to BTTV cards, various USB cameras and IP network cameras. It is
designed to support as many cameras as you can attach to your computer without
too much degradation of performance.

This sub-package contains configuration specific to Apache web server

%package nginx
Summary: ZoneMinder configuration for Nginx web server
Requires: %{name}-common%{?_isa} = %{version}-%{release}
Requires: nginx
Requires: php-fpm
Requires: fcgiwrap

Conflicts: %{name}-httpd

%description nginx
ZoneMinder is a set of applications which is intended to provide a complete
solution allowing you to capture, analyze, record and monitor any cameras you
have attached to a Linux based machine. It is designed to run on kernels which
support the Video For Linux (V4L) interface and has been tested with cameras
attached to BTTV cards, various USB cameras and IP network cameras. It is
designed to support as many cameras as you can attach to your computer without
too much degradation of performance.

This sub-package contains support for ZoneMinder with the Nginx web server

%prep
%autosetup -p 1 -a 1
rm -rf ./web/api/app/Plugin/Crud
mv -f crud-%{crud_version} ./web/api/app/Plugin/Crud

# The all powerful autosetup macro does not work after the second source tarball
gzip -dc %{_sourcedir}/cakephp-enum-behavior-%{ceb_version}.tar.gz | tar -xvvf -
rm -rf ./web/api/app/Plugin/CakePHP-Enum-Behavior
mv -f CakePHP-Enum-Behavior-%{ceb_version} ./web/api/app/Plugin/CakePHP-Enum-Behavior

# Change the following default values
./utils/zmeditconfigdata.sh ZM_OPT_CAMBOZOLA yes
./utils/zmeditconfigdata.sh ZM_UPLOAD_FTP_LOC_DIR %{_localstatedir}/spool/zoneminder-upload
./utils/zmeditconfigdata.sh ZM_OPT_CONTROL yes
./utils/zmeditconfigdata.sh ZM_CHECK_FOR_UPDATES no
./utils/zmeditconfigdata.sh ZM_DYN_SHOW_DONATE_REMINDER no
./utils/zmeditconfigdata.sh ZM_OPT_FAST_DELETE no

%build
%cmake3 \
        -DZM_WEB_USER="%{zmuid_final}" \
        -DZM_WEB_GROUP="%{zmgid_final}" \
        -DZM_TARGET_DISTRO="%{zmtargetdistro}" \
        .

%make_build

%install
%make_install

desktop-file-install					\
	--dir %{buildroot}%{_datadir}/applications	\
	--delete-original				\
	--mode 644					\
	%{buildroot}%{_datadir}/applications/zoneminder.desktop

# Remove unwanted files and folders
find %{buildroot} \( -name .htaccess -or -name .editorconfig -or -name .packlist -or -name .git -or -name .gitignore -or -name .gitattributes -or -name .travis.yml \) -type f -delete > /dev/null 2>&1 || :

# Recursively change shebang in all relevant scripts and set execute permission
find %{buildroot}%{_datadir}/zoneminder/www/api \( -name cake -or -name cake.php \) -type f -exec sed -i 's\^#!/usr/bin/env bash$\#!%{_buildshell}\' {} \; -exec %{__chmod} 755 {} \;

# Use the system cacert file rather then the one bundled with CakePHP
rm -f %{buildroot}%{_datadir}/zoneminder/www/api/lib/Cake/Config/cacert.pem
ln -s ../../../../../../../..%{_sysconfdir}/pki/tls/certs/ca-bundle.crt %{buildroot}%{_datadir}/zoneminder/www/api/lib/Cake/Config/cacert.pem

# Handle the polkit file differently for web server agnostic support (see post)
rm -f %{buildroot}%{_datadir}/polkit-1/rules.d/com.zoneminder.systemctl.rules

%post common
# Initial installation
if [ $1 -eq 1 ] ; then
    %systemd_post %{name}.service
fi

# Upgrade from a previous version of zoneminder 
if [ $1 -eq 2 ] ; then
    # Add any new PTZ control configurations to the database (will not overwrite)
    %{_bindir}/zmcamtool.pl --import >/dev/null 2>&1 || :

    # Freshen the database
    %{_bindir}/zmupdate.pl -f  >/dev/null 2>&1 || :
fi

# Warn the end user to read the README file
echo -e "\nVERY IMPORTANT: Before starting ZoneMinder, you must read the README file\nto finish the installation or upgrade!"
echo -e "\nThe README file is located here: %{_pkgdocdir}-common/README\n"

%post httpd
# For the case of changing from nginx <-> httpd, files in these folders must change ownership if they exist
%{_bindir}/chown -R %{zmuid_final}:%{zmgid_final} %{_sharedstatedir}/php/session/* >/dev/null 2>&1 || :
%{_bindir}/chown -R %{zmuid_final}:%{zmgid_final} %{_localstatedir}/log/zoneminder/* >/dev/null 2>&1 || :

ln -sf %{_sysconfdir}/zm/www/com.zoneminder.systemctl.rules.httpd %{_datadir}/polkit-1/rules.d/com.zoneminder.systemctl.rules
# backwards compatibility
ln -sf %{_sysconfdir}/zm/www/zoneminder.httpd.conf %{_sysconfdir}/zm/www/zoneminder.conf

# Allow zoneminder access to local video sources, serial ports, and x10
%{_bindir}/gpasswd -a %{zmuid_final} video >/dev/null 2>&1 || :
%{_bindir}/gpasswd -a %{zmuid_final} dialout >/dev/null 2>&1 || :

%post nginx

# Php package owns the session folder and sets group ownership to apache account
# We could override the folder permission, but adding nginx to the apache group works better
%{_bindir}/gpasswd -a nginx apache >/dev/null 2>&1 || :

# For the case of changing from httpd <-> nginx, files in these folders must change ownership if they exist
%{_bindir}/chown -R nginx:nginx %{_sharedstatedir}/php/session/* >/dev/null 2>&1 || :
%{_bindir}/chown -R nginx:nginx %{_localstatedir}/log/zoneminder/* >/dev/null 2>&1 || :

ln -sf %{_sysconfdir}/zm/www/com.zoneminder.systemctl.rules.nginx %{_datadir}/polkit-1/rules.d/com.zoneminder.systemctl.rules
# backwards compatibility
ln -sf %{_sysconfdir}/zm/www/zoneminder.nginx.conf %{_sysconfdir}/zm/www/zoneminder.conf

# Allow zoneminder access to local video sources, serial ports, and x10
%{_bindir}/gpasswd -a nginx video >/dev/null 2>&1 || :
%{_bindir}/gpasswd -a nginx dialout >/dev/null 2>&1 || :

# Nginx does not create an SSL certificate like the apache package does so lets do that here
if [ -f %{sslkey} -o -f %{sslcert} ]; then
   exit 0
fi

umask 077
%{_bindir}/openssl genrsa -rand /proc/apm:/proc/cpuinfo:/proc/dma:/proc/filesystems:/proc/interrupts:/proc/ioports:/proc/pci:/proc/rtc:/proc/uptime 2048 > %{sslkey} 2> /dev/null

FQDN=`hostname`
# A >59 char FQDN means "root@FQDN" exceeds 64-char max length for emailAddress
if [ "x${FQDN}" = "x" -o ${#FQDN} -gt 59 ]; then
   FQDN=localhost.localdomain
fi

cat << EOF | %{_bindir}/openssl req -new -key %{sslkey} \
         -x509 -sha256 -days 365 -set_serial $RANDOM -extensions v3_req \
         -out %{sslcert} 2>/dev/null
--
SomeState
SomeCity
SomeOrganization
SomeOrganizationalUnit
${FQDN}
root@${FQDN}
EOF

%preun
%systemd_preun %{name}.service

%postun
%systemd_postun_with_restart %{name}.service

%files
# nothing

%files common
%license COPYING
%doc README.md distros/redhat/readme/README distros/redhat/readme/README.httpd distros/redhat/readme/README.nginx distros/redhat/readme/README.https

# We want these two folders to have "normal" read permission
# compared to the folder contents
%dir %{_sysconfdir}/zm
%dir %{_sysconfdir}/zm/conf.d

# Config folder contents contain sensitive info
# and should not be readable by normal users
%{_sysconfdir}/zm/conf.d/README

%config(noreplace) %{_sysconfdir}/logrotate.d/zoneminder

%{_unitdir}/zoneminder.service
%{_datadir}/polkit-1/actions/com.zoneminder.systemctl.policy
%{_bindir}/zmsystemctl.pl

%{_bindir}/zma
%{_bindir}/zmaudit.pl
%{_bindir}/zmc
%{_bindir}/zmcontrol.pl
%{_bindir}/zmdc.pl
%{_bindir}/zmfilter.pl
%{_bindir}/zmpkg.pl
%{_bindir}/zmtrack.pl
%{_bindir}/zmtrigger.pl
%{_bindir}/zmu
%{_bindir}/zmupdate.pl
%{_bindir}/zmvideo.pl
%{_bindir}/zmwatch.pl
%{_bindir}/zmcamtool.pl
%{_bindir}/zmtelemetry.pl
%{_bindir}/zmx10.pl
%{_bindir}/zmonvif-probe.pl
%{_bindir}/zmstats.pl
%{_bindir}/zmrecover.pl

%{perl_vendorlib}/ZoneMinder*
%{perl_vendorlib}/ONVIF*
%{perl_vendorlib}/WSDiscovery*
%{perl_vendorlib}/WSSecurity*
%{perl_vendorlib}/WSNotification*
%{_mandir}/man*/*

%{_libexecdir}/zoneminder/
%{_datadir}/zoneminder/
%{_datadir}/applications/*zoneminder.desktop

%files httpd
%config(noreplace) %attr(640,root,%{zmgid_final}) %{_sysconfdir}/zm/zm.conf
%config(noreplace) %attr(640,root,%{zmgid_final}) %{_sysconfdir}/zm/conf.d/0*.conf
%ghost %attr(640,root,%{zmgid_final}) %{_sysconfdir}/zm/conf.d/zmcustom.conf
%config(noreplace) %{_sysconfdir}/zm/www/zoneminder.httpd.conf
%ghost %{_sysconfdir}/zm/www/zoneminder.conf
%config(noreplace) %{_sysconfdir}/zm/www/com.zoneminder.systemctl.rules.httpd
%ghost %{_datadir}/polkit-1/rules.d/com.zoneminder.systemctl.rules

%{_unitdir}/zoneminder.service.d/zm-httpd.conf
%{_tmpfilesdir}/zoneminder.httpd.tmpfiles.conf
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_sharedstatedir}/zoneminder
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_sharedstatedir}/zoneminder/events
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_sharedstatedir}/zoneminder/sock
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_sharedstatedir}/zoneminder/swap
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_sharedstatedir}/zoneminder/temp
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_localstatedir}/cache/zoneminder
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_localstatedir}/log/zoneminder
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_localstatedir}/spool/zoneminder-upload

%files nginx
%config(noreplace) %attr(640,root,nginx) %{_sysconfdir}/zm/zm.conf
%config(noreplace) %attr(640,root,nginx) %{_sysconfdir}/zm/conf.d/*.conf
%ghost %attr(640,root,nginx) %{_sysconfdir}/zm/conf.d/zmcustom.conf
%config(noreplace) %{_sysconfdir}/zm/www/zoneminder.nginx.conf
%config(noreplace) %{_sysconfdir}/zm/www/redirect.nginx.conf
%ghost %{_sysconfdir}/zm/www/zoneminder.conf
%config(noreplace) %{_sysconfdir}/zm/www/com.zoneminder.systemctl.rules.nginx
%ghost %{_datadir}/polkit-1/rules.d/com.zoneminder.systemctl.rules

%config(noreplace) %{_sysconfdir}/php-fpm.d/zoneminder.php-fpm.conf


%{_unitdir}/zoneminder.service.d/zm-nginx.conf
%{_tmpfilesdir}/zoneminder.nginx.tmpfiles.conf
%dir %attr(755,nginx,nginx) %{_sharedstatedir}/zoneminder
%dir %attr(755,nginx,nginx) %{_sharedstatedir}/zoneminder/events
%dir %attr(755,nginx,nginx) %{_sharedstatedir}/zoneminder/sock
%dir %attr(755,nginx,nginx) %{_sharedstatedir}/zoneminder/swap
%dir %attr(755,nginx,nginx) %{_sharedstatedir}/zoneminder/temp
%dir %attr(755,nginx,nginx) %{_localstatedir}/cache/zoneminder
%dir %attr(755,nginx,nginx) %{_localstatedir}/log/zoneminder
%dir %attr(755,nginx,nginx) %{_localstatedir}/spool/zoneminder-upload

%changelog
* Tue Feb 04 2020 Andrew Bauer <zonexpertconsulting@outlook.com> - 1.34.2-1
- 1.34.2 Release

* Fri Jan 31 2020 Andrew Bauer <zonexpertconsulting@outlook.com> - 1.34.1-1
- 1.34.1 Release

* Sat Jan 18 2020 Andrew Bauer <zonexpertconsulting@outlook.com> - 1.34.0-1
- 1.34.0 Release

* Tue Dec 17 2019 Leigh Scott <leigh123linux@gmail.com> - 1.32.3-5
- Mass rebuild for x264

* Wed Aug 07 2019 Leigh Scott <leigh123linux@gmail.com> - 1.32.3-4
- Rebuild for new ffmpeg version

* Tue Mar 12 2019 Sérgio Basto <sergio@serjux.com> - 1.32.3-3
- Mass rebuild for x264

* Tue Mar 05 2019 RPM Fusion Release Engineering <leigh123linux@gmail.com> - 1.32.3-2
- Rebuilt for https://fedoraproject.org/wiki/Fedora_30_Mass_Rebuild

* Sat Dec 08 2018 Andrew Bauer <zonexpertconsulting@outlook.com> - 1.32.3-1
- 1.32.3 Release
- Break into sub-packages

* Tue Nov 13 2018 Antonio Trande <sagitter@fedoraproject.org> - 1.32.2-2
- Rebuild for ffmpeg-3.4.5 on el7
- Use CMake3

* Sat Oct 13 2018 Andrew Bauer <zonexpertconsulting@outlook.com> - 1.32.2-1
- 1.32.2 release
- Bug fix release

* Thu Oct 04 2018 Sérgio Basto <sergio@serjux.com> - 1.32.1-2
- Mass rebuild for x264 and/or x265

* Tue Oct 2 2018 Andrew Bauer <zonexpertconsulting@outlook.com> - 1.32.1-1
- 1.32.1 release
- Bug fix release

* Wed Sep 12 2018 Andrew Bauer <zonexpertconsulting@outlook.com> - 1.32.0-1
- 1.32.0 release
- remove el6 (sys v init) support
- Make README name consistent across all supported distros
- remove jscalendar
- add requires php-json, zip
- support zm/conf.d folder
- support zm cache (busting) folder

* Sun Aug 19 2018 Leigh Scott <leigh123linux@googlemail.com> - 1.30.4-9
- Rebuilt for Fedora 29 Mass Rebuild binutils issue

* Fri Jul 27 2018 RPM Fusion Release Engineering <leigh123linux@gmail.com> - 1.30.4-8
- Rebuilt for https://fedoraproject.org/wiki/Fedora_29_Mass_Rebuild

* Thu Mar 08 2018 RPM Fusion Release Engineering <leigh123linux@googlemail.com> - 1.30.4-7
- Rebuilt for new ffmpeg snapshot

* Thu Mar 01 2018 RPM Fusion Release Engineering <leigh123linux@googlemail.com> - 1.30.4-6
- Rebuilt for https://fedoraproject.org/wiki/Fedora_28_Mass_Rebuild

* Thu Jan 18 2018 Leigh Scott <leigh123linux@googlemail.com> - 1.30.4-5
- Rebuilt for ffmpeg-3.5 git

* Thu Aug 31 2017 RPM Fusion Release Engineering <kwizart@rpmfusion.org> - 1.30.4-4
- Rebuilt for https://fedoraproject.org/wiki/Fedora_27_Mass_Rebuild

* Tue May 09 2017 Andrew Bauer <zonexpertconsulting@outlook.com> - 1.30.4-1
- modify autosetup macro parameters
- modify requirements for php-pecl-acpu-bc package
- 1.30.4 release

* Tue May 02 2017 Andrew Bauer <zonexpertconsulting@outlook.com> - 1.30.3-1
- 1.30.3 release

* Thu Mar 30 2017 Andrew Bauer <zonexpertconsulting@outlook.com> - 1.30.2-2
- 1.30.2 release

* Wed Feb 08 2017 Andrew Bauer <zonexpertconsulting@outlook.com> - 1.30.2-1
- Bump version for 1.30.2 release candidate 1

* Wed Dec 28 2016 Andrew Bauer <zonexpertconsulting@outlook.com> - 1.30.1-2 
- Changes from rpmfusion #4393

* Fri Dec 23 2016 Andrew Bauer <zonexpertconsulting@outlook.com> - 1.30.1-1 
- Consolidate fedora/centos spec files
- Add preliminary nginx support
- New contact email

* Thu Mar 3 2016 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.30.0-1 
- Bump version fo 1.30.0 release.

* Sat Nov 21 2015 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.29.0-1 
- Bump version for 1.29.0 release on Fedora 23.

* Sat Feb 14 2015 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.28.1-1 
- Bump version for 1.28.1 release on Fedora 21.

* Sun Oct 5 2014 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.28.0-1 
- Bump version for 1.28.0 release.

* Fri Mar 14 2014 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.27-1 
- Tweak build requirements for cmake

* Sat Feb 01 2014 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.27-1
- Add zmcamtool.pl. Bump version for 1.27 release. 

* Mon Dec 16 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.26.5-1
- This is a bug fixe release
- RTSP fixes, cmake enhancements, couple other misc fixes

* Mon Oct 07 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.26.4-1
- Initial cmake build.

* Sat Oct 05 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.26.4-1
- Fedora specific path changes have been moved to zoneminder-1.26.0-defaults.patch
- All files are now part of the zoneminder source tree. Update specfile accordingly.

* Sat Sep 21 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.26.3-1
- Initial rebuild for ZoneMinder 1.26.3 release.

* Fri Feb 15 2013 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 1.25.0-13
- Rebuilt for https://fedoraproject.org/wiki/Fedora_19_Mass_Rebuild

* Mon Jan 21 2013 Adam Tkac <atkac redhat com> - 1.25.0-12
- rebuild due to "jpeg8-ABI" feature drop

* Mon Jan 7 2013 Remi Collet <rcollet@redhat.com> - 1.25.0-11
- fix configuration file for httpd 2.4, #871502

* Fri Dec 21 2012 Adam Tkac <atkac redhat com> - 1.25.0-10
- rebuild against new libjpeg

* Thu Aug 09 2012 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.25.0-9
- Add patch to work around v4l2 api breakage in 3.5 kernel.

* Sun Jul 22 2012 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 1.25.0-8
- Rebuilt for https://fedoraproject.org/wiki/Fedora_18_Mass_Rebuild

* Sat Jun 23 2012 Petr Pisar <ppisar@redhat.com> - 1.25.0-7
- Perl 5.16 rebuild

* Wed Mar 21 2012 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.25.0-6
- Fix stupid thinko in sql modifications.

* Sat Feb 25 2012 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.25.0-5
- Clean up macro usage.

* Sat Feb 25 2012 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.25.0-4
- Convert to systemd.
- Add tmpfiles.d configuration since the initscript isn't around to create
  /run/zoneminder.
- Remove some pointless executable permissions.
- Add logrotate file.

* Wed Feb 22 2012 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.25.0-3
- Update README.Fedora to reference systemctl and mention timezone info in
  php.ini.
- Add proper default for EYEZM_LOG_TO_FILE.


* Thu Feb 09 2012 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.25.0-2
- Rebuild for new pcre.

* Thu Jan 19 2012 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.25.0-1
- Update to 1.25.0
- Fix gcc4.7 build problems.
- Drop gcc4.4 build fixes; for whatever reason they now break the build.
- Clean up old patches.
- Force setting of ZM_TMPDIR and ZM_RUNDIR.

* Sat Jan 14 2012 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 1.24.4-4
- Rebuilt for https://fedoraproject.org/wiki/Fedora_17_Mass_Rebuild

* Thu Sep 15 2011 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.24.4-3
- Re-add the dist-tag that somehow got lost.

* Thu Sep 15 2011 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.24.4-2
- Add patch for bug 711780 - fix syntax issue in Mapped.pm.
- Undo that patch, and undo another which was the cause of the whole mess.
- Fix up other patches so ZM_PATH_BUILD is both defined and useful.
- Make sure database creation mods actually take.
- Update Fedora-specific docs with some additional info.
- Use bundled mootools (javascript, so no guideline violation).
- Update download location.
- Update the gcrypt patch to actually work.
- Upstream changed the tarball without changing the version to patch a
  vulnerability, so redownload.

* Sun Aug 14 2011 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.24.4-1
- Initial attempt to upgrade to 1.24.4.
- Add patch from BZ 460310 to build against libgcrypt instead of requiring the
  gnutls openssl libs.

* Thu Jul 21 2011 Petr Sabata <contyk@redhat.com> - 1.24.3-7.20110324svn3310
- Perl mass rebuild

* Wed Jul 20 2011 Petr Sabata <contyk@redhat.com> - 1.24.3-6.20110324svn3310
- Perl mass rebuild

* Mon May 09 2011 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.24.3-5.20110324svn3310
- Bump for gnutls update.

* Thu Mar 24 2011 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.24.3-4.20110324svn3310
- Update to latest 1.24.3 subversion. Turns out that what upstream was calling
  1.24.3 is really just an occasionally updated devel snapshot.
- Rebase various patches.

* Wed Mar 23 2011 Dan Horák <dan@danny.cz> - 1.24.3-3
- rebuilt for mysql 5.5.10 (soname bump in libmysqlclient)

* Tue Feb 08 2011 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 1.24.3-2
- Rebuilt for https://fedoraproject.org/wiki/Fedora_15_Mass_Rebuild

* Tue Jan 25 2011 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.24.3-1
- Update to latest upstream version.
- Rebase patches.
- Initial incomplete attempt to disable v4l1 support.

* Fri Jan 21 2011 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.24.2-6
- Unbundle cambozola; instead link to the separately pacakged copy.
- Remove BuildRoot:, %%clean and buildroot cleaning in %%install.
- Git rid of mixed space/tab usage by removing all tabs.
- Remove unnecessary Conflicts: line.
- Attempt to force short_open_tag on for the code directories.
- Move default location of sockets, swaps, logfiles and some temporary files to
  make more sense and allow things to work better with a future selinux policy.
- Fix errors in README.Fedora.

* Wed Jun 02 2010 Marcela Maslanova <mmaslano@redhat.com> - 1.24.2-5
- Mass rebuild with perl-5.12.0

* Fri Dec 4 2009 Stepan Kasal <skasal@redhat.com> - 1.24.2-4
- rebuild against perl 5.10.1
- use Perl vendorarch and archlib variables correctly

* Mon Jul 27 2009 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 1.24.2-3
- Rebuilt for https://fedoraproject.org/wiki/Fedora_12_Mass_Rebuild

* Wed Jul 22 2009 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.24.2-2
- Bump release since 1.24.2-1 was mistakenly tagged a few months ago.

* Wed Jul 22 2009 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.24.2-1
- Initial update to 1.24.2.
- Rebase patches.
- Update mootools download location.
- Update to mootools 1.2.3.
- Add additional dependencies for some optional features.

* Sat Apr 11 2009 Martin Ebourne <martin@zepler.org> - 1.24.1-3
- Remove unused Sys::Mmap perl dependency RPM is finding

* Sat Apr 11 2009 Martin Ebourne <martin@zepler.org> - 1.24.1-2
- Update gcc44 patch to disable -frepo, seems to be broken with gcc44
- Added noffmpeg patch to make building outside mock easier

* Sat Mar 21 2009 Martin Ebourne <martin@zepler.org> - 1.24.1-1
- Patch for gcc 4.4 compilation errors
- Upgrade to 1.24.1

* Wed Feb 25 2009 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 1.23.3-4
- Rebuilt for https://fedoraproject.org/wiki/Fedora_11_Mass_Rebuild

* Sat Jan 24 2009 Caolán McNamara <caolanm@redhat.com> - 1.23.3-3
- rebuild for dependencies

* Mon Dec 15 2008 Martin Ebourne <martin@zepler.org> - 1.23.3-2
- Fix permissions on zm.conf

* Fri Jul 11 2008 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.23.3-1
- Initial attempt at packaging 1.23.

* Tue Jul 1 2008 Martin Ebourne <martin@zepler.org> - 1.22.3-15
- Add perl module compat dependency, bz #453590

* Tue May 6 2008 Martin Ebourne <martin@zepler.org> - 1.22.3-14
- Remove default runlevel, bz #441315

* Mon Apr 28 2008 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.22.3-13
- Backport patch for CVE-2008-1381 from 1.23.3 to 1.22.3.

* Tue Feb 19 2008 Fedora Release Engineering <rel-eng@fedoraproject.org> - 1.22.3-12
- Autorebuild for GCC 4.3

* Thu Jan 3 2008 Martin Ebourne <martin@zepler.org> - 1.22.3-11
- Fix compilation on gcc 4.3

* Thu Dec 6 2007 Martin Ebourne <martin@zepler.org> - 1.22.3-10
- Rebuild for new openssl

* Thu Aug 2 2007 Martin Ebourne <martin@zepler.org> - 1.22.3-8
- Fix licence tag

* Thu Jul 12 2007 Martin Ebourne <martin@zepler.org> - 1.22.3-7
- Fixes from testing by Jitz including missing dependencies and database creation

* Sat Jun 30 2007 Martin Ebourne <martin@zepler.org> - 1.22.3-6
- Disable crashtrace on ppc

* Sat Jun 30 2007 Martin Ebourne <martin@zepler.org> - 1.22.3-5
- Fix uid for directories in /var/lib/zoneminder

* Tue Jun 26 2007 Martin Ebourne <martin@zepler.org> - 1.22.3-4
- Added perl Archive::Tar dependency
- Disabled web interface due to lack of access control on the event images

* Sun Jun 10 2007 Martin Ebourne <martin@zepler.org> - 1.22.3-3
- Changes recommended in review by Jason Tibbitts

* Mon Apr 2 2007 Martin Ebourne <martin@zepler.org> - 1.22.3-2
- Standardised on package name of zoneminder

* Thu Dec 28 2006 Martin Ebourne <martin@zepler.org> - 1.22.3-1
- First version. Uses some parts from zm-1.20.1 by Corey DeLasaux and Serg Oskin
