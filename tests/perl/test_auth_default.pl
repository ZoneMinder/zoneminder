#!/usr/bin/perl
#
# ZM_OPT_USE_AUTH is on by default, so a new install starts with
# authentication. An existing install must keep the value it has: zmupdate.pl
# -f loads Value from the database and writes it back, and only DefaultValue
# takes the new default. The update script also records the new DefaultValue,
# because the C++ config loader reads only rows whose Value differs from
# DefaultValue: an upgrade that left Value = DefaultValue = 0 would make the
# daemons use their compiled-in default (on) while the web UI read off.
#
# Run as: perl tests/perl/test_auth_default.pl
#
# ConfigData has no database dependency, so this runs without a configured
# ZoneMinder. The upgrade itself is exercised against a database in the
# holodeck lab (see the PR).
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../build/scripts/ZoneMinder/lib";

use ZoneMinder::ConfigData qw(:data);

my $failures = 0;
my $passes = 0;

sub ok {
  my ($name, $cond, $detail) = @_;
  if ($cond) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print "  $detail\n" if defined $detail;
  }
}

my $option = $options_hash{ZM_OPT_USE_AUTH};
ok('ZM_OPT_USE_AUTH exists', defined $option);
ok('ZM_OPT_USE_AUTH defaults to on', $option && $option->{default} eq '1',
  'default is '.($option ? "'$option->{default}'" : 'undef'));
ok('a new install stores it as on',
  $option && dbValue($option->{type}{db_type}, $option->{value}) eq '1');

# Every update script that touches this row must only change DefaultValue.
my $dir = "$FindBin::Bin/../../db";
opendir(my $dh, $dir) or die "Can't open $dir: $!";
my @updates = sort grep { /^zm_update-[\d.]+\.sql$/ } readdir($dh);
closedir($dh);
my $records_default = 0;
foreach my $file (@updates) {
  open(my $fh, '<', "$dir/$file") or die "Can't open $file: $!";
  my $sql = do { local $/; <$fh> };
  close($fh);
  $sql =~ s/^--.*$//mg;
  foreach my $stmt (grep { /ZM_OPT_USE_AUTH/ } split(/;/, $sql)) {
    ok("$file does not set the Value of ZM_OPT_USE_AUTH", $stmt !~ /(?<!Default)`?Value`?\s*=/i, $stmt)
      if $stmt =~ /^\s*UPDATE/i;
    $records_default = 1 if $stmt =~ /SET\s+`?DefaultValue`?\s*=\s*'1'/i;
  }
}
ok('an update script records the new DefaultValue', $records_default);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
