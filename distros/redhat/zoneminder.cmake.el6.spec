%define zmuid $(id -un)
%define zmgid $(id -gn)
%define zmuid_final apache
%define zmgid_final apache

Name:       zoneminder
Version:    1.28.0
Release:    1%{?dist}
Summary:    A camera monitoring and analysis tool
Group:      System Environment/Daemons
# jscalendar is LGPL (any version):  http://www.dynarch.com/projects/calendar/
# Mootools is inder the MIT license: http://mootools.net/
# Cambozola is GPL: http://www.charliemouse.com/code/cambozola/
License:    GPLv2+ and LGPLv2+ and MIT 
URL:        http://www.zoneminder.com/

#Source0: https://github.com/ZoneMinder/ZoneMinder/archive/v%{version}.tar.gz
Source0:    ZoneMinder-%{version}.tar.gz

BuildRequires:  cmake gnutls-devel bzip2-devel
BuildRequires:  mysql-devel pcre-devel libjpeg-turbo-devel
BuildRequires:  perl(Archive::Tar) perl(Archive::Zip)
BuildRequires:  perl(Date::Manip) perl(DBD::mysql)
BuildRequires:  perl(ExtUtils::MakeMaker) perl(LWP::UserAgent)
BuildRequires:  perl(MIME::Entity) perl(MIME::Lite)
BuildRequires:  perl(PHP::Serialization) perl(Sys::Mmap)
BuildRequires:  perl(Time::HiRes) perl(Net::SFTP::Foreign)
BuildRequires:  perl(Expect) perl(X10::ActiveHome) perl(Astro::SunTime)
BuildRequires:  libcurl-devel vlc-devel ffmpeg-devel polkit-devel
# cmake needs the following installed at build time due to the way it auto-detects certain parameters
BuildRequires:  httpd ffmpeg

Requires:   httpd php php-mysql mysql-server libjpeg-turbo polkit
Requires:   perl(:MODULE_COMPAT_%(eval "`%{__perl} -V:version`"; echo $version))
Requires:   perl(DBD::mysql) perl(Archive::Tar) perl(Archive::Zip)
Requires:   perl(MIME::Entity) perl(MIME::Lite) perl(Net::SMTP) perl(Net::FTP)
Requires:   libcurl vlc-core ffmpeg

Requires(post): /sbin/chkconfig
Requires(post): /usr/bin/checkmodule
Requires(post): /usr/bin/semodule_package
Requires(post): /usr/sbin/semodule
Requires(post): /usr/bin/gpasswd
Requires(post): /usr/bin/less
Requires(preun): /sbin/chkconfig
Requires(preun): /sbin/service
Requires(preun): /usr/sbin/semodule
Requires(postun): /sbin/service


%description
ZoneMinder is a set of applications which is intended to provide a complete
solution allowing you to capture, analyse, record and monitor any cameras you
have attached to a Linux based machine. It is designed to run on kernels which
support the Video For Linux (V4L) interface and has been tested with cameras
attached to BTTV cards, various USB cameras and IP network cameras. It is
designed to support as many cameras as you can attach to your computer without
too much degradation of performance.


%prep
%setup -q -n ZoneMinder-%{version}

# Change the following default values
./utils/zmeditconfigdata.sh ZM_PATH_ZMS /cgi-bin/zm/nph-zms
./utils/zmeditconfigdata.sh ZM_OPT_CAMBOZOLA yes
./utils/zmeditconfigdata.sh ZM_PATH_SWAP /dev/shm
./utils/zmeditconfigdata.sh ZM_UPLOAD_FTP_LOC_DIR /var/spool/zoneminder-upload
./utils/zmeditconfigdata.sh ZM_OPT_CONTROL yes

%build
# Have to override CMAKE_INSTALL_LIBDIR for cmake < 2.8.7 due to this bug:
# https://bugzilla.redhat.com/show_bug.cgi?id=795542
%cmake -DZM_TARGET_DISTRO="el6" -DCMAKE_INSTALL_LIBDIR:PATH=%{_lib} -DZM_PERL_SUBPREFIX=`x="%{perl_vendorlib}" ; echo ${x#"%{_prefix}"}` .

