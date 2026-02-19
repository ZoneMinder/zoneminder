#
# spec file for package ZoneMinder
#
# Copyright (c) 2021 SUSE LLC
#
# All modifications and additions to the file contributed by third parties
# remain the property of their copyright owners, unless otherwise agreed
# upon. The license for this file, and modifications and additions to the
# file, is the same license as for the pristine package itself (unless the
# license for the pristine package is not an Open Source License, in which
# case the license is the MIT License). An "Open Source License" is a
# license that conforms to the Open Source Definition (Version 1.9)
# published by the Open Source Initiative.

# Please submit bugfixes or comments via https://bugs.opensuse.org/
#


%bcond_with mp_package
%bcond_without debug
%global _lto_cflags %{?_lto_cflags} -ffat-lto-objects
# Crud is configured as a git submodule
%global crud_commit 14292374ccf1328f2d5db20897bd06f99ba4d938
# CakePHP-Enum-Behavior is configured as a git submodule
%global cakephp_commit ea90c0cd7f6e24333a90885e563b5d30b793db29
# RtspServer is configured as a git submodule
%global rtspserver_commit 24e6b71
# CxxUrl is configured as a git submodule
%global cxxurl_commit eaf46c0


Version:        1.38.1+git5.3bb76ff0c
Release:        0
Summary:        A Linux based camera monitoring and analysis tool
License:        GPL-2.0-only
Group:          Productivity/Networking/Web/Servers
URL:            https://www.zoneminder.com/
Source0:        zoneminder-%{version}.tar.gz
Source1:        zm.service
Source2:        zm.logrotate
Source3:        README.SUSE
Source4:        zm.apache
Source5:        permissions
Source6:        permissions.secure
Source7:        permissions.paranoid
Source8:        MooTools-Core-1.6.0-compat.js
Source9:        LICENSE.mootools
Source10:       zm_tempfiles.conf
Source11:       zoneminder_mysql_settings.cnf
Source12:       crud-%{crud_commit}.tar.gz
Source13:       CakePHP-Enum-Behavior-%{cakephp_commit}.tar.gz
Source14:       RtspServer-%{rtspserver_commit}.tar.gz
Source15:       CxxUrl-%{cxxurl_commit}.tar.gz
Patch1:         zm.conf.patch
Patch2:         zm_database_default_config.patch
Patch3:         ignore_signal_on_config_write.patch
Patch4:         reload_config.patch
Patch5:         systemd-name-conventions.patch
BuildRequires:  -post-build-checks
BuildRequires:  -rpmlint-Factory
BuildRequires:  apache2
BuildRequires:  arp-scan
BuildRequires:  mod_php_any
BuildRequires:  cmake
BuildRequires:  fdupes
%if %{with mp_package}
BuildRequires:  ffmpeg-6-libavcodec-devel
BuildRequires:  ffmpeg-6-libavdevice-devel
BuildRequires:  ffmpeg-6-libavfilter-devel
BuildRequires:  ffmpeg-6-libavformat-devel
#BuildRequires:  ffmpeg-4-libavresample-devel
BuildRequires:  ffmpeg-6-libavutil-devel
BuildRequires:  ffmpeg-6-libpostproc-devel
BuildRequires:  ffmpeg-6-libswresample-devel
BuildRequires:  ffmpeg-6-libswscale-devel
%else
BuildRequires:  ffmpeg-4-libavcodec-devel
BuildRequires:  ffmpeg-4-libavdevice-devel
BuildRequires:  ffmpeg-4-libavfilter-devel
BuildRequires:  ffmpeg-4-libavformat-devel
BuildRequires:  ffmpeg-4-libavresample-devel
BuildRequires:  ffmpeg-4-libavutil-devel
BuildRequires:  ffmpeg-4-libpostproc-devel
BuildRequires:  ffmpeg-4-libswresample-devel
BuildRequires:  ffmpeg-4-libswscale-devel
%endif
#BuildRequires:  gcc
#BuildRequires:  gcc-c++
%if 0%{?suse_version} < 1600
BuildRequires:  gcc11
BuildRequires:  gcc11-c++
BuildRequires:  libstdc++6-devel-gcc11
%endif
%if 0%{?suse_version} >= 1600
BuildRequires:  gcc15
BuildRequires:  gcc15-c++
BuildRequires:  libstdc++6-devel-gcc15
%endif
BuildRequires:  gsoap-devel
BuildRequires:  libcurl-devel
BuildRequires:  libgcrypt-devel
BuildRequires:  libgnutls-devel
BuildRequires:  libjpeg-devel
BuildRequires:  libmysqlclient-devel
BuildRequires:  mosquitto-devel
BuildRequires:  mysql
BuildRequires:  nlohmann_json-devel
BuildRequires:  pam-config
BuildRequires:  pcre2-devel
BuildRequires:  perl
BuildRequires:  perl-Archive-Zip
BuildRequires:  perl-Crypt-SSLeay
BuildRequires:  perl-DBD-mysql
BuildRequires:  perl-DBI
BuildRequires:  perl-Date-Manip
BuildRequires:  perl-MIME-Lite
BuildRequires:  perl-MIME-tools
BuildRequires:  perl-Sys-Mmap
BuildRequires:  perl-XML-Parser
BuildRequires:  perl-libwww-perl
BuildRequires:  perl-macros
BuildRequires:  php-devel >= 7.4
BuildRequires:  pkgconfig
BuildRequires:  polkit-devel
BuildRequires:  systemd
BuildRequires:  config(udev)

