#!/usr/bin/perl
#
# php-jwt 7 refuses an HS256 key shorter than 32 bytes, so a hand-set
# ZM_AUTH_HASH_SECRET shorter than that breaks token login after an upgrade.
# zmupdate.pl -f (saveConfigToDB) extends such a secret by repeating it,
# through extendAuthHashSecret(), keeping the secret the user chose rather than
# replacing it.
#
# Run as: perl tests/perl/test_auth_hash_secret_extend.pl
#
# ConfigData has no database dependency, so this runs without a configured
# ZoneMinder.
use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../build/scripts/ZoneMinder/lib";

use ZoneMinder::ConfigData qw(extendAuthHashSecret);

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

sub is {
  my ($name, $got, $expected) = @_;
  ok($name, $got eq $expected, "got '$got', expected '$expected'");
}

is('short secret is repeated to at least 32 characters',
  extendAuthHashSecret('abc'), 'abc' x 11);
is('31 characters doubles', extendAuthHashSecret('x' x 31), 'x' x 62);
is('32 characters is left alone', extendAuthHashSecret('y' x 32), 'y' x 32);
my $hex = 'ab' x 32;
is('generated 64 hex character secret is left alone', extendAuthHashSecret($hex), $hex);
is('empty is left for generateAuthHashSecret to replace', extendAuthHashSecret(''), '');
ok('result starts with the original secret', index(extendAuthHashSecret('secret'), 'secret') == 0);
ok('result is never shorter than 32', length(extendAuthHashSecret('q')) >= 32);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
