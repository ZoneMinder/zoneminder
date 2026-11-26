#!/usr/bin/perl
# zmvideo.pl detaints $transforms. With no -t option it is '', which does not
# match the detaint pattern, so the assignment must leave '' and not undef or
# GenerateVideo warns "uninitialized value $transforms in split" on every run
# (seen on every AutoVideo filter pass). Structural test on the template.
use strict;
use warnings;
use FindBin;

my $file = "$FindBin::Bin/../../scripts/zmvideo.pl.in";
open(my $fh, '<', $file) or die "Can't read $file: $!";
my @lines = <$fh>;
close($fh);

my $failures = 0;
sub check { my ($n, $ok) = @_; print(($ok ? 'ok' : 'FAIL')." - $n\n"); $failures++ if !$ok; }

my ($line) = grep { /^\s*\(?\$transforms\)?\s*=.*\$transforms\s*=~/ } @lines;
check('detaint statement found', defined $line);

my %result;
foreach my $value ('', 'transpose=1', 'hflip,vflip', 'a b') {
  our $transforms = $value;
  my $code = $line;
  $code =~ s/\$transforms/\$main::transforms/g;
  eval $code;
  $result{$value} = $main::transforms;
}
check('empty transforms stays defined', (defined $result{''} && $result{''} eq '') ? 1 : 0);
check('valid transforms are kept', ($result{'transpose=1'} // '') eq 'transpose=1');
check('valid list is kept', ($result{'hflip,vflip'} // '') eq 'hflip,vflip');
check('invalid transforms are dropped without undef', (defined $result{'a b'} && $result{'a b'} eq '') ? 1 : 0);
print "\n", ($failures ? "$failures failed\n" : "all passed\n");
exit($failures ? 1 : 0);
