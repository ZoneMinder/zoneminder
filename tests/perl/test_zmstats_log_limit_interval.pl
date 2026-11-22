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

my $root = $ENV{ZM_TEST_ROOT} // "$FindBin::Bin/../..";
my $slurp = sub {
  my $path = shift;
  open(my $fh, '<', $path) or die "Can't open $path: $!";
  local $/;
  return scalar <$fh>;
};

# logLimitInterval() lives in ZoneMinder::General, which zmstats and zmaudit share.
my $module = "$root/scripts/ZoneMinder/lib/ZoneMinder/General.pm";
my $module_source = $slurp->($module);
my ($block) = $module_source =~ /^(sub logLimitInterval \{.*?\n\})$/ms;
die "Could not extract logLimitInterval from $module\n" unless $block;

eval $block;
die "Could not compile logLimitInterval from $module: $@\n" if $@;

check("'7 days' -> '7 day'", logLimitInterval('7 days'), '7 day');
check("'1 week' -> '1 week'", logLimitInterval('1 week'), '1 week');
check("'2 hours' -> '2 hour'", logLimitInterval('2 hours'), '2 hour');
check("' 3 MONTHS ' -> '3 month'", logLimitInterval(' 3 MONTHS '), '3 month');
check("'1day' -> '1 day'", logLimitInterval('1day'), '1 day');
check("'1 day) OR 1=1 -- ' -> undef", logLimitInterval('1 day) OR 1=1 -- '), undef);
check("'1 day; DROP' -> undef", logLimitInterval('1 day; DROP'), undef);
check("'day' -> undef", logLimitInterval('day'), undef);
check("'' -> undef", logLimitInterval(''), undef);
check("undef -> undef", logLimitInterval(undef), undef);
check("'5 fortnights' -> undef", logLimitInterval('5 fortnights'), undef);

check('General.pm exports logLimitInterval', $module_source =~ /^\s+logLimitInterval$/m ? 'yes' : 'no', 'yes');

for my $script ('zmstats.pl.in', 'zmaudit.pl.in') {
  my $source = $slurp->("$root/scripts/$script");
  check("$script does not interpolate ZM_LOG_DATABASE_LIMIT into an SQL interval",
    index($source, q{interval '.$Config{ZM_LOG_DATABASE_LIMIT}}) < 0 ? 'absent' : 'present', 'absent');
  check("$script does not interpolate \$audit_limit into an SQL interval",
    index($source, q{interval '.$audit_limit.'}) < 0 ? 'absent' : 'present', 'absent');
  check("$script validates both limits with logLimitInterval",
    scalar(() = $source =~ /logLimitInterval\(/g), 2);
}

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
