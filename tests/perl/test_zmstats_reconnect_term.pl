#!/usr/bin/perl
use strict;
use warnings;
use FindBin;
use File::Temp qw(tempdir);
use File::Path qw(make_path);
use POSIX qw(WNOHANG);

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
make_path("$dir/lib/ZoneMinder", "$dir/lib/Sys");

# The first connection works but is dead on ping; every later attempt fails,
# like a database that went away after startup.
write_file("$dir/lib/ZoneMinder.pm", <<'EOM');
package ZoneMinder;
use ZoneMinder::Logger;
my $connects = 0;
sub import {
  no strict 'refs';
  my $caller = caller();
  *{$caller.'::'.$_} = sub { 1 } for qw(logInit logSetSignal Debug Info Warning Error Fatal);
  *{$caller.'::startedInteractively'} = sub { 1 };
  *{$caller.'::zmDbDo'} = sub { 0 };
  *{$caller.'::zmDbConnect'} = sub { return $connects++ ? undef : bless {}, 'StubDbh'; };
  *{$caller.'::Config'} = \%ZoneMinder::Config;
}
%ZoneMinder::Config = (ZM_STATS_UPDATE_INTERVAL => 1, ZM_SERVER_ID => 0);
package StubDbh;
sub do { 1 }
sub ping { 0 }
1;
EOM
write_file("$dir/lib/ZoneMinder/Logger.pm", "package ZoneMinder::Logger;\nuse constant AUDIT => 5;\n1;\n");
write_file("$dir/lib/DBI.pm", "package DBI;\n1;\n");
write_file("$dir/lib/Sys/MemInfo.pm", "package Sys::MemInfo;\nsub import {}\n1;\n");
write_file("$dir/lib/ZoneMinder/Server.pm", <<'EOM');
package ZoneMinder::Server;
sub new { bless {}, shift }
1;
EOM

my $source = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmstats.pl.in") or die "Can't open zmstats.pl.in: $!";
  local $/;
  <$fh>;
};
$source =~ s/\@PERL_EXECUTABLE\@/$^X/g;
$source =~ s/\@EXTRA_PERL_LIB\@//g;
$source =~ s/\@ZM_SCRIPT_PATH\@/\/usr\/bin:\/bin/g;
write_file("$dir/zmstats.pl", $source);

my $pid = fork();
die "fork: $!" if !defined $pid;
if (!$pid) {
  open(STDOUT, '>', '/dev/null');
  open(STDERR, '>', '/dev/null');
  exec($^X, '-T', "-I$dir/lib", "$dir/zmstats.pl");
  exit(127);
}

sleep(2);
kill('TERM', $pid);
my $exited = 0;
for (1 .. 40) {
  if (waitpid($pid, WNOHANG) == $pid) { $exited = 1; last; }
  select(undef, undef, undef, 0.1);
}
if (!$exited) {
  kill('KILL', $pid);
  waitpid($pid, 0);
}
check('SIGTERM stops the daemon while the database is unreachable', $exited ? 'exited' : 'still running', 'exited');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
