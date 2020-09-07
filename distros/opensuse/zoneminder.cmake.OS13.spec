%define zmuid $(id -un)
%define zmgid $(id -gn)
%define zmuid_final wwwrun
%define zmgid_final www
# definitions for OpenSuse
%define zm_tmpdir /var/run/zoneminder
%define zm_instdir /opt/zoneminder
%define zm_rundir %{zm_instdir}/bin
# OpenSuse seems to have its web services in a different
# directory structure to some other distros
%define webroot /srv/www/htdocs
%define webcgi /srv/www/cgi-bin

Name: zoneminder
Version: 1.27.0
Release: 1%{?dist}
Summary: A camera monitoring and analysis tool
Group: System Environment/Daemons
# Mootools is under the MIT license: http://mootools.net/
License: GPLv2+ and LGPLv2+ and MIT
URL: http://www.zoneminder.com/

Source: ZoneMinder-%{version}.tar.gz

# patch no longer necessary as OpenSuse now in standard build
# Patch1: zoneminder-1.26.5-opensuse.patch

BuildRequires: cmake polkit-devel
BuildRequires: perl-DBI perl-DBD-mysql perl-Date-Manip perl-Sys-Mmap 
BuildRequires: libjpeg62 libjpeg62-devel libmysqld-devel libSDL-devel libgcrypt-devel libgnutls-devel
BuildRequires: libffmpeg-devel x264
BuildRequires: pcre-devel w32codec-all  

Requires: apache2 apache2-mod_php5 mysql polkit
Requires: ffmpeg libavformat55
Requires: php php-mysql 
Requires: perl(:MODULE_COMPAT_%(eval "`%{__perl} -V:version`"; echo $version))
Requires: perl-Sys-Mmap perl-Date-Manip perl-DBD-mysql
Requires: perl-Archive-Tar perl-Archive-Zip
Requires: perl-MIME-Lite perl-LWP-Protocol-https

# Can't find suitable packages for OpenSuse for 
# perl-MIME-Entity perl-Net-SMTP perl-Net-FTP so installing using cpanm
# cpanm needs make
# Am installing perl(MIME::Tools), perl(Net::SMTP) and perl(Net::FTP) 
# MIME::Tools provides MIME::Entity

Requires(post): make cpanm

Requires(post): /usr/bin/gpasswd

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
# cp and patch no longer necessary as opensuse distro now in standard build from 1.27.0 on
# cp -R /home/makerpm/rpmbuild/SOURCES/opensuse distros
# %patch1 -p0 -b .opensuse

%build
# For OpenSuse 13.1 we need to set DENABLE_MMAP to yes to vercome a problem 
# where the perl modules don't have shared memory enabled
%cmake  \
	-DCMAKE_INSTALL_PREFIX=%{zm_instdir} \
	-DZM_TARGET_DISTRO="OS13" \
	-DZM_NO_X10=ON \
	-DENABLE_MMAP=yes

make 
# There doesn't seem to be any point in using the next make as the
# makefiles for cmake don't seem to support multiple streams	
#make %{?_smp_mflags}

%install
export DESTDIR=%{buildroot}
# don't understand why but the built system appears in build under BUILDROOT
cd build
make install prefix=\${RPM_BUILD_ROOT}
cd ..

%post
if [ $1 -eq 1 ] ; then
    # Initial installation
    /bin/systemctl daemon-reload >/dev/null 2>&1 || :
fi

# Allow zoneminder access to local video sources
/usr/bin/gpasswd -a %zmuid_final video


# Display the README for post installation instructions
#/usr/bin/less %{_docdir}/%{name}-%{version}/README.OpenSuse
# both less and more scroll straight off the end of the file
# so we'll output info with echo

echo Installing additional perl modules
/usr/bin/cpanm MIME::Tools
/usr/bin/cpanm Net::SMTP
/usr/bin/cpanm Net::FTP
echo \***********************************************
echo \*****         For further information 
echo \*****         please refer to 
echo \*****  %{_docdir}/%{name}/README.OpenSuse
echo \*****
echo \***********************************************

