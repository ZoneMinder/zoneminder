use strict;
use warnings;
use Test::More tests => 5;
use FindBin;

open(my $fh, '<', "$FindBin::Bin/../../zmeventtool.pl.in") or die "zmeventtool.pl.in: $!";
my $source = do { local $/; <$fh> };
my ($renumber) = $source =~ /elsif \(\$command eq 'renumber'\) \{\n(.*?)\n  \}\n\}/s;
ok($renumber, 'found the renumber command');

# renumber inserts a new Events row and deletes the old one, and the delete
# cascades to every table with a foreign key on Events.Id
like($renumber, qr/UPDATE Events_Tags SET EventId=\?/, 'tags move to the new event');
like($renumber, qr/UPDATE Snapshots_Events SET EventId=\?/, 'snapshot membership moves to the new event');

open(my $efh, '<', "$FindBin::Bin/../lib/ZoneMinder/Event.pm") or die "Event.pm: $!";
my $event_pm = do { local $/; <$efh> };
my ($fields) = $event_pm =~ /%fields = map \{ \$_, \$_ \} qw\((.*?)\);/s;
my %has = map { $_ => 1 } split(' ', $fields // '');
ok((grep { $has{$_} } qw(Locked Latitude Longitude MaxScoreFrameId)) == 4, 'clone copies Locked, location and MaxScoreFrameId');
like($renumber, qr/UPDATE Events_Tags.*?\$event->delete\(\)/s, 'rows are moved before the old event is deleted');
