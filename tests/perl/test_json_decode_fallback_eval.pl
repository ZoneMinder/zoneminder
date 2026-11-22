#!/usr/bin/perl
use strict;
use warnings;
use FindBin;

my $failures = 0;
my $passes = 0;

sub check {
  my ($name, $got, $want) = @_;
  if ((defined $got ? $got : '') eq (defined $want ? $want : '')) {
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

my ($extracted) = $source =~ /^(sub jsonDecode \{.*?^\})/ms;
die "Could not find sub jsonDecode in $file" unless $extracted;

my $code = "package TestGeneral; my \$hasJSONAny = 0; sub _testJSON {} sub Fatal { die \$_[0] } sub Error {} "
  . $extracted . "; 1";
eval $code;
die "Failed to define TestGeneral::jsonDecode: $@" if $@;

my $result = eval { TestGeneral::jsonDecode('{"a":"b","n":[1,2.5,-3],"t":true,"f":false,"z":null,"o":{"k":"v"}}') };
check('valid input decodes', $@ ? "died: $@" : 'ok', 'ok');
if (!$@ && ref($result) eq 'HASH') {
  check('string value', $result->{a}, 'b');
  check('numeric array element', $result->{n}[1], 2.5);
  check('true becomes 1', $result->{t}, 1);
  check('false becomes 0', $result->{f}, 0);
  check('null becomes undef', defined $result->{z} ? 'defined' : 'undef', 'undef');
  check('nested object value', $result->{o}{k}, 'v');
} else {
  check('decoded value is a hash', ref($result) || 'not a reference', 'HASH');
}

$result = eval { TestGeneral::jsonDecode('{"a":"cost $5 and @home"}') };
check('dollar and at in string survive', $@ ? "died: $@" : $result->{a}, 'cost $5 and @home');

undef $main::pwned;
$result = eval { TestGeneral::jsonDecode('{"a":"x@{[ $main::pwned = 1 ]}y"}') };
check('interpolation in string does not run', defined $main::pwned ? 'set' : 'undef', 'undef');
if (!$@ && ref($result) eq 'HASH') {
  check('interpolation text is literal', index($result->{a}, '@{[ $main::pwned = 1 ]}') >= 0 ? 'yes' : 'no', 'yes');
}

undef $main::pwned2;
$result = eval { TestGeneral::jsonDecode('{"a":"q\"@{[ $main::pwned2 = 1 ]}"}') };
check('escaped quote does not allow interpolation', defined $main::pwned2 ? 'set' : 'undef', 'undef');

undef $main::pwned3;
$result = eval { TestGeneral::jsonDecode('{"a":@{[ $main::pwned3 = 1 ]}}') };
check('code outside a string dies', $@ ? 'died' : 'no error', 'died');
check('code outside a string does not run', defined $main::pwned3 ? 'set' : 'undef', 'undef');

$result = eval { TestGeneral::jsonDecode('{"a":[1..1000000]}') };
check('range operator between numbers is refused', $@ ? 'died' : 'no error: '.scalar(@{$result->{a}}).' elements', 'died');

$result = eval { TestGeneral::jsonDecode('{"a":"a"."b"}') };
check('string concatenation outside strings is refused', $@ ? 'died' : 'no error: '.$result->{a}, 'died');

$result = eval { TestGeneral::jsonDecode('{"a":1.5e3,"b":-1.5e3,"c":[0,-2,3E+2,4.25e-1]}') };
check('numbers with fraction and exponent decode', $@ ? "died: $@" : join(',', $result->{a}, $result->{b}, @{$result->{c}}), '1500,-1500,0,-2,300,0.425');

print "\n$passes passed, $failures failed\n";
exit($failures ? 1 : 0);
