#!/usr/bin/perl
use strict;
use warnings;
use Test::More;

my $file = $ARGV[0] // $ENV{ZMUPDATE} // 'scripts/zmupdate.pl.in';
ok( -f $file, "source file $file exists" ) or BAIL_OUT("missing $file");
open my $fh, '<', $file or BAIL_OUT("cannot open $file: $!");
my $src = do { local $/; <$fh> };
close $fh;

my $opening = 'if ( $cascade || $version lt \'1.26.0\' ) {';
my ($block) = $src =~ /^\s*\Q$opening\E[^\n]*\n(.*?)^    \$version = '1\.26\.0';\n^  \}\n/ms;

ok( defined $block, 'extracted the 1.26.0 column block' ) or BAIL_OUT('could not extract block');

package FakeSth;

sub new {
  my $class = shift;
  return bless { NAME => [qw(Id Name)] }, $class;
}

sub execute { return 1 }
sub finish { return 1 }

package FakeDbh;

our @done;

sub new {
  my ( $class, %args ) = @_;
  return bless { do_result => $args{do_result} }, $class;
}

sub prepare_cached { return FakeSth->new }
sub do {
  my ( $self, $sql ) = @_;
  push @done, $sql;
  return $self->{do_result};
}
sub errstr { return 'alter failed' }

package main;

sub run_block {
  my $do_result = shift;
  @FakeDbh::done = ();
  my ( $cascade, $version ) = ( 1, '1.25.0' );
  my $dbh = FakeDbh->new( do_result => $do_result );
  $@ = '';
  eval $block;
  return ( $@, [@FakeDbh::done] );
}

my ( $err, $done ) = run_block(undef);
ok( $err ne '', 'failed alter dies instead of being ignored' );
like( $err, qr/^Error: alter failed/, 'die message starts with "Error: " and contains errstr' );
is( scalar @$done, 1, 'die on first failure stops before the second alter' );

my ( $err2, $done2 ) = run_block('0E0');
is( $err2, '', 'successful alters do not die' );
is( scalar @$done2, 2, 'both alters attempted on success' );
ok( ( grep { /Colours/ } @$done2 ), 'Colours alter was issued' );
ok( ( grep { /Deinterlacing/ } @$done2 ), 'Deinterlacing alter was issued' );

done_testing();
