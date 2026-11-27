#!/usr/bin/perl
use strict;
use warnings;
use FindBin;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '<undef>') eq (defined $want ? $want : '<undef>')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n  got:  ".(defined $got ? $got : '<undef>')."\n  want: ".(defined $want ? $want : '<undef>')."\n";
  }
}

my $source = do {
  open(my $fh, '<', "$FindBin::Bin/../../scripts/zmtelemetry.pl.in")
    or die "Can't open zmtelemetry.pl.in: $!";
  local $/;
  <$fh>;
};

my ($block) = $source =~ /(if\s*\(defined\s*\$interval\)\s*\{.*?\n\}\s*else\s*\{.*?\n\})/s;
die "Could not extract interval validation logic from zmtelemetry.pl.in" unless $block;

sub run_validation {
  my ($interval_in, $config_in) = @_;
  my $interval = $interval_in;
  my %Config = (ZM_TELEMETRY_INTERVAL => $config_in);
  my ($fatal, $warning);
  no warnings 'once';
  local *Fatal = sub { $fatal = $_[0]; die "FATAL: $_[0]\n"; };
  local *Warning = sub { $warning = $_[0]; };
  eval $block;
  return ($interval, $fatal, $warning);
}

# 1. CLI --interval checks
{
  my ($inv, $fat, $wrn) = run_validation('86400', undef);
  check('--interval with valid integer', $inv, 86400);
  check('--interval with valid integer has no fatal', $fat, undef);

  ($inv, $fat, $wrn) = run_validation('24*60*60', undef);
  check('--interval with valid arithmetic expression', $inv, 86400);
  check('--interval with arithmetic expression has no fatal', $fat, undef);

  ($inv, $fat, $wrn) = run_validation('bad', undef);
  check('--interval with non-arithmetic characters triggers regex Fatal', $fat, '--interval must be a number of seconds or a simple arithmetic expression');

  ($inv, $fat, $wrn) = run_validation('1/0', undef);
  check('--interval with division by zero triggers positive number Fatal', $fat, '--interval must evaluate to a positive number of seconds');

  ($inv, $fat, $wrn) = run_validation('(', undef);
  check('--interval with parse error triggers positive number Fatal', $fat, '--interval must evaluate to a positive number of seconds');

  ($inv, $fat, $wrn) = run_validation('0', undef);
  check('--interval evaluating to 0 triggers positive number Fatal', $fat, '--interval must evaluate to a positive number of seconds');

  ($inv, $fat, $wrn) = run_validation('-60', undef);
  check('--interval evaluating to negative triggers positive number Fatal', $fat, '--interval must evaluate to a positive number of seconds');
}

# 2. Config ZM_TELEMETRY_INTERVAL checks on the else branch
{
  my ($inv, $fat, $wrn) = run_validation(undef, '86400');
  check('config with valid integer sets interval', $inv, 86400);
  check('config with valid integer produces no warning', $wrn, undef);

  ($inv, $fat, $wrn) = run_validation(undef, '24*60*60');
  check('config with arithmetic expression sets interval', $inv, 86400);
  check('config with arithmetic expression produces no warning', $wrn, undef);

  ($inv, $fat, $wrn) = run_validation(undef, '1/0');
  check('config with division by zero falls back to 86400', $inv, 86400);
  check('config with division by zero warns', ($wrn // '') =~ /Invalid ZM_TELEMETRY_INTERVAL/ ? 'warned' : ($wrn // '<undef>'), 'warned');

  ($inv, $fat, $wrn) = run_validation(undef, '(');
  check('config with parse error falls back to 86400', $inv, 86400);
  check('config with parse error warns', ($wrn // '') =~ /Invalid ZM_TELEMETRY_INTERVAL/ ? 'warned' : ($wrn // '<undef>'), 'warned');

  ($inv, $fat, $wrn) = run_validation(undef, '0');
  check('config with 0 falls back to 86400', $inv, 86400);
  check('config with 0 warns', ($wrn // '') =~ /Invalid ZM_TELEMETRY_INTERVAL/ ? 'warned' : ($wrn // '<undef>'), 'warned');

  ($inv, $fat, $wrn) = run_validation(undef, '-3600');
  check('config with negative value falls back to 86400', $inv, 86400);
  check('config with negative value warns', ($wrn // '') =~ /Invalid ZM_TELEMETRY_INTERVAL/ ? 'warned' : ($wrn // '<undef>'), 'warned');

  ($inv, $fat, $wrn) = run_validation(undef, 'bad_config');
  check('config with invalid characters falls back to 86400', $inv, 86400);
  check('config with invalid characters warns', ($wrn // '') =~ /Invalid ZM_TELEMETRY_INTERVAL/ ? 'warned' : ($wrn // '<undef>'), 'warned');

  ($inv, $fat, $wrn) = run_validation(undef, undef);
  check('config with undef falls back to 86400', $inv, 86400);
  check('config with undef warns', ($wrn // '') =~ /Invalid ZM_TELEMETRY_INTERVAL/ ? 'warned' : ($wrn // '<undef>'), 'warned');
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
