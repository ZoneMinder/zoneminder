#!/usr/bin/perl
#
# ZoneMinder::Config::generateAuthHashSecret() extends a short hand-set
# ZM_AUTH_HASH_SECRET through ZoneMinder::ConfigData's extendAuthHashSecret().
# Config.pm only requires ConfigData and does not import from it, so a bare
# extendAuthHashSecret() call dies with "Undefined subroutine" and zmupdate.pl
# fails during a package upgrade.
#
# The sub is taken from Config.pm.in and run in package ZoneMinder::Config
# with a ConfigData stand-in that exports nothing.
#
# Run as: perl tests/perl/test_config_generate_auth_hash_secret.pl [path/to/Config.pm.in]
use strict;
use warnings;
use FindBin;

my $file = $ARGV[0] // "$FindBin::Bin/../../scripts/ZoneMinder/lib/ZoneMinder/Config.pm.in";
open(my $fh, '<', $file) or die "Can't read $file: $!";
my $src = do { local $/; <$fh> };
close($fh);

my ($sub) = $src =~ /^(sub generateAuthHashSecret \{.*?^\} # end sub generateAuthHashSecret|sub generateAuthHashSecret \{.*?^\})/ms;
die "generateAuthHashSecret not found in $file\n" if !$sub;

package ZoneMinder::ConfigData;
our %options_hash;
sub extendAuthHashSecret {
  my $value = shift;
  return $value if length($value) >= 32;
  my $out = $value;
  $out .= $value while length($out) < 32;
  return $out;
}

package ZoneMinder::Config;
sub loadConfigData { }

package main;

my ($passes, $failures) = (0, 0);
sub check {
  my ($name, $cond, $detail) = @_;
  if ($cond) { $passes++; print "ok - $name\n"; }
  else { $failures++; print "FAIL - $name\n"; print "  $detail\n" if defined $detail; }
}

{
  no warnings 'redefine';
  my $code = "package ZoneMinder::Config;\n$sub\n1;";
  eval $code or die "Couldn't compile generateAuthHashSecret: $@";
}

$ZoneMinder::ConfigData::options_hash{ZM_AUTH_HASH_SECRET} = { value => 'abc', default => '...Change me to something unique...' };
my $out = '';
{
  local *STDOUT;
  open(STDOUT, '>', \$out);
  eval { ZoneMinder::Config::generateAuthHashSecret(); 1 } or do {
    my $err = $@;
    close(STDOUT);
    check('short secret is extended without dying', 0, $err);
  };
}
my $value = $ZoneMinder::ConfigData::options_hash{ZM_AUTH_HASH_SECRET}{value};
check('short secret is extended to at least 32 characters', length($value) >= 32, "got '$value'");
check('extended secret keeps the chosen secret', index($value, 'abc') == 0, "got '$value'");

$ZoneMinder::ConfigData::options_hash{ZM_AUTH_HASH_SECRET} = { value => 'z' x 40, default => '...Change me to something unique...' };
eval { ZoneMinder::Config::generateAuthHashSecret(); 1 } or check('long secret is left alone without dying', 0, $@);
check('long secret is left alone', $ZoneMinder::ConfigData::options_hash{ZM_AUTH_HASH_SECRET}{value} eq 'z' x 40);

print "$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
