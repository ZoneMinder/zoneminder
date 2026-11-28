#!/usr/bin/perl
#
# The type of an option carries a hint (what the options page shows as the
# allowed values), a pattern and a default. For selection options written as
# literal alternatives (a|b|c) every alternative and the default must match the
# pattern. For numeric patterns the default must match.
#
# Run as: perl tests/perl/test_configdata_patterns.pl
#
# ConfigData.pm.in is read directly, with the cmake @VAR@ placeholders blanked,
# so this runs without a build or a configured ZoneMinder.
use strict;
use warnings;
use FindBin;

my $src = "$FindBin::Bin/../../scripts/ZoneMinder/lib/ZoneMinder/ConfigData.pm.in";
open(my $fh, '<', $src) or die "Can't open $src: $!";
my $code = do { local $/; <$fh> };
close($fh);
$code =~ s/\@OPT_FFMPEG\@/1/g;
$code =~ s/\@[A-Z_]+\@/x/g;

BEGIN { $INC{'ZoneMinder/Base.pm'} = 1; }
{
  package ZoneMinder::Base;
  our $VERSION = '0';
}
{
  # ConfigData has an INIT block, which cannot run from a string eval.
  local $SIG{__WARN__} = sub { warn @_ if $_[0] !~ /Too late to run INIT block/ };
  no warnings;
  eval "$code; 1" or die "Can't load ConfigData: $@";
}
my @options = do { no warnings 'once'; @ZoneMinder::ConfigData::options };
die 'no options loaded' unless @options > 100;

my $failures = 0;
my $checked = 0;

sub fail {
  my ($name, $msg) = @_;
  $failures++;
  print "FAIL - $name: $msg\n";
}

foreach my $option (@options) {
  my $name = $option->{name};
  my $type = $option->{type};
  my $pattern = $type->{pattern};
  my $hint = $type->{hint};
  next unless defined $pattern and defined $hint;
  my $default = $option->{default};

  if ($hint =~ /^[\w\-. =]+(?:\|[\w\-. =]+)+$/) {
    # Selection: literal alternatives, each either "value" or "Label=value".
    $checked++;
    my @values = map { /=(.*)$/ ? $1 : $_ } split(/\|/, $hint);
    foreach my $v (@values) {
      fail($name, "hint value '$v' does not match $pattern") if $v !~ $pattern;
    }
    if (defined $default and $default ne '' and !grep { $_ eq $default } @values) {
      fail($name, "default '$default' is not in hint '$hint'");
    }
    if (defined $default and $default ne '' and $default !~ $pattern) {
      fail($name, "default '$default' does not match $pattern");
    }
  } elsif ($hint =~ /^(integer|decimal|hexadecimal)$/) {
    # Numeric: a default that is set must match.
    next if !defined $default or $default eq '';
    $checked++;
    fail($name, "default '$default' does not match $hint pattern $pattern")
      if $default !~ $pattern;
  }
}

print "checked $checked options, $failures failures\n";
exit($failures ? 1 : 0);