%preun
if [ $1 -eq 0 ] ; then
    # Package removal, not upgrade
    /bin/systemctl --no-reload disable zoneminder.service > /dev/null 2>&1 || :
    /bin/systemctl stop zoneminder.service > /dev/null 2>&1 || :
fi

%postun
/bin/systemctl daemon-reload >/dev/null 2>&1 || :
if [ $1 -ge 1 ] ; then
    # Package upgrade, not uninstall
    /bin/systemctl try-restart zoneminder.service >/dev/null 2>&1 || :
fi

# Next section removed for OpenSuse as the install starts
# at 1.26.5 for this rpm
# %triggerun -- zoneminder < 1.25.0-4
# Save the current service runlevel info
# User must manually run systemd-sysv-convert --apply zoneminder
# to migrate them to systemd targets
# /usr/bin/systemd-sysv-convert --save zoneminder >/dev/null 2>&1 ||:

# Run these because the SysV package being removed won't do them
# /sbin/chkconfig --del zoneminder >/dev/null 2>&1 || :
# /bin/systemctl try-restart zoneminder.service >/dev/null 2>&1 || :


%files
%defattr(-,root,root,-)
%doc AUTHORS COPYING README.md distros/opensuse/README.OpenSuse
%docdir /opt/zoneminder/share/man
%config %attr(640,root,%{zmgid_final}) /etc/zm.conf
%config(noreplace) %attr(644,root,root) /etc/apache2/conf.d/zoneminder.conf
%config(noreplace) %attr(644,root,root) /etc/tmpfiles.d/zoneminder.conf
%config(noreplace) %attr(644,root,root) /etc/logrotate.d/zoneminder

%{_unitdir}/zoneminder.service

# zmfix removed from zoneminder 1.26.6
# %attr(4755,root,root) %{zm_rundir}/zmfix


%{zm_instdir}
%{webcgi}/nph-zms
%{webcgi}/zms
%{webroot}/zoneminder

%{_datadir}/polkit-1/actions/com.zoneminder.systemctl.policy
%{_datadir}/polkit-1/rules.d/com.zoneminder.systemctl.rules

%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{webroot}/zoneminder/events
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{webroot}/zoneminder/images
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{webroot}/zoneminder/temp
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{webcgi}
%dir %attr(755,%{zmuid_final},%{zmgid_final}) %{zm_tmpdir}
%dir %attr(755,%{zmuid_final},%{zmgid_final}) /var/log/zoneminder
%dir %attr(755,%{zmuid_final},%{zmgid_final}) /var/spool/zoneminder-upload


%changelog
* Wed Apr 02 2014 David Wilcox <david.wilcox@cloverbeen.com> - 1.27.0
- Correct requires for cpanm and make as they should be post
- change cpanm call to be full path name
- correct permissions on events, images and temp

* Mon Mar 24 2014 David Wilcox <david.wilcox@cloverbeen.com> - 1.27.0
- Update to zm 1.27.0
- Remove patch which brought opensuse into distros as it is now included

* Tue Mar 18 2014 David Wilcox <david.wilcox@cloverbeen.com> - 1.26.5
- Latest update for Opensuse 13.1 - work is still in progress

* Thu Feb 06 2014 David Wilcox <david.wilcox@cloverbeen.com> - 1.26.5
- Initial build for OpenSuse 13.1 - based on Fedora 19 build

* Mon Oct 07 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.26.4
- Initial cmake build.

* Sat Oct 05 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.26.4
- Fedora specific path changes have been moved to zoneminder-1.26.0-defaults.patch
- All files are now part of the zoneminder source tree. Update specfile accordingly.

* Sat Sep 21 2013 Andrew Bauer <knnniggett@users.sourceforge.net> - 1.26.3
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

* Wed Mar 23 2011 Dan HorÃƒÂ¡k <dan@danny.cz> - 1.24.3-3
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

* Sat Jan 24 2009 CaolÃƒÂ¡n McNamara <caolanm@redhat.com> - 1.23.3-3
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
