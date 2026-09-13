macOS
=====

.. contents::

What this covers
----------------

ZoneMinder builds, installs and runs on macOS from source. There is no package,
so everything here is manual.

These steps have been run through on Apple Silicon to a working system: capture
from a network source, motion detection, events recorded to disk, and the web
interface serving its console. Treat it as a development and evaluation
platform rather than something to put cameras behind — see the gaps at the end.

Two prefixes are in play and it is worth keeping them apart. Homebrew's prefix
is where the dependencies come from — ``/opt/homebrew`` on Apple Silicon,
``/usr/local`` on Intel — and the commands below write it as ``$(brew --prefix)``
so they work on either. ZoneMinder's own install prefix is separate and defaults
to ``/usr/local`` on both, which is what the paths in this guide assume. Pass
``-DCMAKE_INSTALL_PREFIX=`` at configure time to put it somewhere else, and
adjust the paths here to match.

Local USB cameras are not supported. V4L2 is a Linux interface and has no macOS
equivalent in ZoneMinder, so configure reports ``Could NOT find V4L2`` and that
is expected. Network cameras — RTSP, HTTP, ONVIF — work normally, and that is
what ZoneMinder is mostly used with anyway.

Prerequisites
-------------

Xcode command line tools and Homebrew:

::

    xcode-select --install
    /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

Dependencies
------------

To build:

::

    brew install \
      catch2 ffmpeg gsoap jpeg-turbo mosquitto mysql-client \
      nlohmann-json openssl@3 pcre2

``cmake`` and ``pkg-config`` come with the command line tools, and ``curl`` comes
from the SDK, so none of those need installing.

To run, you also need a database, PHP and a web server. macOS still ships Apache
at ``/usr/sbin/httpd``, but it has not shipped PHP since Monterey, so PHP comes
from Homebrew either way:

::

    brew install mariadb php

Perl modules
------------

Some modules ZoneMinder needs are not present in either Perl, and configure only
*warns* about them rather than failing, so it is easy to end up with a clean
build and a system whose Perl daemons do not work.

- ``Sys::Mmap`` backs ``ZoneMinder::Memory::Mapped``. Every Perl daemon reads
  monitor state through it, so without it ``zmdc.pl``, ``zmwatch.pl`` and
  ``zmpkg.pl`` cannot see your monitors at all.
- ``Date::Manip`` is used by ``zmfilter.pl``, ``Event.pm`` and ``Filter.pm``.
- A database driver. ZoneMinder prefers ``DBD::MariaDB``, which drives both
  MariaDB and MySQL.

.. note::
   ``-DZM_NO_MMAP=ON`` is not a way around ``Sys::Mmap``. It switches ZoneMinder
   to SysV shared memory, and macOS caps that at 4 MiB per segment
   (``kern.sysv.shmmax``) across at most 8 segments. A single 1080p RGB frame is
   larger than one segment. Memory mapping is the only workable mode here.

Using a Homebrew Perl
~~~~~~~~~~~~~~~~~~~~~

Recommended. Apple has deprecated the Perl it bundles and it will eventually be
removed, and this keeps ZoneMinder off the system directories entirely.

::

    brew install perl cpanminus
    cpanm --notest Sys::Mmap Date::Manip DBI LWP::UserAgent

A Homebrew Perl has none of the extras Apple bundles, so it needs ``DBI`` and
``LWP::UserAgent`` as well.

``DBD::MariaDB`` needs help. Its configure step asks ``mysql_config`` for link
flags and gets ``-lzstd -lssl -lcrypto`` back, but a Homebrew Perl's ``ldflags``
carry no ``-L$(brew --prefix)/lib``, so the check fails with ``Can't
link/include C library 'zstd', 'ssl', 'crypto', aborting``. Pass the paths in:

::

    export PATH="$(brew --prefix mysql-client)/bin:$PATH"
    cpanm --notest \
      --configure-args="--libs=\"-L$(brew --prefix mysql-client)/lib -L$(brew --prefix)/lib -lmysqlclient -lz -lzstd -lssl -lcrypto -lresolv\" --cflags=\"-I$(brew --prefix mysql-client)/include/mysql\"" \
      DBD::MariaDB

Then configure ZoneMinder against that Perl:

::

    -DPERL_EXECUTABLE=$(brew --prefix)/bin/perl

ZoneMinder's own modules install to that Perl's ``vendorlib``, under
``$(brew --prefix)/lib/perl5/vendor_perl/<version>``, which sits outside the
Cellar and survives Perl upgrades. The CPAN modules above do not: ``cpanm``
writes them to ``sitelib``, which resolves into the versioned Cellar directory
and is erased whenever Homebrew upgrades Perl. Reinstall them afterwards, or set
up ``local::lib`` as ``brew info perl`` describes.

