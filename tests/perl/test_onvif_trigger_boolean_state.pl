#!/usr/bin/perl
use strict;
use warnings;
use FindBin;

my $root = $ENV{ZM_TEST_ROOT} || "$FindBin::Bin/../..";
my $script = "$root/scripts/zmonvif-trigger.pl.in";

open( my $fh, '<', $script ) or die "Cannot open $script: $!";
local $/;
my $text = <$fh>;
close($fh);

my ($sub) = $text =~ /^(sub handle_notification \{.*?^\})/ms;
die "Could not extract sub handle_notification from $script\n" if !defined $sub;

package main;

our @calls;

sub Debug { }
sub Warning { }

eval $sub;
die "Failed to eval handle_notification: $@" if $@;

package FakeZm;

sub new {
  my ($class) = @_;
  return bless {}, $class;
}

sub monitors {
  return ( 4 => {} );
}

sub eventOn {
  my ( $self, $id ) = @_;
  push @main::calls, "on:$id";
}

sub eventOff {
  my ( $self, $id ) = @_;
  push @main::calls, "off:$id";
}

package main;

my $zm = FakeZm->new();
my $topic = 'tns1:RuleEngine/CellMotionDetector/Motion';
my $passed = 0;
my $failed = 0;

sub check {
  my ( $name, $expected, $v, $data ) = @_;
  @calls = ();
  handle_notification( $zm, 4, $topic, 'T', { Rule => 'R' }, $data );
  my @got = @calls;
  my @want = defined $expected ? ($expected) : ();
  if ( "@got" eq "@want" ) {
    print "ok - $name\n";
    $passed++;
  } else {
    print "NOT OK - $name (got: '@got', want: '@want')\n";
    $failed++;
  }
}

check( 'IsMotion true',  'on:4',  'true',  { IsMotion => 'true' } );
check( 'IsMotion false', 'off:4', 'false', { IsMotion => 'false' } );
check( 'IsMotion 1',     'on:4',  '1',     { IsMotion => '1' } );
check( 'IsMotion 0',     'off:4', '0',     { IsMotion => '0' } );
check( 'IsMotion TRUE',  'on:4',  'TRUE',  { IsMotion => 'TRUE' } );
check( 'State 1',        'on:4',  '1',     { State => '1' } );
check( 'IsMotion unknown', undef, 'unknown', { IsMotion => 'unknown' } );

print "\n$passed passed, $failed failed\n";

exit( $failed ? 1 : 0 );