BuildRequires:  unzip
BuildRequires:  update-desktop-files
BuildRequires:  vlc-devel
BuildRequires:  vorbis-tools
BuildRequires:  pkgconfig(libvncclient)

Requires:       apache2
Requires:       iproute2
Requires:       mod_php_any
Requires:       mysql
Requires:       netpbm
Requires:       perl-Archive-Tar
Requires:       perl-Archive-Zip
Requires:       perl-Class-Std-Fast
Requires:       perl-Cpanel-JSON-XS
Requires:       perl-Crypt-Eksblowfish
Requires:       perl-Crypt-SSLeay
Requires:       perl-DBD-mysql
Requires:       perl-DBI
Requires:       perl-Data-Entropy
Requires:       perl-Data-UUID
Requires:       perl-Date-Manip
Requires:       perl-IO-Socket-Multicast
Requires:       perl-JSON-MaybeXS
Requires:       perl-MIME-Lite
Requires:       perl-MIME-tools
Requires:       perl-Number-Bytes-Human
Requires:       perl-PHP-Serialization
Requires:       perl-SOAP-WSDL
Requires:       perl-Sys-CPU
Requires:       perl-Sys-MemInfo
Requires:       perl-Sys-Mmap
Requires:       perl-XML-Parser
Requires:       perl-libwww-perl
Requires:       php
Requires:       php-cli
Requires:       php-APCu
Requires:       php-bz2
Requires:       php-gd
Requires:       php-iconv
Requires:       php-intl
Requires:       php-mbstring
Requires:       php-mysql
Requires:       php-sockets
Requires:       php-zip
Requires:       php-zlib
Requires:       python3-zm_database_init >= 3.2.1
Requires:       zip
Requires(post): permissions
Requires(pre):  shadow
Recommends:     arp-scan
Recommends:     libgsoap-2_8_134
Recommends:     logrotate
Recommends:     mosquitto
Recommends:     net-tools-deprecated
Recommends:     perl-Device-SerialPort
Recommends:     perl-Expect
Recommends:     perl-Net-SFTP-Foreign
Recommends:     perl-SOAP-Lite
Recommends:     php-gd
Recommends:     php-sysvsem