Using the system Perl
~~~~~~~~~~~~~~~~~~~~~

Fewer modules are needed, because Apple bundles ``DBI`` and ``LWP::UserAgent``:

::

    sudo cpan Sys::Mmap Date::Manip

You still need a database driver, and it needs the same link flags as above.
Modules install to ``/Library/Perl/<version>``, which needs ``sudo``.

Build
-----

::

    cmake -S . -B build \
      -DCMAKE_BUILD_TYPE=Release \
      -DBUILD_TEST_SUITE=ON \
      -DCMAKE_PREFIX_PATH="$(brew --prefix mysql-client);$(brew --prefix)" \
      -DOPENSSL_ROOT_DIR="$(brew --prefix openssl@3)" \
      -DCMAKE_C_FLAGS="-I$(brew --prefix mysql-client)/include" \
      -DCMAKE_CXX_FLAGS="-I$(brew --prefix mysql-client)/include"
    cmake --build build -j"$(sysctl -n hw.ncpu)"

The ``mysql-client`` flags are not optional. Homebrew keeps that formula
keg-only, so it is not on the default include path and the bare ``find_library``
call in ``CMakeLists.txt`` will not find its headers. Leave them out and the
build stops at ``'mysql/mysql.h' file not found``.

To check the build, from the ``tests`` directory — the font tests load fixtures
by relative path, so the working directory matters:

::

    cd build/tests && ./tests "~[notCI]"

Install
-------

::

    sudo cmake --install build

With the default prefix this puts binaries in ``/usr/local/bin``, the web files
in ``/usr/local/share/zoneminder/www``, CGI in
``/usr/local/libexec/zoneminder/cgi-bin``, configuration in
``/usr/local/etc/zm`` and the Perl modules in ``/Library/Perl/<version>``.

Create the runtime directories
------------------------------

Nothing creates these for you. On Linux the distribution package does it; here
you do it yourself, once:

::

    sudo mkdir -p /usr/local/var/run/zm \
                  /usr/local/var/log/zm \
                  /usr/local/var/cache/zoneminder/temp \
                  /usr/local/var/lib/zoneminder/events
    sudo chown -R _www:_www /usr/local/var/run/zm \
                            /usr/local/var/log/zm \
                            /usr/local/var/cache/zoneminder \
                            /usr/local/var/lib/zoneminder

``_www`` is the account macOS runs its web server as, and is what configure
detects.

Whatever account you choose, **ZoneMinder has to be started as that account**.
``zmpkg.pl`` compares the current user against ``ZM_WEB_USER`` and, when they
differ, tries ``sudo -u``, then two forms of ``su``, to become it. Run it as
yourself against a configured user of ``_www`` and all three fail — ``su`` needs
root and ``sudo`` wants a password — and it stops with ``Unable to find valid su
syntax``. On Linux systemd sidesteps this by starting the unit as the web user;
the launchd job below does the same.

So either start it under launchd, or run everything as one account. For a
workstation install the simpler option is to set ``ZM_WEB_USER`` and
``ZM_WEB_GROUP`` in ``/usr/local/etc/zm/zm.conf`` to your own account and group,
point a web server that runs as you at it, and own the directories above
yourself. ``zms`` reads the mapped memory that ``zmc`` writes, so the capture
daemons and the web server must agree on the account either way. Mapped memory files live in ``/usr/local/var/run/zm`` rather than
``/dev/shm``, which macOS does not have. That directory is on disk, not a RAM
filesystem — macOS mounts no tmpfs — so expect more disk traffic than the same
setup on Linux.

Database
--------

A Homebrew MariaDB authenticates ``root`` with the ``unix_socket`` plugin, so
``mysql -u root`` is refused — only the operating system's root user matches it.
Your own account gets an administrative account instead, which is what ``mariadb``
with no ``-u`` uses:

::

    brew services start mariadb
    mariadb < /usr/local/share/zoneminder/db/zm_create.sql
    mariadb -e "CREATE USER IF NOT EXISTS 'zmuser'@localhost IDENTIFIED BY 'zmpass';"
    mariadb -e "GRANT LOCK TABLES, ALTER, SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX ON zm.* TO 'zmuser'@localhost;"
    zmupdate.pl --nointeractive

Change the user and password from the defaults, in the grant above and in
``/usr/local/etc/zm/zm.conf``, before putting this anywhere reachable.

