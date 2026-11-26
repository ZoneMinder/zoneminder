#!/usr/bin/perl
# zmdc.pl shutdown answers once every daemon has exited or been sent KILL, and a
# daemon that ignores TERM is only sent KILL after KILL_DELAY seconds. The client has to wait
# that long: zmpkg.pl restart runs zmdc.pl check after the shutdown
# and only starts ZoneMinder again when it says stopped.
#
# Runs zmdc.pl from the template with stub ZoneMinder modules and a stub
# zmwatch.pl that ignores TERM. Takes about 35 seconds.
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

my $dir = tempdir(CLEANUP => 1);
($dir) = abs_path($dir) =~ /^(.*)$/;
make_path("$dir/lib/ZoneMinder", "$dir/lib/Sys", "$dir/bin", "$dir/path", "$dir/socks");

write_file("$dir/lib/ZoneMinder.pm", <<"EOM");
package ZoneMinder::Base;
use constant ZM_VERSION => '0';
package ZoneMinder::Logger;
use constant { DEBUG=>1, INFO=>0, WARNING=>-1, ERROR=>-2, FATAL=>-3 };
package ZoneMinder::Server;
sub new { return bless({}, \$_[0]); }
sub Id { return undef; }
BEGIN { \$INC{'ZoneMinder/Server.pm'} = 1; }
package ZoneMinder;
our %Config = (ZM_PATH_SOCKS => '$dir/socks', ZM_PATH_BIN => '$dir/bin', ZM_SERVER_ID => '', ZM_MAX_RESTART_DELAY => 600);
sub import {
  no strict 'refs';
  my \$caller = caller();
  *{\$caller.'::Config'} = \\%Config;
  *{\$caller.'::ZM_PID'} = sub () { '$dir/zm.pid' };
  *{\$caller.'::'.\$_} = sub { 1 } for qw(Debug Info Warning Error logInit logReinit logTerm zmDbConnect zmDbDisconnect);
  *{\$caller.'::Fatal'} = sub { exit(1) };
}
1;
EOM
write_file("$dir/lib/Sys/MemInfo.pm", "package Sys::MemInfo;\nuse Exporter 'import';\nour \@EXPORT_OK = qw(totalmem freemem totalswap freeswap);\n1;\n");
# zmdc kills every ZoneMinder daemon by name when its server starts; keep that
# away from any real ones.
write_file("$dir/path/$_", "#!/bin/sh\nexit 0\n", 0755) for qw(killall pkill);
write_file("$dir/bin/zmwatch.pl", "#!/bin/sh\nPATH=/usr/bin:/bin\ntrap '' TERM\ntouch $dir/started\nexec sleep 90\n", 0755);

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

zmdc('startup');
zmdc('start', 'zmwatch.pl');
# Wait until the stub is running and ignoring TERM
for (1 .. 300) { last if -e "$dir/started"; sleep(0.1); }
check('stub zmwatch.pl is running', -e "$dir/started" ? 'yes' : 'no', 'yes');
my $started = time();
my $out = zmdc('shutdown');
my $took = time() - $started;
my $status = zmdc('check');
chomp($status);

check('shutdown reports the server shutting down', $out =~ /Server shutdown at/ ? 'reported' : 'nothing', 'reported');
check('ZoneMinder is stopped once shutdown returns', $status, 'stopped');
print "  (shutdown took ".sprintf('%.1f', $took)." s)\n";

# On failure the server is still waiting to kill the stub; let it finish first
# so it does not outlive the temp dir.
sleep(1) while -e "$dir/zm.pid" and time() - $started < 45;

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
