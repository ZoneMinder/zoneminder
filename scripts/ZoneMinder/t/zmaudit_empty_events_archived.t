use strict;
use warnings;
use Test::More tests => 3;
use FindBin;

open(my $fh, '<', "$FindBin::Bin/../../zmaudit.pl.in") or die "zmaudit.pl.in: $!";
my $source = do { local $/; <$fh> };
my ($select) = $source =~ /\$selectEmptyEventsSql = '(.*?)';/s;
ok($select, 'found the empty events query');
like($select // '', qr/`E`\.`Archived`/, 'the empty events query selects Archived');
like($source, qr/if \(\$\$event\{Archived\}\) \{\s*aud_print\("Not deleting because it is archived\."\);/, 'archived empty events are skipped');