make %{?_smp_mflags}

%install
export DESTDIR=%{buildroot}
make install

%post
/sbin/chkconfig --add zoneminder
/sbin/chkconfig zoneminder on

# Allow zoneminder access to local video sources, serial ports, and x10
echo
/usr/bin/gpasswd -a %{zmuid_final} video
/usr/bin/gpasswd -a %{zmuid_final} dialout

# Create and load zoneminder selinux policy module
echo -e "\nCreating and installing a ZoneMinder SELinux policy module. Please wait.\n"
/usr/bin/checkmodule -M -m -o %{_docdir}/%{name}-%{version}/local_zoneminder.mod %{_docdir}/%{name}-%{version}/local_zoneminder.te > /dev/null
/usr/bin/semodule_package -o %{_docdir}/%{name}-%{version}/local_zoneminder.pp -m %{_docdir}/%{name}-%{version}/local_zoneminder.mod > /dev/null 
/usr/sbin/semodule -i %{_docdir}/%{name}-%{version}/local_zoneminder.pp > /dev/null

# Display the README for post installation instructions
/usr/bin/less %{_docdir}/%{name}-%{version}/README.CentOS

%preun
if [ $1 -eq 0 ]; then
    /sbin/service zoneminder stop > /dev/null 2>&1 || :
    /sbin/chkconfig --del zoneminder
    echo -e "\nRemoving ZoneMinder SELinux policy module. Please wait.\n"
    /usr/sbin/semodule -r local_zoneminder.pp
fi


%postun
if [ $1 -ge 1 ]; then
    /sbin/service zoneminder condrestart > /dev/null 2>&1 || :
fi

# Remove the doc folder. 
rm -rf %{_docdir}/%{name}-%{version}

%files
%defattr(-,root,root,-)
%doc AUTHORS BUGS ChangeLog COPYING LICENSE NEWS README.md distros/redhat/README.CentOS distros/redhat/jscalendar-doc
%doc distros/redhat/cambozola-doc distros/redhat/local_zoneminder.te
%config %attr(640,root,%{zmgid_final}) %{_sysconfdir}/zm.conf
%config(noreplace) %attr(644,root,root) %{_sysconfdir}/httpd/conf.d/zoneminder.conf
%config(noreplace) /etc/logrotate.d/%{name}
%attr(755,root,root) %{_initrddir}/zoneminder

%{_bindir}/zma
%{_bindir}/zmaudit.pl
%{_bindir}/zmc
%{_bindir}/zmcontrol.pl
%{_bindir}/zmdc.pl
%{_bindir}/zmf
%{_bindir}/zmfilter.pl
%{_bindir}/zmpkg.pl
%{_bindir}/zmstreamer
%{_bindir}/zmtrack.pl
%{_bindir}/zmtrigger.pl
%{_bindir}/zmu
%{_bindir}/zmupdate.pl
%{_bindir}/zmvideo.pl
%{_bindir}/zmwatch.pl
%{_bindir}/zmcamtool.pl
%{_bindir}/zmsystemctl.pl
%{_bindir}/zmx10.pl

