#!/usr/bin/perl
use strict;
use warnings;
use FindBin;

my $root = $ENV{ZM_TEST_ROOT} || "$FindBin::Bin/../..";
my $source = "$root/scripts/zmx10.pl.in";

open( my $fh, '<', $source ) or die "Cannot open $source: $!";
my $text = do { local $/; <$fh> };
close($fh);

my $start_marker = "foreach my \$code_string ( split(',', \$monitor->{Activation}) ) {";
my $end_marker = "} # end foreach code_string";

my $start = index( $text, $start_marker );
die "Could not find Activation foreach loop in $source" if $start < 0;
$start = rindex( $text, "\n", $start ) + 1;

my $end = index( $text, $end_marker, $start );
die "Could not find end of Activation foreach loop in $source" if $end < 0;
$end += length($end_marker);
my $nl = index( $text, "\n", $end );
$end = $nl >= 0 ? $nl + 1 : length($text);

my $loop = substr( $text, $start, $end - $start );

my @calls;
sub addToDeviceList {
  push @calls, join( ':', @_[0, 1] );
}

my $f = eval 'sub { my ($monitor) = @_; '.$loop.' }';
die "Failed to compile Activation loop: $@" if $@;

my ( $passes, $failures ) = ( 0, 0 );

sub check {
  my ( $name, $got, $want ) = @_;
  if ( defined($got) && defined($want) && $got eq $want ) {
    $passes++;
    print "ok - $name\n";
  } else {
    $failures++;
    print "not ok - $name\n";
    print "  got:  " . ( defined($got) ? $got : 'undef' ) . "\n";
    print "  want: " . ( defined($want) ? $want : 'undef' ) . "\n";
  }
}

my @cases = (
  [ 'plain list',          '5,6',       '5:ON,5:OFF,6:ON,6:OFF' ],
  [ 'space after comma',   '5, 6',      '5:ON,5:OFF,6:ON,6:OFF' ],
  [ 'surrounding spaces',  ' 5 , 6 ',   '5:ON,5:OFF,6:ON,6:OFF' ],
  [ 'single unit',         '5',         '5:ON,5:OFF' ],
);

foreach my $case ( @cases ) {
  my ( $name, $activation, $want ) = @$case;
  @calls = ();
  $f->( { Name => 'm', Activation => $activation } );
  check( $name, join( ',', @calls ), $want );
}

print "\n$passes passed, $failures failed\n";
exit( $failures ? 1 : 0 );
