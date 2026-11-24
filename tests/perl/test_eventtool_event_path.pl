#!/usr/bin/perl
use strict;
use warnings;
use File::Basename;
use Cwd 'abs_path';

my $root = $ENV{ZM_TEST_ROOT};
if (!defined $root || $root eq '') {
  my $dir = dirname(abs_path(__FILE__));
  $root = abs_path("$dir/../..");
}

my $script = "$root/scripts/zmeventtool.pl.in";
open(my $fh, '<', $script) or die "cannot open $script: $!";
my $text = do { local $/; <$fh> };
close($fh);

my ($block) = $text =~ /(if \( \$event_id =~ \/\\D\/ \) \{.*?^\s*\}\s*$)/ms;
die "could not extract block from $script" unless defined $block;

my $sub = eval "sub { my \$event_id = shift; $block; return \$event_id }";
die "eval failed building sub: $@" if $@;

my @cases = (
  [ '123', '123' ],
  [ '/var/events/1/2026-01-01/123', '123' ],
  [ '/var/events/1/2026-01-01/123/', '123' ],
  [ '/var/events/1/2026-01-01/123///', '123' ],
);

my $n = 0;
my $failed = 0;
for my $case (@cases) {
  my ($input, $expect) = @$case;
  $n++;
  my $got = eval { $sub->($input) };
  if ($@) {
    print "not ok $n - input '$input' died: $@";
    $failed++;
  } elsif ($got eq $expect) {
    print "ok $n - input '$input' -> '$expect'\n";
  } else {
    print "not ok $n - input '$input' expected '$expect' got '$got'\n";
    $failed++;
  }
}

print "1..$n\n";
exit($failed ? 1 : 0);
