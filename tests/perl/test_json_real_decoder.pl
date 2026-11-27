#!/usr/bin/perl
#
# jsonEncode and jsonDecode in ZoneMinder::General use JSON::MaybeXS when it is
# installed. They used to keep the hand written fallback instead, because the
# load check never saw the import succeed. The fallback decodes a unicode
# escape in a filter's Query_json to its hex digits, and writes a raw newline
# into a string.
#
use strict;
use warnings;
use FindBin;

binmode(STDOUT, ':encoding(UTF-8)');

if (!eval { require JSON::MaybeXS; require JSON::PP; 1 }) {
  print "skip - JSON::MaybeXS is not installed\n";
  exit 0;
}

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '(undef)') eq (defined $want ? $want : '(undef)')) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "FAIL - $name\n";
    print '  got:  '.(defined $got ? $got : '(undef)')."\n";
    print '  want: '.(defined $want ? $want : '(undef)')."\n";
  }
}

my $root = $ENV{ZM_TEST_ROOT} || "$FindBin::Bin/../..";
my $file = "$root/scripts/ZoneMinder/lib/ZoneMinder/General.pm";

open(my $fh, '<', $file) or die "Cannot open $file: $!";
local $/;
my $source = <$fh>;
close($fh);

my $extracted = '';
foreach my $name (qw(_testJSON _getJSONType jsonEncode jsonDecode)) {
  my ($sub) = $source =~ /^(sub \Q$name\E \{.*?^\})/ms;
  die "Could not find sub $name in $file" unless $sub;
  $extracted .= $sub."\n";
}

my $code = "package TestGeneral; our \$testedJSON = 0; our \$hasJSONAny = 0; sub Fatal { die \$_[0] } sub Error {} "
  . $extracted . "; 1";
eval $code;
die "Failed to define TestGeneral functions: $@" if $@;

TestGeneral::_testJSON();
no warnings 'once';
check('JSON::MaybeXS is detected', $TestGeneral::hasJSONAny ? 1 : 0, 1);

# What the web ui stores: json_encode escapes non-ASCII as a backslash-u escape and / as backslash-slash
my $bs = chr(92);
my $query = '{"terms":[{"attr":"MonitorName","op":"=","val":"K' . $bs . 'u00fcche ' . $bs . 'u4e2d"},{"attr":"Notes","op":"LIKE","val":"a' . $bs . '/b"}]}';
my $decoded = TestGeneral::jsonDecode($query);
check('unicode escapes decode to the character', $decoded->{terms}[0]{val}, "K\x{fc}che \x{4e2d}");
check('escaped slash decodes to /', $decoded->{terms}[1]{val}, 'a/b');

my $text = "line one\nline two\t\"quoted\" back\\slash K\x{fc}che";
my $json = TestGeneral::jsonEncode({error => $text, n => 1});
my $strict = eval { JSON::PP->new->decode($json) };
check('encoded text with a newline is valid JSON', $@ ? "died: $@" : 'ok', 'ok');
check('encoded text decodes to the original', ref($strict) ? $strict->{error} : undef, $text);
check('encoded text is plain ASCII', $json =~ /^[\x20-\x7e]*$/ ? 'yes' : 'no', 'yes');
my $round = eval { TestGeneral::jsonDecode($json) };
check('encode then decode round trips', ref($round) ? $round->{error} : undef, $text);

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
