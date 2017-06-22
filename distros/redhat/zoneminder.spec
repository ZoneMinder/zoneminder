%global zmuid_final apache
%global zmgid_final apache

# Crud is configured as a git submodule
%global crud_version 3.0.10

%if "%{zmuid_final}" == "nginx"
%global with_nginx 1
%global wwwconfdir %{_sysconfdir}/nginx/default.d
%else
%global wwwconfdir %{_sysconfdir}/httpd/conf.d
%endif

%global sslcert %{_sysconfdir}/pki/tls/certs/localhost.crt
%global sslkey %{_sysconfdir}/pki/tls/private/localhost.key

# This will tell zoneminder's cmake process we are building against a known distro
%global zmtargetdistro %{?rhel:el%{rhel}}%{!?rhel:fc%{fedora}}

# Fedora >= 25 needs apcu backwards compatibility module
%if 0%{?fedora} >= 25
%global with_apcu_bc 1
%endif

# Include files for SysV init or systemd
%if 0%{?fedora} >= 15 || 0%{?rhel} >= 7
%global with_init_systemd 1
%else
%global with_init_sysv 1
%endif

%global readme_suffix %{?rhel:Redhat%{?rhel}}%{!?rhel:Fedora}
%global _hardened_build 1

Name: zoneminder
Version: 1.31.1
Release: 1%{?dist}
Summary: A camera monitoring and analysis tool
Group: System Environment/Daemons
# jscalendar is LGPL (any version): http://www.dynarch.com/projects/calendar/
# Mootools is inder the MIT license: http://mootools.net/
# CakePHP is under the MIT license: https://github.com/cakephp/cakephp
# Crud is under the MIT license: https://github.com/FriendsOfCake/crud
License: GPLv2+ and LGPLv2+ and MIT
URL: http://www.zoneminder.com/

Source0: https://github.com/ZoneMinder/ZoneMinder/archive/%{version}.tar.gz#/zoneminder-%{version}.tar.gz
Source1: https://github.com/FriendsOfCake/crud/archive/v%{crud_version}.tar.gz#/crud-%{crud_version}.tar.gz

%{?with_init_systemd:BuildRequires: systemd-devel}
%{?with_init_systemd:BuildRequires: mariadb-devel}
%{?with_init_systemd:BuildRequires: perl-podlators}
%{?with_init_systemd:BuildRequires: polkit-devel}
%{?with_init_sysv:BuildRequires: mysql-devel}
%{?el6:BuildRequires: epel-rpm-macros}
BuildRequires: cmake >= 2.8.7
BuildRequires: gnutls-devel
BuildRequires: bzip2-devel
BuildRequires: pcre-devel 
BuildRequires: libjpeg-turbo-devel
BuildRequires: findutils
BuildRequires: coreutils
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
BuildRequires: ffmpeg-devel

%{?with_nginx:Requires: nginx}
%{?with_nginx:Requires: fcgiwrap}
%{?with_nginx:Requires: php-fpm}
%{!?with_nginx:Requires: httpd}
%{!?with_nginx:Requires: php}
Requires: php-mysqli
Requires: php-common
Requires: php-gd
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

%{?with_init_systemd:Requires(post): systemd}
%{?with_init_systemd:Requires(post): systemd-sysv}
%{?with_init_systemd:Requires(preun): systemd}
%{?with_init_systemd:Requires(postun): systemd}

%{?with_init_sysv:Requires(post): /sbin/chkconfig}
%{?with_init_sysv:Requires(post): %{_bindir}/checkmodule}
%{?with_init_sysv:Requires(post): %{_bindir}/semodule_package}
%{?with_init_sysv:Requires(post): %{_sbindir}/semodule}
%{?with_init_sysv:Requires(preun): /sbin/chkconfig}
%{?with_init_sysv:Requires(preun): /sbin/service}
%{?with_init_sysv:Requires(preun): %{_sbindir}/semodule}
%{?with_init_sysv:Requires(postun): /sbin/service}

