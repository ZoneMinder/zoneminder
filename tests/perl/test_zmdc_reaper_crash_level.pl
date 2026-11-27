#!/usr/bin/perl
# A daemon killed by a signal other than TERM/ALRM (e.g. zmc SIGSEGV) has
# exit status 0 in the wait status, so zmdc's reaper must not log it at Info.
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

sub reap {
  my $status = shift;
  ($info, $error) = ('', '');
  %ZMServer::cmd_hash = (); %ZMServer::pid_hash = ();
  my $p = { command=>'zmc -m 1', daemon=>'zmc', pid=>42, started=>time()-10, keepalive=>1 };
  $ZMServer::pid_hash{42} = $p;
  $ZMServer::pids_to_reap{42} = { status=>$status, stopped=>time() };
  ZMServer::reaper();
}

reap(11);
check('SIGSEGV is logged as an error', $error =~ /crashed, signal 11/ && $info eq '');
reap(15);
check('SIGTERM is logged as info', $info =~ /signal 15/ && $error eq '');
reap(0);
check('clean exit is logged as info', $info =~ /normally/ && $error eq '');
reap(3<<8);
check('non-zero exit is logged as an error', $error =~ /exit status 3/);

print "\n", ($failures ? "$failures failed\n" : "all passed\n");
exit($failures ? 1 : 0);