%{perl_vendorlib}/ZoneMinder*
%{perl_vendorlib}/%{_arch}-linux-thread-multi/auto/ZoneMinder*
%{_mandir}/man*/*
%dir %{_libexecdir}/%{name}
%{_libexecdir}/%{name}/cgi-bin
%dir %{_datadir}/%{name}
%{_datadir}/%{name}/db
%{_datadir}/%{name}/www

%{_datadir}/polkit-1/actions/com.zoneminder.systemctl.policy
%{_datadir}/polkit-1/rules.d/com.zoneminder.systemctl.rules

%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_localstatedir}/lib/zoneminder
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_localstatedir}/lib/zoneminder/events
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_localstatedir}/lib/zoneminder/images
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_localstatedir}/lib/zoneminder/sock
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_localstatedir}/lib/zoneminder/swap
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_localstatedir}/lib/zoneminder/temp
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_localstatedir}/log/zoneminder
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{_localstatedir}/spool/zoneminder-upload

%changelog
* Sun Oct 5 2014 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.28.0 
- Bump version for 1.28.0 release.

* Fri Mar 14 2014 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.27 
- Tweak build requirements for cmake

* Sat Feb 01 2014 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.27
- Add zmcamtool.pl. Bump version for 1.27 release. 

* Mon Dec 16 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.26.5
- This is a bug fixe release
- RTSP fixes, cmake enhancements, couple other misc fixes

* Sat Oct 19 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.26.4
- Streamline the cmake build. Move much code into cmakelist.txt file.

* Mon Oct 07 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.26.4
- Initial cmake build.

* Sun Oct 06 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.26.4
- All files are now part of the zoneminder source tree. Update specfile accordingly.

* Thu Sep 05 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.26.0
- 1.26.0 Release
- https://github.com/ZoneMinder/ZoneMinder/archive/v1.26.0.tar.gz

* Sun Sep 01 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.26.0-beta
- Update SELinux policy module

* Thu Aug 29 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.26.0-beta
- Third Beta release
- https://github.com/ZoneMinder/ZoneMinder/tree/release-1.26
- Reduce number of uneeded dependencies by integrating cambozola into spec file

* Thu Aug 15 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.26.0-beta
- Initial Beta release
- https://github.com/ZoneMinder/ZoneMinder/tree/release-1.26

* Sun Aug 11 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.25.0-kfirproper
- Modified specfile to work with kfir-proper branch  
- https://github.com/ZoneMinder/ZoneMinder/tree/kfir-proper

* Wed Aug 07 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.25.0-2svn3827
- Move RHEL/CentOS specific defaults to a patch file
- Add bzip2-devel as a build dependency
- Default ZM_SSL_LIB back to gnutls. AUTH_RELAY = hashed didn't work with openssl. 

* Fri Aug 02 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.25.0-1svn3827
- Update to latest 1.25.0 subversion. 
- Does not compile with modern versions of ffmpeg. Configure to work only with older versions.
- Does not compile with gcc 4.7. Configure to build with gcc less than 4.7.  

* Thu Mar 24 2011 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.24.3-4.20110324svn3310
- Update to latest 1.24.3 subversion.  Turns out that what upstream was calling
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
- Fix errors in README.CentOS.

* Wed Jun 02 2010 Marcela Maslanova <mmaslano@redhat.com> - 1.24.2-5
- Mass rebuild with perl-5.12.0

* Fri Dec  4 2009 Stepan Kasal <skasal@redhat.com> - 1.24.2-4
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

* Tue Jul  1 2008 Martin Ebourne <martin@zepler.org> - 1.22.3-15
- Add perl module compat dependency, bz #453590

* Tue May  6 2008 Martin Ebourne <martin@zepler.org> - 1.22.3-14
- Remove default runlevel, bz #441315

* Mon Apr 28 2008 Jason L Tibbitts III <tibbs@math.uh.edu> - 1.22.3-13
- Backport patch for CVE-2008-1381 from 1.23.3 to 1.22.3.

* Tue Feb 19 2008 Fedora Release Engineering <rel-eng@fedoraproject.org> - 1.22.3-12
- Autorebuild for GCC 4.3

* Thu Jan  3 2008 Martin Ebourne <martin@zepler.org> - 1.22.3-11
- Fix compilation on gcc 4.3

* Thu Dec  6 2007 Martin Ebourne <martin@zepler.org> - 1.22.3-10
- Rebuild for new openssl

* Thu Aug  2 2007 Martin Ebourne <martin@zepler.org> - 1.22.3-8
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

* Mon Apr  2 2007 Martin Ebourne <martin@zepler.org> - 1.22.3-2
- Standardised on package name of zoneminder

* Thu Dec 28 2006 Martin Ebourne <martin@zepler.org> - 1.22.3-1
- First version. Uses some parts from zm-1.20.1 by Corey DeLasaux and Serg Oskin