Requires(post): %{_bindir}/gpasswd
Requires(post): %{_bindir}/less

%description
ZoneMinder is a set of applications which is intended to provide a complete
solution allowing you to capture, analyze, record and monitor any cameras you
have attached to a Linux based machine. It is designed to run on kernels which
support the Video For Linux (V4L) interface and has been tested with cameras
attached to BTTV cards, various USB cameras and IP network cameras. It is
designed to support as many cameras as you can attach to your computer without
too much degradation of performance.

%prep
%autosetup -p 1 -a 1 -n ZoneMinder-%{version}
%{__rm} -rf ./web/api/app/Plugin/Crud
%{__mv} -f crud-%{crud_version} ./web/api/app/Plugin/Crud

# Change the following default values
./utils/zmeditconfigdata.sh ZM_OPT_CAMBOZOLA yes
./utils/zmeditconfigdata.sh ZM_UPLOAD_FTP_LOC_DIR %{_localstatedir}/spool/zoneminder-upload
./utils/zmeditconfigdata.sh ZM_OPT_CONTROL yes
./utils/zmeditconfigdata.sh ZM_CHECK_FOR_UPDATES no
./utils/zmeditconfigdata.sh ZM_DYN_SHOW_DONATE_REMINDER no
./utils/zmeditconfigdata.sh ZM_OPT_FAST_DELETE no

%build
%cmake \
        -DZM_WEB_USER="%{zmuid_final}" \
        -DZM_WEB_GROUP="%{zmuid_final}" \
        -DZM_TARGET_DISTRO="%{zmtargetdistro}" \
        .

%make_build

%install
%make_install

# Remove unwanted files and folders
find %{buildroot} \( -name .packlist -or -name .git -or -name .gitignore -or -name .gitattributes -or -name .travis.yml \) -type f -delete > /dev/null 2>&1 || :

%post
%if 0%{?with_init_sysv}
/sbin/chkconfig --add zoneminder
/sbin/chkconfig zoneminder on

# Create and load zoneminder selinux policy module
echo -e "\nCreating and installing a ZoneMinder SELinux policy module. Please wait.\n"
%{_bindir}/checkmodule -M -m -o %{_docdir}/%{name}-%{version}/local_zoneminder.mod %{_docdir}/%{name}-%{version}/local_zoneminder.te > /dev/null 2>&1 || :
%{_bindir}/semodule_package -o %{_docdir}/%{name}-%{version}/local_zoneminder.pp -m %{_docdir}/%{name}-%{version}/local_zoneminder.mod > /dev/null 2>&1 || :
%{_sbindir}/semodule -i %{_docdir}/%{name}-%{version}/local_zoneminder.pp > /dev/null 2>&1 || :

%endif

%if 0%{?with_init_systemd}
# Initial installation
if [ $1 -eq 1 ] ; then
    %systemd_post %{name}.service
fi
%endif

# Upgrade from a previous version of zoneminder 
if [ $1 -eq 2 ] ; then

    # Add any new PTZ control configurations to the database (will not overwrite)
    %{_bindir}/zmcamtool.pl --import >/dev/null 2>&1 || :

    # Freshen the database
    %{_bindir}/zmupdate.pl -f  >/dev/null 2>&1 || :

    # We can't run this automatically when new sql account permissions need to
    # be manually added first
    # Run zmupdate non-interactively
    # zmupdate.pl --nointeractive
fi

# Allow zoneminder access to local video sources, serial ports, and x10
%{_bindir}/gpasswd -a %{zmuid_final} video >/dev/null 2>&1 || :
%{_bindir}/gpasswd -a %{zmuid_final} dialout >/dev/null 2>&1 || :

# Warn the end user to read the README file
echo -e "\nVERY IMPORTANT: Before starting ZoneMinder, read README.%{readme_suffix} to finish the\ninstallation or upgrade!\n"
echo -e "\nThe README file is located here: %{_docdir}/%{name}\n"

