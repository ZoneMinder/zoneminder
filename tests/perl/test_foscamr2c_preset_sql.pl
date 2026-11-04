#!/usr/bin/perl
#
# ZoneMinder::Control::FOSCAMR2C must not put the preset into SQL text. The preset comes
# from the web request; presetGoto/presetSet interpolated it into
# "SELECT Label FROM ControlPresets WHERE Preset = $preset" and sent the selected Label to
# the camera, so a viewer could read any value from the database. refs GHSA-qcm7-vq92-f86f
#
# The master/1.38 test loads the module with a fake dbh, but on 1.36 the dev box cannot
# load ZoneMinder::Config (a .pm.in template), so this reads the real module source and
# checks the statements it prepares. Run: perl tests/perl/test_foscamr2c_preset_sql.pl
#
use strict;
use warnings;
use FindBin;

my $failures = 0;
my $passes = 0;
sub check {
  my ($name, $cond) = @_;
  if ($cond) { $passes++; print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name\n"; }
}

my $file = "$FindBin::Bin/../../scripts/ZoneMinder/lib/ZoneMinder/Control/FOSCAMR2C.pm";
open(my $fh, '<', $file) or die "cannot open $file: $!";
my $src = do { local $/; <$fh> };
close($fh);

# Isolate presetGoto and presetSet, dropping comment-only lines so the explanatory
# comment (which names the old interpolated SQL) is not mistaken for live code.
sub body {
  my ($name) = @_;
  return '' unless $src =~ /\nsub \Q$name\E\b(.*?)\n\}/s;
  my $b = $1;
  return join("\n", grep { !/^\s*#/ } split(/\n/, $b));
}
my $goto = body('presetGoto');
my $set  = body('presetSet');
check('found sub presetGoto', length $goto);
check('found sub presetSet', length $set);

for my $pair (['presetGoto', $goto], ['presetSet', $set]) {
  my ($name, $b) = @$pair;

  # A numeric guard runs before any SQL.
  check("$name refuses a non-numeric preset",
    $b =~ /\$preset\s*!~\s*\/\^\\d\+\$\// && $b =~ /return\b/);

  # No ControlPresets lookup interpolates the preset into the SQL text.
  check("$name does not interpolate \$preset into ControlPresets SQL",
    $b !~ /ControlPresets`?\s+WHERE[^?]*\$preset/s);

  # The ControlPresets lookup is a prepared statement scoped by MonitorId and bound.
  check("$name looks ControlPresets up by MonitorId with a bound preset",
    $b =~ /SELECT\s+`Label`\s+FROM\s+`ControlPresets`\s+WHERE\s+`MonitorId`\s*=\s*\?\s+AND\s+`Preset`\s*=\s*\?/);
}

# presetSet's writes to ControlPresetNames must be bound too, not built from $preset/$label.
check('presetSet deletes ControlPresetNames with a bound preset',
  $set =~ /DELETE\s+FROM\s+`ControlPresetNames`\s+WHERE\s+`Preset`\s*=\s*\?/ &&
  $set !~ /DELETE\s+FROM\s+`ControlPresetNames`\s+WHERE\s+`Preset`\s*=\s*\$preset/);
check('presetSet inserts into ControlPresetNames with bound values',
  $set =~ /INSERT\s+INTO\s+`ControlPresetNames`\([^)]*\)\s*VALUES\s*\(\?,\s*\?\)/ &&
  $set !~ /VALUES\s*\('\$preset'/);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