%{perl_requires}
%{?systemd_requires}
%if %{with mp_package}
Name:           ZoneMinder-MP
%else
Name:           ZoneMinder
%endif
%if %{with mp_package}
BuildRequires:  faac
BuildRequires:  lame
BuildRequires:  libfaad-devel
BuildRequires:  libx264-devel
BuildRequires:  libxvidcore-devel
%endif
%if %{with mp_package}
Requires:       ffmpeg
Requires:       lame
%endif
%if %{with mp_package}
Conflicts:      ZoneMinder
%endif

%description
ZoneMinder is an integrated set of applications which provide a complete surveillance solution allowing capture,
analysis, recording and monitoring of any CCTV or security cameras attached to a Linux based machine.
It is designed to run on distributions which support the Video For Linux (V4L) interface and has been tested
with video cameras attached to BTTV cards, various USB cameras and also supports most IP network cameras.

%package        devel
Summary:        Development files for ZoneMinder
Group:          Development
Requires:       %{name} = %{version}

%description    devel
This package contains files used to develop ZoneMinder.

%prep

%if %{with mp_package}
%setup -q -n zoneminder-%{version}
%else
%setup -q -n zoneminder-%{version}
%endif
cp -a %{SOURCE3} .
cp -a %{SOURCE9} .
%patch -P1 -p1
%patch -P2 -p1
%patch -P3 -p1
%patch -P4 -p1
%patch -P5 -p1

# add ZM_VERSION to config file
cat >> zm.conf.in << EOF

# Current version of ZoneMinder
ZM_VERSION=%{version}

EOF

#install Crud
pushd web/api/app/Plugin/Crud/
tar xfvz %{SOURCE12} --strip 1
popd

#install enum behaviour
pushd web/api/app/Plugin/CakePHP-Enum-Behavior
tar xfvz %{SOURCE13} --strip 1
popd

#install enum RtspServer
pushd dep/RtspServer
tar xfvz %{SOURCE14} --strip 1
popd

#install enum CxxUrl
pushd dep/CxxUrl
tar xfvz %{SOURCE15} --strip 1
popd

%build
export CFLAGS="%{optflags}"
export CXXFLAGS="%{optflags}"

# FIXME: you should use the %%cmake macros
cmake \
  -DZM_DB_HOST=localhost \
  -DZM_DB_NAME=zm \
  -DZM_DB_USER=zm_admin \
  -DZM_CONFIG_DIR=%{_sysconfdir}/zoneminder \
  -DZM_RUNDIR=/run/zm \
  -DZM_TMPDIR=/tmp \
  -DZM_LOGDIR=%{_localstatedir}/log/zm \
  -DZM_MYSQL_ENGINE=InnoDB \
  -DZM_WEBDIR=%{_datadir}/zoneminder/www \
  -DZM_CGIDIR=%{_prefix}/lib/zoneminder/cgi-bin \
  -DZM_LOGDIR=%{_localstatedir}/log/zm \
  -DZM_RUNDIR=/run/zm \
  -DZM_SOCKDIR=/run/zm \
  -DZM_NO_FFMPEG=OFF \
  -DENABLE_MMAP=yes \
  -DZM_WEB_USER=wwwrun \
  -DZM_WEB_GROUP=www \
  -DCMAKE_INSTALL_PREFIX=%{_prefix} \
 %if 0%{?suse_version} < 1600
  -DCMAKE_C_COMPILER=gcc-11 \
  -DCMAKE_CXX_COMPILER=g++-11
 %endif
 %if 0%{?suse_version} >= 1600
  -DCMAKE_C_COMPILER=gcc-15 \
  -DCMAKE_CXX_COMPILER=g++-15
 %endif

%make_build

%install
%make_install

# rm -f %buildroot/srv/www/htdocs/zm/api/.editorconfig
# rm -f %buildroot/srv/www/htdocs/zm/api/.gitattributes
# rm -f %buildroot/srv/www/htdocs/zm/api/.gitignore
rm -rf %buildroot%{_datadir}/zoneminder/www/api/app/vendor/composer/installers/.gitignore

