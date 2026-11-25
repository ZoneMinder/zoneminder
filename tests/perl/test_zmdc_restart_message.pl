#!/usr/bin/perl
# When a keepalive daemon exits abnormally zmdc's reaper schedules a restart.
# The log line must say what the restart delay is and how many failures in a
# row have happened, so a crash loop is visible to the user.
use strict;
use warnings;
use FindBin;

my $path = "$FindBin::Bin/../../scripts/zmdc.pl.in";
open(my $fh, '<', $path) or die "Cannot read $path: $!";
my $src = do { local $/; <$fh> };
close($fh);
$src =~ /^(sub reaper \{.*?^\} # end sub reaper)/ms or die "reaper not found in $path";
my $code = $1;

my ($info, $error) = ('', '');
sub ZoneMinder::Logger::INFO () { 1 }
sub ZoneMinder::Logger::DEBUG () { 0 }
{
  package ZMServer;
  our (%pids_to_reap, %pid_hash, %terminating_processes, %cmd_hash, %Config);
  $Config{ZM_MAX_RESTART_DELAY} = 600;
  sub Info { $info .= "@_" }
  sub Error { $error .= "@_" }
  sub dPrint {}
  eval $code;
  die $@ if $@;
}

my $failures = 0;
sub check { my ($n, $ok) = @_; print(($ok ? 'ok' : 'FAIL')." - $n\n"); $failures++ if !$ok; }

# Reap one exit, keeping the process record in cmd_hash so delay/failure state
# carries over to the next exit exactly as it does across real restarts.
sub reap {
  my $status = shift;
  ($info, $error) = ('', '');
  my $p = $ZMServer::cmd_hash{'zmc -m 1'};
  if ( !$p ) {
    $p = { command=>'zmc -m 1', daemon=>'zmc', keepalive=>1 };
  }
  $p->{pid} = 42;
  $p->{started} = time()-10;
  $ZMServer::pid_hash{42} = $p;
  $ZMServer::pids_to_reap{42} = { status=>$status, stopped=>time() };
  ZMServer::reaper();
  return $p;
}

my $p = reap(1<<8);
check('first abnormal exit says it restarts now', $error =~ /exit status 1.*restarting now/);
check('first abnormal exit logs failure 1 in a row', $error =~ /failure 1 in a row/);

$p = reap(1<<8);
check('second abnormal exit waits the first delay', $error =~ /restarting in 5s/);
check('second abnormal exit counts failure 2 in a row', $error =~ /failure 2 in a row/);

# A fresh delay starts a new failure streak.
$p = { command=>'zmc -m 1', daemon=>'zmc', keepalive=>1, delay=>0, failures=>5 };
%ZMServer::cmd_hash = ('zmc -m 1' => $p);
%ZMServer::pid_hash = (); %ZMServer::pids_to_reap = ();
$info = $error = '';
reap(1<<8);
check('a fresh delay restarts the failure count', $error =~ /failure 1 in a row/);

# Clean exit must not advertise a restart, even though keepalive restarts it.
%ZMServer::cmd_hash = (); %ZMServer::pid_hash = ();
%ZMServer::pids_to_reap = ();
$info = $error = '';
reap(0);
check('clean exit logs normally', $info =~ /exited normally/);
check('clean exit logs no restart text', $info !~ /restart/i && $error eq '');

# The delay is capped, and the message says so.
$ZMServer::Config{ZM_MAX_RESTART_DELAY} = 20;
$p = { command=>'zmc -m 1', daemon=>'zmc', keepalive=>1, delay=>10, failures=>2 };
%ZMServer::cmd_hash = ('zmc -m 1' => $p);
%ZMServer::pid_hash = (); %ZMServer::pids_to_reap = ();
$info = $error = '';
reap(1<<8);
check('capped delay is reported as the max', $error =~ /restarting in 10s/ && $error =~ /retry delay at its 20s maximum/);
check('capped delay keeps counting failures', $error =~ /failure 3 in a row/);

print "\n", ($failures ? "$failures failed\n" : "all passed\n");
exit($failures ? 1 : 0);
