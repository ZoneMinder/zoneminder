#!/usr/bin/perl
#
# Regression test for GHSA-9v28-c6w5-v8w6.
#
# When zmfilter builds a filter's AutoExecute command, each substituted tag value
# is inserted into the command line bare (unquoted) only when it is classified
# "plain" by a regex; otherwise it is passed through an environment variable and
# referenced quoted. The classifier used /^[\w\/.,:+=@% -]*$/, and in Perl $ also
# matches immediately before a trailing newline, so a value like "foo\n" was
# classified plain and inserted bare. The newline then ends that shell command
# line and a second plain tag placed after it runs as its own command. A user
# with Events edit controls the event fields that feed the tags, so on a filter
# with an AutoExecute action this is authenticated command execution.
#
# The fix anchors the test with \z (true end of string) so any trailing newline
# fails the plain test and the value is quoted.
#
# This test reads the real scripts/zmfilter.pl.in, extracts the classification
# regex, and applies it to sample values -- so it fails if the anchor is reverted
# to $. Run as: perl tests/perl/test_zmfilter_autoexecute_plain.pl
#
use strict;
use warnings;
use FindBin;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ($got == $want) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name (got $got, want $want)\n";
  }
}

my $src_file = "$FindBin::Bin/../../scripts/zmfilter.pl.in";
open(my $fh, '<', $src_file) or die "cannot open $src_file: $!";
my $src = do { local $/; <$fh> };
close($fh);

# Pull the plain-value classification out of executeCommand():
#     if ($value =~ /^[\w\/.,:+=@% -]*\z/) {
my ($regex_literal) = $src =~ /\$value\s*=~\s*(\/\^\[.*?\]\*\\?[z\$]\/)/;
die "could not find the plain-value classification regex in $src_file\n"
  unless defined $regex_literal;
print "# using classifier: $regex_literal\n";

# Build a predicate from the extracted literal. eval-string so the slashes and
# anchor come straight from the source under test.
my $is_plain = eval "sub { my \$v = shift; return (\$v =~ $regex_literal) ? 1 : 0; }";
die "could not compile extracted regex: $@\n" if $@ or !$is_plain;

# The anchor must be \z, not $, or a trailing newline slips through as "plain".
check('classifier is anchored with \\z, not $', ($regex_literal =~ /\\z\/$/) ? 1 : 0, 1);

# Values that are genuinely plain stay plain (no behaviour change for valid input).
check('plain word is plain',            $is_plain->('eventname'), 1);
check('path is plain',                  $is_plain->('/var/cache/zm/1/1-video.mp4'), 1);
check('key=value is plain',             $is_plain->('id=5'), 1);
check('dashes and dots are plain',      $is_plain->('a-b_c.2'), 1);
check('spaces and percent are plain',   $is_plain->('100% done'), 1);

# The GHSA-9v28 case: a trailing newline must NOT be plain (it must be quoted).
check('trailing newline is NOT plain',  $is_plain->("foo\n"), 0);
check('value that is only a newline is NOT plain', $is_plain->("\n"), 0);

# Shell metacharacters must not be plain either (already true before the fix,
# kept here so the classifier is not loosened by a future edit).
check('semicolon is NOT plain',         $is_plain->('foo; rm -rf /'), 0);
check('command substitution is NOT plain', $is_plain->('a$(touch /tmp/x)b'), 0);
check('backtick is NOT plain',          $is_plain->('a`id`b'), 0);
check('ampersand is NOT plain',         $is_plain->('a&b'), 0);
check('pipe is NOT plain',              $is_plain->('a|b'), 0);

print '1..'.($passes + $failures)."\n";
if ($failures) {
  print "# $failures of ".($passes + $failures)." checks failed\n";
  exit 1;
}
print "# all $passes checks passed\n";
exit 0;
