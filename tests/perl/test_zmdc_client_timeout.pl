#!/usr/bin/perl
# zmdc.pl's client gives up when the server does not answer in time. That has
# to be a failure, or zmpkg.pl and the API's daemonControl take a command that
# never ran for a success. Lines the server sent before it went quiet must
# still be printed: select() does not see what is already in a PerlIO buffer.
#
# Runs the zmdc.pl client from the template with stub ZoneMinder modules
# against a fake server that sends two lines in one write and then hangs.
use strict;
use warnings;
use FindBin;
use Cwd qw(abs_path);
use File::Temp qw(tempdir);
use File::Path qw(make_path);
use Socket;

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
  my ($path, $content) = @_;
  open(my $fh, '>', $path) or die "Can't write $path: $!";
  print $fh $content;
  close($fh);
}

my $dir = tempdir(CLEANUP => 1);
($dir) = abs_path($dir) =~ /^(.*)$/;
make_path("$dir/lib/ZoneMinder", "$dir/lib/Sys", "$dir/socks");

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
our %Config = (ZM_PATH_SOCKS => '$dir/socks', ZM_PATH_BIN => '$dir', ZM_SERVER_ID => '', ZM_MAX_RESTART_DELAY => 600);
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

my $src = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmdc.pl.in") or die "Can't open zmdc.pl.in: $!";
  local $/;
  <$fh>;
};
$src =~ s/\@PERL_EXECUTABLE\@/$^X/g;
$src =~ s/\@EXTRA_PERL_LIB\@//g;
$src =~ s/\@ZM_SCRIPT_PATH\@/$dir/g;
$src =~ s/\@HOST_OS\@/Linux/g;
# Wait 2 seconds instead of 30.
$src =~ s/(my \$client_timeout = .*?: )30;/${1}2;/ or die 'client timeout not found';
write_file("$dir/zmdc.pl", $src);

socket(my $server, PF_UNIX, SOCK_STREAM, 0) or die "socket: $!";
bind($server, sockaddr_un("$dir/socks/zmdc.sock")) or die "bind: $!";
listen($server, 5) or die "listen: $!";
my $pid = fork();
die "fork: $!" if !defined $pid;
if (!$pid) {
  accept(my $client, $server) or exit(1);
  syswrite($client, "first line\nsecond line\n");
  sleep(10);
  exit(0);
}

my $out = qx($^X -T -I$dir/lib $dir/zmdc.pl status 2>&1);
my $status = $? >> 8;
kill('KILL', $pid);
waitpid($pid, 0);

check('client fails when the server does not answer', $status ? 'failed' : 'exit 0', 'failed');
check('client prints both lines the server sent', ($out =~ /^first line$/m && $out =~ /^second line$/m) ? 'both' : $out, 'both');
check('client says it timed out', $out =~ /Timed out/ ? 'yes' : 'no', 'yes');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
