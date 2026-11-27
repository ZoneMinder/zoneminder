#!/usr/bin/perl
#
# An empty ZM_TELEMETRY_LAST_UPLOAD (the config default) must count as 0 so the
# first loop in zmtelemetry.pl does not warn "isn't numeric" when subtracting it.
#
# Run as: perl tests/perl/test_telemetry_last_upload.pl
#
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

my ($block) = $source =~ /^(my \$lastCheck = .*?;(?:\n[^\n]*\$lastCheck[^\n]*;)*)$/m;
die 'Could not extract lastCheck initialisation from zmtelemetry.pl.in' unless $block;

sub run_init {
  my ($value) = @_;
  my %Config = (ZM_TELEMETRY_LAST_UPLOAD => $value);
  my @warnings;
  local $SIG{__WARN__} = sub { push @warnings, $_[0] };
  my $result = eval "$block\n\$lastCheck + 0;" // die $@;
  return ($result, scalar(@warnings));
}

my ($v, $w) = run_init('');
check('empty value counts as 0', $v, 0);
check('empty value does not warn', $w, 0);

($v, $w) = run_init(undef);
check('undef value counts as 0', $v, 0);
check('undef value does not warn', $w, 0);

($v, $w) = run_init('1700000000');
check('numeric value is kept', $v, 1700000000);
check('numeric value does not warn', $w, 0);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
