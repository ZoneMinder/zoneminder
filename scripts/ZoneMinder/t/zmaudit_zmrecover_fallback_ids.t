use strict;
use warnings;
use Test::More tests => 7;
use FindBin;

# An event zmc recorded under a fallback id, because it could not insert the
# row, is video to keep. zmaudit must leave it and the rows zmc queued for it
# alone, and zmrecover must give it a normal id when it adds the row.

sub source {
  open(my $fh, '<', "$FindBin::Bin/../../$_[0]") or die "$_[0]: $!";
  local $/;
  return <$fh>;
}

my $zmaudit = source('zmaudit.pl.in');
ok($zmaudit =~ /is_fallback_id\(\$fs_event_id\).*?next;.*?\$Event->delete_files\(\)/s,
  'zmaudit skips a fallback-id event before deleting the files of events with no row');
foreach my $table (qw(Frames Stats EventData)) {
  ok($zmaudit =~ /\$selectOrphaned${table}Sth->execute\(ZoneMinder::Event::FALLBACK_ID_MIN\(\)\)/,
    "zmaudit keeps orphaned $table rows under a fallback id");
}
is(scalar(() = $zmaudit =~ /AND `EventId` < \?'/g), 3, 'each orphan query stops below the fallback range');

my $zmrecover = source('zmrecover.pl.in');
is(scalar(() = $zmrecover =~ /saveRecovered\(\$event\)/g), 4, 'every recovered scheme saves through saveRecovered');
ok($zmrecover =~ /sub saveRecovered \{[^}]*save_with_new_id\(\) if ZoneMinder::Event::is_fallback_id\(\$\$event\{Id\}\)/,
  'saveRecovered gives a fallback-id event a new id');
