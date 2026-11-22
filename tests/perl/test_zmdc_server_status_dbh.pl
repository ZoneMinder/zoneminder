#!/usr/bin/perl
# With ZM_SERVER_ID set, the zmdc server writes its status to the Servers row
# every 60 passes of its loop. Starting a daemon reconnects to the database
# (start() drops the connection before it forks), so the status update has to
# use that new connection rather than one it kept from before. With a handle
# that stays dead once disconnected (DBD::MariaDB) the next status update warns
# "Not connected to db"; with one that ping() reconnects (DBD::mysql) the server
# quietly keeps a second connection.
#
# Runs zmdc.pl from the template with stub ZoneMinder modules, once for each
# kind of handle.
use strict;
use warnings;
use FindBin;
use Cwd qw(abs_path);
use File::Temp qw(tempdir);
use File::Path qw(make_path);
use Time::HiRes qw(time sleep);

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  $got\n  want: $want\n";
  }
}

sub write_file {
  my ($path, $content, $mode) = @_;
  open(my $fh, '>', $path) or die "Can't write $path: $!";
  print $fh $content;
  close($fh);
  chmod($mode, $path) if $mode;
}

sub read_file {
  open(my $fh, '<', $_[0]) or return '';
  local $/;
  my $content = <$fh>;
  return defined($content) ? $content : '';
}

my $dir = tempdir(CLEANUP => 1);
($dir) = abs_path($dir) =~ /^(.*)$/;
make_path("$dir/lib/ZoneMinder", "$dir/lib/Sys", "$dir/bin", "$dir/path", "$dir/socks");

write_file("$dir/lib/ZoneMinder.pm", <<"EOM");
package ZoneMinder::Base;
use constant ZM_VERSION => '0';
package ZoneMinder::Logger;
use constant { DEBUG=>1, INFO=>0, WARNING=>-1, ERROR=>-2, FATAL=>-3 };
sub rec { open(my \$f, '>>', '$dir/log.txt') or return; print \$f join(' ', \@_), "\\n"; close(\$f); }
package ZoneMinder::Database;
our \$dbh;
sub start_transaction { return 1; }
sub end_transaction { return 1; }
package StubDbh;
sub ping {
  return 1 if \$_[0]{connected};
  return 0 if !\$ENV{ZMDC_TEST_REVIVE};
  \$_[0]{connected} = 1;
  ZoneMinder::Logger::rec('REVIVED');
  return 1;
}
sub disconnect { \$_[0]{connected} = 0; return 1; }
sub errstr { return ''; }
package ZoneMinder::Server;
sub new { return bless({Id=>1}, \$_[0]); }
sub Id { return \$_[0]{Id}; }
sub CpuLoad { ZoneMinder::Logger::rec('STATUS'); return (0.1, 0.1, 0.1); }
sub CpuUsage { return (1, 1, 1, 97, 3); }
sub lock_and_load { return 1; }
sub save { return ''; }
BEGIN { \$INC{'ZoneMinder/Server.pm'} = 1; }
package ZoneMinder;
our %Config = (ZM_PATH_SOCKS => '$dir/socks', ZM_PATH_BIN => '$dir/bin', ZM_SERVER_ID => 1, ZM_MAX_RESTART_DELAY => 600);
sub import {
  no strict 'refs';
  my \$caller = caller();
  *{\$caller.'::Config'} = \\%Config;
  *{\$caller.'::ZM_PID'} = sub () { '$dir/zm.pid' };
  *{\$caller.'::'.\$_} = sub { 1 } for qw(Debug Info Error logInit logReinit logTerm);
  *{\$caller.'::Warning'} = sub { ZoneMinder::Logger::rec('WARNING', \@_) };
  *{\$caller.'::Fatal'} = sub { exit(1) };
  *{\$caller.'::zmDbDisconnect'} = sub {
    \$ZoneMinder::Database::dbh->disconnect() if \$ZoneMinder::Database::dbh;
    \$ZoneMinder::Database::dbh = undef;
  };
  *{\$caller.'::zmDbConnect'} = sub {
    &{\$caller.'::zmDbDisconnect'}() if \$_[0];
    if (!\$ZoneMinder::Database::dbh or !\$ZoneMinder::Database::dbh->ping()) {
      \$ZoneMinder::Database::dbh = bless({connected=>1}, 'StubDbh');
    }
    return \$ZoneMinder::Database::dbh;
  };
}
1;
EOM
write_file("$dir/lib/Sys/MemInfo.pm", <<'EOM');
package Sys::MemInfo;
use Exporter 'import';
our @EXPORT_OK = qw(totalmem freemem totalswap freeswap);
sub totalmem { 1 } sub freemem { 1 } sub totalswap { 1 } sub freeswap { 1 }
1;
EOM
# zmdc kills every ZoneMinder daemon by name when its server starts; keep that
# away from any real ones.
write_file("$dir/path/$_", "#!/bin/sh\nexit 0\n", 0755) for qw(killall pkill);
write_file("$dir/bin/zmwatch.pl", "#!/bin/sh\nPATH=/usr/bin:/bin\ntouch $dir/started\nexec sleep 60\n", 0755);

my $src = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmdc.pl.in") or die "Can't open zmdc.pl.in: $!";
  local $/;
  <$fh>;
};
$src =~ s/\@PERL_EXECUTABLE\@/$^X/g;
$src =~ s/\@EXTRA_PERL_LIB\@//g;
$src =~ s/\@ZM_SCRIPT_PATH\@/$dir\/path/g;
$src =~ s/\@HOST_OS\@/Linux/g;
write_file("$dir/zmdc.pl", $src);

sub zmdc {
  my $out = qx($^X -T -I$dir/lib $dir/zmdc.pl @_ 2>&1);
  return defined($out) ? $out : '';
}

foreach my $case (['a handle that stays dead', 0], ['a handle that ping() reconnects', 1]) {
  my ($name, $revive) = @$case;
  local $ENV{ZMDC_TEST_REVIVE} = $revive;
  unlink("$dir/log.txt", "$dir/started");

  zmdc('startup');
  zmdc('start', 'zmwatch.pl');
  for (1 .. 300) { last if -e "$dir/started"; sleep(0.1); }

  # Each request is a pass of the server's loop; run passes until the next
  # status update has been written.
  my $updates = () = read_file("$dir/log.txt") =~ /^STATUS/mg;
  for (1 .. 70) {
    zmdc('check');
    last if (() = read_file("$dir/log.txt") =~ /^STATUS/mg) > $updates;
  }
  my $alive = zmdc('check') =~ /running/ ? 'running' : 'stopped';
  zmdc('shutdown');
  for (1 .. 100) { last if !-e "$dir/zm.pid"; sleep(0.1); }

  my $log = read_file("$dir/log.txt");
  check("$name: the server is still running", $alive, 'running');
  check("$name: a status update ran after the daemon started", ((() = $log =~ /^STATUS/mg) > $updates) ? 'ran' : 'did not run', 'ran');
  check("$name: no reconnect warning", $log =~ /^WARNING (Not connected to db.*)$/m ? $1 : 'none', 'none');
  check("$name: the old handle is not used again", $log =~ /^REVIVED/m ? 'revived' : 'not used', 'not used');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
