#!/usr/bin/perl
#
# Tests for scripts/zmonvif-trigger.pl.in: xs_duration for day-long values,
# notification handling (State instead of IsMotion, missing Rule, unknown
# monitor), a failed subscription or renewal not killing the daemon, and
# -v being a plain flag.
#
# Run as: perl tests/perl/test_onvif_trigger.pl
#
use strict;
use warnings;
use FindBin;
use Getopt::Long qw(:config no_auto_abbrev no_ignore_case bundling);

my $file = "$FindBin::Bin/../../scripts/zmonvif-trigger.pl.in";
open(my $fh, '<', $file) or die "Can't open $file: $!";
my $src = do { local $/; <$fh> };
close($fh);

sub extract {
  my ($name) = @_;
  $src =~ /^(sub \Q$name\E \{.*?^\})/ms or die "sub $name not found";
  return $1;
}

my @warnings;
use constant SUBSCRIPTION_RENEW_INTERVAL => 60;
use constant SUBSCRIPTION_RENEW_EARLY => 5;
sub Debug {}
sub Info {}
sub Warning { push @warnings, $_[0]; }

my $handler_called = 0;
$SIG{__DIE__} = sub { $handler_called++; };   # stands in for the daemon's exit handler

my $sub_calls = 0;
my $fail_subscribe = 0;
my $fail_renew = 0;
my $renew_calls = 0;
sub subscribe { $sub_calls++; die "no events service\n" if $fail_subscribe; return 'svc'.$sub_calls; }
sub renew { $renew_calls++; die "renew fault\n" if $fail_renew; }

eval join("\n", map { extract($_) } qw(xs_duration handle_notification subscribe_monitor renew_monitor)) . "\n1;"
  or die $@;

my ($passes, $failures) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '<undef>') eq (defined $want ? $want : '<undef>')) {
    $passes++; print "ok - $name\n";
  } else {
    $failures++; print "NOT OK - $name: got '".(defined $got ? $got : '<undef>')."' want '".(defined $want ? $want : '<undef>')."'\n";
  }
}

# xs_duration
check('duration 65s', xs_duration(65), 'PT1M5S');
check('duration 1h', xs_duration(3600), 'PT1H');
check('duration 1 day', xs_duration(86400), 'P1DT');
check('duration 2 days 3h', xs_duration(2*86400+3*3600), 'P2DT3H');

# notifications
package FakeZM;
sub new { return bless { calls => [] }, shift; }
sub monitors { return (1 => { Id => 1 }); }
sub eventOn { my $s = shift; push @{$s->{calls}}, join('/', 'on', @_); }
sub eventOff { my $s = shift; push @{$s->{calls}}, join('/', 'off', @_); }
package main;

sub notify {
  my ($id, $source, $data) = @_;
  my $zm = FakeZM->new();
  handle_notification($zm, $id, 'tns1:RuleEngine/x', 't', $source, $data);
  return join(',', @{$zm->{calls}});
}
check('IsMotion true', notify(1, {Rule => 'r'}, {IsMotion => 'true'}), 'on/1/100/r/t');
check('IsMotion false', notify(1, {Rule => 'r'}, {IsMotion => 'false'}), 'off/1/100/r/t');
check('State true', notify(1, {Rule => 'r'}, {State => 'true'}), 'on/1/100/r/t');
check('State false', notify(1, {Rule => 'r'}, {State => 'false'}), 'off/1/100/r/t');
check('no Rule uses topic', notify(1, {}, {IsMotion => 'true'}), 'on/1/100/tns1:RuleEngine/x/t');
check('no state ignored', notify(1, {Rule => 'r'}, {}), '');
check('unknown monitor ignored', notify(7, {Rule => 'r'}, {IsMotion => 'true'}), '');

# subscribe / renew failures
my $transport = 'tr';
my $client = FakeClient->new();
package FakeClient;
sub new { return bless {}, shift; }
sub get_endpoint { return $_[0]; }
sub set_transport { }
package main;

my $m = { Id => 3, onvif_client => $client };
@warnings = ();
$fail_subscribe = 1;
$handler_called = 0;
my $ok = eval { subscribe_monitor($m, 'a:1', $transport); 1 };
check('failed subscription survives', $ok ? 1 : 0, 1);
check('die handler not triggered', $handler_called, 0);
check('failed subscription warns', scalar(@warnings), 1);
check('no submgr after failure', defined $m->{submgr_svc} ? 'set' : 'unset', 'unset');

$fail_subscribe = 0;
subscribe_monitor($m, 'a:1', $transport);
check('subscription stored', $m->{submgr_svc}, 'svc'.$sub_calls);

$fail_renew = 1;
my $before = $sub_calls;
$handler_called = 0;
$ok = eval { renew_monitor($m, 'a:1', $transport); 1 };
check('failed renewal survives', $ok ? 1 : 0, 1);
check('die handler not triggered by renewal', $handler_called, 0);
check('failed renewal resubscribes', $sub_calls, $before + 1);

delete $m->{submgr_svc};
$fail_renew = 0;
$before = $sub_calls;
renew_monitor($m, 'a:1', $transport);
check('missing subscription retried', $sub_calls, $before + 1);

# -v is a flag
my ($verbose, $localaddr) = (0);
@ARGV = ('-v', '-l', '1.2.3.4:5');
GetOptions('local-addr|l=s' => \$localaddr, 'verbose|v+' => \$verbose);
check('-v does not eat next option', $localaddr, '1.2.3.4:5');
check('-v counted', $verbose, 1);
$src =~ /'verbose\|v\+'/ ? check('script declares -v as flag', 1, 1) : check('script declares -v as flag', 0, 1);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
