#!/usr/bin/perl
#
# Tests for scripts/zmonvif-trigger.pl.in: a die caught by an eval must not
# reach the daemon's shutdown handler (it would kill the renewal process or
# exit the daemon), and the notification address must have a host when no
# local address is given with -l.
#
# Run as: perl tests/perl/test_onvif_trigger_die_handler.pl
#
use strict;
use warnings;
use FindBin;

my $file = "$FindBin::Bin/../../scripts/zmonvif-trigger.pl.in";
open(my $fh, '<', $file) or die "Can't open $file: $!";
my $src = do { local $/; <$fh> };
close($fh);

my ($passes, $failures) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '<undef>') eq (defined $want ? $want : '<undef>')) {
    $passes++; print "ok - $name\n";
  } else {
    $failures++; print "NOT OK - $name: got '".(defined $got ? $got : '<undef>')."' want '".(defined $want ? $want : '<undef>')."'\n";
  }
}

# the die handler the script installs, with the shutdown handler stubbed
my $shutdowns = 0;
{
  no warnings 'once';
  *handler = sub { $shutdowns++; };
}
$src =~ /^(\$SIG\{__DIE__\} = .*?;)\s*$/m or die '__DIE__ handler not found';
my $install = $1;
{
  local $SIG{__DIE__};
  eval "no strict; $install; 1" or die $@;
  eval { die "caught by eval\n" };
  check('die inside eval does not shut down', $shutdowns, 0);
  check('eval still sees the error', $@, "caught by eval\n");
}

# notification address
$src =~ /^(sub consumer_address \{.*?^\})/ms or do {
  check('consumer_address exists', 0, 1);
  print "\n$passes passed, $failures failed\n";
  exit 1;
};
eval "$1\n1;" or die $@;
check('listen on all interfaces uses url host', consumer_address(undef, 'http://camhost:8123/'), 'camhost:8123');
check('-l address used', consumer_address('10.0.0.5', 'http://127.0.0.1:8123/'), '10.0.0.5:8123');
check('-l empty uses url host', consumer_address('', 'http://camhost:8123/'), 'camhost:8123');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