.. note::
   ``zmupdate.pl`` does not use ``DBI`` for schema changes; it shells out to a
   client binary, picking ``mariadb`` over ``mysql`` when one is on ``PATH``.
   The Perl scripts run under ``-T`` and set a taint-safe
   ``PATH`` of ``/bin:/usr/bin:/usr/local/bin``, so on Apple Silicon, where
   Homebrew is ``/opt/homebrew``, neither client is found and the upgrade fails
   with ``sh: mysql: command not found``. Until that path is configurable, add
   the Homebrew prefix to the ``$ENV{PATH}`` line near the top of
   ``zmupdate.pl``, or symlink ``mariadb`` into ``/usr/local/bin``.

Web server
----------

The build generates a starting point for both Apache and nginx at
``build/misc/apache.conf`` and ``build/misc/nginx.conf``, with your configured
paths already substituted. They are guidance, not drop-in configuration —
neither is installed, and both need adapting to how your web server is set up.

Whichever you choose, it needs PHP, CGI enabled for ``nph-zms``, and rewrite
rules for the API. The sample files show all three.

The CGI part decides which server is less work. ``zms`` is a CGI binary, and
Apache runs those directly with ``mod_cgi``; nginx cannot, and needs a wrapper
such as ``fcgiwrap``, which Homebrew does not package. With nginx and no wrapper
the interface loads and the console works, but live streams and event playback
do not.

The samples are also written for the Linux layout and need editing here. The
nginx one expects certificates under ``/etc/pki``, ``fastcgi_params`` under
``/etc/nginx`` and an ``fcgiwrap`` socket at ``/run/fcgiwrap.sock``, none of
which exist on macOS.

Starting ZoneMinder
-------------------

``zmpkg.pl`` falls back to ``zmdc.pl`` when systemd is absent, so you can start
and stop ZoneMinder by hand:

::

    sudo zmpkg.pl start
    sudo zmpkg.pl status
    sudo zmpkg.pl stop

To start it at boot, the build generates a launchd job at
``build/misc/com.zoneminder.zoneminder.plist`` with your paths and web user
already filled in. Copy it into place and load it:

::

    sudo install -o root -g wheel -m 644 \
      build/misc/com.zoneminder.zoneminder.plist /Library/LaunchDaemons/
    sudo launchctl load -w /Library/LaunchDaemons/com.zoneminder.zoneminder.plist

The job starts ZoneMinder; it does not supervise it. ``zmpkg.pl`` forks
``zmdc.pl`` and returns — the same shape systemd calls ``Type=forking`` — and
from there ``zmdc.pl`` and ``zmwatch.pl`` restart the capture and analysis
daemons themselves. launchd's job is to run that once at boot.

Unloading does not stop ZoneMinder, because launchd has no equivalent of
``ExecStop``. Stop it first:

::

    sudo zmpkg.pl stop
    sudo launchctl unload -w /Library/LaunchDaemons/com.zoneminder.zoneminder.plist

There is also no equivalent of the systemd unit's ``After=`` and ``Requires=``.
launchd only orders jobs it manages itself, and a Homebrew MariaDB is not one of
them, so at boot ZoneMinder may start before the database is listening.
``zmdc.pl`` retries, but ``zmdc.log`` is the place to look if monitors come up
unexpectedly idle.

Known gaps
----------

- No Homebrew formula, so no ``brew services`` integration; the launchd job
  above is loaded by hand.
- Installation is from source only.
- No log rotation. ``misc/logrotate.conf`` is written for logrotate, which macOS
  does not use — it uses ``newsyslog``, and no configuration is provided.
- No local camera support, as described at the top.
- ``libunwind``, ``libVLC`` and ``libVNC`` are not found by default. All three
  are optional; the first only affects backtrace detail in crash logs.
- Configure warns that it cannot find ``arp-scan`` and ``ip``. Neither is fatal:
  ``brew install arp-scan`` covers the first, and ``ip`` is a Linux tool that
  macOS has no equivalent of, so monitor probing is a little less capable.
- The taint-safe ``PATH`` the Perl scripts set is hardcoded to
  ``/bin:/usr/bin:/usr/local/bin`` in seventeen files, so anything they shell out
  to has to be in one of those. Apple Silicon's Homebrew prefix is not, which is
  what breaks ``zmupdate.pl`` above. The same would affect a ``--prefix=/opt``
  install on Linux.
- ``Sys::CPU``, which ``zmtelemetry.pl`` uses, has been removed from CPAN and
  cannot be installed at all. Telemetry is the only thing affected.
