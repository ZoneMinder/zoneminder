use strict;
use warnings;
use Test::More tests => 7;
use FindBin;
use File::Temp qw(tempfile tempdir);

my $src_path = $ENV{ZMAUDIT_SRC} // "$FindBin::Bin/../../zmaudit.pl.in";
open(my $fh, '<', $src_path) or die "Cannot open $src_path: $!";
my $source = do { local $/; <$fh> };

# Find the block handling "does not point to valid target" using brace counting
my $aud_print_text = q{aud_print("Event link $day_dir/$event_link does not point to valid target at $event_path");};
my $pos = index($source, $aud_print_text);
die "aud_print not found" if $pos < 0;

$pos = index($source, "if ( confirm() ) {", $pos);
die "if ( confirm() ) not found" if $pos < 0;

my $start = $pos;
my $depth = 0;
my $i;
for ($i = $pos; $i < length($source); ++$i) {
    my $c = substr($source, $i, 1);
    if ($c eq "{") { $depth++; }
    elsif ($c eq "}") { $depth--; last if $depth == 0; }
}
my $block = substr($source, $start, $i - $start + 1);

ok($block, 'found the broken event link handling block');

# Assert $cleaned = 1 only happens inside if ( unlink($event_link) )
like($block, qr/if\s*\(\s*unlink\(\$event_link\)\s*\)\s*\{\s*\$cleaned\s*=\s*1;/,
    '$cleaned=1 only set when unlink succeeds');

# Assert bare unlink($event_link); followed by $cleaned = 1; is NOT present
unlike($block, qr/unlink\(\$event_link\);\s*\$cleaned\s*=\s*1;/,
    'no bare unlink followed by $cleaned=1');

# ---- Behaviour tests ----
sub confirm { 1 }

# Test 1: unlink fails (path in non-existent dir) -> $cleaned stays 0, error recorded
{
    my $cleaned = 0;
    my @errs;
    my $day_dir = 'd';
    my $event_link = '/nonexistent/dir/that/cannot/exist/.123';
    local *Error = sub { push @errs, $_[0] };

    eval $block;
    is($cleaned, 0, 'cleaned stays 0 when unlink fails');
    ok(@errs && $errs[0] =~ /Unable to delete broken event link/, 'error recorded on unlink failure');
}

# Test 2: unlink succeeds (real temp file) -> $cleaned becomes 1, file gone
{
    my $cleaned = 0;
    my @errs;
    my $day_dir = 'd';
    my ($fh, $target) = tempfile(UNLINK => 0);
    close $fh;
    my $tmpdir = tempdir(CLEANUP => 1);
    my $event_link = "$tmpdir/.456";
    symlink($target, $event_link) or die "symlink: $!";
    local *Error = sub { push @errs, $_[0] };

    eval $block;
    is($cleaned, 1, 'cleaned becomes 1 when unlink succeeds');
    ok(!-e $event_link, 'symlink removed on success');
}