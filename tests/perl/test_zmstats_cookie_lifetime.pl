#!/usr/bin/perl
# zmstats.pl purges Sessions older than ZM_COOKIE_LIFETIME. At 0 that deleted
# every session, so values under 60 seconds use the default 3600 with one warning.
# Set ZMSTATS_SOURCE to test another copy of zmstats.pl.in.
use strict;
use warnings;
use FindBin;
use Scalar::Util qw(looks_like_number);

my $path = $ENV{ZMSTATS_SOURCE} // "$FindBin::Bin/../../scripts/zmstats.pl.in";
open(my $fh, '<', $path) or die "Can't open $path: $!";
my $source = do { local $/; <$fh> };
close($fh);

my ($pass, $fail) = (0, 0);
sub check {
  my ($name, $got, $want) = @_;
  if ($got eq $want) { $pass++; print "ok - $name\n"; }
  else { $fail++; print "FAIL - $name\n  got:  $got\n  want: $want\n"; }
}

our %Config;
my @warnings;
sub Warning { push @warnings, join('', @_); }

my ($sub) = $source =~ /^(sub cookie_lifetime \{.*?^\})/ms;
check('cookie_lifetime() is defined in zmstats.pl.in', defined $sub ? 1 : 0, 1);
if (!defined $sub) {
  print "\n$pass passed, ".($fail)." failed\n";
  exit 1;
}
eval "$sub; 1" or die $@;

$Config{ZM_COOKIE_LIFETIME} = 0;
check('0 gives 3600', cookie_lifetime(), 3600);
check('0 warned once', scalar(@warnings), 1);
check('warning names the option', $warnings[0] =~ /ZM_COOKIE_LIFETIME/ ? 1 : 0, 1);
cookie_lifetime();
check('warning is not repeated', scalar(@warnings), 1);
$Config{ZM_COOKIE_LIFETIME} = 30;
check('30 gives 3600', cookie_lifetime(), 3600);
$Config{ZM_COOKIE_LIFETIME} = 'abc';
check('non-number gives 3600', cookie_lifetime(), 3600);
$Config{ZM_COOKIE_LIFETIME} = undef;
check('undef gives 3600', cookie_lifetime(), 3600);
$Config{ZM_COOKIE_LIFETIME} = 60;
check('60 is kept', cookie_lifetime(), 60);
$Config{ZM_COOKIE_LIFETIME} = 86400;
check('86400 is kept', cookie_lifetime(), 86400);

(my $outside = $source) =~ s/^sub cookie_lifetime \{.*?^\}//ms;
$outside =~ s/^\s*#.*$//mg;
check('purge does not read $Config{ZM_COOKIE_LIFETIME} directly',
  scalar(() = $outside =~ /ZM_COOKIE_LIFETIME/g), 0);
check('purge uses cookie_lifetime()', $outside =~ /DELETE FROM Sessions[^\n]*cookie_lifetime\(\)/ ? 1 : 0, 1);

print "\n$pass passed, $fail failed\n";
exit($fail ? 1 : 0);
