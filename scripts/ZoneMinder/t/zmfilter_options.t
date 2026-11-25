use strict;
use warnings;
use Test::More;
use Getopt::Long;

# zmfilter documents -f <filter name>, but with 'filter=s' and 'filter_id=s'
# Getopt::Long's auto-abbreviation made -f ambiguous, so GetOptions failed and
# the daemon exited via pod2usage before doing any work.

my $src_path = $ENV{ZMFILTER_SRC} // 'scripts/zmfilter.pl.in';
open(my $fh, '<', $src_path) or plan skip_all => "cannot read $src_path: $!";
my $src = do { local $/; <$fh> };
close $fh;

my $start = index($src, 'GetOptions(');
ok($start >= 0, 'the script calls GetOptions') or done_testing() and exit;
$start += length('GetOptions(');

my ($depth, $end) = (1, undef);
for my $i ($start .. length($src) - 1) {
  my $c = substr($src, $i, 1);
  if ($c eq '(') { $depth++ }
  elsif ($c eq ')' and --$depth == 0) { $end = $i; last }
}
ok(defined $end, 'the GetOptions argument list is balanced') or done_testing() and exit;

my $args = substr($src, $start, $end - $start);
my @specs = $args =~ /^\s*'?([\w|=:!+]+)'?\s*=>/mg;
ok(scalar @specs, 'extracted at least one option spec') or done_testing() and exit;

my ($filter_spec) = grep { /(?:^|\|)filter=/ } @specs;
my ($filter_id_spec) = grep { /^filter_id=/ } @specs;
ok($filter_spec, 'the script has a filter option');
ok($filter_id_spec, 'the script has a filter_id option') or done_testing() and exit;

sub parse {
  my (@argv) = @_;
  my (%opts, %value);
  for my $spec (@specs) {
    my $scalar;
    $opts{$spec} = \$scalar;
    $value{$spec} = \$scalar;
  }
  my $ok;
  {
    local $SIG{__WARN__} = sub { };
    my @copy = @argv;
    $ok = Getopt::Long::GetOptionsFromArray(\@copy, %opts);
  }
  return ($ok, $value{$filter_spec}, $value{$filter_id_spec});
}

my ($ok, $filter, $filter_id) = parse('-f', 'front');
ok($ok, '-f succeeds (not ambiguous with filter_id)');
is($$filter, 'front', '-f sets the filter name');

($ok, $filter) = parse('--filter=front');
ok($ok, '--filter=front succeeds');
is($$filter, 'front', '--filter sets the filter name');

($ok, undef, $filter_id) = parse('--filter_id=5');
ok($ok, '--filter_id=5 succeeds');
is($$filter_id, '5', '--filter_id sets the filter id');

done_testing();