%perl_process_packlist
%perl_gen_filelist
install -m 700 -d %{buildroot}%{_datadir}/zoneminder/db
install -m 600 db/*.sql %{buildroot}%{_datadir}/zoneminder/db

for f in %{_sysconfdir}/logrotate.d %{_sysconfdir}/init.d %{_sbindir} %{_sysconfdir}/cron.hourly/ %{_localstatedir}/log %{_sysconfdir}/apache2/vhosts.d %{_sysconfdir}/permissions.d/ /run/zm %{_localstatedir}/cache/zoneminder %{_localstatedir}/lib/zoneminder/events %{_localstatedir}/lib/zoneminder/images
do
    install -d %{buildroot}/$f
done

for f in %{_unitdir} %{_prefix}/lib/tmpfiles.d
do
  install -d %{buildroot}/$f
done

install -m 644 %{SOURCE1} %{buildroot}/%{_unitdir}
install -m 644 %{SOURCE10} %{buildroot}%{_prefix}/lib/tmpfiles.d
install -d -m 750 %{buildroot}/%{_sysconfdir}/my.cnf.d/
install -m 640 %{SOURCE11} %{buildroot}/%{_sysconfdir}/my.cnf.d/zoneminder_mysql_settings.cnf

install -m 775 -d %{buildroot}%{_localstatedir}/log/zm
install -m 644 %{SOURCE2} %{buildroot}/%{_sysconfdir}/logrotate.d/zm
install -m 644 %{SOURCE4} %{buildroot}/%{_sysconfdir}/apache2/vhosts.d/zm.conf

install -m 644 %{SOURCE5} %{buildroot}%{_sysconfdir}/permissions.d/%{name}
install -m 644 %{SOURCE5} %{buildroot}%{_sysconfdir}/permissions.d/%{name}.easy

install -m 644 %{SOURCE6} %{buildroot}%{_sysconfdir}/permissions.d/%{name}
install -m 644 %{SOURCE7} %{buildroot}%{_sysconfdir}/permissions.d/%{name}

install -m 644 %{SOURCE8} %{buildroot}%{_datadir}/zoneminder/www/mootools.js

echo %{version} > %{buildroot}%{_datadir}/zoneminder/version


%fdupes -s %{buildroot}/%{_mandir}
%fdupes %{buildroot}

%files -f %{name}.files
%dir %{_datadir}/zoneminder
%dir %{_datadir}/zoneminder/db
%dir %{_prefix}/lib/zoneminder
%dir %{_prefix}/lib/zoneminder/cgi-bin
%dir %{_datadir}/zoneminder/fonts
%dir %{_datadir}/zoneminder/icons
%dir %{_localstatedir}/lib/zoneminder
%{_datadir}/zoneminder/fonts/default.zmfnt
%{_datadir}/zoneminder/version
%{_datadir}/zoneminder/icons/*
%{_datadir}/zoneminder/db/*.sql
%{_datadir}/zoneminder/MacVendors.json
%{_datadir}/polkit-1/actions/com.zoneminder.systemctl.policy
%{_datadir}/polkit-1/rules.d/com.zoneminder.systemctl.rules
%{_datadir}/polkit-1/actions/com.zoneminder.arp-scan.policy
%{_datadir}/polkit-1/rules.d/com.zoneminder.arp-scan.rules

#%{_prefix}/include/jwt-cpp/*
#%{_prefix}/include/picojson/*
#%{_prefix}/cmake/jwt-cpp-config-version.cmake
#%{_prefix}/cmake/jwt-cpp-config.cmake
#%{_prefix}/cmake/jwt-cpp-targets.cmake
#%{_includedir}/CxxUrl/*
#%{_libdir}/cmake/CxxUrl/*
#%{_libdir}/libCxxUrl.a


%{_sysconfdir}/apache2/vhosts.d/*.conf
%dir %{_datadir}/zoneminder/www/
%{_datadir}/zoneminder/www/*
%{_prefix}/lib/zoneminder/cgi-bin/*
%{_datadir}/applications/zoneminder.desktop

%{_unitdir}/*
%{_prefix}/lib/tmpfiles.d/*

%defattr(-,wwwrun,www)
%dir %{_localstatedir}/cache/zoneminder
%dir %{_localstatedir}/lib/zoneminder/events
%dir %{_localstatedir}/lib/zoneminder/images
%ghost /run/zm

%defattr(-,root,mysql)
%config(noreplace) %{_sysconfdir}/my.cnf.d/zoneminder_mysql_settings.cnf

%{_sysconfdir}/permissions.d/*
%{_sysconfdir}/logrotate.d/zm
%license LICENSE*
%doc README*
%defattr(640,root,www)
%config(noreplace) %{_sysconfdir}/zoneminder/zm.conf
%config(noreplace) %{_sysconfdir}/zoneminder/conf.d/*
%defattr(-,root,www)
%dir %{_localstatedir}/log/zm

%{perl_vendorlib}/WSDiscovery/
%{perl_vendorlib}/WSDiscovery10/
%{perl_vendorlib}/WSDiscovery11/
%{perl_vendorlib}/ONVIF/
%{perl_vendorlib}/WSNotification/
%{perl_vendorlib}/WSSecurity/
%{perl_vendorlib}/ZoneMinder/

%files devel
%{_prefix}/cmake/jwt-cpp-config-version.cmake
%{_prefix}/cmake/jwt-cpp-config.cmake
%{_prefix}/cmake/jwt-cpp-targets.cmake
%{_includedir}/CxxUrl/string.hpp
%{_includedir}/CxxUrl/url.hpp
%{_includedir}/jwt-cpp/base.h
%{_includedir}/jwt-cpp/jwt.h
%{_includedir}/jwt-cpp/traits/boost-json/*.h
%{_includedir}/jwt-cpp/traits/danielaparker-jsoncons/*.h
%{_includedir}/jwt-cpp/traits/defaults.h.mustache
%{_includedir}/jwt-cpp/traits/kazuho-picojson/*.h
%{_includedir}/jwt-cpp/traits/nlohmann-json/*.h
%{_includedir}/picojson/picojson.h
%{_libdir}/cmake/CxxUrl/CxxUrl*.cmake
%{_libdir}/libCxxUrl.a

%post
/sbin/ldconfig
touch %{_datadir}/zoneminder/lock || :
PHP_MAJOR=$(php -r "print PHP_MAJOR_VERSION;")
echo "o %{name}: Enable php${PHP_MAJOR} and rewrite in Webserver..." || :
if [ -x %{_sbindir}/a2enmod ]; then
  a2enmod php${PHP_MAJOR} || :
  a2enmod rewrite || :
fi

%service_add_post zm.service
systemd-tmpfiles --create zm_tempfiles.conf || :

%posttrans
test -f %{_sysconfdir}/zm.conf.rpmsave && mv -v %{_sysconfdir}/zm.conf.rpmsave %{_sysconfdir}/zoneminder/zm.conf && touch %{_datadir}/zoneminder/lock ||:

%pre
%{_bindir}/gpasswd -a wwwrun video >/dev/null 2>&1 || :
%service_add_pre zm.service
test -f %{_sysconfdir}/zm.conf.rpmsave && mv -v %{_sysconfdir}/zm.conf.rpmsave %{_sysconfdir}/zm.conf.rpmsave.old ||:

%preun
%stop_on_removal zm
if [ $1 -eq 0 ]; then
    if test -f %{_datadir}/zoneminder/lock ; then
        rm -f %{_datadir}/zoneminder/lock
    fi
fi

%service_del_preun zm.service

%postun
%insserv_cleanup
/sbin/ldconfig

%service_del_postun zm.service

%if %{with mp_package}
%changelog -n ZoneMinder-MP
%else
%changelog -n ZoneMinder
%endif

%changelog