%if 0%{?with_nginx}
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
%endif

%preun
%if 0%{?with_init_sysv}
if [ $1 -eq 0 ]; then
    /sbin/service zoneminder stop > /dev/null 2>&1 || :
    /sbin/chkconfig --del zoneminder
    echo -e "\nRemoving ZoneMinder SELinux policy module. Please wait.\n"
    %{_sbindir}/semodule -r local_zoneminder.pp
fi
%endif

%if 0%{?with_init_systemd}
%systemd_preun %{name}.service
%endif

%postun
%if 0%{?with_init_sysv}
if [ $1 -ge 1 ]; then
    /sbin/service zoneminder condrestart > /dev/null 2>&1 || :
fi

# Remove the doc folder. 
rm -rf %{_docdir}/%{name}-%{version}
%endif

%if 0%{?with_init_systemd}
%systemd_postun_with_restart %{name}.service
%endif

%if 0%{?with_init_systemd}
%triggerun -- zoneminder < 1.25.0-4
# Save the current service runlevel info
# User must manually run systemd-sysv-convert --apply zoneminder
# to migrate them to systemd targets
%{_bindir}/systemd-sysv-convert --save zoneminder >/dev/null 2>&1 ||:

# Run these because the SysV package being removed won't do them
/sbin/chkconfig --del zoneminder >/dev/null 2>&1 || :
/bin/systemctl try-restart zoneminder.service >/dev/null 2>&1 || :
%endif

%files
%license COPYING
%doc AUTHORS README.md distros/redhat/readme/README.%{readme_suffix} distros/redhat/readme/README.https distros/redhat/jscalendar-doc
%dir %{_sysconfdir}/zm
%dir %{_sysconfdir}/zm/conf.d
%{_sysconfdir}/zm/conf.d/README
# Always overwrite zm.conf now that ZoneMinder supports conf.d folder
%attr(640,root,%{zmgid_final}) %{_sysconfdir}/zm/zm.conf
%config(noreplace) %attr(640,root,%{zmgid_final}) %{_sysconfdir}/zm/conf.d/*.conf

%config(noreplace) %attr(644,root,root) %{wwwconfdir}/zoneminder.conf
%config(noreplace) %{_sysconfdir}/logrotate.d/zoneminder

%if 0%{?with_nginx}
%config(noreplace) %{_sysconfdir}/php-fpm.d/zoneminder.conf
%endif

%if 0%{?with_init_systemd}
%{_tmpfilesdir}/zoneminder.conf
%{_unitdir}/zoneminder.service
%{_datadir}/polkit-1/actions/com.zoneminder.systemctl.policy
%{_datadir}/polkit-1/rules.d/com.zoneminder.systemctl.rules
%endif

%if 0%{?with_init_sysv}
%doc distros/redhat/misc/local_zoneminder.te
%attr(755,root,root) %{_initrddir}/zoneminder
%endif

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
%{_bindir}/zmsystemctl.pl
%{_bindir}/zmtelemetry.pl
%{_bindir}/zmx10.pl
%{_bindir}/zmonvif-probe.pl

%{perl_vendorlib}/ZoneMinder*
%{perl_vendorlib}/ONVIF*
%{perl_vendorlib}/WSDiscovery*
%{perl_vendorlib}/WSSecurity*
%{perl_vendorlib}/WSNotification*
%{_mandir}/man*/*

%{_libexecdir}/zoneminder/
%{_datadir}/zoneminder/

%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_sharedstatedir}/zoneminder
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_sharedstatedir}/zoneminder/events
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_sharedstatedir}/zoneminder/images
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_sharedstatedir}/zoneminder/sock
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_sharedstatedir}/zoneminder/swap
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_sharedstatedir}/zoneminder/temp
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_localstatedir}/log/zoneminder
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_localstatedir}/spool/zoneminder-upload
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_localstatedir}/run/zoneminder

%changelog
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
