use strict;
use warnings;
use Test::More;
use FindBin;

open(my $fh, '<', "$FindBin::Bin/../../zmaudit.pl.in") or die "zmaudit.pl.in: $!";
my $source = do { local $/; <$fh> };
close($fh);

like($source, qr/SELECT DISTINCT\s+`EventId`\s+FROM\s+`Event_Data`/s, 'Event_Data orphan select exists');
like($source, qr/EventId`\s+IS\s+NOT\s+NULL/s, 'Event_Data select excludes NULL EventId');
like($source, qr/DELETE LOW_PRIORITY FROM Event_Data WHERE EventId = \?/s, 'DELETE FROM Event_Data statement exists');

my $stats_idx = index($source, 'Checking for Orphaned Stats');
my $event_data_idx = index($source, 'Checking for Orphaned Event Data');
ok($stats_idx >= 0 && $event_data_idx > $stats_idx, 'Event_Data pass comes after the Stats pass');

done_testing();
