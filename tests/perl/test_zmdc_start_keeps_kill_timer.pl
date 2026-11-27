#!/usr/bin/perl
# A start sent while a daemon is still being stopped cancels the stop (the
# daemon is flagged to be restarted once it dies). The TERM has already been
# sent though, so the KILL_DELAY timer must keep running: a daemon that ignores
# TERM would otherwise never be killed or restarted.
#
# Extracts start() and check_for_processes_to_kill() from zmdc.pl.in and runs
# them against stubs. Starts no daemon and needs no root.
use strict;
use warnings;
use FindBin;

my $failures = 0;
sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) { print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name\n  got:  $got\n  want: $want\n"; }
}

open(my $fh, '<', "$FindBin::Bin/../../scripts/zmdc.pl.in") or die "Can't open zmdc.pl.in: $!";
my $src = do { local $/; <$fh> };
close($fh);
my ($start) = $src =~ /^(sub start \{.*?^\} # end sub start)$/ms or die 'start not found';
my ($kill) = $src =~ /^(sub check_for_processes_to_kill \{.*?^\} # end sub check_for_processes_to_kill)$/ms
  or die 'check_for_processes_to_kill not found';

my @killed;
BEGIN { *CORE::GLOBAL::kill = sub { push @killed, [@_]; 1 }; }

package ZoneMinder::Logger;
use constant { DEBUG=>1, INFO=>0, WARNING=>-1, ERROR=>-2, FATAL=>-3 };
package ZMServer;
use POSIX qw(strftime sigprocmask SIGCHLD SIGINT SIGTERM SIGABRT SIG_BLOCK SIG_SETMASK SIG_UNBLOCK);
use constant KILL_DELAY => 30;
our (%cmd_hash, %pid_hash, %terminating_processes, $dbh, %Config);
our $daemon_patt = '(zmc)';
sub dPrint {}
sub Debug {}
sub Warning {}
sub Error {}
sub Fatal { die @_ }
sub zmDbConnect {}
sub zmDbDisconnect {}
sub logReinit {}
sub logTerm {}
eval "$start\n$kill\n1" or die $@;

package main;
my $now = time();
my $process = { daemon=>'zmc', args=>['-m', '1'], command=>'zmc -m 1', keepalive=>0,
  pid=>4242, started=>$now-100, term_sent_at=>$now-5 };
$ZMServer::cmd_hash{'zmc -m 1'} = $process;
$ZMServer::pid_hash{4242} = $process;
$ZMServer::terminating_processes{'zmc -m 1'} = $process;

ZMServer::start('zmc', '-m', '1');
check('start flags the stopping daemon to be restarted', $process->{keepalive} ? 'keepalive' : 'none', 'keepalive');
check('the stopping daemon stays on the kill list', exists $ZMServer::terminating_processes{'zmc -m 1'} ? 'yes' : 'no', 'yes');

$process->{term_sent_at} = time() - ZMServer::KILL_DELAY - 5;
ZMServer::check_for_processes_to_kill();
check('a daemon that ignores TERM is sent KILL', join(',', map { "$_->[0] $_->[1]" } @killed), 'KILL 4242');

print $failures ? "\n$failures failed\n" : "\nall passed\n";
exit($failures ? 1 : 0);
